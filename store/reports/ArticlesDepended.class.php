<?php


/**
 * Мениджър на отчети за залежали артикули
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Склад » Залежали артикули
 */
class store_reports_ArticlesDepended extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, cat';
    
    
    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     *
     * @var string
     */
    protected $hashField;
    
    
    /**
     * Кое поле от $data->recs да се следи, ако има нов във новата версия
     *
     * @var string
     */
    protected $newFieldToCheck;
    
    
    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField;
    
    
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
        
        $fieldset->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,placeholder=Всички,after=title,single=none');
        $fieldset->FLD('period', 'time(suggestions=1 месец|3 месеца|6 месеца|1 година)', 'caption=Период, after=storeId,mandatory,single=none');
        $fieldset->FLD('minCost', 'double', 'caption=Мин. наличност, after=period,single=none, unit= лв.');
        $fieldset->FLD('reversibility', 'percent(suggestions=1%|5% |10%|20%)', 'caption=Обращаемост, after=minCost,mandatory,single=none');
        
        //Подредба на резултатите
        $fieldset->FLD('orderBy', 'enum(name=Артикул, reversibility=Обращаемост,storeAmount=Стойност,storeQuantity=Количество,code=Код)', 'caption=Подреждане по,after=reversibility');
       
        $fieldset->FNC('from', 'date', 'caption=Период->От,after=title,single=none,input = hiden');
        $fieldset->FNC('to', 'date', 'caption=Период->До,after=from,single=none,input = hiden');
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_Form         $form
     * @param stdClass          $data
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        if ($form->isSubmitted()) {
            
            if ($form->rec->minCost < 0) {
                $form->setError('minCost', 'Наличността трябва да е положително число.');
            }
            
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        $form->setDefault('orderBy', 'name');
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
        
        $pQuery = store_Products::getQuery();
        $pQuery->where("#state != 'rejected'");
        $pQuery->where('#quantity > 0');
        
        $pQuery->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');
        
        $prodArr = array();
        while ($pRec = $pQuery->fetch()) {
            $pQuantity = 0;
            
            //Себестойност на артикула
            $selfPrice = cat_Products::getPrimeCost($pRec->productId, null, $pRec->quantity, null);
            
            $minCost = $rec->minCost ? $rec->minCost : 0;
            $pQuantity = store_Products::getQuantity($pRec->productId,$rec->storeId);
            $amount = $pQuantity * $selfPrice;
            $code = $pRec->code ? $pRec->code : 'Art' . $pRec->productId;
            
            if ($amount  > $minCost) {
               
                //Налични артикули на склад
                $prodArr[$pRec->productId] =(object) array(
                    
                    'productId' => $pRec->productId,                //Id на артикула
                    'pQuantity' => $pQuantity,                      //Складова наличност: количество
                    'amount' => $amount,                            //Складова наличност: стойност
                    'code' => $code,                                //код на артикула
                    
                );
            }
        }
        foreach (array('sales_Sales','store_ShipmentOrders','planning_DirectProductionNote','planning_ConsumptionNotes') as $val) {
            $docTypeIdArr[] = (core_Classes::getId($val));
        }
        
        
        $rec->from = $startDate = dt::addSecs(-$rec->period, dt::now());
        $rec->to = dt::today();
        $query = acc_JournalDetails::getQuery();
        acc_JournalDetails::filterQuery($query, $startDate, dt::now(), '321', null, null, null, null, null, $documents = $docTypeIdArr);
        $query->show('creditItem1,creditItem2,creditQuantity');
        
        $journalProdArr = array();
        while ($jRec = $query->fetch()) {
            if ($jRec->creditItem2) {
                $productId = acc_Items::fetch($jRec->creditItem2)->objectId;
                $storeId = acc_Items::fetch($jRec->creditItem1)->objectId;
                
                //Филтър по склад
                if ($rec->storeId && ($storeId != $rec->storeId)) {
                    continue;
                }
              
                //Обороти дебит на артикулите от журнала, за които записите са от посочените класове
                $journalProdArr[$productId] += $jRec->creditQuantity;
            }
        }
        
        foreach ($prodArr as $prod) {
        
            $id = $prod->productId;
        
            $reversibility =$prod->pQuantity ? $journalProdArr[$prod->productId] / $prod->pQuantity :0 ;
            
            if ($reversibility > $rec->reversibility) {
                continue;
            }
          
            $storeQuantity = $prod->pQuantity;
            $storeAmount = $prod->amount;
            $totalCreditQuantity = $journalProdArr[$prod->productId];
            
            
            // Запис в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object) array(
                    
                    'productId' => $prod->productId,                            //Id на артикула
                    'code' => $prod->code,                                      //код на артикула
                    'name' => cat_Products::getTitleById($prod->productId),     //Име на артикула
                    'storeQuantity' => $storeQuantity,                          //Складова наличност: количество
                    'storeAmount' => $storeAmount,                              //Складова наличност: стойност
                    'totalCreditQuantity' => $totalCreditQuantity,              //Кредит обороти
                    'reversibility' => $reversibility                           //Обръщаемост
                
                );
            }
        }
        
        //Подредба на резултатите
        if (!is_null($recs)) {
            $typeOrder = ($rec->orderBy == 'name' || $rec->orderBy == 'code') ? 'stri' : 'native';
            
            $order = in_array($rec->orderBy, array('reversibility','name','code')) ? 'ASC' : 'DESC';
            
            $orderBy = $rec->orderBy;
            
            arr::sortObjects($recs, $orderBy, $order, $typeOrder);
        }
        
        return $recs;
    }
    
    
    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec    - записа
     * @param bool     $export - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');
        
        $fld->FLD('code', 'varchar', 'caption=Код,tdClass=centered');
        $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
        $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Наличност->Мярка,tdClass=centered');
        $fld->FLD('storeQuantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Наличност->Количество');
        $fld->FLD('storeAmount', 'double(smartRound,decimals=2)', 'smartCenter,caption=Наличност->Стойност');
        $fld->FLD('totalCreditQuantity', 'double(smartRound,decimals=2)', 'caption=Обороти');
        
        $fld->FLD('reversibility', 'percent', 'caption=Обращаемост');
        
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
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        
        $row = new stdClass();
        
        
        if (isset($dRec->code)) {
            $row->code = $dRec->code;
        }
        if (isset($dRec->productId)) {
            $row->productId = cat_Products::getLinkToSingle_($dRec->productId, 'name');
        }
        
        $row->measure = cat_UoM::fetchField(cat_Products::fetch($dRec->productId)->measureId, 'shortName');
        
        
        if (isset($dRec->storeId)) {
            $row->storeId = store_Stores::getLinkToSingle_($dRec->storeId, 'name');
        }
        
        if (isset($dRec->storeQuantity)) {
            $row->storeQuantity = $Double->toVerbal($dRec->storeQuantity);
        }
        
        if (isset($dRec->storeAmount)) {
            $row->storeAmount = $Double->toVerbal($dRec->storeAmount);
        }
        
        if (isset($dRec->totalCreditQuantity)) {
            $row->totalCreditQuantity = $Double->toVerbal($dRec->totalCreditQuantity);
        }
        
        if (isset($dRec->reversibility)) {
            $row->reversibility = core_Type::getByName('percent(smartRound,decimals=2)')->toVerbal($dRec->reversibility);
        }
        
        return $row;
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param core_ET             $tpl
     * @param stdClass            $data
     */
    protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
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
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        $currency = 'лв.';
        
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                <small><div><!--ET_BEGIN from-->|От|*: [#from#]<!--ET_END from--></div></small>
                                <small><div><!--ET_BEGIN to-->|До|*: [#to#]<!--ET_END to--></div></small>
                                <small><div><!--ET_BEGIN storeId-->|Склад|*: [#storeId#]<!--ET_END storeId--></div></small>
                                <small><div><!--ET_BEGIN minCost-->|Мин. наличност|*: [#minCost#] $currency<!--ET_END minCost--></div></small>
                                <small><div><!--ET_BEGIN reversibility-->|Мин. обращаемост|*: [#reversibility#]<!--ET_END reversibility--></div></small>
                                </fieldset><!--ET_END BLOCK-->"));
        if (isset($data->rec->from)) {
            $fieldTpl->append('<b>' .$Date->toVerbal($data->rec->from) . '</b>', 'from');
        }
        
        if (isset($data->rec->to)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->to) . '</b>', 'to');
        }
        
        if ((isset($data->rec->storeId))) {
            $fieldTpl->append('<b>'. store_Stores::getTitleById($data->rec->storeId) .'</b>', 'storeId');
        }
        
        if ((isset($data->rec->minCost))) {
            $fieldTpl->append('<b>'. core_Type::getByName('double(smartRound,decimals=2)')->toVerbal($data->rec->minCost) .'</b>', 'minCost');
        }
        
        if ((isset($data->rec->reversibility))) {
            $fieldTpl->append('<b>'. core_Type::getByName('percent(smartRound,decimals=2)')->toVerbal($data->rec->reversibility) .'</b>', 'reversibility');
        }
        
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
