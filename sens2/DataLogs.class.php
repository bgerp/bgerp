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
    public $loadList = 'plg_RowTools, sens2_Wrapper, plg_Sorting, plg_RefreshRows, plg_AlignDecimals,plg_Chart';

    
    /**
     * Заглавие
     */
    public $title = 'Записи от IP контролерите';
    
    
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
    public function description()
    {
        $this->FLD('indicatorId', 'key(mvc=sens2_Indicators, select=title, allowEmpty, where=#state !\\= \\\'rejected\\\')', 'caption=Индикатор,input=silent,autoFilter,chart=diff');
        $this->FLD('value', 'double(minDecimals=0, maxDecimals=4)', 'caption=Стойност, chart=ay');
        $this->FLD('time', 'datetime', 'caption=Към момент,chart=ax');
        $this->FNC('groupBy', 'enum(all=Без осредняване,howr=По часове,day=По дни,dayMax=Макс. дневни,dayMin=Мин. дневни, week=По седмици)', 'caption=Осредняване,input=silent,autoFilter');

        $this->setDbIndex('time');
        
        $this->dbEngine = 'InnoDB';
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
        $data->listFilter->toolbar->addSbBtn('Филтър');
        $data->listFilter->view = 'horizontal';
        $data->listFilter->showFields = 'indicatorId,groupBy';
        $data->listFilter->input('indicatorId,groupBy', 'silent');
        
        $rec = $data->listFilter->rec;
        
        
        if ($indicatorId = $data->listFilter->rec->indicatorId) {
            $data->query->where("#indicatorId = {$indicatorId}");
        }


        if ($rec->groupBy == 'all' || !$rec->groupBy) {
            $data->query->XPR('timeGroup', 'date', '#time');
        } elseif ($rec->groupBy == 'day') {
            $data->query->XPR('timeGroup', 'date', 'DATE(#time)');
            $data->query->XPR('valueRes', 'float', 'AVG(#value)');
        } elseif ($rec->groupBy == 'dayMax') {
            $data->query->XPR('timeGroup', 'date', 'DATE(#time)');
            $data->query->XPR('valueRes', 'float', 'MAX(#value)');
            $data->query->fields['value'] = $data->query->fields['valueRes'];
        } elseif ($rec->groupBy == 'dayMin') {
            $data->query->XPR('timeGroup', 'date', 'DATE(#time)');
            $data->query->XPR('valueRes', 'float', 'MIN(#value)');
            $data->query->fields['value'] = $data->query->fields['valueRes'];
        } elseif ($rec->groupBy == 'howr') {
            $data->query->XPR('timeGroup', 'date', "DATE_FORMAT(#time,'%Y-%m-%d %H:00')");
            $data->query->XPR('valueRes', 'float', 'AVG(#value)');
            $data->query->fields['value'] = $data->query->fields['valueRes'];
        } elseif ($rec->groupBy == 'week') {
            $data->query->XPR('timeGroup', 'varchar(16)', "STR_TO_DATE(DATE_FORMAT(#time,'%x%v Monday'), '%x%v %W') ");
            $data->query->XPR('valueRes', 'float', 'AVG(#value)');
            $data->query->fields['value'] = $data->query->fields['valueRes'];
        }
        
        if ($rec->groupBy && $rec->groupBy != 'all') {
            $data->query->groupBy('indicatorId,timeGroup');
            $data->query->show('id,indicatorId,value,time,timeGroup,valueRes');
        }
       
        $data->query->orderBy('#time', 'DESC');
    }
    
    
    /**
     * Извиква се след подготовката на вербалните данни
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->indicatorId = sens2_Indicators::getTitleById($rec->indicatorId);

        if ($rec->timeGroup) {
            $row->time = $rec->timeGroup;
        }
        if ($rec->time) {
            $color = dt::getColorByTime($rec->time);
            $row->time = ht::createElement('span', array('style' => "color:#{$color}"), $row->time);
        }

        if ($rec->valueRec) {
            $rec->value = $rec->valueRec;
            $row->value = self::getVerbal($rec, 'value');
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на редовете на листовия изглед
     */
    public static function on_AfterPrepareListRows($mvc, &$res, $data)
    {
        if (is_array($data->rows)) {
            foreach ($data->rows as $id => &$row) {
                $row->value .= "<span class='measure'>" .
                    type_Varchar::escape(sens2_Indicators::fetch($data->recs[$id]->indicatorId)->uom) . '</span>';
            }
        }
    }
}
