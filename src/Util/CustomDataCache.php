<?php
namespace WPCivi\Shared\Util;

use WPCivi\Shared\Civi\WPCiviApi;
use WPCivi\Shared\Civi\WPCiviException;

/**
 * Class Util\CustomDataCache.
 * Functionality to access configuration about CustomGroups/CustomFields and cache that information in a local array. We might want to reuse this, with a good base class, for other types of entities (OptionGroup/OptionField, etc).
 *
 * @package WPCivi\Shared
 */
class CustomDataCache
{

    /**
     * @var static $instance
     */
    private static $instance;

    /**
     * @var array $actionFieldsCache
     */
    private $actionFieldsCache = [];

    /**
     * @var array $customGroupCache
     */
    private $customGroupCache = [];

    /**
     * @var array $customGroupMapping
     */
    private $customGroupMapping = [];

    /**
     * Get class instance.
     * @return static
     */
    public static function getInstance()
    {
        if (!isset(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * getGroup: load a custom group and its fields into local cache.
     * Changed this back from SP CustomField class: we can always add an initAllGroups method for performance if needed.
     * @param int|string $value Value (id or name)
     * @param string $key Key (id or name)
     * @return \stdClass|null Group data on success, null if call valid but not found
     * @throws WPCiviException If parameters and high-level checks are invalid
     * @throws \CiviCRM_API3_Exception If unexpected input or output occurred for API calls.
     */
    public function getGroup($value, $key = 'id')
    {
        // Check if entity is already in cache
        if ($key == 'name') {
            if (isset($this->customGroupCache[$value])) {
                return $this->customGroupCache[$value];
            }
            $this->customGroupCache[$value] = null;
        } elseif ($key == 'id') {
            if (isset($this->customGroupMapping[$value])) {
                return $this->customGroupCache[$this->customGroupMapping[$value]];
            }
            $this->customGroupMapping[$value] = null;
        } else {
            throw new WPCiviException("Invalid key type '{$key}' in CustomDataCache::getGroup.'");
        }

        // Fetch group and fill class variables
        $group = WPCiviApi::call('CustomGroup', 'getsingle', [$key => $value]);

        $this->customGroupCache[$group->name] = $group;
        $this->customGroupCache[$group->name]->fields = [];
        $this->customGroupCache[$group->name]->fieldMapping = [];
        $this->customGroupMapping[$group->id] = $group->name;

        // Fetch fields
        $fields = WPCiviApi::call('CustomField', 'get', [
            'custom_group_id' => $group->id,
            'options'         => ['limit' => 10000],
        ]);
        foreach ($fields->values as $field) {
            $this->customGroupCache[$group->name]->fields[$field->name] = $field;
            $this->customGroupCache[$group->name]->fields[$field->name]->custom_group_name = $group->name;
            $this->customGroupCache[$group->name]->fieldMapping[$field->id] = $field->name;
        }

        return $this->customGroupCache[$group->name];
    }

    /**
     * Find a custom field group by name (now handled by function above).
     * @param string $name Group name
     * @return \stdClass|null Group if found, null if not found
     */
    public function getGroupByName($name)
    {
        return $this->getGroup($name, 'name');
    }

    /**
     * Find a custom field group by ID (now handled by function above).
     * @param int $id ID
     * @return \stdClass|null Group if found, null if not found
     */
    public function getGroupById($id)
    {
        return $this->getGroup($id, 'id');
    }

    /**
     * Find a custom field by group name and field name.
     * @param string $groupName Group name
     * @param string $fieldName Field name
     * @return \stdClass|bool Field if found, false if not found
     */
    public function getField($groupName, $fieldName)
    {
        $group = $this->getGroupByName($groupName);

        if (!is_null($group) && isset($group->fields[$fieldName])) {
            return $group->fields[$fieldName];
        }
        return false;
    }

    /**
     * Get all custom fields by group name
     * @param string $groupName Group name
     * @return array|bool Array of fields if found, false if not found
     */
    public function getFields($groupName)
    {
        $group = $this->getGroupByName($groupName);
        if(!is_null($group) && isset($group['fields'])) {
            return $group['fields'];
        }
        return false;
    }

    /**
     * Get custom field by group id and field id, using the cache array wherever possible
     * Quick hack, will result in multiple API requests and could be more efficient
     * @param int $groupId Group ID
     * @param int $fieldId Field ID
     * @return \stdClass|bool Field if found, false if not found
     */
    public function getFieldByIds($groupId, $fieldId) {
        $group = $this->getGroupById($groupId);
        if(!empty($group) && !empty($group->fieldMapping)) {
            $fieldName = $group->fieldMapping[$fieldId];
            if (!empty($fieldName)) {
                return $group->fields[$fieldName];
            }
        }
        return false;
    }

    /**
     * Get a custom field ID by group name and field name.
     * @param string $groupName Group name
     * @param string $fieldName Field name
     * @return int|bool Field ID if found, false if not found
     */
    public function getFieldId($groupName, $fieldName)
    {
        $field = $this->getField($groupName, $fieldName);
        if (!empty($field)) {
            return $field->id;
        }
        return false;
    }

    /**
     * Get defined fields for a certain action for a certain entity type, with some extra properties.
     * Used by entities to determine what fields can be requested or saved.
     * @param string $entityType Entity Type
     * @param string $action Action (e.g. 'get')
     * @param bool $reload Always reload?
     * @return array Fields
     */
    public function getEntityActionFields($entityType, $action, $reload = false)
    {
        if(empty($this->actionFieldsCache[$entityType])) {
            $this->actionFieldsCache[$entityType] = [];
        }

        if(empty($this->actionFieldsCache[$entityType][$action]) || $reload == true) {

            $fields = WPCiviApi::call($entityType, 'getfields', ['options' => ['limit' => 9999]]);
            $this->actionFieldsCache[$entityType][$action] = [];

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

                    $customField = $this->getFieldByIds($field->custom_group_id, $fieldId);
                    if(!empty($customField)) {
                        $newField->custom_group_name = $customField->custom_group_name;
                        $newField->api_field_name = $field->name;
                        $newField->name = $customField->name;
                    }
                }

                $this->actionFieldsCache[$entityType][$action][$newField->name] = $newField;
            }
        }

        return $this->actionFieldsCache[$entityType][$action];
    }

}