<?php


/**
 * Kлас за наследяване на екстендъри на класове
 *
 *
 * @category  bgerp
 * @package   core
 *
 * @author    Ivelin Dimov <ivelin_pdimob@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class core_Extender extends core_Manager
{
    /**
     * Кой може да редактира
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой може да изтрива
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да редактира
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Име на полето за класа на ембедъра
     * 
     * @var string
     */
    public $mainClassFieldName = 'classId';
    
    
    /**
     * Име на полето за ид-то на ембедъра
     * 
     * @var string
     */
    public $mainIdFieldName = 'objectId';
    
    
    /**
     * Кои полета от екстендъра да се добавят към класа
     *
     * @var string
     */
    protected $extenderFields;
    
    
    /**
     * Какъв да е интерфейса на позволените класа
     *
     * @var string
     */
    protected $extenderClassInterfaces;
    
    
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        expect($mvc->extenderClassInterfaces);
        
        // Добавяне на задължителните полета за информация на екстендъра
        $mvc->FLD($mvc->mainClassFieldName, "class(interface={$mvc->extenderClassInterfaces})", 'caption=Клас,forceField');
        $mvc->FLD($mvc->mainIdFieldName, 'int', 'caption=Ид,forceField,tdClass=leftCol');
        
        $mvc->setDbUnique('classId,objectId');
    }
    
    
    /**
     * Кои са полетата на ембедедъра
     *
     * @return array $res
     */
    public function getExtenderFields()
    {
        $fieldNames = arr::make($this->extenderFields, true);
        $fields = $this->selectFields();
        $res = array_intersect_key($fields, $fieldNames);
        
        return $res;
    }
    
    
    /**
     * Извлича записа на екстендера
     * 
     * @param mixed $classId
     * @param int $objectId
     * @return stdClass|false
     */
    public static function getRec($classId, $objectId)
    {
        $Class = cls::get($classId);
        $me = cls::get(get_called_class());
        
        // Връщане на записа от екстендъра за съответния клас
        return self::fetch("#{$me->mainClassFieldName} = {$Class->getClassId()} && #{$me->mainIdFieldName} = {$objectId}");
    }
    
    
    /**
     * Реализация по подразбиране на метода getEditUrl()
     *
     * @param core_Mvc $mvc
     * @param array    $editUrl
     * @param stdClass $rec
     */
    protected static function on_AfterGetEditUrl($mvc, &$editUrl, $rec)
    {
        $Embedder = cls::get($rec->{$mvc->mainClassFieldName});

        // Подмяна на едит урл-то да е към ембедъра
        $editUrl = null;
        if($Embedder->haveRightFor('edit', $rec->{$mvc->mainIdFieldName})) {
            $editUrl = array($Embedder, 'edit', 'id' => $rec->{$mvc->mainIdFieldName}, 'ret_url' => true);
        }
    }
    
    
    /**
     * Реализация по подразбиране на метода getDeleteUrl()
     *
     * @param core_Mvc $mvc
     * @param array    $editUrl
     * @param stdClass $rec
     */
    protected static function on_AfterGetDeleteUrl($mvc, &$deleteUrl, $rec)
    {
        $Embedder = cls::get($rec->{$mvc->mainClassFieldName});
        
        // Подмяна на урл-то за изтриване да е към ембедъра
        $deleteUrl = null;
        if($Embedder->haveRightFor('delete', $rec->{$mvc->mainIdFieldName})) {
            $deleteUrl = array($Embedder, 'delete', 'id' => $rec->{$mvc->mainIdFieldName}, 'ret_url' => true);
        }
    }
}