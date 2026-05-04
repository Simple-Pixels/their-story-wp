<?php

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap their-story-dashboard">
    <div class="their-story-brand-header">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="their-story-brand-logo-link" rel="home">
            <img
                src="<?php echo esc_url('https://theirstory.kinsta.cloud/wp-content/uploads/2025/12/Their-Story-Temp-Logo.png'); ?>"
                alt="<?php echo esc_attr(get_bloginfo('name')); ?>"
                class="their-story-brand-logo"
                decoding="async"
            />
        </a>
    </div>

    <div class="their-story-actions">
        <div class="their-story-actions-text">
            <h2 class="their-story-section-title"><?php echo esc_html__('Your Stories', 'their-story'); ?></h2>
            <p class="their-story-section-lede"><?php echo esc_html__('Edit or manage your stories', 'their-story'); ?></p>
        </div>
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
                <div class="their-story-form-field">
                    <label for="contribution-subject-name" class="their-story-label">
                        <?php echo esc_html__('Contributor invitation — first name of person stories are about (optional)', 'their-story'); ?>
                    </label>
                    <input type="text" id="contribution-subject-name" name="contribution_subject_name" class="their-story-input" maxlength="120" placeholder="<?php echo esc_attr__('e.g. Lucy', 'their-story'); ?>" />
                    <p class="their-story-help-text"><?php echo esc_html__('Shown on the contributor form. If left blank, the story title is used.', 'their-story'); ?></p>
                </div>
                <div class="their-story-form-field">
                    <label for="contribution-relation-label" class="their-story-label">
                        <?php echo esc_html__('Relationship phrase (optional)', 'their-story'); ?>
                    </label>
                    <input type="text" id="contribution-relation-label" name="contribution_relation_label" class="their-story-input" maxlength="120" placeholder="<?php echo esc_attr__('e.g. family member or friend', 'their-story'); ?>" />
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
                            <th><?php echo esc_html__('Unique Link', 'their-story'); ?></th>
                            <th><?php echo esc_html__('Created', 'their-story'); ?></th>
                            <th><?php echo esc_html__('Actions', 'their-story'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stories as $story) : 
                            $unique_link = get_post_meta($story->ID, '_story_unique_link', true);
                            $is_password_protected = post_password_required($story->ID);
                            $story_url = get_permalink($story->ID);
                            $their_story = new Their_Story();
                            $obfuscated_url = $their_story->get_story_url_from_link($unique_link);
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
                                    <?php if ($unique_link && $obfuscated_url) : ?>
                                        <div class="their-story-link-group">
                                            <code class="their-story-code"><?php echo esc_html($obfuscated_url); ?></code>
                                            <button type="button" class="copy-link-btn their-story-btn-small" data-link="<?php echo esc_attr($obfuscated_url); ?>">
                                                <?php echo esc_html__('Copy', 'their-story'); ?>
                                            </button>
                                        </div>
                                    <?php else : ?>
                                        <span class="their-story-muted"><?php echo esc_html__('Not generated yet', 'their-story'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="their-story-muted">
                                    <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($story->post_date))); ?>
                                </td>
                                <td>
                                    <div class="their-story-actions-inline">
                                        <a href="<?php echo esc_url($story_url); ?>" target="_blank" class="their-story-action-btn" title="<?php echo esc_attr__('View Story', 'their-story'); ?>">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </a>
                                        <button type="button" class="their-story-action-btn their-story-password-btn" data-story-id="<?php echo esc_attr($story->ID); ?>" data-story-title="<?php echo esc_attr($story->post_title); ?>" title="<?php echo esc_attr__('Change Password', 'their-story'); ?>">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                            </svg>
                                        </button>
                                        <button type="button" class="their-story-action-btn their-story-delete-btn" data-story-id="<?php echo esc_attr($story->ID); ?>" data-story-title="<?php echo esc_attr($story->post_title); ?>" title="<?php echo esc_attr__('Delete Story', 'their-story'); ?>">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="their-story-dashboard-footer">
        <a class="their-story-logout-btn" href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">
            <?php esc_html_e('Log out', 'their-story'); ?>
        </a>
        <a
            class="their-story-help-btn"
            href="<?php echo esc_url(apply_filters('their_story_help_url', 'https://theirstory.kinsta.cloud')); ?>"
            target="_blank"
            rel="noopener noreferrer"
        >
            <?php esc_html_e('Get help', 'their-story'); ?>
        </a>
    </div>
</div>
