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
        
        //Масив с id-та на ПКО-та по които има избрани безналични методи на плащане
        $pkoWitnNonCashPaymentsArr = arr::extractValuesFromArray($nonCashQuery->fetchAll(), 'documentId');
        
        while ($nonRec = $nonCashQuery->fetch()){
            
            $pkoNonCashAmount[$nonRec->documentId] = $nonRec->amount;
        }
        
        
        
        //ПКО-та по които има избрани безналични методи на плащане
        $pkoQuery = cash_Pko::getQuery();
        $pkoQuery->where("#state != 'rejected' AND #state != 'draft'"); 
        $pkoQuery->in('id',$pkoWitnNonCashPaymentsArr);
        
        //Филтър по период(по подразбиране началната дата е най-старата на която има запис за полето sourceId)
        $pkoQuery->where(array("#valior>= '[#1#]' AND #valior <= '[#2#]'",$rec->from. ' 00:00:01',$rec->to . ' 23:59:59'));
        
        //Масив с containerId-та на ПКО-та по които има избрани безналични методи на плащане
        $pkoDocsArr = arr::extractValuesFromArray($pkoQuery->fetchAll(), 'containerId');
       
        $iQuery = cash_InternalMoneyTransfer::getQuery();
        
        $iQuery->in('sourceId',$pkoDocsArr);
       
        while  ($iRec = $iQuery->fetch()){
            
    
          
            $intenalMoneyTrArr[$iRec->sourceId][$iRec->id] = (object) array(
                    
                    'id' => $iRec->id,
                    'pkoContainerId' => $iRec->sourceId,
                    
                    'amount' => $iRec->amount,
                    'state' => $iRec->state,
                    
                    );
            
            
            
            
            
        }
       
        while ($pkoRec = $pkoQuery->fetch()){
            
            $id = $pkoRec->id;
            
            // добавяме в масива
            if (! array_key_exists($id, $recs)) {
                $recs[$id] = (object) array(
                    
                    'pkoId' => $pkoRec->id,
                    'folderId' => $pkoRec->folderId,
                    'creditCase' => $pkoRec->peroCase,
                    'paymentId' => $pkoRec->paymentId,
                    'currencyId' => $pkoRec->currencyId,
                    'containerId' => $pkoRec->containerId,
                    
                    'pkoAmount' => $pkoNonCashAmount[$pkoRec->id],
                    'inTransferMoney' => $intenalMoneyTrArr[$pkoRec->containerId],
                    
                );
            }
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
     
           
            $fld->FLD('pko', 'varchar', 'caption=ПКО->Документ');
            $fld->FLD('pkoAmount', 'double(smartRound,decimals=2)', 'caption=ПКО->Сума');
            $fld->FLD('rest', 'double(smartRound,decimals=2)', 'caption=ПКО->Остатък');
            $fld->FLD('transfer', 'varchar', 'caption=Трансфер->Документ');
            $fld->FLD('amount', 'double(smartRound,decimals=2)', 'caption=Трансфер->Сума');
            
           
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
  
        
        
        if (isset($dRec->pkoAmount)) {
            $row->pkoAmount = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->pkoAmount);
        }
        
      
        
        if(is_array($dRec->inTransferMoney)){
            $sum = 0;
            foreach ($dRec->inTransferMoney as $val){
               
                    $row->transfer .= cash_InternalMoneyTransfer::getLinkToSingle($val->id)."</br>";
                    
                    $inAmount = ($val->state == 'pending') ? 0 : $val->amount;
                    $color = $inAmount == 0 ? 'blue': 'black' ;
                    
                    $row->amount .= "<span style='color: {$color}'>".core_Type::getByName('double(decimals=2)')->toVerbal($inAmount)."</br>";
                    
                    $sum += $inAmount;
                
            }
            
            
            
        }
        
        $color = $dRec->pkoAmount - $sum < 0 ? 'red': 'black' ;
        
        $rest = $dRec->pkoAmount - $sum;
        
        $row->rest = "<span style='color: {$color}'>".core_Type::getByName('double(decimals=2)')->toVerbal($rest);
        
        if (isset($dRec->pkoId)) {
            $row->pko = cash_Pko::getLinkToSingle($dRec->pkoId);
          
            if ($rest > 0){
                $url = array('cash_InternalMoneyTransfer', 'add', 'operationSysId' => 'nonecash2case', 'amount' => $rest, 'creditCase' => $dRec->creditCase, 'paymentId' => $dRec->paymentId, 'currencyId' => $dRec->currencyId, 'sourceId' => $dRec->containerId, 'foreignId' => $dRec->containerId, 'ret_url' => true);
                $toolbar = new core_RowToolbar();
                $toolbar->addLink('Инкасиране(Каса)', $url, "ef_icon = img/16/safe-icon.png,title=Създаване на вътрешно касов трансфер  за инкасиране на безналично плащане по каса");
                
                $url['operationSysId'] = 'nonecash2bank';
                $toolbar->addLink('Инкасиране(Банка)', $url, "ef_icon = img/16/own-bank.png,title=Създаване на вътрешно касов трансфер  за инкасиране на безналично плащане по банка");
                $row->pko .= $toolbar->renderHtml(2);
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

}
