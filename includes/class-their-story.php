<?php

if (!defined('ABSPATH')) {
    exit;
}

class Their_Story {
    
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_menu', array($this, 'remove_admin_menu_items'), 999);
        add_action('pre_get_posts', array($this, 'exclude_story_pages_from_admin_pages_list'));
        add_filter('login_redirect', array($this, 'redirect_after_login'), 10, 3);
        add_action('init', array($this, 'add_storyteller_role'));
        add_action('init', array($this, 'register_story_submission_post_type'));
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_story_link_redirect'));
        add_action('template_redirect', array($this, 'handle_book_closed_page'));
        add_action('template_redirect', array($this, 'handle_csv_export'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_filter('admin_body_class', array($this, 'storyteller_dashboard_body_class'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('wp_ajax_their_story_create_story', array($this, 'ajax_create_story'));
        add_action('wp_ajax_their_story_delete_story', array($this, 'ajax_delete_story'));
        add_action('wp_ajax_their_story_update_password', array($this, 'ajax_update_password'));
        add_action('wp_ajax_their_story_submit_message', array($this, 'ajax_submit_message'));
        add_action('wp_ajax_nopriv_their_story_submit_message', array($this, 'ajax_submit_message'));
        add_action('wp_ajax_their_story_moderate_submission', array($this, 'ajax_moderate_submission'));
        add_action('wp_ajax_their_story_close_story', array($this, 'ajax_close_story'));
        add_action('wp_ajax_their_story_reopen_story', array($this, 'ajax_reopen_story'));
        add_action('admin_init', array($this, 'restrict_admin_access'));
        add_filter('show_admin_bar', array($this, 'hide_admin_bar_for_storytellers'));
        add_action('in_admin_header', array($this, 'suppress_storyteller_dashboard_notices'), 99999);
        add_filter('the_content', array($this, 'add_story_page_content'), 20);
        add_filter('post_password_required', array($this, 'bypass_password_for_owner'), 10, 2);
        
        if (class_exists('WooCommerce')) {
            add_filter('woocommerce_prevent_admin_access', array($this, 'allow_storyteller_admin_access'));
            add_filter('woocommerce_add_to_cart_redirect', array($this, 'preserve_story_id_in_cart_redirect'));
            add_action('woocommerce_add_to_cart', array($this, 'store_story_id_in_cart_item'), 10, 6);
            add_action('woocommerce_before_single_product', array($this, 'preserve_story_id_on_product_page'));
            add_action('woocommerce_before_add_to_cart_button', array($this, 'display_story_info_on_product_page'));
            add_filter('woocommerce_cart_item_name', array($this, 'add_story_name_to_cart_item'), 10, 3);
            add_action('woocommerce_before_single_product_summary', array($this, 'auto_select_variation_by_message_count'), 5);
            add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_story_id_to_order_item'), 10, 4);
            add_action('woocommerce_new_order', array($this, 'add_story_details_to_order_note'), 10, 1);
            add_action('woocommerce_email_order_details', array($this, 'add_story_details_to_email'), 20, 4);
        }
    }
    
    public function add_storyteller_role() {
        add_role(
            'storyteller',
            __('Storyteller', 'their-story'),
            array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'publish_posts' => false,
                'upload_files' => true,
            )
        );
    }
    
    public function register_story_submission_post_type() {
        register_post_type('story_submission', array(
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'supports' => array('title', 'editor', 'thumbnail'),
        ));
    }
    
    public function add_rewrite_rules() {
        add_rewrite_rule('^story/([^/]+)/?$', 'index.php?their_story_link=$matches[1]', 'top');
        add_rewrite_rule('^book-closed/?$', 'index.php?their_story_book_closed=1', 'top');
    }
    
    public function flush_rewrite_rules() {
        $this->add_rewrite_rules();
        flush_rewrite_rules();
    }
    
    public function add_query_vars($vars) {
        $vars[] = 'their_story_link';
        $vars[] = 'their_story_book_closed';
        return $vars;
    }
    
    public function handle_story_link_redirect() {
        $story_link = get_query_var('their_story_link');
        if ($story_link) {
            $args = array(
                'post_type' => 'page',
                'posts_per_page' => 1,
                'meta_query' => array(
                    array(
                        'key' => '_story_unique_link',
                        'value' => $story_link,
                        'compare' => '='
                    )
                )
            );
            
            $posts = get_posts($args);
            if (!empty($posts)) {
                $story = $posts[0];
                $story_url = get_permalink($story->ID);
                wp_redirect($story_url, 301);
                exit;
            } else {
                wp_die(__('Story not found.', 'their-story'), __('Not Found', 'their-story'), array('response' => 404));
            }
        }
    }
    
    public function handle_book_closed_page() {
        $book_closed = get_query_var('their_story_book_closed');
        if ($book_closed) {
            $story_id = isset($_GET['story']) ? intval($_GET['story']) : 0;
            $message_count = isset($_GET['messages']) ? intval($_GET['messages']) : 0;
            
            if ($story_id) {
                setcookie('their_story_id', $story_id, time() + (86400 * 30), '/'); 
                $_COOKIE['their_story_id'] = $story_id;
            }
            
            $template_path = THEIR_STORY_PLUGIN_DIR . 'templates/book-closed.php';
            if (file_exists($template_path)) {
                include $template_path;
                exit;
            } else {
                wp_die(__('Book closed page template not found.', 'their-story'), __('Not Found', 'their-story'), array('response' => 404));
            }
        }
    }
    
    public function add_admin_menu() {
        $current_user = wp_get_current_user();
        
        if (in_array('storyteller', $current_user->roles)) {
            add_menu_page(
                __('My Stories', 'their-story'),
                __('My Stories', 'their-story'),
                'read',
                'their-story-dashboard',
                array($this, 'render_storyteller_dashboard'),
                'dashicons-book-alt',
                30
            );
        }
        
        if (in_array('administrator', $current_user->roles)) {
            add_menu_page(
                __('All Stories', 'their-story'),
                __('All Stories', 'their-story'),
                'manage_options',
                'their-story-admin',
                array($this, 'render_admin_dashboard'),
                'dashicons-book',
                30
            );
            
            add_submenu_page(
                'their-story-admin',
                __('Submissions', 'their-story'),
                __('Submissions', 'their-story'),
                'manage_options',
                'their-story-submissions',
                array($this, 'render_submissions_page')
            );

            add_submenu_page(
                'their-story-admin',
                __('Story pages', 'their-story'),
                __('Story pages', 'their-story'),
                'manage_options',
                'their-story-story-pages',
                array($this, 'render_story_pages_list')
            );
        }
    }

    /**
     * Hide Their Story pages from the main Pages admin list (they remain editable here and via direct URL).
     */
    public function exclude_story_pages_from_admin_pages_list($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        global $pagenow;
        if ($pagenow !== 'edit.php') {
            return;
        }
        $post_type = isset($_GET['post_type']) ? sanitize_key(wp_unslash($_GET['post_type'])) : 'post';
        if ($post_type !== 'page') {
            return;
        }
        if (!current_user_can('edit_pages')) {
            return;
        }
        $exclude = array(
            'key' => '_storyteller_id',
            'compare' => 'NOT EXISTS',
        );
        $existing = $query->get('meta_query');
        if (empty($existing)) {
            $query->set('meta_query', array($exclude));
            return;
        }
        $query->set('meta_query', array(
            'relation' => 'AND',
            $existing,
            $exclude,
        ));
    }

    public function render_story_pages_list() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'their-story'));
        }
        $stories = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'orderby' => 'modified',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => '_storyteller_id',
                    'compare' => 'EXISTS',
                ),
            ),
        ));
        include THEIR_STORY_PLUGIN_DIR . 'templates/admin-story-pages.php';
    }
    
    public function remove_admin_menu_items() {
        $current_user = wp_get_current_user();
        
        if (in_array('storyteller', $current_user->roles)) {
            remove_menu_page('index.php');
            remove_menu_page('edit.php');
            remove_menu_page('upload.php');
            remove_menu_page('edit.php?post_type=page');
            remove_menu_page('edit-comments.php');
            remove_menu_page('themes.php');
            remove_menu_page('plugins.php');
            remove_menu_page('users.php');
            remove_menu_page('tools.php');
            remove_menu_page('options-general.php');
            
            global $menu;
            foreach ($menu as $key => $item) {
                if (isset($item[2]) && $item[2] !== 'their-story-dashboard') {
                    remove_menu_page($item[2]);
                }
            }
        }
    }
    
    public function restrict_admin_access() {
        $current_user = wp_get_current_user();
        
        if (in_array('storyteller', $current_user->roles)) {
            global $pagenow;
            
            if ($pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === 'their-story-dashboard') {
                return;
            }
            
            if ($pagenow === 'admin-ajax.php') {
                return;
            }
            
            if (is_admin() && !wp_doing_ajax()) {
                wp_redirect(admin_url('admin.php?page=their-story-dashboard'));
                exit;
            }
        }
    }
    
    public function hide_admin_bar_for_storytellers($show) {
        $user = wp_get_current_user();
        if ($user->ID && in_array('storyteller', (array) $user->roles, true)) {
            return false;
        }
        return $show;
    }

    /**
     * Whether the current request is the Storyteller My Stories admin screen.
     */
    private function is_storyteller_dashboard_screen() {
        if (!is_user_logged_in()) {
            return false;
        }
        if (!in_array('storyteller', (array) wp_get_current_user()->roles, true)) {
            return false;
        }
        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        return $page === 'their-story-dashboard';
    }

    public function suppress_storyteller_dashboard_notices() {
        if (!$this->is_storyteller_dashboard_screen() || !is_admin()) {
            return;
        }
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
        remove_all_actions('network_admin_notices');
        remove_all_actions('user_admin_notices');
    }
    
    public function render_storyteller_dashboard() {
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $stories = $this->get_user_stories($user_id);
        include THEIR_STORY_PLUGIN_DIR . 'templates/storyteller-dashboard.php';
    }
    
    public function render_admin_dashboard() {
        $stories = $this->get_all_stories();
        $pending_count = $this->get_pending_submissions_count();
        include THEIR_STORY_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }
    
    public function render_submissions_page() {
        $submissions = $this->get_all_submissions();
        include THEIR_STORY_PLUGIN_DIR . 'templates/admin-submissions.php';
    }
    
    public function get_pending_submissions_count() {
        $args = array(
            'post_type' => 'story_submission',
            'post_status' => 'pending',
            'posts_per_page' => -1,
            'fields' => 'ids'
        );
        
        $pending = get_posts($args);
        return count($pending);
    }
    
    public function get_user_stories($user_id) {
        $args = array(
            'post_type' => 'page',
            'post_status' => 'any',
            'meta_query' => array(
                array(
                    'key' => '_storyteller_id',
                    'value' => $user_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        return get_posts($args);
    }
    
    public function get_all_stories() {
        $args = array(
            'post_type' => 'page',
            'post_status' => 'any',
            'meta_query' => array(
                array(
                    'key' => '_storyteller_id',
                    'compare' => 'EXISTS'
                )
            ),
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $stories = get_posts($args);
        
        foreach ($stories as $story) {
            $storyteller_id = get_post_meta($story->ID, '_storyteller_id', true);
            $story->storyteller = get_userdata($storyteller_id);
            $story->unique_link = get_post_meta($story->ID, '_story_unique_link', true);
            $story->is_closed = get_post_meta($story->ID, '_story_closed', true) === '1';
        }
        
        return $stories;
    }
    
    public function redirect_after_login($redirect_to, $requested_redirect_to, $user) {
        if (isset($user->roles) && in_array('storyteller', $user->roles)) {
            return admin_url('admin.php?page=their-story-dashboard');
        }
        return $redirect_to;
    }
    
    public function ajax_create_story() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'their_story_create_story')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'their-story')));
        }
        
        $current_user = wp_get_current_user();
        if (!in_array('storyteller', $current_user->roles)) {
            wp_send_json_error(array('message' => __('You do not have permission to create stories.', 'their-story')));
        }
        
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $password = isset($_POST['password']) ? sanitize_text_field($_POST['password']) : '';
        
        if (empty($title)) {
            wp_send_json_error(array('message' => __('Story title is required.', 'their-story')));
        }
        
        $page_data = array(
            'post_title'    => $title,
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_author'   => $current_user->ID,
        );
        
        if (!empty($password)) {
            $page_data['post_password'] = $password;
        }
        
        $page_id = wp_insert_post($page_data);
        
        if (is_wp_error($page_id)) {
            wp_send_json_error(array('message' => __('Error creating story: ', 'their-story') . $page_id->get_error_message()));
        }
        
        update_post_meta($page_id, '_storyteller_id', $current_user->ID);
        
        $unique_link = $this->generate_unique_link($page_id);
        update_post_meta($page_id, '_story_unique_link', $unique_link);
        
        wp_send_json_success(array(
            'message' => __('Story created successfully!', 'their-story'),
            'page_id' => $page_id,
            'unique_link' => $unique_link
        ));
    }
    
    public function ajax_delete_story() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'their_story_delete_story')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'their-story')));
        }
        
        $current_user = wp_get_current_user();
        $story_id = isset($_POST['story_id']) ? intval($_POST['story_id']) : 0;
        
        if (!$story_id) {
            wp_send_json_error(array('message' => __('Invalid story ID.', 'their-story')));
        }
        
        $story = get_post($story_id);
        if (!$story) {
            wp_send_json_error(array('message' => __('Story not found.', 'their-story')));
        }
        
        if (in_array('administrator', $current_user->roles)) {
            $can_delete = true;
        } elseif (in_array('storyteller', $current_user->roles)) {
            $storyteller_id = get_post_meta($story_id, '_storyteller_id', true);
            $can_delete = ($storyteller_id == $current_user->ID);
        } else {
            $can_delete = false;
        }
        
        if (!$can_delete) {
            wp_send_json_error(array('message' => __('You do not have permission to delete this story.', 'their-story')));
        }
        
        $result = wp_delete_post($story_id, true);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Story deleted successfully.', 'their-story')));
        } else {
            wp_send_json_error(array('message' => __('Error deleting story.', 'their-story')));
        }
    }
    
    private function generate_unique_link($page_id) {
        $random = wp_generate_password(16, false);
        $unique_id = base64_encode($page_id . '-' . $random);
        $unique_id = rtrim(strtr($unique_id, '+/', '-_'), '=');
        return $unique_id;
    }
    
    public function get_story_url_from_link($unique_link) {
        if (empty($unique_link)) {
            return '';
        }
        return home_url('/story/' . $unique_link . '/');
    }
    
    public function storyteller_dashboard_body_class($classes) {
        if (!is_user_logged_in() || !in_array('storyteller', (array) wp_get_current_user()->roles, true)) {
            return $classes;
        }
        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        if ($page !== 'their-story-dashboard') {
            return $classes;
        }
        return $classes . ' their-story-storyteller-screen';
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'their-story') === false && strpos($hook, 'their_story') === false) {
            return;
        }
        
        $css_file = THEIR_STORY_PLUGIN_DIR . 'assets/css/admin.css';
        $css_version = file_exists($css_file) ? filemtime($css_file) : THEIR_STORY_VERSION;
        wp_enqueue_style(
            'their-story-admin',
            THEIR_STORY_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            $css_version
        );

        if ($hook === 'toplevel_page_their-story-dashboard') {
            wp_enqueue_style(
                'their-story-font-inter',
                'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
                array(),
                null
            );
            $storyteller_css = THEIR_STORY_PLUGIN_DIR . 'assets/css/storyteller-dashboard.css';
            $storyteller_ver = file_exists($storyteller_css) ? filemtime($storyteller_css) : THEIR_STORY_VERSION;
            wp_enqueue_style(
                'their-story-storyteller',
                THEIR_STORY_PLUGIN_URL . 'assets/css/storyteller-dashboard.css',
                array('their-story-admin', 'their-story-font-inter'),
                $storyteller_ver
            );
        }
        
        $js_file = THEIR_STORY_PLUGIN_DIR . 'assets/js/admin.js';
        $js_version = file_exists($js_file) ? filemtime($js_file) : THEIR_STORY_VERSION;
        wp_enqueue_script(
            'their-story-admin',
            THEIR_STORY_PLUGIN_URL . 'assets/js/admin.js',
            array(),
            $js_version,
            true
        );
        
        wp_localize_script('their-story-admin', 'theirStoryAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('their_story_create_story'),
            'deleteNonce' => wp_create_nonce('their_story_delete_story'),
            'passwordNonce' => wp_create_nonce('their_story_update_password'),
            'moderateNonce' => wp_create_nonce('their_story_moderate_submission'),
            'reopenNonce' => wp_create_nonce('their_story_reopen_story')
        ));
    }
    
    public function enqueue_frontend_assets() {
        if (is_page()) {
            global $post;
            $storyteller_id = get_post_meta($post->ID, '_storyteller_id', true);
            if ($storyteller_id) {
                wp_enqueue_style(
                    'their-story-font-inter',
                    'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
                    array(),
                    null
                );
                $css_file = THEIR_STORY_PLUGIN_DIR . 'assets/css/frontend.css';
                $css_version = file_exists($css_file) ? filemtime($css_file) : THEIR_STORY_VERSION;
                wp_enqueue_style(
                    'their-story-frontend',
                    THEIR_STORY_PLUGIN_URL . 'assets/css/frontend.css',
                    array('their-story-font-inter'),
                    $css_version
                );
                
                $js_file = THEIR_STORY_PLUGIN_DIR . 'assets/js/frontend.js';
                $js_version = file_exists($js_file) ? filemtime($js_file) : THEIR_STORY_VERSION;
                wp_enqueue_script(
                    'their-story-frontend',
                    THEIR_STORY_PLUGIN_URL . 'assets/js/frontend.js',
                    array(),
                    $js_version,
                    true
                );
                
                $current_user = wp_get_current_user();
                $can_moderate = in_array('administrator', $current_user->roles);
                
                $storyteller_id = get_post_meta($post->ID, '_storyteller_id', true);
                $is_storyteller = in_array('storyteller', $current_user->roles) && ($storyteller_id == $current_user->ID);
                $can_close_story = $can_moderate || $is_storyteller;
                
                wp_localize_script('their-story-frontend', 'theirStoryFrontend', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'storyId' => $post->ID,
                    'submitNonce' => wp_create_nonce('their_story_submit_message'),
                    'moderateNonce' => wp_create_nonce('their_story_moderate_submission'),
                    'closeNonce' => wp_create_nonce('their_story_close_story'),
                    'canModerate' => $can_moderate,
                    'canCloseStory' => $can_close_story
                ));
            }
        }
    }
    
    public function bypass_password_for_owner($required, $post) {
        if (!$required || !$post) {
            return $required;
        }
        
        $current_user = wp_get_current_user();
        if (!$current_user || !$current_user->ID) {
            return $required;
        }
        
        $storyteller_id = get_post_meta($post->ID, '_storyteller_id', true);
        if ($storyteller_id && $storyteller_id == $current_user->ID) {
            return false;
        }
        
        return $required;
    }
    
    public function add_story_page_content($content) {
        if (!is_page() || !is_singular()) {
            return $content;
        }
        
        global $post;
        $storyteller_id = get_post_meta($post->ID, '_storyteller_id', true);
        
        if (!$storyteller_id) {
            return $content;
        }
        
        if (post_password_required($post)) {
            return $content;
        }
        
        ob_start();
        include THEIR_STORY_PLUGIN_DIR . 'templates/story-page-content.php';
        $story_content = ob_get_clean();
        
        if (empty(trim($story_content))) {
            return $content;
        }
        
        return $story_content;
    }
    
    public function ajax_update_password() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'their_story_update_password')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'their-story')));
        }
        
        $current_user = wp_get_current_user();
        $story_id = isset($_POST['story_id']) ? intval($_POST['story_id']) : 0;
        $password = isset($_POST['password']) ? sanitize_text_field($_POST['password']) : '';
        
        if (!$story_id) {
            wp_send_json_error(array('message' => __('Invalid story ID.', 'their-story')));
        }
        
        $story = get_post($story_id);
        if (!$story) {
            wp_send_json_error(array('message' => __('Story not found.', 'their-story')));
        }
        
        $can_update = false;
        if (in_array('administrator', $current_user->roles)) {
            $can_update = true;
        } elseif (in_array('storyteller', $current_user->roles)) {
            $storyteller_id = get_post_meta($story_id, '_storyteller_id', true);
            if ($storyteller_id == $current_user->ID) {
                $can_update = true;
            }
        }
        
        if (!$can_update) {
            wp_send_json_error(array('message' => __('You do not have permission to update this story.', 'their-story')));
        }
        
        $update_data = array(
            'ID' => $story_id
        );
        
        if (empty($password)) {
            $update_data['post_password'] = '';
        } else {
            $update_data['post_password'] = $password;
        }
        
        $result = wp_update_post($update_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => __('Error updating password.', 'their-story')));
        }
        
        wp_send_json_success(array('message' => __('Password updated successfully.', 'their-story')));
    }
    
    public function ajax_submit_message() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'their_story_submit_message')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'their-story')));
        }
        
        $story_id = isset($_POST['story_id']) ? intval($_POST['story_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        
        if (!$story_id) {
            wp_send_json_error(array('message' => __('Invalid story ID.', 'their-story')));
        }
        
        if (empty($name)) {
            wp_send_json_error(array('message' => __('Name is required.', 'their-story')));
        }
        
        if (empty($message)) {
            wp_send_json_error(array('message' => __('Message is required.', 'their-story')));
        }
        
        $story = get_post($story_id);
        if (!$story) {
            wp_send_json_error(array('message' => __('Story not found.', 'their-story')));
        }
        
        $is_closed = get_post_meta($story_id, '_story_closed', true) === '1';
        if ($is_closed) {
            wp_send_json_error(array('message' => __('This story is closed. No new messages can be added.', 'their-story')));
        }
        
        $image_ids = array();
        
        if (!empty($_FILES['images']['name']) && is_array($_FILES['images']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            
            $file_count = count($_FILES['images']['name']);
            $file_count = min($file_count, 5);
            
            for ($i = 0; $i < $file_count; $i++) {
                if (!empty($_FILES['images']['name'][$i])) {
                    $file = array(
                        'name' => $_FILES['images']['name'][$i],
                        'type' => $_FILES['images']['type'][$i],
                        'tmp_name' => $_FILES['images']['tmp_name'][$i],
                        'error' => $_FILES['images']['error'][$i],
                        'size' => $_FILES['images']['size'][$i]
                    );
                    
                    $upload = wp_handle_upload($file, array('test_form' => false));
                    if (!isset($upload['error'])) {
                        $attachment = array(
                            'post_mime_type' => $upload['type'],
                            'post_title' => sanitize_file_name(pathinfo($upload['file'], PATHINFO_FILENAME)),
                            'post_content' => '',
                            'post_status' => 'inherit'
                        );
                        $image_id = wp_insert_attachment($attachment, $upload['file'], $story_id);
                        $attach_data = wp_generate_attachment_metadata($image_id, $upload['file']);
                        wp_update_attachment_metadata($image_id, $attach_data);
                        $image_ids[] = $image_id;
                    }
                }
            }
        }
        
        $submission_data = array(
            'post_title' => $name,
            'post_content' => $message,
            'post_status' => 'pending',
            'post_type' => 'story_submission',
            'post_parent' => $story_id,
        );
        
        $submission_id = wp_insert_post($submission_data);
        
        if (is_wp_error($submission_id)) {
            wp_send_json_error(array('message' => __('Error submitting message.', 'their-story')));
        }
        
        update_post_meta($submission_id, '_submission_name', $name);
        update_post_meta($submission_id, '_submission_story_id', $story_id);
        if (!empty($image_ids)) {
            update_post_meta($submission_id, '_submission_image_ids', $image_ids);
        }
        
        $this->invalidate_csv_cache($story_id);
        
        wp_send_json_success(array('message' => __('Your message has been submitted and is awaiting approval.', 'their-story')));
    }
    
    public function ajax_moderate_submission() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'their_story_moderate_submission')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'their-story')));
        }
        
        $current_user = wp_get_current_user();
        $submission_id = isset($_POST['submission_id']) ? intval($_POST['submission_id']) : 0;
        $action = isset($_POST['moderate_action']) ? sanitize_text_field($_POST['moderate_action']) : '';
        
        if (!$submission_id || !in_array($action, array('approve', 'delete'))) {
            wp_send_json_error(array('message' => __('Invalid request.', 'their-story')));
        }
        
        $submission = get_post($submission_id);
        if (!$submission || $submission->post_type !== 'story_submission') {
            wp_send_json_error(array('message' => __('Submission not found.', 'their-story')));
        }
        
        $can_moderate = in_array('administrator', $current_user->roles);
        
        if (!$can_moderate) {
            wp_send_json_error(array('message' => __('You do not have permission to moderate this submission.', 'their-story')));
        }
        
        $story_id = get_post_meta($submission_id, '_submission_story_id', true);
        if ($story_id) {
            $is_closed = get_post_meta($story_id, '_story_closed', true) === '1';
            if ($is_closed) {
                if ($action === 'approve') {
                    wp_send_json_error(array('message' => __('This story is closed. Submissions cannot be approved.', 'their-story')));
                } elseif ($action === 'delete') {
                    wp_send_json_error(array('message' => __('This story is closed. Submissions cannot be deleted.', 'their-story')));
                }
            }
        }
        
        if ($action === 'approve') {
            wp_update_post(array(
                'ID' => $submission_id,
                'post_status' => 'publish'
            ));
            
            if ($story_id) {
                $this->invalidate_csv_cache($story_id);
            }
            
            wp_send_json_success(array('message' => __('Submission approved.', 'their-story')));
        } elseif ($action === 'delete') {
            $image_ids = get_post_meta($submission_id, '_submission_image_ids', true);
            if (!empty($image_ids) && is_array($image_ids)) {
                foreach ($image_ids as $image_id) {
                    if ($image_id) {
                        wp_delete_attachment($image_id, true);
                    }
                }
            }
            wp_delete_post($submission_id, true);
            
            if ($story_id) {
                $this->invalidate_csv_cache($story_id);
            }
            
            wp_send_json_success(array('message' => __('Submission deleted.', 'their-story')));
        }
    }
    
    public function get_story_submissions($story_id, $approved_only = false) {
        return self::get_story_submissions_static($story_id, $approved_only);
    }
    
    public static function get_story_submissions_static($story_id, $approved_only = false) {
        $args = array(
            'post_type' => 'story_submission',
            'post_status' => $approved_only ? 'publish' : array('publish', 'pending'),
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => '_submission_story_id',
                    'value' => $story_id,
                    'compare' => '='
                )
            )
        );
        
        $submissions = get_posts($args);
        
        foreach ($submissions as $submission) {
            $submission->submission_name = get_post_meta($submission->ID, '_submission_name', true);
            $image_ids = get_post_meta($submission->ID, '_submission_image_ids', true);
            $submission->submission_image_ids = (!empty($image_ids) && is_array($image_ids)) ? $image_ids : array();
        }
        
        return $submissions;
    }
    
    public function get_all_submissions() {
        $args = array(
            'post_type' => 'story_submission',
            'post_status' => array('publish', 'pending'),
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $submissions = get_posts($args);
        
        foreach ($submissions as $submission) {
            $submission->submission_name = get_post_meta($submission->ID, '_submission_name', true);
            $image_ids = get_post_meta($submission->ID, '_submission_image_ids', true);
            $submission->submission_image_ids = (!empty($image_ids) && is_array($image_ids)) ? $image_ids : array();
            $story_id = get_post_meta($submission->ID, '_submission_story_id', true);
            $submission->story_id = $story_id;
            if ($story_id) {
                $story = get_post($story_id);
                $submission->story = $story;
                $storyteller_id = get_post_meta($story_id, '_storyteller_id', true);
                if ($storyteller_id) {
                    $submission->storyteller = get_userdata($storyteller_id);
                }
            }
        }
        
        return $submissions;
    }
    
    public function ajax_close_story() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'their_story_close_story')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'their-story')));
        }
        
        $current_user = wp_get_current_user();
        $story_id = isset($_POST['story_id']) ? intval($_POST['story_id']) : 0;
        
        if (!$story_id) {
            wp_send_json_error(array('message' => __('Invalid story ID.', 'their-story')));
        }
        
        $story = get_post($story_id);
        if (!$story) {
            wp_send_json_error(array('message' => __('Story not found.', 'their-story')));
        }
        
        $can_close = false;
        if (in_array('administrator', $current_user->roles)) {
            $can_close = true;
        } elseif (in_array('storyteller', $current_user->roles)) {
            $storyteller_id = get_post_meta($story_id, '_storyteller_id', true);
            if ($storyteller_id == $current_user->ID) {
                $can_close = true;
            }
        }
        
        if (!$can_close) {
            wp_send_json_error(array('message' => __('You do not have permission to close this story.', 'their-story')));
        }
        
        update_post_meta($story_id, '_story_closed', '1');
        
        wp_send_json_success(array('message' => __('Story closed successfully.', 'their-story')));
    }
    
    public function ajax_reopen_story() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'their_story_reopen_story')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'their-story')));
        }
        
        $current_user = wp_get_current_user();
        $story_id = isset($_POST['story_id']) ? intval($_POST['story_id']) : 0;
        
        if (!$story_id) {
            wp_send_json_error(array('message' => __('Invalid story ID.', 'their-story')));
        }
        
        $story = get_post($story_id);
        if (!$story) {
            wp_send_json_error(array('message' => __('Story not found.', 'their-story')));
        }
        
        $can_reopen = in_array('administrator', $current_user->roles);
        
        if (!$can_reopen) {
            wp_send_json_error(array('message' => __('You do not have permission to reopen this story.', 'their-story')));
        }
        
        delete_post_meta($story_id, '_story_closed');
        
        wp_send_json_success(array('message' => __('Story reopened successfully.', 'their-story')));
    }
    
    public function preserve_story_id_in_cart_redirect($url) {
        $story_id = isset($_GET['story']) ? intval($_GET['story']) : (isset($_COOKIE['their_story_id']) ? intval($_COOKIE['their_story_id']) : 0);
        if ($story_id) {
            $url = add_query_arg('story', $story_id, $url);
        }
        return $url;
    }
    
    public function store_story_id_in_cart_item($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
        $story_id = isset($_GET['story']) ? intval($_GET['story']) : (isset($_COOKIE['their_story_id']) ? intval($_COOKIE['their_story_id']) : 0);
        if ($story_id) {
            WC()->cart->cart_contents[$cart_item_key]['their_story_id'] = $story_id;
            setcookie('their_story_id', $story_id, time() + (86400 * 30), '/');
        }
    }
    
    public function preserve_story_id_on_product_page() {
        $story_id = isset($_GET['story']) ? intval($_GET['story']) : (isset($_COOKIE['their_story_id']) ? intval($_COOKIE['their_story_id']) : 0);
        if ($story_id) {
            setcookie('their_story_id', $story_id, time() + (86400 * 30), '/');
            $_COOKIE['their_story_id'] = $story_id;
            
            add_action('wp_footer', function() use ($story_id) {
                ?>
                <script>
                (function() {
                    const storyId = <?php echo intval($story_id); ?>;
                    
                    const addToCartForms = document.querySelectorAll('form.cart, form.variations_form');
                    addToCartForms.forEach(function(form) {
                        let storyInput = form.querySelector('input[name="story"]');
                        if (!storyInput) {
                            storyInput = document.createElement('input');
                            storyInput.type = 'hidden';
                            storyInput.name = 'story';
                            form.appendChild(storyInput);
                        }
                        storyInput.value = storyId;
                    });
                    
                    const productLinks = document.querySelectorAll('a[href*="/product/"]');
                    productLinks.forEach(function(link) {
                        try {
                            const url = new URL(link.href);
                            if (!url.searchParams.has('story')) {
                                url.searchParams.set('story', storyId);
                                link.href = url.toString();
                            }
                        } catch(e) {
                        }
                    });
                })();
                </script>
                <?php
            }, 999);
        }
    }
    
    public function display_story_info_on_product_page() {
        $story_id = isset($_GET['story']) ? intval($_GET['story']) : (isset($_COOKIE['their_story_id']) ? intval($_COOKIE['their_story_id']) : 0);
        if ($story_id) {
            $story = get_post($story_id);
            if ($story) {
                $story_title = get_the_title($story_id);
                $story_title = str_replace('Protected: ', '', $story_title);
                $message_count = isset($_GET['messages']) ? intval($_GET['messages']) : 0;
                if (!$message_count) {
                    $approved_submissions = Their_Story::get_story_submissions_static($story_id, true);
                    $message_count = count($approved_submissions);
                }
                ?>
                <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
                <div class="their-story-product-info" style="background: #f0f6fc; border: 1px solid #c3d4e6; border-radius: 8px; padding: 20px; margin-bottom: 20px; font-family: 'Poppins', sans-serif;">
                    <p style="margin: 0 0 10px 0; font-size: 1.125rem; font-weight: 600; color: #1a1a1a;">
                        <?php echo esc_html__('Book for:', 'their-story'); ?> <strong><?php echo esc_html($story_title); ?></strong>
                    </p>
                    <?php if ($message_count > 0) : ?>
                        <p style="margin: 0; font-size: 0.875rem; color: #666;">
                            <?php printf(esc_html__('This story contains %d message(s).', 'their-story'), $message_count); ?>
                        </p>
                    <?php endif; ?>
                </div>
                <?php
            }
        }
    }
    
    public function add_story_name_to_cart_item($name, $cart_item, $cart_item_key) {
        if (isset($cart_item['their_story_id']) && $cart_item['their_story_id']) {
            $story_id = $cart_item['their_story_id'];
            $story = get_post($story_id);
            if ($story) {
                $story_title = get_the_title($story_id);
                $story_title = str_replace('Protected: ', '', $story_title);
                $name .= '<br><small style="color: #666; font-size: 0.875rem;">' . esc_html__('Story:', 'their-story') . ' <strong>' . esc_html($story_title) . '</strong></small>';
            }
        }
        return $name;
    }
    
    public function auto_select_variation_by_message_count() {
        $story_id = isset($_GET['story']) ? intval($_GET['story']) : (isset($_COOKIE['their_story_id']) ? intval($_COOKIE['their_story_id']) : 0);
        if (!$story_id) {
            return;
        }
        
        global $product;
        if (!$product || !$product->is_type('variable')) {
            return;
        }
        
        $message_count = isset($_GET['messages']) ? intval($_GET['messages']) : 0;
        if (!$message_count) {
            $approved_submissions = Their_Story::get_story_submissions_static($story_id, true);
            $message_count = count($approved_submissions);
        }
        
        if ($message_count <= 0) {
            return;
        }
        
        $variations = $product->get_available_variations();
        $selected_variation = null;
        $selected_attributes = array();
        $closest_diff = PHP_INT_MAX;
        
        foreach ($variations as $variation_data) {
            $variation_id = $variation_data['variation_id'];
            $variation = wc_get_product($variation_id);
            
            if (!$variation) {
                continue;
            }
            
            $variation_name = $variation->get_name();
            $variation_attributes = $variation->get_attributes();
            
            preg_match('/\((\d+)\s*[Mm]essages?\)/', $variation_name, $matches);
            if (empty($matches)) {
                foreach ($variation_attributes as $attr_value) {
                    if (is_string($attr_value)) {
                        preg_match('/\((\d+)\s*[Mm]essages?\)/', $attr_value, $attr_matches);
                        if (!empty($attr_matches)) {
                            $matches = $attr_matches;
                            break;
                        }
                    }
                }
            }
            
            if (!empty($matches)) {
                $variation_message_count = intval($matches[1]);
                $diff = abs($variation_message_count - $message_count);
                
                if ($diff < $closest_diff) {
                    $closest_diff = $diff;
                    $selected_variation = $variation_data;
                    $selected_attributes = $variation_data['attributes'];
                }
            }
        }
        
        if ($selected_variation && !empty($selected_attributes)) {
            add_action('wp_footer', function() use ($selected_attributes, $message_count) {
                ?>
                <script>
                (function() {
                    document.addEventListener('DOMContentLoaded', function() {
                        const attributes = <?php echo json_encode($selected_attributes); ?>;
                        const messageCount = <?php echo intval($message_count); ?>;
                        
                        setTimeout(function() {
                            const variationForm = document.querySelector('form.variations_form');
                            if (variationForm && attributes) {
                                Object.keys(attributes).forEach(function(attrName) {
                                    const attrValue = attributes[attrName];
                                    if (attrValue) {
                                        const select = variationForm.querySelector('select[name="attribute_' + attrName + '"]');
                                        if (select) {
                                            select.value = attrValue;
                                            select.dispatchEvent(new Event('change', { bubbles: true }));
                                        } else {
                                            const radio = variationForm.querySelector('input[type="radio"][name="attribute_' + attrName + '"][value="' + attrValue + '"]');
                                            if (radio) {
                                                radio.checked = true;
                                                radio.dispatchEvent(new Event('change', { bubbles: true }));
                                            }
                                        }
                                    }
                                });
                                
                                const checkVariationsEvent = new Event('check_variations', { bubbles: true });
                                variationForm.dispatchEvent(checkVariationsEvent);
                                
                                const foundVariationEvent = new Event('found_variation', { bubbles: true });
                                variationForm.dispatchEvent(foundVariationEvent);
                            }
                        }, 1000);
                    });
                })();
                </script>
                <?php
            }, 999);
        }
    }
    
    public function allow_storyteller_admin_access($prevent_access) {
        $current_user = wp_get_current_user();
        
        if ($current_user && (in_array('storyteller', $current_user->roles) || in_array('administrator', $current_user->roles))) {
            return false;
        }
        
        return $prevent_access; 
    }
    
    public function save_story_id_to_order_item($item, $cart_item_key, $values, $order) {
        if (isset($values['their_story_id']) && $values['their_story_id']) {
            $item->add_meta_data('_their_story_id', $values['their_story_id'], true);
        }
    }
    
    public function add_story_details_to_order_note($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        $story_details = $this->get_story_details_from_order($order);
        
        if (!empty($story_details)) {
            $note_content = __('Story Details:', 'their-story') . "\n\n";
            
            foreach ($story_details as $details) {
                $note_content .= __('Title:', 'their-story') . ' ' . $details['title'] . "\n";
                $note_content .= __('Storyteller:', 'their-story') . ' ' . $details['storyteller'] . "\n";
                $note_content .= __('Messages:', 'their-story') . ' ' . $details['message_count'] . "\n";
                if ($details['url']) {
                    $note_content .= __('Story URL:', 'their-story') . ' ' . $details['url'] . "\n";
                }
                $note_content .= "\n";
            }
            
            $order->add_order_note($note_content);
        }
    }
    
    private function get_story_details_from_order($order) {
        if (!$order) {
            return array();
        }
        
        $story_ids = array();
        $story_details = array();
        
        foreach ($order->get_items() as $item_id => $item) {
            $story_id = $item->get_meta('_their_story_id');
            if ($story_id && !in_array($story_id, $story_ids)) {
                $story_ids[] = $story_id;
                
                $story = get_post($story_id);
                if ($story) {
                    $story_title = get_the_title($story_id);
                    $story_title = str_replace('Protected: ', '', $story_title);
                    
                    $storyteller_id = get_post_meta($story_id, '_storyteller_id', true);
                    $storyteller = $storyteller_id ? get_userdata($storyteller_id) : null;
                    $storyteller_name = $storyteller ? $storyteller->display_name : __('Unknown', 'their-story');
                    
                    $approved_submissions = Their_Story::get_story_submissions_static($story_id, true);
                    $message_count = count($approved_submissions);
                    
                    $unique_link = get_post_meta($story_id, '_story_unique_link', true);
                    $story_url = '';
                    if ($unique_link) {
                        $their_story = new Their_Story();
                        $story_url = $their_story->get_story_url_from_link($unique_link);
                    }
                    
                    $story_details[] = array(
                        'title' => $story_title,
                        'storyteller' => $storyteller_name,
                        'message_count' => $message_count,
                        'url' => $story_url,
                        'story_id' => $story_id
                    );
                }
            }
        }
        
        return $story_details;
    }
    
    public function add_story_details_to_email($order, $sent_to_admin, $plain_text, $email) {
        $story_details = $this->get_story_details_from_order($order);
        
        if (empty($story_details)) {
            return;
        }
        
        if ($plain_text) {
            echo "\n\n" . __('Story Details:', 'their-story') . "\n\n";
            
            foreach ($story_details as $details) {
                echo __('Title:', 'their-story') . ' ' . $details['title'] . "\n";
                echo __('Storyteller:', 'their-story') . ' ' . $details['storyteller'] . "\n";
                echo __('Messages:', 'their-story') . ' ' . $details['message_count'] . "\n";
                if ($details['url']) {
                    echo __('View Your Story:', 'their-story') . ' ' . $details['url'] . "\n";
                }
                
                if ($sent_to_admin && isset($details['story_id'])) {
                    $csv_url = $this->get_csv_export_url($details['story_id']);
                    echo __('Download CSV:', 'their-story') . ' ' . $csv_url . "\n";
                }
                
                echo "\n";
            }
        } else {
            echo '<div style="margin: 20px 0; padding: 15px; background-color: #f0f6fc; border: 1px solid #c3d4e6; border-radius: 8px;">';
            echo '<h2 style="margin: 0 0 15px 0; font-size: 1.25rem; font-weight: 600; color: #1a1a1a;">' . esc_html__('Story Details:', 'their-story') . '</h2>';
            
            foreach ($story_details as $details) {
                echo '<div style="margin-bottom: 15px;">';
                echo '<p style="margin: 5px 0;"><strong>' . esc_html__('Title:', 'their-story') . '</strong> ' . esc_html($details['title']) . '</p>';
                echo '<p style="margin: 5px 0;"><strong>' . esc_html__('Storyteller:', 'their-story') . '</strong> ' . esc_html($details['storyteller']) . '</p>';
                echo '<p style="margin: 5px 0;"><strong>' . esc_html__('Messages:', 'their-story') . '</strong> ' . esc_html($details['message_count']) . '</p>';
                if ($details['url']) {
                    echo '<p style="margin: 5px 0;"><strong>' . esc_html__('View Your Story:', 'their-story') . '</strong> <a href="' . esc_url($details['url']) . '" style="color: #0073aa;">' . esc_html__('View Your Story', 'their-story') . '</a></p>';
                }
                
                if ($sent_to_admin && isset($details['story_id'])) {
                    $csv_url = $this->get_csv_export_url($details['story_id']);
                    echo '<p style="margin: 5px 0;"><strong>' . esc_html__('Download CSV:', 'their-story') . '</strong> <a href="' . esc_url($csv_url) . '" style="color: #0073aa;">' . esc_html__('Download Messages as CSV', 'their-story') . '</a></p>';
                }
                
                echo '</div>';
            }
            
            echo '</div>';
        }
    }
    
    public function handle_csv_export() {
        if (!isset($_GET['their_story_export_csv']) || !isset($_GET['story_id'])) {
            return;
        }
        
        $story_id = intval($_GET['story_id']);
        if (!$story_id) {
            wp_die(__('Invalid story ID.', 'their-story'));
        }
        
        $current_user = wp_get_current_user();
        if (!in_array('administrator', $current_user->roles)) {
            wp_die(__('You do not have permission to export this story.', 'their-story'));
        }
        
        $this->export_story_csv($story_id);
    }
    
    public function get_csv_export_url($story_id) {
        $nonce = wp_create_nonce('their_story_csv_export_' . $story_id);
        return add_query_arg(array(
            'their_story_export_csv' => '1',
            'story_id' => $story_id,
            'nonce' => $nonce
        ), home_url('/'));
    }
    
    private function invalidate_csv_cache($story_id) {
        $cache_key = 'their_story_csv_' . $story_id;
        delete_transient($cache_key);
    }
    
    private function export_story_csv($story_id) {
        
        $story = get_post($story_id);
        if (!$story) {
            wp_die(__('Story not found.', 'their-story'));
        }
        
        $cache_key = 'their_story_csv_' . $story_id;
        $cache_time = 3600; 
        $cached_data = get_transient($cache_key);
        
        if ($cached_data === false) {
            $submissions = Their_Story::get_story_submissions_static($story_id, false);
            
            $csv_data = array();
            
            foreach ($submissions as $submission) {
                $submission_name = get_post_meta($submission->ID, '_submission_name', true);
                $message = wp_strip_all_tags($submission->post_content);
                $message = str_replace(array("\r\n", "\r", "\n"), ' ', $message);
                $message = trim($message);
                
                $image_ids = get_post_meta($submission->ID, '_submission_image_ids', true);
                $image_urls = array();
                
                if (!empty($image_ids) && is_array($image_ids)) {
                    foreach ($image_ids as $image_id) {
                        if ($image_id) {
                            $image_url = wp_get_attachment_url($image_id);
                            if ($image_url) {
                                $image_urls[] = $image_url;
                            }
                        }
                    }
                }
                
                $csv_data[] = array(
                    'name' => $submission_name,
                    'message' => $message,
                    'image_urls' => implode(' | ', $image_urls),
                    'date' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission->post_date)),
                    'status' => $submission->post_status === 'publish' ? __('Approved', 'their-story') : __('Pending', 'their-story')
                );
            }
            
            set_transient($cache_key, $csv_data, $cache_time);
            $cached_data = $csv_data;
        }
        
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        $story_title = get_the_title($story_id);
        $story_title = str_replace('Protected: ', '', $story_title);
        $story_title = sanitize_file_name($story_title);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="story-' . $story_title . '-' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        $headers = array(
            __('Name', 'their-story'),
            __('Message', 'their-story'),
            __('Image URLs', 'their-story'),
            __('Date Submitted', 'their-story'),
            __('Status', 'their-story')
        );
        
        fputcsv($output, $headers);
        
        foreach ($cached_data as $row) {
            fputcsv($output, array(
                $row['name'],
                $row['message'],
                $row['image_urls'],
                $row['date'],
                $row['status']
            ));
        }
        
        fclose($output);
        exit;
    }
}

