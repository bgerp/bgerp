<?php


/**
 * Клас 'cal_Tasks' - Документ - задача
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cal_Tasks extends core_Master
{
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, cal_Wrapper, doc_DocumentPlg, doc_ActivatePlg, plg_Printing, bgerp_plg_GroupByDate, doc_SharablePlg';
    

    /**
     * Името на полито, по което плъгина GroupByDate ще групира редовете
     */
    var $groupByDateField = 'timeStart';
    
    
    /**
     * Заглавие
     */
    var $title = "Задачи";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Задача";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, title, timeStart=Начало, repeat=Повторение, timeNextRepeat';
    
    
    /**
     * Поле в което да се показва иконата за единичен изглед
     */
    var $rowToolsSingleField = 'title';
    
    
    /**
     * Кой може да чете?
     */
    var $canRead = 'admin,doc';
    
    
    /**
     * Кой може да го промени?
     */
    var $canEdit = 'admin,doc';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin,doc';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin,doc';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'admin,doc';
    
    
    /**
     * Кой има право да приключва?
     */
    var $canChangeTaskState = 'admin, doc';
    
    
    /**
     * Кой има право да затваря задачите?
     */
    var $canClose = 'admin, doc';
    
    
    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/sheduled-task-icon.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'cal/tpl/SingleLayoutTasks.shtml';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Tsk";
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title',    'varchar(128)', 'caption=Заглавие,mandatory,width=100%');
        $this->FLD('priority', 'enum(low=нисък,
                                    normal=нормален,
                                    high=висок,
                                    critical=критичен)', 
            'caption=Приоритет,mandatory,maxRadio=4,columns=4');
        $this->FLD('description',      'richtext', 'caption=Описание,mandatory');
        $this->FLD('sharedUsers', 'keylist(mvc=core_Users,select=names)', 'caption=Отговорници,mandatory');
        
        // Начало на задачата
        $this->FLD('timeStart', 'datetime', 'caption=Времена->Начало');
        
        // Продължителност на задачата
        $this->FLD('timeDuration', 'time', 'caption=Времена->Продължителност');
        
        // Краен срок на задачата
        $this->FLD('timeEnd', 'datetime',     'caption=Времена->Край');
        
        // Дали началото на задачата не е точно определено в рамките на деня?
        $this->FLD('allDay', 'enum(no,yes)',     'caption=Цял ден?,input=none');
        
        // Каква част от задачата е изпълнена?
        $this->FLD('progress', 'percent(min=0,max=1,decimals=0)',     'caption=Прогрес,input=none,notNull,value=0');
        
        // Колко време е отнело изпълнението?
        $this->FLD('workingTime', 'time',     'caption=Отработено време,input=none');

        // Край на задача ю, която има продължителност
        $this->FLD('reminder1',   'type_Time(suggestions=1 седмица|2 седмици|3 седмици)', 'caption=Предизвестие->Първо');
        $this->FLD('reminder2',   'type_Time(suggestions=1 ден|2 дни|3 дни)', 'caption=Предизвестие->Второ');
        $this->FLD('reminder3',   'type_Time(suggestions=10 минути|20 минути|30 минути|1 час|2 часа|3 часа)', 'caption=Предизвестие->Трето');

    }


    /**
     * Подготовка на формата за добавяне/редактиране
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {
        $data->form->setDefault('priority', 'normal');

        $rec = $data->form->rec;
 
        if($rec->allDay == 'yes') {
            list($rec->timeStart,) = explode(' ', $rec->timeStart);
        }

    }


    /**
     * Подготвяне на вербалните стойности
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $progressPx = min(100, round(100 * $rec->progress));
        $progressRemainPx = 100 - $progressPx;
        $row->progressBar = "<div style='display:inline-block;top:-5px;border-bottom:solid 10px #33f;width:{$progressPx}px;'> </div><div style='display:inline-block;top:-5px;border-bottom:solid 10px #cc9;width:{$progressRemainPx}px;'> </div>";
        
        if($rec->timeEnd && ($rec->state != 'closed' && $rec->state != 'rejected')) {
            $rec->remainingTime = round((dt::mysql2timestamp($rec->timeEnd) - time()) / 60) * 60;
            $typeTime = cls::get('type_Time');
            if($rec->remainingTime > 0) {
                $row->remainingTime = ' (' . tr('остават') . ' ' . $typeTime->toVerbal($rec->remainingTime) . ')';
            } else {
                 $row->remainingTime = ' (' . tr('просрочване с') . ' ' . $typeTime->toVerbal(-$rec->remainingTime) . ')';
            }
        }
    }


    /**
     * Показване на задачите в портала
     */
    static function renderPortal($userId = NULL)
    {

        if(empty($userId)) {
            $userId = core_Users::getCurrent();
        }
                
        // Създаваме обекта $data
        $data = new stdClass();
        
        // Създаваме заявката
        $data->query = self::getQuery();
        
        // Подготвяме полетата за показване
        $data->listFields = 'timeStart,title';
        
        // Подготвяме формата за филтриране
        // $this->prepareListFilter($data);

        $now = dt::verbal2mysql();
        
        $data->query->where("#sharedUsers LIKE '%|{$userId}|%' AND (#timeStart < '{$now}' || #timeStart IS NULL)");
        $data->query->orderBy("timeStart=DESC");
        
        // Подготвяме навигацията по страници
        self::prepareListPager($data);
        
        // Подготвяме записите за таблицата
        self::prepareListRecs($data);
 
        foreach($data->recs  as   &$rec) {
             $rec->state = '';
        }

        
        // Подготвяме редовете на таблицата
        self::prepareListRows($data);

        
        $tpl = new ET("
            [#PortalPagerTop#]
            [#PortalTable#]
          ");
        
        // Попълваме таблицата с редовете
        $tpl->append(self::renderListTable($data), 'PortalTable');

        return $tpl;
    }


    /**
     *
     */
    function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec;

        $rec->allDay = (strlen($rec->timeStart) == 10) ? 'yes' : 'no';
     }
    

    /**
     * Интерфейсен метод на doc_DocumentIntf
     *
     * @param int $id
     * @return stdClass $row
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        
        //Заглавие
        $row->title = $this->getVerbal($rec, 'title');
        
        //Създателя
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        //Състояние
        $row->state = $rec->state;
        
        //id на създателя
        $row->authorId = $rec->createdBy;
        
        return $row;
    }
    
    
    /**
     * Потребителите, с които е споделен този документ
     *
     * @return string keylist(mvc=core_Users)
     * @see doc_DocumentIntf::getShared()
     */
    static function getShared($id)
    {
        return static::fetchField($id, 'sharedUsers');
    }
       
}