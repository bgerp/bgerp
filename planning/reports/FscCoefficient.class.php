<?php


/**
 * Мениджър на отчети за коефициент по FSC стандарт
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
 * @title     Производство » Коефициент по FSC стандарт
 */
class planning_reports_FscCoefficient extends frame2_driver_TableData
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
     * Кое поле от $data->recs да се следи, ако има нов във новата версия
     *
     * @var string
     */
    protected $newFieldToCheck;
    
    
    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField;
    
    
    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'from,to';
    
    
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
        
        $fieldset->FNC('sumQuantyti', 'double(smartRound,decimals=2)', 'caption=Общо тегло производство,after=to,single=none,input=hiden');
        $fieldset->FNC('sumConsumWeight', 'double(smartRound,decimals=2)', 'caption=Общо тегло вложено,after=sumQuantyti,single=none,input=hiden');
        $fieldset->FNC('koefOfTransform', 'double(smartRound,decimals=2)', 'caption=Коефициент на трансформация,after=sumConsumWeight,single=none,input=hiden');
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
        
        $jobsQuery->EXT('groupMat', 'cat_Products', 'externalName=groups,externalKey=productId');
        
        $jobsQuery->where("#state = 'closed'");
        $jobsQuery->where('#timeClosed IS NOT NULL');
        
        //Филтриране на периода
        $jobsQuery->where(array(
            "#timeClosed >= '[#1#]' AND #timeClosed <= '[#2#]'",
            $rec->from .' 00:00:00',$rec->to . ' 23:59:59'));
        
        
        while ($jobRec = $jobsQuery->fetch()) {
            $consumWeight = 0;
            $threadsIdForCheck = array();
            
            $singleProductWeight = cat_Products::getParams($jobRec->productId, 'weight');
            
            //////////////////////////////////////////////////////
            //////////////////////////////////////////////////////
            // ЗАЩО НЯМА ТЕГЛО НА АРТИКУЛА
            
            $singleProductWeight = $singleProductWeight ? $singleProductWeight : 'n.a.';
            
            //Масив от нишки в които може да има протоколи за производство към това задание   $threadsIdForCheck
            $threadsIdForCheck[] = $jobRec->threadId;
            
            $taskQuery = planning_Tasks::getQuery();
            
            $taskQuery->where("#originId = {$jobRec->containerId}");
            
            while ($taskRec = $taskQuery->fetch()) {
                array_push($threadsIdForCheck, $taskRec->threadId);
            }
            
            foreach ($threadsIdForCheck as $threadId) {
                $totalProductWeight = $prodQuantity = null;
                $productinNotes = array();
                $productionQuery = planning_DirectProductionNote::getQuery();
                $productionQuery -> where("#threadId = ${threadId}");
                $productionQuery->show('quantity');
                
                //Произведено количество по всички протоколи за производство от това задание($jobsQuery)/този артикул
                $prodQuantity = array_sum(arr::extractValuesFromArray($productionQuery->fetchAll(), 'quantity'));
                
                $productinNotes = arr::extractValuesFromArray($productionQuery->fetchAll(), 'id');
                
                //Теортично изчислено тегло на произведеното количество от този артикул по това задание за производство
                //по протоколите за производство в тази нишка
                $totalProductWeight = $singleProductWeight * $prodQuantity;
                
                
                //Мярка на артикула
                $measureArtId = cat_Products::getProductInfo($jobRec->productId)->productRec->measureId;
                
                
                //ВЛОЖЕНИ МАТЕРИАЛИ ОТ ГРУПА "Хартия FSC MIX Credit" и "Хартия, FSC Recycled 100%"
                $grousFsc = array('Хартия FSC MIX Credit','Хартия, FSC Recycled 100%');
                
                //Общо тегло на вложените суровини от тези две групи
                
                
                foreach ($grousFsc as $groupFsc) {
                    
                    // Масив с id-та на групите материали за проверка
                    $val = cat_Groups::getQuery()->fetch("#name = '{$groupFsc}'")->id;
                    $grousFscIdsArr[$val] = $val;
                }
                
                $grousFscIdsKeylist = keylist::fromArray($grousFscIdsArr);
                
                
                //Вложени количества по ПРОТОКОЛИ ЗА ВЛАГАНЕ
                $consumQuery = planning_ConsumptionNotes::getQuery();
                $consumQuery->where("#threadId = ${threadId}");
                
                while ($consumRec = $consumQuery->fetch()) {
                    $consumDetailQuery = planning_ConsumptionNoteDetails::getQuery();
                    $consumDetailQuery->where("#noteId = {$consumRec->id}");
                    $consumDetailQuery->EXT('groupMat', 'cat_Products', 'externalName=groups,externalKey=productId');
                    
                    $consumDetailQuery->likeKeylist('groupMat', $grousFscIdsKeylist);
                    
                    $consumWeight = array_sum(arr::extractValuesFromArray($consumDetailQuery->fetchAll(), 'quantity'));
                }
                
                
                //Вложени количества по ПРОТОКОЛИ ЗА ПРОИЗВОДСТВО
                $directProdQuery = planning_DirectProductionNote::getQuery();
                $directProdQuery->where("#threadId = ${threadId}");
                
                while ($consumRec = $directProdQuery->fetch()) {
                    $productinNotesDetailQuery = planning_DirectProductNoteDetails::getQuery();
                    $arr[] = $productinNotes;
                    $productinNotesDetailQuery->in('noteId', $productinNotes);
                    
                    $productinNotesDetailQuery->EXT('groupMat', 'cat_Products', 'externalName=groups,externalKey=productId');
                    
                    $productinNotesDetailQuery->likeKeylist('groupMat', $grousFscIdsKeylist);
                    
                    $consumWeight += array_sum(arr::extractValuesFromArray($productinNotesDetailQuery->fetchAll(), 'quantity'));
                }
                
                
                //Върнати количества по ПРОТОКОЛИ ЗА ВРЪЩАНЕ
                $retQuery = planning_ReturnNotes::getQuery();
                $retQuery->where("#threadId = ${threadId}");
                
                while ($retRec = $retQuery->fetch()) {
                    $retDetailQuery = planning_ReturnNoteDetails::getQuery();
                    $retDetailQuery->where("#noteId = {$retRec->id}");
                    $retDetailQuery->EXT('groupMat', 'cat_Products', 'externalName=groups,externalKey=productId');
                    
                    $retDetailQuery->likeKeylist('groupMat', $grousFscIdsKeylist);
                    
                    $consumWeight -= array_sum(arr::extractValuesFromArray($retDetailQuery->fetchAll(), 'quantity'));
                }
                
                $id = $jobRec->id;
                
                // Запис в масива
                if (!array_key_exists($id, $recs)) {
                    $recs[$id] = (object) array(
                        
                        'job' => $id,                                         // id на заданието за производство
                        'productId' => $jobRec->productId,                    //Id на артикула
                        'measure' => $measureArtId,                           //Мярка
                        'singleProductWeight' => $singleProductWeight,        //Единично тегло на артикула
                        'totalProductWeight' => $totalProductWeight,          // Теоретично тегло на произведеното количество артикули
                        'quantity' => $prodQuantity,                          //Произведено количество
                        'consumWeight' => $consumWeight,                      //Вложено количество от тези групи количество
                        'koef' => '',                                         //Коефициент на трансформация
                        'sumQuantyti' => '',                                  //Общо тегло на продукцията за периода
                        'sumConsumWeight' => '',                              //Общо тегло на вложеното за периода
                    
                    );
                } else {
                    $obj = &$recs[$id];
                    
                    $obj->quantity += $prodQuantity;
                    $obj->totalProductWeight += $totalProductWeight;
                    $obj->consumWeight += $consumWeight;
                }
            }
        }
        $sumQuantyti = $sumConsumWeight = 0;
        foreach ($recs as $key => $val) {
            $sumQuantyti += $val->totalProductWeight;
            $sumConsumWeight += $val->consumWeight;
            $val->koef = $val->consumWeight ?($val->totalProductWeight / 1000) / $val->consumWeight : 'n.a.';
        }
        
        $rec->sumQuantyti = $sumQuantyti / 1000;
        $rec->sumConsumWeight = $sumConsumWeight;
        
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
        
        if ($export === false) {
            $fld->FLD('job', 'varchar', 'caption=Задание за производство');
            
            $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул->Име');
            $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Артикул->Мярка,tdClass=centered');
            $fld->FLD('quantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Артикул->Количество');
            $fld->FLD('singleProductWeight', 'double(smartRound,decimals=2)', 'caption=Артикул->Ед. тегло,tdClass=centered');
            
            $fld->FLD('totalProductWeight', 'double(smartRound,decimals=2)', 'caption=Артикул->Общо тегло,tdClass=centered');
            
            $fld->FLD('consumWeight', 'double(smartRound,decimals=2)', 'caption=Вложено,tdClass=centered');
            
            $fld->FLD('koef', 'double(smartRound,decimals=2)', 'caption=Коефициент,tdClass=centered');
        }
        
        return $fld;
    }
    
    
    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec
     *                       - записа if (isset($dRec->quantity)) {
     *                       $row->quantity = $Double->toVerbal($dRec->quantity);
     *                       }
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
        
        
        if (isset($dRec->job)) {
            $row->job = planning_Jobs::getHyperlink($dRec->job);
        }
        if (isset($dRec->productId)) {
            $row->productId = cat_Products::getLinkToSingle_($dRec->productId, 'name');
        }
        if (isset($dRec->measure)) {
            $row->measure = cat_UoM::fetchField($dRec->measure, 'shortName');
        }
        
        if (isset($dRec->singleProductWeight)) {
            if (is_numeric($dRec->singleProductWeight)) {
                $row->singleProductWeight = $Double->toVerbal($dRec->singleProductWeight);
            } else {
                $row->singleProductWeight = core_Type::getByName('varchar')->toVerbal($dRec->singleProductWeight);
            }
        }
        if (isset($dRec->quantity)) {
            $row->quantity = $Double->toVerbal($dRec->quantity);
        }
        
        if (isset($dRec->totalProductWeight)) {
            $row->totalProductWeight = $Double->toVerbal($dRec->totalProductWeight);
        }
        
        if (isset($dRec->consumWeight)) {
            $row->consumWeight = $Double->toVerbal($dRec->consumWeight);
        }
        
        if (isset($dRec->koef)) {
            $row->koef = $Double->toVerbal($dRec->koef);
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
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
        		                <small><div><!--ET_BEGIN sumQuantyti-->|Общо тегло на произведените артикули|*: [#sumQuantyti#]<!--ET_END sumQuantyti--></div></small>
                                <small><div><!--ET_BEGIN sumConsumWeight-->|Общо тегло на вложените артикули|*: [#sumConsumWeight#]<!--ET_END sumConsumWeight--></div></small>
                                <small><div><!--ET_BEGIN koefOfTransform-->|Коефициент на трансформация|*: [#koefOfTransform#]<!--ET_END koefOfTransform--></div></small>
                                </fieldset><!--ET_END BLOCK-->"));
        
        if (isset($data->rec->sumQuantyti)) {
            $fieldTpl->append('<b>'. core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->sumQuantyti) .'</b>', 'sumQuantyti');
        }
        
        if (isset($data->rec->sumConsumWeight)) {
            $fieldTpl->append('<b>'. core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->sumConsumWeight) .'</b>', 'sumConsumWeight');
        }
        
        
        $fieldTpl->append('<b>'. core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->sumQuantyti / $data->rec->sumConsumWeight) .'</b>', 'koefOfTransform');
        
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
