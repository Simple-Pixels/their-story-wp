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
                            <th><?php echo esc_html__('Password Protected', 'their-story'); ?></th>
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
                                    <span class="story-status status-<?php echo esc_attr($story->post_status); ?>">
                                        <?php echo esc_html(ucfirst($story->post_status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($story->unique_link) : ?>
                                        <div class="their-story-link-group">
                                            <code class="their-story-code"><?php echo esc_html($story->unique_link); ?></code>
                                            <button type="button" class="copy-link-btn their-story-btn-small" data-link="<?php echo esc_attr($story->unique_link); ?>">
                                                <?php echo esc_html__('Copy', 'their-story'); ?>
                                            </button>
                                        </div>
                                    <?php else : ?>
                                        <span class="their-story-muted"><?php echo esc_html__('Not generated yet', 'their-story'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="their-story-icon-group">
                                        <?php if ($story->is_password_protected) : ?>
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
