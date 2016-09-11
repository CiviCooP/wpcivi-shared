<?php
namespace WPCivi\Shared\Civi;

/**
 * Class Civi\WPCiviApi. (Mag ook best nog een keer 'Api' gaan heten.)
 * CiviCRM API Wrapper, intended to work both locally and remotely, as long as users use $this->api().
 * Roughly the Wordpress equivalent of https://github.com/SPnl/nl.sp.drupal-civiapi.
 * @package WPCivi\Shared
 */
class WPCiviApi
{
    /** @var static $instance */
    protected static $instance;

    /** @var \civicrm_api3 $apiClass CiviCRM API class */
    protected $apiClass;

    /**
     * Get instance
     * @return WPCiviApi|bool Instance or false
     */
    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self;
            self::$instance->initialize();
        }

        return self::$instance;
    }

    /**
     * Initialize CiviCRM API.
     * If this is a local installation, call civicrm_initialize(). Otherwise, initalize \civicrm_api3.
     * @throws WPCiviException If CiviCRM could not be initialised.
     */
    public function initialize()
    {
        if (function_exists('civicrm_initialize')) {

            if (!civicrm_initialize()) {
                throw new WPCiviException("Could not initialize CiviCRM in WPCiviApi::initialize!");
            }

            require_once 'CiviCRM_API3.php';
            $this->apiClass = new \civicrm_api3;

        } else {

            require_once 'CiviCRM_API3.php';
            $this->apiClass = new \civicrm_api3([
                'server'  => get_option('civiapi_civicrm_server', 'https://localhost/'),
                'path'    => get_option('civiapi_civicrm_path', '/wp-content/plugins/civicrm/civicrm/extern/rest.php'),
                'key'     => get_option('civiapi_civicrm_key'),
                'api_key' => get_option('civiapi_civicrm_userkey'),
            ]);
        }
    }

    /**
     * Call the CiviCRM API class. We're using the civicrm_api3 class because it allows both local and remote requests.
     * Apparently, the call() method was set as private recently... so we're imitating it now.
     * @param string $entity Entity
     * @param string $action Action
     * @param mixed $params Parameters
     * @return \StdClass|array API result
     */
    public function api($entity, $action, $params)
    {
        $ret = $this->apiClass->{$entity}->{$action}($params);
        return $this->apiClass->result();
    }

    /**
     * Get the CiviCRM API class. (Fix + didn't notice more modules used this function)
     * @return \civicrm_api3 API class
     */
    public function getApi()
    {
        return $this->apiClass;
    }

    /**
     * Call the CiviCRM API class. Static version of $this->api.
     * @param string $entity Entity
     * @param string $action Action
     * @param mixed $params Parameters
     * @return \StdClass|array API result
     */
    public static function call($entity, $action, $params)
    {
        return static::getInstance()->api($entity, $action, $params);
    }

}