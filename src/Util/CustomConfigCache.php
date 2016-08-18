<?php
namespace WPCivi\Shared\Util;

use WPCivi\Shared\Civi\WPCiviApi;
use WPCivi\Shared\Civi\WPCiviException;

/**
 * Class Util\CustomConfigCache.
 * Static functions to quickly get some config or id we need and we can cache.
 * @package WPCivi\Shared
 */
class CustomConfigCache
{

    /**
     * @var static $instance
     */
    private static $instance;

    /**
     * @var WPCiviApi $wpcivi
     */
    private $wpcivi;

    /**
     * @var array $cache Custom Config Cache
     */
    private $cache = [];

    /**
     * Get class instance.
     * @return self
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * CustomConfigCache constructor.
     */
    public function __construct()
    {
        $this->wpcivi = WPCiviApi::getInstance();
    }

    /**
     * Get default location type id.
     * @return int|null
     */
    public function getDefaultLocationTypeId()
    {
        if(!isset($this->cache['location_type_default_id'])) {
            $ltParams = ['is_default' => 1, 'return' => 'id'];
            $this->cache['location_type_default_id'] = $this->wpcivi->api('LocationType', 'getvalue', $ltParams);
        }
        return $this->cache['location_type_default_id'];
    }

    /**
     * Get default country id (the API gets the country code too, so that's easy).
     * @return string;
     */
    public function getDefaultCountryId()
    {
        return 'NL';
    }

}