<?php 


/**
 * 
 *
 * @category  bgerp
 * @package   borsa
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class borsa_Periods extends core_Manager
{
    /**
     * Заглавие на модела
     */
    public $title = 'Периоди';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'borsa, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'borsa, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'borsa, ceo';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'borsa, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'borsa, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да променя състоянието на документите
     *
     * @see plg_State2
     */
    public $canChangestate = 'borsa, ceo';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'borsa_Wrapper, plg_Created, plg_State2, plg_Sorting, plg_Modified, plg_RowTools2';
    
    
    /**
     * 
     */
    public $listFields = 'lotId, price, periodFromTo, qAvailable, qBooked, qConfirmed, modifiedOn, modifiedBy, state';
    
    
    /**
     * 
     */
    public function description()
    {
        $this->FLD('lotId', 'key(mvc=borsa_Lots,select=productName)', 'caption=Артикул, mandatory, removeAndRefreshForm=from|to, refreshForm, silent');
        $this->FLD('from', 'date', 'caption=От, mandatory, input');
        $this->FLD('to', 'date', 'caption=До, mandatory, input');
        $this->FLD('qAvailable', 'double(smartRound,decimals=2,Min=0)', 'caption=Количество->Общо, oldFieldName=qAviable, mandatory');
        $this->FLD('qBooked', 'double(smartRound,decimals=2)', 'caption=Количество->Запазено, input=none');
        $this->FLD('qConfirmed', 'double(smartRound,decimals=2)', 'caption=Количество->Потвърдено, input=none');
        
        $this->FNC('price', 'double(smartRound,decimals=2)', 'caption=Цена|* ' . acc_Periods::getBaseCurrencyCode());
        $this->FNC('periodFromTo', 'varchar', 'caption=За период');
        
        $this->setDbUnique('lotId, from, to');
    }
    
    
    /**
     * Връща съответния запис за периода
     * 
     * @param integer $lotId
     * @param DateTime $from
     * @param DateTime $to
     * 
     * @return false|stdClass
     */
    public static function getPeriodRec($lotId, $from, $to, $state = null)
    {
        if (isset($state)) {
            
            return self::fetch(array("#lotId = '[#1#]' AND #from = '[#2#]' AND #to = '[#3#]' AND #state = '[#4#]'", $lotId, $from, $to, $state));
        } else {
            
            return self::fetch(array("#lotId = '[#1#]' AND #from = '[#2#]' AND #to = '[#3#]'", $lotId, $from, $to));
        }
    }
    
    
    /**
     * 
     * @param borsa_Periods $mvc
     * @param stdClass $rec
     */
    function on_CalcPeriodFromTo($mvc, $rec)
    {
        $rec->periodFromTo = borsa_Lots::getPeriodVerb(array('bPeriod' => $rec->from, 'ePeriod' => $rec->to));
    }
    
    
    /**
     *
     * @param borsa_Periods $mvc
     * @param stdClass $rec
     */
    function on_CalcPrice($mvc, $rec)
    {
        $pArr = cls::get('borsa_Lots')->getChangePeriods($rec->lotId);
        foreach ($pArr as $pRec) {
            if (($pRec['bPeriod'] == $rec->from) && ($pRec['ePeriod'] == $rec->to)) {
                if ($pRec['price']) {
                    $rec->price = $pRec['price'];
                }
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('from', 'DESC');
        $data->query->orderBy('to', 'DESC');
        $data->query->orderBy('modifiedOn', 'DESC');
        
        $data->listFilter->setFieldTypeParams('lotId', array('allowEmpty' => 'allowEmpty'));
        
        $data->listFilter->showFields = 'lotId';
        
        $data->listFilter->input('lotId');
        
        if ($data->listFilter->rec->lotId) {
            $data->query->where(array("#lotId = '[#1#]'", $data->listFilter->rec->lotId));
        }
        
        if ($data->listFilter->rec->lotId) {
            $data->query->where(array("#lotId = '[#1#]'", $data->listFilter->rec->lotId));
        }
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        if ($rec->id) {
            $form->setReadOnly('lotId');
            $form->setReadOnly('from');
            $form->setReadOnly('to');
        } else {
            $optArr = $form->fields['lotId']->type->prepareOptions();
            if (!empty($optArr)) {
                $form->setDefault('lotId', key($optArr));
            }
            
            $suggArr = array();
            if ($rec->lotId) {
                $pArr = cls::get('borsa_Lots')->getChangePeriods($rec->lotId);
                foreach ($pArr as $pRec) {
                    if (!$mvc->getPeriodRec($rec->lotId, $pRec['bPeriod'], $pRec['ePeriod'])) {
                        $form->setDefault('from', $pRec['bPeriod']);
                        $form->setDefault('to', $pRec['ePeriod']);
                        
                        break;
                    }
                }
                
                $pId = borsa_Lots::fetchField($rec->lotId, 'productId');
                if ($pId) {
                    $mId = cat_Products::fetchField($pId, 'measureId');
                    if ($mId) {
                        $sName = cat_UoM::getShortName($mId);
                        $data->form->setField('qAvailable', array('unit' => $sName));
                    }
                }
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if ($rec->lotId) {
            $pId = borsa_Lots::fetchField($rec->lotId, 'productId');
            if ($pId && cat_Products::haveRightFor('single', $pId)) {
                $row->lotId = cat_Products::getLinkToSingle($pId, 'name');
            }
        }
    }
}
