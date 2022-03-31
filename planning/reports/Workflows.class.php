<?php
 
 
 /**
  * Мениджър на отчети за заработки
  *
  * @category  bgerp
  * @package   planning
  *
  * @author    Angel Trifonov angel.trifonoff@gmail.com
  * @copyright 2006 - 2019 Experta OOD
  * @license   GPL 3
  *
  * @since     v 0.1
  * @title     Производство » Заработки
  */
 class planning_reports_Workflows extends frame2_driver_TableData
 {
     /**
      * Кой може да избира драйвъра
      */
     public $canSelectDriver = 'ceo,planning,hrMaster';
     
     
     /**
      * Брой записи на страница
      *
      * @var int
      */
     protected $listItemsPerPage = 30;
     
     
     /**
      * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
      */
     protected $changeableFields = 'start,to,resultsOn,centre,assetResources,employees';
     
     
     /**
      * Добавя полетата на драйвера към Fieldset
      *
      * @param core_Fieldset $fieldset
      */
     public function addFields(core_Fieldset &$fieldset)
     {
         $fieldset->FLD('start', 'datetime', 'caption=От,after=title,single=none,mandatory');
         $fieldset->FLD('to', 'datetime', 'caption=До,after=start,single=none,mandatory');
         
         $fieldset->FLD('centre', 'key(mvc=planning_Centers,title=name)', 'caption=Център,removeAndRefreshForm,after=to,silent');
         $fieldset->FLD('assetResources', 'keylist(mvc=planning_AssetResources,title=title)', 'caption=Машини,placeholder=Всички,after=centre,single=none');
         $fieldset->FLD('employees', 'keylist(mvc=crm_Persons,title=name,allowEmpty)', 'caption=Служители,placeholder=Всички,after=assetResources,single=none');
         
         $fieldset->FLD('typeOfReport', 'enum(full=Подробен,short=Опростен)', 'caption=Тип на отчета,after=employees,mandatory,removeAndRefreshForm,single=none');
         
         $fieldset->FLD('resultsOn', 'enum(arts=Артикули,users=Служители,usersMachines=Служители по машини,machines=Машини)', 'caption=Разбивка по,maxRadio=4,columns=4,after=typeOfReport,single=none');
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
         
         $form->setDefault('typeOfReport', 'short');
         $form->setDefault('resultsOn', 'users');
         $form->input('typeOfReport');
         if ($rec->typeOfReport == 'short') {
             $form->setField('resultsOn', 'input=none');
         }
         
         
         if ($rec->centre) {

             $suggestions = array();
             $suggestions = planning_Hr::getByFolderId(planning_Centers::fetch($rec->centre)->folderId);

             foreach ($suggestions as $key => $val) {
                 $suggestions[$key] = crm_Persons::fetch($key)->name;
             }
             
             $form->setSuggestions('employees', $suggestions);
             
             $suggestions = '';
             
             $suggestions = planning_AssetResources::getByFolderId(planning_Centers::fetch($rec->centre)->folderId);
             
             $form->setSuggestions('assetResources', $suggestions);
         }
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
             if (isset($form->rec->start, $form->rec->to) && ($form->rec->start > $form->rec->to)) {
                 $form->setError('start,to', 'Началната дата на периода не може да бъде по-голяма от крайната.');
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
         
         $query = planning_ProductionTaskDetails::getQuery();
         
         $query->EXT('indTimeAllocation', 'planning_Tasks', 'externalName=indTimeAllocation,externalKey=taskId');
         $query->EXT('folderId', 'planning_Tasks', 'externalName=folderId,externalKey=taskId');
         
         $query->where("#state != 'rejected' ");

         // Ако е посочена начална дата на период
         if ($rec->start) {
             $query->where(array(
                 "#createdOn >= '[#1#]'",
                 $rec->start . ' 00:00:00'
             ));
         }
         
         //Крайна дата / 'към дата'
         if ($rec->to) {
             $query->where(array(
                 "#createdOn <= '[#1#]'",
                 $rec->to . ' 23:59:59'
             ));
         }

         //Филтър по център на дейност
         if ($rec->centre) {
             $cFolderId = planning_Centers::fetch($rec->centre)->folderId;
             $query->where("#folderId = $cFolderId");
         }
         
         //Филтър по служители
         if ($rec->employees) {
             $query->likeKeylist('employees', $rec->employees);
         }
         
         
         //Филтър по машини
         if ($rec->assetResources) {
             $assetArr = keylist::toArray($rec->assetResources);
             
             $query->in('fixedAsset', $assetArr);
         }

         while ($tRec = $query->fetch()) {
             $id = self::breakdownBy($tRec, $rec);

             $labelQuantity = 1;
             $employees = $tRec->employees;
             
             $counter = ($rec->typeOfReport == 'short') ? keylist::toArray($tRec->employees):array($id => $id);

             if ($rec->employees && $rec->typeOfReport == 'short') {
                 $counter = array_intersect($counter, keylist::toArray($rec->employees));
             }

             foreach ($counter as $val) {
                 $Task = doc_Containers::getDocument(planning_Tasks::fetchField($tRec->taskId, 'containerId'));
                 $iRec = $Task->fetch('id,containerId,measureId,folderId,quantityInPack,labelPackagingId,indTime,indPackagingId,indTimeAllocation,totalQuantity');

                 if(!empty($iRec->indTime)){
                     $iRec->indTime = planning_type_ProductionRate::getInSecsByQuantity($iRec->indTime, $iRec->quantityInPack);
                 }
                  $divisor = countR(keylist::toArray($tRec->employees));
                 if ($rec->typeOfReport == 'short') {

                     $id = $val;
                     
                     $labelQuantity = 1 / $divisor;

                     $employees = $val;
                 }

                 if ($divisor){

                     $timeAlocation = ($tRec->indTimeAllocation == 'common') ? 1 / $divisor : 1;
                     $indTimeSum = $timeAlocation * $iRec->indTime;

                 }else{
                     $indTimeSum = 0;
                 }

                 $pRec = cat_Products::fetch($tRec->productId, 'measureId,name');

                 // Запис в масива
                 if (!array_key_exists($id, $recs)) {
                     $recs[$id] = (object) array(
                         
                         'taskId' => $tRec->taskId,
                         'detailId' => $tRec->id,
                         'indTime' => $iRec->indTime,
                         'indTimeSum' => $indTimeSum,
                         'indPackagingId' => $iRec->indPackagingId,
                         'indTimeAllocation' => $iRec->indTimeAllocation,
                         'quantityInPack' => $iRec->quantityInPack,
                         
                         'employees' => $employees,
                         'assetResources' => $tRec->fixedAsset,
                         
                         'productId' => $tRec->productId,
                         'measureId' => $pRec->measureId,
                         
                         'quantity' => $tRec->quantity,
                         'scrap' => $tRec->scrappedQuantity,
                         
                         'labelMeasure' => $iRec->labelPackagingId,
                         'labelQuantity' => $labelQuantity,
                         
                         'weight' => $tRec->weight,
                     
                     );
                 } else {
                     $obj = &$recs[$id];
                     
                     $obj->quantity += $tRec->quantity;
                     $obj->scrap += $tRec->scrappedQuantity;
                     $obj->labelQuantity += $labelQuantity;
                     $obj->indTimeSum += $indTimeSum;
                     
                     $obj->weight += $tRec->weight;
                 }
             }
         }

         //Когато е избран тип на справката - ПОДРОБНА
         if ($rec->typeOfReport == 'full') {
         
         //Разпределяне по работници, когато са повече от един
             foreach ($recs as $key => $val) {
                 if (countR(keylist::toArray($val->employees)) > 1) {
                     $clone = clone $val;
                     
                     $divisor = countR(keylist::toArray($val->employees));
                     
                     foreach (keylist::toArray($val->employees) as $k => $v) {
                         unset($id);
                         
                         if (!is_null($rec->employees) && !in_array($v, keylist::toArray($rec->employees))) {
                             continue;
                         }

                         if ($rec->resultsOn == 'arts') {
                             $id = $val->taskId.'|'.$val->productId.'|';
                         }

                         if ($rec->resultsOn == 'machines') {
                              $id = $val->taskId.'|'.$val->productId.'|'.'|'.$val->assetResources.'|';
                         }

                         if ($rec->resultsOn == 'users') {
                             $id = $val->taskId.'|'.$val->productId.'|'.'|'.$v.'|';
                         }
                         if ($rec->resultsOn == 'usersMachines') {
                             $id = $val->taskId.'|'.$val->productId.'|'.'|'.$v.'|'.'|'.$val->assetResources;
                         }

                         if ($divisor){
                             $timeAlocation = ($clone->indTimeAllocation == 'common') ? 1 / $divisor : 1;
                             $indTimeSum = $timeAlocation * $clone->indTime;
                         }else{
                             $indTimeSum = 0;
                         }

                         $clone = clone $val;

                         if (!array_key_exists($id, $recs)) {
                             $recs[$id] = (object) array(
                                 
                                 'taskId' => $clone->taskId,
                                 'detailId' => $clone->detailId,
                                 'indTime' => $clone->indTime,
                                 'indPackagingId' => $clone->indPackagingId,
                                 'indTimeAllocation' => $clone->indTimeAllocation,
                                 
                                 'indTimeSum' => $indTimeSum,
                                 
                                 'employees' => '|'.$v.'|',
                                 'assetResources' => $clone->assetResources,
                                 
                                 'productId' => $clone->productId,
                                 'measureId' => $clone->measureId,
                                 
                                 'quantity' => $clone->quantity / $divisor,
                                 'scrap' => $clone->scrap / $divisor,
                                 
                                 'labelMeasure' => $clone->labelMeasure,
                                 'labelQuantity' => $clone->labelQuantity / $divisor,
                                 
                                 'weight' => $clone->weight / $divisor,
                             
                             );
                         } else {
                             $obj = &$recs[$id];
                             
                             $obj->quantity += $clone->quantity / $divisor;
                             $obj->scrap += $clone->scrap / $divisor;
                             $obj->labelQuantity += $clone->labelQuantity / $divisor;
                             $obj->weight += $clone->weight / $divisor;
                             $obj->indTimeSum += $indTimeSum;
                         }
                     }
                     unset($recs[$key]);
                 }
             }

             arr::sortObjects($recs, 'taskId', 'asc');
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
         
         if ($export === false) {
             if ($rec->typeOfReport == 'full') {
                 $fld->FLD('taskId', 'varchar', 'caption=Операция');
                 $fld->FLD('article', 'varchar', 'caption=Артикул');
                 
                 $fld->FLD('measureId', 'varchar', 'caption=Произведено->Мярка,tdClass=centered');
                 $fld->FLD('quantity', 'double', 'caption=Произведено->Кол');
                 $fld->FLD('scrap', 'double', 'caption=Брак');
                 $fld->FLD('weight', 'double', 'caption=Тегло');
                 $fld->FLD('min', 'double(smartRound,decimals=2)', 'caption=Минути');
                 if ($rec->resultsOn != 'arts') {
                     if ($rec->resultsOn == 'users' || $rec->resultsOn == 'usersMachines') {
                         $fld->FLD('employees', 'varchar', 'caption=Служител');
                     }
                     if ($rec->resultsOn == 'usersMachines' || $rec->resultsOn == 'machines') {
                         $fld->FLD('assetResources', 'varchar', 'caption=Оборудване');
                     }
                 }
             }
             if ($rec->typeOfReport == 'short') {
                 $fld->FLD('employees', 'varchar', 'caption=Служител');
                 $fld->FLD('indTimeSum','double(smartRound,decimals=2)', 'caption=Време->min,tdClass=centered');
             }
             $fld->FLD('labelMeasure', 'varchar', 'caption=Етикет->мярка,tdClass=centered');
             $fld->FLD('labelQuantity', 'varchar', 'caption=Етикет->кол,tdClass=centered');
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
         $Time = cls::get('type_Time');
         $Double = cls::get('type_Double');
         $Double->params['decimals'] = 2;
         $row = new stdClass();

         $row->taskId = planning_Tasks::getHyperlink($dRec->taskId, true);
         $row->article = cat_Products::getHyperlink($dRec->productId, true);
         
         $row->measureId = cat_UoM::getShortName($dRec->measureId);
         $row->quantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->quantity);
         
         $row->labelMeasure = isset($dRec->labelMeasure)? cat_UoM::getShortName($dRec->labelMeasure) :'';
         
         
         $row->labelQuantity = $Double->toVerbal($dRec->labelQuantity);
         
         $row->scrap = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->scrap);
         $row->weight = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->weight);
         
         
         if ($rec->typeOfReport == 'short' && isset($dRec->employees)) {
             $row->employees = crm_Persons::getTitleById(($dRec->employees)).' - '.planning_Hr::getCodeLink($dRec->employees);
             $row->indTimeSum = $Double->toVerbal($dRec->indTimeSum/60);
         } else {
             if (isset($dRec->employees)) {
                 foreach (keylist::toArray($dRec->employees) as $key => $val) {
                     $pers = (planning_Hr::getCodeLink(($val)));
                     
                     $row->employees .= $pers.'</br>';
                 }
             }
         }
         if (isset($dRec->assetResources)) {
             $row->assetResources = planning_AssetResources::fetch($dRec->assetResources)->name;
         } else {
             $row->assetResources = '';
         }

         $indTimeSumm = ($dRec->indTime * $row->labelQuantity);
         //$row->min = $Time->toVerbal($indTimeSumm);
         $row->min =$Double->toVerbal($indTimeSumm/60);
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
         $Date = cls::get('type_Datetime');
         {
            $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
                                <fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN start--><div>|От|*: [#start#]</div><!--ET_END start-->
                                        <!--ET_BEGIN to--><div>|До|*: [#to#]</div><!--ET_END to-->
                                        <!--ET_BEGIN employees--><div>|Служители|*: [#employees#]</div><!--ET_END employees-->
                                        <!--ET_BEGIN assetResources--><div>|Оборудване|*: [#assetResources#]</div><!--ET_END assetResources-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));
            
            if (isset($data->rec->start)) {
                $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->start) . '</b>', 'start');
            }
            
            if (isset($data->rec->to)) {
                $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->to) . '</b>', 'to');
            }
            
            
            if (($data->rec->resultsOn == 'users' || $data->rec->resultsOn == 'usersMachines')) {
                if (isset($data->rec->employees)) {
                    $marker = 0;
                    foreach (type_Keylist::toArray($data->rec->employees) as $empl) {
                        $marker++;
                        
                        $employeesVerb .= (planning_Hr::getCodeLink(($empl)));
                        
                        if ((countR(type_Keylist::toArray($data->rec->employees))) - $marker != 0) {
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
                        
                        $employeesVerb .= (planning_Hr::getCodeLink(($empl)));
                        
                        if ((countR(type_Keylist::toArray($data->rec->employees))) - $marker != 0) {
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
                
                if ((countR(type_Keylist::toArray($data->rec->assetResources))) - $marker != 0) {
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


     /**
      * След рендиране на единичния изглед
      *
      * @param frame2_driver_Proto $Driver
      * @param embed_Manager $Embedder
      * @param core_ET $tpl
      * @param stdClass $data
      */
     protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
     {
         $row->centre = planning_Centers::getHyperlink($rec->centre, true);
     }
 }
