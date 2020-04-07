<?php


/**
 * Мениджър на отчети за артикули с отрицателни количества
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Счетоводство » Артикули с отрицателни количества
 */
class acc_reports_NegativeQuantities extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,acc';
    
    
    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;
    
    
    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField = 'articul';
    
    
    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields;
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('period', 'key(mvc=acc_Periods,title=title)', 'caption = Период,after=accountId,single=none');
        $fieldset->FLD('accountId', 'key(mvc=acc_Accounts,title=title)', 'caption = Сметка,after=title,single=none');
        $fieldset->FLD('storeId', 'keylist(mvc=store_Stores,select=name)', 'caption = Склад,after=accountId');
        $fieldset->FLD('minval', 'double(decimals=2)', 'caption = Минимален праг за отчитане,unit= (количество),
                        placeholder=Без праг,after=period,single=none');
        
        $fieldset->FNC('counter', 'int', 'caption = Брояч,input=none,single=none');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     *                                      $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        $form->setDefault('accountId', 81);
    }
    
    
    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     *
     * @return array
     */
    protected function prepareRecs($rec, &$data = null)
    {
        $recs = array();
        
        $query = acc_BalanceDetails::getQuery();
        
        $query->EXT('periodId', 'acc_Balances', 'externalName=periodId,externalKey=balanceId');
        
        $query->where("#accountId = '{$rec->accountId}'");
        
        $query->where("#periodId = {$rec->period}");
        
        $query->where('#ent1Id IS NOT NULL AND #ent2Id IS NOT NULL');
        
        if (!is_null($rec->storeId)) {
            $storesForCheck = keylist::toArray($rec->storeId);
        } 
        
        while ($detail = $query->fetch()) {
           
            if (!is_null($rec->storeId)) {

                if (!(in_array((acc_Items::fetch($detail->ent1Id)->objectId), $storesForCheck))) {
                    continue;
                }
                
            }
     
                if (!array_key_exists($detail->ent2Id, $recs)) {
                    $recs[$detail->ent2Id] = (object) array(
                        
                        'articulId' => $detail->ent2Id,
                        'articulNo' => '',
                        'articulName' => cat_Products::getTitleById($detail->ent2Id),
                        'uomId' => acc_Items::fetch($detail->ent2Id)->uomId,
                        'storeId' => array($detail->ent1Id),
                        'quantity' => array($detail->blQuantity),
                    );
                } else {
                    $obj = &$recs[$detail->ent2Id];
                    
                    array_push($obj->storeId, $detail->ent1Id);
                    array_push($obj->quantity, $detail->blQuantity);
                }
        }
        
        $number = 1;
         foreach ($recs as $key => $val) {
             
             if (min($val->quantity) > 0) {
                  unset($recs[$key]);
             }else{
            
            $val->articulNo = $number;
            $number++;
             }
        }
        
        $rec->counter = countR($recs);
        
        
        
       
   
        return $recs;
    }
    
    
    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec
     *                         - записа
     * @param bool     $export
     *                         - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');
        
        if ($export === false) {
            $fld->FLD('articul', 'varchar', 'caption=Артикул');
            $fld->FLD('uomId', 'varchar', 'caption=Мярка');
            $fld->FLD('store', 'varchar', 'caption=Склад');
            $fld->FLD('quantity', 'double(decimals=2)', 'caption=Количество->Към периода');
            $fld->FLD('quantityNow', 'double(decimals=2)', 'caption=Количество->Актуално');
        }
        
        return $fld;
    }
    
    
    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec
     *                       - записа
     * @param stdClass $dRec
     *                       - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $isPlain = Mode::is('text', 'plain');
        $Int = cls::get('type_Int');
        $Date = cls::get('type_Date');
        $resArr = array();
        
        $quantityNow = store_Products::getQuantity($dRec->articulId, null, false);
        
        $row = new stdClass();
        
        $rec->productCount++;
        
        $productId = acc_Items::fetch($dRec->articulId)->objectId;
        
        $row->articul = "<span class= ''>" . $dRec->articulNo . '. ' . '</span>';
        
        $row->articul .= "<span class= ''>" . cat_Products::getShortHyperlink($productId, true) . '</span>';
        
        $row->uomId = cat_UoM::getTitleById($dRec->uomId);
        
        $resArr = array_combine($dRec->storeId, $dRec->quantity);
        
        asort($resArr);
        
        foreach ($resArr as $key => $val) {
            
            //филтър за праг
            if($rec->minval && (abs($val) < $rec->minval))continue;
            
            $from = acc_Periods::fetch($rec->period)->start;
            
            $to = dt::today();
            
            $storeId = acc_Items::fetch($key)->objectId;
            
            $histUrl = array(
                'acc_BalanceHistory',
                'History',
                'fromDate' => $from,
                'toDate' => $to,
                'accNum' => 321,
                'ent1Id' => $key,
                'ent2Id' => $dRec->articulId
            );
            
            $row->store .= "<div class='nowrap'>" . ht::createLink('', $histUrl, null, 'title=Хронологична справка,ef_icon=img/16/clock_history.png');
            
            $row->store .= store_Stores::getHyperlink($storeId, true) . '</div>';
            
            $color = 'green';
            if ($val < 0) {
                $color = 'red';
            }
            
            $row->quantity .= "<span class= '{$color}'>" . core_Type::getByName('double(decimals=2)')->toVerbal($val) . '</span>' . '</br>';
            
            if (!is_null($productId)) {
                $quantityNow = store_Products::getQuantity($productId, $storeId);
            }
            $colorNow = 'green';
            if ($quantityNow < 0) {
                $colorNow = 'red';
            }
            
            $row->quantityNow .= "<span class= '{$colorNow}'>" . core_Type::getByName('double(decimals=2)')->toVerbal($quantityNow) . '</span>' . '</br>';
        }
        
        return $row;
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_ET           $tpl
     * @param stdClass          $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        $Date = cls::get('type_Date');
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
        		                <small><div><!--ET_BEGIN period-->|Период|*: [#period#]<!--ET_END period--></div></small>
                                <small><div><!--ET_BEGIN minval-->|Минимален праг за отчитане|*: [#minval#]<!--ET_END minval--></div></small>
                                <small><div><!--ET_BEGIN counter-->|Брой артикули|*: [#counter#]<!--ET_END counter--></div></small>
                                </fieldset><!--ET_END BLOCK-->"));
        
        if (isset($data->rec->period)) {
            $fieldTpl->append('<b>' . acc_Periods::getTitleById($data->rec->period) . '</b>', 'period');
        }
        
        if (isset($data->rec->minval)) {
            $fieldTpl->append('<b>' . ($data->rec->minval) . ' единици' . '</b>', 'minval');
        }
        
        $fieldTpl->append('<b>' . ($data->rec->counter) . '</b>', 'counter');
        
        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }
    
    
    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver
     * @param stdClass            $res
     * @param stdClass            $rec
     * @param stdClass            $dRec
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
    }
}
