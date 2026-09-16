<?php

if (!defined('ABSPATH')) {
    exit;
}

class Their_Story {
    
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_menu', array($this, 'remove_admin_menu_items'), 999);
        add_action('admin_init', array($this, 'register_settings'));
        add_action('pre_get_posts', array($this, 'exclude_story_pages_from_admin_pages_list'));
        add_filter('login_redirect', array($this, 'redirect_after_login'), 10, 3);
        add_action('init', array($this, 'add_storyteller_role'));
        add_action('init', array($this, 'register_story_submission_post_type'));
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_story_link_redirect'));
        add_action('template_redirect', array($this, 'handle_csv_export'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_filter('admin_body_class', array($this, 'storyteller_dashboard_body_class'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_filter('body_class', array($this, 'story_page_body_class'));
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
        
        add_filter('wp_robots', array($this, 'noindex_story_pages'));
        add_filter('wpseo_robots', array($this, 'noindex_story_pages_yoast'));
        add_filter('rank_math/frontend/robots', array($this, 'noindex_story_pages_rankmath'));
        add_filter('wpseo_exclude_from_sitemap_by_post_ids', array($this, 'exclude_story_pages_from_yoast_sitemap'));
        add_action('template_redirect', array($this, 'handle_begin_checkout'));
        add_action('template_redirect', array($this, 'handle_contributor_thankyou'));
        add_action('wp_ajax_their_story_get_products', array($this, 'ajax_get_products'));
        add_action('wp_ajax_their_story_prepare_checkout', array($this, 'ajax_prepare_checkout'));
        add_action('wp_ajax_their_story_prepare_reorder', array($this, 'ajax_prepare_reorder'));
        add_action('wp_ajax_their_story_help_request', array($this, 'ajax_help_request'));
        add_action('woocommerce_before_calculate_totals', array($this, 'apply_setup_fee_to_cart'), 10, 1);
        add_filter('woocommerce_get_item_data', array($this, 'hide_setup_fee_cart_item_data'), 10, 2);

        if (class_exists('WooCommerce')) {
            add_filter('woocommerce_prevent_admin_access', array($this, 'allow_storyteller_admin_access'));
            add_filter('woocommerce_thankyou_order_received_text', '__return_empty_string');
            add_action('woocommerce_thankyou', array($this, 'their_story_thankyou_content'), 5);
            add_action('wp_head', array($this, 'their_story_thankyou_styles'));
            add_filter('woocommerce_cart_item_name', array($this, 'add_story_name_to_cart_item'), 10, 3);
            add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_story_id_to_order_item'), 10, 4);
            add_action('woocommerce_payment_complete', array($this, 'create_story_on_payment'), 10, 1);
            add_action('woocommerce_order_status_processing', array($this, 'create_story_on_payment'), 10, 1);
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
    
    public function handle_book_closed_cookie() {
        if (!$this->is_book_closed_page_view()) {
            return;
        }
        $story_id = isset($_GET['story']) ? intval($_GET['story']) : 0;
        if ($story_id) {
            setcookie('their_story_id', $story_id, time() + (86400 * 30), '/');
            $_COOKIE['their_story_id'] = $story_id;
        }
    }

    public function get_book_closed_page_slug() {
        return apply_filters('their_story_book_closed_slug', 'next-steps');
    }

    public function get_book_closed_page_id() {
        $slug = $this->get_book_closed_page_slug();
        if ($slug === '') {
            return (int) apply_filters('their_story_book_closed_page_id', 0);
        }
        $posts = get_posts(
            array(
                'post_type' => 'page',
                'name' => $slug,
                'post_status' => 'publish',
                'numberposts' => 1,
                'fields' => 'ids',
                'no_found_rows' => true,
            )
        );
        if (!empty($posts)) {
            return (int) $posts[0];
        }
        return (int) apply_filters('their_story_book_closed_page_id', 0);
    }

    public function is_book_closed_page_view() {
        if (!is_singular('page')) {
            return false;
        }
        $obj = get_queried_object();
        if (!$obj || empty($obj->post_name)) {
            return false;
        }
        return $obj->post_name === $this->get_book_closed_page_slug();
    }

    public function get_book_closed_url($query_args = array()) {
        $page_id = $this->get_book_closed_page_id();
        $url = '';
        if ($page_id) {
            $permalink = get_permalink($page_id);
            if (is_string($permalink) && $permalink !== '') {
                $url = $permalink;
            }
        }
        if ($url === '') {
            $slug = $this->get_book_closed_page_slug();
            $url = $slug !== '' ? home_url(user_trailingslashit($slug)) : home_url('/');
        }
        if (!empty($query_args)) {
            $url = add_query_arg($query_args, $url);
        }
        return $url;
    }

    public function book_product_url($product_id, $story_id, $message_count = 0) {
        $url = get_permalink($product_id);
        if ($story_id) {
            $url = add_query_arg('story', $story_id, $url);
            if ($message_count) {
                $url = add_query_arg('messages', $message_count, $url);
            }
        }
        return $url;
    }

    public function register_book_closed_shortcode() {
        add_shortcode('their_story_book_closed', array($this, 'shortcode_book_closed'));
    }

    public function shortcode_book_closed($atts = array(), $content = '') {
        $their_story = $this;
        $story_id = isset($_GET['story']) ? intval($_GET['story']) : 0;
        $message_count = isset($_GET['messages']) ? intval($_GET['messages']) : 0;
        $story = $story_id ? get_post($story_id) : null;
        $story_title = $story ? get_the_title($story_id) : '';
        $story_title = str_replace('Protected: ', '', $story_title);
        $book_products = array();
        if (class_exists('WooCommerce')) {
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_type',
                        'field' => 'slug',
                        'terms' => 'variable',
                    ),
                ),
            );
            $products = wc_get_products($args);
            foreach ($products as $wc_product) {
                if ($wc_product && $wc_product->is_type('variable')) {
                    $book_products[] = $wc_product;
                }
            }
        }
        ob_start();
        include THEIR_STORY_PLUGIN_DIR . 'templates/book-closed-inner.php';
        return ob_get_clean();
    }

    public function book_closed_body_class($classes) {
        if ($this->is_book_closed_page_view()) {
            $classes[] = 'their-story-book-closed-page';
        }
        return $classes;
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
                __('Story Pages', 'their-story'),
                __('Story Pages', 'their-story'),
                'manage_options',
                'their-story-story-pages',
                array($this, 'render_story_pages_list')
            );

            add_submenu_page(
                'their-story-admin',
                __('Settings', 'their-story'),
                __('Settings', 'their-story'),
                'manage_options',
                'their-story-settings',
                array($this, 'render_settings_page')
            );
        }
    }

    public function register_settings() {
        register_setting('their_story_settings_group', 'their_story_setup_fee', array(
            'type'              => 'number',
            'sanitize_callback' => function($val) { return max(0, floatval($val)); },
            'default'           => 300,
        ));

        add_settings_section(
            'their_story_pricing_section',
            __('Pricing', 'their-story'),
            null,
            'their-story-settings'
        );

        add_settings_field(
            'their_story_setup_fee',
            __('Initial setup fee ($)', 'their-story'),
            array($this, 'render_setup_fee_field'),
            'their-story-settings',
            'their_story_pricing_section'
        );
    }

    public function render_setup_fee_field() {
        $value = floatval(get_option('their_story_setup_fee', 300));
        ?>
        <input
            type="number"
            name="their_story_setup_fee"
            id="their_story_setup_fee"
            value="<?php echo esc_attr($value); ?>"
            min="0"
            step="0.01"
            style="width:120px;"
        />
        <p class="description"><?php esc_html_e('Added to the product price on a customer\'s first story purchase. Set to 0 to disable.', 'their-story'); ?></p>
        <?php
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) return;
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Share Their Story — Settings', 'their-story'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('their_story_settings_group');
                do_settings_sections('their-story-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }


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

    /**
     * Published story pages that belong to the current user and are closed (ready for book / shop flow).
     *
     * @return WP_Post[]
     */
    private function get_closed_stories_for_current_user() {
        if (!is_user_logged_in()) {
            return array();
        }
        $user = wp_get_current_user();
        if (!in_array('storyteller', (array) $user->roles, true)) {
            return array();
        }
        return get_posts(
            array(
                'post_type' => 'page',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'orderby' => 'modified',
                'order' => 'DESC',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => '_storyteller_id',
                        'value' => (string) $user->ID,
                        'compare' => '=',
                    ),
                    array(
                        'key' => '_story_closed',
                        'value' => '1',
                        'compare' => '=',
                    ),
                ),
            )
        );
    }

    /**
     * Story ID used on product pages (cart, variations). Storytellers with closed stories are limited to those IDs; URL/cookie must match when possible.
     */
    public function resolve_product_page_story_id() {
        $url = isset($_GET['story']) ? intval($_GET['story']) : 0;
        $cookie = isset($_COOKIE['their_story_id']) ? intval($_COOKIE['their_story_id']) : 0;
        $resolved = $url ?: $cookie;

        $closed = $this->get_closed_stories_for_current_user();
        if (!empty($closed)) {
            $ids = array_map('intval', wp_list_pluck($closed, 'ID'));
            if ($resolved && in_array($resolved, $ids, true)) {
                return $resolved;
            }
            return (int) $ids[0];
        }

        if (is_user_logged_in() && in_array('storyteller', (array) wp_get_current_user()->roles, true)) {
            return 0;
        }

        return $resolved;
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
            $story->is_closed = self::story_is_closed((int) $story->ID);
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

        $subject_name = isset($_POST['contribution_subject_name']) ? sanitize_text_field(wp_unslash($_POST['contribution_subject_name'])) : '';
        $relation_label = isset($_POST['contribution_relation_label']) ? sanitize_text_field(wp_unslash($_POST['contribution_relation_label'])) : '';
        if ($subject_name !== '') {
            update_post_meta($page_id, '_their_story_contribution_subject_name', $subject_name);
        }
        if ($relation_label !== '') {
            update_post_meta($page_id, '_their_story_contribution_relation_label', $relation_label);
        }
        
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
            'reopenNonce' => wp_create_nonce('their_story_reopen_story'),
            'getProductsNonce' => wp_create_nonce('their_story_get_products'),
            'prepareCheckoutNonce' => wp_create_nonce('their_story_prepare_checkout'),
            'prepareReorderNonce' => wp_create_nonce('their_story_prepare_reorder'),
            'helpNonce' => wp_create_nonce('their_story_help_request'),
            'setupFee' => floatval(get_option('their_story_setup_fee', 300)),
            'currencySymbol' => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '$',
        ));
    }
    
    public function story_page_body_class($classes) {
        if (!is_page()) {
            return $classes;
        }
        global $post;
        if (!$post || !get_post_meta($post->ID, '_storyteller_id', true)) {
            return $classes;
        }
        if (post_password_required($post)) {
            $classes[] = 'their-story-password-page';
        }
        return $classes;
    }

    public function enqueue_frontend_assets() {
        // Also enqueue on the contributor thank-you virtual page
        if (isset($_GET['their_story_thankyou'])) {
            $css_file = THEIR_STORY_PLUGIN_DIR . 'assets/css/frontend.css';
            wp_enqueue_style(
                'their-story-frontend',
                THEIR_STORY_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                file_exists($css_file) ? filemtime($css_file) : THEIR_STORY_VERSION
            );
            return;
        }

        if (is_page()) {
            global $post;
            if (!$post) {
                return;
            }
            $storyteller_id = get_post_meta($post->ID, '_storyteller_id', true);
            if ($storyteller_id) {
                if (post_password_required($post)) {
                    $pass_css = THEIR_STORY_PLUGIN_DIR . 'assets/css/story-password.css';
                    wp_enqueue_style(
                        'their-story-password',
                        THEIR_STORY_PLUGIN_URL . 'assets/css/story-password.css',
                        array(),
                        file_exists($pass_css) ? filemtime($pass_css) : THEIR_STORY_VERSION
                    );
                    return;
                }
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

                $cf_js = THEIR_STORY_PLUGIN_DIR . 'assets/js/contribution-form.js';
                wp_enqueue_script(
                    'their-story-contribution',
                    THEIR_STORY_PLUGIN_URL . 'assets/js/contribution-form.js',
                    array('their-story-frontend'),
                    file_exists($cf_js) ? filemtime($cf_js) : THEIR_STORY_VERSION,
                    true
                );
                
                $current_user = wp_get_current_user();
                $can_moderate = in_array('administrator', $current_user->roles);
                
                $storyteller_id = get_post_meta($post->ID, '_storyteller_id', true);
                $is_storyteller = in_array('storyteller', $current_user->roles) && ($storyteller_id == $current_user->ID);
                $can_close_story = $can_moderate || $is_storyteller;
                
                $story_closed = self::story_is_closed((int) $post->ID);
                wp_localize_script('their-story-frontend', 'theirStoryFrontend', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'storyId' => $post->ID,
                    'submitNonce' => wp_create_nonce('their_story_submit_message'),
                    'moderateNonce' => wp_create_nonce('their_story_moderate_submission'),
                    'closeNonce' => wp_create_nonce('their_story_close_story'),
                    'canModerate' => $can_moderate,
                    'canCloseStory' => $can_close_story,
                    'isStoryClosed' => $story_closed,
                    'storyClosedMessage' => $story_closed
                        ? __('This story has been closed. No new messages can be added.', 'their-story')
                        : '',
                    'contributorThankyouUrl' => add_query_arg('their_story_thankyou', '1', home_url('/')),
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
        if (!$story_id) {
            wp_send_json_error(array('message' => __('Invalid story ID.', 'their-story')));
        }
        
        $story = get_post($story_id);
        if (!$story) {
            wp_send_json_error(array('message' => __('Story not found.', 'their-story')));
        }
        
        if (self::story_is_closed($story_id)) {
            wp_send_json_error(array('message' => __('This story has been closed. No new messages can be added.', 'their-story')));
        }

        $contribution_clean = null;
        if (!empty($_POST['contribution_json'])) {
            $raw = wp_unslash($_POST['contribution_json']);
            $data = json_decode($raw, true);
            if (!is_array($data)) {
                wp_send_json_error(array('message' => __('Invalid submission data.', 'their-story')));
            }
            $err = $this->validate_contribution_submission($data);
            if (is_wp_error($err)) {
                wp_send_json_error(array('message' => $err->get_error_message()));
            }
            $contribution_clean = $this->sanitize_contribution_for_storage($data);
            $name = trim(($contribution_clean['first_name'] ?? '') . ' ' . ($contribution_clean['surname'] ?? ''));
            $message = $this->build_contribution_post_content($contribution_clean, $story_id);
        } else {
            $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
            $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';
            if (empty($name)) {
                wp_send_json_error(array('message' => __('Name is required.', 'their-story')));
            }
            if (empty($message)) {
                wp_send_json_error(array('message' => __('Message is required.', 'their-story')));
            }
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
        if (!empty($contribution_clean)) {
            update_post_meta($submission_id, '_submission_contribution', wp_json_encode($contribution_clean));
        }
        
        $this->invalidate_csv_cache($story_id);
        
        wp_send_json_success(array('message' => __('Your message has been submitted and is awaiting approval.', 'their-story')));
    }

    /**
     * @param array $data Raw contribution from JSON.
     * @return true|WP_Error
     */
    private function validate_contribution_submission($data) {
        if (($data['join_intent'] ?? '') !== 'yes') {
            return new WP_Error('their_story_join', __('Only completed “yes” contributions can be submitted.', 'their-story'));
        }
        $required = array('first_name', 'surname', 'email', 'live_where', 'known_duration', 'how_met_story', 'describe_one_word', 'particular_story');
        foreach ($required as $k) {
            if (empty(trim((string) ($data[$k] ?? '')))) {
                return new WP_Error('their_story_required', __('Please complete all required fields.', 'their-story'));
            }
        }
        $email = sanitize_email($data['email'] ?? '');
        if (!is_email($email)) {
            return new WP_Error('their_story_email', __('Please enter a valid email address.', 'their-story'));
        }
        $funny = $data['funny_story_choice'] ?? '';
        if (!in_array($funny, array('yes', 'no'), true)) {
            return new WP_Error('their_story_funny', __('Please answer the funny story question.', 'their-story'));
        }
        if ($funny === 'yes' && trim((string) ($data['funny_story_text'] ?? '')) === '') {
            return new WP_Error('their_story_funny_text', __('Please enter your funny story or choose No.', 'their-story'));
        }
        $ex1 = $data['extra_story1_choice'] ?? '';
        if (!in_array($ex1, array('yes', 'no'), true)) {
            return new WP_Error('their_story_choice', __('Please answer the “any other stories” question.', 'their-story'));
        }
        if ($ex1 === 'yes' && trim((string) ($data['extra_story1_text'] ?? '')) === '') {
            return new WP_Error('their_story_extra1', __('Please enter your other story or choose No.', 'their-story'));
        }
        if ($ex1 === 'yes') {
            $ex2 = $data['extra_story2_choice'] ?? '';
            if (!in_array($ex2, array('yes', 'no'), true)) {
                return new WP_Error('their_story_extra2_choice', __('Please answer the second “any other stories” question.', 'their-story'));
            }
            if ($ex2 === 'yes' && trim((string) ($data['extra_story2_text'] ?? '')) === '') {
                return new WP_Error('their_story_extra2', __('Please enter your other story or choose No.', 'their-story'));
            }
        }
        if (!in_array($data['other_thoughts_choice'] ?? '', array('yes', 'no'), true)) {
            return new WP_Error('their_story_thoughts_choice', __('Please answer whether you would like to share other thoughts.', 'their-story'));
        }
        if (($data['other_thoughts_choice'] ?? '') === 'yes' && trim((string) ($data['other_thoughts_text'] ?? '')) === '') {
            return new WP_Error('their_story_thoughts', __('Please enter your thoughts or choose No.', 'their-story'));
        }
        return true;
    }

    /**
     * @param array $data
     * @return array<string, mixed>
     */
    private function sanitize_contribution_for_storage($data) {
        $out = array();
        $long = array(
            'how_met_story', 'particular_story', 'funny_story_text', 'extra_story1_text',
            'extra_story2_text', 'other_thoughts_text',
        );
        $text_keys = array(
            'join_intent', 'first_name', 'surname', 'email', 'live_where', 'known_duration',
            'how_met_story', 'describe_one_word', 'particular_story', 'funny_story_choice', 'funny_story_text',
            'extra_story1_choice', 'extra_story1_text', 'extra_story2_choice', 'extra_story2_text',
            'other_thoughts_choice', 'other_thoughts_text',
        );
        foreach ($text_keys as $k) {
            $v = isset($data[$k]) ? (string) $data[$k] : '';
            if (strlen($v) > 12000) {
                $v = substr($v, 0, 12000);
            }
            if ($k === 'email') {
                $out[$k] = sanitize_email($v);
            } elseif (in_array($k, $long, true)) {
                $out[$k] = sanitize_textarea_field($v);
            } else {
                $out[$k] = sanitize_text_field($v);
            }
        }
        return $out;
    }

    /**
     * @param array $data Sanitized contribution.
     */
    private function build_contribution_post_content($data, $story_id) {
        $subject = trim((string) get_post_meta($story_id, '_their_story_contribution_subject_name', true));
        if ($subject === '') {
            $subject = preg_replace('/^Protected:\s*/i', '', get_the_title($story_id));
        }
        $lines = array();
        $lines[] = __('First name', 'their-story') . ': ' . ($data['first_name'] ?? '');
        $lines[] = __('Surname', 'their-story') . ': ' . ($data['surname'] ?? '');
        $lines[] = __('Email', 'their-story') . ': ' . ($data['email'] ?? '');
        $lines[] = __('Where they live', 'their-story') . ': ' . ($data['live_where'] ?? '');
        $lines[] = sprintf(
            /* translators: %s: honoree name */
            __('How long known %s', 'their-story'),
            $subject
        ) . ': ' . ($data['known_duration'] ?? '');
        $lines[] = __('How we met', 'their-story') . ': ' . ($data['how_met_story'] ?? '');
        $lines[] = __('One word', 'their-story') . ': ' . ($data['describe_one_word'] ?? '');
        $lines[] = __('Particular story', 'their-story') . ': ' . ($data['particular_story'] ?? '');
        if (($data['funny_story_choice'] ?? '') === 'yes' && trim((string) ($data['funny_story_text'] ?? '')) !== '') {
            $lines[] = __('Funny story', 'their-story') . ': ' . ($data['funny_story_text'] ?? '');
        }
        if (($data['extra_story1_choice'] ?? '') === 'yes' && trim((string) ($data['extra_story1_text'] ?? '')) !== '') {
            $lines[] = __('Other story (1)', 'their-story') . ': ' . ($data['extra_story1_text'] ?? '');
        }
        if (($data['extra_story1_choice'] ?? '') === 'yes'
            && ($data['extra_story2_choice'] ?? '') === 'yes'
            && trim((string) ($data['extra_story2_text'] ?? '')) !== '') {
            $lines[] = __('Other story (2)', 'their-story') . ': ' . ($data['extra_story2_text'] ?? '');
        }
        if (($data['other_thoughts_choice'] ?? '') === 'yes' && trim((string) ($data['other_thoughts_text'] ?? '')) !== '') {
            $lines[] = __('Other thoughts', 'their-story') . ': ' . ($data['other_thoughts_text'] ?? '');
        }
        return implode("\n", $lines);
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
            if (self::story_is_closed((int) $story_id)) {
                if ($action === 'approve') {
                    wp_send_json_error(array('message' => __('This story has been closed. Submissions cannot be approved.', 'their-story')));
                } elseif ($action === 'delete') {
                    wp_send_json_error(array('message' => __('This story has been closed. Submissions cannot be deleted.', 'their-story')));
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

    /**
     * Whether the story is closed to new submissions (checks post meta).
     *
     * @param int $story_id
     * @return bool
     */
    public static function story_is_closed($story_id) {
        $story_id = (int) $story_id;
        if ($story_id <= 0) {
            return false;
        }
        $v = get_post_meta($story_id, '_story_closed', true);
        if ($v === '' || $v === false || $v === null) {
            return false;
        }
        return $v === '1' || $v === 1 || $v === true;
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

        // Notify the Their Story team
        $story_title    = get_the_title($story_id);
        $storyteller_name  = $current_user->display_name;
        $storyteller_email = $current_user->user_email;
        $admin_subject  = sprintf('Story closed: %s', $story_title);
        $admin_message  = sprintf(
            "A story has been closed by its storyteller and is ready for production.\n\nStory: %s\nStoryteller: %s (%s)\n\nDashboard: %s",
            $story_title,
            $storyteller_name,
            $storyteller_email,
            admin_url('admin.php?page=their-story-admin')
        );
        wp_mail('grant@sharetheirstory.com.au', $admin_subject, $admin_message);

        wp_send_json_success(array('message' => __('Story closed. The Their Story team has been notified.', 'their-story')));
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
        $story_id = $this->resolve_product_page_story_id();
        if ($story_id) {
            $url = add_query_arg('story', $story_id, $url);
        }
        return $url;
    }
    
    public function store_story_id_in_cart_item($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
        $story_id = $this->resolve_product_page_story_id();
        if ($story_id) {
            WC()->cart->cart_contents[$cart_item_key]['their_story_id'] = $story_id;
            setcookie('their_story_id', $story_id, time() + (86400 * 30), '/');
        }
    }
    
    public function preserve_story_id_on_product_page() {
        $story_id = $this->resolve_product_page_story_id();
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
        $dashboard_url = admin_url('admin.php?page=their-story-dashboard');
        $box_style = 'background: #ffffff; border: 1px solid #e6b3a1; border-radius: 8px; padding: 20px; margin-bottom: 20px; font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, sans-serif;';
        $muted = 'margin: 0 0 12px 0; font-size: 0.875rem; font-weight: 400; color: rgba(0, 0, 0, 0.55); line-height: 1.45;';

        $closed_stories = $this->get_closed_stories_for_current_user();

        if (!empty($closed_stories)) {
            $ids = array_map('intval', wp_list_pluck($closed_stories, 'ID'));
            $url_story = isset($_GET['story']) ? intval($_GET['story']) : 0;
            $cookie_story = isset($_COOKIE['their_story_id']) ? intval($_COOKIE['their_story_id']) : 0;
            $preferred = $url_story ? $url_story : $cookie_story;
            $default_id = ($preferred && in_array($preferred, $ids, true)) ? $preferred : (int) $closed_stories[0]->ID;

            $story_choices = array();
            foreach ($closed_stories as $s) {
                $approved = Their_Story::get_story_submissions_static($s->ID, true);
                $story_choices[] = array(
                    'id' => (int) $s->ID,
                    'title' => preg_replace('/^Protected:\s*/i', '', get_the_title($s)),
                    'messages' => count($approved),
                );
            }

            $selected_messages = 0;
            foreach ($story_choices as $c) {
                if ($c['id'] === $default_id) {
                    $selected_messages = $c['messages'];
                    break;
                }
            }

            $select_style = 'width: 100%; max-width: 100%; box-sizing: border-box; padding: 10px 12px; margin: 0 0 12px 0; border: 1px solid #e6b3a1; border-radius: 6px; font-family: inherit; font-size: 1rem; font-weight: 400; color: #000000; background: #ffffff;';
            ?>
            <div class="their-story-product-info" style="<?php echo esc_attr($box_style); ?>">
                <p style="<?php echo esc_attr($muted); ?>">
                    <?php echo esc_html__('Choose from any of your closed stories.', 'their-story'); ?>
                </p>
                <label for="their-story-product-story-select" style="display: block; margin: 0 0 6px 0; font-size: 0.9375rem; font-weight: 400; color: #000000;">
                    <?php echo esc_html__('Book for', 'their-story'); ?>
                </label>
                <select id="their-story-product-story-select" class="their-story-product-story-select" style="<?php echo esc_attr($select_style); ?>">
                    <?php foreach ($story_choices as $c) : ?>
                        <option value="<?php echo esc_attr((string) $c['id']); ?>" data-messages="<?php echo esc_attr((string) $c['messages']); ?>" <?php selected($default_id, $c['id']); ?>>
                            <?php echo esc_html($c['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($selected_messages > 0) : ?>
                    <p class="their-story-product-info-messages" style="margin: 0 0 12px 0; font-size: 0.875rem; font-weight: 400; color: rgba(0, 0, 0, 0.55);">
                        <?php printf(esc_html__('This story contains %d message(s).', 'their-story'), $selected_messages); ?>
                    </p>
                <?php endif; ?>
                <p style="margin: 0; font-size: 0.8125rem; font-weight: 400; color: rgba(0, 0, 0, 0.55); line-height: 1.45;">
                    <?php echo esc_html__("Can't see anything?", 'their-story'); ?>
                    <a href="<?php echo esc_url($dashboard_url); ?>" style="color: #000000; text-decoration: underline; text-underline-offset: 0.15em;">
                        <?php echo esc_html__('Close a story', 'their-story'); ?>
                    </a>
                    <?php echo esc_html__('in My Stories to see it here.', 'their-story'); ?>
                </p>
            </div>
            <?php
            add_action(
                'wp_footer',
                static function () {
                    ?>
                    <script>
                    (function() {
                        var sel = document.getElementById('their-story-product-story-select');
                        if (!sel) return;
                        sel.addEventListener('change', function() {
                            var opt = sel.options[sel.selectedIndex];
                            if (!opt) return;
                            var u = new URL(window.location.href);
                            u.searchParams.set('story', sel.value);
                            u.searchParams.set('messages', opt.getAttribute('data-messages') || '0');
                            window.location.href = u.toString();
                        });
                    })();
                    </script>
                    <?php
                },
                998
            );
            return;
        }

        if (is_user_logged_in() && in_array('storyteller', (array) wp_get_current_user()->roles, true)) {
            ?>
            <div class="their-story-product-info" style="<?php echo esc_attr($box_style); ?>">
                <p style="<?php echo esc_attr($muted); ?>">
                    <?php echo esc_html__('Choose from any of your closed stories.', 'their-story'); ?>
                </p>
                <p style="margin: 0; font-size: 0.875rem; font-weight: 400; color: rgba(0, 0, 0, 0.55); line-height: 1.45;">
                    <?php echo esc_html__("Can't see anything?", 'their-story'); ?>
                    <a href="<?php echo esc_url($dashboard_url); ?>" style="color: #000000; text-decoration: underline; text-underline-offset: 0.15em;">
                        <?php echo esc_html__('Close a story', 'their-story'); ?>
                    </a>
                    <?php echo esc_html__('in My Stories to see it here.', 'their-story'); ?>
                </p>
            </div>
            <?php
            return;
        }

        $story_id = $this->resolve_product_page_story_id();
        if (!$story_id) {
            return;
        }
        $story = get_post($story_id);
        if (!$story) {
            return;
        }

        $story_title = get_the_title($story_id);
        $story_title = str_replace('Protected: ', '', $story_title);
        $message_count = isset($_GET['messages']) ? intval($_GET['messages']) : 0;
        if (!$message_count) {
            $approved_submissions = Their_Story::get_story_submissions_static($story_id, true);
            $message_count = count($approved_submissions);
        }
        ?>
        <div class="their-story-product-info" style="<?php echo esc_attr($box_style); ?>">
            <p style="margin: 0 0 10px 0; font-size: 1.125rem; font-weight: 400; color: #000000;">
                <?php echo esc_html__('Book for:', 'their-story'); ?> <?php echo esc_html($story_title); ?>
            </p>
            <?php if ($message_count > 0) : ?>
                <p style="margin: 0; font-size: 0.875rem; font-weight: 400; color: rgba(0, 0, 0, 0.55);">
                    <?php printf(esc_html__('This story contains %d message(s).', 'their-story'), $message_count); ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    public function add_story_name_to_cart_item($name, $cart_item, $cart_item_key) {
        if (!empty($cart_item['their_story_pending']['title'])) {
            $story_title = $cart_item['their_story_pending']['title'];
            $name .= '<br><small style="color: #666; font-size: 0.875rem;">' . esc_html__('Story:', 'their-story') . ' <strong>' . esc_html($story_title) . '</strong></small>';
        } elseif (isset($cart_item['their_story_id']) && $cart_item['their_story_id']) {
            $story_id = $cart_item['their_story_id'];
            $story = get_post($story_id);
            if ($story) {
                $story_title = str_replace('Protected: ', '', get_the_title($story_id));
                $name .= '<br><small style="color: #666; font-size: 0.875rem;">' . esc_html__('Story:', 'their-story') . ' <strong>' . esc_html($story_title) . '</strong></small>';
            }
        }
        return $name;
    }
    
    public function auto_select_variation_by_message_count() {
        $story_id = $this->resolve_product_page_story_id();
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
        if (!empty($values['their_story_pending']) && is_array($values['their_story_pending'])) {
            $item->add_meta_data('_their_story_pending', wp_json_encode($values['their_story_pending']), true);
        }
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
        delete_transient($this->csv_export_cache_key($story_id));
        delete_transient('their_story_csv_' . (int) $story_id);
    }

    /**
     * Transient key for cached CSV rows (bump suffix when columns change).
     *
     * @param int $story_id
     * @return string
     */
    private function csv_export_cache_key($story_id) {
        return 'their_story_csv_rows_v2_' . (int) $story_id;
    }

    /**
     * Single-line cell text for CSV (no HTML, normalized whitespace).
     *
     * @param mixed $text
     * @return string
     */
    private function csv_export_flatten_cell($text) {
        $text = wp_strip_all_tags((string) $text);
        $text = str_replace(array("\r\n", "\r", "\n"), ' ', $text);
        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * Contribution meta keys in export column order (matches wizard / storage).
     *
     * @return string[]
     */
    private function csv_export_contribution_keys() {
        return array(
            'join_intent',
            'first_name',
            'surname',
            'email',
            'live_where',
            'known_duration',
            'how_met_story',
            'describe_one_word',
            'particular_story',
            'funny_story_choice',
            'funny_story_text',
            'extra_story1_choice',
            'extra_story1_text',
            'extra_story2_choice',
            'extra_story2_text',
            'other_thoughts_choice',
            'other_thoughts_text',
        );
    }

    /**
     * @return string[] Translated header labels for contribution columns.
     */
    private function csv_export_contribution_headers() {
        return array(
            __('Join intent', 'their-story'),
            __('First name', 'their-story'),
            __('Surname', 'their-story'),
            __('Email', 'their-story'),
            __('Where they live', 'their-story'),
            __('How long known', 'their-story'),
            __('How we met', 'their-story'),
            __('One word', 'their-story'),
            __('Particular story', 'their-story'),
            __('Funny story (choice)', 'their-story'),
            __('Funny story', 'their-story'),
            __('Other story 1 (choice)', 'their-story'),
            __('Other story 1', 'their-story'),
            __('Other story 2 (choice)', 'their-story'),
            __('Other story 2', 'their-story'),
            __('Other thoughts (choice)', 'their-story'),
            __('Other thoughts', 'their-story'),
        );
    }
    
    private function export_story_csv($story_id) {
        
        $story = get_post($story_id);
        if (!$story) {
            wp_die(__('Story not found.', 'their-story'));
        }
        
        $cache_key = $this->csv_export_cache_key($story_id);
        $cache_time = 3600; 
        $cached_data = get_transient($cache_key);
        
        if ($cached_data === false) {
            $submissions = Their_Story::get_story_submissions_static($story_id, false);
            
            $csv_data = array();
            $contrib_keys = $this->csv_export_contribution_keys();
            
            foreach ($submissions as $submission) {
                $submission_name = get_post_meta($submission->ID, '_submission_name', true);
                $message = $this->csv_export_flatten_cell($submission->post_content);
                
                $contrib_raw = get_post_meta($submission->ID, '_submission_contribution', true);
                $contrib = is_string($contrib_raw) ? json_decode($contrib_raw, true) : null;
                if (!is_array($contrib)) {
                    $contrib = array();
                }
                
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
                
                $row = array($this->csv_export_flatten_cell($submission_name));
                foreach ($contrib_keys as $key) {
                    $row[] = isset($contrib[$key]) ? $this->csv_export_flatten_cell($contrib[$key]) : '';
                }
                $row[] = $message;
                $row[] = implode(' | ', $image_urls);
                $row[] = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission->post_date));
                $row[] = $submission->post_status === 'publish' ? __('Approved', 'their-story') : __('Pending', 'their-story');
                
                $csv_data[] = $row;
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
        
        $headers = array_merge(
            array(__('Name', 'their-story')),
            $this->csv_export_contribution_headers(),
            array(
                __('Message (stored)', 'their-story'),
                __('Image URLs', 'their-story'),
                __('Date Submitted', 'their-story'),
                __('Status', 'their-story'),
            )
        );
        
        fputcsv($output, $headers);
        
        foreach ($cached_data as $row) {
            if (!is_array($row)) {
                continue;
            }
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }

    // -------------------------------------------------------------------------
    // WooCommerce thank-you page
    // -------------------------------------------------------------------------

    public function their_story_thankyou_content($order_id) {
        $order = wc_get_order($order_id);
        if (!$order || !$order->get_meta('_their_story_created')) {
            return;
        }

        $dashboard_url = admin_url('admin.php?page=their-story-dashboard');
        $is_reorder    = (bool) $order->get_meta('_their_story_reorder');
        ?>
        <div class="ts-thankyou-header">
            <?php if ($is_reorder) : ?>
                <h1 class="ts-thankyou-title"><?php esc_html_e('Thank you! Your reorder has been received.', 'their-story'); ?></h1>
                <p class="ts-thankyou-lede"><?php esc_html_e('The Share Their Story team has been notified and will be in touch shortly.', 'their-story'); ?></p>
            <?php else : ?>
                <h1 class="ts-thankyou-title"><?php esc_html_e('Thank you! Your story has been created.', 'their-story'); ?></h1>
            <?php endif; ?>
            <a href="<?php echo esc_url($dashboard_url); ?>" class="ts-thankyou-btn">
                <?php esc_html_e('Go to my dashboard', 'their-story'); ?>
            </a>
            <p class="ts-thankyou-support">
                <?php esc_html_e('For any questions please email', 'their-story'); ?>
                <a href="mailto:support@sharetheirstory.com.au">support@sharetheirstory.com.au</a>
            </p>
        </div>
        <?php
    }

    public function their_story_thankyou_styles() {
        if (!is_wc_endpoint_url('order-received')) {
            return;
        }
        ?>
        <style>
        /* Their Story — order confirmation page */
        .woocommerce-order {
            text-align: center;
            max-width: 640px;
            margin-left: auto;
            margin-right: auto;
        }
        .ts-thankyou-header {
            padding: 2rem 0 1.5rem;
        }
        .ts-thankyou-title {
            font-size: clamp(1.5rem, 4vw, 2.25rem);
            font-weight: 700;
            margin: 0 0 1.5rem;
            line-height: 1.2;
        }
        .ts-thankyou-btn {
            display: inline-block;
            padding: 0.875rem 2.25rem;
            background-color: #e6b3a1;
            color: #000000;
            font-size: 1.125rem;
            font-weight: 600;
            border-radius: 9999px;
            text-decoration: none;
            transition: background-color 0.2s ease;
            margin-bottom: 1.25rem;
        }
        .ts-thankyou-btn:hover {
            background-color: #d49a87;
            color: #000000;
        }
        .ts-thankyou-support {
            font-size: 0.9375rem;
            color: rgba(0,0,0,0.6);
            margin: 0 0 2rem;
        }
        .ts-thankyou-support a {
            color: inherit;
            text-decoration: underline;
            text-underline-offset: 0.15em;
        }
        /* Order overview & details — centered */
        .woocommerce-order-overview,
        .woocommerce-order-details,
        .woocommerce-order-details__title {
            text-align: center;
        }
        .woocommerce-order-overview {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.25rem 1.5rem;
            list-style: none;
            padding: 0;
            margin: 0 0 2rem;
        }
        .woocommerce-order-overview li {
            border-right: none !important;
            padding-right: 0 !important;
        }
        /* Hide billing/shipping address blocks */
        .woocommerce-customer-details {
            display: none !important;
        }
        </style>
        <?php
    }

    // -------------------------------------------------------------------------
    // Noindex story pages
    // -------------------------------------------------------------------------

    private function is_story_page() {
        if (!is_singular('page')) {
            return false;
        }
        global $post;
        return $post && get_post_meta($post->ID, '_storyteller_id', true);
    }

    /** wp_robots filter (WordPress 5.7+) */
    public function noindex_story_pages($robots) {
        if ($this->is_story_page()) {
            $robots['noindex']  = true;
            $robots['nofollow'] = false;
        }
        return $robots;
    }

    /** Yoast SEO */
    public function noindex_story_pages_yoast($robots) {
        if ($this->is_story_page()) {
            return 'noindex, follow';
        }
        return $robots;
    }

    /** Yoast sitemap exclusion */
    public function exclude_story_pages_from_yoast_sitemap($excluded_ids) {
        $story_ids = get_posts(array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => array(
                array(
                    'key'     => '_storyteller_id',
                    'compare' => 'EXISTS',
                ),
            ),
        ));
        return array_merge((array) $excluded_ids, $story_ids);
    }

    /** Rank Math */
    public function noindex_story_pages_rankmath($robots) {
        if ($this->is_story_page()) {
            $robots['index'] = 'noindex';
        }
        return $robots;
    }

    // -------------------------------------------------------------------------
    // Purchase-first workflow
    // -------------------------------------------------------------------------

    public function apply_setup_fee_to_cart($cart) {
        if (is_admin() && !defined('DOING_AJAX')) return;

        $setup_fee = floatval(get_option('their_story_setup_fee', 300));
        if ($setup_fee <= 0) return;

        foreach ($cart->get_cart() as $item) {
            if (!empty($item['their_story_setup_fee'])) {
                $product = $item['data'];
                $product->set_price(floatval($product->get_price()) + $setup_fee);
            }
        }
    }

    public function hide_setup_fee_cart_item_data($item_data, $cart_item) {
        foreach ($item_data as $key => $row) {
            if (isset($row['key']) && $row['key'] === 'their_story_setup_fee') {
                unset($item_data[$key]);
            }
        }
        return array_values($item_data);
    }

    public function ajax_help_request() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'their_story_help_request')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'their-story')));
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in.', 'their-story')));
        }

        $name    = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $email   = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $message = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));

        if (!$name || !$email || !$message) {
            wp_send_json_error(array('message' => __('Please fill in all fields.', 'their-story')));
        }

        if (!is_email($email)) {
            wp_send_json_error(array('message' => __('Please enter a valid email address.', 'their-story')));
        }

        $subject = sprintf('Customer help request from %s', $name);
        $body    = sprintf("Name: %s\nEmail: %s\n\nMessage:\n%s", $name, $email, $message);
        $headers = array('Reply-To: ' . $name . ' <' . $email . '>');

        wp_mail('grant@sharetheirstory.com.au', $subject, $body, $headers);

        wp_send_json_success(array('message' => __('Your message has been sent. We\'ll be in touch soon!', 'their-story')));
    }

    /**
     * Returns available WC variable products + their variations for the creation wizard.
     */
    public function ajax_get_products() {
        check_ajax_referer('their_story_get_products', 'nonce');

        $user = wp_get_current_user();
        if (!in_array('storyteller', (array) $user->roles, true) && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No permission.', 'their-story')));
        }

        if (!class_exists('WooCommerce')) {
            wp_send_json_error(array('message' => __('WooCommerce is not active.', 'their-story')));
        }

        $wc_products = wc_get_products(array(
            'type'   => 'variable',
            'status' => 'publish',
            'limit'  => -1,
        ));

        $result = array();
        foreach ($wc_products as $product) {
            if (!$product->is_type('variable')) {
                continue;
            }

            $raw_variations = $product->get_available_variations();
            if (empty($raw_variations)) {
                continue;
            }

            // Build per-attribute groups (key → label, ordered options)
            $attr_groups = array();
            foreach ($raw_variations as $v) {
                foreach ($v['attributes'] as $attr_key => $attr_value) {
                    if ($attr_value === '') {
                        continue;
                    }
                    if (!isset($attr_groups[$attr_key])) {
                        $taxonomy    = str_replace('attribute_', '', $attr_key);
                        $attr_obj_id = wc_attribute_taxonomy_id_by_name($taxonomy);
                        $attr_obj    = $attr_obj_id ? wc_get_attribute($attr_obj_id) : null;
                        $attr_groups[$attr_key] = array(
                            'key'     => $attr_key,
                            'label'   => $attr_obj ? $attr_obj->name : ucwords(str_replace(array('-', '_'), ' ', $taxonomy)),
                            'options' => array(),
                        );
                    }
                    if (!array_key_exists($attr_value, $attr_groups[$attr_key]['options'])) {
                        $taxonomy = str_replace('attribute_', '', $attr_key);
                        $term     = get_term_by('slug', $attr_value, $taxonomy);
                        $attr_groups[$attr_key]['options'][$attr_value] = $term
                            ? $term->name
                            : ucwords(str_replace(array('-', '_'), ' ', $attr_value));
                    }
                }
            }

            // Convert options maps to indexed arrays
            $attr_groups_out = array();
            foreach ($attr_groups as $group) {
                $opts = array();
                foreach ($group['options'] as $slug => $label) {
                    $opts[] = array('slug' => $slug, 'label' => $label);
                }
                $attr_groups_out[] = array(
                    'key'     => $group['key'],
                    'label'   => $group['label'],
                    'options' => $opts,
                );
            }

            // Slim variation list — id, price_html, attributes map, and variation image
            $variations = array();
            foreach ($raw_variations as $v) {
                $var = wc_get_product($v['variation_id']);
                if (!$var) {
                    continue;
                }
                $var_image_id = $var->get_image_id();
                $var_image    = $var_image_id
                    ? wp_get_attachment_image_url($var_image_id, 'large')
                    : wp_get_attachment_image_url($product->get_image_id(), 'large');
                $variations[] = array(
                    'id'         => $v['variation_id'],
                    'price'      => floatval($var->get_price()),
                    'price_html' => $var->get_price_html(),
                    'attributes' => $v['attributes'],
                    'image'      => $var_image ?: '',
                );
            }

            $result[] = array(
                'id'               => $product->get_id(),
                'name'             => $product->get_name(),
                'description'      => wp_strip_all_tags($product->get_short_description()),
                'image'            => wp_get_attachment_image_url($product->get_image_id(), 'medium') ?: '',
                'attribute_groups' => $attr_groups_out,
                'variations'       => $variations,
            );
        }

        wp_send_json_success($result);
    }

    /**
     * Stores pending story details in user meta and returns the begin-checkout URL.
     */
    public function ajax_prepare_checkout() {
        check_ajax_referer('their_story_prepare_checkout', 'nonce');

        $user = wp_get_current_user();
        if (!in_array('storyteller', (array) $user->roles, true)) {
            wp_send_json_error(array('message' => __('No permission.', 'their-story')));
        }

        $title          = sanitize_text_field(wp_unslash($_POST['story_title'] ?? ''));
        $password       = sanitize_text_field(wp_unslash($_POST['story_password'] ?? ''));
        $subject_name   = sanitize_text_field(wp_unslash($_POST['contribution_subject_name'] ?? ''));
        $relation_label = sanitize_text_field(wp_unslash($_POST['contribution_relation_label'] ?? ''));
        $product_id     = intval($_POST['product_id'] ?? 0);
        $variation_id   = intval($_POST['variation_id'] ?? 0);

        if (empty($title)) {
            wp_send_json_error(array('message' => __('Story title is required.', 'their-story')));
        }
        if (!$product_id || !$variation_id) {
            wp_send_json_error(array('message' => __('Please select a product and size.', 'their-story')));
        }
        if (!class_exists('WooCommerce') || !wc_get_product($variation_id)) {
            wp_send_json_error(array('message' => __('Invalid product selection.', 'their-story')));
        }

        update_user_meta($user->ID, '_their_story_pending_creation', array(
            'title'          => $title,
            'password'       => $password,
            'subject_name'   => $subject_name,
            'relation_label' => $relation_label,
            'product_id'     => $product_id,
            'variation_id'   => $variation_id,
            'storyteller_id' => $user->ID,
            'created_at'     => time(),
        ));

        wp_send_json_success(array(
            'redirect_url' => add_query_arg('their_story_begin_checkout', '1', home_url('/')),
        ));
    }

    public function handle_contributor_thankyou() {
        if (!isset($_GET['their_story_thankyou'])) {
            return;
        }
        $subject      = sanitize_text_field(wp_unslash($_GET['subject'] ?? ''));
        $about_url    = apply_filters('their_story_about_url', home_url('/about/'));
        $register_url = apply_filters('their_story_register_url', wp_registration_url());
        include THEIR_STORY_PLUGIN_DIR . 'templates/contributor-thankyou.php';
        exit;
    }

    public function ajax_prepare_reorder() {
        check_ajax_referer('their_story_prepare_reorder', 'nonce');

        $user = wp_get_current_user();
        if (!in_array('storyteller', (array) $user->roles, true)) {
            wp_send_json_error(array('message' => __('No permission.', 'their-story')));
        }

        $story_id = intval($_POST['story_id'] ?? 0);
        $qty      = max(1, intval($_POST['qty'] ?? 1));

        if (!$story_id) {
            wp_send_json_error(array('message' => __('Invalid story.', 'their-story')));
        }

        // Verify storyteller owns this story
        $storyteller_id = intval(get_post_meta($story_id, '_storyteller_id', true));
        if ($storyteller_id !== $user->ID) {
            wp_send_json_error(array('message' => __('No permission.', 'their-story')));
        }

        $variation_id = intval(get_post_meta($story_id, '_their_story_variation_id', true));
        $product_id   = intval(get_post_meta($story_id, '_their_story_product_id', true));

        if (!$variation_id || !$product_id) {
            wp_send_json_error(array('message' => __('Original order details not found. Please contact support.', 'their-story')));
        }

        if (!class_exists('WooCommerce') || !wc_get_product($variation_id)) {
            wp_send_json_error(array('message' => __('Product no longer available.', 'their-story')));
        }

        update_user_meta($user->ID, '_their_story_pending_reorder', array(
            'story_id'     => $story_id,
            'variation_id' => $variation_id,
            'product_id'   => $product_id,
            'qty'          => $qty,
            'created_at'   => time(),
        ));

        wp_send_json_success(array(
            'redirect_url' => add_query_arg('their_story_begin_checkout', '1', home_url('/')),
        ));
    }

    /**
     * Handles the begin-checkout redirect: adds product to WC cart then redirects to checkout.
     */
    public function handle_begin_checkout() {
        if (!isset($_GET['their_story_begin_checkout'])) {
            return;
        }

        $user = wp_get_current_user();
        if (!$user->ID || !in_array('storyteller', (array) $user->roles, true)) {
            wp_safe_redirect(wp_login_url(home_url('/?their_story_begin_checkout=1')));
            exit;
        }

        if (!class_exists('WooCommerce')) {
            wp_safe_redirect(admin_url('admin.php?page=their-story-dashboard'));
            exit;
        }

        // Check for reorder first
        $reorder = get_user_meta($user->ID, '_their_story_pending_reorder', true);
        if (!empty($reorder) && is_array($reorder)) {
            if (!empty($reorder['created_at']) && (time() - intval($reorder['created_at'])) > 3600) {
                delete_user_meta($user->ID, '_their_story_pending_reorder');
                wp_safe_redirect(admin_url('admin.php?page=their-story-dashboard&their_story_error=expired'));
                exit;
            }

            delete_user_meta($user->ID, '_their_story_pending_reorder');

            $variation = wc_get_product($reorder['variation_id']);
            $var_attrs = array();
            if ($variation) {
                foreach ($variation->get_variation_attributes() as $key => $value) {
                    $var_attrs[$key] = $value;
                }
            }

            WC()->cart->empty_cart();

            $cart_key = WC()->cart->add_to_cart(
                $reorder['product_id'],
                intval($reorder['qty']),
                $reorder['variation_id'],
                $var_attrs,
                array(
                    'their_story_pending' => array(
                        'reorder_story_id' => $reorder['story_id'],
                    ),
                )
            );

            if (!$cart_key) {
                wp_safe_redirect(admin_url('admin.php?page=their-story-dashboard&their_story_error=cart'));
                exit;
            }

            wp_safe_redirect(wc_get_checkout_url());
            exit;
        }

        // New story checkout
        $pending = get_user_meta($user->ID, '_their_story_pending_creation', true);
        if (empty($pending) || !is_array($pending) || empty($pending['title'])) {
            wp_safe_redirect(admin_url('admin.php?page=their-story-dashboard'));
            exit;
        }

        // Expire after 1 hour to prevent stale replays
        if (!empty($pending['created_at']) && (time() - intval($pending['created_at'])) > 3600) {
            delete_user_meta($user->ID, '_their_story_pending_creation');
            wp_safe_redirect(admin_url('admin.php?page=their-story-dashboard&their_story_error=expired'));
            exit;
        }

        delete_user_meta($user->ID, '_their_story_pending_creation');

        WC()->cart->empty_cart();

        $variation    = wc_get_product($pending['variation_id']);
        $var_attrs    = array();
        if ($variation) {
            foreach ($variation->get_variation_attributes() as $key => $value) {
                $var_attrs[$key] = $value;
            }
        }

        $cart_key = WC()->cart->add_to_cart(
            $pending['product_id'],
            1,
            $pending['variation_id'],
            $var_attrs,
            array(
                'their_story_pending' => array(
                    'title'          => $pending['title'],
                    'password'       => $pending['password'],
                    'subject_name'   => $pending['subject_name'],
                    'relation_label' => $pending['relation_label'],
                    'storyteller_id' => $pending['storyteller_id'],
                ),
                'their_story_setup_fee' => true,
            )
        );

        if (!$cart_key) {
            wp_safe_redirect(admin_url('admin.php?page=their-story-dashboard&their_story_error=cart'));
            exit;
        }

        wp_safe_redirect(wc_get_checkout_url());
        exit;
    }

    /**
     * Creates the story page once payment is confirmed.
     * Hooked to woocommerce_payment_complete and woocommerce_order_status_processing.
     */
    public function create_story_on_payment($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Prevent double-processing
        if ($order->get_meta('_their_story_created')) {
            return;
        }

        foreach ($order->get_items() as $item) {
            $pending_json = $item->get_meta('_their_story_pending');
            if (!$pending_json) {
                continue;
            }

            $pending = json_decode($pending_json, true);
            if (!is_array($pending)) {
                continue;
            }

            // --- Reorder: existing story, just link the order ---
            if (!empty($pending['reorder_story_id'])) {
                $story_id    = intval($pending['reorder_story_id']);
                $story_title = get_the_title($story_id);

                $item->add_meta_data('_their_story_id', $story_id, true);
                $item->save();

                $order->update_meta_data('_their_story_created', '1');
                $order->update_meta_data('_their_story_id', $story_id);
                $order->update_meta_data('_their_story_reorder', '1');
                $order->save();

                // Notify support of reorder
                wp_mail(
                    'grant@sharetheirstory.com.au',
                    sprintf(__('Reorder placed for story: %s', 'their-story'), $story_title),
                    sprintf(
                        __("A reorder has been placed.\n\nStory: %s\nStory ID: %d\nOrder ID: %d\nQty: %d", 'their-story'),
                        $story_title,
                        $story_id,
                        $order_id,
                        $item->get_quantity()
                    )
                );

                break;
            }

            // --- New story creation ---
            if (empty($pending['title'])) {
                continue;
            }

            $storyteller_id = intval($pending['storyteller_id']);
            $storyteller    = get_userdata($storyteller_id);
            if (!$storyteller) {
                continue;
            }

            $page_data = array(
                'post_title'  => $pending['title'],
                'post_status' => 'publish',
                'post_type'   => 'page',
                'post_author' => $storyteller_id,
            );
            if (!empty($pending['password'])) {
                $page_data['post_password'] = $pending['password'];
            }

            $page_id = wp_insert_post($page_data);
            if (is_wp_error($page_id)) {
                continue;
            }

            update_post_meta($page_id, '_storyteller_id', $storyteller_id);
            $unique_link = $this->generate_unique_link($page_id);
            update_post_meta($page_id, '_story_unique_link', $unique_link);

            if (!empty($pending['subject_name'])) {
                update_post_meta($page_id, '_their_story_contribution_subject_name', $pending['subject_name']);
            }
            if (!empty($pending['relation_label'])) {
                update_post_meta($page_id, '_their_story_contribution_relation_label', $pending['relation_label']);
            }

            // Save the purchased variation so reorders can look it up
            $variation_id = $item->get_variation_id();
            $product_id   = $item->get_product_id();
            if ($variation_id) {
                update_post_meta($page_id, '_their_story_variation_id', $variation_id);
            }
            if ($product_id) {
                update_post_meta($page_id, '_their_story_product_id', $product_id);
            }

            // Link story back to the order item and order
            $item->add_meta_data('_their_story_id', $page_id, true);
            $item->save();

            $order->update_meta_data('_their_story_created', '1');
            $order->update_meta_data('_their_story_id', $page_id);
            $order->save();

            // Confirmation email to storyteller
            $story_url     = $this->get_story_url_from_link($unique_link);
            $dashboard_url = admin_url('admin.php?page=their-story-dashboard');

            $subject = sprintf(
                /* translators: %s: story title */
                __('Your story "%s" is ready!', 'their-story'),
                $pending['title']
            );
            $message = sprintf(
                __("Hi %s,\n\nYour story \"%s\" has been created and is ready to share!\n\nShare this link with friends and family so they can contribute:\n%s\n\nManage your story from your dashboard:\n%s\n\nThank you,\nThe Share Their Story Team", 'their-story'),
                $storyteller->display_name,
                $pending['title'],
                $story_url,
                $dashboard_url
            );
            wp_mail($storyteller->user_email, $subject, $message);

            // Only create one story per order
            break;
        }
    }
}

