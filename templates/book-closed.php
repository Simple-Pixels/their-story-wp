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
get_header();
?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    body.their-story-book-closed-page,
    .their-story-book-closed-page {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        background-color: #faf4f0;
        color: #000000;
    }
    .their-story-book-closed-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
    }
    .their-story-book-closed-header {
        text-align: center;
        margin-bottom: 50px;
    }
    .their-story-book-closed-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: #000000;
        margin: 0 0 1rem 0;
    }
    .their-story-book-closed-subtitle {
        font-size: 1.25rem;
        color: rgba(0, 0, 0, 0.55);
        margin: 0;
    }
    .their-story-book-products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 30px;
        margin-top: 40px;
    }
    .their-story-book-product-card {
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid rgba(230, 179, 161, 0.35);
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        transition: transform 0.2s, box-shadow 0.2s;
        text-decoration: none;
        color: inherit;
        display: block;
    }
    .their-story-book-product-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        text-decoration: none;
        color: inherit;
    }
    .their-story-book-product-image {
        width: 100%;
        height: 300px;
        object-fit: cover;
        background: #faf4f0;
    }
    .their-story-book-product-content {
        padding: 20px;
    }
    .their-story-book-product-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #000000;
        margin: 0 0 10px 0;
    }
    .their-story-book-product-price {
        font-size: 1.125rem;
        font-weight: 600;
        color: #000000;
        margin: 0;
    }
    .their-story-book-product-price .woocommerce-Price-amount {
        color: #e6b3a1;
    }
    .their-story-story-info {
        background: #ffffff;
        border: 1px solid rgba(230, 179, 161, 0.35);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 30px;
        text-align: center;
    }
    .their-story-story-info-text {
        margin: 0;
        color: #000000;
        font-size: 1rem;
    }
    .their-story-story-info strong {
        color: #e6b3a1;
    }
    .their-story-book-empty {
        text-align: center;
        padding: 60px 20px;
        background: #fff;
        border-radius: 12px;
        border: 1px solid rgba(230, 179, 161, 0.35);
    }
    .their-story-book-empty p {
        font-size: 1.125rem;
        color: rgba(0, 0, 0, 0.55);
        margin: 0;
    }
</style>
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

