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
  * @title     Логистика » Линии по шофьор
  */
 class trans_reports_LinesByForwarder extends frame2_driver_TableData
 {
     /**
      * Кой може да избира драйвъра
      */
     public $canSelectDriver = 'ceo';
     
     
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
         $suggestions = '';
         
         // $suggestions = planning_Hr::getByFolderId(planning_Centers::fetch($rec->centre)->folderId);
         
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
         
         //  $query = trans_Lines::getQuery();
         
         $query->EXT('forwarderPersonId', 'trans_Lines', 'externalName=forwarderPersonId,externalKey=lineId');
         
         $query->EXT('state', 'trans_Lines', 'externalName=state,externalKey=lineId');
         
         $query->EXT('createdOn', 'trans_Lines', 'externalName=createdOn,externalKey=lineId');
         
         $query->where('#forwarderPersonId IS NOT NULL');
         
         $query->where("#state != 'rejected' ");
         
         // Ако е посочена начална дата на период
         if ($rec->from) {
             $query->where(array(
                 "#createdOn >= '[#1#]'",
                 $rec->from . ' 00:00:00'
             ));
         }
         
         //Крайна дата / 'към дата'
         if ($rec->to) {
             $query->where(array(
                 "#createdOn <= '[#1#]'",
                 $rec->to . ' 23:59:59'
             ));
         }
         
         
         //Филтър по служители
         if ($rec->employees) {
             $query->likeKeylist('employees', $rec->employees);
         }
         
         
         while ($tRec = $query->fetch()) {
             $arr[] = ($tRec);
         }
         
         $id = self::breakdownBy($tRec, $rec);
         
         $Task = doc_Containers::getDocument(planning_Tasks::fetchField($tRec->taskId, 'containerId'));
         
         
         $iRec = $Task->fetch('id,containerId,measureId,folderId,quantityInPack,packagingId,indTime,indPackagingId,indTimeAllocation');
         $pRec = cat_Products::fetch($tRec->productId, 'measureId,name');
         
         // Запис в масива
         if (!array_key_exists($id, $recs)) {
             $recs[$id] = (object) array(
                 
                 'taskId' => $tRec->taskId,
                 'detailId' => $tRec->id,
                 'indTime' => $iRec->indTime,
                 'indPackagingId' => $irec->indPackagingId,
                 'indTimeAllocation' => $iRec->indTimeAllocation,
                 
                 'employees' => $tRec->employees,
                 'assetResources' => $tRec->fixedAsset,
                 
                 'productId' => $tRec->productId,
                 'measureId' => $pRec->measureId,
                 
                 'quantity' => $tRec->quantity,
                 'scrap' => $tRec->scrappedQuantity,
                 
                 'labelMeasure' => $iRec->packagingId,
                 'labelQuantity' => 1,
                 
                 'weight' => $tRec->weight,
             
             );
         } else {
             $obj = &$recs[$id];
             
             $obj->quantity += $tRec->quantity;
             $obj->scrap += $tRec->scrappedQuantity;
             ++$obj->labelQuantity;
             
             $obj->weight += $tRec->weight;
         }
         
         //    }
         
         
         //Разпределяне по работници, когато са повече от един
         foreach ($recs as $key => $val) {
             if (count(keylist::toArray($val->employees)) > 1) {
                 $clone = clone $val;
                 
                 $divisor = count(keylist::toArray($val->employees));
                 
                 foreach (keylist::toArray($val->employees) as $k => $v) {
                     unset($id);
                     
                     if (!is_null($rec->employees) && !in_array($v, keylist::toArray($rec->employees))) {
                         continue;
                     }
                     
                     if ($rec->resultsOn == 'users') {
                         $id = $val->taskId.'|'.$val->productId.'|'.'|'.$v.'|';
                     }
                     if ($rec->resultsOn == 'usersMachines') {
                         $id = $val->taskId.'|'.$val->productId.'|'.'|'.$v.'|'.'|'.$val->assetResources;
                     }
                     
                     $clone = clone $val;
                     
                     if (!array_key_exists($id, $recs)) {
                         $recs[$id] = (object) array(
                             
                             'taskId' => $clone->taskId,
                             'detailId' => $clone->detailId,
                             'indTime' => $iRec->indTime,
                             'indPackagingId' => $irec->indPackagingId,
                             'indTimeAllocation' => $iRec->indTimeAllocation,
                             
                             'employees' => '|'.$v.'|',
                             'assetResources' => $clone->assetResources,
                             
                             'productId' => $clone->productId,
                             'measureId' => $clone->measureId,
                             
                             'quantity' => $clone->quantity / $divisor,
                             'scrap' => $clone->scrap / $divisor,
                             
                             'labelMeasure' => $clone->labelMeasure,
                             'labelQuantity' => 1,
                             
                             'weight' => $clone->weight / $divisor,
                         
                         );
                     } else {
                         $obj = &$recs[$id];
                         
                         $obj->quantity += $clone->quantity / $divisor;
                         $obj->scrap += $clone->scrap / $divisor;
                         ++$obj->labelQuantity;
                         $obj->weight += $clone->weight / $divisor;
                     }
                 }
                 unset($recs[$key]);
             }
         }
         
         arr::sortObjects($recs, 'taskId', 'asc');
         
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
             $fld->FLD('taskId', 'varchar', 'caption=Задача');
             $fld->FLD('article', 'varchar', 'caption=Артикул');
             $fld->FLD('min', 'varchar', 'caption=Минути');
             if ($rec->resultsOn != 'arts') {
                 if ($rec->resultsOn == 'users' || $rec->resultsOn == 'usersMachines') {
                     $fld->FLD('employees', 'varchar', 'caption=Служител');
                 }
                 if ($rec->resultsOn == 'usersMachines' || $rec->resultsOn == 'machines') {
                     $fld->FLD('assetResources', 'varchar', 'caption=Оборудване');
                 }
             }
             $fld->FLD('measureId', 'varchar', 'caption=Произведено->Мярка,tdClass=centered');
             $fld->FLD('quantity', 'double', 'caption=Произведено->Кол');
             $fld->FLD('labelMeasure', 'varchar', 'caption=Етикет->мярка,tdClass=centered');
             $fld->FLD('labelQuantity', 'varchar', 'caption=Етикет->кол,tdClass=centered');
             $fld->FLD('scrap', 'double', 'caption=Брак');
             $fld->FLD('weight', 'double', 'caption=Тегло');
         } else {
             $fld->FLD('taskId', 'varchar', 'caption=Задача');
             $fld->FLD('article', 'varchar', 'caption=Артикул');
             
             $fld->FLD('measureId', 'varchar', 'caption=Произведено->Мярка,tdClass=centered');
             $fld->FLD('quantity', 'varchar', 'caption=Произведено->Кол');
             $fld->FLD('labelMeasure', 'varchar', 'caption=Етикет->мярка,tdClass=centered');
             $fld->FLD('labelQuantity', 'varchar', 'caption=Етикет->кол,tdClass=centered');
             $fld->FLD('scrap', 'varchar', 'caption=Брак');
             $fld->FLD('weight', 'varchar', 'caption=Тегло');
             $fld->FLD('employees', 'varchar', 'caption=Служител');
             $fld->FLD('assetResources', 'varchar', 'caption=Оборудване,tdClass=centered');
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
         
         $row = new stdClass();
         
         
         $row->taskId = planning_Tasks::getHyperlink($dRec->taskId);
         $row->article = cat_Products::getHyperlink($dRec->productId);
         
         $row->measureId = cat_UoM::getShortName($dRec->measureId);
         $row->quantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->quantity);
         
         $row->labelMeasure = isset($dRec->labelMeasure)? cat_UoM::getShortName($dRec->labelMeasure) :'';
         
         
         $row->labelQuantity = $Int->toVerbal($dRec->labelQuantity);
         
         $row->scrap = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->scrap);
         $row->weight = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->weight);
         
         if (isset($dRec->employees)) {
             foreach (keylist::toArray($dRec->employees) as $key => $val) {
                 $pers = (core_Users::getNick(crm_Profiles::getUserByPerson($val)));
                 
                 $row->employees .= $pers.'</br>';
             }
         }
         
         if (isset($dRec->assetResources)) {
             $row->assetResources = planning_AssetResources::fetch($dRec->assetResources)->name;
         } else {
             $row->assetResources = '';
         }
         
         $indTimeSumm = ($dRec->indTime * $dRec->quantity) / 60;
         
         $row->min = core_Type::getByName('double(decimals=2)')->toVerbal($indTimeSumm);
         
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
                                <small><div><!--ET_BEGIN from-->|От|*: [#from#]<!--ET_END from--></div></small>
                                <small><div><!--ET_BEGIN to-->|До|*: [#to#]<!--ET_END to--></div></small>
                                <small><div><!--ET_BEGIN employees-->|Служители|*: [#employees#]<!--ET_END employees--></div></small>
                                <small><div><!--ET_BEGIN assetResources-->|Оборудване|*: [#assetResources#]<!--ET_END assetResources--></div></small>
                                </fieldset><!--ET_END BLOCK-->"));
            
            if (isset($data->rec->from)) {
                $fieldTpl->append('<b>' . $data->rec->from . '</b>', 'from');
            }
            
            if (isset($data->rec->to)) {
                $fieldTpl->append('<b>' . $data->rec->to . '</b>', 'to');
            }
            
            
            if (($data->rec->resultsOn == 'users' || $data->rec->resultsOn == 'usersMachines')) {
                if (isset($data->rec->employees)) {
                    $marker = 0;
                    foreach (type_Keylist::toArray($data->rec->employees) as $empl) {
                        $marker++;
                        
                        $employeesVerb .= (core_Users::getNick(crm_Profiles::getUserByPerson($empl)));
                        
                        if ((count(type_Keylist::toArray($data->rec->employees))) - $marker != 0) {
                            $employeesVerb .= ', ';
                        }
                    }
                    
                    $fieldTpl->append('<b>' . $employeesVerb . '</b>', 'employees');
                } else {
                    $fieldTpl->append('<b>' . 'Всички от този център на дейност' . '</b>', 'employees');
                }
            } else {
                if (isset($data->rec->employees)) {
                    $marker = 0;
                    foreach (type_Keylist::toArray($data->rec->employees) as $empl) {
                        $marker++;
                        
                        $employeesVerb .= (core_Users::getNick(crm_Profiles::getUserByPerson($empl)));
                        
                        if ((count(type_Keylist::toArray($data->rec->employees))) - $marker != 0) {
                            $employeesVerb .= ', ';
                        }
                    }
                    
                    $fieldTpl->append('<b>' . $employeesVerb . '</b>', 'employees');
                }
            }
        
        if (isset($data->rec->assetResources)) {
            $marker = 0;
            foreach (type_Keylist::toArray($data->rec->assetResources) as $asset) {
                $marker++;
                
                $assetVerb .= planning_AssetResources::fetch($asset)->name;
                
                if ((count(type_Keylist::toArray($data->rec->assetResources))) - $marker != 0) {
                    $assetVerb .= ', ';
                }
            }
            
            $fieldTpl->append('<b>' . $assetVerb . '</b>', 'assetResources');
        }
            
            $tpl->append($fieldTpl, 'DRIVER_FIELDS');
        }
     }
     
     
     /**
      * Кой може да избере драйвера
      * ceo, planning+officer
      */
     public function canSelectDriver($userId = null)
     {
         if (haveRole('ceo', $userId)) {
             
             return true;
         }
         
         if (!haveRole('ceo', $userId) && haveRole('planning', $userId)) {
             if (haveRole('officer', $userId)) {
                 
                 return true;
             }
             
             return false;
         }
         
         return false;
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
         $res->taskId = planning_Tasks::getTitleById($dRec->taskId);
         $res->article = cat_Products::getTitleById($dRec->productId);
         
         if (isset($dRec->employees)) {
             foreach (keylist::toArray($dRec->employees) as $key => $val) {
                 $pers = (core_Users::getNick(crm_Profiles::getUserByPerson($val)));
                 
                 $res->employees .= $pers.', ';
             }
         }
         
         if (isset($dRec->assetResources)) {
             $res->assetResources = planning_AssetResources::fetch($dRec->assetResources)->name;
         } else {
             $res->assetResources = '';
         }
     }
     
     
     /**
      * Връща ключ по който да се направи разбивка на резултата
      *
      * @param stdClass $rec
      *
      * @return string
      */
     public static function breakdownBy($tRec, $rec)
     {
         $key = '';
         
         switch ($rec->resultsOn) {
            
            case 'arts':$key = $tRec->taskId.'|'.$tRec->productId; break;
            
            case 'users':$key = $tRec->taskId.'|'.$tRec->productId.'|'.$tRec->employees; break;
            
            case 'usersMachines':$key = $tRec->taskId.'|'.$tRec->productId.'|'.$tRec->employees.'|'.$tRec->fixedAsset; break;
            
            case 'machines':$key = $tRec->taskId.'|'.$tRec->productId.'|'.$tRec->fixedAsset; break;
        
        }
         
         return $key;
     }
 }
