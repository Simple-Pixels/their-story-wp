<?php

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap their-story-admin-dashboard">
    <div class="their-story-header-section">
        <h1 class="their-story-title"><?php echo esc_html__('All Submissions', 'their-story'); ?></h1>
        <p class="their-story-subtitle"><?php echo esc_html__('Review and approve submissions from all stories', 'their-story'); ?></p>
    </div>  
    
    <?php
    $pending_submissions = array_filter($submissions, function($sub) {
        return $sub->post_status === 'pending';
    });
    $approved_submissions = array_filter($submissions, function($sub) {
        return $sub->post_status === 'publish';
    });
    ?>
    
    <div class="their-story-stats-grid">
        <div class="their-story-stat-card">
            <div class="their-story-stat-content">
                <div class="their-story-stat-icon their-story-stat-icon-orange">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="their-story-stat-text">
                    <h3 class="their-story-stat-label"><?php echo esc_html__('Pending Submissions', 'their-story'); ?></h3>
                    <p class="their-story-stat-number"><?php echo esc_html(count($pending_submissions)); ?></p>
                </div>
            </div>
        </div>
        <div class="their-story-stat-card">
            <div class="their-story-stat-content">
                <div class="their-story-stat-icon their-story-stat-icon-green">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="their-story-stat-text">
                    <h3 class="their-story-stat-label"><?php echo esc_html__('Approved Submissions', 'their-story'); ?></h3>
                    <p class="their-story-stat-number"><?php echo esc_html(count($approved_submissions)); ?></p>
                </div>
            </div>
        </div>
        <div class="their-story-stat-card">
            <div class="their-story-stat-content">
                <div class="their-story-stat-icon their-story-stat-icon-blue">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="their-story-stat-text">
                    <h3 class="their-story-stat-label"><?php echo esc_html__('Total Submissions', 'their-story'); ?></h3>
                    <p class="their-story-stat-number"><?php echo esc_html(count($submissions)); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="their-story-list">
        <?php if (empty($submissions)) : ?>
            <div class="their-story-empty">
                <svg class="their-story-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="their-story-empty-text"><?php echo esc_html__('No submissions have been made yet.', 'their-story'); ?></p>
            </div>
        <?php else : ?>
            <div class="their-story-table-wrapper">
                <table class="their-story-table">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Submission', 'their-story'); ?></th>
                            <th><?php echo esc_html__('Story', 'their-story'); ?></th>
                            <th><?php echo esc_html__('Storyteller', 'their-story'); ?></th>
                            <th><?php echo esc_html__('Status', 'their-story'); ?></th>
                            <th><?php echo esc_html__('Submitted', 'their-story'); ?></th>
                            <th><?php echo esc_html__('Actions', 'their-story'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission) : 
                            $submission_name = $submission->submission_name ? $submission->submission_name : get_post_meta($submission->ID, '_submission_name', true);
                            $image_id = $submission->submission_image_id ? $submission->submission_image_id : get_post_meta($submission->ID, '_submission_image_id', true);
                            $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
                            $story = $submission->story;
                            $storyteller = isset($submission->storyteller) ? $submission->storyteller : null;
                            $story_url = $story ? get_permalink($story->ID) : '';
                            $is_story_closed = $story ? (get_post_meta($story->ID, '_story_closed', true) === '1') : false;
                        ?>
                            <tr data-submission-id="<?php echo esc_attr($submission->ID); ?>" class="submission-status-<?php echo esc_attr($submission->post_status); ?>">
                                <td>
                                    <div class="their-story-table-cell">
                                        <div class="their-story-submission-preview">
                                            <?php if ($image_url) : ?>
                                                <div class="their-story-submission-thumb">
                                                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($submission_name); ?>" />
                                                </div>
                                            <?php endif; ?>
                                            <div class="their-story-submission-info">
                                                <strong class="their-story-submission-name"><?php echo esc_html($submission_name); ?></strong>
                                                <div class="their-story-submission-excerpt their-story-muted their-story-text-xs">
                                                    <?php echo esc_html(wp_trim_words($submission->post_content, 20)); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($story) : ?>
                                        <div class="their-story-table-cell">
                                            <a href="<?php echo esc_url($story_url); ?>" target="_blank" class="their-story-link">
                                                <?php echo esc_html($story->post_title); ?>
                                            </a>
                                        </div>
                                    <?php else : ?>
                                        <span class="their-story-muted"><?php echo esc_html__('Story not found', 'their-story'); ?></span>
                                    <?php endif; ?>
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
                                    <span class="story-status status-<?php echo esc_attr($submission->post_status); ?>">
                                        <?php echo esc_html(ucfirst($submission->post_status)); ?>
                                    </span>
                                </td>
                                <td class="their-story-muted">
                                    <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission->post_date))); ?>
                                </td>
                                <td>
                                    <div class="their-story-actions-inline">
                                        <?php if ($submission->post_status === 'pending' && !$is_story_closed) : ?>
                                            <button type="button" class="their-story-link their-story-approve-submission-btn" data-submission-id="<?php echo esc_attr($submission->ID); ?>" style="cursor: pointer; background: none; border: none; padding: 0; text-decoration: underline; color: #46b450;">
                                                <?php echo esc_html__('Approve', 'their-story'); ?>
                                            </button>
                                            <span class="their-story-divider">|</span>
                                        <?php endif; ?>
                                        <?php if (!$is_story_closed) : ?>
                                        <button type="button" class="their-story-link their-story-delete-submission-btn" data-submission-id="<?php echo esc_attr($submission->ID); ?>" style="color: #dc3232; cursor: pointer; background: none; border: none; padding: 0; text-decoration: underline;">
                                            <?php echo esc_html__('Delete', 'their-story'); ?>
                                        </button>
                                        <span class="their-story-divider">|</span>
                                        <?php endif; ?>
                                        <a href="<?php echo esc_url($story_url); ?>" target="_blank" class="their-story-link">
                                            <?php echo esc_html__('View Story', 'their-story'); ?>
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

