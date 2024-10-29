<?php
namespace Affilicious\Product\Model;

use Affilicious\Common\Helper\Assert_Helper;
use Affilicious\Common\Model\Slug;
use Affilicious\Detail\Model\Detail;

if (!defined('ABSPATH')) {
    exit('Not allowed to access pages directly.');
}

/**
 * @since 0.8
 */
trait Detail_Aware_Trait
{
    /**
     * @since 0.8
     * @var Detail[]
     */
	protected $details;

    /**
     * @since 0.8
     */
    public function __construct()
    {
        $this->details = array();
    }

    /**
     * Check if the product has a specific detail by the slug.
     *
     * @since 0.8
     * @param Slug $slug
     * @return bool
     */
    public function has_detail(Slug $slug)
    {
        return isset($this->details[$slug->get_value()]);
    }

    /**
     * Add a new product detail.
     *
     * @since 0.8
     * @param Detail $detail
     */
    public function add_detail(Detail $detail)
    {
        $this->details[$detail->get_slug()->get_value()] = $detail;
    }

    /**
     * Remove the product detail by the slug.
     *
     * @since 0.8
     * @param Slug $slug
     */
    public function remove_detail(Slug $slug)
    {
        unset($this->details[$slug->get_value()]);
    }

    /**
     * Get the product detail by the slug.
     *
     * @since 0.8
     * @param Slug $slug
     * @return null|Detail
     */
    public function get_detail(Slug $slug)
    {
        if(!$this->has_detail($slug)) {
            return null;
        }

        $detail = $this->details[$slug->get_value()];

        return $detail;
    }

    /**
     * Check if the product has any details.
     *
     * @since 0.8
     * @return bool
     */
    public function has_details()
    {
        return !empty($this->details);
    }

    /**
     * Get the product details.
     *
     * @since 0.8
     * @return Detail[]
     */
    public function get_details()
    {
        $details = array_values($this->details);

        return $details;
    }

    /**
     * Set the product details.
     * If you do this, the old details going to be replaced.
     *
     * @since 0.8
     * @param Detail[] $details
     */
    public function set_details($details)
    {
	    Assert_Helper::all_is_instance_of($details, Detail::class, __METHOD__, 'Expected an array of details. But one of the values is %s', '0.9.2');

        $this->details = $details;
    }
}
