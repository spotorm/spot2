<?php
namespace Spot;

/**
* Entity object
*
* @package Spot
*/
abstract class Entity implements EntityInterface, \JsonSerializable
{
    protected static $table;
    protected static $tableOptions = [];
    protected static $mapper = false;

    protected $_objectId;

    // Entity data storage
    protected $_data = [];
    protected $_dataModified = [];

    // Used internally so entity knows which fields are relations
    protected static $relationFields = [];

    // Entity state
    protected $_isNew = true;
    protected $_inGetter = [];
    protected $_inSetter = [];

    // Entity error messages (may be present after save attempt)
    protected $_errors = [];

    /**
     * Constructor - allows setting of object properties with array on construct
     */
    public function __construct(array $data = array())
    {
        // Generate Unique object ID
        $this->_objectId = uniqid('entity_', true) . spl_object_hash($this);

        $this->initFields();

        // Set given data
        if ($data) {
            $this->data($data, false);
        }
    }

    /**
     * Set all field values to their defualts or null
     */
    protected function initFields()
    {
        $fields = static::fields();
        foreach ($fields as $field => $opts) {
            if (!isset($this->_data[$field])) {
                $this->_data[$field] = isset($opts['value']) ? $opts['value'] : null;
            }
        }

        $entityName = get_class($this);
        if (!isset(self::$relationFields[$entityName])) {
            self::$relationFields[$entityName] = [];
        }
    }

    /**
     * Table name getter/setter
     */
    public static function table($tableName = null)
    {
        if (null !== $tableName) {
            static::$table = $tableName;
        }

        return static::$table;
    }

    /**
     * Datasource options getter/setter
     */
    public static function tableOptions($tableOpts = null)
    {
        if (null !== $tableOpts) {
            static::$tableOptions = $tableOpts;
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
    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [];
    }

    /**
     * Return scopes defined by this entity. Scopes are called from the
     * Spot\Query object as a sort of in-context dynamic query method
     */
    public static function scopes()
    {
        return [];
    }

    /**
     * Gets and sets data on the current entity
     */
    public function data($data = null, $modified = true)
    {
        // GET
        if (null === $data || !$data) {
            $data = array_merge($this->_data, $this->_dataModified);
            foreach ($data as $k => &$v) {
                $v = $this->__get($k, $v);
            }

            return $data;
        }

        // SET
        if (is_object($data) || is_array($data)) {
            foreach ($data as $k => $v) {
                $this->set($k, $v, $modified);
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
        if ($new !== null) {
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
            } elseif (array_key_exists($field, $this->_data)) {
                return false;
            } else {
                return null;
            }
        }

        /* Check if any of values really has changed. */
        foreach (array_keys($this->_dataModified) as $field) {
            if (true === $this->isModified($field)) {
                return true;
            }
        };

        return false;
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
     * @param  string  $field OPTIONAL field name
     * @return boolean
     */
    public function hasErrors($field = null)
    {
        if (null !== $field) {
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
        if (is_string($msgs)) {
            return isset($this->_errors[$msgs]) ? $this->_errors[$msgs] : [];

        // Set error messages from given array
        } elseif (is_array($msgs)) {
            if ($overwrite) {
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
     * @param mixed  $msg   Error message text - String or array of messages
     */
    public function error($field, $msg)
    {
        if (is_array($msg)) {
            // Add array of error messages about field
            foreach ($msg as $msgx) {
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
        $entityName = get_class($this);
        return isset($this->_data[$key]) || isset($this->_dataModified[$key]) || in_array($key, self::$relationFields[$entityName]);
    }

    /**
     * Getter for field properties
     */
    public function &__get($field)
    {
        $v = null;

        $camelCaseField = str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
        $getterMethod = 'get' . $camelCaseField;
        if (!in_array($field, $this->_inGetter) && method_exists($this, $getterMethod)) {
            // Custom getter method
            $this->_inGetter[$field] = true;
            $v = call_user_func([$this, $getterMethod]);
            unset($this->_inGetter[$field]);
        } else {
            // We can't use isset because it returns false for NULL values
            if (array_key_exists($field, $this->_dataModified)) {
                $v =& $this->_dataModified[$field];
            } elseif (array_key_exists($field, $this->_data)) {
                $v =& $this->_data[$field];
            } elseif ($relation = $this->relation($field)) {
                $v =& $relation;
            }
        }

        return $v;
    }
    public function get($field)
    {
        return $this->__get($field);
    }

    /**
     * Setter for field properties
     */
    public function __set($field, $value)
    {
        return $this->set($field, $value);
    }
    public function set($field, $value, $modified = true)
    {
        // Custom setter method
        $camelCaseField = str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
        $setterMethod = 'set' . $camelCaseField;
        if (!in_array($field, $this->_inSetter) && method_exists($this, $setterMethod)) {
            $this->_inSetter[$field] = true;
            $value = call_user_func([$this, $setterMethod], $value);
            unset($this->_inSetter[$field]);
        }

        if (in_array($field, self::$relationFields[get_class($this)])) {
            // Set relation
            $this->relation($field, $value);
        } elseif ($modified) {
            $this->_dataModified[$field] = $value;
        } else {
            $this->_data[$field] = $value;
        }
    }

    /**
     * Get/Set relation
     */
    public function relation($relationName, $relationObj = null)
    {
        // Local static property instead of class variable prevents the
        // relation object, mapper, and connection info
        // from being printed with a var_dump() of the entity
        static $relations = [];
        $objectId = $this->_objectId;

        // Get relation
        if ($relationObj === null) {
            if (isset($relations[$objectId][$relationName])) {
                return $relations[$objectId][$relationName];
            }

            return false;
        } elseif ($relationObj === false) {
            // Unset relation
            if (isset($relations[$objectId][$relationName])) {
                unset($relations[$objectId][$relationName]);
            }

            return false;
        } else {
            // Set relation
            $relations[$objectId][$relationName] = $relationObj;

            // Add to relation field array
            $entityName = get_class($this);
            if (!in_array($relationName, self::$relationFields[$entityName])) {
                self::$relationFields[$entityName][] = $relationName;
            }
        }

        return $relations[$objectId][$relationName];
    }

    /**
     * Get primary key field name
     *
     * @return string Primary key field name
     */
    public function primaryKeyField()
    {
        $fields = static::fields();
        foreach ($fields as $fieldName => $fieldAttrs) {
            if (isset($fieldAttrs['primary'])) {
                return $fieldName;
            }
        }
        return false;
    }

    /**
     * Get the value of the primary key field defined on this entity
     *
     * @return mixed Value of the primary key field
     */
    public function primaryKey()
    {
        $pkField = $this->primaryKeyField();
        return $pkField ? $this->get($pkField) : false;
    }

    /**
     * Helper function so entity can be accessed via relation in a more
     * consistent manner with 'entity()' without any errors (i.e. relation will
     * not error if it already has a loaded entity object - it just returns
     * $this)
     */
    public function entity()
    {
        return $this;
    }

    /**
     * Return array for json_encode()
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * String representation of the class (JSON)
     */
    public function __toString()
    {
        return json_encode($this->jsonSerialize());
    }

    /**
     * Do some cleanup of stored relations so orphaned relations are not held
     * in memory
     */
    public function __destruct()
    {
        $entityName = get_class($this);
        if (isset(self::$relationFields[$entityName])) {
            foreach (self::$relationFields[$entityName] as $relation) {
                $this->relation($relation, false);
            }
        }
    }
}
