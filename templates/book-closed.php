<?php
if (!defined('ABSPATH')) {
    exit;
}

$story_id = isset($_GET['story']) ? intval($_GET['story']) : 0;
$message_count = isset($_GET['messages']) ? intval($_GET['messages']) : 0;

$story = $story_id ? get_post($story_id) : null;
$story_title = $story ? get_the_title($story_id) : '';
$story_title = str_replace('Protected: ', '', $story_title);

$book_products = array();
if (class_exists('WooCommerce')) {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'tax_query' => array(
            array(
                'taxonomy' => 'product_type',
                'field' => 'slug',
                'terms' => 'variable'
            )
        )
    );
    
    $products = wc_get_products($args);
    
    foreach ($products as $wc_product) {
        if ($wc_product && $wc_product->is_type('variable')) {
            $book_products[] = $wc_product;
        }
    }
}

function their_story_product_url($product_id, $story_id) {
    $url = get_permalink($product_id);
    if ($story_id) {
        $url = add_query_arg('story', $story_id, $url);
        $message_count = isset($_GET['messages']) ? intval($_GET['messages']) : 0;
        if ($message_count) {
            $url = add_query_arg('messages', $message_count, $url);
        }
    }
    return $url;
}

add_filter(
    'body_class',
    static function ($classes) {
        $classes[] = 'their-story-book-closed-page';
        return $classes;
    }
);
?>
<?php
$book_closed_css = THEIR_STORY_PLUGIN_DIR . 'assets/css/book-closed.css';
wp_enqueue_style(
    'their-story-book-closed',
    THEIR_STORY_PLUGIN_URL . 'assets/css/book-closed.css',
    array(),
    file_exists($book_closed_css) ? filemtime($book_closed_css) : THEIR_STORY_VERSION
);
get_header();
?>
<div class="their-story-book-closed-page">
    <div class="their-story-book-closed-container">
        <div class="their-story-book-closed-header">
            <h1 class="their-story-book-closed-title"><?php echo esc_html__('Ready to choose a book?', 'their-story'); ?></h1>
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
                <?php foreach ($book_products as $product) : 
                    $product_id = $product->get_id();
                    $product_url = their_story_product_url($product_id, $story_id);
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

<?php
get_footer();

