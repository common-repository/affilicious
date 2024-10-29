<?php
namespace Affilicious\Shop\Helper;

use Affilicious\Common\Helper\Image_Helper;
use Affilicious\Shop\Model\Shop_Template;
use Affilicious\Shop\Model\Shop_Template_Id;
use Affilicious\Shop\Repository\Shop_Template_Repository_Interface;

if (!defined('ABSPATH')) {
    exit('Not allowed to access pages directly.');
}

/**
 * @since 0.8
 */
class Shop_Template_Helper
{
    /**
     * Check if the Wordpress term or term ID belongs to a shop template.
     *
     * @since 0.8.9
     * @param int|string|array|\WP_Term|Shop_Template|Shop_Template_Id $term_or_id
     * @return bool
     */
    public static function is_shop_template($term_or_id)
    {
        // The argument is already a shop template
        if ($term_or_id instanceof Shop_Template) {
            return true;
        }

        // The argument is an integer or string.
        if(is_int($term_or_id) || is_string($term_or_id)) {
            return term_exists(intval($term_or_id), Shop_Template::TAXONOMY);
        }

        // The argument is an attribute template ID
        if($term_or_id instanceof Shop_Template_Id) {
            return term_exists($term_or_id->get_value(), Shop_Template::TAXONOMY);
        }

        // The argument is an array of a shop template.
        if(is_array($term_or_id) && !empty($term_or_id['id'])) {
            return term_exists(intval($term_or_id['id']), Shop_Template::TAXONOMY);
        }

        // The argument is an array of a shop.
        if(is_array($term_or_id) && !empty($term_or_id['template_id'])) {
            return term_exists(intval($term_or_id['template_id']), Shop_Template::TAXONOMY);
        }

        // The argument is a term.
        if($term_or_id instanceof \WP_Term) {
            return $term_or_id->taxonomy === Shop_Template::TAXONOMY;
        }

        return false;
    }

    /**
     * Find one shop template by the ID or Wordpress term.
     *
     * @since 0.8
     * @param int|string|array|\WP_Term|Shop_Template|Shop_Template_Id $term_or_id
     * @return Shop_Template|null
     */
    public static function get_shop_template($term_or_id)
    {
        /** @var Shop_Template_Repository_Interface $shop_template_repository */
        $shop_template_repository = \Affilicious::get('affilicious.shop.repository.shop_template');

        // The argument is already an shop template
        if ($term_or_id instanceof Shop_Template) {
            return $term_or_id;
        }

        // The argument is an attribute template ID
        if($term_or_id instanceof Shop_Template_Id) {
            return $shop_template_repository->find_one_by_id($term_or_id);
        }

        // The argument is an integer or string.
        if(is_int($term_or_id) || is_string($term_or_id)) {
            return $shop_template_repository->find_one_by_id(new Shop_Template_Id($term_or_id));
        }

        // The argument is an array of an shop template
        if(is_array($term_or_id) && !empty($term_or_id['id'])) {
            return $shop_template_repository->find_one_by_id(new Shop_Template_Id($term_or_id['id']));
        }

        // The argument is an array of an shop.
        if(is_array($term_or_id) && !empty($term_or_id['template_id'])) {
            return $shop_template_repository->find_one_by_id(new Shop_Template_Id($term_or_id['template_id']));
        }

        // The argument is a term.
        if($term_or_id instanceof \WP_Term) {
            return $shop_template_repository->find_one_by_id(new Shop_Template_Id($term_or_id->term_id));
        }

        return null;
    }

    /**
     * Convert the shop template into an array.
     *
     * @since 0.8.9
     * @param Shop_Template $shop_template
     * @return array
     */
    public static function to_array(Shop_Template $shop_template)
    {
        $array = array(
            'id' => $shop_template->has_id() ? $shop_template->get_id()->get_value() : null,
            'name' => $shop_template->get_name()->get_value(),
            'slug' => $shop_template->get_slug()->get_value(),
            'thumbnail' => $shop_template->has_thumbnail() ? Image_Helper::to_array($shop_template->get_thumbnail()) : null,
            'provider_id' => $shop_template->has_provider_id() ? $shop_template->get_provider_id()->get_value() : null,
            'price_indication' => $shop_template->has_price_indication() ? $shop_template->get_price_indication()->get_value() : null,
            'custom_values' => $shop_template->has_custom_values() ? $shop_template->get_custom_values() : null,

            // Deprecated 1.1. It's just used for legacy purpose. Use 'thumbnail' instead.
            'thumbnail_id' => $shop_template->has_thumbnail_id() ? $shop_template->get_thumbnail_id()->get_value() : null,
        );

        $array = apply_filters('aff_shop_template_to_array', $array, $shop_template);

        return $array;
    }
}
