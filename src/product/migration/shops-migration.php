<?php
namespace Affilicious\Product\Migration;

use Affilicious\Common\Model\Slug;
use Affilicious\Product\Model\Simple_Product;
use Affilicious\Product\Repository\Product_Repository_Interface;
use Affilicious\Shop\Repository\Shop_Template_Repository_Interface;

if (!defined('ABSPATH')) {
    exit('Not allowed to access pages directly.');
}

/**
 * @since 0.8
 */
final class Shops_Migration
{
    /**
     * @since 0.8
     * @var Product_Repository_Interface
     */
    protected $product_repository;

    /**
     * @since 0.8
     * @var Shop_Template_Repository_Interface
     */
    protected $shop_template_repository;

    /**
     * @since 0.8
     * @param Product_Repository_Interface $product_repository
     * @param Shop_Template_Repository_Interface $shop_template_repository
     */
    public function __construct(
        Product_Repository_Interface $product_repository,
        Shop_Template_Repository_Interface $shop_template_repository
    ) {
        $this->product_repository = $product_repository;
        $this->shop_template_repository = $shop_template_repository;
    }

    /**
     * Migrate the old shops to the new product.
     *
     * @since 0.8
     */
    public function migrate()
    {
        $products = $this->product_repository->find_all();
        foreach ($products as $product) {
            if(!($product instanceof Simple_Product)) {
                continue;
            }

            $shops = carbon_get_post_meta($product->get_id()->get_value(), '_affilicious_product_shops', 'complex');
            if(!empty($shops)) {
                foreach ($shops as $index => $shop) {
                    if (!isset($shop['_type'])) {
                        continue;
                    }

                    $slug = str_replace('_', '-', substr($shop['_type'], 1, strlen($shop['_type'])));
                    $shop_template = $this->shop_template_repository->find_one_by_slug(new Slug($slug));
                    if ($shop_template === null) {
                        continue;
                    }

                    $template_id = $shop_template->get_id()->get_value();

                    $meta_key = sprintf('_affilicious_product_shops%s-_template_id_%s', $shop['_type'], $index);
                    $old_meta_key = sprintf('_affilicious_product_shops%s-_shop_template_id_%s', $shop['_type'], $index);

                    delete_post_meta($product->get_id()->get_value(), $old_meta_key);

                    if (!update_post_meta($product->get_id()->get_value(), $meta_key, $template_id)) {
                        add_post_meta($product->get_id()->get_value(), $meta_key, $template_id);
                    }
                }
            }
        }
    }
}
