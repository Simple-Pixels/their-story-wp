<?php
if (!defined('ABSPATH')) {
    exit;
}
// Variables available: $subject, $about_url, $register_url

get_header();
?>
<div class="their-story-page-content">
<div class="their-story-contributor-thankyou">
    <div class="their-story-contributor-thankyou__inner">
        <div class="their-story-contributor-thankyou__icon" aria-hidden="true">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="48" height="48">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>

        <h1 class="their-story-contributor-thankyou__title">
            <?php
            if ($subject) {
                echo wp_kses(
                    sprintf(
                        /* translators: %s: honoree name */
                        __('Thank you for your contribution of stories and memories relating to <strong>%s</strong>.', 'their-story'),
                        esc_html($subject)
                    ),
                    array('strong' => array())
                );
            } else {
                esc_html_e('Thank you for your contribution.', 'their-story');
            }
            ?>
        </h1>

        <p class="their-story-contributor-thankyou__body">
            <?php esc_html_e('Your submission has successfully been made and your content will now be reviewed prior to inclusion in the final storybook.', 'their-story'); ?>
        </p>

        <p class="their-story-contributor-thankyou__cta-label">
            <?php esc_html_e('Would you be interested in creating your own storybook for someone you know or love?', 'their-story'); ?>
        </p>

        <div class="their-story-contributor-thankyou__actions">
            <a href="<?php echo esc_url($about_url); ?>" class="their-story-btn their-story-btn-outline">
                <?php esc_html_e('Learn More', 'their-story'); ?>
            </a>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="their-story-btn their-story-btn-outline">
                <?php esc_html_e('Return to Home', 'their-story'); ?>
            </a>
            <a href="<?php echo esc_url($register_url); ?>" class="their-story-btn their-story-btn-primary">
                <?php esc_html_e('Create My Own Story', 'their-story'); ?>
            </a>
        </div>
    </div>
</div>
</div>
<?php
get_footer();
