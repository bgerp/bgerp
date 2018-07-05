<?php

/**
 * Клас 'plg_Created' - Поддръжка на createdOn и createdBy
 *
 *
 * @category  ef
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class plg_Created extends core_Plugin
{

    /**
     * Извиква се след описанието на модела
     */
    public static function on_AfterDescription(&$invoker)
    {
        // Добавяне на необходимите полета
        $invoker->FLD('createdOn', 'datetime(format=smartTime)', 'caption=Създаване||Created->На, notNull, input=none');
        $invoker->FLD('createdBy', 'key(mvc=core_Users)', 'caption=Създаване||Created->От||By, notNull, input=none');
        
        // По подразбиране никой не може да редактира данни, записани от системата
        setIfNot($invoker->canEditsysdata, 'no_one');
        setIfNot($invoker->canDeletesysdata, 'no_one');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($requiredRoles != 'no_one' && is_object($rec) && $rec->id && $rec->createdBy == core_Users::SYSTEM_USER) {
            if ($action == 'edit') {
                $requiredRoles = $mvc->getRequiredRoles('editsysdata', $rec, $userId);
            }
            if ($action == 'delete') {
                $requiredRoles = $mvc->getRequiredRoles('deletesysdata', $rec, $userId);
            }
        }
    }
    

    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    public static function on_BeforeSave(&$invoker, &$id, &$rec, &$fields = null, &$mode = null)
    {
        // Записваме полетата, ако записът е нов и дали трябва да има createdOn и createdBy
        if (!$rec->id || strtolower($mode) == 'replace') {
            if ($fields) {
                $fieldsArr = arr::make($fields, true);
                $mustHaveCreatedBy = isset($fieldsArr['createdBy']);
                $mustHaveCreatedOn = isset($fieldsArr['createdOn']);
            } else {
                $mustHaveCreatedBy = true;
                $mustHaveCreatedOn = true;
            }
            
            // Определяме кой е създал продажбата
            if (!isset($rec->createdBy) && $mustHaveCreatedBy) {
                $rec->createdBy = Users::getCurrent();

                if (!$rec->createdBy) {
                    $rec->createdBy = core_Users::ANONYMOUS_USER;
                }
            }
            
            // Записваме момента на създаването
            if (!isset($rec->createdOn) && $mustHaveCreatedOn) {
                $rec->createdOn = dt::verbal2Mysql();
            }
        }
    }


    /**
     * След поготовката на формата, премахва възможността за редакция на системни полета
     */
    public static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        if ($data->form->rec->createdBy == core_Users::SYSTEM_USER && $mvc->protectedSystemFields) {
            $mvc->protectedSystemFields = arr::make($mvc->protectedSystemFields, true);
            
            foreach ($data->form->fields as &$f) {
                if ($mvc->protectedSystemFields[$f->name]) {
                    $f->input = 'none';
                }
            }
        }
    }
}
