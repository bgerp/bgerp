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
        
        $fieldset->FLD('horizon', 'time(uom=days,Min=0,allowEmpty)', 'caption=Хоризонт,after=jobses');
        
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
        
        $form->setSuggestions('horizon', explode('|', '1 месец|3 месеца|6 месеца|12 месеца|15 месеца|18 месеца|2 години'));
        $form->setDefault('horizon', '1 год.');
        
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
        
        $jobsQuery = planning_Jobs::getQuery();
        $jobsQuery->where("#state != 'rejected' AND #state != 'closed' AND #state != 'draft'");
        $today = dt::today();
        $endDay = dt::addSecs($rec->horizon);
        $jobsQuery->where(array("#quantity > #quantityProduced AND #dueDate <= '[#1#]'", $endDay . ' 23:59:59'));
       
        while ($jobsRec = $jobsQuery->fetch()) {
            
            $materialsArr = array();
            
            $quantityRemaining = $jobsRec->quantity - $jobsRec->quantityProduced;
            
                
                $materialsArr = cat_Products::getMaterialsForProduction($jobsRec->productId,$quantityRemaining);
            
                if (!empty($materialsArr)){
                foreach ($materialsArr as $val){
                    
                    $matRec = cat_Products::fetch($val[productId]);
                    
                    //Филтрира само складируеми материали
                    if ($matRec->canStore == 'no')continue;
                    
                    //Ако има избрана група или групи материали
                    if ($rec->groups){
                        $groupsArr = keylist::toArray($rec->groups);
                        if (!keylist::isIn($groupsArr, $matRec->groups))
                            
                            continue;
                        
                    }
                    
                    $id = $val[productId];
                    
                    // Запис в масива
                    if (!array_key_exists($id, $recs)) {
                        $recs[$id] = (object) array(
                            
                            'jobId'=> array($jobsRec->id),
                            'jobProductId' => $jobsRec->productId,                                           //Id на артикула
                            'quantityRemaining' => $quantityRemaining,                                       // Оставащо количество
                            
                            'materialId'=> $val[productId],
                            'materialQuantiry'=> $val[quantity],
                            
                        );
                    } else {
                        $obj = &$recs[$id];
                        
                        $obj->quantityRemaining += $quantityRemaining;
                        $obj->materialQuantiry += $val[quantity];
                        array_push($obj->jobId, $jobsRec->id);
                    }
                }
            }
        }
     //   bp($recs);

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
        $fld->FLD('jobs', 'varchar', 'smartCenter,caption=@Задания');
        
        $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
        
        $fld->FLD('materialQuantiry', 'double(smartRound,decimals=2)', 'smartCenter,caption=Необходимо Количество');
       
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
        
        if (isset($dRec->materialId)) {
            $row->materialId = cat_Products::getLinkToSingle_($dRec->materialId, 'name');
 
        } 
        $marker=0;
        foreach ($dRec->jobId as $job) {
            $marker++;
            
            $jRec = planning_Jobs::fetch($job);
            
            $jContainer = $jRec->containerId;
            
            $Job = doc_Containers::getDocument($jContainer);
            
            $handle = $Job->getHandle();
            
            $singleUrl = $Job->getUrlWithAccess($Job->getInstance(), $job);
            
            $row->jobs .=  ht::createLink("#{$handle}", $singleUrl);
            
            if ((countR(($dRec->jobId )) - $marker) != 0) {
                $row->jobs .= ', ';
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
    
}
