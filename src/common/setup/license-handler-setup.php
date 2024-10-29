<?php
namespace Affilicious\Common\Setup;

use Affilicious\Common\License\License_Manager;
use Webmozart\Assert\Assert;

if (!defined('ABSPATH')) {
    exit('Not allowed to access pages directly.');
}

class License_Handler_Setup
{
    /**
     * @var License_Manager
     */
    private $license_manager;

    /**
     * @since 0.8.12
     * @param License_Manager $license_manager
     */
    public function __construct(License_Manager $license_manager)
    {
        $this->license_manager = $license_manager;
    }

    /**
     * Make all license handlers available in Affilicious.
     *
     * @hook
     * @since 0.8.12
     */
    public function init()
    {
        do_action('aff_license_handler_before_init');

        $license_handlers = apply_filters('aff_license_handler_init', array());
        Assert::isArray($license_handlers, 'Expected the license handlers to be an array. Got: %s');

        foreach ($license_handlers as $license_handler) {
            $this->license_manager->add_license_handler($license_handler);
        }

        do_action('aff_license_handler_after_init', $license_handlers);
    }
}
