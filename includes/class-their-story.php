<?php

if (!defined('ABSPATH')) {
    exit;
}

class Their_Story {
    
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_menu', array($this, 'remove_admin_menu_items'), 999);
        add_action('user_register', array($this, 'redirect_after_registration'));
        add_filter('login_redirect', array($this, 'redirect_after_login'), 10, 3);
        add_action('init', array($this, 'add_storyteller_role'));
        add_action('init', array($this, 'register_story_submission_post_type'));
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_story_link_redirect'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
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
        add_action('admin_head', array($this, 'hide_admin_bar_in_admin'));
        add_action('admin_footer', array($this, 'hide_admin_footer_for_storytellers'));
        add_filter('the_content', array($this, 'add_story_page_content'), 20);
        add_filter('post_password_required', array($this, 'bypass_password_for_owner'), 10, 2);
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
    }
    
    public function flush_rewrite_rules() {
        $this->add_rewrite_rules();
        flush_rewrite_rules();
    }
    
    public function add_query_vars($vars) {
        $vars[] = 'their_story_link';
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
        }
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
        $current_user = wp_get_current_user();
        if (in_array('storyteller', $current_user->roles)) {
            return false;
        }
        return $show;
    }
    
    public function hide_admin_bar_in_admin() {
        $current_user = wp_get_current_user();
        if (in_array('storyteller', $current_user->roles)) {
            echo '<style>#wpadminbar { display: none !important; }</style>';
        }
    }
    
    public function hide_admin_footer_for_storytellers() {
        $current_user = wp_get_current_user();
        if (in_array('storyteller', $current_user->roles)) {
            echo '<style>#wpfooter { display: none !important; }</style>';
        }
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
    
    public function redirect_after_registration($user_id) {
        $user = get_userdata($user_id);
        if (in_array('storyteller', $user->roles)) {
            set_transient('their_story_welcome_' . $user_id, true, 30);
        }
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
                $css_file = THEIR_STORY_PLUGIN_DIR . 'assets/css/frontend.css';
                $css_version = file_exists($css_file) ? filemtime($css_file) : THEIR_STORY_VERSION;
                wp_enqueue_style(
                    'their-story-frontend',
                    THEIR_STORY_PLUGIN_URL . 'assets/css/frontend.css',
                    array(),
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
        
        return $content . $story_content;
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
        
        // Handle multiple image uploads (up to 5)
        if (!empty($_FILES['images']['name']) && is_array($_FILES['images']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            
            $file_count = count($_FILES['images']['name']);
            $file_count = min($file_count, 5); // Limit to 5 images
            
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
}

