<?php


/**
 * Лог за стойностите на входовете и изходите на контролерите
 *
 *
 * @category  bgerp
 * @package   sens2
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sens2_DataLogs extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, sens2_Wrapper, plg_Sorting, plg_RefreshRows, plg_AlignDecimals';
    
    
    /**
     * Интерфейси, които имплементира класа
     */
    var $interfaces = 'frame_ReportSourceIntf';

    
    /**
     * Заглавие
     */
    var $title = 'Записи на индикаторите';
    
    
    /**
     * На колко време ще се актуализира листа
     */
    var $refreshRowsTime = 25000;
    
    
    /**
     * Права за запис
     */
    var $canWrite = 'debug';
    
    
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo, sens, admin';
    
    
    /**
     * Права за четене
     */
    var $canRead = 'ceo,sens, admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,admin,sens';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,admin,sens';
    
    
    /**
     * Брой записи на страница
     */
    var $listItemsPerPage = 100;
    
    
    /**
     * @todo Чака за документация...
     */
    var $listFields = 'id,indicatorId, value, time';
    
    
    /**
     * Описание на модела
     */
    function description()
    { 
        $this->FLD('indicatorId', 'key(mvc=sens2_Indicators, select=port)', 'caption=Контролер::Порт');
        $this->FLD('value', 'double(minDecimals=0, maxDecimals=4)', 'caption=Показания, chart=ay');
        $this->FLD('time', 'datetime', 'caption=Време ');
        
        $this->setDbIndex('time');
    }
    
    
    /**
     * Добавя запис в логовете
     */
    static function addValue($indicatorId, $value, $time)
    {
        $rec = (object) array('indicatorId' => $indicatorId, 'value' => $value, 'time' => $time);

        self::save($rec);  

        return $rec->id;
    }
    
    
    /**
     * Изпълнява се след подготовката на списъчния филтър
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        
        $data->listFilter->FNC('groupBy', 'enum(all=Без осредняване,howr=По часове,day=По дни,dayMax=Макс. дневни,dayMin=Мин. дневни, week=По седмици)', 'caption=Осредняване,input');
        //$data->listFilter->FNC('period', 'enum(all=Период,day=Последни 24 часа,week=Последна седмица,month=Последен месец,quarter=Последни 3 мес.)', 'caption=Период,input');
        //$data->listFilter->showFields = 'paramId,groupBy,period';
        
        $data->listFilter->toolbar->addSbBtn('Филтър');
        
        $data->listFilter->view = 'horizontal';
        

        
        $data->query->orderBy('#time', 'DESC');
    }
    
    
    /**
     * Извиква се след подготовката на вербалните данни
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->indicatorId = sens2_Indicators::getTitleById($rec->indicatorId);
        if($rec->time) {
            $color = dt::getColorByTime($rec->time);
            $row->time = ht::createElement('span', array('style' => "color:#{$color}"), $row->time);
        }

    }
    
    
    /**
     * Изпълнява се след подготовката на редовете на листовия изглед
     */
    static function on_AfterPrepareListRows($mvc, $data, $data)
    { 
    	if(is_array($data->rows)) {
            foreach($data->rows as $id => &$row) {
                $row->value .= "<span class='measure'>" . 
                    type_Varchar::escape(sens2_Indicators::getUom($data->recs[$id]->indicatorId)) . "</span>";
            }
    	}
    }

    
    /**
     * Изпълнява се след начално установяване(настройка) на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
    
    }


    /**
     * Имплементиране на интерфейсен метод (@see frame_ReportSourceIntf)
     */
    function prepareReportForm(core_Form $form)
    {
        $form->FLD('from', 'datetime', 'caption=От,mandatory');
        $form->FLD('to', 'datetime', 'caption=До,mandatory');
        $form->FLD('indicators', 'keylist(mvc=sens2_Indicators,select=title)', 'caption=Сензори,mandatory');
    }


    /**
     * Имплементиране на интерфейсен метод (@see frame_ReportSourceIntf)
     */
    function checkReportForm(core_Form $form)
    {
    }


    /**
     * Имплементиране на интерфейсен метод (@see frame_ReportSourceIntf)
     */
    function prepareReportData($filter)
    {
        $data = new StdClass();
        $data->rec = $filter;
        
        $DateTime = cls::get('type_Datetime');
        $KeyList = cls::get('type_KeyList', array('params' => array('mvc' => 'sens2_Indicators', 'select' => 'title')));
    
        if(!strpos($filter->to, ' ')) {
        	$filter->to .= ' 23:59:59';
        }
        
        $data->row = new stdClass();
        $data->row->from = $DateTime->toVerbal($filter->from);
        $data->row->to = $DateTime->toVerbal($filter->to);
        $data->row->indicators = $KeyList->toVerbal($filter->indicators);
        
        $query = self::getQuery();

        $query->where(array("#time >= '[#1#]' AND #time <= '[#2#]'", $filter->from, $filter->to));

        $query->in("indicatorId", keylist::toArray($filter->indicators));

        while($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
        }
 
        return $data;
    }


    /**
     * Имплементиране на интерфейсен метод (@see frame_ReportSourceIntf)
     */
    function renderReportData($data)
    {
        $layout = new ET(getFileContent('sens2/tpl/ReportLayout.shtml'));
        
        $layout->placeObject($data->row);

        if(is_array($data->recs)) {
            foreach($data->recs as $id => $rec) {
                $data->rows[$id] = self::recToVerbal($rec);
                $data->rows[$id]->time = str_replace(' ', '&nbsp;', $data->rows[$id]->time);
            }

            $this->invoke('AfterPrepareListRows', array($data, $data));

            $table = cls::get('core_TableView');
 
            $layout->append($table->get($data->rows, 'time=Време,indicatorId=Индикатор,value=Стойност'), 'data');
        }

        return $layout;
    }


    /**
     * Имплементиране на интерфейсен метод (@see frame_ReportSourceIntf)
     */
    function canSelectSource($userId = NULL)
    {
    	return core_Users::haveRole($this->canSelectSource, $userId);
    }
}