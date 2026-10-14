<?php


/**
 * Сериализира jsonld_Node възлите в една обща JSON-LD графа
 *
 * Без външни библиотеки - възлите сами дават масива си (jsonld_Node::toArray()).
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

        $graph = array();

        foreach ($nodes as $node) {
            if (!$node instanceof jsonld_Node) {
                throw new InvalidArgumentException(
                    'jsonld_Adapter accepts only jsonld_Node objects'
                );
            }

            $graph[] = $node->toArray();
        }

        $json = json_encode(
            array(
                '@context' => 'https://schema.org',
                '@graph' => $graph,
            ),
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES |
            JSON_HEX_TAG |
            JSON_HEX_AMP |
            JSON_HEX_APOS |
            JSON_HEX_QUOT |
            JSON_INVALID_UTF8_SUBSTITUTE
        );

        if ($json === false) {
            throw new RuntimeException(
                'Error serializing JSON-LD: ' . json_last_error_msg()
            );
        }

        return $json;
    }
}
