<?php
/**
 * Book / product selection markup (embedded via shortcode on the Next steps page, default slug next-steps).
 *
 * @var int    $story_id
 * @var int    $message_count
 * @var string $story_title
 * @var array  $book_products
 */

if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="their-story-book-closed-page">
    <div class="their-story-book-closed-container">
        <div class="their-story-book-closed-header">
            <h1 class="their-story-book-closed-title">
                <span class="their-story-book-closed-title-sans"><?php esc_html_e('Ready to', 'their-story'); ?></span>
                <span class="their-story-book-closed-title-display"><?php esc_html_e('choose a book?', 'their-story'); ?></span>
            </h1>
            <?php if ($story_title) : ?>
                <p class="their-story-book-closed-subtitle">
                    <?php echo esc_html__('Select a book format for your story:', 'their-story'); ?> <strong><?php echo esc_html($story_title); ?></strong>
                </p>
            <?php endif; ?>
        </div>

        <?php if ($story_id && $message_count) : ?>
        <div class="their-story-story-info">
            <p class="their-story-story-info-text">
                <?php
                printf(
                    esc_html__('Your story contains %d message(s) and will be included in your book.', 'their-story'),
                    $message_count
                );
                ?>
            </p>
        </div>
        <?php endif; ?>

        <?php if (!empty($book_products)) : ?>
            <div class="their-story-book-products-grid">
                <?php
                foreach ($book_products as $product) :
                    $product_id = $product->get_id();
                    $product_url = $their_story->book_product_url($product_id, $story_id, $message_count);
                    $product_title = $product->get_name();
                    $product_price = $product->get_price_html();
                    $product_image_id = $product->get_image_id();
                    $product_image_url = $product_image_id ? wp_get_attachment_image_url($product_image_id, 'large') : wc_placeholder_img_src('large');
                    ?>
                    <a href="<?php echo esc_url($product_url); ?>" class="their-story-book-product-card">
                        <?php if ($product_image_url) : ?>
                            <img src="<?php echo esc_url($product_image_url); ?>" alt="<?php echo esc_attr($product_title); ?>" class="their-story-book-product-image" />
                        <?php endif; ?>
                        <div class="their-story-book-product-content">
                            <h3 class="their-story-book-product-title"><?php echo esc_html($product_title); ?></h3>
                            <p class="their-story-book-product-price"><?php echo $product_price; ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="their-story-book-empty">
                <p>
                    <?php echo esc_html__('No book products available at this time.', 'their-story'); ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>
