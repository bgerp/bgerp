<?php
 
 
 /**
  * Мениджър на отчети за линии по шофьор
  *
  * @category  bgerp
  * @package   trans
  *
  * @author    Angel Trifonov angel.trifonoff@gmail.com
  * @copyright 2006 - 2019 Experta OOD
  * @license   GPL 3
  *
  * @since     v 0.1
  * @title     Логистика » Данни от Транспортни линии
  */
 class trans_reports_LinesByForwarder extends frame2_driver_TableData
 {
     /**
      * Кой може да избира драйвъра
      */
     public $canSelectDriver = 'ceo,acc,debug';
     
     
     /**
      * Брой записи на страница
      *
      * @var int
      */
     protected $listItemsPerPage = 30;
     
     
     /**
      * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
      */
     protected $changeableFields = 'from,to,resultsOn,centre,assetResources,employees';
     
     
     /**
      * Добавя полетата на драйвера към Fieldset
      *
      * @param core_Fieldset $fieldset
      */
     public function addFields(core_Fieldset &$fieldset)
     {
         $fieldset->FLD('from', 'date', 'caption=От,after=title,single=none,mandatory');
         $fieldset->FLD('to', 'date', 'caption=До,after=from,single=none,mandatory');
         
         $fieldset->FLD('forwarderPersonId', 'keylist(mvc=crm_Persons,title=name,allowEmpty)', 'caption=Шофьор,placeholder=Всички,after=to,single=none');
    
         $fieldset->FLD('seeLines', 'set(yes = )', 'caption=Покажи линиите,after=forwarderPersonId,single=none');
         
     
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
         
         
         $suggestions = array();
         foreach (keylist::toArray($rec->forwarderPersonId) as $val) {
             $suggestions[$val] = crm_Persons::fetch($val)->name;
         }
         
        
         
         $fQuery = trans_Lines::getQuery();
         $fQuery->where('#forwarderPersonId IS NOT NULL');
         while ($forwarder = $fQuery->fetch()) {
             $suggestions[$forwarder->forwarderPersonId] = crm_Persons::fetch($forwarder->forwarderPersonId)->name;
         }
         
         $form->setSuggestions('forwarderPersonId', $suggestions);
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
            
            // Проверка на периоди
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
         
         $query = trans_LineDetails::getQuery();
         
         $query->EXT('forwarderPersonId', 'trans_Lines', 'externalName=forwarderPersonId,externalKey=lineId');
         
         $query->EXT('state', 'trans_Lines', 'externalName=state,externalKey=lineId');
         
         $query->EXT('start', 'trans_Lines', 'externalName=start,externalKey=lineId');
         
         $query->EXT('createdOn', 'trans_Lines', 'externalName=createdOn,externalKey=lineId');
         
         $query->where('#forwarderPersonId IS NOT NULL');
         
         $query->where("#status != 'removed'");
         
         $query->in('state',array('active','closed'));
         
         // Ако е посочена начална дата на период
         if ($rec->from) {
             $query->where(array(
                 "#start >= '[#1#]'",
                 $rec->from . ' 00:00:00'
             ));
         }
         
         //Крайна дата / 'към дата'
         if ($rec->to) {
             $query->where(array(
                 "#start <= '[#1#]'",
                 $rec->to . ' 23:59:59'
             ));
         }
         
         
         //Филтър по служители
         if ($rec->forwarderPersonId) {
         
         $forwarderPersonIdArr = keylist::toArray($rec->forwarderPersonId);
         $query->in('forwarderPersonId', $forwarderPersonIdArr);
         }
         
         
         //Складови документи
         $shipDocsArr = array(store_ShipmentOrders::getClassId(),
             store_ConsignmentProtocols::getClassId(),
             store_Receipts::getClassId(),
             store_Transfers::getClassId(),
         );
         
         //Платежни документи(в момента отчита само ПКО)
         $paymentDocsArr = array(cash_Pko::getClassId());
         
         // Синхронизира таймлимита с броя записи //
         $maxTimeLimit = $query->count() * 0.5;
         $maxTimeLimit = max(array($maxTimeLimit, 300));
         core_App::setTimeLimit($maxTimeLimit);
         
         while ($tRec = $query->fetch()) {
             $isShipmentDoc = $isPaymentDoc = 0;
             $weight = $transportUnits = $cashAmount = 0;
             
             $id = ($tRec -> forwarderPersonId) ?$tRec -> forwarderPersonId : 'Не е избран';
             
             $Document = doc_Containers::getDocument($tRec->containerId);
             $transInfo = $Document->getTransportLineInfo($tRec->lineId);
             
             //Адокумента е експедиционен
             if (in_array($tRec->classId, $shipDocsArr)) {
                 $isShipmentDoc = 1;
                 
                 $weight = $transInfo[weight];
                 
                 if (is_array($transInfo[transportUnits])) {
                     $transportUnits = array_sum($transInfo[transportUnits]);
                 }
             }
             
             if (in_array($tRec->classId, $paymentDocsArr)) {
                 
                 $isPaymentDoc = 1;
                 
                 if (($tRec->classId == cash_Pko::getClassId())) {
                     $cashAmount = $transInfo[baseAmount];
                 }
             }
             
             // Запис в масива
             if (!array_key_exists($id, $recs)) {
                 $recs[$id] = (object) array(
                     
                     'forwarderPersonId' => $tRec->forwarderPersonId,
                     'lineId' => array($tRec->lineId),
                     'documents' => array($tRec->classId),
                     'numberOfDocuments' => 1,
                     
                     'shipmentDocs' => $isShipmentDoc,
                     'paymentDocs' => $isPaymentDoc,
                     
                     'weight' => $weight,
                     'transportUnits' => $transportUnits,
                     
                     'cashAmount' => $cashAmount,
                 
                 
                 );
             } else {
                 $obj = &$recs[$id];
                 if (!in_array($tRec->lineId, $obj->lineId)) {
                     array_push($obj->lineId, $tRec->lineId);
                 }
                 array_push($obj->documents, $tRec->classId);
                 ++$obj->numberOfDocuments;
                 
                 $obj->weight += $weight;
                 $obj->transportUnits += $transportUnits;
                 $obj->cashAmount += $cashAmount;
                 
                 $obj->shipmentDocs += $isShipmentDoc;
                 $obj->paymentDocs += $isPaymentDoc;
             }
         }

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
         
         $fld->FLD('forwarderPersonId', 'key(mvc=crm_Persons,select=name)', 'caption=Служител');
         
         $fld->FLD('numberOfLines', 'varchar', 'caption=Брой->линии,tdClass=centered');
         $fld->FLD('numberOfShips', 'varchar', 'caption=Брой->експедиции,tdClass=centered');
         $fld->FLD('numberOfPacks', 'varchar', 'caption=Брой->товари,tdClass=centered');
         $fld->FLD('weight', 'double', 'caption=Общо тегло');
         
         $fld->FLD('numberOfPko', 'varchar', 'caption=ПКО->Брой,tdClass=centered');
         $fld->FLD('sumOfPko', 'double', 'caption=ПКО->сума');
         
         if($rec->seeLines == 'yes'){
            $fld->FLD('lines', 'varchar', 'caption=@Линии');
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
         $Double = cls::get('type_Double');
         $Double->params['decimals'] = 2;
         
         $row = new stdClass();
         
         
         $row->forwarderPersonId = crm_Persons::getHyperlink($dRec->forwarderPersonId).'</br>'.
         core_Users::getNick(crm_Profiles::getUserByPerson($dRec->forwarderPersonId));
         
         $numberOfLines = countR($dRec->lineId);
         $row->numberOfLines = $Int->toVerbal($numberOfLines)."</br>";
         $marker = 0;
         $row->lines = 'ТЛ №: ';
         foreach ($dRec->lineId as $val){
             $marker++;
             $row->lines .= ht::createLink("#$val", toUrl(array('trans_Lines', 'single',$val)));
             if($marker < countR($dRec->lineId)){
                 $row->lines .=', ';
             }
         }
         
         $row->numberOfShips = $Int->toVerbal($dRec->shipmentDocs);
         $row->numberOfPko = $Int->toVerbal($dRec->paymentDocs);
         $row->numberOfPacks = $Int->toVerbal($dRec->transportUnits);
         $row->weight = $Double->toVerbal($dRec->weight);
         $row->sumOfPko = $Double->toVerbal($dRec->cashAmount);
         
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
         {
            $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
                                <fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN from--><div>|От|*: [#from#]</div><!--ET_END from-->
                                        <!--ET_BEGIN to--><div>|До|*: [#to#]</div><!--ET_END to-->
                                        <!--ET_BEGIN employees--><div>|Служители|*: [#employees#]</div><!--ET_END employees-->
                                        <!--ET_BEGIN assetResources--> <div>|Оборудване|*: [#assetResources#]</div><!--ET_END assetResources-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));
            
            if (isset($data->rec->from)) {
                $fieldTpl->append('<b>' . $data->rec->from . '</b>', 'from');
            }
            
            if (isset($data->rec->to)) {
                $fieldTpl->append('<b>' . $data->rec->to . '</b>', 'to');
            }
            
            
            $tpl->append($fieldTpl, 'DRIVER_FIELDS');
        }
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
         if (isset($dRec->forwarderPersonId)) {
             $res->forwarderPersonId = core_Users::getNick(crm_Profiles::getUserByPerson($dRec->forwarderPersonId));
         }
     }
 }
