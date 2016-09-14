<?php
namespace WPCivi\Shared\Util;

/**
 * Class DatastoreTrait.
 * Use for classes that have a datastore in a $this->data array,
 * to make that data transparently accessibly through object properties or array keys.
 * Provides all methods to implement \ArrayAccess, \Iterator, \Traversable, \Countable.
 * @package WPCivi\Shared
 */
trait DatastoreTrait
{

    /* ----- Magic methods ----- */

    public function __get($key)
    {
        if (isset($this->data->$key)) {
            return $this->data->$key;
        }
        return null;
    }

    public function __set($key, $value)
    {
        if(!is_object($this->data)) {
            $this->data = new \stdClass;
        }
        $this->data->$key = $value;
    }

    public function __isset($key)
    {
        return isset($this->data->$key);
    }

    public function __unset($key)
    {
        unset($this->data->$key);
    }

    public function __toString()
    {
        return get_class($this) . ": " . (($this->id || $this->name) ? "{$this->id} {$this->name}" : "Unknown");
    }

    /* ----- Array access ----- */

    public function offsetGet($key)
    {
        return $this->__get($key);
    }

    public function offsetSet($key, $value)
    {
        $this->__set($key, $value);
    }

    public function offsetUnset($key)
    {
        $this->__unset($key);
    }

    public function offsetExists($key)
    {
        return $this->__isset($key);
    }

    /* ----- Iterator / Traversable ----- */

    public function rewind()
    {
        reset($this->data);
    }

    public function current()
    {
        return current($this->data);
    }

    public function key()
    {
        return key($this->data);
    }

    public function next()
    {
        return next($this->data);
    }

    public function valid()
    {
        return $this->current() !== false;
    }

    /* ----- Countable ------ */

    public function count()
    {
        return count($this->data);
    }

}