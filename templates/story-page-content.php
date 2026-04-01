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
$pending_submissions = array_filter($all_submissions, function ($sub) {
    return $sub->post_status === 'pending';
});

$unique_link = get_post_meta($story_id, '_story_unique_link', true);
$story_url = get_permalink($story_id);
$password = $post->post_password;
$their_story = new Their_Story();
$obfuscated_url = $their_story->get_story_url_from_link($unique_link);

$storyteller = $storyteller_id ? get_userdata((int) $storyteller_id) : null;
$display_title = get_the_title($story_id);
$display_title = preg_replace('/^Protected:\s*/i', '', $display_title);
$what_is_this_url = apply_filters('their_story_what_is_this_url', 'https://theirstory.kinsta.cloud');
?><div class="their-story-page-content">

    <header class="their-story-intro">
        <h1 class="their-story-intro-title"><?php echo esc_html($display_title); ?></h1>
        <?php if ($storyteller) : ?>
            <p class="their-story-intro-byline">
                <?php
                printf(
                    /* translators: %s: person name */
                    esc_html__('A message book from %s', 'their-story'),
                    esc_html($storyteller->display_name)
                );
                ?>
            </p>
        <?php endif; ?>
        <div class="their-story-intro-body">
            <?php if (has_excerpt($story_id)) : ?>
                <div class="their-story-intro-custom">
                    <?php echo wp_kses_post(wpautop(get_post($story_id)->post_excerpt)); ?>
                </div>
            <?php else : ?>
                <p><?php echo esc_html('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.'); ?></p>
            <?php endif; ?>
        </div>
        <p class="their-story-intro-what-wrap">
            <a href="<?php echo esc_url($what_is_this_url); ?>" class="their-story-btn their-story-btn-outline their-story-intro-what-link" target="_blank" rel="noopener noreferrer">
                <?php echo esc_html__('What is this?', 'their-story'); ?>
            </a>
        </p>
    </header>

    <?php if (!$is_story_closed) : ?>
    <section class="their-story-form-section their-story-flow-section" aria-labelledby="their-story-form-heading">
        <h2 id="their-story-form-heading" class="their-story-section-title"><?php echo esc_html__('Share your message', 'their-story'); ?></h2>
        <p class="their-story-section-lede"><?php echo esc_html__('Write a note and optionally add up to five images.', 'their-story'); ?></p>
        <form id="their-story-submission-form" class="their-story-submission-form">
            <div class="their-story-form-field">
                <label for="submission-name" class="their-story-label">
                    <?php echo esc_html__('Your name', 'their-story'); ?>
                    <span class="required">*</span>
                </label>
                <input type="text" id="submission-name" name="name" required class="their-story-input" autocomplete="name" />
            </div>

            <div class="their-story-form-field">
                <label for="submission-message" class="their-story-label">
                    <?php echo esc_html__('Your message', 'their-story'); ?>
                    <span class="required">*</span>
                </label>
                <textarea id="submission-message" name="message" required rows="5" class="their-story-textarea"></textarea>
            </div>

            <div class="their-story-form-field">
                <label for="submission-images" class="their-story-label">
                    <?php echo esc_html__('Images (optional, up to 5)', 'their-story'); ?>
                </label>
                <input type="file" id="submission-images" name="images[]" accept="image/*" multiple class="their-story-file-input" />
                <p class="their-story-help-text"><?php echo esc_html__('You can upload up to five images.', 'their-story'); ?></p>
                <div id="images-preview" class="their-story-images-preview"></div>
            </div>

            <button type="submit" class="their-story-btn their-story-btn-primary">
                <?php echo esc_html__('Submit message', 'their-story'); ?>
            </button>

            <div id="submission-status-message" class="their-story-message" style="display: none;"></div>
        </form>
    </section>
    <?php else : ?>
    <section class="their-story-form-section their-story-flow-section">
        <div class="their-story-notice-closed">
            <p><?php echo esc_html__('This story is closed. No new messages can be added.', 'their-story'); ?></p>
        </div>
        <?php if ($is_storyteller) : ?>
        <div class="their-story-shop-section">
            <p><?php echo esc_html__('Next step — add your messages to a book', 'their-story'); ?></p>
            <?php
            $message_count = count($approved_submissions);
            $book_closed_url = home_url('/book-closed');
            $book_closed_url = add_query_arg(array(
                'story' => $story_id,
                'messages' => $message_count,
            ), $book_closed_url);
            ?>
            <a href="<?php echo esc_url($book_closed_url); ?>" class="their-story-btn their-story-btn-primary">
                <?php echo esc_html__('Shop now', 'their-story'); ?>
            </a>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($can_moderate && !empty($pending_submissions) && !$is_story_closed) : ?>
    <section class="their-story-moderation-section their-story-flow-section" aria-labelledby="their-story-pending-heading">
        <h2 id="their-story-pending-heading" class="their-story-section-title"><?php echo esc_html__('Pending submissions', 'their-story'); ?></h2>
        <p class="their-story-section-lede"><?php echo esc_html__('Review and approve messages before they appear below.', 'their-story'); ?></p>
        <div class="their-story-pending-list">
            <?php foreach ($pending_submissions as $submission) :
                $submission_name = $submission->submission_name ? $submission->submission_name : get_post_meta($submission->ID, '_submission_name', true);
                $image_ids = isset($submission->submission_image_ids) ? $submission->submission_image_ids : array();
                ?>
                <div class="their-story-submission-item their-story-pending" data-submission-id="<?php echo esc_attr($submission->ID); ?>">
                    <div class="their-story-submission-content">
                        <div class="their-story-submission-header">
                            <h3 class="their-story-submission-name"><?php echo esc_html($submission_name); ?></h3>
                            <span class="their-story-submission-date"><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission->post_date))); ?></span>
                        </div>
                        <div class="their-story-submission-message">
                            <?php
                            $content = $submission->post_content;
                            $lines = explode("\n", $content);
                            $lines = array_map('trim', $lines);
                            $lines = array_filter($lines, function ($line) {
                                return $line !== '';
                            });
                            $content = implode("\n", $lines);
                            $content = nl2br($content);
                            echo wp_kses_post($content);
                            ?>
                        </div>
                        <?php if (!empty($image_ids)) : ?>
                            <div class="their-story-submission-gallery">
                                <?php foreach ($image_ids as $image_id) :
                                    $image_url = wp_get_attachment_image_url($image_id, 'medium');
                                    $full_image_url = wp_get_attachment_image_url($image_id, 'full');
                                    if ($image_url) :
                                        ?>
                                    <div class="their-story-gallery-item">
                                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($submission_name); ?>" class="their-story-gallery-image" data-full-image="<?php echo esc_url($full_image_url); ?>" />
                                    </div>
                                        <?php
                                    endif;
                                endforeach; ?>
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
    </section>
    <?php endif; ?>

    <section class="their-story-messages-section their-story-flow-section" aria-labelledby="their-story-messages-heading">
        <h2 id="their-story-messages-heading" class="their-story-section-title"><?php echo esc_html__('Messages', 'their-story'); ?></h2>
        <p class="their-story-section-lede"><?php echo esc_html__('Every approved note appears here.', 'their-story'); ?></p>
        <?php if (empty($approved_submissions)) : ?>
            <div class="their-story-empty">
                <p><?php echo esc_html__('No messages yet. Be the first to share.', 'their-story'); ?></p>
            </div>
        <?php else : ?>
            <div class="their-story-messages-list">
                <?php foreach ($approved_submissions as $submission) :
                    $submission_name = $submission->submission_name ? $submission->submission_name : get_post_meta($submission->ID, '_submission_name', true);
                    $image_ids = isset($submission->submission_image_ids) ? $submission->submission_image_ids : array();
                    ?>
                    <div class="their-story-submission-item their-story-approved" data-submission-id="<?php echo esc_attr($submission->ID); ?>">
                        <div class="their-story-submission-header">
                            <h3 class="their-story-submission-name"><?php echo esc_html($submission_name); ?></h3>
                            <span class="their-story-submission-date"><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission->post_date))); ?></span>
                        </div>
                        <div class="their-story-submission-message">
                            <?php
                            $content = $submission->post_content;
                            $lines = explode("\n", $content);
                            $lines = array_map('trim', $lines);
                            $lines = array_filter($lines, function ($line) {
                                return $line !== '';
                            });
                            $content = implode("\n", $lines);
                            $content = nl2br($content);
                            echo wp_kses_post($content);
                            ?>
                        </div>
                        <?php if (!empty($image_ids)) : ?>
                            <div class="their-story-submission-gallery">
                                <?php foreach ($image_ids as $image_id) :
                                    $image_url = wp_get_attachment_image_url($image_id, 'large');
                                    $full_image_url = wp_get_attachment_image_url($image_id, 'full');
                                    if ($image_url) :
                                        ?>
                                    <div class="their-story-gallery-item">
                                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($submission_name); ?>" class="their-story-gallery-image" data-full-image="<?php echo esc_url($full_image_url); ?>" />
                                    </div>
                                        <?php
                                    endif;
                                endforeach; ?>
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
    </section>

    <div class="their-story-page-actions">
        <button type="button" class="their-story-btn their-story-btn-outline" id="their-story-open-share-modal">
            <?php echo esc_html__('Share this story', 'their-story'); ?>
        </button>
        <?php if ($can_close_story && !$is_story_closed) : ?>
            <button type="button" id="close-story-btn" class="their-story-btn their-story-btn-danger">
                <?php echo esc_html__('Close story', 'their-story'); ?>
            </button>
        <?php endif; ?>
    </div>

    <div
        id="their-story-share-modal"
        class="their-story-modal"
        hidden
        aria-hidden="true"
        role="dialog"
        aria-modal="true"
        aria-labelledby="their-story-share-modal-title"
    >
        <div class="their-story-modal-backdrop" tabindex="-1" data-their-story-close-modal></div>
        <div class="their-story-modal-panel" role="document">
            <div class="their-story-modal-header">
                <h2 id="their-story-share-modal-title" class="their-story-modal-title"><?php echo esc_html__('Share this story', 'their-story'); ?></h2>
                <button type="button" class="their-story-modal-close" data-their-story-close-modal aria-label="<?php echo esc_attr__('Close', 'their-story'); ?>">&times;</button>
            </div>
            <div class="their-story-modal-body">
                <p class="their-story-modal-lede"><?php echo esc_html__('Copy the link to invite others. If the page asks for a password, copy that too.', 'their-story'); ?></p>
                <div class="their-story-share-info">
                    <div class="their-story-share-item">
                        <span class="their-story-share-label"><?php echo esc_html__('Story link', 'their-story'); ?></span>
                        <div class="their-story-share-value">
                            <code class="their-story-code" id="story-link-code"><?php echo esc_html($obfuscated_url ? $obfuscated_url : $story_url); ?></code>
                            <button type="button" class="their-story-btn their-story-btn-copy" data-copy-target="story-link-code">
                                <?php echo esc_html__('Copy', 'their-story'); ?>
                            </button>
                        </div>
                    </div>
                    <?php if ($password) : ?>
                    <div class="their-story-share-item">
                        <span class="their-story-share-label"><?php echo esc_html__('Password', 'their-story'); ?></span>
                        <div class="their-story-share-value">
                            <code class="their-story-code" id="story-password-code"><?php echo esc_html($password); ?></code>
                            <button type="button" class="their-story-btn their-story-btn-copy" data-copy-target="story-password-code">
                                <?php echo esc_html__('Copy', 'their-story'); ?>
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>
