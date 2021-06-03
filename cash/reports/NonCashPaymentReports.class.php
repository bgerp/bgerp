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
    const START_DATE = '2020-02-05';
    
    
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,accMaster,sales,bank,cash,acc,debug';
    
    
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
    protected $changeableFields='from,to,pkoCase';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('from', 'date', 'caption=Период->От,after=title,single=none');
        $fieldset->FLD('to', 'date', 'caption=Период->До,after=from,single=none');
        
        $fieldset->FLD('pkoCase', 'key(mvc=cash_Cases, select=name,allowEmpty)', 'caption=Каса,placeholder = Всички каси,after=to,single=none,silent');
        $fieldset->FLD('see', 'enum(notIn=За инкасиране, all=Всички)', 'notNull,caption=Покажи,maxRadio=2,after=pkoCase');
        $fieldset->FLD('orderBy', 'enum(pkoId=ПКО номер,pkoAmount=ПКО сума, contragentName=Контрагент)', 'caption=Подреди по,after=see,silent');
        
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
        
        $form->setDefault('orderBy', 'pkoId');
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
        $Date = cls::get('type_Date');
        
        $rec = $form->rec;
        if ($form->isSubmitted()) {
            
            // Проверка на периоди
            $startDate = self::START_DATE;
            $startDateVerb = $Date -> toVerbal($startDate);
            
            if (isset($rec->from) && ($rec->from < $startDate)) {
                $form->setError('from', "Началната дата на периода не може да бъде по-стара от {$startDateVerb} .");
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
        
        
        //Масив с id-та на ПКО-та по които има избрани безналични методи на плащане
        $pkoWitnNonCashPaymentsArr = arr::extractValuesFromArray($nonCashQuery->fetchAll(), 'documentId');
        
        
        $pkoNonCashAmount = array();
        while ($nonRec = $nonCashQuery->fetch()) {
            if (! array_key_exists($nonRec->documentId, $pkoNonCashAmount)) {
                $pkoNonCashAmount[$nonRec->documentId] = (object) array('nonCashPaymentAmount' => $nonRec->amount,
                                                                        'nonCashPaymentId' => $nonRec->paymentId
                                                                        );
                                                                        } else {
                                                                            $obj = & $pkoNonCashAmount[$nonRec->documentId];
                                                                            $obj->nonCashPaymentAmount += $nonRec->amount;
                                                                        }
        }
        
        //ПКО-та по които има избрани безналични методи на плащане
        $pkoQuery = cash_Pko::getQuery();
        $pkoQuery->where("#state != 'rejected' AND #state != 'draft'");
        
        if ($rec->pkoCase){
            
            $pkoQuery->where("#peroCase = $rec->pkoCase");
        }
        $pkoQuery->in('id', $pkoWitnNonCashPaymentsArr);
        
        //Филтър по период(по подразбиране началната дата е най-старата на която има запис за полето sourceId)
        $pkoQuery->where(array("#valior>= '[#1#]' AND #valior <= '[#2#]'",$rec->from. ' 00:00:00',$rec->to . ' 23:59:59'));
        
        //Масив с containerId-та на ПКО-та по които има избрани безналични методи на плащане
        $pkoDocsArr = arr::extractValuesFromArray($pkoQuery->fetchAll(), 'containerId');
        
        $iQuery = cash_InternalMoneyTransfer::getQuery();
        $iQuery->where("#state != 'rejected'");
        $iQuery->where('#sourceId IS NOT NULL');
        $iQuery->in('sourceId', $pkoDocsArr);
        
        $intenalMoneyTrArr = array();
        while ($iRec = $iQuery->fetch()) {
            $intenalMoneyTrArr[$iRec->sourceId][$iRec->id] = (object) array(
                
                'id' => $iRec->id,
                'pkoContainerId' => $iRec->sourceId,
                'paymentId' => $iRec->paymentId,
                
                'amount' => $iRec->amount,
                'state' => $iRec->state,
            
            );
        }
        
        while ($pkoRec = $pkoQuery->fetch()) {
            
            $id = $pkoRec->id;
            $stateArr = array('active', 'closed');
            $pkoTransferedSumm = 0;
            if (is_array($intenalMoneyTrArr[$pkoRec->containerId])){
                foreach ($intenalMoneyTrArr[$pkoRec->containerId] as $val){
                
                    if(in_array($val->state, $stateArr)){
                        $pkoTransferedSumm += $val->amount;
                    }
                }
            }
          
           if ($rec->see == 'notIn' && $pkoNonCashAmount[$pkoRec->id]->nonCashPaymentAmount == $pkoTransferedSumm)continue;
           
           $pkoInvoice = $pkoRec->fromContainerId;
           
           if(!$pkoInvoice){
               
               $pkoInvoice = self::determiningInvoice($pkoRec);
           }
         
           
           $contragentClassName = core_Classes::getName($pkoRec->contragentClassId);
           $contragentName = $contragentClassName::getTitleById($pkoRec->contragentId);
           
            // добавяме в масива
            if (! array_key_exists($id, $recs)) {
                $recs[$id] = (object) array(
                    
                    'pkoId' => $pkoRec->id,
                    'invoice' => $pkoInvoice,
                    'contragentName' => $contragentName,
                    'pkoValior' => $pkoRec->valior,
                    'folderId' => $pkoRec->folderId,
                    'creditCase' => $pkoRec->peroCase,
                    'currencyId' => $pkoRec->currencyId,
                    'containerId' => $pkoRec->containerId,
                    
                    'pkoAmount' => $pkoNonCashAmount[$pkoRec->id]->nonCashPaymentAmount,
                    'pkoTransferedSumm' => $pkoTransferedSumm,
                    'pkoNonCashPaymentId' => $pkoNonCashAmount[$pkoRec->id]->nonCashPaymentId,
                    'inTransferMoney' => $intenalMoneyTrArr[$pkoRec->containerId],
                
                );
            }
        }
        
        
        if (! is_null($recs)) {
            arr::sortObjects($recs, $rec->orderBy, 'asc');
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
        
        if ($export === false) {
        
        $fld->FLD('contragentName', 'varchar', 'caption=Контрагент');
        $fld->FLD('pko', 'varchar', 'caption=ПКО->Документ');
        $fld->FLD('invoiceNum', 'varchar', 'caption=ПКО->Фактура');
        $fld->FLD('pkoAmount', 'double(smartRound,decimals=2)', 'caption=ПКО->Сума');
        $fld->FLD('rest', 'double(smartRound,decimals=2)', 'caption=ПКО->Остатък');
        $fld->FLD('transfer', 'varchar', 'caption=Трансфер->Документ');
        $fld->FLD('amount', 'double(smartRound,decimals=2)', 'caption=Трансфер->Сума');
        
        }else{
            
            $fld->FLD('contragentName', 'varchar', 'caption=Контрагент');
            $fld->FLD('pko', 'varchar', 'caption=ПКО->Документ');
            $fld->FLD('invoiceNum', 'varchar', 'caption=ПКО->Фактура');
            $fld->FLD('pkoAmount', 'varchar', 'caption=ПКО->Сума');
            $fld->FLD('rest', 'varchar', 'caption=ПКО->Остатък');
            $fld->FLD('transfer', 'varchar', 'caption=Трансфер->Документ');
            $fld->FLD('amount', 'varchar', 'caption=Трансфер->Сума');
            
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
        
        if (isset($dRec->contragentName)) {
            $row->contragentName = $dRec->contragentName;
        }
        
        if (isset($dRec->pkoAmount)) {
            $row->pkoAmount = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->pkoAmount);
        }
        
        
        if (is_array($dRec->inTransferMoney)) {
            $sum = 0;
            foreach ($dRec->inTransferMoney as $val) {
                $state = $val->state;
                
                $url = toUrl(array("cash_InternalMoneyTransfer",'single', $val->id));
                
                $inAmount = ($val->state == 'pending' || $val->state == 'draft') ? 0 : $val->amount;
                $color = $inAmount == 0 ? 'blue': 'black' ;
                $sum += $inAmount;
                if ($state == 'pending' || $state == 'draft') {
                    $row->transfer .= "<div><span class= 'state-{$state} document-handler' >".ht::createLink("Cvt#$val->id", $url, false, array()).'</div>';
                    $row->amount .= "<span style='color: {$color}'>".$Double->toVerbal($inAmount).'</br>';
                } else {
                    $row->transfer .= ht::createLink("Cvt#$val->id", $url, false, array()).'</br>';
                    $row->amount .= "<span style='color: {$color}'>".$Double->toVerbal($inAmount).'</br>';
                }
            }
        }
        
        $color = $dRec->pkoAmount - $sum < 0 ? 'red': 'black' ;
        $rest = $dRec->pkoAmount - $sum;
        
        $row->rest = "<span style='color: {$color}'>"."<b>".core_Type::getByName('double(decimals=2)')->toVerbal($rest)."</b>";
        
        if (isset($dRec->pkoId)) {
            
            
            $handle = "Pko #$dRec->pkoId".' / '.$Date->toVerbal($dRec->pkoValior);
            
            $url = toUrl(array("cash_Pko",'single', $dRec->pkoId));
            
            $row->pko =ht::createLink($handle, $url, false, array());
            
            $cashFolderId = cash_Cases::fetchField($dRec->creditCase, 'folderId');
            
            if ($rest > 0) {
                $url = array('cash_InternalMoneyTransfer', 'add', 'folderId' => $cashFolderId, 'operationSysId' => 'nonecash2case', 'amount' => $rest, 'creditCase' => $dRec->creditCase, 'paymentId' => $dRec->pkoNonCashPaymentId, 'currencyId' => $dRec->currencyId, 'sourceId' => $dRec->containerId, 'foreignId' => $dRec->containerId, 'ret_url' => true);
                $toolbar = new core_RowToolbar();
                $toolbar->addLink('Инкасиране(Каса)', $url, 'ef_icon = img/16/safe-icon.png,title=Създаване на вътрешно касов трансфер  за инкасиране на безналично плащане по каса');
                
                $url['operationSysId'] = 'nonecash2bank';
                $toolbar->addLink('Инкасиране(Банка)', $url, 'ef_icon = img/16/own-bank.png,title=Създаване на вътрешно касов трансфер  за инкасиране на безналично плащане по банка');
                $row->pko .= ' - '.$toolbar->renderHtml(2);
            }
        }
        
        if (isset($dRec->invoice)) {
            
            if(!is_array($dRec->invoice)){
            
            $Invoice = doc_Containers::getDocument($dRec->invoice);
            
            $invRec = sales_Invoices::fetch($Invoice->that);
            
            $handle = "Inv #$invRec->number".' / '.$Date->toVerbal($invRec->date);
           
            $url = toUrl(array("sales_Invoices",'single', $Invoice->that));
            
            $row->invoiceNum =ht::createLink($handle, $url, false, array());
            }else{
                
                if(!empty($dRec->invoice)){
                    $row->invoiceNum ='За избор'."</br>";
                }
                
                foreach ($dRec->invoice as $val){
                    
                    $Invoice = doc_Containers::getDocument($val);
                    
                    $invRec = sales_Invoices::fetch($Invoice->that);
                    
                    $handle = "Inv #$invRec->number".' / '.$Date->toVerbal($invRec->date);
                    
                    $url = toUrl(array("sales_Invoices",'single', $Invoice->that));
                    
                    $row->invoiceNum .=ht::createLink($handle, $url, false, array())."</br>";
                    
                }
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
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><b>|Филтър|*</b></small></legend>
								    <div class='small'>
                                        <!--ET_BEGIN from--><div>|От|*: [#from#]</div><!--ET_END from-->
                                        <!--ET_BEGIN to--><div>|До|*: [#to#]</div><!--ET_END to-->
                                        <!--ET_BEGIN pkoCase--><div>|Каса|*: [#pkoCase#]</div><!--ET_END pkoCase-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));
        
        
        if (isset($data->rec->from)) {
            $fieldTpl->append('<b>' . $data->rec->from . '</b>', 'from');
        }
        
        if (isset($data->rec->to)) {
            $fieldTpl->append('<b>' . $data->rec->to . '</b>', 'to');
        }
        
        if (isset($data->rec->pkoCase)) {
            $fieldTpl->append('<b>' . cash_Cases::getTitleById($data->rec->pkoCase) . '</b>', 'pkoCase');
        }else{
            
            $fieldTpl->append('<b>' . 'Всички' . '</b>', 'pkoCase');
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
        $Date = cls::get('type_Date');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        
        $res->pko = "Pko #$dRec->pkoId".' / '.$Date->toVerbal($dRec->pkoValior);
        $res->pkoAmount = $Double->toVerbal($dRec->pkoAmount);
       
        
        if (is_array($dRec->inTransferMoney)) {
            $sum = 0;$marker = 0;
            foreach ($dRec->inTransferMoney as $val) {
                $marker ++;
                $inAmount = ($val->state == 'pending' || $val->state == 'draft') ? 0 : $val->amount;
                
                $sum += $inAmount;
                
                $res->transfer .= "Cvt#$val->id";
                $res->amount .= $Double->toVerbal($inAmount);
                
                
                if ((countR($dRec->inTransferMoney)) - $marker != 0) {
                $res->transfer .= ' |';
                $res->amount .= ' |';
                }
                
                
                
                
            }
        }
        
       
            
            if (is_array($dRec->invoice)){
                
                if(!empty($dRec->invoice)){
                    $res->invoiceNum .='За избор: ';
                }
                $marker = 0;
                foreach ($dRec->invoice as $val){ 
                    $marker++;
                    $Invoice = doc_Containers::getDocument($val);
                    
                    $invRec = sales_Invoices::fetch($Invoice->that);
                    
                    $handle = "Inv#$invRec->number";
                    
                    $res->invoiceNum .=$handle;
                    
                    if ((countR($dRec->invoice)) - $marker != 0) {
                        $res->invoiceNum .=" |";
                    }
                }
                
                
            }else{
            
                $Invoice = doc_Containers::getDocument($dRec->invoice);
                
                $invRec = sales_Invoices::fetch($Invoice->that);
                
                 $handle = "Inv #$invRec->number".' / '.$Date->toVerbal($invRec->date);
                
                 $res->invoiceNum =$handle;
                 
                 
                 
             }
       
        
        $rest = $dRec->pkoAmount - $sum;
        $res->rest =$Double->toVerbal($rest);
        
    }
    
    private static function determiningInvoice($pkoRec) {
        
        $invArr = array();
        
        $iQuery = sales_Invoices::getQuery();
        $iQuery->where("#threadId = $pkoRec->threadId AND #state = 'active'"); 
        $iQuery->where("#paymentType = 'cash'"); 
        
        
        //Условие за дата на издаване на фактурата: +/- 5 дена от вальора на ПКО
        $from = dt::addDays(-5,$pkoRec->valior);
        $to = dt::addDays(5,$pkoRec->valior);
        
        $iQuery->where(array("#date>= '[#1#]' AND #date <= '[#2#]'",$from. ' 00:00:00',$to . ' 23:59:59'));
        
        
        while ($iRec = $iQuery->fetch()) {
            $totalAmount = ($iRec->dealValue + $iRec->vatAmount)-$iRec->discountAmount ;
            
            if(($pkoRec->amountDeal <= ($totalAmount + 0.05)) && ($pkoRec->amountDeal >= ($totalAmount - 0.05))){
            
                return $iRec->containerId;
            
            }else{
                array_push($invArr, $iRec->containerId);
            }
        }
        
        return $invArr  ;
    }
}