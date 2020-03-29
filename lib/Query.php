<?php

namespace Spot;

/**
 * Query Object - Used to build adapter-independent queries PHP-style
 *
 * @package Spot
 * @author Vance Lucas <vance@vancelucas.com>
 */
class Query extends BaseQuery implements \Countable, \IteratorAggregate, \ArrayAccess, \JsonSerializable
{
    // ===================================================================

    /**
     * SPL Countable function
     * Called automatically when attribute is used in a 'count()' function call
     *
     * Executes separate query with COUNT(*), and drops and ordering (not
     * important for counting)
     *
     * @return int
     */
    public function count()
    {
        $countCopy = clone $this->builder();
        $stmt = $countCopy->select('COUNT(*)')->resetQueryPart('orderBy')->execute();

        return (int)$stmt->fetchColumn(0);
    }

    /**
     * SPL IteratorAggregate function
     * Called automatically when attribute is used in a 'foreach' loop
     *
     * @return \Spot\Entity\Collection
     */
    public function getIterator()
    {
        // Execute query and return result set for iteration
        $result = $this->execute();

        return ($result !== false) ? $result : [];
    }

    /**
     * Convenience function passthrough for Collection
     *
     * @param string|null $keyColumn
     * @param string|null $valueColumn
     * @return array
     */
    public function toArray($keyColumn = null, $valueColumn = null)
    {
        $result = $this->execute();

        return ($result !== false) ? $result->toArray($keyColumn, $valueColumn) : [];
    }

    /**
     * JsonSerializable
     *
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Return the first entity matched by the query
     *
     * @return mixed Spot_Entity on success, boolean false on failure
     */
    public function first()
    {
        $result = $this->limit(1)->execute();

        return ($result !== false) ? $result->first() : false;
    }

    /**
     * Get raw SQL string from built query
     *
     * @return string
     */
    public function toSql()
    {
        if ($this->_noQuote) {
            $escapeCharacter = $this->mapper()->connection()->getDatabasePlatform()->getIdentifierQuoteCharacter();
            return str_replace($escapeCharacter, '', $this->builder()->getSQL());
        }

        return $this->builder()->getSQL();
    }

    /**
     * Escape/quote direct user input
     *
     * @param string $string
     * @return string
     */
    public function escape($string)
    {
        if ($this->_noQuote) {
            return $string;
        }

        return $this->mapper()->connection()->quote($string);
    }

    /**
     * Get field name with table alias appended
     * @param string $field
     * @param bool $escaped
     * @return string
     */
    public function fieldWithAlias($field, $escaped = true)
    {
        $fieldInfo = $this->_mapper->entityManager()->fields();
        
        // Detect function in field name
        $field = trim($field);
        $function = strpos($field, '(');
        if ($function) {
            foreach ($fieldInfo as $key => $currentField) {
                $fieldFound = strpos($field, $key);
                if ($fieldFound) {
                    $functionStart = substr($field, 0, $fieldFound);
                    $functionEnd = substr($field, $fieldFound + strLen($key));
                    $field = $key;
                    break;
                }
            }
        }

        // Determine real field name (column alias support)
        if (isset($fieldInfo[$field])) {
            $field = $fieldInfo[$field]['column'];
        }

        $field = $this->_tableName . '.' . $field;
        $field = $escaped ? $this->escapeIdentifier($field) : $field;
        
        $result = $function ? $functionStart : '';
        $result .= $field;
        $result .= $function ? $functionEnd : '';

        return $result;
    }

    /**
     * SPL - ArrayAccess
     *
     * @inheritdoc
     */
    public function offsetExists($key)
    {
        $results = $this->getIterator();

        return isset($results[$key]);
    }

    /**
     * SPL - ArrayAccess
     *
     * @inheritdoc
     */
    public function offsetGet($key)
    {
        $results = $this->getIterator();

        return $results[$key];
    }

    /**
     * SPL - ArrayAccess
     *
     * @inheritdoc
     */
    public function offsetSet($key, $value)
    {
        $results = $this->getIterator();
        if ($key === null) {
            return $results[] = $value;
        } else {
            return $results[$key] = $value;
        }
    }

    /**
     * SPL - ArrayAccess
     *
     * @inheritdoc
     */
    public function offsetUnset($key)
    {
        $results = $this->getIterator();
        unset($results[$key]);
    }
}
