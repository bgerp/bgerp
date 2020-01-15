<?php


/**
 * Мениджър на отчети за вложени артикули по задания
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Производство » Вложени артикули по задания
 */
class planning_reports_ConsumedItemsByJob extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, debug';
    
    
    /**
     * Кои полета от листовия изглед да може да се сортират
     *
     * @var int
     */
    protected $sortableListFields = 'code,name, consumedQuantity,returnedQuantity,totalAmount,totalQuantity';
    
    
    /**
     * Кои полета от таблицата в справката да се сумират в обобщаващия ред
     *
     * @var int
     */
    protected $summaryListFields = 'consumedQuantity,returnedQuantity,totalAmount,totalQuantity';
    
    
    /**
     * Как да се казва обобщаващия ред. За да се покаже трябва да е зададено $summaryListFields
     *
     * @var int
     */
    protected $summaryRowCaption = 'ОБЩО';
    
    
    /**
     * Коя комбинация от полета от $data->recs да се следи, ако има промяна в последната версия
     *
     * @var string
     */
    protected $newFieldsToCheck;
    
    
    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField;
    
    
    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'jobses, from, to, groups';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        //Задания
        $fieldset->FLD('jobses', 'keylist(mvc=planning_Jobs,allowEmpty)', 'caption=Задания,placeholder=Избери задание,after=title,single=none,mandatory');
        
        //Период
        $fieldset->FLD('from', 'date', 'caption=От,after=jobses,single=none,mandatory');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,single=none,mandatory');
        
        //Групи артикули
        if (BGERP_GIT_BRANCH == 'dev') {
            $fieldset->FLD('groups', 'keylist(mvc=cat_Groups,select=name, parentId=parentId)', 'caption=Артикули->Група артикули,placeholder = Всички,after=to,single=none');
        } else {
            $fieldset->FLD('groups', 'treelist(mvc=cat_Groups,select=name, parentId=parentId)', 'caption=Артикули->Група артикули,placeholder = Всички,after=to,single=none');
        }
        
        //Подредба на резултатите
        $fieldset->FLD('orderBy', 'enum(name=Артикул, code=Код,amount=Стойност,consumedQuantity=Вложено,returnedQuantity=Върнато,total=Общо)', 'caption=Подреждане по,after=groups,single=none');
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
        
        $form->setDefault('orderBy', 'code');
        $suggestions = array();
        foreach (keylist::toArray($rec->jobses) as $val){
            
            $suggestions[$val]= planning_Jobs::getTitleById($val);
        }
       
        $stateArr = array('active','wakeup');
        
        $jQuery = planning_Jobs::getQuery();
        $jQuery->in('state', $stateArr);
        $jQuery->show('productId');
       
        
        while ($jRec = $jQuery->fetch()) {
            if (!array_key_exists($jRec->id, $suggestions)) {
                $suggestions[$jRec->id] = planning_Jobs::getTitleById($jRec->id);
            }
        }
       
        asort($suggestions);
        
        $form->setSuggestions('jobses', $suggestions);
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
        
        //Избрани задания за производство
        $jobsThreadArr = array();
        foreach (keylist::toArray($rec->jobses) as $val) {
            
            //Масив с ID-та на нишките на избраните ЗАДАНИЯ - $jobsThreadArr
            $jobsThreadArr[$val] = planning_Jobs::fetchField($val, 'threadId');
            $jobsContainersArr[planning_Jobs::fetchField($val, 'threadId')] = planning_Jobs::fetchField($val, 'containerId');
            
        }
        if (!empty($jobsContainersArr)){
            $tQuery = planning_Tasks::getQuery();
            $tQuery->where("#state != 'rejected'");
            $tQuery->in('originId', $jobsContainersArr);
            
           
            while ($tRec = $tQuery->fetch()){
               
                if (in_array($tRec->threadId, $jobsThreadArr))continue;
                $jobsThreadArr[$tRec->originId] = $tRec->threadId;
                
            }
        
        }
        
        //Вложени и върнати артикули в нишките на заданията
        
        $mvcArr = array('planning_DirectProductionNote' => 'planning_DirectProductNoteDetails',
            'planning_ReturnNotes' => 'planning_ReturnNoteDetails',
            'planning_ConsumptionNotes'=>'planning_ConsumptionNoteDetails'
        );
        foreach ($mvcArr as $master => $details) {
            
           
        
        //Вложени и върнати артикули по протоколи, които са в нишките на избраните задания
            $pQuery = $details::getQuery();
            
            $pQuery->EXT('valior', "${master}", 'externalName=valior,externalKey=noteId');
            $pQuery->EXT('state', "${master}", 'externalName=state,externalKey=noteId');
            $pQuery->EXT('threadId', "${master}", 'externalName=threadId,externalKey=noteId');
            $pQuery->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');
            $pQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
            $pQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
             
            
            $pQuery->where(array("#valior >= '[#1#]' AND #valior <= '[#2#]'",$rec->from . ' 00:00:01',$rec->to . ' 23:59:59'));
           
            
            $pQuery->where("#state != 'rejected'");
            $pQuery->where("#canStore != 'no'");
            $pQuery->in('threadId', ($jobsThreadArr));
            
            
            //Филтър по група артикули
            if (isset($rec->groups)) {
                $pQuery->likeKeylist('groups', $rec->groups);
            }
            
            // Синхронизира таймлимита с броя записи
            $timeLimit = $pQuery->count() * 0.05;
            
            if ($timeLimit >= 30) {
                core_App::setTimeLimit($timeLimit);
            }
            
            while ($pRec = $pQuery->fetch()) {
               
                $consumedQuantity = $returnedQuantity = $pRec->quantity;
                
                if ($master == 'planning_ReturnNotes') {
                    $consumedQuantity = 0;
                } else {
                    $returnedQuantity = 0;
                }
                $code = $pRec->code ? $pRec->code : 'Art' . $pRec->productId;
                
                $name = cat_Products::fetch($pRec->productId)->name;
                
                $id = $pRec->productId;
                
                //Себестойност на артикула
                $selfPrice = cat_Products::getPrimeCost($pRec->productId, null, $pRec->quantity, null);
                
                // Запис в масива
                if (!array_key_exists($id, $recs)) {
                    $recs[$id] = (object) array(
                        
                        'productId' => $pRec->productId,                            //Id на артикула
                        'code' => $code,                                            //код на артикула
                        'name' => $name,                                            //Име на артикула
                        'selfPrice' => $selfPrice,                                  //Себестойност на артикула
                        'consumedQuantity' => $consumedQuantity,                    //Вложено количество
                        'returnedQuantity' => $returnedQuantity,                    //Върнато количество
                        'totalQuantity' => '',
                        'totalAmount' => '',
                    
                    );
                } else {
                    $obj = &$recs[$id];
                    
                    $obj->consumedQuantity += $consumedQuantity;
                    $obj->returnedQuantity += $returnedQuantity;
                }
            }
        }
        
        
        
        
        foreach ($recs as $key=> $val){
            
            $val->totalQuantity = $val->consumedQuantity - $val->returnedQuantity;
            $val->totalAmount = ($val->consumedQuantity - $val->returnedQuantity)* $val->selfPrice;
            
        }
        
        //Подредба на резултатите
        if (!is_null($recs)) {
            $typeOrder = ($rec->orderBy == 'name' || $rec->orderBy == 'code') ? 'stri' : 'native';
            
            $orderBy = $rec->orderBy;
            
            arr::sortObjects($recs, $orderBy, 'DESC', $typeOrder);
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
        $fld->FLD('name', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
        $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
        $fld->FLD('consumedQuantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Количество->Вложено');
        $fld->FLD('returnedQuantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Количество->Върнато');
        $fld->FLD('totalQuantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Количество->Общо');
        $fld->FLD('totalAmount', 'double(smartRound,decimals=2)', 'caption=Сума');
        
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
            $row->name = cat_Products::getLinkToSingle_($dRec->productId, 'name');
        }
        
        $row->measure = cat_UoM::fetchField(cat_Products::fetch($dRec->productId)->measureId, 'shortName');
        
        
        if (isset($dRec->consumedQuantity)) {
            $row->consumedQuantity = $Double->toVerbal($dRec->consumedQuantity);
        }
        
        if (isset($dRec->returnedQuantity)) {
            $row->returnedQuantity = $Double->toVerbal($dRec->returnedQuantity);
        }
        
        $row->totalQuantity = $Double->toVerbal($dRec->totalQuantity);
        
        $row->totalAmount = $Double->toVerbal($dRectotalAmount);
        
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
                                <small><div><!--ET_BEGIN jobses-->|Избрани задания|*: [#jobses#]<!--ET_END jobses--></div></small>
                                <small><div><!--ET_BEGIN groups-->|Групи продукти|*: [#groups#]<!--ET_END groups--></div></small>
                                </fieldset><!--ET_END BLOCK-->"));
        if (isset($data->rec->from)) {
            $fieldTpl->append('<b>' .$Date->toVerbal($data->rec->from) . '</b>', 'from');
        }
        
        if (isset($data->rec->to)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->to) . '</b>', 'to');
        }
        
        
        $marker = 0;
        if (isset($data->rec->groups)) {
            foreach (type_Keylist::toArray($data->rec->groups) as $group) {
                $marker++;
                
                $groupVerb .= (cat_Groups::getTitleById($group));
                
                if ((countR((type_Keylist::toArray($data->rec->groups))) - $marker) != 0) {
                    $groupVerb .= ', ';
                }
            }
            
            $fieldTpl->append('<b>' . $groupVerb . '</b>', 'groups');
        } else {
            $fieldTpl->append('<b>' . 'Всички' . '</b>', 'groups');
        }
        
        $marker = 0;
        if (isset($data->rec->jobses)) {
            foreach (type_Keylist::toArray($data->rec->jobses) as $job) {
                $marker++;
                
                $jRec = planning_Jobs::fetch($job);
                
                $jContainer = $jRec->containerId;
                
                $Job = doc_Containers::getDocument($jContainer);
                
                $handle = $Job->getHandle();
                
                $singleUrl = $Job->getUrlWithAccess($Job->getInstance(), $job);
                
                $jobVerb .= ht::createLink("#{$handle}", $singleUrl);
               
                if ((countR((type_Keylist::toArray($data->rec->jobses))) - $marker) != 0) {
                    $jobVerb .= ', ';
                }
            }
            
            $fieldTpl->append('<b>' . $jobVerb . '</b>', 'jobses');
        } else {
            $fieldTpl->append('<b>' . 'Всички' . '</b>', 'jobses');
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
    
    
    /**
     * Кои артикули са произвеждани или доставени през периода soonPeriod в количество повече от soonQuantity
     *
     *
     * @param $prodArr - артикули на склад
     *
     * @return array
     */
    private static function removeSoonDeliveredProds($rec, $prodArr)
    {
        $query = purchase_PurchasesData::getQuery();
        
        $from = dt::addSecs(-($rec->soonPeriod), dt::now());
        $query->where(array("#valior>= '[#1#]' AND #valior <= '[#2#]'",$from,dt::now()));
        $query->where("#isFromInventory = 'no'");
        
        $extractProdArr = arr::extractValuesFromArray($prodArr, 'productId');
        $query->in('productId', $extractProdArr);
        
        foreach (array('purchase_PurchasesDetails','store_ReceiptDetails','acc_ArticleDetails') as $val) {
            $detClassesId[] = core_Classes::getId($val);
        }
        
        $query->in('detailClassId', $detClassesId);
        
        $deliveredProdInPeriod = array();
        while ($prod = $query->fetch()) {
            
            //Артикули които имат доставка през част от периода на стойност заложената част от скл. наличност
            $deliveredProdInPeriod[$prod->productId] += $prod->quantity * $prodArr[$prod->productId]->selfPrice;
        }
        
        foreach ($deliveredProdInPeriod as $key => $val) {
            if ($val > $prodArr[$key]->amount * $rec->soonQuantity) {
                unset($prodArr[$key]) ;
                unset($extractProdArr[$key]) ;
            }
        }
        
        //Произведени артикули
        $planningQuery = planning_DirectProductionNote::getQuery();
        
        $planningQuery->where("#state = 'active'");
        
        $planningQuery->where(array("#valior>= '[#1#]' AND #valior <= '[#2#]'",$from,dt::now()));
        
        $planningQuery->in('productId', $extractProdArr);
        
        $planningProdsInPeriod = array();
        while ($planningProd = $planningQuery->fetch()) {
            $planningProdsInPeriod[$planningProd->productId] += $planningProd->quantity * $prodArr[$planningProd->productId]->selfPrice;
        }
        
        foreach ($planningProdsInPeriod as $key => $val) {
            if ($val > $prodArr[$key]->amount * $rec->soonQuantity) {
                unset($prodArr[$key]) ;
            }
        }
        
        return $prodArr;
    }
}
