<?php
namespace Affilicious\Product\Admin\Filter;

use Affilicious\Product\Model\Product;

if (!defined('ABSPATH')) {
    exit('Not allowed to access pages directly.');
}

/**
 * @since 0.9
 */
class Table_Content_Filter
{
    /**
     * Hide the product variants from the product table list
     *
     * @filter pre_get_posts
     * @since 0.9
     * @param \WP_Query $query
     */
    public function filter(\WP_Query $query)
    {
        if(is_admin() && !empty($_GET['post_type']) &&
            $_GET['post_type'] == Product::POST_TYPE &&
            $query->query['post_type'] == Product::POST_TYPE &&
            !current_user_can('be_overlord')) {
            $query->query_vars['post_parent'] = 0;
        }
    }
}
