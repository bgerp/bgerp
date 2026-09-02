<?php


/**
 * Generic vendor-neutral representation of one Schema.org node
 *
 * Property values may contain scalars, arrays and nested jsonld_Node objects.
 * The ID is the semantic JSON-LD @id, while the key is used internally for
 * aggregation and deduplication.
 */
class jsonld_Node
{
    /**
     * Schema.org type
     *
     * @var string
     */
    protected $type;


    /**
     * Schema.org properties
     *
     * @var array
     */
    protected $properties = array();


    /**
     * Semantic JSON-LD identifier
     *
     * @var string|null
     */
    protected $id;


    /**
     * Internal identity/deduplication key
     *
     * @var string|null
     */
    protected $key;


    /**
     * @param string      $type
     * @param array       $properties
     * @param string|null $id
     * @param string|null $key
     */
    public function __construct(
        $type,
        array $properties = array(),
        $id = null,
        $key = null
    ) {
        if (!is_string($type)
            || !preg_match('/^[A-Z][A-Za-z0-9]*$/', $type)) {
            throw new InvalidArgumentException(
                'Invalid Schema.org type: ' . (string) $type
            );
        }

        $this->type = $type;
        $this->properties = $properties;
        $this->id = $id;
        $this->key = $key;
    }


    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }


    /**
     * @return string|null
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }


    /**
     * @return string|null
     */
    public function getKey()
    {
        return $this->key;
    }


    /**
     * Returns a recursively normalized JSON-compatible representation
     *
     * @return array
     */
    public function toArray()
    {
        $result = array('@type' => $this->type);

        if ($this->id !== null && $this->id !== '') {
            $result['@id'] = $this->id;
        }

        foreach ($this->properties as $property => $value) {
            $result[$property] = static::normalizeValue($value);
        }

        return $result;
    }


    /**
     * @param mixed $value
     *
     * @return mixed
     */
    protected static function normalizeValue($value)
    {
        if ($value instanceof self) {
            return $value->toArray();
        }

        if (is_array($value)) {
            $result = array();

            foreach ($value as $key => $item) {
                $result[$key] = static::normalizeValue($item);
            }

            return $result;
        }

        if (is_object($value) || is_resource($value)) {
            throw new InvalidArgumentException(
                'JSON-LD node properties must contain only nodes, arrays, '
                . 'scalars or null'
            );
        }

        return $value;
    }
}
