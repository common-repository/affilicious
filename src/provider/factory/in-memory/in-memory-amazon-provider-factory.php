<?php
namespace Affilicious\Provider\Factory\In_Memory;

use Affilicious\Common\Model\Name;
use Affilicious\Common\Model\Slug;
use Affilicious\Common\Generator\Slug_Generator_Interface;
use Affilicious\Provider\Factory\Amazon_Provider_Factory_Interface;
use Affilicious\Provider\Model\Amazon\Amazon_Provider;
use Affilicious\Provider\Model\Credentials;

if (!defined('ABSPATH')) {
    exit('Not allowed to access pages directly.');
}

class In_Memory_Amazon_Provider_Factory implements Amazon_Provider_Factory_Interface
{
    /**
     * The slug generator is responsible to auto-generating slugs.
     *
     * @var Slug_Generator_Interface
     */
    protected $slug_generator;

    /**
     * @since 0.8
     * @param Slug_Generator_Interface $slug_generator
     */
    public function __construct(Slug_Generator_Interface $slug_generator)
    {
        $this->slug_generator = $slug_generator;
    }

    /**
     * @inheritdoc
     * @since 0.8
     */
    public function create(Name $title, Slug $name, Credentials $credentials)
    {
        do_action('aff_amazon_provider_factory_before_create');
        do_action('aff_provider_factory_before_create');

        $amazon_provider = new Amazon_Provider($title, $name, $credentials);
        $amazon_provider = apply_filters('aff_amazon_provider_factory_create', $amazon_provider);
        $amazon_provider = apply_filters('aff_provider_factory_create', $amazon_provider);

        do_action('aff_amazon_provider_factory_after_create', $amazon_provider);
        do_action('aff_provider_factory_after_create', $amazon_provider);

        return $amazon_provider;
    }

    /**
     * @inheritdoc
     * @since 0.8
     */
    public function create_from_name(Name $name, Credentials $credentials)
    {
        $amazon_provider = $this->create(
            $name,
            $this->slug_generator->generate_from_name($name),
            $credentials
        );

        return $amazon_provider;
    }
}
