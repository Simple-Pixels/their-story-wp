<?php

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap their-story-admin-dashboard">
    <div class="their-story-header-section">
        <h1 class="their-story-title"><?php echo esc_html__('All Stories Overview', 'their-story'); ?></h1>
        <p class="their-story-subtitle"><?php echo esc_html__('Manage all stories from all storytellers', 'their-story'); ?></p>
    </div>  
    
    <div class="their-story-stats-grid">
        <div class="their-story-stat-card">
            <div class="their-story-stat-content">
                <div class="their-story-stat-icon their-story-stat-icon-blue">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="their-story-stat-text">
                    <h3 class="their-story-stat-label"><?php echo esc_html__('Total Stories', 'their-story'); ?></h3>
                    <p class="their-story-stat-number"><?php echo esc_html(count($stories)); ?></p>
                </div>
            </div>
        </div>
        <div class="their-story-stat-card">
            <div class="their-story-stat-content">
                <div class="their-story-stat-icon their-story-stat-icon-green">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
                <div class="their-story-stat-text">
                    <h3 class="their-story-stat-label"><?php echo esc_html__('Total Storytellers', 'their-story'); ?></h3>
                    <p class="their-story-stat-number">
                        <?php
                        $storyteller_ids = array_unique(array_map(function($story) {
                            return get_post_meta($story->ID, '_storyteller_id', true);
                        }, $stories));
                        echo esc_html(count($storyteller_ids));
                        ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="their-story-stat-card">
            <div class="their-story-stat-content">
                <div class="their-story-stat-icon their-story-stat-icon-orange">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="their-story-stat-text">
                    <h3 class="their-story-stat-label"><?php echo esc_html__('Pending Submissions', 'their-story'); ?></h3>
                    <p class="their-story-stat-number"><?php echo esc_html($pending_count); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="their-story-list">
        <?php if (empty($stories)) : ?>
            <div class="their-story-empty">
                <svg class="their-story-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="their-story-empty-text"><?php echo esc_html__('No stories have been created yet.', 'their-story'); ?></p>
            </div>
        <?php else : ?>
            <div class="their-story-table-wrapper">
                <table class="their-story-table">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Title', 'their-story'); ?></th>
                            <th><?php echo esc_html__('Storyteller', 'their-story'); ?></th>
                            <th><?php echo esc_html__('Status', 'their-story'); ?></th>
                            <th><?php echo esc_html__('Unique Link', 'their-story'); ?></th>
                            <th><?php echo esc_html__('Created', 'their-story'); ?></th>
                            <th><?php echo esc_html__('Actions', 'their-story'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stories as $story) : 
                            $storyteller = $story->storyteller;
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
                                    <?php if ($storyteller) : ?>
                                        <div class="their-story-table-cell">
                                            <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $storyteller->ID)); ?>" class="their-story-link">
                                                <?php echo esc_html($storyteller->display_name); ?>
                                            </a>
                                            <div class="their-story-muted their-story-text-xs"><?php echo esc_html($storyteller->user_email); ?></div>
                                        </div>
                                    <?php else : ?>
                                        <span class="their-story-muted"><?php echo esc_html__('Unknown', 'their-story'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $is_closed = isset($story->is_closed) ? $story->is_closed : (get_post_meta($story->ID, '_story_closed', true) === '1');
                                    $is_active = ($story->post_status === 'publish' && !$is_closed);
                                    $status_class = $is_active ? 'active' : 'closed';
                                    $status_text = $is_active ? __('Active', 'their-story') : __('Closed', 'their-story');
                                    ?>
                                    <span class="story-status status-<?php echo esc_attr($status_class); ?>">
                                        <?php echo esc_html($status_text); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $their_story = new Their_Story();
                                    $obfuscated_url = $their_story->get_story_url_from_link($story->unique_link);
                                    if ($story->unique_link && $obfuscated_url) : ?>
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
                                        <a href="<?php echo esc_url($story_url); ?>" target="_blank" class="their-story-link">
                                            <?php echo esc_html__('View', 'their-story'); ?>
                                        </a>
                                        <span class="their-story-divider">|</span>
                                        <button type="button" class="their-story-link their-story-password-btn" data-story-id="<?php echo esc_attr($story->ID); ?>" data-story-title="<?php echo esc_attr($story->post_title); ?>" style="cursor: pointer; background: none; border: none; padding: 0; text-decoration: underline;">
                                            <?php echo esc_html__('Change Password', 'their-story'); ?>
                                        </button>
                                        <span class="their-story-divider">|</span>
                                        <button type="button" class="their-story-link their-story-delete-btn" data-story-id="<?php echo esc_attr($story->ID); ?>" data-story-title="<?php echo esc_attr($story->post_title); ?>" style="color: #dc3232; cursor: pointer; background: none; border: none; padding: 0; text-decoration: underline;">
                                            <?php echo esc_html__('Delete', 'their-story'); ?>
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
</div>
