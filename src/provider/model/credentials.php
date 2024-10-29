<?php
namespace Affilicious\Provider\Model;

use Affilicious\Common\Helper\Assert_Helper;
use Affilicious\Common\Model\Simple_Value_Trait;

if (!defined('ABSPATH')) {
    exit('Not allowed to access pages directly.');
}

/**
 * @since 0.8
 */
class Credentials
{
    use Simple_Value_Trait {
        Simple_Value_Trait::__construct as private set_value;
    }

    /**
     * @inheritdoc
     * @since 0.8
     */
    public function __construct($value)
    {
        Assert_Helper::is_array($value, __METHOD__, 'Expected credentials to be an array. Got: %s', '0.9.2');

        $this->set_value($value);
    }

    /**
     * Get the part of the credentials by the key.
     *
     * @since 0.8
     * @param string $key
     * @return null|string
     */
    public function get($key)
    {
        return isset($this->value[$key]) ? $this->value[$key] : null;
    }

	/**
	 * Check if the credentials contains a specific part by the key.
	 *
	 * @since 0.9.1
	 * @param string $key
	 * @return bool
	 */
    public function has($key)
    {
	    return isset($this->value[$key]);
    }
}
