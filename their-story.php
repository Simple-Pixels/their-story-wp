<?php
/**
 * Plugin Name: Their Story
 * Description: A WordPress plugin to power Their Story
 * Version: 1.1.3
 * Author: Simple Pixels

 */

if (!defined('ABSPATH')) {
    exit;
}

define('THEIR_STORY_VERSION', '1.0.0');
define('THEIR_STORY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('THEIR_STORY_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once THEIR_STORY_PLUGIN_DIR . 'includes/class-their-story.php';

function their_story_init() {
    $their_story = new Their_Story();
    $their_story->init();
}
add_action('plugins_loaded', 'their_story_init');

register_activation_hook(__FILE__, 'their_story_activate');
function their_story_activate() {
    $their_story = new Their_Story();
    $their_story->flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, 'their_story_deactivate');
function their_story_deactivate() {
    flush_rewrite_rules();
}

