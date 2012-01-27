<?php



/**
 * Клас 'plg_Modified' - Поддръжка на modifiedOn и modifiedBy
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
class plg_Modified extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$invoker)
    {
        // Добавяне на необходимите полета
        $invoker->FLD('modifiedOn', 'datetime(format=smartTime)', 'caption=Модифициране->На,input=none');
        $invoker->FLD('modifiedBy', 'key(mvc=core_Users)', 'caption=Модифициране->От,input=none');
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    function on_BeforeSave(&$invoker, &$id, &$rec, &$fields = NULL)
    {
        $fieldsArr = arr::make($fields, TRUE);
        
        // Определяме кой е модифицирал записа
        if (!isset($rec->modifieddBy) || !$fieldsArr['modifiedBy']) {
            $rec->modifiedBy = Users::getCurrent();
            
            if (!$rec->modifiedBy) {
                $rec->modifiedBy = 0;
            }
        }
        
        // Записваме момента на създаването
        if (!isset($rec->modifiedOn) || !$fieldsArr['modifiedOn']) {
            $rec->modifiedOn = dt::verbal2Mysql();
        }
    }
    
    
    /**
     * Добавя ново поле, което съдържа датата, в чист вид
     */
    function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->modifiedDate = dt::mysql2verbal($rec->modifiedOn, 'd-m-Y');
    }
}