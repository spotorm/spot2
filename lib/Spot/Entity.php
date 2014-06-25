<?php
namespace Spot;

/**
* Entity object
*
* @package Spot
*/
abstract class Entity
{
    protected static $table;
    protected static $tableOptions = [];
    protected static $mapper = false;

    // Entity data storage
    protected $_data = [];
    protected $_dataModified = [];
    protected $_relations = [];

    // Entity state
    protected $_isNew = true;

    // Entity error messages (may be present after save attempt)
    protected $_errors = [];

    /**
     * Constructor - allows setting of object properties with array on construct
     */
    public function __construct(array $data = array())
    {
        $this->initFields();

        // Set given data
        if($data) {
            $this->data($data, false);
        }
    }

    /**
     * Set all field values to their defualts or null
     */
    protected function initFields()
    {
        $fields = static::fields();
        foreach($fields as $field => $opts) {
            if(!isset($this->_data[$field])) {
                $this->_data[$field] = isset($opts['value']) ? $opts['value'] : null;
            }
        }
    }

    /**
     * Table name getter/setter
     */
    public static function table($tableName = null)
    {
        if(null !== $tableName) {
            static::$table = $tableName;
            return $this;
        }
        return static::$table;
    }

    /**
     * Datasource options getter/setter
     */
    public static function tableOptions($tableOpts = null)
    {
        if(null !== $tableOpts) {
            static::$tableOptions = $tableOpts;
            return $this;
        }
        return static::$tableOptions;
    }

    /**
     * Mapper name getter
     */
    public static function mapper()
    {
        return static::$mapper;
    }

    /**
     * Return defined fields of the entity
     */
    public static function fields()
    {
        return [];
    }

    /**
     * Add events to this entity
     */
    public static function events(EventEmitter $eventEmitter) { }

    /**
     * Return defined fields of the entity
     */
    public static function relations(Mapper $mapper, self $entity)
    {
        return [];
    }

    /**
     * Gets and sets data on the current entity
     */
    public function data($data = null, $modified = true)
    {
        // GET
        if(null === $data || !$data) {
            return array_merge($this->_data, $this->_dataModified);
        }

        // SET
        if(is_object($data) || is_array($data)) {
            $fields = $this->fields();
            foreach($data as $k => $v) {
                if(true === $modified) {
                    $this->_dataModified[$k] = $v;
                } else {
                    $this->_data[$k] = $v;
                }
            }
            return $this;
        } else {
            throw new \InvalidArgumentException(__METHOD__ . " Expected array or object input - " . gettype($data) . " given");
        }
    }

    /**
     * Return array of field data with data from the field names listed removed
     *
     * @param array List of field names to exclude in data list returned
     */
    public function dataExcept(array $except)
    {
        return array_diff_key($this->data(), array_flip($except));
    }

    /**
     * Gets data that has been modified since object construct,
     * optionally allowing for selecting a single field
     */
    public function dataModified($field = null)
    {
        if (null !== $field) {
            return isset($this->_dataModified[$field]) ? $this->_dataModified[$field] : null;
        }
        return $this->_dataModified;
    }

    /**
     * Gets data that has not been modified since object construct,
     * optionally allowing for selecting a single field
     */
    public function dataUnmodified($field = null)
    {
        if (null !== $field) {
            return isset($this->_data[$field]) ? $this->_data[$field] : null;
        }
        return $this->_data;
    }

    /**
     * Is entity new (unsaved)?
     *
     * @return boolean
     */
    public function isNew($new = null)
    {
        if($new !== null) {
            $this->_isNew = (boolean) $new;
        }
        return $this->_isNew;
    }

    /**
     * Returns true if a field has been modified.
     * If no field name is passed in, return whether any fields have been changed
     */
    public function isModified($field = null)
    {
        if (null !== $field) {
            if (array_key_exists($field, $this->_dataModified)) {
                if (is_null($this->_dataModified[$field]) || is_null($this->_data[$field])) {
                    // Use strict comparison for null values, non-strict otherwise
                    return $this->_dataModified[$field] !== $this->_data[$field];
                }
                return $this->_dataModified[$field] != $this->_data[$field];
            } else if (array_key_exists($field, $this->_data)) {
                return false;
            } else {
                return null;
            }
        }
        return !!count($this->_dataModified);
    }

    /**
     * Alias of self::data()
     */
    public function toArray()
    {
        return $this->data();
    }

    /**
     * Check if any errors exist
     *
     * @param string $field OPTIONAL field name
     * @return boolean
     */
    public function hasErrors($field = null)
    {
        if(null !== $field) {
            return isset($this->_errors[$field]) ? count($this->_errors[$field]) > 0 : false;
        }
        return count($this->_errors) > 0;
    }

    /**
     * Error message getter/setter
     *
     * @param $field string|array String return errors with field key, array sets errors
     * @return self|array|boolean Setter return self, getter returns array or boolean if key given and not found
     */
    public function errors($msgs = null, $overwrite = true)
    {
        // Return errors for given field
        if(is_string($msgs)) {
            return isset($this->_errors[$msgs]) ? $this->_errors[$msgs] : [];

        // Set error messages from given array
        } elseif(is_array($msgs)) {
            if($overwrite) {
                $this->_errors = $msgs;
            } else {
                $this->_errors = array_merge_recursive($this->_errors, $msgs);
            }
        }
        return $this->_errors;
    }

    /**
     * Add an error to error messages array
     *
     * @param string $field Field name that error message relates to
     * @param mixed $msg Error message text - String or array of messages
     */
    public function error($field, $msg)
    {
        if(is_array($msg)) {
            // Add array of error messages about field
            foreach($msg as $msgx) {
                $this->_errors[$field][] = $msgx;
            }
        } else {
            // Add to error array
            $this->_errors[$field][] = $msg;
        }
    }

    /**
     * Enable isset() for object properties
     */
    public function __isset($key)
    {
        return isset($this->_data[$key]) || isset($this->_dataModified[$key]);
    }

    /**
     * Getter for field properties
     */
    public function &__get($field)
    {
        $v = null;

        if (isset($this->_relations[$field])) {
            $v =& $this->_relations[$field];
        // We can't use isset for _dataModified because it returns
        // false for NULL values
        } elseif (array_key_exists($field, $this->_dataModified)) {
            $v =&  $this->_dataModified[$field];
        } elseif (isset($this->_data[$field])) {
            $v =& $this->_data[$field];
        }

        return $v;
    }

    /**
     * Setter for field properties
     */
    public function __set($field, $value)
    {
        $this->_dataModified[$field] = $value;
    }

    /**
     * Set relation
     */
    public function setRelation($relationName, $relationObj)
    {
        return $this->_relations[$relationName] = $relationObj;
    }

    /**
     * Has relation
     *
     * @return boolean
     */
    public function hasRelation($relationName)
    {
        return isset($this->_relations[$relationName]);
    }

    /**
     * String representation of the class
     */
    public function __toString()
    {
        return __CLASS__;
    }
}
