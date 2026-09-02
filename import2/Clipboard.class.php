<?php


/**
 * Помощен клас за импорт на данни от документния клипборд
 *
 * @category  bgerp
 * @package   import2
 *
 * @since     v 0.1
 */
class import2_Clipboard
{
    /**
     * Връща наличните данни в клипборда
     *
     * @param int|null $userId
     *
     * @return array|false
     */
    public static function getVals($userId = null)
    {
        return export_Clipboard::getVals($userId);
    }


    /**
     * Връща опциите за избор на документ от клипборда
     *
     * @param array|false|null $clipboardVals
     *
     * @return array
     */
    public static function getOptions($clipboardVals = null)
    {
        if (!isset($clipboardVals)) {
            $clipboardVals = self::getVals();
        }

        $options = array();
        foreach ((array) $clipboardVals as $classId => $objects) {
            if (!cls::load($classId, true)) {

                continue;
            }

            $Class = cls::get($classId);
            foreach ((array) $objects as $objectId => $data) {
                $documentRow = $Class->getDocumentRow($objectId);
                $options[self::getSourceKey($classId, $objectId)] = !empty($documentRow->recTitle) ? $documentRow->recTitle : $documentRow->title;
            }
        }

        return $options;
    }


    /**
     * Връща ключа, с който източникът участва във формите
     */
    public static function getSourceKey($classId, $objectId)
    {
        return "{$classId}_{$objectId}";
    }


    /**
     * Разделя ключа на източника на classId и objectId
     *
     * @return array
     */
    public static function parseSourceKey($source)
    {
        $parts = explode('_', (string) $source, 2);

        return array($parts[0] ?? null, $parts[1] ?? null);
    }


    /**
     * Връща записаните данни за избрания източник
     *
     * @param string           $source
     * @param array|false|null $clipboardVals
     *
     * @return stdClass|null
     */
    public static function getData($source, $clipboardVals = null)
    {
        if (!isset($clipboardVals)) {
            $clipboardVals = self::getVals();
        }

        list($classId, $objectId) = self::parseSourceKey($source);

        return $clipboardVals[$classId][$objectId] ?? null;
    }


    /**
     * Връща редовете за избрания източник
     *
     * @return array
     */
    public static function getRows($source, $clipboardVals = null)
    {
        $data = self::getData($source, $clipboardVals);

        return (array) ($data->recs ?? array());
    }


    /**
     * Връща видимите колони и съответствието им към реалните имена на полета
     *
     * @return array
     */
    public static function getColumns($source, $clipboardVals = null)
    {
        $result = array('options' => array(), 'map' => array(), 'names' => array());
        $data = self::getData($source, $clipboardVals);
        if (!$data || !countR($data->recs ?? array())) {

            return $result;
        }

        $recs = $data->recs;
        $firstRec = reset($recs);
        foreach ((array) $firstRec as $name => $value) {
            $caption = $data->fields->fields[$name]->caption ?? $name;
            $result['options'][$caption] = $caption;
            $result['map'][$caption] = $name;
            $result['names'][$caption] = $name;
        }

        return $result;
    }


    /**
     * Намира най-подходящата колона по име на поле и заглавие
     */
    public static function findMatchingColumn($name, $caption, $columns)
    {
        $caption = mb_strtolower(trim($caption));
        $name = mb_strtolower(trim($name));

        foreach ((array) ($columns['names'] ?? array()) as $columnCaption => $columnName) {
            $columnCaptionLower = mb_strtolower(trim($columnCaption));
            $columnNameLower = mb_strtolower(trim($columnName));
            if ($columnCaptionLower == $caption || $columnNameLower == $name) {

                return $columnCaption;
            }
        }

        if (!strlen($caption)) {

            return null;
        }

        foreach ((array) ($columns['names'] ?? array()) as $columnCaption => $columnName) {
            $columnCaptionLower = mb_strtolower(trim($columnCaption));
            if (strlen($columnCaptionLower) && (strpos($caption, $columnCaptionLower) !== false || strpos($columnCaptionLower, $caption) !== false)) {

                return $columnCaption;
            }
        }

        return null;
    }
}
