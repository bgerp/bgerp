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
    public $loadList = 'plg_RowTools, sens2_Wrapper, plg_Sorting, plg_RefreshRows, plg_AlignDecimals';

    
    /**
     * Заглавие
     */
    public $title = 'Записи на индикаторите';
    
    
    /**
     * На колко време ще се актуализира листа
     */
    public $refreshRowsTime = 25000;
    
    
    /**
     * Права за запис
     */
    public $canWrite = 'debug';
    
    
    /**
     * Права за четене
     */
    public $canRead = 'ceo,sens, admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,admin,sens';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,admin,sens';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 100;
    
    
    /**
     * Полета за еденичен изглед
     */
    public $listFields = 'id,indicatorId, value, time';
    
    
    /**
     * Описание на модела
     */
    function description()
    { 
        $this->FLD('indicatorId', 'key(mvc=sens2_Indicators, select=port)', 'caption=Индикатор');
        $this->FLD('value', 'double(minDecimals=0, maxDecimals=4)', 'caption=Стойност, chart=ay');
        $this->FLD('time', 'datetime', 'caption=Към момент');
        
        $this->setDbIndex('time');
    }
    
    
    /**
     * Добавя запис в логовете
     */
    public static function addValue($indicatorId, $value, $time)
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
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->FNC('groupBy', 'enum(all=Без осредняване,howr=По часове,day=По дни,dayMax=Макс. дневни,dayMin=Мин. дневни, week=По седмици)', 'caption=Осредняване,input');
        $data->listFilter->toolbar->addSbBtn('Филтър');
        $data->listFilter->view = 'horizontal';
        
        $data->query->orderBy('#time', 'DESC');
    }
    
    
    /**
     * Извиква се след подготовката на вербалните данни
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
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
    public static function on_AfterPrepareListRows($mvc, $data, $data)
    { 
    	if(is_array($data->rows)) {
            foreach($data->rows as $id => &$row) {
                $row->value .= "<span class='measure'>" . 
                    type_Varchar::escape(sens2_Indicators::getUom($data->recs[$id]->indicatorId)) . "</span>";
            }
    	}
    }
}