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
                                        <a href="<?php echo esc_url($story_url); ?>" target="_blank" class="their-story-action-btn" title="<?php echo esc_attr__('View Story', 'their-story'); ?>">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </a>
                                        <?php
                                        $csv_url = $their_story->get_csv_export_url($story->ID);
                                        ?>
                                        <a href="<?php echo esc_url($csv_url); ?>" class="their-story-action-btn" title="<?php echo esc_attr__('Download CSV', 'their-story'); ?>">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                            </svg>
                                        </a>
                                        <button type="button" class="their-story-action-btn their-story-password-btn" data-story-id="<?php echo esc_attr($story->ID); ?>" data-story-title="<?php echo esc_attr($story->post_title); ?>" title="<?php echo esc_attr__('Change Password', 'their-story'); ?>">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                            </svg>
                                        </button>
                                        <?php if ($is_closed) : ?>
                                            <button type="button" class="their-story-action-btn their-story-reopen-btn" data-story-id="<?php echo esc_attr($story->ID); ?>" data-story-title="<?php echo esc_attr($story->post_title); ?>" title="<?php echo esc_attr__('Re-open Story', 'their-story'); ?>">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                            </button>
                                        <?php endif; ?>
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
</div>
