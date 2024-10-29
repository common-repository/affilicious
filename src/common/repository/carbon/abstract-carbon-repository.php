<?php
namespace Affilicious\Common\Repository\Carbon;

use Affilicious\Common\Repository\Wordpress\Abstract_Wordpress_Repository;

if (!defined('ABSPATH')) {
    exit('Not allowed to access pages directly.');
}

/**
 * @since 0.8
 */
abstract class Abstract_Carbon_Repository extends Abstract_Wordpress_Repository
{
    /**
     * Builds the complex carbon post meta keys for the storage
     * This method works recursively and it's not easy to understand.
     *
     * @see https://carbonfields.net/docs/complex-field-data-storage/
     * @since 0.8
     * @param array $values
     * @param string $prefix
     * @param int $depth
     * @param string|int $prev_key
     * @return array
     *
     * Example process:
     *  _affilicious_product_variants_-_shops_0_amazon-_title_0  -> complex
     *  _-_shops_0_amazon-_title_0                               -> group
     *  _shops_0_amazon-_title_0                                 -> field
     *  _amazon-_title_0                                         -> group
     *  _title_0                                                 -> field
     *
     * Example input:
     * array(
     *   '_' => array(
     *     0 => array(
     *       'title' => 'Product Variant 1',
     *       'thumbnail' => 'http://url-to-thumbnail.com',
     *       'shops' => array(
     *         'amazon' =>array(
     *           0 => array(
     *             'shop_id' => 3,
     *             'title' => 'Amazon',
     *           )
     *         )
     *       )
     *     )
     *   )
     * )
     *
     * Example output:
     * array(
     *    _affilicious_product_variants_-_title_0 => 'Product Variant 1',
     *    _affilicious_product_variants_-_thumbnail_0 => 'http://url-to-thumbnail.com',
     *    _affilicious_product_variants_-_shops_0_amazon-_shop_id_0 => 3,
     *    _affilicious_product_variants_-_shops_0_amazon-_title_0 => 'Amazon',
     * )
     */
    protected function build_complex_carbon_meta_key($values, $prefix, $depth = 0, $prev_key = '')
    {
        $regex_complex = '_%s';
        $regex_group = '_%s-';
        $regex_field = '_%s_%d';

        if($depth === 0) {
            if(strpos($prefix, '_') === 0) {
                $prefix = substr($prefix, 1, strlen($prefix) - 1);
            }

            $prefix = sprintf($regex_complex, $prefix);
        }

        $temp = array();
        if(is_array($values)) {
            foreach ($values as $key => $value) {

                // Key is a string. Entry might be a complex field, a group or a simple field
                if(is_string($key)) {

                    // Value is an array. Entry might be a complex field or a group
                    if(is_array($value)) {

                        // The previous key is an int. Entry must be a complex field
                        if(is_int($prev_key)) {
                            $_prefix = $prefix . sprintf($regex_field, $key, $prev_key);
                            $temp[] = $this->build_complex_carbon_meta_key($value, $_prefix, $depth + 1, $key);

                            // The previous key is a string. Entry must be a group
                        } else {
                            $_prefix = $prefix . sprintf($regex_group, $key);
                            $temp[] = $this->build_complex_carbon_meta_key($value, $_prefix, $depth + 1, $key);
                        }

                        // Key is a string. Entry must be a simple field
                    } else {
                        $_prefix = $prefix . sprintf($regex_field, $key, $prev_key);
                        $temp[$_prefix] = $value;
                    }

                    // Key is int. Entry must be a repeatable field
                } else {
                    $temp[] = $this->build_complex_carbon_meta_key($value, $prefix, $depth + 1, $key);
                }
            }
        }

        // Break the recursion
        if($depth > 0) {
            return $temp;
        }

        // Remove the nested arrays
        $result = array();
        array_walk_recursive($temp, function($value, $key) use (&$result) {
            $result[$key] = $value;
        });

        return $result;
    }
}
