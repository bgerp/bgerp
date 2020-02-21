<?php


/**
 * Мениджър на отчети за вложени артикули по задания
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2020 Experta OOD
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
    public $canSelectDriver = 'ceo, debug, acc, planning';
    
    
    /**
     * Кои полета от листовия изглед да може да се сортират
     *
     * @var int
     */
    protected $sortableListFields = 'code,name, consumedQuantity,consumedAmount,returnedQuantity,returnedAmount,totalAmount,totalQuantity';
    
    
    /**
     * Кои полета от таблицата в справката да се сумират в обобщаващия ред
     *
     * @var int
     */
    protected $summaryListFields = 'consumedQuantity,consumedAmount,returnedQuantity,returnedAmount,totalAmount,totalQuantity';
    
    
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
        $fieldset->FLD('jobses', 'keylist(mvc=planning_Jobs,allowEmpty)', 'caption=Задания,placeholder=Всички активни,after=title,single=none');
        
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
        $fieldset->FLD('orderBy', 'enum(valior=Дата,name=Артикул, code=Код,amount=Стойност,consumedQuantity=Вложено,returnedQuantity=Върнато,total=Общо)', 'caption=Подреждане по,after=groups,single=none,silent');
     
        //Групиране на резултатите
        $fieldset->FLD('groupBy', 'enum(no=Без групиране,jobId=Задание, valior=Дата,mounth=Месец,year=Година)', 'caption=Групиране по,after=groups,single=none,refreshForm,silent');
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
        $form->setDefault('groupBy', 'no');
        
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
       
        //Показването да бъде ли ГРУПИРАНО
        if ($rec->groupBy != 'no') {
            $this->groupByField = $rec->groupBy;
        }
        
        $recs = array();
        
        //Избрани задания за производство
        if ($rec->jobses){
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
            
            //Ако има избрани задания, вадим само от техните нишки
            if(!empty($jobsThreadArr)){
                $pQuery->in('threadId', ($jobsThreadArr));
            }
            
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
                
                list($year, $mounth) = explode('-',$pRec->valior);
                
                //Себестойност на артикула
                $selfPrice = cat_Products::getPrimeCost($pRec->productId, null, $pRec->quantity, null);
                
                // Запис в масива
                if (!array_key_exists($id, $recs)) {
                    $recs[$id] = (object) array(
                        
                        'jobId' => doc_Threads::getFirstDocument($pRec->threadId)->that,           //Id на заданието
                        'valior' => $pRec->valior,
                        'mounth' => $mounth,
                        'year' => $year,
                        'productId' => $pRec->productId,                                           //Id на артикула
                        'code' => $code,                                                           //код на артикула
                        'name' => $name,                                                           //Име на артикула
                        'selfPrice' => $selfPrice,                                                 //Себестойност на артикула
                        'consumedQuantity' => $consumedQuantity,                                   //Вложено количество
                        'consumedAmount' => $consumedQuantity*$selfPrice,                          //Стойност на вложеното количество
                        
                        'returnedQuantity' => $returnedQuantity,                                   //Върнато количество
                        'returnedAmount' => $returnedQuantity*$selfPrice,                          //Стойност на върнатото количество
                        
                        'totalQuantity' => '',
                        'totalAmount' => '',
                    
                    );
                } else {
                    $obj = &$recs[$id];
                    
                    $obj->consumedQuantity += $consumedQuantity;
                    $obj->consumedAmount += $consumedQuantity*$selfPrice;
                    
                    $obj->returnedQuantity += $returnedQuantity;
                    $obj->returnedAmount += $returnedQuantity*$selfPrice;
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
        
        $fld->FLD('consumedQuantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Вложено->Количество');
        $fld->FLD('consumedAmount', 'double(smartRound,decimals=2)', 'smartCenter,caption=Вложено->Стойност');
        
        $fld->FLD('returnedQuantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Върнато->Количество');
        $fld->FLD('returnedAmount', 'double(smartRound,decimals=2)', 'smartCenter,caption=Върнато->Стойност');
        
        $fld->FLD('totalQuantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Резултат->Количество');
        $fld->FLD('totalAmount', 'double(smartRound,decimals=2)', 'caption=Резултат->Стойност');
        
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
        $Date = cls::get('type_Date');
        
        $row = new stdClass();
        
        if ($rec->groupBy == 'valior'){
            $row->valior = $Date->toVerbal($dRec->valior);
        }
        
        if ($rec->groupBy == 'mounth'){
            
            $row->mounth = $dRec->mounth.'-'.$dRec->year;
        }
        
        if ($rec->groupBy == 'year'){
            
            $row->year = $dRec->year;
        }
        
        if ($rec->groupBy == 'jobId'){
            $row->jobId = planning_Jobs::getLinkToSingle($dRec->jobId);
        }
        
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
        if (isset($dRec->consumedAmount)) {
            $row->consumedAmount = $Double->toVerbal($dRec->consumedAmount);
        }
        
        
        
        if (isset($dRec->returnedQuantity)) {
            $row->returnedQuantity = $Double->toVerbal($dRec->returnedQuantity);
        }
        if (isset($dRec->returnedAmount)) {
            $row->returnedAmount = $Double->toVerbal($dRec->returnedAmount);
        }
        
        
        
        $row->totalQuantity = $Double->toVerbal($dRec->totalQuantity);
        
        $row->totalAmount = $Double->toVerbal($dRec->totalAmount);
        
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
    
}
