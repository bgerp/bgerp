<?php



/**
 * Мениджър за логовете на сензорите
 *
 *
 * @category  bgerp
 * @package   sens
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Перманентни данни
 */
class sens_IndicationsLog extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools2, sens_Wrapper, plg_Sorting,
                      plg_Chart, Params=sens_Params, plg_RefreshRows';
    
    
    /**
     * Заглавие
     */
    var $title = 'Записи от сензорите';
    
    
    /**
     * На колко време ще се актуализира листа
     */
    var $refreshRowsTime = 15000;
    
    
    /**
     * Права за запис
     */
    var $canWrite = 'ceo,sens, admin';
    
    
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
    var $listFields = 'sensorId, paramId, value, measure, time';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('sensorId', 'key(mvc=sens_Sensors, select=title, allowEmpty)', 'caption=Сензор');
        $this->FLD('paramId', 'key(mvc=sens_Params, select=param, allowEmpty)', 'caption=Параметър, chart=diff');
        $this->FLD('value', 'double(decimals=2)', 'caption=Показания, chart=ay');
        $this->EXT('measure', 'sens_Params', 'externalName=details, externalKey=paramId', 'caption=Мярка');
        $this->FLD('time', 'datetime', 'caption=Време, chart=ax');
        
        $this->setDbIndex('time');
        
        $this->dbEngine = 'InnoDB';
    }
    
    
    /**
     * Добавя запис в логовете
     */
    public static function add($sensorId, $param, $value, $measure = NULL)
    {
        $rec = new stdClass();
        $rec->sensorId = $sensorId;
        $rec->paramId = sens_Params::getIdByUnit($param);
        $rec->value = $value;
        
        //$rec->measure = $measure;
        $rec->time = dt::verbal2mysql();
        
        sens_IndicationsLog::save($rec);
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        
        $data->listFilter->FNC('groupBy', 'enum(all=Без осредняване,howr=По часове,day=По дни,dayMax=Макс. дневни,dayMin=Мин. дневни, week=По седмици)', 'caption=Осредняване,input');
        $data->listFilter->FNC('period', 'enum(all=Период,day=Последни 24 часа,week=Последна седмица,month=Последен месец,quarter=Последни 3 мес.)', 'caption=Период,input');
        $data->listFilter->showFields = 'sensorId,paramId,groupBy,period';
        
        $data->listFilter->toolbar->addSbBtn('Филтър');
        
        $data->listFilter->view = 'horizontal';
        
        $url = getCurrentUrl();
        
        unset($url['sensorId'], $url['paramId'], $url['Cmd'], $url['groupBy'], $url['period']);
        
        $data->listFilter->setHidden($url);
        
        $rec = $data->listFilter->input();
        
        // $data->query->groupBy('sensorId,paramId');
        
        if($rec->groupBy == 'all' || !$rec->groupBy) {
            $data->query->XPR('timeGroup', 'date', '#time');
        } elseif($rec->groupBy == 'day') {
            $data->query->XPR('timeGroup', 'date', 'DATE(#time)');
        } elseif($rec->groupBy == 'dayMax') {
            $data->query->XPR('timeGroup', 'date', 'DATE(#time)');
            $data->query->XPR('valueRes', 'float', 'MAX(#value)');
            $data->query->fields['value'] = $data->query->fields['valueRes'];
        } elseif($rec->groupBy == 'dayMin') {
            $data->query->XPR('timeGroup', 'date', 'DATE(#time)');
            $data->query->XPR('valueRes', 'float', 'MIN(#value)');
            $data->query->fields['value'] = $data->query->fields['valueRes'];
        } elseif($rec->groupBy == 'howr') {
            $data->query->XPR('timeGroup', 'date', "DATE_FORMAT(#time,'%Y-%m-%d %H:00')");
            $data->query->XPR('valueRes', 'float', 'AVG(#value)');
            $data->query->fields['value'] = $data->query->fields['valueRes'];
        } elseif($rec->groupBy == 'week') {
            $data->query->XPR('timeGroup', 'varchar(16)', "DATE_FORMAT(#time,'%u-%Y')");
            $data->query->XPR('valueRes', 'float', 'AVG(#value)');
            $data->query->fields['value'] = $data->query->fields['valueRes'];
        }
        
        if($rec->groupBy && $rec->groupBy != 'all') {
            $data->query->groupBy('sensorId,paramId,timeGroup');
        }
        
        if($rec) {
            switch ($rec->period) {
                case 'day' :
                    $data->query->where("#time > '" . date('Y-m-d H:i:s', strtotime('-1 day')) . "'");
                    break;
                case 'week' :
                    $data->query->where("#time > '" . date('Y-m-d H:i:s', strtotime('-1 week')) . "'");
                    break;
                case 'month' :
                    $data->query->where("#time > '" . date('Y-m-d H:i:s', strtotime('-1 month')) . "'");
                    break;
                case 'quarter' :
                    $data->query->where("#time > '" . date('Y-m-d H:i:s', strtotime('-3 month')) . "'");
                    break;
            }
            
            if($rec->sensorId) {
                $data->query->where("#sensorId = {$rec->sensorId}");
            }
            
            if($rec->paramId) {
                $data->query->where("#paramId = {$rec->paramId}");
                $data->listFields['value'] = $mvc->Params->fetchField($rec->paramId, 'param');
            }
        }
        
        $data->query->orderBy('#time', 'DESC');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->time = $rec->timeGroup;
    }
    
    
    /**
     * Изпълнява се след начално установяване(настройка) на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
    
    }
}
