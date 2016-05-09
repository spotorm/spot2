<?php
namespace Spot\Entity;

use Spot;

/**
 * Entity Manager for storing information about entities
 *
 * @package Spot\Entity
 * @author Vance Lucas <vance@vancelucas.com>
 */
class Manager
{
    /**
     * @var string|\Spot\Entity
     */
    protected $entityName;

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * @var array
     */
    protected $fieldAliasMappings = [];

    /**
     * @var array
     */
    protected $fieldsDefined = [];

    /**
     * @var array
     */
    protected $fieldDefaultValues = [];

    /**
     * @var array
     */
    protected $relations = [];

    /**
     * @var array
     */
    protected $scopes = [];

    /**
     * @var
     */
    protected $primaryKeyField;

    /**
     * @var
     */
    protected $table;

    /**
     * @var
     */
    protected $tableOptions;

    /**
     * @var
     */
    protected $mapper;

    /**
     * Entity to get information for
     * @param string|\Spot\Entity $entityName
     * @throws Spot\Exception
     */
    public function __construct($entityName)
    {
        if (!is_string($entityName)) {
            throw new \Spot\Exception(__METHOD__ . " only accepts a string. Given (" . gettype($entityName) . ")");
        }

        if (!is_subclass_of($entityName, '\Spot\Entity')) {
            throw new \Spot\Exception($entityName . " must be subclass of '\Spot\Entity'.");
        }

        $this->entityName = $entityName;
    }

    /**
     * Get formatted fields with all necessary array keys and values.
     * Merges defaults with defined field values to ensure all options exist for each field.
     *
     * @return array Defined fields plus all defaults for full array of all possible options
     */
    public function fields()
    {
        $entityName = $this->entityName;

        if (!empty($this->fields)) {
            $returnFields = $this->fields;
        } else {
            // Table info
            $entityTable = null;
            $entityTable = $entityName::table();
            if (null === $entityTable || !is_string($entityTable)) {
                throw new \InvalidArgumentException("Entity must have a table defined. Please define a protected static property named 'table' on your '" . $entityName . "' entity class.");
            }
            $this->table = $entityTable;

            // Table Options
            $entityTableOptions = $entityName::tableOptions();
            $this->tableOptions = (array) $entityTableOptions;

            // Custom Mapper
            $this->mapper = $entityName::mapper();

            // Default settings for all fields
            $fieldDefaults = [
                'type' => 'string',
                'default' => null,
                'value' => null,
                'length' => null,
                'column' => null,
                'required' => false,
                'notnull' => false,
                'unsigned' => false,
                'fulltext' => false,
                'primary' => false,
                'index' => false,
                'unique' => false,
                'autoincrement' => false,
                'foreignkey' => true,
                'onUpdate' => null,
                'onDelete' => null
            ];

            // Type default overrides for specific field types
            $fieldTypeDefaults = [
                'string' => [
                    'length' => 255
                ],
                'float' => [
                    'length' => [10, 2]
                ],
                'integer' => [
                    'length' => 10,
                    'unsigned' => true
                ]
            ];

            // Get entity fields from entity class
            $entityFields = false;
            $entityFields = $entityName::fields();
            if (!is_array($entityFields) || count($entityFields) < 1) {
                throw new \InvalidArgumentException($entityName . " Must have at least one field defined.");
            }

            $returnFields = [];
            $this->fieldDefaultValues = [];
            foreach ($entityFields as $fieldName => $fieldOpts) {
                // Store field definition exactly how it is defined before modifying it below
                $this->fieldsDefined[$fieldName] = $fieldOpts;

                // Format field will full set of default options
                if (isset($fieldOpts['type']) && isset($fieldTypeDefaults[$fieldOpts['type']])) {
                    // Include type defaults
                    $fieldOpts = array_merge($fieldDefaults, $fieldTypeDefaults[$fieldOpts['type']], $fieldOpts);
                } else {
                    // Merge with defaults
                    $fieldOpts = array_merge($fieldDefaults, $fieldOpts);
                }

                // Required = 'notnull' for DBAL unless manually set in schema
                if (true === $fieldOpts['required']) {
                    // If notnull is set in schema use it
                    if (isset($this->fieldsDefined[$fieldName]['notnull'])) {
                        $fieldOpts['notnull'] = $this->fieldsDefined[$fieldName]['notnull'];
                    } else {
                        $fieldOpts['notnull'] = true;
                    }
                }

                // Set column name to field name/key as default
                if (null === $fieldOpts['column']) {
                    $fieldOpts['column'] = $fieldName;
                } else {
                    // Store user-specified field alias mapping
                    $this->fieldAliasMappings[$fieldName] = $fieldOpts['column'];
                }

                // Old Spot used 'serial' field to describe auto-increment
                // fields, so accomodate that here
                if (isset($fieldOpts['serial']) && $fieldOpts['serial'] === true) {
                    $fieldOpts['primary'] = true;
                    $fieldOpts['autoincrement'] = true;
                }

                // Store primary key
                if (true === $fieldOpts['primary']) {
                    $this->primaryKeyField = $fieldName;
                } elseif (true === $fieldOpts['autoincrement']) {
                    $this->primaryKeyField = $fieldName;
                }

                // Store default value
                if (null !== $fieldOpts['value']) {
                    $this->fieldDefaultValues[$fieldName] = $fieldOpts['value'];
                } elseif (null !== $fieldOpts['default']) {
                    $this->fieldDefaultValues[$fieldName] = $fieldOpts['default'];
                } else {
                    $this->fieldDefaultValues[$fieldName] = null;
                }

                $returnFields[$fieldName] = $fieldOpts;
            }
            $this->fields = $returnFields;
        }

        return $returnFields;
    }

    /**
     * Field alias mappings (used for lookup)
     *
     * @return array Field alias => actual column name
     */
    public function fieldAliasMappings()
    {
        return $this->fieldAliasMappings;
    }

    /**
     * Groups field keys into names arrays of fields with key name as index
     *
     * @return array Key-named associative array of field names in that index
     */
    public function fieldKeys()
    {
        $entityName = $this->entityName;
        $table = $entityName::table();
        $formattedFields = $this->fields();

        // Keys...
        $ki = 0;
        $tableKeys = [
            'primary' => [],
            'unique' => [],
            'index' => []
        ];
        $usedKeyNames = [];
        foreach ($formattedFields as $fieldInfo) {
            $fieldName = $fieldInfo['column'];

            // Determine key field name (can't use same key name twice, so we have to append a number)
            $fieldKeyName = $table . '_' . $fieldName;
            while (in_array($fieldKeyName, $usedKeyNames)) {
                $fieldKeyName = $fieldName . '_' . $ki;
            }
            // Key type
            if ($fieldInfo['primary']) {
                $tableKeys['primary'][] = $fieldName;
            }
            if ($fieldInfo['unique']) {
                if (is_string($fieldInfo['unique'])) {
                    // Named group
                    $fieldKeyName = $table . '_' . $fieldInfo['unique'];
                }
                $tableKeys['unique'][$fieldKeyName][] = $fieldName;
                $usedKeyNames[] = $fieldKeyName;
            }
            if ($fieldInfo['index']) {
                if (is_string($fieldInfo['index'])) {
                    // Named group
                    $fieldKeyName = $table . '_' . $fieldInfo['index'];
                }
                $tableKeys['index'][$fieldKeyName][] = $fieldName;
                $usedKeyNames[] = $fieldKeyName;
            }
        }

        return $tableKeys;
    }

    /**
     * Get field information exactly how it is defined in the class
     *
     * @return array Array of field key => value pairs
     */
    public function fieldsDefined()
    {
        if (!isset($this->fieldsDefined)) {
            $this->fields();
        }

        return $this->fieldsDefined;
    }

    /**
     * Get field default values as defined in class field definitions
     *
     * @return array Array of field key => value pairs
     */
    public function fieldDefaultValues()
    {
        if (!isset($this->fieldDefaultValues)) {
            $this->fields();
        }

        return $this->fieldDefaultValues;
    }

    /**
     *
     */
    public function resetFields()
    {
        $this->fields = [];
        $this->fieldsDefined = [];
        $this->fieldDefaultValues = [];
        $this->relations = [];
        $this->primaryKeyField = null;
    }

    /**
     * Get defined relations
     */
    public function relations()
    {
        $this->fields();
        if (!isset($this->relations)) {
            return [];
        }

        return $this->relations;
    }

    /**
     * Get value of primary key for given row result
     */
    public function primaryKeyField()
    {
        if (!isset($this->primaryKeyField)) {
            $this->fields();
        }

        return $this->primaryKeyField;
    }

    /**
     * Check if field exists in defined fields
     *
     * @param string $field Field name to check for existence
     * @return bool
     */
    public function fieldExists($field)
    {
        return array_key_exists($field, $this->fields());
    }

    /**
     * Return field type
     *
     * @param  string $field Field name
     * @return mixed  Field type string or boolean false
     */
    public function fieldType($field)
    {
        $fields = $this->fields();

        return $this->fieldExists($field) ? $fields[$field]['type'] : false;
    }

    /**
     * Get name of table for given entity class
     *
     * @return string
     */
    public function table()
    {
        if ($this->table === null) {
            $this->fields();
        }

        return $this->table;
    }

    /**
     * Get table options for given entity class
     *
     * @return string
     */
    public function tableOptions()
    {
        if ($this->tableOptions === null) {
            $this->fields();
        }

        return $this->tableOptions;
    }

    /**
     * Get name of custom mapper for given entity class
     *
     * @return string
     */
    public function mapper()
    {
        if ($this->mapper === null) {
            $this->fields();
        }

        return $this->mapper;
    }
}
