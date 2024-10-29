<?php
namespace Affilicious\Product\Migration;

if (!defined('ABSPATH')) {
    exit('Not allowed to access pages directly.');
}

/**
 * @since 0.7
 */
final class Post_Type_Migration
{
    /**
     * Migrate the old product post type from "product" to "aff_product"
     * to prevent any unnecessary collisions with other plugins.
     *
     * @since 0.7
     */
    public function migrate()
    {
        global $wpdb;

        $wpdb->query("
            UPDATE $wpdb->posts posts
            LEFT JOIN $wpdb->postmeta meta 
            ON posts.id = meta.post_id
            SET posts.post_type = 'aff_product'
            WHERE posts.post_type = 'product'
            AND meta.meta_key LIKE '_affilicious%'
        ");
    }
}
