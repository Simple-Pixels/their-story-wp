<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap their-story-admin-dashboard">
    <h1 class="their-story-title"><?php echo esc_html__('Story pages', 'their-story'); ?></h1>
    <p class="their-story-subtitle their-story-story-pages-intro">
        <?php echo esc_html__('These pages are hidden from the main Pages list. Edit the WordPress page here when you need raw HTML, template, or SEO settings.', 'their-story'); ?>
    </p>

    <?php if (empty($stories)) : ?>
        <p class="their-story-muted"><?php echo esc_html__('No story pages found.', 'their-story'); ?></p>
    <?php else : ?>
        <div class="their-story-table-wrapper their-story-story-pages-table-wrap">
            <table class="their-story-table">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Title', 'their-story'); ?></th>
                        <th><?php echo esc_html__('Storyteller', 'their-story'); ?></th>
                        <th><?php echo esc_html__('Status', 'their-story'); ?></th>
                        <th><?php echo esc_html__('Actions', 'their-story'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stories as $story) : ?>
                        <?php
                        $sid = get_post_meta($story->ID, '_storyteller_id', true);
                        $storyteller = $sid ? get_userdata((int) $sid) : null;
                        $their_story = new Their_Story();
                        $link = $their_story->get_story_url_from_link(get_post_meta($story->ID, '_story_unique_link', true));
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html(get_the_title($story)); ?></strong>
                            </td>
                            <td>
                                <?php if ($storyteller) : ?>
                                    <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $storyteller->ID)); ?>">
                                        <?php echo esc_html($storyteller->display_name); ?>
                                    </a>
                                <?php else : ?>
                                    <span class="their-story-muted"><?php echo esc_html__('—', 'their-story'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $post_status = $story->post_status;
                                $status_slug = sanitize_html_class($post_status, 'draft');
                                ?>
                                <span class="story-status status-<?php echo esc_attr($status_slug); ?>">
                                    <?php echo esc_html(ucfirst($post_status)); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(get_edit_post_link($story->ID, 'raw')); ?>"><?php echo esc_html__('Edit', 'their-story'); ?></a>
                                <?php if ($link) : ?>
                                    &nbsp;|&nbsp;
                                    <a href="<?php echo esc_url($link); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('View', 'their-story'); ?></a>
                                <?php else : ?>
                                    &nbsp;|&nbsp;
                                    <a href="<?php echo esc_url(get_permalink($story->ID)); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('View', 'their-story'); ?></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
