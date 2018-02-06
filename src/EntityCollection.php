<?php
namespace WPCivi\Shared;

use WPCivi\Shared\Civi\WPCiviApi;
use WPCivi\Shared\Util\CustomDataCache;
use WPCivi\Shared\Util\DatastoreTrait;

/**
 * Class EntityCollection. This class represents a (temporary, iterable) collection of Entity objects.
 * This structure is very provisional - these classes could use the API better, and the architecture for a long-term solution to integrate WP+CiviEntities should probably be very different.
 * @package WPCivi\Shared
 */
class EntityCollection implements \ArrayAccess, \Iterator, \Countable
{

    use DatastoreTrait;

    /**
     * @var string $entityType CiviCRM Entity Type
     * Note this is defined dynamically, while the Entity classes all have their own permanent entity ypes.
     */
    protected $entityType;

    /**
     * @var Entity[]|array $data Item data
     */
    private $data = [];

    /**
     * EntityCollection constructor.
     * @param string $entityType CiviCRM Entity Type
     */
    public function __construct($entityType)
    {
        $this->entityType = $entityType;
    }

    /**
     * Return entity class name.
     * @return string
     */
    protected function getEntityClassName()
    {
        return __NAMESPACE__ . "\\Entity\\" . $this->entityType;
    }

    /**
     * Get entity table name (provisional)
     * @return string Table Name
     */
    protected function entityTable()
    {
        return 'civicrm_' . strtolower($this->entityType);
    }

    /**
     * Create a new collection of entity type $entity.
     * @param string $entity CiviCRM Entity Type
     * @return EntityCollection This class
     */
    public static function create($entity)
    {
        return new static($entity);
    }

    /**
     * Create a new collection from a CiviCRM API request!
     * @param string $entity Entity Type
     * @param string $action Action Method
     * @param mixed[] $params API Parameters
     * @return EntityCollection This collection
     */
    public static function createApi($entity, $action, $params)
    {
        $collection = new static($entity);

        $entity = ($entity == 'Cases' ? 'Case' : $entity); // Case<->Cases...
        $results = WPCiviApi::call($entity, $action, $params);

        if($results && !empty($results->values)) {
            $collection->fill($results->values);
        }

        return $collection;
    }

    /**
     * Create a new collection from a CiviCRM API Get request!
     * @param string $entity Entity Type
     * @param mixed[] $params API Parameters
     * @return EntityCollection This collection
     */
    public static function get($entity, $params)
    {
        $action = 'get';
        if(!isset($params['options'])) {
            $params['options'] = [];
        }
        if(!isset($params['options']['limit'])) {
            $params['options']['limit'] = 0;
        }

        return static::createApi($entity, $action, $params);
    }

    /**
     * Add or update collection: change an array of API data
     * into an array of entity objects we support.
     * @param array [array]|array[object] $data Arrays or objects
     * @param bool $parse Parse to entity objects
     */
    public function fill($data = [], $parse = true)
    {
        if ($parse == true) {
            $className = $this->getEntityClassName();
            foreach ($data as $row) {
                /** @var Entity $entity */
                $entity = new $className();
                $entity->setArray($row);
                $key = (isset($row->id) ? $row->id : null);
                $this->data[$key] = $entity;
            }
        } else {
            $this->data = array_merge($this->data, $data);
        }
    }

    /**
     * Prefetch a number of custom fields for all entities in this entity collection,
     * so we won't have to do n+1 queries in a loop to get them all.
     * @param array $fieldNames
     */
    public function prefetchCustomData($fieldNames = [])
    {
        if(count($this->data) == 0) {
            return;
        }

        $fields = CustomDataCache::getInstance()->getEntityActionFields($this->entityType, 'get');
        $returnFields = [];
        foreach($fieldNames as $fName) {
            if(isset($fields[$fName])) {
                $returnFields[] = $fields[$fName]->api_field_name;
            }
        }

        $entityIds = [];
        foreach($this->data as $entity) {
            $entityIds[] = $entity->id;
        }

        $result = WPCiviApi::call($this->entityType, 'Get', [
            'id' => ['IN' => $entityIds],
            'return' => implode(',', $returnFields),
            'options' => ['limit' => 0],
        ]);
        if(empty($result) || $result->is_error) {
            return;
        }

        foreach($result->values as $customRow) {
           if(isset($this->data[$customRow->id])) {
               foreach($customRow as $cFieldName => $cFieldValue) {
                   if(in_array($cFieldName, $returnFields)) {
                       $this->data[$customRow->id]->$cFieldName = $cFieldValue;
                   }
               }
           }
        }
    }

    /**
     * Add or update collection: add a single entity
     * @param Entity $entity Entity Object
     */
    public function add($entity = null) {
        $key = (isset($entity->id) ? $entity->id : null);
        $this->data[$key] = $entity;
    }

    /**
     * Clear collection
     */
    public function clear()
    {
        $this->data = [];
    }

    /**
     * Sort collection using a custom function
     * @param \Closure $callback
     */
    public function usort(\Closure $callback)
    {
        usort($this->data, $callback);
    }
}
