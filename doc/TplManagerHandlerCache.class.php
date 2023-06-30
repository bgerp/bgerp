<?php

/**
 * Клас 'doc_TplManagerHandlerCache' - Кеш на обработвачите на шаблони
 *
 *
 * @category  bgerp
 * @package   core
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class doc_TplManagerHandlerCache extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Кеш на обработвачите на шаблони';


    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Кеш на обработвачите на шаблони';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';


    /**
     * Кой може да добавя
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да редактира
     */
    public $canEdit = 'no_one';


    /**
     * Кой може да изтрива
     */
    public $canDelete = 'no_one';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created,doc_Wrapper,plg_Sorting';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'classId,objectId,key,data,createdOn,createdBy';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('classId', 'class(interface=doc_DocumentIntf,select=title,allowEmpty)', 'caption=Документ');
        $this->FLD('objectId', 'int', 'caption=Ид');
        $this->FLD('key', 'varchar', 'caption=Ключ');
        $this->FLD('data', 'blob(16777215,serialize,compress)', 'caption=Данни');

        $this->setDbUnique('classId,objectId,key');
    }


    /**
     * Записва/обновява стойност в модела
     *
     * @param mixed $classId  - клас
     * @param int $objectId   - ид на обект
     * @param string $key     - ключ
     * @param stdClass $data  - кеширани данни
     * @return int $id        - ид на създадения/обновения кеш
     */
    public static function set($classId, $objectId, $key, $data)
    {
        $class = cls::get($classId);
        $rec = (object)array('classId' => $class->getClassId(), 'objectId' => $objectId, 'key' => $key, 'data' => $data);

        $id = static::save($rec, null, 'REPLACE');

        return $id;
    }


    /**
     * Има ли кеширана стойност за този обект
     *
     * @param mixed $classId  - клас
     * @param int $objectId   - ид на обект
     * @param string $key     - ключ
     * @return mixed          - кеширания обект, ако има такъв
     */
    public static function get($classId, $objectId, $key)
    {
        $class = cls::get($classId);
        $data = static::fetchField(array("#classId = {$class->getClassId()} AND #objectId = {$objectId} AND #key = '[#1#]'", $key), 'data');

        return isset($data) ? $data : null;
    }
}