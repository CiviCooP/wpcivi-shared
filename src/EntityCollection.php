<?php
namespace WPCivi\Shared;

use WPCivi\Shared\Civi\WPCiviApi;
use WPCivi\Shared\Util\DatastoreTrait;

/**
 * Class EntityCollection. This class represents a (temporary, iterable) collection of Entity objects.
 * This structure is very provisional - these classes could use the API better, and the architecture for a long-term solution to integrate WP+CiviEntities should probably be very different.
 * @package WPCivi\Shared
 */
class EntityCollection implements \ArrayAccess, \Iterator, \Traversable, \Countable
{

    use DatastoreTrait;

    /**
     * @var string $entityType CiviCRM Entity Type
     * Note this is defined dynamically, while the Entity classes all have their own permanent entity ypes.
     */
    protected $entityType;

    /**
     * @var array(mixed) $data Item data
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
     * @param string $entityType CiviCRM Entity Type
     * @return EntityCollection This class
     */
    public static function create($entityType)
    {
        return new self($entityType);
    }

    /**
     * Create a new collection from a CiviCRM API request!
     * @param string $entityType Entity Type
     * @param string $action Action Method
     * @param mixed[]  $params API Parameters
     * @return EntityCollection This collection
     */
    public static function createFromApiCall($entityType, $action, $params)
    {
        $wpcivi = WPCiviApi::getInstance();
        $collection = new self($entityType);

        $results = $wpcivi->api($entityType, $action, $params);
        if($results) {
            $collection->fill($results->values);
        }

        return $collection;
    }

    /**
     * Create a new collection from a CiviCRM API Get request!
     * @param string $entity Action Method
     * @param string $action Action Method
     * @param array [mixed] $params API Parameters
     * @return EntityCollection This collection
     */
    public static function get($entity, $action, $params)
    {
        $wpcivi = WPCiviApi::getInstance();
        $collection = new self($entity);

        $results = $wpcivi->api($entity, $action, $params);
        $collection->fill($results);

        return $collection;
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
            $className = $this->entityType;
            foreach ($data as $row) {
                /** @var Entity $entity */
                $entity = new $className();
                $entity->setArray($row);
                $this->data[] = $entity;
            }
        } else {
            $this->data = array_merge($this->data, $data);
        }
    }

    /**
     * Clear collection
     */
    public function clear()
    {
        $this->data = [];
    }

}
