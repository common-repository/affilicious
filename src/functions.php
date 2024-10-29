<?php
use Affilicious\Attribute\Helper\Attribute_Helper;
use Affilicious\Attribute\Helper\Attribute_Template_Helper;
use Affilicious\Attribute\Model\Attribute_Template;
use Affilicious\Detail\Helper\Detail_Helper;
use Affilicious\Detail\Helper\Detail_Template_Helper;
use Affilicious\Detail\Model\Detail_Template;
use Affilicious\Product\Helper\Product_Helper;
use Affilicious\Product\Model\Complex_Product;
use Affilicious\Product\Model\Detail_Aware_Interface;
use Affilicious\Product\Model\Product;
use Affilicious\Product\Model\Product_Variant;
use Affilicious\Product\Model\Relation_Aware_Interface;
use Affilicious\Product\Model\Review_Aware_Interface;
use Affilicious\Product\Model\Shop_Aware_Interface;
use Affilicious\Product\Model\Tag_Aware_Interface;
use Affilicious\Product\Model\Type;
use Affilicious\Shop\Helper\Shop_Helper;
use Affilicious\Shop\Helper\Shop_Template_Helper;
use Affilicious\Shop\Model\Affiliate_Link;
use Affilicious\Shop\Model\Availability;
use Affilicious\Shop\Model\Shop_Template;

if (!defined('ABSPATH')) {
    exit('Not allowed to access pages directly.');
}

/**
 * Check if the current page is a product.
 *
 * @since 0.6
 * @return bool
 */
function aff_is_product_page()
{
    return is_singular(Product::POST_TYPE);
}

/**
 * Check if post, the post with the ID or the current post is a product.
 * If you pass in nothing as a parameter, the current post will be used.
 *
 * @since 0.6
 * @param int|\WP_Post|Product|null $product_or_id
 * @return bool
 */
function aff_is_product($product_or_id = null)
{
    $result = Product_Helper::is_product($product_or_id);

    return $result;
}

/**
 * Get the product by the Wordpress ID or post.
 * If you pass in nothing as a parameter, the current post will be used.
 *
 * @since 0.3
 * @param int|\WP_Post|Product|null $product_or_id
 * @return Product
 */
function aff_get_product($product_or_id = null)
{
    $product = Product_Helper::get_product($product_or_id);

    return $product;
}

/**
 * Get the product review rating from 0 to 5
 *
 * @since 0.6
 * @param int|\WP_Post|Product|null $product_or_id
 * @return null|float
 */
function aff_get_product_review_rating($product_or_id = null)
{
    $product = aff_get_product($product_or_id);
    if($product instanceof Product_Variant) {
        $product = $product->get_parent();
    }

    if($product === null || !($product instanceof Review_Aware_Interface) || !$product->has_review()) {
        return null;
    }

    $review = $product->get_review();
    $rating = $review->get_rating();
    $raw_rating = $rating->get_value();

    return $raw_rating;
}

/**
 * Get the product review votes
 *
 * @since 0.6
 * @param int|\WP_Post|Product|null $product_or_id
 * @return null|int
 */
function aff_get_product_review_votes($product_or_id = null)
{
    $product = aff_get_product($product_or_id);
    if($product instanceof Product_Variant) {
        $product = $product->get_parent();
    }

    if($product === null || !($product instanceof Review_Aware_Interface) || !$product->has_review()) {
        return null;
    }

    $review = $product->get_review();
    if(!$review->has_votes()) {
        return null;
    }

    $votes = $review->get_votes();
    $raw_votes = $votes->get_value();

    return $raw_votes;
}

/**
 * Get the plain product details of the detail groups.
 * If you pass in nothing as a parameter, the current post will be used.
 *
 * @since 0.3
 * @param int|\WP_Post|Product|null $product_or_id
 * @return null|array
 */
function aff_get_product_details($product_or_id = null)
{
    $product = aff_get_product($product_or_id);
    if($product instanceof Product_Variant) {
        $product = $product->get_parent();
    }

    if($product === null || !($product instanceof Detail_Aware_Interface)) {
        return null;
    }

    $details = $product->get_details();
    $raw_details = array();
    foreach ($details as $detail) {
        $raw_detail = Detail_Helper::to_array($detail);
        $raw_details[] = $raw_detail;
    }

    return $raw_details;
}

/**
 * Get the product image gallery by the product.
 * If you pass in nothing as a parameter, the current post will be used.
 *
 * @since 0.6
 * @param int|\WP_Post|Product|null $product_or_id
 * @return null|array
 */
function aff_get_product_image_gallery($product_or_id = null)
{
    $product = aff_get_product($product_or_id);
    if($product === null) {
        return null;
    }

    $image_ids = $product->get_image_gallery();

    $raw_image_ids = array();
    foreach ($image_ids as $image_id) {
        $raw_image_ids[] = $image_id->get_value();
    }

    return $raw_image_ids;
}

/**
 * Count the shops of the product.
 * If you pass in nothing as a parameter, the current post will be used.
 *
 * @since 0.7.1
 * @param int|\WP_Post|Product|null $product_or_id
 * @return int
 */
function aff_count_product_shops($product_or_id = null)
{
    return count(aff_get_product_shops($product_or_id));
}

/**
 * Check if the product has any shops.
 * If you pass in nothing as a parameter, the current post will be used.
 *
 * @since 0.7.1
 * @param int|\WP_Post|Product|null $product_or_id
 * @return bool
 */
function aff_has_product_shops($product_or_id = null)
{
    return aff_count_product_shops($product_or_id) > 0;
}

/**
 * Get the shops by the product.
 * If you pass in nothing as a parameter, the current post will be used.
 *
 * @since 0.3
 * @param int|\WP_Post|Product|null $product_or_id
 * @return null|array
 */
function aff_get_product_shops($product_or_id = null)
{
    $product = aff_get_product($product_or_id);
    if($product === null) {
        return null;
    }

    $shops = array();
    if($product instanceof Shop_Aware_Interface) {
        $shops = $product->get_shops();
    } elseif ($product instanceof Complex_Product) {
        $default_variant = $product->get_default_variant();
        $shops = $default_variant !== null ? $default_variant->get_shops() : array();
    }

    $raw_shops = array();
    foreach ($shops as $shop) {
        $raw_shop = Shop_Helper::to_array($shop);
        $raw_shops[] = $raw_shop;
    }

    return $raw_shops;
}

/**
 * Check if the product has any related products.
 * If you pass in nothing as a parameter, the current post will be used.
 *
 * @since 0.7.1
 * @param int|\WP_Post|Product|null $product_or_id
 * @return bool
 */
function aff_has_product_related_products($product_or_id = null)
{
    $products = aff_get_product_related_products($product_or_id);

    return !empty($products);
}

/**
 * Get the related products by the product.
 * If you pass in nothing as a parameter, the current post will be used.
 *
 * @since 0.3
 * @param int|\WP_Post|Product|null $product_or_id
 * @return null|int[]
 */
function aff_get_product_related_products($product_or_id = null)
{
    $product = aff_get_product($product_or_id);
    if($product === null || !($product instanceof Relation_Aware_Interface)) {
        return null;
    }

    $related_products = $product->get_related_products();

    $raw_related_products = array();
    foreach ($related_products as $related_product) {
        $raw_related_product = $related_product->get_value();

        $raw_related_products[] = $raw_related_product;
    }

    return $raw_related_products;
}

/**
 * Get the query of the related products by the product.
 * If you pass in nothing as a product, the current post will be used.
 *
 * @since 0.3
 * @param int|\WP_Post|Product|null $product_or_id
 * @param array $args
 * @return null|WP_Query
 */
function aff_get_product_related_products_query($product_or_id = null, $args = array())
{
    $related_product_ids = aff_get_product_related_products($product_or_id);
    if (empty($related_product_ids)) {
        return null;
    }

    $options = wp_parse_args($args, array(
        'post_type' => Product::POST_TYPE,
        'post__in' => $related_product_ids,
        'order_by' => 'ASC',
    ));

    $query = new \WP_Query($options);

    return $query;
}

/**
 * Get the query for the products.
 *
 * @since 0.7.1
 * @param array $args
 * @return WP_Query
 */
function aff_get_products_query($args = array())
{
    $options = wp_parse_args($args, array(
        'post_type' => Product::POST_TYPE,
        'order_by' => 'ASC',
    ));

    $query = new \WP_Query($options);

    return $query;
}

/**
 * Check if the product has any related accessories.
 * If you pass in nothing as a parameter, the current post will be used.
 *
 * @since 0.7.1
 * @param int|\WP_Post|Product|null $product_or_id
 * @return bool
 */
function aff_has_product_related_accessories($product_or_id = null)
{
    $accessories = aff_get_product_related_accessories($product_or_id);

    return !empty($accessories);
}

/**
 * Get the related accessories by the product.
 * If you pass in nothing as a product, the current post will be used.
 *
 * @since 0.3
 * @param int|\WP_Post|Product|null $product_or_id
 * @return null|int[]
 */
function aff_get_product_related_accessories($product_or_id = null)
{
    $product = aff_get_product($product_or_id);
    if($product === null || !($product instanceof Relation_Aware_Interface)) {
        return null;
    }

    $related_accessories = $product->get_related_accessories();

    $raw_related_accessories = array();
    foreach ($related_accessories as $related_accessory) {
        $raw_related_product = $related_accessory->get_value();
        $raw_related_accessories[] = $raw_related_product;
    }

    return $raw_related_accessories;
}

/**
 * Get the query of the related accessories by the product.
 * If you pass in nothing as a product, the current post will be used.
 *
 * @since 0.3
 * @param int|\WP_Post|Product|null $product_or_id
 * @param array $args
 * @return null|WP_Query
 */
function aff_get_product_related_accessories_query($product_or_id = null, $args = array())
{
    $related_accessories_ids = aff_get_product_related_accessories($product_or_id);
    if (empty($related_accessories_ids)) {
        return null;
    }

    $options = wp_parse_args($args, array(
        'post_type' => Product::POST_TYPE,
        'post__in' => $related_accessories_ids,
        'order_by' => 'ASC',
    ));

    $query = new \WP_Query($options);

    return $query;
}

/**
 * Get the product link.
 * If you pass in nothing as a product, the current post will be used.
 *
 * @since 0.3
 * @param int|\WP_Post|Product|null $product_or_id
 * @return null|string
 */
function aff_get_product_link($product_or_id = null)
{
    $product = aff_get_product($product_or_id);
    if($product === null) {
        return null;
    }

    $link = get_permalink($product->get_post());
    if(empty($link)) {
        return null;
    }

    return esc_url($link);
}

/**
 * Print the product link.
 * If you pass in nothing as a product, the current post will be used.
 *
 * @since 0.8.8
 * @param null $product_or_id
 * @return null|string
 */
function aff_the_product_link($product_or_id = null)
{
    echo aff_get_product_link($product_or_id);
}

/**
 * Get the shop of the given product.
 * If you pass in nothing as a product, the current post will be used.
 * If you pass in nothing as an affiliate link, the cheapest shop will be used.
 *
 * @since 0.3
 * @param int|\WP_Post|Product|null $product_or_id
 * @param string|Affiliate_Link|null $affiliate_link
 * @return null|array
 */
function aff_get_product_shop($product_or_id = null, $affiliate_link = null)
{
    $product = aff_get_product($product_or_id);
    if($product === null || !($product instanceof Shop_Aware_Interface)) {
        return null;
    }

    $shop = null;
    if($affiliate_link instanceof Affiliate_Link) {
        $shop = $product->get_shop($affiliate_link);
    } elseif ($affiliate_link === null) {
        $shop = $product->get_cheapest_shop();
    } elseif (is_string($affiliate_link)) {
        $shop = $product->get_shop(new Affiliate_Link($affiliate_link));
    }

    if($shop === null) {
        return null;
    }

    $raw_shop = Shop_Helper::to_array($shop);

    return $raw_shop;
}

/**
 * Check if the given product has any tags.
 * If you pass in nothing as a product, the current post will be used.
 *
 * @since 0.7.1
 * @param null $product_or_id
 * @return bool
 */
function aff_has_product_tags($product_or_id = null)
{
    $product = aff_get_product($product_or_id);
    if($product === null) {
        return false;
    }

    if($product instanceof Tag_Aware_Interface) {
        return $product->has_tags();
    }

    if ($product instanceof Complex_Product) {
        $default_variant = $product->get_default_variant();
        if(empty($default_variant)) {
            return false;
        }

        return $default_variant->has_tags();
    }

    return false;
}

/**
 * Get the tags of the given product.
 * If you pass in nothing as a product, the current post will be used.
 *
 * @since 0.7.1
 * @param int|\WP_Post|Product|null $product_or_id
 * @return null|array
 */
function aff_get_product_tags($product_or_id = null)
{
    $product = aff_get_product($product_or_id);
    if($product === null) {
        return null;
    }

    if($product instanceof Tag_Aware_Interface) {
        $tags = $product->get_tags();
    }

    if ($product instanceof Complex_Product) {
        $default_variant = $product->get_default_variant();
        if(empty($default_variant)) {
            return null;
        }

        $tags = $default_variant->get_tags();
    }

    if(empty($tags)) {
        return null;
    }

    $raw_tags = array();
    foreach ($tags as $tag) {
        $raw_tags[] = $tag->get_value();
    }

    return $raw_tags;
}

/**
 * Print the tags of the given product.
 * If you pass in nothing as a product, the current post will be used.
 *
 * @since 0.7.1
 * @param int|\WP_Post|Product|null $product_or_id
 * @param string $before
 * @param string $after
 */
function aff_the_product_tags($product_or_id = null, $before = '', $after = '')
{
    $tags = aff_get_product_tags($product_or_id);
    if(empty($tags)) {
        return;
    }

    foreach ($tags as $tag) {
        echo $before . $tag . $after;
    }
}

/**
 * Get the cheapest shop of the given product.
 * If you pass in nothing as a product, the current post will be used.
 *
 * @since 0.5.1
 * @param int|\WP_Post|Product|null $product_or_id
 * @return null|array
 */
function aff_get_product_cheapest_shop($product_or_id = null)
{
    $product = aff_get_product($product_or_id);
    if($product === null) {
        return null;
    }

    if($product instanceof Complex_Product) {
        $product = $product->get_default_variant();
    }

    if(!($product instanceof Shop_Aware_Interface)) {
        return null;
    }

    $shop = $product->get_cheapest_shop();
    if($shop === null) {
        return null;
    }

    $raw_shop = Shop_Helper::to_array($shop);

    return $raw_shop;
}

/**
 * Check if the product has any discounted price.
 * If you pass in nothing as a product, the current post will be used.
 * If you pass in nothing as an affiliate link, the cheapest shop will be used.
 *
 * @since 0.7.1
 * @param int|\WP_Post|Product|null $product_or_id
 * @param string|Affiliate_Link|null $affiliate_link
 * @return bool
 */
function aff_has_product_price($product_or_id = null, $affiliate_link = null)
{
    $price = aff_get_product_price($product_or_id, $affiliate_link);

    return !empty($price);
}

/**
 * Get the discounted price with the currency of the product.
 * If you pass in nothing as a product, the current post will be used.
 * If you pass in nothing as an affiliate link, the cheapest shop will be used.
 *
 * @since 0.3
 * @param int|\WP_Post|Product|null $product_or_id
 * @param string|Affiliate_Link|null $affiliate_link
 * @return null|string
 */
function aff_get_product_price($product_or_id = null, $affiliate_link = null)
{
    $product = aff_get_product($product_or_id);
    if($product === null) {
        return null;
    }

    if($product instanceof Complex_Product) {
        $product = $product->get_default_variant();
    }

    if(!($product instanceof Shop_Aware_Interface)) {
        return null;
    }

    $shop = null;
    if($affiliate_link instanceof Affiliate_Link) {
        $shop = $product->get_shop($affiliate_link);
    } elseif ($affiliate_link === null) {
        $shop = $product->get_cheapest_shop();
    } elseif (is_string($affiliate_link)) {
        $shop = $product->get_shop(new Affiliate_Link($affiliate_link));
    }
    if (empty($shop)) {
        return null;
    }

    $price = $shop->get_pricing()->get_price();
    if($price === null) {
        return null;
    }

    $raw_price = $price->get_value() . ' ' . $price->get_currency()->get_symbol();

    return $raw_price;
}

/**
 * Print the price with the currency of the product.
 * If you pass in nothing as a product, the current post will be used.
 * If you pass in nothing as an affiliate link, the cheapest shop will be used.
 *
 * @since 0.7.1
 * @param int|\WP_Post|Product|null $product_or_id
 * @param string|Affiliate_Link|null $affiliate_link
 */
function aff_the_product_price($product_or_id = null, $affiliate_link = null)
{
    $price = aff_get_product_price($product_or_id, $affiliate_link);
    if(!empty($price)) {
        echo $price;
    };
}

/**
 * Get the cheapest price with the currency of the product.
 * If you pass in nothing as a product, the current post will be used.
 *
 * @since 0.5.1
 * @param int|\WP_Post|Product|null $product_or_id
 * @return null|string
 */
function aff_get_product_cheapest_price($product_or_id = null)
{
    $product = aff_get_product($product_or_id);
    if($product === null || !($product instanceof Shop_Aware_Interface)) {
        return null;
    }

    $shop = $product->get_cheapest_shop();
    if (empty($shop)) {
        return null;
    }

    $price = $shop->get_pricing()->get_price();
    if($price === null) {
        return null;
    }

    $raw_price = $price->get_value() . ' ' . $price->get_currency()->get_symbol();

    return $raw_price;
}

/**
 * Get the affiliate link by the product and shop
 * If you pass in nothing as a product, the current post will be used.
 * If you pass in nothing as a shop, the first shop will be used.
 *
 * @since 0.3
 * @param int|\WP_Post|Product|null $product_or_id
 * @param int|\WP_Post|Shop_Template|null $shop_or_id
 * @return null|string
 */
function aff_get_product_affiliate_link($product_or_id = null, $shop_or_id = null)
{
    $shop = aff_get_product_shop($product_or_id, $shop_or_id);
    if(empty($shop)) {
        return null;
    }

    $affiliate_link = isset($shop['affiliate_link']) ? $shop['affiliate_link'] : null;

    return esc_url($affiliate_link);
}

/**
 * Print the affiliate link by the product and shop.
 * If you pass in nothing as a product, the current post will be used.
 * If you pass in nothing as a shop, the first shop will be used.
 *
 * @since 0.8.8
 * @param int|\WP_Post|Product|null $product_or_id
 * @param int|\WP_Post|Shop_Template|null $shop_or_id
 */
function aff_the_product_affiliate_link($product_or_id = null, $shop_or_id = null)
{
    echo aff_get_product_link($product_or_id);
}

/**
 * Get the affiliate link by the product and shop
 * If you pass in nothing as a product, the current post will be used.
 *
 * @since 0.5.1
 * @param int|\WP_Post|Product|null $product_or_id
 * @return null|string
 */
function aff_get_product_cheapest_affiliate_link($product_or_id = null)
{
    $shop = aff_get_product_cheapest_shop($product_or_id);
    if(empty($shop)) {
        return null;
    }


    $affiliate_link = isset($shop['affiliate_link']) ? $shop['affiliate_link'] : null;

    return $affiliate_link;
}

/**
 * Check if the product is of the given type.
 * If you pass in nothing as a product, the current post will be used.
 *
 * @since 0.6
 * @param string|Type $type
 * @param int|\WP_Post|Product|null $product_or_id
 * @return bool
 */
function aff_product_is_type($type, $product_or_id = null)
{
    $product = aff_get_product($product_or_id);
    if($product === null) {
        return false;
    }

    if($type instanceof Type) {
        $type = $type->get_value();
    }

    return $product->get_type()->get_value() == $type;
}

/**
 * Check if the product is a simple product.
 * If you pass in nothing as a product, the current post will be used.
 *
 * @since 0.6
 * @param int|\WP_Post|Product|null $product_or_id
 * @return bool
 */
function aff_product_is_simple($product_or_id = null)
{
    return aff_product_is_type(Type::simple(), $product_or_id);
}

/**
 * Check if the product is a complex product.
 * If you pass in nothing as a product, the current post will be used.
 *
 * @since 0.6
 * @param int|\WP_Post|Product|null $product_or_id
 * @return bool
 */
function aff_product_is_complex($product_or_id = null)
{
    return aff_product_is_type(Type::complex(), $product_or_id);
}

/**
 * Check if the product is a product variant.
 * If you pass in nothing as a product, the current post will be used.
 *
 * @since 0.6
 * @param int|\WP_Post|Product|null $product_or_id
 * @return bool
 */
function aff_product_is_variant($product_or_id = null)
{
    return aff_product_is_type(Type::variant(), $product_or_id);
}

/**
 * Get the parent of the product variant.
 * If the given product is already the parent, it will be returned instead.
 * If you pass in nothing as a product, the current post will be used.
 *
 * @since 0.6
 * @param int|\WP_Post|Product|null $product_or_id
 * @return null|Product
 */
function aff_product_get_parent($product_or_id = null)
{
    $product = aff_get_product($product_or_id);
    if($product === null) {
        return null;
    }

    if($product instanceof Complex_Product) {
        return $product;
    }

    if($product instanceof Product_Variant) {
        $parent = $product->get_parent();

        return $parent;
    }

    return null;
}

/**
 * Check if the given parent complex product contains the variants
 * If you pass in nothing as a complex product, the current post will be used.
 * If you pass in nothing as a product variant, the default variant will be used.
 *
 * @since 0.6
 * @param int|\WP_Post|Product|null $complex_or_id
 * @param int|\WP_Post|Product|null $variant_or_id
 * @return bool
 */
function aff_product_has_variant($complex_or_id = null, $variant_or_id = null)
{
    $complex_product = aff_get_product($complex_or_id);
    if(!($complex_product instanceof Complex_Product)) {
        return false;
    }

    $product_variant = aff_get_product($variant_or_id);
    if(!($product_variant instanceof Product_Variant)) {
        $result = $complex_product->has_variants();
    } else {
        $result = $complex_product->has_variant($product_variant->get_slug());
    }

    return $result;
}

/**
 * Get the product variant by the complex parent product.
 * If you pass in nothing as a complex product, the current post will be used.
 * If you pass in nothing as a product variant, the default variant will be used.
 *
 * @since 0.8
 * @param int|\WP_Post|Complex_Product|null $complex_or_id
 * @param int|\WP_Post|Complex_Product|null $variant_or_id
 * @return null|Product_Variant
 */
function aff_product_get_variant($complex_or_id = null, $variant_or_id = null)
{
    $complex_product = aff_get_product($complex_or_id);
    if(!($complex_product instanceof Complex_Product)) {
        return null;
    }

    $product_variant = aff_get_product($variant_or_id);
    if(!($product_variant instanceof Product_Variant)) {
        return null;
    }

    $product_variant = $complex_product->get_variant($product_variant->get_slug());

    return $product_variant;
}

/**
 * Check if the given product has any variants.
 * If you pass in nothing as a complex product, the current post will be used.
 *
 * @since 0.7.1
 * @param int|\WP_Post|Product|null $complex_or_id
 * @return bool
 */
function aff_product_has_variants($complex_or_id = null)
{
    $complex_product = aff_get_product($complex_or_id);
    if(!($complex_product instanceof Complex_Product)) {
        return null;
    }

    $result = $complex_product->has_variants();

    return $result;
}

/**
 * Get the product variants of the given product.
 * If you pass in nothing as a complex product, the current post will be used.
 *
 * @since 0.6
 * @param int|\WP_Post|Product|null $complex_or_id
 * @return Product_variant[]
 */
function aff_product_get_variants($complex_or_id = null)
{
    $complex_product = aff_get_product($complex_or_id);
    if(!($complex_product instanceof Complex_Product)) {
        return array();
    }

    $variants = $complex_product->get_variants();

    return $variants;
}

/**
 * Get the default variant of the given product.
 * If you pass in nothing as a complex product, the current post will be used.
 *
 * @since 0.6
 * @param int|\WP_Post|Product|null $complex_or_id
 * @return null|Product_variant
 */
function aff_product_get_default_variant($complex_or_id = null)
{
    $complex_product = aff_get_product($complex_or_id);
    if(!($complex_product instanceof Complex_Product)) {
        return null;
    }

    $default_variant = $complex_product->get_default_variant();

    return $default_variant;
}

/**
 * Check if the given variant is the default one
 *
 * @since 0.6
 * @param int|\WP_Post|Product|null $complex_or_id
 * @param int|\WP_Post|Product|null $variant_or_id
 * @return bool
 */
function aff_product_is_default_variant($complex_or_id = null, $variant_or_id = null) {

    $complex_product = aff_get_product($complex_or_id);
    if(!($complex_product instanceof Complex_Product)) {
        return false;
    }

    $product_variant = aff_get_product($variant_or_id);
    if($product_variant === null || !($complex_product instanceof Product_Variant)) {
        return false;
    }

    $default_variant = aff_product_get_default_variant($complex_product);

    return $product_variant->is_equal_to($default_variant);
}

/**
 * Get the attributes of the product variant
 * If you pass in nothing as a product, the current post will be used.
 * If you pass in nothing as a variant, the default variant will be used.
 *
 * @since 0.8
 * @param int|\WP_Post|Product|null $product_or_id
 * @param int|\WP_Post|Product|null $variant_or_id
 * @return null|array
 */
function aff_product_get_variant_attributes($product_or_id = null, $variant_or_id = null)
{
    $complex_product = aff_get_product($product_or_id);
    if($complex_product instanceof Product_Variant) {
        $complex_product = $complex_product->get_parent();
    }

    if(!($complex_product instanceof Complex_Product)) {
        return null;
    }

    $product_variant = null;
    if($variant_or_id === null) {
        $product_variant = $complex_product->get_default_variant();
    } else {
        $product_variant = aff_product_get_variant($complex_product, $variant_or_id);
    }

    if($product_variant === null) {
        return null;
    }

    $raw_attributes = array();
    foreach ($product_variant->get_attributes() as $attribute) {
        $raw_attributes[] = Attribute_Helper::to_array($attribute);
    }

    return $raw_attributes;
}

/**
 * Get the product attributes choices
 *
 * @since 0.6
 * @param int|\WP_Post|Product|null $product_or_id
 * @return null|array
 */
function aff_get_product_attribute_choices($product_or_id = null)
{
    // Current product
    $product = aff_get_product($product_or_id);
    if($product === null) {
        return null;
    }

    // Parent product
    $parent = aff_product_get_parent($product);
    if($parent === null) {
        return null;
    }

    // Product variants
    $variants = aff_product_get_variants($parent);
    if($variants === null) {
        return null;
    }

    // Current attribute
    if($product instanceof Product_Variant) {
        $current_attributes = aff_product_get_variant_attributes($parent, $product);
    } elseif($product instanceof Complex_Product) {
        $current_attributes = aff_product_get_variant_attributes($product);
    }

    if(empty($current_attributes)) {
        return null;
    }

    // Create the basic choices without permalinks and display
    $choices = array();
    foreach ($variants as $variant) {
        if(!$variant->has_id()) {
            continue;
        }

        $attributes = aff_product_get_variant_attributes($product, $variant);
        if(empty($attributes)) {
            continue;
        }

        foreach ($attributes as $index => $attribute) {
            if(!isset($choices[$attribute['name']])) {
                $choices[$attribute['name']] = array(
                    'name' => $attribute['name'],
                    'slug' => $attribute['slug'],
                    'attributes' => array(),
                );
            }

            // Get the previous and next index
            $next_index = $index + 1 < count($attributes) ? $index + 1 : $index;
            $prev_index = $index - 1 >= 0 ? $index - 1 : 0;

            $display = 'unreachable';
            if($attribute['value'] == $current_attributes[$index]['value']) {
                $display = 'selected';
            }

            if ($display == 'unreachable' && (
                ($index !== $prev_index && $attributes[$prev_index]['value'] == $current_attributes[$prev_index]['value']) ||
                ($index !== $next_index && $attributes[$next_index]['value'] == $current_attributes[$next_index]['value']))) {
                $display = 'reachable';
            }

            if( !isset($choices[$attribute['name']]['attributes'][$attribute['value']]) ||
                ($display == 'selected' && $choices[$attribute['slug']]['attributes'][$attribute['value']]['display'] != 'selected') ||
               ($display == 'reachable' && $choices[$attribute['slug']]['attributes'][$attribute['value']]['display'] == 'unreachable')) {

                $choices[$attribute['name']]['attributes'][$attribute['value']] = array(
                    'value' => $attribute['value'],
                    'unit' => $attribute['unit'],
                    'display' => $display,
                    'permalink' => $display == 'selected' ? null : get_permalink($variant->get_raw_post()),
                );
            }
        }
    }

    // Remove the keys
    $choices = array_values($choices);
    foreach ($choices as $index => $choice) {
        $choices[$index]['attributes'] = array_values($choices[$index]['attributes']);
    }

    return $choices;
}

/**
 * Prints the product attributes choices to the screen
 *
 * @since 0.6
 * @param int|\WP_Post|Product|null $product_or_id
 */
function aff_the_product_attribute_choices($product_or_id = null)
{
    $attribute_choices = aff_get_product_attribute_choices($product_or_id);
    if(empty($attribute_choices)) {
        return;
    }

    echo '<div class="aff-product-attributes-container">';
    echo '<ul class="aff-product-attributes-choices-list">';

    foreach ($attribute_choices as $name => $attribute_choice) {
        echo '<li class="aff-product-attributes-choices">';
        echo '<span class="aff-product-attributes-choices-name">' . $attribute_choice['name'] . '</span>';
        echo '<ul class="aff-product-attributes-choice-list">';

        foreach ($attribute_choice['attributes'] as $attribute) {
            echo '<li class="aff-product-attributes-choice ' . $attribute['display'] . '">';
            if(!empty($attribute['permalink'])): echo '<a href="' . $attribute['permalink'] .'">'; endif;
            echo $attribute['value'];
            if(!empty($attribute['unit'])): echo ' <span class="unit">' . $attribute['unit'] . '</span>'; endif;
            if(!empty($attribute['permalink'])): echo '</a>'; endif;
            echo '</li>';
        }

        echo '</ul>';
        echo '</li>';
    }

    echo "</ul>";
    echo "</div>";
}

/**
 * Get the shop template by the ID or Wordpress term.
 *
 * @since 0.6
 * @param int|array|\WP_Term|Shop_Template $shop_or_id
 * @return null|Shop_Template
 */
function aff_get_shop_template($shop_or_id)
{
    $shop = Shop_Template_Helper::find_one($shop_or_id);

    return $shop;
}

/**
 * Get the detail template by the ID or Wordpress term.
 *
 * @since 0.8
 * @param int|array|\WP_Term|Detail_Template $detail_template_or_id
 * @return null|Detail_Template
 */
function aff_get_detail_template($detail_template_or_id)
{
    $detail_template = Detail_Template_Helper::find_one($detail_template_or_id);

    return $detail_template;
}

/**
 * Get the attribute template by the ID or Wordpress term.
 *
 * @since 0.8
 * @param int|array|\WP_Term|Attribute_Template $attribute_template_or_id
 * @return null|Attribute_Template
 */
function aff_get_attribute_template($attribute_template_or_id)
{
    $attribute_template = Attribute_Template_Helper::find_one($attribute_template_or_id);

    return $attribute_template;
}

/**
 * Get the price indication like VAT and shipping costs.
 *
 * @since 0.7
 * @return string
 */
function aff_get_shop_price_indication()
{
    return __('Incl. 19 % VAT and excl. shipping costs.', 'affilicious');
}

/**
 * Print the price indication like VAT and shipping costs.
 *
 * @since 0.7
 */
function aff_the_shop_price_indication()
{
    echo aff_get_shop_price_indication();
}

/**
 * Get the last update indication for the shop.
 *
 * @since 0.7
 * @param array $shop
 * @return string
 */
function aff_get_shop_updated_at_indication($shop)
{
    if(!empty($shop['updated_at'])) {
        return sprintf(
            __('Last updated: %s.', 'affilicious'),
            $shop['updated_at']
        );
    }

    return '';
}

/**
 * Print the last update indication for the shop.
 *
 * @since 0.7
 * @param array $shop
 */
function aff_the_shop_updated_at_indication($shop)
{
    echo aff_get_shop_updated_at_indication($shop);
}

/**
 * Check if the shop is available or out of stock.
 *
 * @since 0.7
 * @param array $shop
 * @return bool
 */
function aff_is_shop_available($shop)
{
    return isset($shop['pricing']['availability']) && $shop['pricing']['availability'] === Availability::AVAILABLE;
}

/**
 * Check if the shop should display the stock price.
 * This is important if the discounted price is greater than the stock price.
 *
 * @since 0.8
 * @param array $shop
 * @return bool
 */
function aff_should_shop_display_old_price($shop)
{
    if(!isset($shop['pricing']['price']['value']) || !isset($shop['pricing']['old_price']['value'])) {
        return false;
    }

    $price = floatval($shop['pricing']['price']['value']);
    $old_price = floatval($shop['pricing']['old_price']['value']);

    return $old_price > $price;
}
