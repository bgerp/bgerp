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
 * @title     Производство » Планиране на материали
 */
class planning_reports_MaterialPlanning extends frame2_driver_TableData
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
    protected $sortableListFields;
    
    
    /**
     * Кои полета от таблицата в справката да се сумират в обобщаващия ред
     *
     * @var int
     */
    protected $summaryListFields;
    
    
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
    protected $groupByField = 'week';
    
    
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
        
        $fieldset->FLD('weeks', 'int', 'caption=Брой седмици,after=horizon');
        
        //Групи артикули
        if (BGERP_GIT_BRANCH == 'dev') {
            $fieldset->FLD('groups', 'keylist(mvc=cat_Groups,select=name, parentId=parentId)', 'caption=Артикули->Група артикули,placeholder = Всички,after=to,single=none');
        } else {
            $fieldset->FLD('groups', 'treelist(mvc=cat_Groups,select=name, parentId=parentId)', 'caption=Артикули->Група артикули,placeholder = Всички,after=to,single=none');
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
        
        $form->setDefault('weeks', 8);
        
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
        $today = dt::today();
        
        $thisWeek = date("W", strtotime($today));
        $year = date("Y", strtotime($today));
       
        //Кои седмици влизат в отчета $weeksForCheck
        $weeksForCheck = array();
        $weekMarker = 0;
        for ($i = $thisWeek; $i < $thisWeek+$rec->weeks; $i++) {
            $weekNumber = $i - $weekMarker;
            $week = $i - $weekMarker.'-'.$year;
            $endDayOfWeek = self::getStartAndEndDate($weekNumber, $year)[1];
            
            if($endDayOfWeek > $year . '-12-31'){
                $weekMarker = $i;
                $year = date("Y", strtotime($endDayOfWeek));
                
            }
            
            array_push($weeksForCheck,$week);
            unset($week);
        }
        
        $jobsQuery = planning_Jobs::getQuery();
        $jobsQuery->where("#state != 'rejected' AND #state != 'closed' AND #state != 'draft'");
        
        list($lastWeek,$lastYear) = explode('-', end($weeksForCheck));
        
        $endDay = self::getStartAndEndDate($lastWeek, $lastYear)[1];
        
        $jobsQuery->where(array("#quantity > #quantityProduced AND #dueDate <= '[#1#]'", $endDay . ' 23:59:59'));
       
        $jobsQuery->show('quantity,quantityProduced,productId,dueDate');
        
        
        $jobsRecsArr = $jobsQuery->fetchAll();
       
       //Добавяне на виртуалните задания
       $vJobsArr = self::createdVirtualJobs($endDay);
       
       $jobsRecsArr = array_merge($jobsRecsArr,$vJobsArr);
       
       foreach ($jobsRecsArr as $jobsRec){
            $materialsArr = array();
            
            $quantityRemaining = $jobsRec->quantity - $jobsRec->quantityProduced;
               
            $materialsArr = cat_Products::getMaterialsForProduction($jobsRec->productId,$quantityRemaining);
              
                if (!empty($materialsArr)){
                foreach ($materialsArr as $val){
                    
                    $matRec = cat_Products::fetch($val[productId]);
                    
                    //Филтрира само складируеми материали
                    if ($matRec->canStore == 'no'){
                        continue;
                    }
                    
                    //Ако има избрана група или групи материали
                    if ($rec->groups){
                        $groupsArr = keylist::toArray($rec->groups);
                        if (!keylist::isIn($groupsArr, $matRec->groups))
                            continue;
                        
                    }
                    
                    $week =($jobsRec->week)?$jobsRec->week : date("W", strtotime($jobsRec->dueDate)).'-'.date("Y", strtotime($jobsRec->dueDate));
                   
                    //Ако падежа е изткъл, заданието се отнася към нулева седмица
                    if ($jobsRec->dueDate && $jobsRec->dueDate < $today) {
                        $week = '0-0';
                    }
                    
                    $doc = ($jobsRec->id) ? 'planning_Jobs'.'|'.$jobsRec->id : 'sales_Sales'.'|'.$jobsRec->saleId;
                    
                    $recsKey = $week .' | '.$val[productId];
                    // Запис в масива
                    if (!array_key_exists($recsKey, $recs)) {
                        $recs[$recsKey] = (object) array(
                            
                            'week'=> $week,
                            
                            'originDoc'=> array($doc),
                            'jobProductId' => $jobsRec->productId,                                           //Id на артикула
                            'quantityRemaining' => $quantityRemaining,                                       // Оставащо количество
                            
                            'materialId'=> $val[productId],
                            'materialQuantiry'=> $val[quantity],
                            
                        );
                    } else {
                        $obj = &$recs[$recsKey];
                        
                        $obj->quantityRemaining += $quantityRemaining;
                        $obj->materialQuantiry += $val[quantity];
                        array_push($obj->originDoc, $doc);
                    }
                }
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
        
        $fld->FLD('materialId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
        $fld->FLD('docs', 'varchar', 'smartCenter,caption=@Задания');
        
        $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
        
        $fld->FLD('materialQuantiry', 'double(smartRound,decimals=2)', 'smartCenter,caption=Необходимо Количество');
       
        return $fld;
    }
    
    
    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отч$sQueryета
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
        
        $row->week = $dRec->week;
        
        if (isset($dRec->materialId)) {
            $row->materialId = cat_Products::getLinkToSingle_($dRec->materialId, 'name');
 
        } 
        $marker=0;
        foreach ($dRec->originDoc as $originDoc) {
            $marker++;
            
            list($docClassName,$doc)=explode('|', $originDoc);
            $docRec = $docClassName::fetch($doc);
           
            $docContainer = $docRec->containerId;
           
            $Document = doc_Containers::getDocument($docContainer);
            $handle =($docClassName != 'planning_Jobs') ? 'VJ-'.$Document->getHandle() : $Document->getHandle();
             
             $singleUrl = $Document->getUrlWithAccess($Document->getInstance(), $originDoc);
            
             $row->docs .=  ht::createLink("#{$handle}", $singleUrl);
            
             if ((countR(($dRec->originDoc )) - $marker) != 0) { 
                $row->docs .= ', ';
            }
        }
    
        $row->measure = cat_UoM::fetchField(cat_Products::fetch($dRec->materialId)->measureId, 'shortName');
        
        
        if (isset($dRec->materialQuantiry)) {
            $row->materialQuantiry = $Double->toVerbal($dRec->materialQuantiry);
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
        $Enum = cls::get('type_Enum', array('options' => array('selfPrice' => 'политика"Себестойност"','catalog' => 'политика"Каталог"', 'accPrice' => 'Счетоводна')));
        $currency = 'лв.';
        
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                <small><div><!--ET_BEGIN groups-->|Групи продукти|*: [#groups#]<!--ET_END groups--></div></small>
                                </fieldset><!--ET_END BLOCK-->"));
        
    
        
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
    
    
    public static function getStartAndEndDate($week, $year){
        $dates[0] = date("Y-m-d", strtotime($year.'W'.str_pad($week, 2, 0, STR_PAD_LEFT)));
        $dates[1] = date("Y-m-d", strtotime($year.'W'.str_pad($week, 2, 0, STR_PAD_LEFT).' +6 days'));
        return $dates;
    }
    
    public static function createdVirtualJobs($endDay){
        
        //Активни договори за продажба със срок за доставка към края на избаните седници
        $sQuery = sales_SalesDetails::getQuery();
       
        $sQuery->EXT('state', 'sales_Sales', 'externalName=state,externalKey=saleId');
        
        $sQuery->where("#state = 'active'");
        
        $sQuery->EXT('deliveryTime', 'sales_Sales', 'externalName=deliveryTime,externalKey=saleId');
        $sQuery->EXT('deliveryTermTime', 'sales_Sales', 'externalName=deliveryTermTime,externalKey=saleId');
        $sQuery->EXT('valior', 'sales_Sales', 'externalName=valior,externalKey=saleId');
       
        //Проверка дали датата на доставка е в периода(ако е NULL осавяме записа за проверка на "срока на доставка")
        $sQuery->where(array("#deliveryTime <= '[#1#]' OR #deliveryTime IS NULL", $endDay . ' 23:59:59'));
        
        $salesIdArr = arr::extractValuesFromArray($sQuery->fetchAll(), 'saleId');
        
        
        //Задания за производство към договорите за този период $jobsArr
        $jobsQuery = planning_Jobs::getQuery();
        $jobsQuery->where("#state != 'rejected' AND #state != 'draft'");
        $jobsQuery->in('saleId',$salesIdArr);
        
        $jobsArr = array();
        while ($jobRec = $jobsQuery->fetch()) {
            
            $key = $jobRec->saleId.'|'.$jobRec->productId;
            
            $quantity = $jobRec->quantity * $jobRec->quantityInPack;
            
            
            if (!array_key_exists($key, $jobsArr)) {
                 $jobsArr[$key] = (object)array('saleId'=>$jobRec->saleId,
                                                  'productId'=>$jobRec->productId,
                                                  'quantity'=>$quantity
                
            );
            
             }else{
                 $obj = & $jobsArr[$key];
                 
                 $obj->quantity += $quantity;
                 
             }
            
        }
        
        //Създаване на виртуални задания за производство
        //Договорените количества от активните договори минус заявените количества
        //за производство в задания към тези договори
         $vJobsArr = array();
        while ($sDetRec = $sQuery->fetch()){
            
            $deliveryDay = $sDetRec->deliveryTime;
            //Ако е зададен срок за доставка проверяваме крайната дата дали е в периода
            if(!$sDetRec->deliveryTime){
                
                if($sDetRec->deliveryTermTime){
                    $newDeliveryDay = dt::addSecs($sDetRec->deliveryTime,$sDetRec->valior);
                    if($newDeliveryDay > $endDay)continue;
                    $deliveryDay = $newDeliveryDay;
                }else{
                    continue;
                }
            }
            
           
            $quantity = $sDetRec->quantity * $sDetRec->quantityInPack - $jobsArr[$vKey]->quantity;
            
            //Ако недопроизведено количество, не се създава виртуално задание
            if ($quantity <= 0 ) {
                continue;
            }
            
            $week = date("W", strtotime($deliveryDay)).'-'.date("Y", strtotime($deliveryDay));
          
            //Ако срока за доставка е изткъл, заданието се отнася към нулева седмица
            if ($deliveryDay < dt::today()) {
                $week = '0-0';
            }
           
            
            $vKey = $sDetRec->saleId.'|'.$sDetRec->productId;
            
            if (!array_key_exists($vKey, $vJobsArr)) {
            $vJobsArr[$vKey] = (object)array(
                                        'productId'=>$sDetRec->productId,
                                        'quantity'=>$sDetRec->saleId,
                                        'saleId'=>$sDetRec->saleId,

                                        'week'=>$week
                                       );
      
            }else{
                $obj = &$vJobsArr[$vKey];
                
                $obj->quantity += $quantity;
            }
        
        }
        
        return $vJobsArr;
    }
}
