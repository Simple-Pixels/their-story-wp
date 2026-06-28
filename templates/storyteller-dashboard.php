<?php

if (!defined('ABSPATH')) {
    exit;
}

$checkout_error = isset($_GET['their_story_error']) ? sanitize_key($_GET['their_story_error']) : '';
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

    <?php if ($checkout_error === 'cart') : ?>
        <div class="their-story-notice their-story-notice-error">
            <?php esc_html_e('There was a problem adding the product to your cart. Please try again.', 'their-story'); ?>
        </div>
    <?php elseif ($checkout_error === 'expired') : ?>
        <div class="their-story-notice their-story-notice-error">
            <?php esc_html_e('Your session expired. Please start the process again.', 'their-story'); ?>
        </div>
    <?php endif; ?>

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
                            $unique_link  = get_post_meta($story->ID, '_story_unique_link', true);
                            $story_url    = get_permalink($story->ID);
                            $their_story  = new Their_Story();
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

<!-- Create Story Wizard Modal -->
<div id="ts-wizard-modal" class="ts-wizard-modal" aria-modal="true" role="dialog" aria-labelledby="ts-wizard-title" hidden>
    <div class="ts-wizard-backdrop"></div>
    <div class="ts-wizard-dialog">

        <button type="button" class="ts-wizard-close" aria-label="<?php esc_attr_e('Close', 'their-story'); ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

        <!-- Step indicator -->
        <div class="ts-wizard-steps" aria-hidden="true">
            <div class="ts-step ts-step--active" data-step="1">
                <div class="ts-step-circle">1</div>
                <span class="ts-step-label"><?php esc_html_e('Terms', 'their-story'); ?></span>
            </div>
            <div class="ts-step-connector"></div>
            <div class="ts-step" data-step="2">
                <div class="ts-step-circle">2</div>
                <span class="ts-step-label"><?php esc_html_e('Your Story', 'their-story'); ?></span>
            </div>
            <div class="ts-step-connector"></div>
            <div class="ts-step" data-step="3">
                <div class="ts-step-circle">3</div>
                <span class="ts-step-label"><?php esc_html_e('Choose &amp; Pay', 'their-story'); ?></span>
            </div>
        </div>

        <!-- Step 1: Terms & Conditions -->
        <div class="ts-wizard-step" id="ts-step-1">
            <h2 class="ts-wizard-title" id="ts-wizard-title"><?php esc_html_e('Terms &amp; Conditions', 'their-story'); ?></h2>
            <p class="ts-wizard-lede"><?php esc_html_e('Please read and accept our terms before creating your story.', 'their-story'); ?></p>

            <div class="ts-terms-scroll">
                <p><?php esc_html_e('By creating a story with Their Story you agree to the following:', 'their-story'); ?></p>
                <p><?php esc_html_e('Payment is required before your story page is created.', 'their-story'); ?></p>
                <p><?php esc_html_e('Your story page will be live once payment is confirmed.', 'their-story'); ?></p>
                <p><?php esc_html_e('All submitted contributions are subject to moderation before appearing on your story page.', 'their-story'); ?></p>
                <p><?php esc_html_e('When you close your story, the Their Story team will begin preparing your book for print.', 'their-story'); ?></p>
                <p><?php esc_html_e('All sales are final. Please contact us if you have any questions before purchasing.', 'their-story'); ?></p>
            </div>

            <label class="ts-checkbox-label">
                <input type="checkbox" id="ts-terms-accept" />
                <span><?php esc_html_e('I have read and agree to the Terms &amp; Conditions.', 'their-story'); ?></span>
            </label>

            <div class="ts-wizard-footer">
                <button type="button" class="their-story-btn their-story-btn-primary ts-wizard-next" id="ts-next-1" disabled>
                    <?php esc_html_e('Next: Story Details', 'their-story'); ?>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" style="margin-left:6px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Step 2: Story Details -->
        <div class="ts-wizard-step" id="ts-step-2" hidden>
            <h2 class="ts-wizard-title"><?php esc_html_e('Story Details', 'their-story'); ?></h2>
            <p class="ts-wizard-lede"><?php esc_html_e('Tell us about the story you\'re creating.', 'their-story'); ?></p>

            <div class="their-story-form-fields">
                <div class="their-story-form-field">
                    <label for="ts-story-title" class="their-story-label">
                        <?php esc_html_e('Story Title', 'their-story'); ?> <span class="ts-required" aria-hidden="true">*</span>
                    </label>
                    <input type="text" id="ts-story-title" class="their-story-input" maxlength="200"
                           placeholder="<?php esc_attr_e('e.g. Celebrating Lucy\'s 50th', 'their-story'); ?>" required />
                </div>

                <div class="their-story-form-field">
                    <label for="ts-subject-name" class="their-story-label">
                        <?php esc_html_e('First name of the person stories are about', 'their-story'); ?>
                    </label>
                    <input type="text" id="ts-subject-name" class="their-story-input" maxlength="120"
                           placeholder="<?php esc_attr_e('e.g. Lucy', 'their-story'); ?>" />
                    <p class="their-story-help-text"><?php esc_html_e('Shown on the contribution form. Defaults to the story title if left blank.', 'their-story'); ?></p>
                </div>

                <div class="their-story-form-field">
                    <label for="ts-relation-label" class="their-story-label">
                        <?php esc_html_e('Relationship phrase', 'their-story'); ?>
                    </label>
                    <input type="text" id="ts-relation-label" class="their-story-input" maxlength="120"
                           placeholder="<?php esc_attr_e('e.g. family member or friend', 'their-story'); ?>" />
                </div>

                <div class="their-story-form-field">
                    <label for="ts-story-password" class="their-story-label">
                        <?php esc_html_e('Password (optional)', 'their-story'); ?>
                    </label>
                    <input type="password" id="ts-story-password" class="their-story-input"
                           placeholder="<?php esc_attr_e('Leave blank for no password', 'their-story'); ?>" />
                    <p class="their-story-help-text"><?php esc_html_e('Restrict who can view and contribute to your story page.', 'their-story'); ?></p>
                </div>
            </div>

            <div class="ts-wizard-footer ts-wizard-footer--split">
                <button type="button" class="their-story-btn their-story-btn-secondary ts-wizard-back" id="ts-back-2">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" style="margin-right:6px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    <?php esc_html_e('Back', 'their-story'); ?>
                </button>
                <button type="button" class="their-story-btn their-story-btn-primary ts-wizard-next" id="ts-next-2">
                    <?php esc_html_e('Next: Choose &amp; Pay', 'their-story'); ?>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" style="margin-left:6px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Step 3: Product Selection -->
        <div class="ts-wizard-step" id="ts-step-3" hidden>
            <h2 class="ts-wizard-title"><?php esc_html_e('Choose Your Book', 'their-story'); ?></h2>
            <p class="ts-wizard-lede"><?php esc_html_e('Select the book style and size that suits your story.', 'their-story'); ?></p>

            <div id="ts-products-container">
                <div class="ts-products-loading">
                    <div class="ts-spinner"></div>
                    <span><?php esc_html_e('Loading options&hellip;', 'their-story'); ?></span>
                </div>
            </div>

            <div class="ts-wizard-footer ts-wizard-footer--split">
                <button type="button" class="their-story-btn their-story-btn-secondary ts-wizard-back" id="ts-back-3">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" style="margin-right:6px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    <?php esc_html_e('Back', 'their-story'); ?>
                </button>
                <button type="button" class="their-story-btn their-story-btn-primary" id="ts-purchase-btn" disabled>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" style="margin-right:6px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <?php esc_html_e('Proceed to Checkout', 'their-story'); ?>
                </button>
            </div>
        </div>

    </div><!-- /.ts-wizard-dialog -->
</div><!-- /#ts-wizard-modal -->
