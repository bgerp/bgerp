<?php


/**
 * Мениджър на отчети за активирани документи по време
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Документи » Активирани документи по време
 */
class doc_reports_ActivatedDocumentsByTime extends frame2_driver_TableData
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
    protected $sortableListFields = 'counter';
    
    
    /**
     * Кои полета от таблицата в справката да се сумират в обобщаващия ред
     *
     * @var int
     */
    protected $summaryListFields = 'counter';
    
    
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
    protected $changeableFields;
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('from', 'date', 'caption=От,mandatory,single=none,after=title');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,single=none,mandatory');
        
        //Задания
        $fieldset->FLD('documents', 'classes(interface = doc_DocumentIntf,select = title)', 'caption=Вид документи,placeholder=Избери вид документи,after=to,single=none,mandatory');
        
        $fieldset->FLD('grouping', 'enum(day=24 часа, week=7 дни, , year=12 месеца)', 'caption=Групиране,after=documents,removeAndRefreshForm');
       
       // $fieldset->FLD('date', 'date', "caption=От,after=grouping,single=none,mandatory");

        $fieldset->FLD('users', 'userList(rolesForAll=ceo|repAllGlobal, rolesForTeams=ceo|manager|repAll|repAllGlobal)', 'caption=Потребители,single=none,mandatory,after=grouping');
     
        $fieldset->FNC('dateEnd', 'date', "caption=До,after=users,single=none");
        
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
        
       
        
        $form->fields['users']->type->userOtherGroup = array(-1 => (object) array('suggName' => 'users', 'title' => 'Система', 'attr' => array('class' => 'team'), 'group' => true, 'autoOpen' => true, 'suggArr' => array(core_Users::ANONYMOUS_USER, core_Users::SYSTEM_USER)));
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
        
        $recs = $documentsForChech = array();
        
        $documentsForChech = keylist::toArray($rec->documents);
        
        $query = doc_Containers::getQuery();
        
        $query->in('docClass', $documentsForChech);
        
        $query->in('state', array('active','closed'));
         
        while ($document = $query->fetch()){
            
            $className = core_Classes::getName($document->docClass);
            
            $docRec = $className::fetch($document->docId);
            
            $dateCheck = $docRec->activatedOn ? $docRec->activatedOn : $docRec->createdOn;
            
         //   $dateCheck = date('Y-m-d', dt::mysql2timestamp($dateCheck));
            
            // Разбиваме подадената дата
            $day = dt::mysql2Verbal($dateCheck, 'd');
            $month = dt::mysql2Verbal($dateCheck, 'm');
            $year = dt::mysql2Verbal($dateCheck, 'Y');
            
            $rec->dateEnd = $dateCheck;
            
            //Определяне ключа на масива в зависимост от избраното групиране
            if ($rec->grouping == 'day'){
                
                if ($dateCheck > $rec->from && $dateCheck < $rec->to){
                    
                $id = date('H',dt::mysql2timestamp($dateCheck));
                
                }else{
                    continue;
                }
            }
            
            if ($rec->grouping == 'week'){
                
                if ($dateCheck > $rec->from && $dateCheck < $rec->to){
                    $dayKeys = array(1 => 'понеделник', 2 => 'вторник', 3 => 'сряда', 4 => 'четвъртък', 5 => 'петък', 6 => 'събота', 7 => 'неделя');
                    
                    // Взимаме кой ден от седмицата е 1=пон ... 7=нед
                    $id = date('N', mktime(0, 0, 0, $month, $day, $year));
                    
                }else{
                    continue;
                }
            }
            
            if ($rec->grouping == 'year'){
                
                if ($dateCheck > $rec->from && $dateCheck < $rec->to){
                   
                    $id = date('m', mktime(0, 0, 0, $month, $day, $year));
              
                }else{
                    continue;
                }
                
            }
             
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object) array(
                    
                    'counter' => 1,
                    'time' => $id,
                    
                   
                );
            } else {
                $obj = &$recs[$id];
                $obj->counter += 1;
            }
        }
        
        if (! is_null($recs)) {
            arr::sortObjects($recs, 'time');
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
        
        switch ($rec->grouping) {
            
            case 'day':$text = 'Час'; break;
            
            case 'week':$text = 'Ден'; break;
            
            case 'year':$text = 'Месец'; break;
            
        }
        
        $fld->FLD('time', 'varchar', "caption=$text,tdClass=centered");
        $fld->FLD('counter', 'varchar', 'caption=Брой,tdClass=centered');
        
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
        
        $dayKeys = array(1 => 'понеделник', 2 => 'вторник', 3 => 'сряда', 4 => 'четвъртък', 5 => 'петък', 6 => 'събота', 7 => 'неделя');
        
        switch ($rec->grouping) {
            
            case 'day':$time = $dRec->time; break;
            
            case 'week':$time = $dayKeys[$dRec->time]; break;
            
            case 'year':$time = dt::getMonth($dRec->time,'F'); break;
            
        }
         
        $row->time = $time;
        $row->counter = $dRec->counter;
        
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
                                <small><div><!--ET_BEGIN dateEnd-->|До|*: [#dateEnd#]<!--ET_END dateEnd--></div></small>
                                <small><div><!--ET_BEGIN documents-->|Документи|*: [#documents#]<!--ET_END documents--></div></small>
                                <small><div><!--ET_BEGIN users-->|Потребители|*: [#users#]<!--ET_END users--></div></small>
                                </fieldset><!--ET_END BLOCK-->"));
        if (isset($data->rec->from)) {
            $fieldTpl->append('<b>' . $data->rec->from . '</b>', 'from');
        }
        
        if (isset($data->rec->to)) {
            $fieldTpl->append('<b>' . $data->rec->to . '</b>', 'to');
        }
        
        if ((isset($data->rec->users)) && ((min(array_keys(keylist::toArray($data->rec->users))) >= 1))) {
            foreach (type_Keylist::toArray($data->rec->users) as $user) {
                $usersVerb .= (core_Users::getTitleById($user) . ', ');
            }
            
            $fieldTpl->append('<b>' . trim($usersVerb, ',  ') . '</b>', 'users');
        } else {
            $fieldTpl->append('<b>' . 'Всички' . '</b>', 'users');
        }
        
        $marker = 0;
        if (isset($data->rec->documents)) {
            foreach (type_Keylist::toArray($data->rec->documents) as $document) {
                $marker++;
                
                
                $documentVerb .= (core_Classes::getTitleById($document));
                
                if ((countR((type_Keylist::toArray($data->rec->documents))) - $marker) != 0) {
                    $documentVerb .= ', ';
                }
            }
            
            $fieldTpl->append('<b>' . $documentVerb . '</b>', 'documents');
        } else {
            $fieldTpl->append('<b>' . 'Всички' . '</b>', 'documents');
        }
        
//         $marker = 0;
//         if (isset($data->rec->jobses)) {
//             foreach (type_Keylist::toArray($data->rec->jobses) as $job) {
//                 $marker++;
                
//                 $jRec = planning_Jobs::fetch($job);
                
//                 $jContainer = $jRec->containerId;
                
//                 $Job = doc_Containers::getDocument($jContainer);
                
//                 $handle = $Job->getHandle();
                
//                 $singleUrl = $Job->getUrlWithAccess($Job->getInstance(), $job);
                
//                 $jobVerb .= ht::createLink("#{$handle}", $singleUrl);
                
//                 if ((countR((type_Keylist::toArray($data->rec->jobses))) - $marker) != 0) {
//                     $jobVerb .= ', ';
//                 }
//             }
            
//             $fieldTpl->append('<b>' . $jobVerb . '</b>', 'jobses');
//         } else {
//             $fieldTpl->append('<b>' . 'Всички' . '</b>', 'jobses');
//         }
        
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
