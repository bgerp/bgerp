<?php


/**
 * Мениджър на отчети за произведени артикули
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
 * @title     Производство » Произведени артикули
 */
class planning_reports_ArticlesProduced extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, planning';
    
    
    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     *
     * @var string
     */
    protected $hashField;
    
    
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
    protected $changeableFields = 'from,duration,compare,compareStart,seeCrmGroup,seeGroup,group,dealers,contragent,crmGroup,articleType,orderBy,grouping,updateDays,updateTime';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        //Период
        $fieldset->FLD('from', 'date', 'caption=Период->От,after=title,single=none,mandatory');
        $fieldset->FLD('to', 'date', 'caption=Период->До,after=from,single=none,mandatory');
        
        //Групиране на резултата
        $fieldset->FLD('groupBy', 'enum(no=Без групиране, department=Център на дейност,storeId=Склад,month=По месеци)', 'notNull,caption=Групиране,after=to');
        
        //Подредба на резултатите
        $fieldset->FLD('orderBy', 'enum(code=Код,name=Артикул,quantity=Количество)', 'caption=Подреждане по,after=groupBy');
        
        $fieldset->FNC('montsArr', 'varchar', 'caption=Месеци по,after=orderBy,input=hiden,single=none');
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
        
        $form->setDefault('groupBy', 'no');
        $form->setDefault('orderBy', 'code');
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
        if ($rec->groupBy != 'no') {
            $this->groupByField = $rec->groupBy;
        }
        $recs = array();
        
        //Произведени артикули
        $planningQuery = planning_DirectProductionNote::getQuery();
        
        $planningQuery->EXT('groupMat', 'cat_Products', 'externalName=groups,externalKey=productId');
        $planningQuery->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');
        $planningQuery->EXT('name', 'cat_Products', 'externalName=name,externalKey=productId');
        
        
        $planningQuery->where("#state = 'active'");
        
        //Филтриране на периода
        $planningQuery->where(array(
            "#valior >= '[#1#]' AND #valior <= '[#2#]'",
            $rec->from .' 00:00:00',$rec->to . ' 23:59:59'));
        
        $montArr = array();
        while ($planningRec = $planningQuery->fetch()) {
            
            $month = substr($planningRec->valior, 0, 7);
            if (!in_array($month, $montArr)){
                
                array_push($montArr, $month);
            }
            
            $id = $planningRec->productId;
           
            //Задание за производство към което е протокола за производство
            $Document = doc_Containers:: getDocument($planningRec->originId);
            
            $className = $Document->className;
            
            //Ако протокола за проиаводство е към задача вземаме заданито от което е направена задачата
            if ($className != 'planning_Jobs') {
                $taskRec = $className::fetch($Document->that);
                
                $Document = doc_Containers:: getDocument($taskRec->originId);
                
                $className = $Document->className;
            }
            
            //Център на дейност
            $departmentId = $className::fetch($Document->that)->department;
            
            //Мярка на артикула
            $measureArtId = cat_Products::fetchField($planningRec->productId, 'measureId');
            
            //Произведено количество
            $quantity = $planningRec->quantity;
            
            //Код на артикула
            $artCode = !is_null($planningRec->code) ? $planningRec->code : "Art{$planningRec->productId}";
            
            //Склад на заприхождаване
            $storeId = $planningRec->storeId;
            
            if ($rec->groupBy == 'month') {
                unset($this->groupByField);
                $monthQuantityArr[$planningRec->productId][$month] += $quantity;
            }
            
            // Запис в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object) array(
                    
                    'code' => $artCode,                                              //Код на артикула
                    'productId' => $planningRec->productId,                          //Id на артикула
                    'measure' => $measureArtId,                                      //Мярка
                    'name' => cat_Products::getTitleById($planningRec->productId),   //Име
                    'storeId' => $storeId,                                           //Склад на заприхождаване
                    'department' => $departmentId,                                   //Център на дейност
                    'quantity' => $quantity,                                         //Текущ период - количество
                    'monthQuantity' => $monthQuantityArr[$planningRec->productId],
                    'group' => $planningRec->groupMat,                               // В кои групи е включен артикула
                    'month' => '',                                               // месец на производство
                
                
                );
            } else {
                $obj = &$recs[$id];
                
                $obj->quantity += $quantity;
                $obj->monthQuantity = $monthQuantityArr[$planningRec->productId];
            }
        }
        
        $rec->montsArr = $montArr;
        //Подредба на резултатите
        if (!is_null($recs)) {
            $typeOrder = ($rec->orderBy == 'name' || $rec->orderBy == 'code') ? 'stri' : 'native';
            
            $orderBy = $rec->orderBy;
            
            arr::sortObjects($recs, $orderBy, 'ASC', $typeOrder);
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
        
        $text =($rec->groupBy != 'month') ? 'Произведено':'Общо';
        $fld->FLD('code', 'varchar', 'caption=Код');
        $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
        $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
        $fld->FLD('quantity', 'double(smartRound,decimals=2)', "smartCenter,caption=$text");
        if ($rec->groupBy != 'month') {
            
            $fld->FLD('department', 'key(mvc=planning_Centers,select=name)', 'caption=Център на дейност');
            $fld->FLD('storeId', 'key(mvc=strore_Stores,select=name)', 'caption=Склад,tdClass=centered');
        } else {
        $monthArr = $rec->montsArr;
            sort($monthArr);
           
            foreach ($monthArr as $val) {
                $year = substr($val, 0, 4);
                $month = substr($val, -2);
                $months = array('01' => 'Jan','02' => 'Feb','03' => 'Mar','04' => 'Apr','05' => 'May','06' => 'Jun','07' => 'Jul','08' => 'Aug','09' => 'Sep','10' => 'Oct','11' => 'Nov','12' => 'Dec');
                
                $monthName = $months[($month)];
                
                $fld->FLD($val, 'double(smartRound,decimals=2)', "smartCenter,caption=${year}->${monthName}");
            }
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
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        
        $row = new stdClass();
        
        
        if (isset($dRec->code)) {
            $row->code = $dRec->code;
        }
        if (isset($dRec->productId)) {
            $row->productId = cat_Products::getLinkToSingle_($dRec->productId, 'name');
        }
        if (isset($dRec->measure)) {
            $row->measure = cat_UoM::fetchField($dRec->measure, 'shortName');
        }
        
        if (isset($dRec->storeId)) {
            $row->storeId = '';
            
            if ($rec->data->groupByField == 'storeId') {
                $row->storeId .= 'Склад: ';
            }
            
            $row->storeId .= store_Stores::getLinkToSingle_($dRec->storeId, 'name');
        }
        
        if (isset($dRec->department)) {
            $row->department = '';
            if ($rec->data->groupByField == 'department') {
                $row->department .= 'Център на дейност: ';
            }
            
            $row->department .= planning_Centers::getLinkToSingle_($dRec->department, 'name');
        } else {
            $row->department = 'Не е посочен';
        }
        
        if (isset($dRec->quantity)) {
            $row->quantity = $Double->toVerbal($dRec->quantity);
        }
        
        if ($rec->groupBy == 'month') {
            foreach ($dRec->monthQuantity as $key => $val) {
                
                    $row->$key = $Double->toVerbal($val);
              
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
        $Date = cls::get('type_Date');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        $currency = 'лв.';
        
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                <small><div><!--ET_BEGIN from-->|От|*: [#from#]<!--ET_END from--></div></small>
                                <small><div><!--ET_BEGIN to-->|До|*: [#to#]<!--ET_END to--></div></small>
                                <small><div><!--ET_BEGIN storeId-->|Склад|*: [#storeId#]<!--ET_END storeId--></div></small>
                                <small><div><!--ET_BEGIN minCost-->|Мин. наличност|*: [#minCost#] ${currency}<!--ET_END minCost--></div></small>
                                <small><div><!--ET_BEGIN reversibility-->|Мин. обращаемост|*: [#reversibility#]<!--ET_END reversibility--></div></small>
                                </fieldset><!--ET_END BLOCK-->"));
        if (isset($data->rec->from)) {
            $fieldTpl->append('<b>' .$Date->toVerbal($data->rec->from) . '</b>', 'from');
        }
        
        if (isset($data->rec->to)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->to) . '</b>', 'to');
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
