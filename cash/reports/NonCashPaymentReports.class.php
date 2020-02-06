<?php


/**
 * Мениджър на отчети за инкасиране безналични плащания
 *
 *
 * @category  bgerp
 * @package   cash
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Счетоводство » Инкасиране безналични плащания
 */
class cash_reports_NonCashPaymentReports extends frame2_driver_TableData
{
    const START_DATE = '2020-02-01';
    
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,accMaster,debug';
    
    
    /**
     * Кои полета от листовия изглед да може да се сортират
     *
     * @var int
     */
    protected $sortableListFields;
    
    
    /**
     * Кои полета от таблицата в справката да се сумират в обобщаващия ред
     *
     * @var int
     */
    protected $summaryListFields ;
    
    
    /**
     * Как да се казва обобщаващия ред. За да се покаже трябва да е зададено $summaryListFields
     *
     * @var int
     */
    protected $summaryRowCaption = 'ОБЩО';
    
    
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
    protected $groupByField ;
    
    
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
        $fieldset->FLD('from', 'date', 'caption=Период->От,after=title,single=none');
        $fieldset->FLD('to', 'date', 'caption=Период->До,after=from,single=none');
       
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
        
        $form->setField('to', 'placeholder=' . dt::addDays(0, null, false));
        $form->setField('from', 'placeholder=' . '2020-02-01');
        
        
        
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
        $rec = $form->rec;
        if ($form->isSubmitted()) {
            
            
            // Проверка на периоди
            $startDate = self::START_DATE;
            
            if (isset($rec->from) && ($rec->from < $startDate)) {
                $form->setError('from', "Началната дата на периода не може да бъде по-голяма от $startDate.");
            }
            
            if (isset($form->rec->from, $form->rec->to) && ($form->rec->from > $form->rec->to)) {
                $form->setError('from,to', 'Началната дата на периода не може да бъде по-голяма от крайната.');
            }
            
            
        }
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
        
        setIfNot($rec->from, self::START_DATE);
        setIfNot($rec->to, dt::addDays(0, null, false));
        
        
        
        $nonCashQuery = cash_NonCashPaymentDetails::getQuery();
        
         
        
        bp($pkoWitnNonCashArr);
        
        
        
        bp(cash_NonCashPaymentDetails::getQuery()->fetchAll(),$rec);
        
        $bgId = drdata_Countries::getIdByName('България');
        
        if ($rec->type == 'long') {
            
            $codeKey = $rec->code == 'mtk' ? 'mtk' : 'prodPromCode';
            $this->groupByField = $codeKey;
        }
        
        //Id на параметъра accProd
        $accProdParamId = cat_Params::force('accProd', 'accProd', 'varchar', null, '');
        
        $stateArr = array('active','closed');
        
        $query = sales_InvoiceDetails::getQuery();
        
        $query->EXT('state', 'sales_Invoices', 'externalName=state,externalKey=invoiceId');
        $query->EXT('threadId', 'sales_Invoices', 'externalName=threadId,externalKey=invoiceId');
        $query->EXT('date', 'sales_Invoices', 'externalName=date,externalKey=invoiceId');
        $query->EXT('contragentCountryId', 'sales_Invoices', 'externalName=contragentCountryId,externalKey=invoiceId');
        
        $query->in('state', $stateArr);
        
        $query->where(array("#date >= '[#1#]'", $rec->from . ' 00:00:00'));
        
        $query->where(array("#date <= '[#1#]'", $rec->to . ' 23:59:59'));
        
        //Масив с всички фактури през периода
        $invProds = array();
        while ($invDetRec = $query->fetch()) {
            $amountBg = $weightBg = $weight = 0;
            
            $id = $invDetRec->productId;
            
            $amount = $invDetRec->amount;
            
            $keyDebug = $invDetRec->invoiceId.'|'.$invDetRec->productId.'|'.$invDetRec->id;
            
            $prodTransportWeight = cat_Products::getParams($invDetRec->productId, 'transportWeight');
            $prodTransportWeightDebug[$keyDebug] = $prodTransportWeight;
            
            $prodWeight = (cat_Products::getParams($invDetRec->productId, 'weight')) / 1000;
            $prodWeightDebug[$keyDebug] = $prodWeight;
            
            $prodWeight = $prodWeight ? $prodWeight  : $prodTransportWeight;
            
            $weight = $prodWeight ? $invDetRec->quantity * $prodWeight : 0;
            
            $amountBg = $weightBg = 0;
            if ($invDetRec->contragentCountryId == $bgId) {
                $amountBg = $invDetRec->amount;
                $weightBg = $weight;
            }
            
            list($a, $accProd) = explode('.', cat_Products::getParams($id, $accProdParamId));
            
            $accProd = trim($accProd);
            
            // добавя в масива
            if (!array_key_exists($id, $invProds)) {
                $invProds[$id] = (object) array(
                    
                    'invoiceId' => $invDetRec->invoiceId,
                    'productId' => $invDetRec->productId,
                    'data' => $invDetRec->date,
                    'accProd' => $accProd,
                    'amount' => $amount,
                    'weight' => $weight,
                    'amountBg' => $amountBg,
                    'weightBg' => $weightBg,
                    'detRecWeightDebug' => $detRecWeightDebug,
                    'prodTransportWeightDebug' => $prodTransportWeightDebug,
                    'prodWeightDebug' => $prodWeightDebug,
                    'prodWeightkgDebug' => $prodWeightkgDebug,
                    
                );
            } else {
                $obj = &$invProds[$id];
                $obj->weight += $weight;
                $obj->amount += $amount;
                $obj->weightBg += $weightBg;
                $obj->amount += $amountBg;
            }
        }
        
        
        $fRecs = array();
        $fQuery = fsd_InvoiceDef::getQuery();
        
        while ($fRec = $fQuery->fetch()) {
            $idf = $fRec->sysId;
            
            $name = $fRec->name;
            
            $mtk = $fRec->mtk;
            
            // Масив записи от fsd_InvoiceDef : accProd=>MTK
            if (!in_array($name, $fRecs)) {
                $fRecs[$name] = $mtk;
            }
        }
        
        
        foreach ($invProds as $val) {
            if (in_array($val->accProd, array_keys($fRecs))) {
                $id = $val->productId;
                
                
                if (!array_key_exists($id, $recs)) {
                    $recs[$id] = (object) array(
                        
                        'mtk' => $fRecs[$val->accProd],
                        'prodPromCode' => self::getProdPromCodes($fRecs[$val->accProd]),
                        'invoiceId' => $val->invoiceId,
                        'productId' => $val->productId,
                        'data' => $val->date,
                        'accProd' => $val->accProd,
                        
                        
                        'amount' => $val->amount,
                        'weight' => $val->weight,
                        'amountBg' => $val->amountBg,
                        'weightBg' => $val->weightBg,
                        
                        
                        'detRecWeightDebug' => $val->detRecWeightDebug,
                        'prodTransportWeightDebug' => $val->prodTransportWeightDebug,
                        'prodWeightDebug' => $val->prodWeightDebug,
                        'prodWeightkgDebug' => $val->prodWeightkgDebug,
                        
                        'groupWeight' => '',
                        'groupAmount' => '',
                        
                    );
                } else {
                    $obj = &$recs[$id];
                    $obj->weight += $val->weight;
                    $obj->amount += $val->amount;
                    $obj->weightBg += $val->weightBg;
                    $obj->amount += $val->amountBg;
                }
            }
        }
        
        
        $groupWeightAndAmount = array();
        
        $codeKey = $rec->code == 'mtk' ? 'mtk' : 'prodprom';
        
        foreach ($recs as $v) {
            $groupWeight[$v->$codeKey] += $v->weight;
            $groupAmount[$v->$codeKey] += $v->amount;
            
            $id = $v->$codeKey;
            if (!in_array($id, array_keys($groupWeightAndAmount))) {
                $groupWeightAndAmount[$id] = (object) array(
                    'mtk' => $v->mtk,
                    'prodPromCode' => $v->prodPromCode,
                    'groupWeight' => $v->weight,
                    'groupAmount' => $v->amount,
                    'groupWeightIn' => $v->weightBg,
                    'groupAmountIn' => $v->amountBg,
                    
                );
            } else {
                $obj = &$groupWeightAndAmount[$id];
                $obj->groupWeight += $v->weight;
                $obj->groupAmount += $v->amount;
                $obj->groupWeightIn += $v->weightBg;
                $obj->groupAmountIn += $v->amountBg;
            }
        }
        
        if ($rec->type == 'short') {
            
            $recs = $groupWeightAndAmount;
            
            return $recs;
        }
        
        foreach ($recs as $key => $val) {
            $codeKey = $rec->code == 'mtk' ? 'mtk' : 'prodprom';
            $val->groupWeight = $groupWeight[$val->$codeKey];
            $val->groupAmount = $groupAmount[$val->$codeKey];
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
        
        if ($rec->type == 'short') {
            
            $codeName = $rec->code == 'mtk' ? 'МТК' : 'Продпром код';
            if ($rec->code == 'mtk'){
                $fld->FLD('mtk', 'int', "caption=${codeName},smartCenter");
            }
            
            if ($rec->code != 'mtk'){
                $fld->FLD('prodPromCode', 'int', "caption=${codeName},smartCenter");
            }
            $fld->FLD('groupWeight', 'double(smartRound,decimals=4)', 'caption=Продажби->Общо->Тегло');
            $fld->FLD('groupAmount', 'double(smartRound,decimals=4)', 'caption=Продажби->Общо->Стойност');
            $fld->FLD('groupPrice', 'double(smartRound,decimals=4)', 'caption=Продажби->Общо->ср.цена');
            $fld->FLD('groupWeightIn', 'double(smartRound,decimals=4)', 'caption=Продажби->В т.ч. вътр.пазар->Тегло');
            $fld->FLD('groupAmountIn', 'double(smartRound,decimals=4)', 'caption=Продажби->В т.ч. вътр.пазар->Стойност');
            $fld->FLD('groupPriceIn', 'double(smartRound,decimals=4)', 'caption=Продажби->В т.ч. вътр.пазар->ср.цена');
            
            return $fld;
        }
        
        $fld->FLD('weight', 'double(smartRound,decimals=4)', 'caption=Тегло,tdClass=centered');
        $fld->FLD('amount', 'double(smartRound,decimals=4)', 'caption=Стойност');
        $fld->FLD('accProd', 'varchar', 'caption=accProd');
        
        
        if (haveRole('debug')) {
            $fld->FLD('product', 'varchar', 'caption=Артикул');
            $fld->FLD('invoice', 'varchar', 'caption=Фактура');
            $fld->FLD('weightDebug', 'varchar', 'caption=Тегла');
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
        $Int = cls::get('type_Int');
        $Date = cls::get('type_Date');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        
        $row = new stdClass();
        
        if (isset($dRec->accProd)) {
            foreach (explode('|', $dRec->accProd) as $val) {
                $row->accProd .= $val.'</br>';
            }
        }
        
        if (isset($dRec->mtk)) {
            if ($rec->type == 'long') {
                $codeKey = $rec->code == 'mtk' ? 'mtk' : 'prodPromCode';
                $codeName = $rec->code == 'mtk' ? 'МТК:' : 'Продпром код:';
                
                $row->mtk = $codeName.$dRec->$codeKey  .' »  '.core_Type::getByName('double(decimals=2)')->toVerbal($dRec->groupWeight).' kg'
                    .' »  '.core_Type::getByName('double(decimals=2)')->toVerbal($dRec->groupAmount).' лв.';
                    
                    $row->prodPromCode = $codeName.$dRec->$codeKey  .' »  '.core_Type::getByName('double(decimals=2)')->toVerbal($dRec->groupWeight).' kg'
                        .' »  '.core_Type::getByName('double(decimals=2)')->toVerbal($dRec->groupAmount).' лв.';
            } else {
                $codeKey = $rec->code == 'mtk' ? 'mtk' : 'prodPromCode';
                $row->mtk = trim($dRec->$codeKey);
                $row->prodPromCode = trim($dRec->$codeKey);
            }
        }
        
        if (isset($dRec->amount)) {
            $row->amount = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->amount);
        }
        
        if (isset($dRec->weight)) {
            $row->weight = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->weight);
        }
        
        if (isset($dRec->groupWeight)) {
            $row->groupWeight = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->groupWeight);
        }
        
        if (isset($dRec->groupAmount)) {
            $row->groupAmount = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->groupAmount);
        }
        
        if (!is_null($dRec->groupWeight)) {
            $row->groupPrice = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->groupAmount / $dRec->groupWeight);
        }
        
        if (!is_null($dRec->groupWeightIn)) {
            $row->groupPriceIn = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->groupAmountIn / $dRec->groupWeightIn);
        }
        
        if (isset($dRec->groupWeightIn)) {
            $row->groupWeightIn = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->groupWeightIn);
        }
        
        if (isset($dRec->groupAmountIn)) {
            $row->groupAmountIn = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->groupAmountIn);
        }
        
        
        if (isset($dRec->productId)) {
            $row->product = cat_Products::getTitleById($dRec->productId);
        }
        
        if (isset($dRec->invoiceId)) {
            $row->invoice = $dRec->invoiceId.' | '.sales_Invoices::fetchField($dRec->invoiceId, 'number');
        }
        
        $row->weightDebug = '';
        
        if (is_array($dRec->detRecWeightDebug)) {
            foreach ($dRec->detRecWeightDebug as $key => $val) {
                list($a, $prKey, $b) = explode('|', $key);
                
                if ($prKey != $dRec->productId) {
                    continue;
                }
                
                $row->weightDebug .= 'detRecWeightDebug '.$key.' | '.$val.'</br>';
            }
        }
        
        if (is_array($dRec->prodTransportWeightDebug)) {
            foreach ($dRec->prodTransportWeightDebug as $key => $val) {
                list($a, $prKey, $b) = explode('|', $key);
                
                if ($prKey != $dRec->productId) {
                    continue;
                }
                
                $row->weightDebug .= 'prodTransportWeightDebug '.$key.' | '.$val.'</br>';
            }
        }
        
        if (is_array($dRec->prodWeightDebug)) {
            foreach ($dRec->prodWeightDebug as $key => $val) {
                list($a, $prKey, $b) = explode('|', $key);
                
                if ($prKey != $dRec->productId) {
                    continue;
                }
                
                $row->weightDebug .= 'prodWeightDebug '.$key.' | '.$val.'</br>';
            }
        }
        
        if (is_array($dRec->prodWeightkgDebug)) {
            foreach ($dRec->prodWeightkgDebug as $key => $val) {
                list($a, $prKey, $b) = explode('|', $key);
                
                if ($prKey != $dRec->productId) {
                    continue;
                }
                
                $row->weightDebug .= 'prodWeightkgDebug '.$key.' | '.$val.'</br>';
            }
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
    
    
    /**
     * Връзка между МТК и Продпром кодове
     */
    public static function getProdPromCodes($mtk)
    {
        $csv = '../extrapack/fsd/csv/MtcToProdPromCode.csv';
        
        $temparr = file($csv);
        
        foreach ($temparr as $key => $val) {
            if ($key > 0) {
                list($mtk, $prodProm) = explode(',', $val);
                $codesArr[$mtk] = $prodProm;
            }
        }
        
        $prodPromCode = $codesArr[$mtk];
        
        return $prodPromCode;
    }
}
