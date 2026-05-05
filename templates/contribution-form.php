<?php
/**
 * Multi-step contributor form (story page) — wizard with Next / Back.
 *
 * @var int    $story_id
 * @var string $contribution_subject Honoree name (for config JSON).
 * @var string $what_is_this_url
 */

if (!defined('ABSPATH')) {
    exit;
}

$faq_url = apply_filters('their_story_faq_url', $what_is_this_url);
$more_info_url = apply_filters('their_story_more_information_url', $faq_url);
/* translators: 1: current step number, 2: total steps */
$cf_step_progress_tpl = __('Step %1$d of %2$d', 'their-story');

?>
<section class="their-story-form-section their-story-flow-section their-story-contribution-section" aria-labelledby="their-story-form-heading">
    <h2 id="their-story-form-heading" class="their-story-section-title"><?php echo esc_html__('Share your stories', 'their-story'); ?></h2>

    <form id="their-story-contribution-form" class="their-story-contribution-form their-story-submission-form" novalidate>
        <p id="their-story-cf-step-progress" class="their-story-cf-step-progress their-story-cf-is-hidden" aria-live="polite"></p>

        <div class="their-story-cf-wizard">
            <div class="their-story-cf-steps">

                <div class="their-story-cf-step their-story-cf-step-active" data-cf-screen="q1">
                    <div class="their-story-cf-block their-story-cf-q1">
                        <p class="their-story-cf-question"><?php
                            printf(
                                /* translators: %s: honoree name */
                                esc_html__('Are you wanting to share a story about %s?', 'their-story'),
                                esc_html($contribution_subject)
                            );
                            ?></p>
                        <div class="their-story-cf-choice-row">
                            <button type="button" class="their-story-btn their-story-btn-outline their-story-cf-choice" data-cf-join="yes"><?php echo esc_html__('Yes', 'their-story'); ?></button>
                            <button type="button" class="their-story-btn their-story-btn-outline their-story-cf-choice" data-cf-join="no"><?php echo esc_html__('No', 'their-story'); ?></button>
                            <button type="button" class="their-story-btn their-story-btn-outline their-story-cf-choice" data-cf-join="uncertain"><?php echo esc_html__('Uncertain', 'their-story'); ?></button>
                        </div>
                    </div>
                </div>

                <div class="their-story-cf-step their-story-cf-is-hidden" data-cf-screen="no_confirm">
                    <div class="their-story-cf-panel their-story-cf-panel-bordered">
                        <p class="their-story-cf-question"><?php echo esc_html__('Are you sure?', 'their-story'); ?></p>
                        <div class="their-story-cf-choice-row">
                            <button type="button" class="their-story-btn their-story-btn-outline" id="their-story-cf-no-final"><?php echo esc_html__('Yes, I am sure', 'their-story'); ?></button>
                            <button type="button" class="their-story-btn their-story-btn-primary" id="their-story-cf-no-more-info"><?php echo esc_html__('I would like more information', 'their-story'); ?></button>
                        </div>
                    </div>
                </div>

                <div class="their-story-cf-step their-story-cf-is-hidden" data-cf-screen="no_thanks">
                    <div class="their-story-cf-panel their-story-cf-panel-bordered">
                        <p><?php
                            printf(
                                esc_html__('Thank you for your consideration. Should you change your mind and would like to contribute to %s’s stories, please open this link again when you are ready.', 'their-story'),
                                esc_html($contribution_subject)
                            );
                            ?></p>
                    </div>
                </div>

                <div class="their-story-cf-step their-story-cf-is-hidden" data-cf-screen="uncertain">
                    <div class="their-story-cf-panel their-story-cf-panel-bordered">
                        <p class="their-story-cf-question"><?php echo esc_html__('Would you like to review our FAQs?', 'their-story'); ?></p>
                        <p><a class="their-story-btn their-story-btn-primary" href="<?php echo esc_url($faq_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Open FAQs', 'their-story'); ?></a></p>
                    </div>
                </div>

                <div class="their-story-cf-step their-story-cf-is-hidden" data-cf-screen="details">
                    <div class="their-story-cf-block">
                        <label for="cf-first-name" class="their-story-label"><?php echo esc_html__('What is your first name?', 'their-story'); ?> <span class="required">*</span></label>
                        <input type="text" id="cf-first-name" name="cf_first_name" class="their-story-input" autocomplete="given-name" maxlength="120" />
                    </div>
                    <div class="their-story-cf-block">
                        <label for="cf-surname" class="their-story-label"><?php echo esc_html__('What is your surname?', 'their-story'); ?> <span class="required">*</span></label>
                        <input type="text" id="cf-surname" name="cf_surname" class="their-story-input" autocomplete="family-name" maxlength="120" />
                    </div>
                    <div class="their-story-cf-block">
                        <label for="cf-email" class="their-story-label"><?php echo esc_html__('What is your email address?', 'their-story'); ?> <span class="required">*</span></label>
                        <p class="their-story-help-text"><?php echo esc_html__('This is in case we need to contact you when reviewing stories, not for marketing.', 'their-story'); ?></p>
                        <input type="email" id="cf-email" name="cf_email" class="their-story-input" autocomplete="email" maxlength="190" />
                    </div>
                    <div class="their-story-cf-block">
                        <label for="cf-live" class="their-story-label"><?php echo esc_html__('Where do you live?', 'their-story'); ?> <span class="required">*</span></label>
                        <input type="text" id="cf-live" name="cf_live" class="their-story-input" autocomplete="address-level1" maxlength="200" />
                    </div>
                    <div class="their-story-cf-block">
                        <label for="cf-known" class="their-story-label"><?php
                            printf(
                                /* translators: %s: honoree name */
                                esc_html__('How long have you known %s?', 'their-story'),
                                esc_html($contribution_subject)
                            );
                            ?> <span class="required">*</span></label>
                        <input type="text" id="cf-known" name="cf_known" class="their-story-input" maxlength="300" />
                    </div>
                </div>

                <div class="their-story-cf-step their-story-cf-is-hidden" data-cf-screen="how_met">
                    <div class="their-story-cf-block">
                        <label for="cf-how-met" class="their-story-label"><?php
                            printf(
                                esc_html__('What is the story of how you and %s met?', 'their-story'),
                                esc_html($contribution_subject)
                            );
                            ?> <span class="required">*</span></label>
                        <textarea id="cf-how-met" name="cf_how_met" class="their-story-textarea" rows="4" maxlength="8000"></textarea>
                    </div>
                </div>

                <div class="their-story-cf-step their-story-cf-is-hidden" data-cf-screen="one_word">
                    <div class="their-story-cf-block">
                        <label for="cf-one-word" class="their-story-label"><?php
                            printf(
                                esc_html__('How would you describe %s in one word?', 'their-story'),
                                esc_html($contribution_subject)
                            );
                            ?> <span class="required">*</span></label>
                        <input type="text" id="cf-one-word" name="cf_one_word" class="their-story-input" maxlength="80" />
                    </div>
                </div>

                <div class="their-story-cf-step their-story-cf-is-hidden" data-cf-screen="particular">
                    <div class="their-story-cf-block">
                        <label for="cf-particular" class="their-story-label"><?php
                            printf(
                                esc_html__('Is there a particular story that you think of when you think of %s?', 'their-story'),
                                esc_html($contribution_subject)
                            );
                            ?> <span class="required">*</span></label>
                        <p class="their-story-help-text"><?php
                        echo wp_kses(
                            sprintf(
                                __('We are after stories of actual events you shared with your loved one, not only a tribute. Need more info? <a href="%s">See our FAQs</a>.', 'their-story'),
                                esc_url($faq_url)
                            ),
                            array(
                                'a' => array(
                                    'href' => true,
                                    'target' => true,
                                    'rel' => true,
                                ),
                            )
                        );
                        ?></p>
                        <textarea id="cf-particular" name="cf_particular" class="their-story-textarea" rows="4" maxlength="8000"></textarea>
                    </div>
                </div>

                <div class="their-story-cf-step their-story-cf-is-hidden" data-cf-screen="funny">
                    <div class="their-story-cf-block">
                        <p class="their-story-cf-question"><?php
                            printf(
                                esc_html__('Is there a funny story you have about %s?', 'their-story'),
                                esc_html($contribution_subject)
                            );
                            ?></p>
                        <div class="their-story-cf-choice-row their-story-cf-choice-row--with-inline-back">
                            <button type="button" class="their-story-btn their-story-btn-outline their-story-cf-funny-yes" data-cf-funny="yes"><?php echo esc_html__('Yes', 'their-story'); ?></button>
                            <button type="button" class="their-story-btn their-story-btn-outline their-story-cf-funny-no" data-cf-funny="no"><?php echo esc_html__('No', 'their-story'); ?></button>
                            <button type="button" class="their-story-btn their-story-btn-outline their-story-cf-inline-back"><?php echo esc_html__('Back', 'their-story'); ?></button>
                        </div>
                    </div>
                </div>

                <div class="their-story-cf-step their-story-cf-is-hidden" data-cf-screen="funny_text">
                    <div class="their-story-cf-block">
                        <label for="cf-funny-text" class="their-story-label"><?php echo esc_html__('Tell us the funny story', 'their-story'); ?></label>
                        <textarea id="cf-funny-text" name="cf_funny_text" class="their-story-textarea" rows="4" maxlength="8000"></textarea>
                    </div>
                </div>

                <div class="their-story-cf-step their-story-cf-is-hidden" data-cf-screen="extra1">
                    <div class="their-story-cf-block">
                        <p class="their-story-cf-question"><?php echo esc_html__('Any other stories you would like to share?', 'their-story'); ?></p>
                        <div class="their-story-cf-choice-row their-story-cf-choice-row--with-inline-back">
                            <button type="button" class="their-story-btn their-story-btn-outline" data-cf-extra1="yes"><?php echo esc_html__('Yes', 'their-story'); ?></button>
                            <button type="button" class="their-story-btn their-story-btn-outline" data-cf-extra1="no"><?php echo esc_html__('No', 'their-story'); ?></button>
                            <button type="button" class="their-story-btn their-story-btn-outline their-story-cf-inline-back"><?php echo esc_html__('Back', 'their-story'); ?></button>
                        </div>
                    </div>
                </div>

                <div class="their-story-cf-step their-story-cf-is-hidden" data-cf-screen="extra1_text">
                    <div class="their-story-cf-block">
                        <label for="cf-extra1" class="their-story-label"><?php echo esc_html__('Your other story', 'their-story'); ?></label>
                        <textarea id="cf-extra1" name="cf_extra1" class="their-story-textarea" rows="4" maxlength="8000"></textarea>
                    </div>
                </div>

                <div class="their-story-cf-step their-story-cf-is-hidden" data-cf-screen="extra2">
                    <div class="their-story-cf-block">
                        <p class="their-story-cf-question"><?php echo esc_html__('Any other stories you would like to share?', 'their-story'); ?></p>
                        <div class="their-story-cf-choice-row their-story-cf-choice-row--with-inline-back">
                            <button type="button" class="their-story-btn their-story-btn-outline" data-cf-extra2="yes"><?php echo esc_html__('Yes', 'their-story'); ?></button>
                            <button type="button" class="their-story-btn their-story-btn-outline" data-cf-extra2="no"><?php echo esc_html__('No', 'their-story'); ?></button>
                            <button type="button" class="their-story-btn their-story-btn-outline their-story-cf-inline-back"><?php echo esc_html__('Back', 'their-story'); ?></button>
                        </div>
                    </div>
                </div>

                <div class="their-story-cf-step their-story-cf-is-hidden" data-cf-screen="extra2_text">
                    <div class="their-story-cf-block">
                        <label for="cf-extra2" class="their-story-label"><?php echo esc_html__('Your other story', 'their-story'); ?></label>
                        <textarea id="cf-extra2" name="cf_extra2" class="their-story-textarea" rows="4" maxlength="8000"></textarea>
                    </div>
                </div>

                <div class="their-story-cf-step their-story-cf-is-hidden" data-cf-screen="thoughts">
                    <div class="their-story-cf-block">
                        <p class="their-story-cf-question"><?php
                            printf(
                                esc_html__('Would you like to share any other thoughts about %s?', 'their-story'),
                                esc_html($contribution_subject)
                            );
                            ?></p>
                        <p class="their-story-help-text"><?php echo esc_html__('This one can be a tribute — your thoughts, feelings, or what you think of them.', 'their-story'); ?></p>
                        <div class="their-story-cf-choice-row their-story-cf-choice-row--with-inline-back">
                            <button type="button" class="their-story-btn their-story-btn-outline" data-cf-thoughts="yes"><?php echo esc_html__('Yes', 'their-story'); ?></button>
                            <button type="button" class="their-story-btn their-story-btn-outline" data-cf-thoughts="no"><?php echo esc_html__('No', 'their-story'); ?></button>
                            <button type="button" class="their-story-btn their-story-btn-outline their-story-cf-inline-back"><?php echo esc_html__('Back', 'their-story'); ?></button>
                        </div>
                    </div>
                </div>

                <div class="their-story-cf-step their-story-cf-is-hidden" data-cf-screen="thoughts_text">
                    <div class="their-story-cf-block">
                        <label for="cf-thoughts" class="their-story-label"><?php echo esc_html__('Your thoughts', 'their-story'); ?></label>
                        <textarea id="cf-thoughts" name="cf_thoughts" class="their-story-textarea" rows="4" maxlength="8000"></textarea>
                    </div>
                </div>

                <div class="their-story-cf-step their-story-cf-is-hidden" data-cf-screen="images">
                    <div class="their-story-cf-block">
                        <label for="cf-submission-images" class="their-story-label"><?php echo esc_html__('Images (optional, up to 5)', 'their-story'); ?></label>
                        <input type="file" id="cf-submission-images" name="images[]" accept="image/*" multiple class="their-story-file-input" />
                        <p class="their-story-help-text"><?php echo esc_html__('You can upload up to five images.', 'their-story'); ?></p>
                        <div id="cf-images-preview" class="their-story-images-preview"></div>
                    </div>
                </div>

            </div>

            <div id="their-story-cf-wizard-nav" class="their-story-cf-wizard-nav">
                <button type="button" class="their-story-btn their-story-btn-outline" id="their-story-cf-wizard-back"><?php echo esc_html__('Back', 'their-story'); ?></button>
                <button type="button" class="their-story-btn their-story-btn-primary" id="their-story-cf-wizard-next"><?php echo esc_html__('Next', 'their-story'); ?></button>
            </div>
        </div>

        <div id="submission-status-message" class="their-story-message" style="display: none;" role="status"></div>
    </form>
</section>

<div id="their-story-submit-confirm-modal" class="their-story-modal" hidden aria-hidden="true">
    <div class="their-story-modal-backdrop" tabindex="-1" data-their-story-close-submit-confirm></div>
    <div class="their-story-modal-panel" role="dialog" aria-labelledby="their-story-submit-confirm-title">
        <div class="their-story-modal-header">
            <h2 id="their-story-submit-confirm-title" class="their-story-modal-title"><?php echo esc_html__('Ready to submit?', 'their-story'); ?></h2>
            <button type="button" class="their-story-modal-close" data-their-story-close-submit-confirm aria-label="<?php echo esc_attr__('Close', 'their-story'); ?>">&times;</button>
        </div>
        <div class="their-story-modal-body">
            <p class="their-story-modal-lede"><?php echo esc_html__('Would you like to change any of your content above? If so, please change it now. If you are ready to submit, click Submit below.', 'their-story'); ?></p>
            <div class="their-story-modal-actions">
                <button type="button" class="their-story-btn their-story-btn-outline" data-their-story-close-submit-confirm><?php echo esc_html__('Go back', 'their-story'); ?></button>
                <button type="button" class="their-story-btn their-story-btn-primary" id="their-story-cf-final-submit"><?php echo esc_html__('Submit', 'their-story'); ?></button>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="their-story-cf-config"><?php
echo wp_json_encode(
    array(
        'faqUrl' => esc_url_raw($faq_url),
        'moreInfoUrl' => esc_url_raw($more_info_url),
        'subjectName' => $contribution_subject,
        'i18n' => array(
            'next' => __('Next', 'their-story'),
            'submit' => __('Submit', 'their-story'),
            'stepProgress' => $cf_step_progress_tpl,
        ),
    )
);
?></script>
