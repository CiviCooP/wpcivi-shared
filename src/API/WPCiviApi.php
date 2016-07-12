<?php
namespace WPCivi\Shared\API;

/**
 * Class API\WPCiviApi
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

    /** Some calls are cached for this request */
    private $customGroupCache = [];
    private $customFieldsCache = [];

    /** Pseudo constants */
    const LOCATION_TYPE_HOME = 1;
    const LOCATION_TYPE_WORK = 2;
    const COUNTRY_CODE_NL = 1152;
    const SYSTEM_CONTACT_ID = 4;

    /**
     * Get instance
     * @return static|bool Instance or false
     */
    public static function getInstance()
    {
        if (!static::$instance) {
            static::$instance = new static;
        }

        static::$instance->initialize();

        return static::$instance;
    }

    /**
     * Initialize CiviCRM API.
     * If this is a local installation, call civicrm_initialize(). Otherwise, initalize \civicrm_api3.
     * @return bool Success
     */
    public function initialize()
    {
        if (function_exists('civicrm_initialize')) {

            if (!civicrm_initialize()) {
                return new \WP_Error('civiapi_error', 'Could not initialize CiviCRM.');
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

        return true;
    }

    /**
     * Call the CiviCRM API class. We're using the civicrm_api3 class because it allows both local and remote requests.
     * Apparently, the call() method was set as private recently... so we're imitating it now.
     * @param string $entity Entity
     * @param string $action Action
     * @param mixed $params Parameters
     * @return \StdClass API result
     */
    public function api($entity, $action, $params)
    {
        $ret = $this->apiClass->{$entity}->{$action}($params);
        if (!$ret) {
            return false;
        }

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
     * Find a custom field ID by name
     * @param string $groupName CustomGroup name
     * @param string $fieldName CustomField name
     * @return int CustomField id
     * @throws \CiviCRM_API3_Exception Exception
     */
    public function getCustomFieldId($groupName, $fieldName)
    {
        $cacheKey = $groupName . '_' . $fieldName;
        if (array_key_exists($cacheKey, $this->customFieldsCache)) {
            return $this->customFieldsCache[$cacheKey];
        }

        $groupId = $this->getCustomGroupId($groupName);
        $fieldId = $this->api('CustomField', 'getvalue', [
            'group_id' => $groupId,
            'name'     => $fieldName,
            'return'   => 'id',
        ]);

        $this->customFieldsCache[$cacheKey] = $fieldId;

        return $fieldId;
    }

    /**
     * Find a custom group ID by name
     * @param string $groupName CustomGroup name
     * @return int CustomGroup id
     * @throws \CiviCRM_API3_Exception Exception
     */
    public function getCustomGroupId($groupName)
    {
        if (array_key_exists($groupName, $this->customGroupCache)) {
            return $this->customGroupCache[$groupName];
        };

        $groupId = $this->api('CustomGroup', 'getvalue', ['name' => $groupName, 'return' => 'id']);
        $this->customGroupCache[$groupName] = $groupId;

        return $groupId;
    }

    /**
     * Return relationship type id by name_a_b
     * @param string $name_a_b Name_A_B
     * @return int|bool Relationship Type ID or false
     */
    public function getRelationshipTypeIdByNameAB($name_a_b)
    {
        try {
            $result = $this->api('RelationshipType', 'getsingle', ['name_a_b' => $name_a_b]);
            return $result['id'];
        } catch (\CiviCRM_API3_Exception $e) {
            return false;
        }
    }

    /**
     * Return autocomplete data for contact lookups on custom forms, taking ACLs into account
     * @param string $string Search string
     * @return string JSON output
     */
    public function getContactAutoCompleteData($string = '')
    {
        // $session = \CRM_Core_Session::singleton();
        list($aclFrom, $aclWhere) = \CRM_Contact_BAO_Contact_Permission::cacheClause('contact_a');

        $params = [];
        $sql = "SELECT contact_a.id, contact_a.display_name
          FROM civicrm_contact contact_a
          {$aclFrom}
          WHERE contact_a.contact_type = 'Individual' AND contact_a.is_deleted = 0 AND contact_a.is_deceased = 0 AND {$aclWhere}
          ";
        if (!empty($string)) {
            $sql .= " AND (contact_a.display_name LIKE %1 OR contact_a.sort_name LIKE %1 OR CONVERT(contact_a.id, CHAR) LIKE %1)";
            $params[1] = ['%' . $string . '%', 'String'];
        }
        $sql .= " ORDER BY contact_a.sort_name LIMIT 0,10";
        $return = [];
        $dao = \CRM_Core_DAO::executeQuery($sql, $params);
        while ($dao->fetch()) {
            $name = $dao->display_name . " (id: " . $dao->id . ")";
            $return[$dao->id] = $name;
        }

        return $return;
    }

    /**
     * Wrapper around the API create method that checks if the entity was created correctly
     * @param string $entity Entity Name
     * @param array $params Contact Parameters
     * @return object Contact or API result
     * @throws \Exception If an entity could not be added
     */
    public function create($entity, $params)
    {
        $ret = $this->api($entity, 'create', $params);

        if (!$ret || $ret->is_error ||
            (in_array($entity, ['Contact', 'Address', 'Phone', 'Email']) && empty($ret->id))
        ) {
            throw new WPCiviException('CiviCRM API error: could not add ' . $entity . '. ' . $ret->error_message);
        }
        return $ret;
    }

    /**
     * Wrapper to add custom fields for a contact, without having to lookup all field names manually
     * @param int $contactId Contact ID
     * @param array $params Array of arrays containing field group, field name, and the value we should set
     * @return object API result
     * @throws \Exception If the custom data could not be added
     */
    public function addCustomData($contactId, $params)
    {
        $apiParams = ['contact_id' => $contactId];

        foreach ($params as $param) {
            if (count($param) < 3) {
                throw new WPCiviException('Invalid addCustomData parameters: ' . print_r($params, true));
            }

            $key = $this->getCustomFieldId($param[0], $param[1]);
            if (!$key) {
                throw new WPCiviException('CiviCRM API error: custom field ' . $param[0] . ' -> ' . $param[1] . ' does not exist.');
            }

            $apiParams['custom_' . $key] = $param[2];
        }

        $ret = $this->api('Contact', 'create', $apiParams);

        if (!$ret || $ret->is_error || empty($ret->id)) {
            throw new WPCiviException('CiviCRM API error: could not set custom data for contact ' . $contactId . '. ' . $ret->error_message);
        }
        return $ret;
    }
}