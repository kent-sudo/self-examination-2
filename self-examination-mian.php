<?php
/*
Plugin Name: KENT Card Plugin
Description: A plugin to add cards from the admin panel.
Version: 2.0
Author: KENT
*/

// Enqueue Bootstrap styles and scripts
function enqueue_bootstrap_assets() {
    wp_enqueue_style('css', plugin_dir_url(__FILE__) . 'css/styles.css');
    wp_register_script('kent-custom-card-js', plugin_dir_url(__FILE__) . 'js/custom-card.js', array('jquery'), null, false);
    wp_enqueue_script('kent-custom-card-js');
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
    add_menu_page('自我檢測', '添加母卡片', 'manage_options', 'add_card', 'add_card_page' );
}

// display the admin options page
function add_card_page() {
    ?>
    <div>
        <h2>添加卡片</h2>
        <form action="" method="post" enctype="multipart/form-data">
            <p><label>標題: <input type="text" name="card_title" /></label></p>
            <p><label>圖片: <input type="file" name="card_image" /></label></p>
            <p><label>文字: <textarea name="card_description"></textarea></label></p>
            <p><input type="submit" value="Submit" /></p>
        </form>
    </div>
    <div>
        <?php if (isset($_GET['success'])): ?>
            <div class="notice notice-success">
                <p>成功</p>
            </div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="notice notice-error">
                <p>失敗: <?php echo urldecode($_GET['error']); ?></p>
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
    add_submenu_page('add_card', '添加子卡片', '添加子卡片', 'manage_options', 'manage_sub_cards', 'manage_sub_cards_page');
}

// import summernote
function enqueue_summernote_assets() {
    wp_enqueue_style('summernote-bootstrap-css','//stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css' );
    wp_enqueue_script('summernote-jquery-js', '//code.jquery.com/jquery-3.5.1.min.js', array('jquery'), null, false);
    wp_enqueue_script('summernote-bootstrap-js', '//stackpath.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js', array('jquery'), null, false);
    wp_enqueue_style('summernote-css', '//cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css');
    wp_enqueue_script('summernote-js', '//cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js', array('jquery'), null, false);
    wp_enqueue_script('summernote-init-js', plugin_dir_url(__FILE__) . 'js/summernote-init.js', array('jquery'), null, true);
    wp_enqueue_script('summernote-zh-TW.js', plugin_dir_url(__FILE__) . 'summernote/lang/summernote-zh-TW.js', array('jquery'), null, false);
}

add_action('admin_enqueue_scripts', 'enqueue_summernote_assets');

// Display the admin options page for managing sub cards
function manage_sub_cards_page() {
    $cards = get_all_cards();
    ?>
    <div>
        <h2>Manage Sub Cards</h2>
        <form action="" method="post" enctype="multipart/form-data">
            <p><label>添加子卡片: <input type="text" name="sub_card_title" /></label></p>
            <select name="sub_card_card_id">
                <option value="">選擇母卡片</option>
                <?php foreach ($cards as $card) : ?>
                    <option value="<?php echo esc_attr($card->id); ?>"><?php echo esc_html($card->title); ?></option>
                <?php endforeach; ?>
            </select>
            <p><label>點擊區的文字: <input type="text" name="sub_card_click" /></label></p>
            <p><label>圖片: <input type="file" name="sub_card_image" /></label></p>
            <p><label>詳細的的講解:</label></p>
            <textarea name="sub_card_description" type="text" id="summernote_kent" >
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
add_action('admin_init', 'handle_sub_card_submission');
function handle_sub_card_submission() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sub_card_title'],$_POST['sub_card_click'], $_POST['sub_card_description'], $_FILES['sub_card_image'], $_POST['sub_card_card_id'])) {
        global $wpdb;

        $sub_table_name = $wpdb->prefix . 'sub_cards'; // Use your sub_cards table name

        $sub_card_title = sanitize_text_field($_POST['sub_card_title']);
        //summernote content
        $content = $_POST['sub_card_description'];
        $content = wp_kses_post($content);
        $sub_card_description = $content;
        $sub_card_click = $_POST['sub_card_click'];
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
            'click' => $sub_card_click,
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

// 添加一个新的edit菜单页面
function add_edit_card_menu() {
    add_submenu_page('add_card', '修改母卡片', '修改母卡片', 'manage_options', 'edit_card', 'edit_card_page');
}

add_action('admin_menu', 'add_edit_card_menu');
function edit_card_page()
{
    $cards = get_all_cards();
    ?>
    <div class="row self-examination-main">
        <?php
        foreach ($cards as $card) {
            ?>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12" style="margin-bottom:20px;">
                <div class="card shadow-sm h-100 main-card" data-card-id="<?php echo esc_attr($card->id); ?>">
                    <img src="<?php echo esc_html($card->image_url); ?>" alt="<?php echo esc_html($card->title); ?>" width="100%" height="250px">
                    <h2><?php echo esc_html($card->title); ?></h2>
                    <p><?php echo esc_html($card->description); ?></p>
                </div>
                <a class="btn btn-primary" href="<?php echo admin_url('admin.php?page=edit_card2&card_id=' . esc_attr($card->id)); ?>" role="button">修改</a>
                <form action="" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="card_id_edit" value="<?php echo esc_attr($card->id); ?>">
                    <p><input class="btn btn-danger" type="submit" name="delete_card" value="刪除" onclick="return confirm('您確定要刪除這張卡嗎？');" /></p>
                </form>
            </div>
            <?php
        }
        ?>
    </div>
    <?php
}

add_action('admin_init', 'handle_delete_card_submission');
// 处理编辑后的卡片数据
function handle_delete_card_submission() {
    if (isset($_POST['delete_card'])) {

        // Get the card ID to delete
        $card_id_to_delete = isset($_POST['card_id_edit']) ? intval($_POST['card_id_edit']) : 0;

        // Delete the card
        if (delete_card_by_id($card_id_to_delete)) {
            // Card deletion was successful
            wp_safe_redirect(admin_url('admin.php?page=edit_card'), 302);
        } else {
            // Error occurred during deletion
            wp_safe_redirect(admin_url('admin.php?page=edit_card2&card_id=' . esc_attr($card_id_to_delete) . '&error=delete_failed'), 302);
        }
        exit();
    }
}

// delete_card
function delete_card_by_id($card_id) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'cards';

    // Delete the card from the database
    $result = $wpdb->delete(
        $table_name,
        array('id' => $card_id),
        array('%d')
    );

    return $result !== false;
}


function add_edit_card_menu2() {
    add_submenu_page('add_card', '修改母卡片—修改', '修改母卡片-修改', 'manage_options', 'edit_card2', 'edit_card_page2');
}

add_action('admin_menu', 'add_edit_card_menu2');
function edit_card_page2()
{
    // 获取要编辑的卡片ID
    $card_id = isset($_GET['card_id']) ? intval($_GET['card_id']) : 0; // 通过URL参数获取

    // 根据卡片ID从数据库中检索卡片数据
    $card = get_card_by_id($card_id);

    if ($card) {
        // Card data is available in the $card variable.
        echo 'Card Title: ' . esc_html($card->title);
        ?>
        <!-- 表单开始 -->
        <div>
            <h2>修改母卡片</h2>
            <form action="" method="post" enctype="multipart/form-data">
                <input type="hidden" name="card_id_edit" value="<?php echo esc_attr($card->id); ?>" />
                <p><label>標題: <input type="text" name="card_title_edit" value="<?php echo esc_attr($card->title); ?>" /></label></p>
                <p><label>圖片: <input type="file" name="card_image_edit" value="<?php echo esc_attr($card->image_url); ?>" /></label></p>
                <p><label>文字: <input type="text" name="card_description_edit" value="<?php echo esc_attr($card->description); ?>" /></textarea></label></p>
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
        <!-- 表单结束 -->
        <?php
    } else {
        echo '卡片不存在或已被删除。';
    }
}

function get_card_by_id($card_id) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'cards';

    $card = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $card_id
        )
    );

    return $card;
}

add_action('admin_init', 'handle_edit_card_submission');
// 处理编辑后的卡片数据
function handle_edit_card_submission() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['card_id_edit'], $_POST['card_title_edit'], $_POST['card_description_edit'], $_FILES['card_image_edit'])) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'cards';

        $card_id = intval($_POST['card_id_edit']);
        $card_title = sanitize_text_field($_POST['card_title_edit']);
        $card_description = sanitize_textarea_field($_POST['card_description_edit']);
        $card_image_edit = $_FILES['card_image_edit'];

        // Validate and sanitize uploaded image
        $image_url = '';

        if (!empty($card_image_edit['tmp_name'])) {
            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }
            $uploadedfile = $_FILES['card_image_edit'];
            $upload_overrides = array('test_form' => false);
            $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
            if ($movefile && !isset($movefile['error'])) {
                $image_url = esc_url($movefile['url']);
            } else {
                wp_redirect(admin_url('admin.php?page=add_card&error=' . urlencode($movefile['error'])));
                exit;
            }
        }

        // Update card data in database
        $data = array(
            'title' => $card_title,
            'description' => $card_description,
            'image_url' => $image_url, // Use the image URL from file upload
        );
        $where = array('id' => $card_id);
        $format = array('%s', '%s', '%s'); // Data format (%s as string; more info in the wpdb documentation)
        $where_format = array('%d'); // Where format

        if ($wpdb->update($table_name, $data, $where, $format, $where_format)) {
            wp_redirect(admin_url('admin.php?page=edit_card2&success=true&card_id='. $card_id));
        } else {
            wp_redirect(admin_url('admin.php?page=edit_card2&error=' . urlencode('Failed to update card in database.') . '&card_id=' . $card_id));
        }
        exit;
    }
}

// 添加一个新的edit子菜单页面
function add_edit_sub_card_menu() {
    add_submenu_page('add_card', '修改子卡片', '修改子卡片', 'manage_options', 'edit_sub_card', 'edit_sub_card_page');
}

add_action('admin_menu', 'add_edit_sub_card_menu');
function edit_sub_card_page()
{
    $card = get_all_cards();
    $sub_cards = get_all_sub_cards();
    ?>
    <div class="row self-examination-main">
        <?php
        foreach ($sub_cards as $sub_card) {
            $matching_card = find_card_by_id($sub_card->card_id);
            ?>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12 sub-cards-container">
                <div class="card shadow-sm h-100 sub-card" data-card-id="' . esc_attr($sub_card->id) . '">
                    <!--card title -->
                    <h2>母卡片是<?php echo esc_html($matching_card) ?></h2>
                    <img src="<?php echo esc_html($sub_card->image_url); ?>" alt="<?php echo esc_html($sub_card->title); ?>" width="100%" height="250px">
                    <h2><?php echo esc_html($sub_card->title); ?></h2>
                    <p><?php echo esc_html($sub_card->click); ?></p>
                </div>
                <a class="btn btn-primary" href="<?php echo admin_url('admin.php?page=edit_sub_card2&sub_card_id=' . esc_attr($sub_card->id)); ?>" role="button">修改</a>
                <form action="" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="sub_card_id_edit" value="<?php echo esc_attr($sub_card->id); ?>">
                    <p><input class="btn btn-danger" type="submit" name="delete_sub_card" value="刪除" onclick="return confirm('您確定要刪除這張卡嗎？');" /></p>
                </form>
            </div>
            <?php
        }
        ?>
    </div>
    <?php
}

function find_card_by_id($card_id) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'cards';

    $card = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT title FROM $table_name WHERE id = %d",
            $card_id
        )
    );

    if ($card) {
        return $card->title; // Return the title if the card is found
    } else {
        return false; // Return false if the card is not found
    }
}


//delete sub card
add_action('admin_init', 'handle_delete_sub_card_submission');

function handle_delete_sub_card_submission() {
    if (isset($_POST['delete_sub_card'])) {

        // Get the card ID to delete
        $sub_card_id_to_delete = isset($_POST['sub_card_id_edit']) ? intval($_POST['sub_card_id_edit']) : 0;

        // Delete the card
        if (delete_sub_card_by_id($sub_card_id_to_delete)) {
            // Card deletion was successful
            wp_safe_redirect(admin_url('admin.php?page=edit_sub_card'), 302);
        } else {
            // Error occurred during deletion
            wp_safe_redirect(admin_url('admin.php?page=edit_sub_card&sub_card_id=' . esc_attr($sub_card_id_to_delete) . '&error=delete_failed'), 302);
        }
        exit();
    }
}

// delete_card
function delete_sub_card_by_id($sub_card_id) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'sub_cards';

    // Delete the card from the database
    $result = $wpdb->delete(
        $table_name,
        array('id' => $sub_card_id),
        array('%d')
    );

    return $result !== false;
}

// 处理编辑后的卡片数据
function add_edit_sub_card_menu2() {
    add_submenu_page('add_card', '修改子卡片-修改', '修改子卡片-修改', 'manage_options', 'edit_sub_card2', 'edit_sub_card_page2');
}

add_action('admin_menu', 'add_edit_sub_card_menu2');
function edit_sub_card_page2()
{
    // 获取要编辑的卡片ID
    $sub_card_id = isset($_GET['sub_card_id']) ? intval($_GET['sub_card_id']) : 0; // 通过URL参数获取

    // 根据卡片ID从数据库中检索卡片数据
    $sub_card = get_sub_card_by_id($sub_card_id);

    if ($sub_card) {
        // Card data is available in the $card variable.
        echo 'Card Title: ' . esc_html($sub_card->title);
        ?>
        <!-- 表单开始 -->
        <div>
            <h2>Edit sub Card</h2>
            <form action="" method="post" enctype="multipart/form-data">
                <input type="hidden" name="sub_card_id_edit" value="<?php echo esc_attr($sub_card->id); ?>" />
                <p><label>標題: <input type="text" name="sub_card_title_edit" value="<?php echo esc_attr($sub_card->title); ?>" /></label></p>
                <p><label>點擊的文字: <input type="text" name="sub_card_click_edit" value="<?php echo esc_attr($sub_card->click); ?>" /></label></p>
                <p><label>圖片: <input type="file" name="sub_card_image_edit" value="<?php echo esc_attr($sub_card->image_url); ?>" /></label></p>
                <p><label>詳細的講解:</label></p>
                <textarea name="sub_card_description_edit" type="text" id="summernote_kent">
                    <?php echo $sub_card->description ?>
                </textarea>
                <p><input type="submit" value="Submit" /></p>
            </form>
        </div>
        <div>
            <?php if (isset($_GET['success'])): ?>
                <div class="notice notice-success">
                    <p>成功!</p>
                </div>
            <?php elseif (isset($_GET['error'])): ?>
                <div class="notice notice-error">
                    <p>失敗: <?php echo urldecode($_GET['error']); ?></p>
                </div>
            <?php endif; ?>

            <!-- Rest of your form goes here -->
        </div>
        <!-- 表单结束 -->
        <?php
    } else {
        echo '卡片不存在或已被删除。';
    }
}

function get_sub_card_by_id($sub_card_id) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'sub_cards';

    $sub_card = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $sub_card_id
        )
    );

    return $sub_card;
}

add_action('admin_init', 'handle_edit_sub_card_submission');
// 处理编辑后的卡片数据
function handle_edit_sub_card_submission() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sub_card_id_edit'], $_POST['sub_card_title_edit'], $_POST['sub_card_click_edit'], $_POST['sub_card_description_edit'], $_FILES['sub_card_image_edit'])) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'sub_cards';
        $sub_card_id = intval($_POST['sub_card_id_edit']);
        $sub_card_title = sanitize_text_field($_POST['sub_card_title_edit']);
        $sub_card_click = sanitize_text_field($_POST['sub_card_click_edit']);
        //summernote content
        $content = $_POST['sub_card_description_edit'];
        $content = wp_kses_post($content);
        $sub_card_description = $content;
        $sub_card_image_edit = $_FILES['sub_card_image_edit'];

        // Validate and sanitize uploaded image
        $image_url = '';
        if (!empty($sub_card_image_edit['tmp_name'])) {
            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }
            $uploadedfile = $_FILES['sub_card_image_edit'];
            $upload_overrides = array('test_form' => false);
            $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
            if ($movefile && !isset($movefile['error'])) {
                $image_url = esc_url($movefile['url']);
            } else {
                wp_redirect(admin_url('admin.php?page=add_sub_card&error=' . urlencode($movefile['error'])));
                exit;
            }
        }

        // Update card data in database
        $data = array(
            'title' => $sub_card_title,
            'description' => $sub_card_description,
            'click' => $sub_card_click,
            'image_url' => $image_url, // Use the image URL from file upload
        );
        $where = array('id' => $sub_card_id);
        $format = array('%s', '%s', '%s'); // Data format (%s as string; more info in the wpdb documentation)
        $where_format = array('%d'); // Where format

        if ($wpdb->update($table_name, $data, $where, $format, $where_format)) {
            wp_redirect(admin_url('admin.php?page=edit_sub_card2&success=true&sub_card_id='. $sub_card_id));
        } else {
            wp_redirect(admin_url('admin.php?page=edit_sub_card2&error=' . urlencode('Failed to update sub card in database.') . '&card_id=' . $sub_card_id));
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
    <div class="container">
    <div class="row self-examination-main">
    <em style=" text-align: center;">Step 1</em>
    <h3 style=" text-align: center;">選擇設備類型</h3>';

    // Generating main card HTML
    foreach ($cards as $card) {
        $output .= '
            <div class="col col-6 col-sm-6 col-md-3 col-lg-3" style="margin-bottom:20px;">
                <div class="card shadow-sm h-100 kent-main-card" data-card-id="' . esc_attr($card->id) . '">
                    <img src="' . esc_url($card->image_url) . '" alt="' . esc_attr($card->title) . '" width="100%" height="auto">
                    <h5>' . esc_html($card->title) . '</h5>
                    <h7 style="color: #3f596b;font-size: 7px;">' . esc_html($card->description) . '</h7>
                </div>
            </div>';
    }

    // Generating sub-card container HTML
    $output .= ' <em style=" text-align: center;">Step 2 </em>
                <h3 style=" text-align: center;">選擇發生的狀況類型</h3>';
    foreach ($sub_cards as $sub_card) {
        $output .= '
            <div class="col col-4 col-sm-4 col-md-2 col-lg-2 kent-sub-cards-container " style="display: none;" data-card-id="' . esc_attr($sub_card->card_id) . '">
                <div class="card shadow-sm h-100 kent-sub-card" data-card-id="' . esc_attr($sub_card->id) . '">
                    <img src="' . esc_url($sub_card->image_url) . '" alt="' . esc_attr($sub_card->title) . '" width="100%" height="auto">
                    <h5>' . esc_html($sub_card->title) . '</h5>
                    <h7 style="color: #3f596b;font-size: 7px;">' . esc_html($sub_card->click) . '</h7>
                </div>
            </div>';
    }

    // Generating sub-card description HTML
    $output .= ' <em style=" text-align: center;">Step 3</em>
                <h3 style=" text-align: center;">故障狀況&解決方式</h3>';
    foreach ($sub_cards as $sub_card) {
    $decoded_string = html_entity_decode($sub_card->description );
        $output .= '
        <div class="kent-sub-card-description"  style="display: none; text-align: ;" data-card-id="' . esc_attr($sub_card->id) . '">
            '.$sub_card->description.'
        </div>';
    }

    $output .= '</div></div>'; // Closing the outer div container

    return $output;
}
?>
