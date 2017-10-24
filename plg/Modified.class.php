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
    public static function on_AfterDescription(&$invoker)
    {
        // Добавяне на необходимите полета
        $invoker->FLD('modifiedOn', 'datetime(format=smartTime)', 'caption=Модифициране||Modified->На,input=none,forceField');
        $invoker->FLD('modifiedBy', 'key(mvc=core_Users)', 'caption=Модифициране||Modified->От||By,input=none,forceField');
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    public static function on_BeforeSave(&$invoker, &$id, &$rec, &$fields = NULL)
    {
        if(!$rec->_notModified) {
            // Определяме кой е модифицирал записа
            $rec->modifiedBy = Users::getCurrent();
            
            // Записваме момента на създаването
            $rec->modifiedOn = dt::verbal2Mysql();
        }
    }
    
    
    /**
     * Добавя ново поле, което съдържа датата, в чист вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {   
        if($rec->modifiedBy == -1) {
            $row->modifiedBy = core_Setup::get('SYSTEM_NICK');
        } elseif($rec->modifiedBy == 0) {
            $row->modifiedBy = '@anonym';
        } else {
            $row->modifiedBy = core_Users::getVerbal($rec->modifiedBy, 'nick');
        }
        $row->modifiedDate = dt::mysql2verbal($rec->modifiedOn, 'd-m-Y');
    }


    /**
     * Изпълнява се след инициализиране на модела
     */
    public static function on_AfterSetupMVC($mvc, &$res)
    {
        if($mvc->count('1=1') && !$mvc->count("#modifiedOn > '1971-01-01 00:00:00'")) {
            $query = $mvc->getQuery();
            $query->show('createdOn,createdBy,modifiedOn');
            while($rec = $query->fetch()) {
                if(!$rec->modifiedOn) {
                    $rec->modifiedOn = $rec->createdOn;
                    $rec->modifiedBy = $rec->createdBy;
                    $mvc->save_($rec, 'modifiedOn,modifiedBy');
                    $modRecs++;
                }
            }
        }

        if($modRecs) {
            $res .= "<li style='color:green'>Обновено времето за модифициране на $modRecs запис(а)</li>";
        }
    }
}
