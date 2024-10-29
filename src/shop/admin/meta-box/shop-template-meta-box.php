<?php
namespace Affilicious\Shop\Admin\Meta_Box;

use Affilicious\Provider\Repository\Provider_Repository_Interface;
use Affilicious\Shop\Model\Price_Indication;
use Affilicious\Shop\Model\Shop_Template;
use Affilicious\Shop\Repository\Carbon\Carbon_Shop_Template_Repository;
use Carbon_Fields\Container as Carbon_Container;
use Carbon_Fields\Field as Carbon_Field;

if (!defined('ABSPATH')) {
	exit('Not allowed to access pages directly.');
}

/**
 * @since 0.9
 */
class Shop_Template_Meta_Box
{
    /**
     * @since 0.9
     * @var Provider_Repository_Interface
     */
    protected $provider_repository;

    /**
     * @since 0.9
     * @param Provider_Repository_Interface $provider_repository
     */
    public function __construct(Provider_Repository_Interface $provider_repository)
    {
        $this->provider_repository = $provider_repository;
    }

	/**
	 * Render the shop template taxonomy meta box.
	 *
	 * @hook init
     * @since 0.9
	 */
	public function render()
	{
        do_action('aff_admin_meta_box_before_render_shop_template_container');

        $container = Carbon_Container::make('term_meta', __('Shop Template', 'affilicious'))
            ->show_on_taxonomy(Shop_Template::TAXONOMY)
            ->add_fields($this->get_fields());

        $container = apply_filters('aff_admin_meta_box_before_shop_template_container', $container);

        do_action('aff_admin_meta_box_after_render_shop_template_container', $container);
	}

	/**
	 * Get the shop template fields.
	 *
	 * @since 0.9.2
	 * @return Carbon_Field[] The list of fields.
	 */
	protected function get_fields()
	{
		$fields = array(
			Carbon_Field::make('select', Carbon_Shop_Template_Repository::PROVIDER, __('Provider', 'affilicious'))
	            ->set_required(true)
	            ->add_options($this->get_provider_options())
	            ->set_help_text(__('The provider is used for the automatic updates for products using this shop.', 'affilicious')),
			Carbon_Field::make('image', Carbon_Shop_Template_Repository::THUMBNAIL_ID, __('Logo', 'affilicious'))
	            ->set_help_text(__('The logo is used to show an image near the shop prices in products.', 'affilicious')),
			Carbon_Field::make('text', Carbon_Shop_Template_Repository::PRICE_INDICATION, __('Price indication', 'affilicious'))
				->set_required(true)
				->set_default_value(Price_Indication::standard()->get_value())
	            ->set_help_text(__('The price indication is used with the price.', 'affilicious')),
		);

		$fields = apply_filters('aff_admin_meta_box_before_shop_template_container_fields', $fields);

		return $fields;
	}

    /**
     * Get the options for the provider choice.
     *
     * @since 0.9
     * @return array The list of available provider options.
     */
	protected function get_provider_options()
    {
        $providers = $this->provider_repository->find_all();

        $options = array('none' => __('None', 'affilicious'));
        foreach ($providers as $provider) {
            if ($provider->has_id()) {
                $options[$provider->get_id()->get_value()] = $provider->get_name()->get_value();
            }
        }

        $options = apply_filters('aff_admin_meta_box_shop_template_provider_options', $options, $providers);

        return $options;
    }
}
