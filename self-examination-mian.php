<?php
/*
Plugin Name: My Custom Card Plugin
Description: A plugin to add cards from the admin panel.
Version: 1.0
Author: Your Name
*/

// Enqueue Bootstrap styles and scripts
function enqueue_bootstrap_assets() {
    wp_enqueue_style('prefix_bootstrap', '//cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css');
    wp_enqueue_script('prefix_bootstrap', '//cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js', array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'enqueue_bootstrap_assets');
// create cards table
function create_cards_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'cards';

    // Check if the table already exists
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        // Table not in database. Create new table
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title text NOT NULL,
            description text NOT NULL,
            image_url text NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Run the function when the plugin is activated
register_activation_hook(__FILE__, 'create_cards_table');


// Hook for adding admin menus
add_action('admin_menu', 'add_card_menu');

// action function for above hook
function add_card_menu() {
    // Add a new top-level menu
    add_menu_page('Add Card', 'Add Card', 'manage_options', 'add_card', 'add_card_page' );
}

// display the admin options page
function add_card_page() {
    ?>
    <div>
        <h2>Add Card</h2>
        <form action="" method="post" enctype="multipart/form-data">
            <p><label>Card Title: <input type="text" name="card_title" /></label></p>
            <p><label>Card Image: <input type="file" name="card_image" /></label></p>
            <p><label>Card Description: <textarea name="card_description"></textarea></label></p>
            <p><input type="submit" value="Submit" /></p>
        </form>
    </div>
    <div>
        <?php if (isset($_GET['success'])): ?>
            <div class="notice notice-success">
                <p>Card added successfully!</p>
            </div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="notice notice-error">
                <p>Error: <?php echo urldecode($_GET['error']); ?></p>
            </div>
        <?php endif; ?>

        <!-- Rest of your form goes here -->
    </div>
    <?php
}

// Hook for handling the form submission
add_action('admin_init', 'handle_card_submission');

function handle_card_submission() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['card_title'], $_POST['card_description'], $_FILES['card_image'])) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'cards'; // Use your table name

        $card_title = sanitize_text_field($_POST['card_title']);
        $card_description = sanitize_textarea_field($_POST['card_description']);
        $card_image = $_FILES['card_image'];

        // Validate and sanitize uploaded image
        $image_url = '';
        if (!empty($card_image['tmp_name'])) {
            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }
            $uploadedfile = $_FILES['card_image'];
            $upload_overrides = array('test_form' => false);
            $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
            if ($movefile && !isset($movefile['error'])) {
                $image_url = esc_url($movefile['url']);
            } else {
                wp_redirect(admin_url('admin.php?page=add_card&error=' . urlencode($movefile['error'])));
                exit;
            }
        }

        $data = array(
            'title' => $card_title,
            'description' => $card_description,
            'image_url' => $image_url, // Use the image URL from file upload
        );

        $format = array('%s', '%s', '%s'); // Data format (%s as string; more info in the wpdb documentation)

        if ($wpdb->insert($table_name, $data, $format)) {
            wp_redirect(admin_url('admin.php?page=add_card&success=true'));
        } else {
            wp_redirect(admin_url('admin.php?page=add_card&error=' . urlencode('Failed to insert card into database.')));
        }

        exit;
    }
}

// Register the shortcode
add_shortcode('show_cards', 'show_cards_shortcode');
function get_all_cards() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'cards';

    $cards = $wpdb->get_results(
        "
        SELECT *
        FROM $table_name
        "
    );

    return $cards;
}

function show_cards_shortcode($atts) {
    $cards = get_all_cards();

    $output = '<div class="card-container">'; // Adding an outer div container
    foreach ($cards as $card) {
        $output .= '
            <div class="card">
                <h2>' . esc_html($card->title) . '</h2>
                <img src="' . esc_url($card->image_url) . '" alt="' . esc_attr($card->title) . '">
                <p>' . esc_html($card->description) . '</p>
                <a href="#' . esc_attr($card->id) . '">View Details</a>
            </div>';
    }
    $output .= '</div>'; // Closing the outer div container

    return $output;
}
?>
