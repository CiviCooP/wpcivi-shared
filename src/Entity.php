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
     * @var WPCiviApi $wpcivi WPCiviApi
     */
    protected $wpcivi;

    /**
     * @var \stdClass $data Data Class
     */
    protected $data = null;

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
        $this->wpcivi = WPCiviApi::getInstance();

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
        $this->data = $this->wpcivi->api($this->entityType, 'getsingle', ['id' => $id]);
        if (!$this->data) {
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
        $this->data = $this->wpcivi->api($this->entityType, 'getsingle', [$params]);
        if (!$this->data) {
            throw new WPCiviException("Could not load entity of type {$this->entityType} with custom params! (" . print_r($params, true) . ")");
        }
        return $this->id;
    }

    /**
     * Function to pass default values if a new entity is created.
     * See the individual entity classes for how this is used.
     */
    protected function setDefaults()
    {
    }

    /**
     * Function to save an entity (= create / update).
     * This will probably also be overloaded often for additional processing.
     * @param bool $reload Reload current class after creation/processing
     * @return bool Success
     * @throws WPCiviException If the entity can't be saved.
     */
    public function save($reload = true)
    {
        if (empty($this->data)) {
            throw new WPCiviException('Could not save entity: it currently doesn\'t contain data.');
        }

        // Check if all field names are valid and omit get-only and internal fields
        $createData = [];
        $createFields = $this->getFields('create');
        foreach($this->data as $key => $value) {
            if(array_key_exists($key, $createFields)) {
                $createData[$key] = $value;
            }
        }

        // Try to save data
        $ret = $this->wpcivi->api($this->entityType, 'create', $this->data);
        if (!isset($ret) || $ret->is_error) {
            throw new WPCiviException('Could not save ' . $this->entityType . ': ' . (int)$this->id . '.');
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
     * Static class to add a new entity - and save and return it, if possible.
     * If $params is empty, this class will just be instantiated. If it is an array,
     * we'll do an API request to save it and return the new entity class.
     * @param array $params Entity Parameters
     * @param bool $returnEntity Return entity after save?
     * @return self Entity or API result
     * @throws WPCiviException If an entity could not be added
     */
    public static function create($params = null, $returnEntity = true)
    {
        if (empty($params)) {
            return new self;
        } else {
            $entity = new self;
            $entity->setArray($params);

            $ret = $entity->save();
            return ($returnEntity == true ? $entity : $ret);
        }
    }

    /**
     * Reload existing entity data
     * @return bool Success
     */
    public function reload()
    {
        if (!empty($this->data) || isset($this->id)) {
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

            $fields = $this->wpcivi->api($this->entityType, 'getfields', []);
            $this->fields[$action] = [];

            // Quickhack! -> get all CustomFields in this class and add their CustomField.name as well...
            // There should be a better way to do this:
            foreach ($fields->values as $field) {
                if (strpos($field->name, 'custom_') === 0) {
                    $fieldId = str_ireplace('custom_', '', $field->name);
                    $customField = $customDataCache->getFieldByIds($field->custom_group_id, $fieldId);
                    $field->custom_field_name = $customField->name;
                    // TODO add $field->custom_group_name = $customField->group_name;

                    $this->fields[$action][$field->custom_field_name] = $field;
                } else {
                    $this->fields[$action][$field->name] = $field;
                }
            }
        }

        return $this->fields[$action];
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
     * Get a custom field value by CustomFieldField name (eg: Lid_NVJ instead of custom_33)
     * @param string $key Custom field name
     * @return mixed|null Field value, if found
     */
    public function getCustom($key) {
        $fields = $this->getFields('get');
        if(isset($fields[$key])) {
            return $this->getValue($fields[$key]->name);
        }
        return null;
    }

    /**
     * Set a custom field value by CustomField name (eg: Lid_NVJ instead of custom_33)
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

        $this->setValue($fields[$key]->name, $value); // Set for custom_33 key
        $this->setValue($key, $value); // Also set for our custom key
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
        $this->data = array_merge($this->data, $params);
    }

    /**
     * Function to clear class variables.
     */
    public function clear()
    {
        $this->data = [];
    }

}
