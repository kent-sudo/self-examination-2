<?php
/*
Plugin Name: My Custom Card Plugin
Description: A plugin to add cards from the admin panel.
Version: 1.0
Author: Your Name
*/

// Enqueue Bootstrap styles and scripts
function enqueue_bootstrap_assets() {
    wp_enqueue_style('css', plugin_dir_url(__FILE__) . 'css/styles.css');
    wp_enqueue_script('custom-card-js', plugin_dir_url(__FILE__) . 'js/custom-card.js', array('jquery'), null, false);
    wp_enqueue_style('prefix_bootstrap', '//cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css');
    wp_enqueue_script('prefix_bootstrap_js', '//cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js', array('jquery'), null, false);
    wp_enqueue_script('prefix_bootstrap_bundle', '//cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js', array('jquery'), null, false);
    wp_enqueue_script('prefix_jq', '//code.jquery.com/jquery-3.5.1.min.js', array('jquery'), null, false);
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
// create sub-cards table
function create_sub_cards_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'sub_cards';

    // Check if the table already exists
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        // Table not in database. Create new table
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            card_id text NOT NULL,
            title text NOT NULL,
            click text NOT NULL,
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
register_activation_hook(__FILE__, 'create_sub_cards_table');

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

// Hook for adding admin menus
add_action('admin_menu', 'add_sub_card_menu');

// action function for above hook
function add_sub_card_menu() {
    // Add a new sub-menu under "Add Card"
    add_submenu_page('add_card', 'Manage Sub Cards', 'Sub Cards', 'manage_options', 'manage_sub_cards', 'manage_sub_cards_page');
}

// import summernote
function enqueue_summernote_assets() {
    wp_enqueue_style('summernote-bootstrap-css','//stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css' );
    wp_enqueue_script('summernote-jquery-js', '//code.jquery.com/jquery-3.5.1.min.js', array('jquery'), null, false);
    wp_enqueue_script('summernote-bootstrap-js', '//stackpath.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js', array('jquery'), null, false);
    wp_enqueue_style('summernote-css', '//cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css');
    wp_enqueue_script('summernote-js', '//cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js', array('jquery'), null, false);
    wp_enqueue_script('summernote-init-js', plugin_dir_url(__FILE__) . 'js/summernote-init.js', array('jquery'), null, true);
}

add_action('admin_enqueue_scripts', 'enqueue_summernote_assets');

// Display the admin options page for managing sub cards
function manage_sub_cards_page() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        handle_sub_card_submission();
    }
    $cards = get_all_cards();
    ?>
    <div>
        <h2>Manage Sub Cards</h2>
        <form action="" method="post" enctype="multipart/form-data">
            <p><label>Sub Card Title: <input type="text" name="sub_card_title" /></label></p>
            <select name="sub_card_card_id">
                <option value="">Select a Card</option>
                <?php foreach ($cards as $card) : ?>
                    <option value="<?php echo esc_attr($card->id); ?>"><?php echo esc_html($card->title); ?></option>
                <?php endforeach; ?>
            </select>
            <p><label>Sub Card Click: <input type="text" name="sub_card_click" /></label></p>
            <p><label>Sub Card Image: <input type="file" name="sub_card_image" /></label></p>
            <p><label>Sub Card Description:</label></p>
            <textarea name="sub_card_description" id="summernote_kent" >
            </textarea>
            <p><input type="submit" value="Submit" /></p>
        </form>
    </div>
    <div>
        <?php if (isset($_GET['success'])): ?>
            <div class="notice notice-success">
                <p>Sub Card added successfully!</p>
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

// Handle the submission of the sub card form
// Handle the submission of the sub card form
function handle_sub_card_submission() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sub_card_title'], $_POST['sub_card_description'], $_FILES['sub_card_image'], $_POST['sub_card_card_id'])) {
        global $wpdb;

        $sub_table_name = $wpdb->prefix . 'sub_cards'; // Use your sub_cards table name

        $sub_card_title = sanitize_text_field($_POST['sub_card_title']);
        $sub_card_description = sanitize_textarea_field($_POST['sub_card_description']);
        $sub_card_card_id = intval($_POST['sub_card_card_id']);
        $sub_card_image = $_FILES['sub_card_image'];

        // Validate and sanitize uploaded image
        $image_url = '';
        if (!empty($sub_card_image['tmp_name'])) {
            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }
            $uploadedfile = $_FILES['sub_card_image'];
            $upload_overrides = array('test_form' => false);
            $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
            if ($movefile && !isset($movefile['error'])) {
                $image_url = esc_url($movefile['url']);
            } else {
                wp_redirect(admin_url('admin.php?page=manage_sub_cards&error=' . urlencode($movefile['error'])));
                exit;
            }
        }

        $data = array(
            'title' => $sub_card_title,
            'description' => $sub_card_description,
            'card_id' => $sub_card_card_id,
            'image_url' => $image_url, // Use the image URL from file upload
        );

        $format = array('%s', '%s', '%d', '%s'); // Add %d for integer data

        if ($wpdb->insert($sub_table_name, $data, $format)) {
            wp_redirect(admin_url('admin.php?page=manage_sub_cards&success=true'));
        } else {
            wp_redirect(admin_url('admin.php?page=manage_sub_cards&error=' . urlencode('Failed to insert sub card into database.')));
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

function get_all_sub_cards() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'sub_cards';

    $sub_cards = $wpdb->get_results(
        "
        SELECT *
        FROM $table_name
        "
    );

    return $sub_cards;
}

function show_cards_shortcode($atts) {
    $cards = get_all_cards();
    $sub_cards = get_all_sub_cards();

    $output = '
    <div class="row self-examination-main">
    <em style=" text-align: center;">Step 1</em>
    <h3 style=" text-align: center;">選擇設備類型</h3>'; // Adding an outer div container
    foreach ($cards as $card) {
        $output .= '
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12" style="margin-bottom:20px;">
                <!--click to move section2-->
                <a href="#section2" style=" text-decoration: none;">
                    <div class="card shadow-sm h-100" data-card-id="' . esc_attr($card->id) . '" >
                        <img src="' . esc_url($card->image_url) . '" alt="' . esc_attr($card->title) . '" width="100%" height="auto">
                        <h2>' . esc_html($card->title) . '</h2>
                        <p>' . esc_html($card->description) . '</p>
                    </div>
                </a>
            </div>';
    }

    $output .= ' <em style=" text-align: center;">Step 2</em>';
    foreach ($sub_cards as $sub_card) {
        $output .= '
        <div id="section2">
            <a href="#section3">
                <div class="card shadow-sm h-100" data-card-id="' . esc_attr($sub_card->id) . '" >
                    <img src="' . esc_url($sub_card->image_url) . '" alt="' . esc_attr($sub_card->title) . '" width="100%" height="auto">
                    <h2>' . esc_html($sub_card->title) . '</h2>
                    <p>' . esc_html($sub_card->click) . '</p>
                </div>
            </a>
        </div>';
    }

    $output .= ' <em style=" text-align: center;">Step 3</em>';
    foreach ($sub_cards as $sub_card) {
        $output .= '
        <div id="section3">
        ' . esc_html($sub_card->description) . '
        </div>
        ';
    }

    $output .= '</div>'; // Closing the outer div container
    return $output;
}
?>
