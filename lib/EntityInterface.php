<?php
namespace Spot;

/**
* Entity object interface
*
* @package Spot
*/
interface EntityInterface
{
    /**
     * Table name getter/setter
     */
    public static function table($tableName = null);

    /**
     * Datasource options getter/setter
     */
    public static function tableOptions($tableOpts = null);

    /**
     * Mapper name getter
     */
    public static function mapper();

    /**
     * Return defined fields of the entity
     */
    public static function fields();

    /**
     * Add events to this entity
     */
    public static function events(EventEmitter $eventEmitter);

    /**
     * Return defined fields of the entity
     */
    public static function relations(MapperInterface $mapper, EntityInterface $entity);

    /**
     * Return scopes defined by this entity. Scopes are called from the
     * Spot\Query object as a sort of in-context dynamic query method
     */
    public static function scopes();

    /**
     * Gets and sets data on the current entity
     */
    public function data($data = null, $modified = true);

    /**
     * Return array of field data with data from the field names listed removed
     *
     * @param array List of field names to exclude in data list returned
     */
    public function dataExcept(array $except);

    /**
     * Gets data that has been modified since object construct,
     * optionally allowing for selecting a single field
     */
    public function dataModified($field = null);

    /**
     * Gets data that has not been modified since object construct,
     * optionally allowing for selecting a single field
     */
    public function dataUnmodified($field = null);

    /**
     * Is entity new (unsaved)?
     *
     * @return boolean
     */
    public function isNew($new = null);

    /**
     * Returns true if a field has been modified.
     * If no field name is passed in, return whether any fields have been changed
     */
    public function isModified($field = null);

    /**
     * Alias of self::data()
     */
    public function toArray();

    /**
     * Check if any errors exist
     *
     * @param  string  $field OPTIONAL field name
     * @return boolean
     */
    public function hasErrors($field = null);

    /**
     * Error message getter/setter
     *
     * @param $field string|array String return errors with field key, array sets errors
     * @return self|array|boolean Setter return self, getter returns array or boolean if key given and not found
     */
    public function errors($msgs = null, $overwrite = true);

    /**
     * Add an error to error messages array
     *
     * @param string $field Field name that error message relates to
     * @param mixed  $msg   Error message text - String or array of messages
     */
    public function error($field, $msg);

    /**
     * Enable isset() for object properties
     */
    public function __isset($key);

    /**
     * Getter for field properties
     */
    public function &__get($field);
    public function get($field);

    /**
     * Setter for field properties
     */
    public function __set($field, $value);
    public function set($field, $value, $modified = true);

    /**
     * Get/Set relation
     */
    public function relation($relationName, $relationObj = null);

    /**
     * Get primary key field name
     *
     * @return string Primary key field name
     */
    public function primaryKeyField();

    /**
     * Get the value of the primary key field defined on this entity
     *
     * @return mixed Value of the primary key field
     */
    public function primaryKey();

    /**
     * Return array for json_encode()
     */
    public function jsonSerialize();

    /**
     * String representation of the class (JSON)
     */
    public function __toString();
}
