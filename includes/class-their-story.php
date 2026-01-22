<?php

if (!defined('ABSPATH')) {
    exit;
}

class Their_Story {
    
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('user_register', array($this, 'redirect_after_registration'));
        add_filter('login_redirect', array($this, 'redirect_after_login'), 10, 3);
        add_action('init', array($this, 'add_storyteller_role'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_their_story_create_story', array($this, 'ajax_create_story'));
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
        include THEIR_STORY_PLUGIN_DIR . 'templates/admin-dashboard.php';
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
            $story->is_password_protected = post_password_required($story->ID);
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
        
        // Placeholder - will be enhanced later
        $unique_link = $this->generate_unique_link($page_id);
        update_post_meta($page_id, '_story_unique_link', $unique_link);
        
        wp_send_json_success(array(
            'message' => __('Story created successfully!', 'their-story'),
            'page_id' => $page_id,
            'unique_link' => $unique_link
        ));
    }
    
    // Placeholder implementation - will be enhanced later
    private function generate_unique_link($page_id) {
        $unique_id = 'story-' . $page_id . '-' . wp_generate_password(8, false);
        return $unique_id;
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
            'nonce' => wp_create_nonce('their_story_create_story')
        ));
    }
}

