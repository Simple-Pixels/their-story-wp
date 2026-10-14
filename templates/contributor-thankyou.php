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
                echo esc_html(sprintf(
                    /* translators: %s: honoree name */
                    __('Thank you for your contribution of stories and memories relating to %s.', 'their-story'),
                    $subject
                ));
            } else {
                esc_html_e('Thank you for your contribution.', 'their-story');
            }
            ?>
        </h1>

        <p class="their-story-contributor-thankyou__body">
            <?php esc_html_e('Your submission has successfully been made and your content will now be reviewed prior to inclusion in the final storybook.', 'their-story'); ?>
        </p>

        <?php if (!empty($story_share_url)) : ?>
        <div class="their-story-contributor-thankyou__section">
            <p class="their-story-contributor-thankyou__section-label">
                <?php esc_html_e('Know someone else who would like to contribute? Share this story with them!', 'their-story'); ?>
            </p>
            <div class="their-story-contributor-thankyou__actions">
                <button type="button" class="their-story-btn their-story-btn-outline" id="their-story-ct-open-share">
                    <?php esc_html_e('Share this story', 'their-story'); ?>
                </button>
            </div>
        </div>
        <hr class="their-story-contributor-thankyou__divider">
        <?php endif; ?>

        <div class="their-story-contributor-thankyou__section">
            <p class="their-story-contributor-thankyou__section-label">
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
</div>

<?php if (!empty($story_share_url)) : ?>
<div
    id="their-story-ct-share-modal"
    class="their-story-modal"
    hidden
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
    aria-labelledby="their-story-ct-share-title"
>
    <div class="their-story-modal-backdrop" tabindex="-1" data-close-ct-share></div>
    <div class="their-story-modal-panel" role="document">
        <div class="their-story-modal-header">
            <h2 id="their-story-ct-share-title" class="their-story-modal-title"><?php esc_html_e('Share this story', 'their-story'); ?></h2>
            <button type="button" class="their-story-modal-close" data-close-ct-share aria-label="<?php esc_attr_e('Close', 'their-story'); ?>">&times;</button>
        </div>
        <div class="their-story-modal-body">
            <p class="their-story-modal-lede"><?php esc_html_e('Copy the link to invite others. If the page asks for a password, copy that too.', 'their-story'); ?></p>
            <div class="their-story-share-info">
                <div class="their-story-share-item">
                    <span class="their-story-share-label"><?php esc_html_e('Story link', 'their-story'); ?></span>
                    <div class="their-story-share-value">
                        <code class="their-story-code" id="ct-story-link-code"><?php echo esc_html($story_share_url); ?></code>
                        <button type="button" class="their-story-btn their-story-btn-copy" data-copy-target="ct-story-link-code"><?php esc_html_e('Copy', 'their-story'); ?></button>
                    </div>
                </div>
                <?php if (!empty($story_password)) : ?>
                <div class="their-story-share-item">
                    <span class="their-story-share-label"><?php esc_html_e('Password', 'their-story'); ?></span>
                    <div class="their-story-share-value">
                        <code class="their-story-code" id="ct-story-pw-code"><?php echo esc_html($story_password); ?></code>
                        <button type="button" class="their-story-btn their-story-btn-copy" data-copy-target="ct-story-pw-code"><?php esc_html_e('Copy', 'their-story'); ?></button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var modal = document.getElementById('their-story-ct-share-modal');
    var openBtn = document.getElementById('their-story-ct-open-share');
    var lastFocus = null;
    function openModal() {
        lastFocus = document.activeElement;
        modal.removeAttribute('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        var close = modal.querySelector('.their-story-modal-close');
        if (close) close.focus();
    }
    function closeModal() {
        if (modal.hasAttribute('hidden')) return;
        modal.setAttribute('hidden', '');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (lastFocus && lastFocus.focus) lastFocus.focus();
    }
    if (openBtn) openBtn.addEventListener('click', openModal);
    modal.querySelectorAll('[data-close-ct-share]').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hasAttribute('hidden')) closeModal();
    });
    modal.querySelectorAll('[data-copy-target]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.getElementById(btn.getAttribute('data-copy-target'));
            if (!target) return;
            navigator.clipboard.writeText(target.textContent).then(function () {
                var orig = btn.textContent;
                btn.textContent = 'Copied!';
                setTimeout(function () { btn.textContent = orig; }, 2000);
            });
        });
    });
}());
</script>
<?php endif; ?>

<?php
get_footer();
