<?php
namespace WPCivi\Shared;

use WPCivi\Shared\Civi\WPCiviApi;
use WPCivi\Shared\Civi\WPCiviException;
use WPCivi\Shared\Util\CustomDataCache;
use WPCivi\Shared\Util\DatastoreTrait;

/**
 * Base Entity class. This class is extended by all Entity\* classes, but could also work independently.
 * A single entity class is a _representation in WordPress_ of a single _CiviCRM Entity_ (one contact, one membership, etc).
 * The entity class may also provide static functions to get collections of entities (@see EntityCollection)
 * @package WPCivi\Shared
 *
 * @property int|null $id
 * @property string|null $name
 */
class Entity implements \ArrayAccess
{

    use DatastoreTrait;

    /**
     * @var string $entityType CiviCRM Entity Name
     */
    protected $entityType;

    /**
     * @var \stdClass $data Data Class
     */
    protected $data = null;

    /**
     * @var array $cache Internal static cache
     */
    protected static $cache = [];

    /**
     * @var bool $customDataFetched Has custom data been fetched for this entity
     */
    protected $customDataFetched = false;

    /**
     * @var string[] $fields Entity fields information, per action
     */
    protected $fields = [];

    /**
     * @var mixed[] $fieldMapping Custom fields mapping
     */
    protected $fieldMapping;

    /**
     * Contact constructor.
     * @param int|null $id Entity ID
     * @param string $entityType Entity Type
     * @throws WPCiviException If it gets too confusing
     */
    public function __construct($id = null, $entityType = null)
    {
        // Load entity data or load defaults if possible and $id is set
        if (isset($id)) {
            $this->load($id);
        } else {
            $this->setDefaults();
        }

        // Entity type is set by entity classes: uses this if an entity class doesn't exist yet
        if (empty($this->entityType)) {
            if(!empty($entityType)) {
                $this->entityType = $entityType;
            } else {
                throw new WPCiviException('Entity type not configured for class ' . get_class() . '!');
            }
        }
    }

    /**
     * Function to load an entity (= getsingle).
     * This will probably be overloaded often by entities themselves.
     * @param int $id Entity ID
     * @return int Entity ID on success
     * @throws WPCiviException|\CiviCRM_API3_Exception Thrown if entity not found
     */
    public function load($id)
    {
        $this->data = WPCiviApi::call($this->entityType, 'getsingle', ['id' => $id]);
        if (!$this->data || (isset($this->data->is_error) && $this->data->is_error == true)) {
            throw new WPCiviException("Could not load entity ID {$id} of type {$this->entityType}!");
        }
        return $this->id;
    }

    /**
     * Function to load a single entity (= getsingle) based on parameters.
     * The parameters should result in 1 record for getsingle!
     * @param array $params Params
     * @return bool Success
     * @throws WPCiviException|\CiviCRM_API3_Exception Thrown if entity not found
     */
    public function loadBy($params = [])
    {
        $this->data = WPCiviApi::call($this->entityType, 'getsingle', $params);
        if (!$this->data || (!empty($this->data->is_error) && $this->data->is_error == true)) {
            $error_msg = (!empty($this->data->error_msg) ? $this->data->error_msg : 'Unknown error');
            $this->clear();
            throw new WPCiviException("Could not load entity of type {$this->entityType} with custom params! ({$error_msg})");
        }
        return $this->id;
    }

    /**
     * Function to fetch custom data for a single entity using CustomValue.get and store it in the current entity object.
     * Called by $this->getCustom() if a custom value key is not set yet.
     */
    public function fetchEntityCustomData()
    {
        if(!isset($this->id)) {
            throw new WPCiviException("Could not fetch custom data for entity of type {$this->entityType}: id is not set.");
        }

        if($this->customDataFetched == true) {
            return null;
        }

        $customData = WPCiviApi::call('CustomValue', 'get', [
            'entity_id' => $this->id,
            'entity_table' => $this->entityTable(),
        ]);

        if(!empty($customData) && !empty($customData->values)) {
            foreach($customData->values as $v) {
                $key = 'custom_' . $v->id;
                $this->$key = $v->latest;
            }
        }

        $this->customDataFetched = true;
    }

    /**
     * Function to pass default values if a new entity is created.
     * See the individual entity classes for how this is used.
     */
    protected function setDefaults() {
        $this->data = new \stdClass;
    }

    /**
     * Function to save an entity (= create / update).
     * This will probably also be overloaded often for additional processing.
     * @param bool $reload Reload current class after creation/processing
     * @param string $saveMethod API method to call on create (default: 'create')
     * @return bool Success
     * @throws WPCiviException If the entity can't be saved.
     */
    public function save($reload = true, $saveMethod = 'create')
    {
        if (empty($this->data)) {
            throw new WPCiviException('Could not save entity: it currently doesn\'t contain data.');
        }

        // Check if all field names are valid and omit get-only and internal fields - custom fields aren't checked for now
        $createData = [];
        $createFields = $this->getFields('create');

        // QUICK HACK for Activity fields -> TODO Activity.GetFields does not return fields specified in _spec() !?
        $extraFields = ['source_contact_id','target_contact_id','case_id'];

        foreach($this->data as $key => $value) {
            if(strpos($key, 'custom_') === 0 || array_key_exists($key, $createFields) || in_array($key, $extraFields)) {
                $createData[$key] = $value;
            }
        }

        // Try to save data
        // print_r($createData);
        $ret = WPCiviApi::call($this->entityType, $saveMethod, $createData);
        // print_r($ret);

        if (!isset($ret) || $ret->is_error) {
            throw new WPCiviException('Could not save ' . $this->entityType . ': ' . (int)$this->id . '. (' . $ret->error_message . ')');
        }

        if (isset($ret->id) && empty($this->id)) {
            $this->id = $ret->id;
        }

        if($reload == true) {
            $this->reload();
        }
        return true;
    }

    /**
     * Get this entity's table name (provisional)
     * @return string Table Name
     */
    private function entityTable()
    {
        if($this->entityType == 'Case') {
            return 'civicrm_case';
        } else {
            return 'civicrm_' . strtolower($this->entityType);
        }
    }

    /**
     * Static class to add a new entity - and save and return it, if possible.
     * If $params is empty, this class will just be instantiated. If it is an array,
     * we'll do an API request to save it and return the new entity class.
     * @param array $params Entity Parameters
     * @param bool $returnEntity Return entity after save?
     * @param string $saveMethod Save method to call on create (default: 'create')
     * @return static Entity or API result
     * @throws WPCiviException If an entity could not be added
     */
    public static function create($params = null, $returnEntity = true, $saveMethod = 'create')
    {
        if (empty($params)) {
            return new static;
        } else {
            $entity = new static;
            $entity->setArray($params);

            $ret = $entity->save(true, $saveMethod);
            return ($returnEntity == true ? $entity : $ret);
        }
    }

    /**
     * Reload existing entity data
     * @return bool Success
     */
    public function reload()
    {
        if (!empty($this->id)) {
            return $this->load($this->id);
        }
        return false;
    }

    /**
     * Get an array of fields that this entity type can get/create/set.
     * Not that this doesn't include any custom data fields.
     * @param string $action API action ('get', 'create', ...')
     * @param bool $reload Reload entity first?
     * @return array Field Return with Field Entity
     */
    public function getFields($action = 'get', $reload = false)
    {
        if(empty($this->fields[$action]) || $reload == true) {

            $customDataCache = CustomDataCache::getInstance();

            $fields = WPCiviApi::call($this->entityType, 'getfields', ['options' => ['limit' => 9999]]);
            $this->fields[$action] = [];

            // Only set/get a subset of data for fields, and add custom field information where available
            foreach ($fields->values as $field) {
                $newField = new \stdClass;
                $newField->label = (isset($field->title) ? $field->title : $field->label);
                $newField->name = $field->name;
                $newField->description = (isset($field->description) ? $field->description : null);
                $newField->is_custom = false;

                if (strpos($field->name, 'custom_') === 0) {
                    $fieldId = str_ireplace('custom_', '', $field->name);

                    $newField->is_custom = true;
                    $newField->custom_field_id = $fieldId;
                    $newField->custom_group_id = $field->custom_group_id;
                    $newField->table_name = $field->table_name;
                    $newField->column_name = $field->column_name;

                    $customField = $customDataCache->getFieldByIds($field->custom_group_id, $fieldId);
                    if(!empty($customField)) {
                        $newField->custom_group_name = $customField->custom_group_name;
                        $newField->api_field_name = $field->name;
                        $newField->name = $customField->name;
                    }
                }

                $this->fields[$action][$newField->name] = $newField;
            }
        }

        return $this->fields[$action];
    }

    /**
     * @return int|null Entity ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get a single property's value
     * @param mixed $key Key
     * @return mixed Value
     */
    public function getValue($key)
    {
        return $this->__get($key);
    }

    /**
     * Set a single property's value
     * @param mixed $key Key
     * @param mixed $value Value
     */
    public function setValue($key, $value)
    {
        $this->__set($key, $value);
    }

    /**
     * Clear a single property's value
     * @param mixed $key Key
     */
    public function clearValue($key)
    {
        $this->__unset($key);
    }

    /**
     * Get a custom field value by CustomField name (eg: Lid_NVJ instead of custom_33)
     * @param string $key Custom field name
     * @return mixed|null Field value, if found
     */
    public function getCustom($key) {
        $fields = $this->getFields('get');
        if(isset($fields[$key])) {
            if(!isset($this->data->$key) && !$this->customDataFetched) {
                $this->fetchEntityCustomData();
            }
            return $this->getValue($fields[$key]->api_field_name);
        }
        return null;
    }

    /**
     * Set a custom field value by CustomField name (eg: Lid_NVJ instead of custom_33).
     * @param string $key Custom field name
     * @param string $value Custom field value
     * @return void
     * @throws WPCiviException If a custom field with this key does not exist
     */
    public function setCustom($key, $value) {
        $fields = $this->getFields('create');
        if(!isset($fields[$key])) {
            throw new WPCiviException("No valid custom field defined for key '{$key}' in Entity.setCustom");
        }

        $this->setValue($fields[$key]->api_field_name, $value); // Set for custom_33 key
        // $this->setValue($key, $value); // Set value for our custom key
    }

    /**
     * Set a single custom value by performing a direct API call.
     * Workaround because the Case Create/Update API doesn't seem to allow submitting custom fields.
     * TODO: Report / fix Case API!
     * @param string $customFieldName Custom field internal name
     * @param string $value Custom field value
     * @return bool Success
     * @throws WPCiviException Thrown if value cannot be set
     */
    public function setSingleCustomValue($customFieldName, $value) {
        $fields = $this->getFields('create');
        $apiResult = WPCiviApi::call($this->entityType, 'setvalue', ['field' => $fields[$customFieldName]->api_field_name, 'id' => $this->getId(), 'value' => $value]);
        if(!empty($apiResult->is_error) && $apiResult->is_error == true) {
            throw new WPCiviException('Could not save custom field value for entity type ' . $this->entityType . ': field name ' . $customFieldName . ', value ' . $value . '.');
            return false;
        }
        return true;
    }

    /**
     * @return array Get an array of all current class variables.
     */
    public function getArray()
    {
        return (array)$this->data;
    }

    /**
     * Add an array of parameters to this entity
     * @param array|\stdClass $params Parameters (as an array, or a single level \stdClass as the API returns)
     * @throws WPCiviException When invalid data is passed
     */
    public function setArray($params = [])
    {
        if(!is_array($params) && !is_a($params, 'stdClass')) {
            throw new WPCiviException('Invalid $params for Entity::setArray - must be array or \stdClass');
        }
        if(is_object($params)) {
            $params = (array)$params;
        }
        if(empty($this->data)) {
            $this->data = new \stdClass;
        }
        foreach($params as $k => $v) {
            $this->data->$k = $v;
        }
    }

    /**
     * Function to clear class variables.
     */
    public function clear()
    {
        $this->data = [];
    }

    /**
     * Get single local static cache variable/array.
     * @param string $name Cache key
     * @param mixed $refreshWith Optional array, object, callable, etc to refresh the cache with
     * @return mixed Cached data
     */
    public static function getStaticCache($name, $refreshWith = null)
    {
        if (empty(static::$cache[$name])) {
            if (is_callable($refreshWith)) {
                static::$cache[$name] = call_user_func($refreshWith);
            } else {
                static::$cache[$name] = $refreshWith;
            }
        }
        return static::$cache[$name];
    }

}
