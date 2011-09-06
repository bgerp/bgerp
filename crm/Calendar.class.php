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
    var $listFields = 'date,event=Събитие';

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
        $this->FLD('date', new type_Date(array('cellAttr' => 'nowrap')), 'caption=Дата');
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
        
        if($from = $data->listFilter->rec->from) {
            $data->query->where("#date >= date('$from')");
        }
    }


    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('from', 'date', 'caption=От,input,silent');
        $data->listFilter->setdefault('from',  date('Y-m-d'));
 
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'from';
        
        $data->listFilter->input('from', 'silent');

     }


    function recToVerbal($rec)
    {
        $row = parent::recToVerbal($rec);
        
        $row->date  = dt::mysql2verbal($rec->date, "d-m-Y, D");
        if(dt::isHoliday($rec->date)) {
            $row->date = "<div style='color:green'>" . $row->date . "</div>";
        }
        $inst = cls::getInterface('crm_CalendarEventsSourceIntf', $rec->classId);

        $row->event = $inst->getVerbalCalendarEvent($rec->type, $rec->objectId, $rec->date);

        $today = date('Y-m-d');
        $tommorow = date('Y-m-d', time()+24*60*60);
        $dayAT = date('Y-m-d', time() + 48*60*60);
        
        if($rec->date == $today) {
            $row->ROW_ATTR = " style='background-color:#ffcc99;'";
        } elseif($rec->date == $tommorow) {
            $row->ROW_ATTR = " style='background-color:#ccffff;'";
        } elseif($rec->date == $dayAT) {
            $row->ROW_ATTR = " style='background-color:#ccffcc;'";
        } elseif($rec->date < $today) {
            $row->ROW_ATTR = " style='background-color:#ccc;'";
        }

        return $row;
    }
}