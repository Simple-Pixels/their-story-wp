<?php

if (!defined('ABSPATH')) {
    exit;
}

global $post;
$story_id = $post->ID;
$current_user = wp_get_current_user();
$storyteller_id = get_post_meta($story_id, '_storyteller_id', true);
$can_moderate = in_array('administrator', $current_user->roles);
$is_storyteller = in_array('storyteller', $current_user->roles) && ($storyteller_id == $current_user->ID);
$can_close_story = $can_moderate || $is_storyteller;
$is_story_closed = get_post_meta($story_id, '_story_closed', true) === '1';

$all_submissions = Their_Story::get_story_submissions_static($story_id, false);
$approved_submissions = Their_Story::get_story_submissions_static($story_id, true);
$pending_submissions = array_filter($all_submissions, function($sub) {
    return $sub->post_status === 'pending';
});

$unique_link = get_post_meta($story_id, '_story_unique_link', true);
$story_url = get_permalink($story_id);
$password = $post->post_password;
$their_story = new Their_Story();
$obfuscated_url = $their_story->get_story_url_from_link($unique_link);
?>

<div class="their-story-page-content">
    
    <?php if (!$is_story_closed) : ?>
    <div class="their-story-form-section">
        <h2 class="their-story-section-title"><?php echo esc_html__('Share Your Message', 'their-story'); ?></h2>
        <form id="their-story-submission-form" class="their-story-submission-form">
            <div class="their-story-form-field">
                <label for="submission-name" class="their-story-label">
                    <?php echo esc_html__('Your Name', 'their-story'); ?>
                    <span class="required">*</span>
                </label>
                <input type="text" id="submission-name" name="name" required class="their-story-input" />
            </div>
            
            <div class="their-story-form-field">
                <label for="submission-message" class="their-story-label">
                    <?php echo esc_html__('Your Message', 'their-story'); ?>
                    <span class="required">*</span>
                </label>
                <textarea id="submission-message" name="message" required rows="5" class="their-story-textarea"></textarea>
            </div>
            
            <div class="their-story-form-field">
                <label for="submission-image" class="their-story-label">
                    <?php echo esc_html__('Image (Optional)', 'their-story'); ?>
                </label>
                <input type="file" id="submission-image" name="image" accept="image/*" class="their-story-file-input" />
                <div id="image-preview" class="their-story-image-preview" style="display: none;">
                    <img id="preview-img" src="" alt="Preview" />
                    <button type="button" id="remove-image" class="their-story-btn-small"><?php echo esc_html__('Remove', 'their-story'); ?></button>
                </div>
            </div>
            
            <button type="submit" class="their-story-btn their-story-btn-primary">
                <?php echo esc_html__('Submit Message', 'their-story'); ?>
            </button>
            
            <div id="submission-status-message" class="their-story-message" style="display: none;"></div>
        </form>
    </div>
    <?php else : ?>
    <div class="their-story-form-section">
        <div class="their-story-notice" style="background-color: #fef2f2; border: 1px solid #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.5rem;">
            <p style="margin: 0; font-weight: 500;"><?php echo esc_html__('This story is closed. No new messages can be added.', 'their-story'); ?></p>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($can_moderate && !empty($pending_submissions) && !$is_story_closed) : ?>
    <div class="their-story-moderation-section">
        <h2 class="their-story-section-title"><?php echo esc_html__('Pending Submissions', 'their-story'); ?></h2>
        <div class="their-story-pending-list">
            <?php foreach ($pending_submissions as $submission) : 
                $submission_name = $submission->submission_name ? $submission->submission_name : get_post_meta($submission->ID, '_submission_name', true);
                $image_id = $submission->submission_image_id ? $submission->submission_image_id : get_post_meta($submission->ID, '_submission_image_id', true);
                $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';
            ?>
                <div class="their-story-submission-item their-story-pending" data-submission-id="<?php echo esc_attr($submission->ID); ?>">
                    <div class="their-story-submission-content">
                        <div class="their-story-submission-header">
                            <h3 class="their-story-submission-name"><?php echo esc_html($submission_name); ?></h3>
                            <span class="their-story-submission-date"><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission->post_date))); ?></span>
                        </div>
                        <div class="their-story-submission-message">
                            <?php echo wp_kses_post(nl2br($submission->post_content)); ?>
                        </div>
                        <?php if ($image_url) : ?>
                            <div class="their-story-submission-image">
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($submission_name); ?>" />
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="their-story-submission-actions">
                        <button type="button" class="their-story-btn their-story-btn-approve" data-action="approve">
                            <?php echo esc_html__('Approve', 'their-story'); ?>
                        </button>
                        <button type="button" class="their-story-btn their-story-btn-delete" data-action="delete">
                            <?php echo esc_html__('Delete', 'their-story'); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="their-story-messages-section">
        <h2 class="their-story-section-title"><?php echo esc_html__('Messages', 'their-story'); ?></h2>
        <?php if (empty($approved_submissions)) : ?>
            <div class="their-story-empty">
                <p><?php echo esc_html__('No messages yet. Be the first to share!', 'their-story'); ?></p>
            </div>
        <?php else : ?>
            <div class="their-story-messages-list">
                <?php foreach ($approved_submissions as $submission) : 
                    $submission_name = $submission->submission_name ? $submission->submission_name : get_post_meta($submission->ID, '_submission_name', true);
                    $image_id = $submission->submission_image_id ? $submission->submission_image_id : get_post_meta($submission->ID, '_submission_image_id', true);
                    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'large') : '';
                ?>
                    <div class="their-story-submission-item their-story-approved" data-submission-id="<?php echo esc_attr($submission->ID); ?>">
                        <div class="their-story-submission-header">
                            <h3 class="their-story-submission-name"><?php echo esc_html($submission_name); ?></h3>
                            <span class="their-story-submission-date"><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission->post_date))); ?></span>
                        </div>
                        <div class="their-story-submission-message">
                            <?php echo wp_kses_post(nl2br($submission->post_content)); ?>
                        </div>
                        <?php if ($image_url) : ?>
                            <div class="their-story-submission-image">
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($submission_name); ?>" />
                            </div>
                        <?php endif; ?>
                        <?php if ($can_moderate && !$is_story_closed) : ?>
                            <div class="their-story-submission-actions">
                                <button type="button" class="their-story-btn their-story-btn-delete" data-action="delete">
                                    <?php echo esc_html__('Delete', 'their-story'); ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="their-story-share-section">
        <h2 class="their-story-section-title"><?php echo esc_html__('Share This Story', 'their-story'); ?></h2>
        <div class="their-story-share-info">
            <div class="their-story-share-item">
                <label class="their-story-share-label"><?php echo esc_html__('Story Link', 'their-story'); ?></label>
                <div class="their-story-share-value">
                    <code class="their-story-code" id="story-link-code"><?php echo esc_html($obfuscated_url ? $obfuscated_url : $story_url); ?></code>
                    <button type="button" class="their-story-btn their-story-btn-copy" data-copy-target="story-link-code">
                        <?php echo esc_html__('Copy', 'their-story'); ?>
                    </button>
                </div>
            </div>
            <?php if ($password) : ?>
            <div class="their-story-share-item">
                <label class="their-story-share-label"><?php echo esc_html__('Password', 'their-story'); ?></label>
                <div class="their-story-share-value">
                    <code class="their-story-code" id="story-password-code"><?php echo esc_html($password); ?></code>
                    <button type="button" class="their-story-btn their-story-btn-copy" data-copy-target="story-password-code">
                        <?php echo esc_html__('Copy', 'their-story'); ?>
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php if ($can_close_story && !$is_story_closed) : ?>
        <div class="their-story-close-section" style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
            <button type="button" id="close-story-btn" class="their-story-btn" style="background-color: #dc2626; color: #ffffff;">
                <?php echo esc_html__('Close Story', 'their-story'); ?>
            </button>
            <p class="their-story-help-text" style="margin-top: 0.5rem; color: #6b7280; font-size: 0.875rem;">
                <?php echo esc_html__('Closing the story will prevent any more messages from being added.', 'their-story'); ?>
            </p>
        </div>
        <?php endif; ?>
    </div>
    
</div>
