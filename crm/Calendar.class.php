<?php
/**
 * Календар - регистър за датите
 * 
 * @category   Experta Framework
 * @package    crm
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class crm_Calendar extends core_Master
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Календар";
    
    
    /**
     *  Класове за автоматично зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, crm_Wrapper, plg_Sorting';
    
    
    /**
     *  Полетата, които ще видим в таблицата
     */
    var $listFields = 'id,date,event=Събитие';

    /**
     *  @todo Чака за документация...
     */
   // var $searchFields = '';
    
    
    /**
     * Кой може да пише
     */
    var $canWrite = 'no_one';
    
    
    /**
     *  Кой може да чете
     */
    var $canRead = 'crm,admin';
    
 
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        // Име на фирмата
        $this->FLD('date', 'date', 'caption=Дата');
        $this->FLD('type', 'varchar(32)', 'caption=Тип');
        $this->FLD('classId', 'class(select=title)', 'caption=Клас');
        $this->FLD('objectId', 'int', 'caption=Обект');
    }

    /**
     * Предизвиква обновяване на информацията
     */
    function updateEventsPerObject($objectId, $caller)
    {
        $classId = $caller->getClassId();

        // Изтриване на събитията до момента
        crm_Calendar::delete("#classId = '{$classId}' AND #objectId = {$objectId}");
        
        // Вземаме събитията за посочения обект
        $callerCalSrc = cls::getInterface('crm_CalendarEventsSourceIntf', $caller);

        $events = $callerCalSrc->getCalendarEvents($objectId);

        // Добавяме ги в календара
        if(count($events)) {
            foreach($events as $eRec) {
                $eRec->classId  = $classId;
                $eRec->objectId = $objectId;
                self::save($eRec);
            }
        }
    }

    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        $data->query->orderBy("#date");
    }


    function recToVerbal($rec)
    {
        $row = parent::recToVerbal($rec);
        
        $inst = cls::getInterface('crm_CalendarEventsSourceIntf', $rec->classId);

        $row->event = $inst->getVerbalCalendarEvent($rec->type, $rec->objectId, $rec->date);

        return $row;
    }
}