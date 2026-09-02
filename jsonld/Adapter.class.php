<?php


/**
 * Единствената production граница към spatie/schema-org
 *
 * Преобразува рекурсивно произволни поддържани jsonld_Node типове в една
 * обща JSON-LD графа.
 */
class jsonld_Adapter
{
    /**
     * Converts vendor-neutral nodes to a JSON-LD string
     *
     * @param jsonld_Node[] $nodes
     *
     * @return string
     */
    public static function toJson(array $nodes)
    {
        if (empty($nodes)) {
            return '';
        }

        if (!core_Composer::isInUse()) {
            throw new RuntimeException(
                'Composer autoloader is not available for JSON-LD'
            );
        }

        $graph = new \Spatie\SchemaOrg\Graph();

        foreach ($nodes as $index => $node) {
            if (!$node instanceof jsonld_Node) {
                throw new InvalidArgumentException(
                    'jsonld_Adapter accepts only jsonld_Node objects'
                );
            }

            $schema = static::convertNode($node);
            $graph->add($schema, 'node-' . $index);
        }

        $json = json_encode(
            $graph->toArray(),
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES |
            JSON_HEX_TAG |
            JSON_HEX_AMP |
            JSON_HEX_APOS |
            JSON_HEX_QUOT
        );

        if ($json === false) {
            throw new RuntimeException(
                'Error serializing JSON-LD: ' . json_last_error_msg()
            );
        }

        return $json;
    }


    /**
     * Converts one neutral node to a temporary Schema.org type
     *
     * @param jsonld_Node $node
     *
     * @return \Spatie\SchemaOrg\Type
     */
    protected static function convertNode(jsonld_Node $node)
    {
        $class = 'Spatie\\SchemaOrg\\' . $node->getType();

        if (!class_exists($class)
            || !is_subclass_of($class, \Spatie\SchemaOrg\Type::class)) {
            throw new InvalidArgumentException(
                'Unsupported Schema.org type: ' . $node->getType()
            );
        }

        $schema = new $class();

        if ($node->getId() !== null && $node->getId() !== '') {
            $schema->setProperty('@id', $node->getId());
        }

        foreach ($node->getProperties() as $property => $value) {
            $schema->setProperty(
                $property,
                static::convertValue($value)
            );
        }

        return $schema;
    }


    /**
     * Recursively converts nested neutral nodes
     *
     * @param mixed $value
     *
     * @return mixed
     */
    protected static function convertValue($value)
    {
        if ($value instanceof jsonld_Node) {
            return static::convertNode($value);
        }

        if (is_array($value)) {
            $result = array();

            foreach ($value as $key => $item) {
                $result[$key] = static::convertValue($item);
            }

            return $result;
        }

        return $value;
    }
}
