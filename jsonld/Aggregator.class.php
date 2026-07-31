<?php


/**
 * Request-local колекция от JSON-LD възли за текущо рендираната страница
 *
 * Данните не се запазват между HTTP заявки и могат да идват от няколко
 * независими доставчика. Дедупликацията е по key, type + @id и накрая
 * по нормализиран хеш.
 */
class jsonld_Aggregator
{
    /**
     * Регистрирани JSON-LD възли за текущия request
     *
     * @var array
     */
    protected static $nodes = array();


    /**
     * Добавя JSON-LD възел
     *
     * @param jsonld_Node $node
     *
     * @return void
     */
    public static function add(jsonld_Node $node)
    {
        $key = self::getKey($node);

        self::$nodes[$key] = $node;
    }


    /**
     * Добавя няколко JSON-LD възела
     *
     * @param jsonld_Node[] $nodes
     *
     * @return void
     */
    public static function addArray($nodes)
    {
        foreach ($nodes as $node) {
            self::add($node);
        }
    }


    /**
     * Връща всички регистрирани JSON-LD възли
     *
     * @return jsonld_Node[]
     */
    public static function getAll()
    {
        return array_values(self::$nodes);
    }


    /**
     * Проверява дали има регистрирани обекти
     *
     * @return bool
     */
    public static function isEmpty()
    {
        return empty(self::$nodes);
    }


    /**
     * Генерира уникален ключ за JSON-LD възел
     *
     * @param jsonld_Node $node
     *
     * @return string
     */
    protected static function getKey(jsonld_Node $node)
    {
        if ($node->getKey() !== null && $node->getKey() !== '') {
            return 'key|' . $node->getKey();
        }

        if ($node->getId() !== null && $node->getId() !== '') {
            return 'id|' . $node->getType() . '|' . $node->getId();
        }

        $data = self::normalize($node->toArray());

        return 'hash|' . hash('sha256', json_encode($data));
    }


    /**
     * Нормализира масивите за детерминиран хеш
     *
     * @param mixed $value
     *
     * @return mixed
     */
    protected static function normalize($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        $isSequential = array_keys($value) === range(0, count($value) - 1);

        if (!$isSequential) {
            ksort($value);
        }

        foreach ($value as $key => $item) {
            $value[$key] = self::normalize($item);
        }

        return $value;
    }
}
