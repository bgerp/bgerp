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
class planning_reports_Fsc extends frame2_driver_TableData
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
    protected $changeableFields =  'from,to';
    
    
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
        $jobsQuery->where("#timeClosed IS NOT NULL");
        
        //Филтриране на периода
        $jobsQuery->where(array(
            "#timeClosed >= '[#1#]' AND #timeClosed <= '[#2#]'",
            $rec->from .' 00:00:00' ,$rec->to . ' 23:59:59'));
        
        
        
        
        $threadsIdForCheck = array();
        while ($jobRec = $jobsQuery->fetch()){
            
            $singleProductWeight = cat_Products::getParams($jobRec->productId, 'weight');
            
            //////////////////////////////////////////////////////
            //////////////////////////////////////////////////////
            // ЗАЩО НЯМА ТЕГЛО НА АРТИКУЛА
            
            $singleProductWeight = $singleProductWeight ? $singleProductWeight : 1;
        
        //Масив от нишки в които може да има протоколи за производство към това задание   $threadsIdForCheck
        $threadsIdForCheck[] = $jobRec->threadId;
        
        $taskQuery = planning_Tasks::getQuery();
        
        $taskQuery->where("#originId = $jobRec->containerId");
        
            while ($taskRec = $taskQuery->fetch()){
                
                $threadsIdForCheck[] = $taskRec->threadId;
            }
          
           
            foreach ($threadsIdForCheck as $threadId){
                $totalProductWeight = $prodQuantity = null;
                
                $productionQuery = planning_DirectProductionNote::getQuery();
                $productionQuery -> where("#threadId = $threadId");
                $productionQuery->show('quantity');
                
                //Произведено количество по всички протоколи за производство от това задание($jobsQuery)/този артикул
                $prodQuantity =  array_sum(arr::extractValuesFromArray($productionQuery->fetchAll(), 'quantity'));
                
                
                //Теортично изчислено тегло на произведеното количество от този артикул по това задание за производство
                //по протоколите за производство в тази нишка
                $totalProductWeight = $singleProductWeight * $prodQuantity;
                
            
                $id = $jobRec->id;
            
            //Мярка на артикула
            $measureArtId = cat_Products::getProductInfo($jobRec->productId)->productRec->measureId;
            
            // Запис в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object) array(
                    
                    'job'=> $id,                                          // id на заданието за производство
                    'productId' => $jobRec->productId,                    //Id на артикула
                    'measure' => $measureArtId,                           //Мярка
                    'singleProductWeight' => $singleProductWeight,        //Единично тегло на артикула
                    'totalProductWeight' => $totalProductWeight,          // Теоретично тегло на произведеното количество артикули
                    'quantity' => $prodQuantity,                          //Произведено количество
                    
                );
            } else {
                $obj = &$recs[$id];
                
                $obj->quantity += $prodQuantity;
                $obj->totalProductWeight += $totalProductWeight;
                
            }
            
        }
        
    }
        return $recs;
    }
    
    
    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec - записа
     *
     * @param bool     $export - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');
        
        if ($export === false) {
            
            $fld->FLD('job', 'varchar', 'caption=Задание за производство');
            $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
            $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
            $fld->FLD('singleProductWeight', 'double(smartRound,decimals=2)', 'caption=Ед. тегло,tdClass=centered');
            $fld->FLD('quantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Количество');
            $fld->FLD('totalProductWeight', 'double(smartRound,decimals=2)', 'caption=Тегло,tdClass=centered');
            
            
        } else {
            
        }
        
        return $fld;
    }
    
    
    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec
     *                       - записаif (isset($dRec->quantity)) {
            $row->quantity = $Double->toVerbal($dRec->quantity);
        }
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
            $row->singleProductWeight = $Double->toVerbal($dRec->singleProductWeight);
        }
        
        if (isset($dRec->quantity)) {
            $row->quantity = $Double->toVerbal($dRec->quantity);
        }
        
        if (isset($dRec->totalProductWeight)) {
            $row->totalProductWeight = $Double->toVerbal($dRec->totalProductWeight);
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
