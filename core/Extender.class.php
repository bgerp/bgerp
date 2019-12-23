<?php


/**
 * Клас за наследяване на екстендъри на класове
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
abstract class core_Extender extends core_Master
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
     * Кой може да възстановява
     */
    public $canRestore = 'no_one';
    
    
    /**
     * Кой може да оттегля
     */
    public $canReject = 'no_one';
    
    
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
    public static function on_AfterDescription(core_Master &$mvc)
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
        if(cls::load($classId, true)){
            $Class = cls::get($classId);
            $me = cls::get(get_called_class());
            
            // Връщане на записа от екстендъра за съответния клас
            return self::fetch("#{$me->mainClassFieldName} = {$Class->getClassId()} && #{$me->mainIdFieldName} = {$objectId}");
        }
        
        return false;
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
        $editUrl = null;
        
        // Подмяна на едит урл-то да е към ембедъра
        if($Extended = $mvc->getExtended($rec)){
            if($Extended->haveRightFor('edit')) {
                $editUrl = array($Extended->getInstance(), 'edit', 'id' => $Extended->that, 'ret_url' => true);
            }
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
        $deleteUrl = null;
        
        // Подмяна на урл-то за изтриван да е към ембедъра
        if($Extended = $mvc->getExtended($rec)){
            if($Extended->haveRightFor('delete')) {
                $deleteUrl = array($Extended->getInstance(), 'delete', 'id' => $Extended->that, 'ret_url' => true);
            }
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        $addUrl = $mvc->getListAddUrl();
        if(count($addUrl) && !Request::get('Rejected', 'int')){
            $data->toolbar->addBtn('Нов запис', $addUrl, false, "ef_icon = img/16/star_2.png,title=Добавяне на нов {$mvc->singleTitle}");
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $editUrl = $mvc->getEditUrl($data->rec);
        if(count($editUrl)){
            $data->toolbar->addBtn('Редакция', $editUrl, 'id=btnEdit', 'ef_icon = img/16/edit-icon.png,title=Редактиране на записа');
        }
    }
    
    
    /**
     * Какво да е дефолтното урл, за добавяне от листовия изглед
     *
     * @return array $addUrl
     */
    protected function getListAddUrl()
    {
        return array();
    }
    
    
    /**
     * Инстанциране на референция, към разширеният обект
     * 
     * @param stdClass $rec
     * @return core_ObjectReference|NULL
     */
    public static function getExtended($rec)
    {
        $me = cls::get(get_called_class());
        if(cls::load($rec->{$me->mainClassFieldName}, true)){
            
            return new core_ObjectReference($rec->{$me->mainClassFieldName}, $rec->{$me->mainIdFieldName});
        }
        
        return null;
    }
}