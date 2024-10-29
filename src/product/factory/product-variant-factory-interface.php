<?php
namespace Affilicious\Product\Factory;

use Affilicious\Common\Model\Name;
use Affilicious\Common\Model\Slug;
use Affilicious\Product\Model\Complex_Product;
use Affilicious\Product\Model\Product_Variant;

if (!defined('ABSPATH')) {
    exit('Not allowed to access pages directly.');
}

/**
 * @since 0.8
 */
interface Product_Variant_Factory_Interface
{
    /**
     * Create a new product variant with the parent complex product.
     *
     * @since 0.8
     * @param Complex_Product $parent
     * @param Name $name
     * @param Slug $slug
     * @return Product_Variant
     */
    public function create(Complex_Product $parent, Name $name, Slug $slug);

	/**
	 * Create a new product variant with the parent complex product.
	 * The slug is auto generated by the name.
	 *
	 * @since 0.9.7
	 * @param Complex_Product $parent
	 * @param Name $name
	 * @return Product_Variant
	 */
	public function create_from_name(Complex_Product $parent, Name $name);
}