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
    protected $changeableFields =  'from,duration,compare,compareStart,seeCrmGroup,seeGroup,group,dealers,contragent,crmGroup,articleType,orderBy,grouping,updateDays,updateTime';
    
    
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
        $planningQuery = planning_DirectProductionNote::getQuery();
        
        $planningQuery->EXT('groupMat', 'cat_Products', 'externalName=groups,externalKey=productId');
        $planningQuery->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');
        $planningQuery->EXT('name', 'cat_Products', 'externalName=name,externalKey=productId');
        
        
        $planningQuery->where("#state != 'rejected'");
        
        //Филтриране на периода
        $planningQuery->where(array(
            "#valior >= '[#1#]' AND #valior <= '[#2#]'",
            $rec->from .' 00:00:00' ,$rec->to . ' 23:59:59'));
        
        
        
        
       
        while ($planningRec = $planningQuery->fetch()){
        
        $id = $planningRec->productId;
        
        //Мярка на артикула
        $measureArtId = cat_Products::getProductInfo($planningRec->productId)->productRec->measureId;
        
        //Произведено количество
        $quantity = $planningRec->quantity;
        
        //Код на артикула
        $artCode =!is_null($planningRec->code) ? $planningRec->code : "Art{$planningRec->productId}";
        
        //Склад на заприхождаване
        $storeId = $planningRec->storeId;
        
        
        
            // Запис в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object) array(
                    
                    'code' => $artCode,                                   //Код на артикула
                    'productId' => $planningRec->productId,               //Id на артикула
                    'measure' => $measureArtId,                           //Мярка
                    'name' => $nameArt,                                   //Име
                    'storeId' => $storeId,                                //Склад на заприхождаване
                    
                    'quantity' => $quantity,                              //Текущ период - количество
                    
                    'group' => $planningRec->groupMat,                    // В кои групи е включен артикула
                 
                    
                );
            } else {
                $obj = &$recs[$id];
                
                $obj->quantity += $quantity;
                
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
            
            $fld->FLD('code', 'varchar', 'caption=Код');
            $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
            $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
            $fld->FLD('storeId', 'key(mvc=strore_Stores,select=name)', 'caption=Склад,tdClass=centered');
            $fld->FLD('quantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Произведено');
          
            
            
        } else {
           
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
            $row->storeId = store_Stores::getLinkToSingle_($dRec->storeId, 'name');
        }
        
        if (isset($dRec->quantity)) {
            $row->quantity = $Double->toVerbal($dRec->quantity);
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
