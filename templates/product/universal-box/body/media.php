<?php
/** @var array $product The product that belongs to the universal box */
$product = !empty($product) ? $product : aff_get_product();
?>

<?php do_action('affilicious_template_before_product_universal_box_media', $product); ?>

<div class="aff-product-universal-box-media aff-product-universal-box-column-half-width aff-product-universal-box-column">
    <?php if(aff_has_product_image_gallery($product)): ?>
	    <?php aff_render_template('product/universal-box/body/media/image-gallery', ['product' => $product]); ?>
    <?php else: ?>
	    <?php aff_render_template('product/universal-box/body/media/thumbnail', ['product' => $product]); ?>
    <?php endif; ?>
</div>

<?php do_action('affilicious_template_after_product_universal_box_media', $product); ?>
