<?php
namespace Affilicious\Provider\Model\Amazon;

use Affilicious\Common\Helper\Assert_Helper;
use Affilicious\Common\Model\Name;
use Affilicious\Common\Model\Slug;
use Affilicious\Provider\Model\Credentials;
use Affilicious\Provider\Model\Provider;
use Affilicious\Provider\Model\Type;

if (!defined('ABSPATH')) {
    exit('Not allowed to access pages directly.');
}

class Amazon_Provider extends Provider
{
	const NAME = 'Amazon';
	const SLUG = 'amazon';
	const TYPE = 'amazon';
    const ACCESS_KEY = 'access_key';
    const SECRET_KEY = 'secret_key';
    const COUNTRY = 'country';
    const ASSOCIATE_TAG = 'associate_tag';

	/**
	 * Get the Amazon provider name.
	 *
	 * @since 0.9.4
	 * @return Name
	 */
    public static function name()
    {
    	return new Name(self::NAME);
    }

	/**
	 * Get the Amazon provider slug.
	 *
	 * @since 0.9.4
	 * @return Slug
	 */
    public static function slug()
    {
    	return new Slug(self::SLUG);
    }

	/**
	 * Get the Amazon provider type.
	 *
	 * @since 0.9.7
	 * @return Type
	 */
	public static function type()
	{
		return new Type(self::TYPE);
	}

    /**
     * @var Access_Key
     */
    protected $access_key;

    /**
     * @var Secret_Key
     */
    protected $secret_key;

    /**
     * @var Country
     */
    protected $country;

    /**
     * @var Associate_Tag
     */
    protected $associate_tag;

    /**
     * @inheritdoc
     * @since 0.8
     */
    public function __construct(Name $name, Slug $slug, Credentials $credentials)
    {
	    Assert_Helper::key_exists($credentials->get_value(), self::ACCESS_KEY, __METHOD__, 'The access key ID for the Amazon provider is missing.', '0.9.2');
	    Assert_Helper::key_exists($credentials->get_value(), self::SECRET_KEY, __METHOD__, 'The secret access key for the Amazon provider is missing.', '0.9.2');
	    Assert_Helper::key_exists($credentials->get_value(), self::COUNTRY, __METHOD__, 'The country for the Amazon provider is missing.', '0.9.2');
	    Assert_Helper::key_exists($credentials->get_value(), self::ASSOCIATE_TAG, __METHOD__, 'The associate tag for the Amazon provider is missing.', '0.9.2');

        parent::__construct($name, $slug, self::type(), $credentials);
        $this->access_key = new Access_Key($credentials->get(self::ACCESS_KEY));
        $this->secret_key = new Secret_Key($credentials->get(self::SECRET_KEY));
        $this->country = new Country($credentials->get(self::COUNTRY));
        $this->associate_tag = new Associate_Tag($credentials->get(self::ASSOCIATE_TAG));
    }

    /**
     * Get the Amazon access key id from the credentials.
     *
     * @since 0.8
     * @return Access_Key
     */
    public function get_access_key()
    {
        return $this->access_key;
    }

    /**
     * Get the Amazon secret access key from the credentials.
     *
     * @since 0.8
     * @return Secret_Key
     */
    public function get_secret_key()
    {
        return $this->secret_key;
    }

    /**
     * Get the Amazon country code from the credentials.
     *
     * @since 0.8
     * @return Country
     */
    public function get_country()
    {
        return $this->country;
    }

    /**
     * Get the Amazon partner tag from the credentials.
     *
     * @since 0.8
     * @return Associate_Tag
     */
    public function get_associate_tag()
    {
        return $this->associate_tag;
    }
}
