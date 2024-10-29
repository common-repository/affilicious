<?php
namespace Affilicious\Product\Meta_Box;

if (!defined('ABSPATH')) {
    exit('Not allowed to access pages directly.');
}

/**
 * This meta box is responsible for displaying the product image gallery
 *
 * Inspired by the Woocommerce aquivalent:
 * https://github.com/woothemes/woocommerce/blob/master/includes/admin/meta-boxes/class-wc-meta-box-product-images.php
 * @deprecated 1.0
 * @since 0.6
 */
class Product_Image_Gallery_Meta_Box implements Meta_Box_Interface
{
    /**
     * @since 0.6
     * @var string
     */
    const META_KEY = 'product_image_gallery';

	/**
	 * @since 0.6
	 * @var string
	 */
    const STORE_KEY = 'affilicious_product_image_gallery';

    /**
     * @inheritdoc
     * @since 0.6
     */
    public static function render(\WP_Post $post, $args)
    {
        $product_image_gallery = get_post_meta($post->ID, '_' . self::STORE_KEY, true);
        $attachment_ids = array_filter(explode(',', $product_image_gallery));

        ?>
        <div id="product_images_container">
            <ul class="product_images">
                <?php
                if (!empty($attachment_ids)) {
                    $update_meta = false;
                    $updated_attachment_ids = array();

                    foreach ($attachment_ids as $attachment_id) {
                        $attachment = wp_get_attachment_image($attachment_id, 'thumbnail');

                        if (empty($attachment)) {
                            $update_meta = true;
                            continue;
                        }

                        ?>
                        <li class="image" data-attachment_id="<?php echo esc_attr($attachment_id); ?>">
                            <?php echo $attachment; ?>
                            <ul class="actions">
                                <li><a href="#" class="delete tips"
                                       data-tip="<?php esc_attr__('Delete Image', 'affilicious'); ?>">
                                        <?php __('Delete', 'affilicious'); ?>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <?php

                        $updated_attachment_ids[] = $attachment_id;
                    }

                    // _update new image gallery IDs
                    if ($update_meta) {
                        update_post_meta( $post->ID, '_' . self::STORE_KEY, implode(',', $updated_attachment_ids));
                    }
                }
                ?>
            </ul>
            <input type="hidden" id="<?php echo self::META_KEY; ?>"
                   name="<?php echo self::META_KEY; ?>"
                   value="<?php echo esc_attr($product_image_gallery); ?>" />
        </div>
        <p class="add_product_images hide-if-no-js">
            <a href="#"
               data-choose="<?php esc_attr_e('Add Images', 'affilicious'); ?>"
               data-update="<?php esc_attr_e('Add Image', 'affilicious'); ?>"
               data-delete="<?php esc_attr_e('Delete Image', 'affilicious'); ?>"
               data-text="<?php esc_attr_e('Delete Image', 'affilicious'); ?>">
                <?php _e('Add Images To Image Gallery', 'affilicious'); ?>
            </a>
        </p>
        <?php
    }

    /**
     * @inheritdoc
     * @since 0.6
     */
    public static function update($post_id, \WP_Post $post)
    {
        $attachment_ids = array();
        if (isset($_POST[self::META_KEY])) {
            $attachment_ids = array_filter(explode(',', $_POST[self::META_KEY]));
        }

        update_post_meta($post_id, '_' . self::STORE_KEY, implode(',', $attachment_ids));
    }
}
