<?php

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
?>

<div class="wrap their-story-dashboard">
    <div class="their-story-header-section">
        <h1 class="their-story-title"><?php echo esc_html__('My Stories Dashboard', 'their-story'); ?></h1>
        <p class="their-story-subtitle"><?php echo esc_html__('Create and manage your stories', 'their-story'); ?></p>
    </div>
    
    <?php
    $welcome_transient = get_transient('their_story_welcome_' . $current_user->ID);
    if ($welcome_transient) {
        delete_transient('their_story_welcome_' . $current_user->ID);
        echo '<div class="their-story-notice their-story-notice-success">';
        echo '<p>' . esc_html__('Welcome to your Stories Dashboard! You can now create and manage your stories.', 'their-story') . '</p>';
        echo '</div>';
    }
    ?>
    
    <div class="their-story-actions">
        <h2 class="their-story-section-title"><?php echo esc_html__('Your Stories', 'their-story'); ?></h2>
        <button type="button" id="create-story-btn" class="their-story-btn their-story-btn-primary">
            <svg class="their-story-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <?php echo esc_html__('Create New Story', 'their-story'); ?>
        </button>
    </div>
    
    <div id="create-story-form" style="display: none;" class="their-story-form">
        <h3 class="their-story-form-title"><?php echo esc_html__('Create New Story', 'their-story'); ?></h3>
        <form id="story-creation-form">
            <div class="their-story-form-fields">
                <div class="their-story-form-field">
                    <label for="story-title" class="their-story-label">
                        <?php echo esc_html__('Story Title', 'their-story'); ?>
                    </label>
                    <input type="text" id="story-title" name="story_title" required 
                           class="their-story-input" 
                           placeholder="<?php echo esc_attr__('Enter story title...', 'their-story'); ?>" />
                </div>
                <div class="their-story-form-field">
                    <label for="story-password" class="their-story-label">
                        <?php echo esc_html__('Password (Optional)', 'their-story'); ?>
                    </label>
                    <input type="password" id="story-password" name="story_password" 
                           class="their-story-input" 
                           placeholder="<?php echo esc_attr__('Leave blank for no password', 'their-story'); ?>" />
                    <p class="their-story-help-text"><?php echo esc_html__('Leave blank if you don\'t want to password protect this story.', 'their-story'); ?></p>
                </div>
            </div>
            <div class="their-story-form-actions">
                <button type="submit" class="their-story-btn their-story-btn-primary">
                    <?php echo esc_html__('Create Story', 'their-story'); ?>
                </button>
                <button type="button" id="cancel-story-btn" class="their-story-btn their-story-btn-secondary">
                    <?php echo esc_html__('Cancel', 'their-story'); ?>
                </button>
            </div>
        </form>
    </div>
    
    <div class="their-story-list">
        <?php if (empty($stories)) : ?>
            <div class="their-story-empty">
                <svg class="their-story-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="their-story-empty-text"><?php echo esc_html__('You haven\'t created any stories yet.', 'their-story'); ?></p>
                <p class="their-story-empty-subtext"><?php echo esc_html__('Click "Create New Story" to get started!', 'their-story'); ?></p>
            </div>
        <?php else : ?>
            <div class="their-story-table-wrapper">
                <table class="their-story-table">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Title', 'their-story'); ?></th>
                            <th><?php echo esc_html__('Status', 'their-story'); ?></th>
                            <th><?php echo esc_html__('Unique Link', 'their-story'); ?></th>
                            <th><?php echo esc_html__('Password Protected', 'their-story'); ?></th>
                            <th><?php echo esc_html__('Created', 'their-story'); ?></th>
                            <th><?php echo esc_html__('Actions', 'their-story'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stories as $story) : 
                            $unique_link = get_post_meta($story->ID, '_story_unique_link', true);
                            $is_password_protected = post_password_required($story->ID);
                            $story_url = get_permalink($story->ID);
                        ?>
                            <tr>
                                <td>
                                    <div class="their-story-table-cell">
                                        <a href="<?php echo esc_url($story_url); ?>" target="_blank" class="their-story-link">
                                            <?php echo esc_html($story->post_title); ?>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <span class="story-status status-<?php echo esc_attr($story->post_status); ?>">
                                        <?php echo esc_html(ucfirst($story->post_status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($unique_link) : ?>
                                        <div class="their-story-link-group">
                                            <code class="their-story-code"><?php echo esc_html($unique_link); ?></code>
                                            <button type="button" class="copy-link-btn their-story-btn-small" data-link="<?php echo esc_attr($unique_link); ?>">
                                                <?php echo esc_html__('Copy', 'their-story'); ?>
                                            </button>
                                        </div>
                                    <?php else : ?>
                                        <span class="their-story-muted"><?php echo esc_html__('Not generated yet', 'their-story'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="their-story-icon-group">
                                        <?php if ($is_password_protected) : ?>
                                            <svg class="their-story-icon-small their-story-icon-lock" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span><?php echo esc_html__('Yes', 'their-story'); ?></span>
                                        <?php else : ?>
                                            <svg class="their-story-icon-small their-story-icon-unlock" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="their-story-muted"><?php echo esc_html__('No', 'their-story'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="their-story-muted">
                                    <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($story->post_date))); ?>
                                </td>
                                <td>
                                    <div class="their-story-actions-inline">
                                        <a href="<?php echo esc_url(admin_url('post.php?post=' . $story->ID . '&action=edit')); ?>" class="their-story-link">
                                            <?php echo esc_html__('Edit', 'their-story'); ?>
                                        </a>
                                        <span class="their-story-divider">|</span>
                                        <a href="<?php echo esc_url($story_url); ?>" target="_blank" class="their-story-link">
                                            <?php echo esc_html__('View', 'their-story'); ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
