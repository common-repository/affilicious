<?php
namespace Affilicious\Product\Update\Worker\Amazon;

use Affilicious\Common\Helper\Image_Helper;
use Affilicious\Common\Helper\Value_Helper;
use Affilicious\Common\Logger\Logger;
use Affilicious\Product\Helper\Amazon_Helper;
use Affilicious\Product\Helper\Product_Helper;
use Affilicious\Product\Model\Complex_Product;
use Affilicious\Product\Model\Product;
use Affilicious\Product\Model\Product_Id;
use Affilicious\Product\Model\Shop_Aware_Interface;
use Affilicious\Product\Repository\Product_Repository_Interface;
use Affilicious\Product\Update\Configuration\Configuration;
use Affilicious\Product\Update\Task\Batch_Update_Task;
use Affilicious\Product\Update\Update_Timer;
use Affilicious\Product\Update\Worker\Update_Worker_Interface;
use Affilicious\Provider\Admin\Options\Amazon_Options;
use Affilicious\Provider\Model\Amazon\Amazon_Provider;
use Affilicious\Provider\Repository\Provider_Repository_Interface;
use Affilicious\Shop\Model\Affiliate_Product_Id;
use Affilicious\Shop\Model\Shop;
use Affilicious\Shop\Repository\Shop_Template_Repository_Interface;
use ApaiIO\ApaiIO;
use ApaiIO\Configuration\GenericConfiguration;
use ApaiIO\Operations\Lookup;
use ApaiIO\Request\GuzzleRequest;
use ApaiIO\ResponseTransformer\XmlToArray;
use GuzzleHttp\Client;

if (!defined('ABSPATH')) {
    exit('Not allowed to access pages directly.');
}

class Amazon_Update_Worker implements Update_Worker_Interface
{
    const NAME = 'amazon';
    const PROVIDER = 'amazon';
    const MAX_BATCH_SIZE = 10;
    const MAX_UPDATES = 10;

    /**
     * @var Product_Repository_Interface
     */
    protected $product_repository;

    /**
     * @var Shop_Template_Repository_Interface
     */
    protected $shop_template_repository;

    /**
     * @var Provider_Repository_Interface
     */
    protected $provider_repository;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @since 0.8
     * @param Product_Repository_Interface $product_repository
     * @param Shop_Template_Repository_Interface $shop_template_repository
     * @param Provider_Repository_Interface $provider_repository
     * @param Logger $logger
     */
    public function __construct(
        Product_Repository_Interface $product_repository,
        Shop_Template_Repository_Interface $shop_template_repository,
        Provider_Repository_Interface $provider_repository,
        Logger $logger
    ) {
        $this->product_repository = $product_repository;
        $this->shop_template_repository = $shop_template_repository;
        $this->provider_repository = $provider_repository;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     * @since 0.8
     */
    public function get_name()
    {
        return self::NAME;
    }

    /**
     * @inheritdoc
     * @since 0.7
     */
    public function configure(Configuration $configuration)
    {
        $configuration->set_all([
            'provider_slug' => self::PROVIDER,
            'max_batch_size' => self::MAX_BATCH_SIZE,
            'max_updates' => self::MAX_UPDATES,
        ]);
    }

    /**
     * @inheritdoc
     * @since 0.7
     */
    public function execute(Batch_Update_Task $batch_update_task, $update_interval)
    {
        // Get the provider containing the credentials.
        $provider = $batch_update_task->get_provider();
        if(!($provider instanceof Amazon_Provider)) {
            return;
        }

        // Get all products for the update.
        $products = $batch_update_task->get_products();
        if(empty($products)) {
            return;
        }

        // Find the affiliate product IDs for the Amazon API batch item lookups.
        $this->logger->debug(sprintf('Trying to find Amazon ASINs for products with IDs: %s (%s)', Value_Helper::implode(', ', Product_Helper::get_all_product_ids($products)), $update_interval));

        $affiliate_product_ids = $this->find_affiliate_product_ids($products, self::MAX_UPDATES);
        if(empty($affiliate_product_ids)) {
            return;
        }

        // Make a Amazon API batch item lookup based on the affiliate IDs. Try again after 3 seconds, if the request has been throttled.
        $this->logger->debug(sprintf('Doing Amazon update request for products with ASINs: %s (%s)', Value_Helper::implode(', ', $affiliate_product_ids), $update_interval));

        $items = $this->batch_item_lookup($provider, $affiliate_product_ids);
        if($items instanceof \WP_Error && $items->get_error_code() == 'aff_product_amazon_request_throttled') {
            $this->logger->alert('Amazon has throttled the update request. Retry after 3 seconds...');

            sleep(3);
            $items = $this->batch_item_lookup($provider, $affiliate_product_ids);
        }

        if(empty($items) || $items instanceof \WP_Error) {
            $this->logger->error(sprintf('Failed to do the Amazon update request (%s). Error: [%s] %s', $update_interval, $items->get_error_code(), $items->get_error_message()));
            return;
        }

        // Apply the updated information's to the products.
        $this->update_products($update_interval, $items, $products);
    }

    /**
     * Find the given number of affiliate product IDs from the products.
     *
     * @since 0.9.8
     * @param Product[] $products The products for the affiliate product ID extraction.
     * @param int $limit The max limit to find.
     * @return Affiliate_Product_Id[]
     */
    protected function find_affiliate_product_ids(array $products, $limit)
    {
        $current = 0;

        $affiliate_product_ids = array();
        foreach ($products as $product) {
            if($product instanceof Complex_Product) {
                $shops = $product->get_default_variant()->get_shops();
            } elseif($product instanceof Shop_Aware_Interface) {
                $shops = $product->get_shops();
            } else {
                continue;
            }

            foreach ($shops as $shop) {
                if($current == $limit) {
                    break;
                }

                if(!$shop->has_template_id()) {
                    continue;
                }

                $shop_template = $this->shop_template_repository->find($shop->get_template_id());
                if($shop_template === null) {
                    continue;
                }

                if(!$shop_template->has_provider_id()) {
                    continue;
                }

                $provider = $this->provider_repository->find($shop_template->get_provider_id());
                if($provider === null) {
                    continue;
                }

                $affiliate_product_id = $shop->get_tracking()->get_affiliate_product_id();
                if($affiliate_product_id === null) {
                    continue;
                }

                if($provider->get_slug()->get_value() === self::PROVIDER) {
                    $affiliate_product_ids[$product->get_id()->get_value()] = $affiliate_product_id;
                    $current++;
                }
            }
        }

        return $affiliate_product_ids;
    }

    /**
     * Make one Amazon API batch item lookup.
     *
     * @since 0.9.8
     * @param Amazon_Provider $provider The Amazon provider which holds the credentials.
     * @param Affiliate_Product_Id[] $affiliate_product_ids The affiliate IDs for the batch item lookup.
     * @return array|\WP_Error The Amazon API response as an array.
     */
    protected function batch_item_lookup(Amazon_Provider $provider, array $affiliate_product_ids)
    {
    	// Convert the affiliate product IDs to unique item IDs.
        $items_ids = array();
        foreach($affiliate_product_ids as $affiliate_product_id) {
            $items_ids[$affiliate_product_id->get_value()] = $affiliate_product_id->get_value();
        }

	    $items_ids = implode(',', $items_ids);

        // Build the Amazon item lookup request.
        $conf = new GenericConfiguration();
        $client = new Client();
        $request = new GuzzleRequest($client);

        $conf
            ->setCountry($provider->get_country()->get_value())
            ->setAccessKey($provider->get_access_key()->get_value())
            ->setSecretKey($provider->get_secret_key()->get_value())
            ->setAssociateTag($provider->get_associate_tag()->get_value())
            ->setRequest($request)
            ->setResponseTransformer(new XmlToArray());

        $lookup = new Lookup();
        $lookup->setItemId($items_ids);
        $lookup->setResponseGroup(['Large']);

        try {
            // Do the Amazon batch item lookup request.
            $apaiIO = new ApaiIO($conf);
            $response = $apaiIO->runOperation($lookup);
        } catch (\Exception $e) {
            if($e->getCode() == 503) {
                return new \WP_Error('aff_product_amazon_request_throttled', __('Amazon has throttled your request speed for a short time.', 'affilicious'));
            } else {
                return new \WP_Error('aff_product_amazon_update_error', $e->getMessage());
            }
        }

        // Map the items to the related product IDs.
	    $results = [];
        $items = $this->find_items($response);
	    foreach ($affiliate_product_ids as $product_id => $affiliate_product_id) {
		    foreach ($items as $item) {
			    if($affiliate_product_id->is_equal_to(Amazon_Helper::find_affiliate_product_id($item))) {
				    $results[$product_id] = $item;
			    }
		    }
	    }

	    return $results;
    }

    /**
     * Update the products with the help of the results.
     *
     * @since 0.9.8
     * @param string $update_interval The current update interval from the cron job.
     * @param array $items The results which can be applied to the products.
     * @param Product[] $products The products where the results can be applied to.
     */
    protected function update_products($update_interval, array $items, array $products)
    {
        foreach ($items as $product_id => $item) {
	        $product_id = new Product_Id($product_id);

	        foreach ($products as $product) {
		        if(!$product_id->is_equal_to($product->get_id())) {
			        continue;
		        }

                $this->logger->debug(sprintf('Applying the new data from the Amazon update request to the product #%s (%s) (%s).', $product_id, $product->get_name(), $update_interval));

		        if($this->should_update_thumbnail($update_interval, $product)) {
			        $this->update_thumbnail($item, $product);
		        }

		        if($this->should_update_image_gallery($update_interval, $product)) {
			        $this->update_image_gallery($item, $product);
		        }

		        if(($product instanceof Shop_Aware_Interface)) {
			        $affiliate_product_id = Amazon_Helper::find_affiliate_product_id($item);
			        if($affiliate_product_id === null) {
				        continue;
			        }

			        $shops = $product->get_shops();
			        foreach($shops as $shop) {
				        if($affiliate_product_id->is_equal_to($shop->get_tracking()->get_affiliate_product_id())) {
					        if($this->should_update_availability($update_interval, $product, $shop)) {
						        $this->update_availability($item, $product, $shop);
					        }

					        if($this->should_update_affiliate_link($update_interval, $product, $shop)) {
						        $this->update_affiliate_link($item, $product, $shop);
					        }

					        if($this->should_update_price($update_interval, $product, $shop)) {
						        $this->update_price($item, $product, $shop);
					        }

					        if($this->should_update_old_price($update_interval, $product, $shop)) {
						        $this->update_old_price($item, $product, $shop);
					        }
				        }
			        }
		        }

		        // Store the updated product.
                $this->logger->debug(sprintf('Storing the updated product #%s (%s) (%s).', $product_id, $product->get_name(), $update_interval));

		        $this->product_repository->store($product);
	        }
        }
    }

    /**
     * Update the product thumbnail.
     *
     * @since 0.9
     * @param array $item The Amazon API response item containing all data.
     * @param Product $product The current product to update.
     */
    protected function update_thumbnail(array $item, Product $product)
    {
	    $thumbnail = Amazon_Helper::find_thumbnail($item);

        do_action('aff_product_amazon_update_worker_before_update_thumbnail', $thumbnail, $product);

        $current_thumbnail = $product->get_thumbnail();
        if($current_thumbnail !== null) {
            Image_Helper::delete($current_thumbnail, true);
        }

	    $thumbnail = apply_filters('aff_product_amazon_update_worker_update_thumbnail', $thumbnail, $product);
        $product->set_thumbnail($thumbnail);
        $product->set_updated_at((new \DateTimeImmutable())->setTimestamp(current_time('timestamp')));

        do_action('aff_product_amazon_update_worker_after_update_thumbnail', $thumbnail, $product);
    }

    /**
     * Update the product image gallery.
     *
     * @since 0.9
     * @param array $item The Amazon API response item containing all data.
     * @param Product $product The current product to update.
     */
    protected function update_image_gallery(array $item, Product $product)
    {
	    $image_gallery = Amazon_Helper::find_image_gallery($item);

        do_action('aff_product_amazon_update_worker_before_update_image_gallery', $image_gallery, $product);

        $current_image_gallery = $product->get_image_gallery();
        foreach ($current_image_gallery as $current_image) {
            Image_Helper::delete($current_image, true);
        }

        $image_gallery = apply_filters('aff_product_amazon_update_worker_update_image_gallery', $image_gallery, $product);
        $product->set_image_gallery($image_gallery);
        $product->set_updated_at((new \DateTimeImmutable())->setTimestamp(current_time('timestamp')));

        do_action('aff_product_amazon_update_worker_after_update_image_gallery', $image_gallery, $product);
    }

    /**
     * Update the shop price in the product.
     *
     * @since 0.9
     * @param array $item The Amazon API response item containing all data.
     * @param Product $product The current product to update.
     * @param Shop $shop The current shop to update.
     */
    protected function update_price(array $item, Product $product, Shop $shop)
    {
	    $price = Amazon_Helper::find_price($item);

        do_action('aff_product_amazon_update_worker_before_update_price', $price, $product, $shop);

        if($shop->get_pricing()->get_availability()->is_out_of_stock()) {
            $price = null;
        }

        $price = apply_filters('aff_product_amazon_update_worker_update_price', $price, $product, $shop);
        $shop->get_pricing()->set_price($price);
        $shop->set_updated_at((new \DateTimeImmutable())->setTimestamp(current_time('timestamp')));
	    $product->set_updated_at((new \DateTimeImmutable())->setTimestamp(current_time('timestamp')));

        do_action('aff_product_amazon_update_worker_after_update_price', $price, $product, $shop);
    }

    /**
     * Update the shop old price in the product.
     *
     * @since 0.9
     * @param array $item The Amazon API response item containing all data.
     * @param Product $product The current product to update.
     * @param Shop $shop The current shop to update.
     */
    protected function update_old_price(array $item, Product $product, Shop $shop)
    {
	    $old_price = Amazon_Helper::find_old_price($item);

        do_action('aff_product_amazon_update_worker_before_update_old_price', $old_price, $product, $shop);

        if($shop->get_pricing()->get_availability()->is_out_of_stock()) {
            $old_price = null;
        }

        $old_price = apply_filters('aff_product_amazon_update_worker_update_old_price', $old_price, $product, $shop);
        $shop->get_pricing()->set_old_price($old_price);
        $shop->set_updated_at((new \DateTimeImmutable())->setTimestamp(current_time('timestamp')));
	    $product->set_updated_at((new \DateTimeImmutable())->setTimestamp(current_time('timestamp')));

        do_action('aff_product_amazon_update_worker_after_update_old_price', $old_price, $product, $shop);
    }

    /**
     * Update the shop availability in the product.
     *
     * @since 0.9
     * @param array $item The Amazon API response item containing all data.
     * @param Product $product The current product to update.
     * @param Shop $shop The current shop to update.
     */
    protected function update_availability(array $item, Product $product, Shop $shop)
    {
	    $availability = Amazon_Helper::find_availability($item);

        do_action('aff_product_amazon_update_worker_before_update_availability', $availability, $product, $shop);

        $availability = apply_filters('aff_product_amazon_update_worker_update_availability', $availability, $product, $shop);
        $shop->get_pricing()->set_availability($availability);
        $shop->set_updated_at((new \DateTimeImmutable())->setTimestamp(current_time('timestamp')));
	    $product->set_updated_at((new \DateTimeImmutable())->setTimestamp(current_time('timestamp')));

        do_action('aff_product_amazon_update_worker_before_update_availability', $availability, $product, $shop);
    }

	/**
	 * Update the shop affiliate link in the product.
	 *
	 * @since 0.9.8
	 * @param array $item The Amazon API response item containing all data.
	 * @param Product $product
	 * @param Shop $shop
	 */
    protected function update_affiliate_link(array $item, Product $product, Shop $shop)
    {
	    $affiliate_link = Amazon_Helper::find_affiliate_link($item);

	    do_action('aff_product_amazon_update_worker_before_update_affiliate_link', $affiliate_link, $product, $shop);

	    $affiliate_link = apply_filters('aff_product_amazon_update_worker_update_affiliate_link', $affiliate_link, $product, $shop);

	    if($affiliate_link !== null) {
		    $shop->get_tracking()->set_affiliate_link( $affiliate_link );
	    }

	    $shop->set_updated_at((new \DateTimeImmutable())->setTimestamp(current_time('timestamp')));
	    $product->set_updated_at((new \DateTimeImmutable())->setTimestamp(current_time('timestamp')));

	    do_action('aff_product_amazon_update_worker_before_update_affiliate_link', $affiliate_link, $product, $shop);
    }

    /**
     * Check if we have to update the product thumbnails.
     *
     * @since 0.9
     * @param string $update_interval The current update interval from the cron job.
     * @param Product $product The current product to update.
     * @return bool Whether to update the thumbnail or not.
     */
    protected function should_update_thumbnail($update_interval, Product $product)
    {
        $thumbnail_update_interval = carbon_get_theme_option(Amazon_Options::THUMBNAIL_UPDATE_INTERVAL);
        $should_update = $this->should_update($update_interval, $thumbnail_update_interval);
        $should_update = apply_filters('aff_product_amazon_update_worker_should_update_thumbnail', $should_update, $update_interval, $product);

        return $should_update;
    }

    /**
     * Check if we have to update the product image galleries.
     *
     * @since 0.9
     * @param string $update_interval The current update interval from the cron job.
     * @param Product $product The current product to update.
     * @return bool Whether to update the image gallery or not.
     */
    protected function should_update_image_gallery($update_interval, Product $product)
    {
        $image_gallery_update_interval = carbon_get_theme_option(Amazon_Options::IMAGE_GALLERY_UPDATE_INTERVAL);
        $should_update = $this->should_update($update_interval, $image_gallery_update_interval);
        $should_update = apply_filters('aff_product_amazon_update_worker_should_update_image_gallery', $should_update, $update_interval, $product);

        return $should_update;
    }

	/**
	 * Check if we have to update the shop affiliate links in the products.
	 *
	 * @since 0.9.8
	 * @param string $update_interval The current update interval from the cron job.
	 * @param Product $product The current product to update.
	 * @param Shop $shop The current shop to update.
	 * @return bool Whether to update the shop price or not.
	 */
	protected function should_update_affiliate_link($update_interval, Product $product, Shop $shop)
	{
		$price_update_interval = carbon_get_theme_option(Amazon_Options::AFFILIATE_LINK_UPDATE_INTERVAL);
		$should_update = $this->should_update($update_interval, $price_update_interval);
		$should_update = apply_filters('aff_product_amazon_update_worker_should_update_affiliate_link', $should_update, $update_interval, $product, $shop);

		return $should_update;
	}

    /**
     * Check if we have to update the shop prices in the products.
     *
     * @since 0.7
     * @param string $update_interval The current update interval from the cron job.
     * @param Product $product The current product to update.
     * @param Shop $shop The current shop to update.
     * @return bool Whether to update the shop price or not.
     */
    protected function should_update_price($update_interval, Product $product, Shop $shop)
    {
        $price_update_interval = carbon_get_theme_option(Amazon_Options::PRICE_UPDATE_INTERVAL);
        $should_update = $this->should_update($update_interval, $price_update_interval);
        $should_update = apply_filters('aff_product_amazon_update_worker_should_update_price', $should_update, $update_interval, $product, $shop);

        return $should_update;
    }

    /**
     * Check if we have to update the old shop prices in the products.
     *
     * @since 0.7
     * @param string $update_interval The current update interval from the cron job.
     * @param Product $product The current product to update.
     * @param Shop $shop The current shop to update.
     * @return bool Whether to update the old shop price or not.
     */
    protected function should_update_old_price($update_interval, Product $product, Shop $shop)
    {
        $old_price_update_interval = carbon_get_theme_option(Amazon_Options::OLD_PRICE_UPDATE_INTERVAL);
        $should_update = $this->should_update($update_interval, $old_price_update_interval);
        $should_update = apply_filters('aff_product_amazon_update_worker_should_update_old_price', $should_update, $update_interval, $product, $shop);

        return $should_update;
    }

    /**
     * Check if we have to update the shop availabilities in the products.
     *
     * @since 0.7
     * @param string $update_interval The current update interval from the cron job.
     * @param Product $product The current product to update.
     * @param Shop $shop The current shop to update.
     * @return bool Whether to update the shop availability or not.
     */
    protected function should_update_availability($update_interval, Product $product, Shop $shop)
    {
        $availability_update_interval = carbon_get_theme_option(Amazon_Options::AVAILABILITY_UPDATE_INTERVAL);
        $should_update = $this->should_update($update_interval, $availability_update_interval);
        $should_update = apply_filters('aff_product_amazon_update_worker_should_update_availability', $should_update, $update_interval, $product, $shop);

        return $should_update;
    }

    /**
     * Check if the update interval is should be active.
     *
     * @since 0.9
     * @param string $current_update_interval The current update interval from the cron job.
     * @param bool|string $check_update_interval The update interval to check.
     * @return bool Whether to update or not.
     */
    protected function should_update($current_update_interval, $check_update_interval)
    {
        if($check_update_interval === false) {
            $check_update_interval = Update_Timer::HOURLY;
        }

        if(empty($check_update_interval) || $check_update_interval === 'none') {
            return false;
        }

        return $check_update_interval == $current_update_interval;
    }

    /**
     * Find the items in the Amazon API response.
     *
     * @since 0.7
     * @param array $response The Amazon API response containing the product items.
     * @return array The items from the Amazon API response containing the current product information.
     */
    protected function find_items($response)
    {
        $items = array();

        if(isset($response['Items']['Item'])) {
            $item = $response['Items']['Item'];

            // Request contains multiple responses
            if(isset($item[0])) {
                $items = $item;
            }

            // Request contains a single response
            if(isset($item['ASIN'])) {
                $items[] = $item;
            }
        }

        return $items;
    }
}
