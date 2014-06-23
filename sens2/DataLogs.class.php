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
     * Заглавие
     */
    var $title = 'Лог на индикаторите';
    
    
    /**
     * На колко време ще се актуализира листа
     */
    var $refreshRowsTime = 25000;
    
    
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
    var $listFields = 'indicatorId, value, time';
    
    
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
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
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
        
    }
    
    
    /**
     * Изпълнява се след подготовката на редовете на листовия изглед
     */
    static function on_AfterPrepareListRows($mvc, $data, $data)
    { 
        foreach($data->rows as $id => &$row) {
            $row->value .= "<span class='measure'>" . 
                type_Varchar::escape(sens2_Indicators::getUom($data->recs[$id]->indicatorId)) . "</span>";
        }
    }

    
    /**
     * Изпълнява се след начално установяване(настройка) на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
    
    }
}
