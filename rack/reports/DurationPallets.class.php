<?php


/**
 * Мениджър на отчети за престой на палети
 *
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Склад » Престой на палети
 */
class rack_reports_DurationPallets extends frame2_driver_TableData
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
    protected $changeableFields ;
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        
        //Период
        $fieldset->FLD('from', 'date', 'caption=От,after=title,single=none');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,single=none');
        
        $fieldset->FLD('storeId', 'keylist(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,placeholder=Всички,after=to,single=none');
        
       
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
        
        $q = rack_OccupancyOfRacks::getQuery();
        $storeIdArr = arr::extractValuesFromArray($q->fetchAll(), 'storeId');
        
        foreach ($storeIdArr as $v){
            $storeSuggestions[$v] = store_Stores::getTitleById($v);
        }
        
        $form->setSuggestions('storeId', $storeSuggestions);
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
        
        $pQuery = rack_OccupancyOfRacks::getQuery();
        
        while ($pRec = $pQuery->fetch()){//bp($pRec);
            
            $key = $pRec->productId;
            
            $quantity = $pRec->quantity;
            $measureArt = cat_Products::fetch($pRec->productId)->measureId;
            
            // Запис в масива
            if (!array_key_exists($key, $recs)) {
                $recs[$key] = (object) array(
                    
                    'productId' => $pRec->productId,                    //Id на артикула
                    'measure' => $measureArt,                           //Мярка
                    
                    'quantity' => $quantity,                            //Количество
                    'palletDays' => 1,                                  //Палетодни за този артикул
                    
                    
                );
            } else {
                $obj = &$recs[$key];
                
                $obj->quantity += $quantity;
                $obj->palletDays += 1;
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
        
        $fld->FLD('product', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
        $fld->FLD('palletDays', 'int', 'caption=Палетодни,tdClass=centered');
        
    
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
        
        $row->product = cat_Products::getHyperlink($dRec->productId);
        
        $row->palletDays = $dRec->palletDays;
            
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
        
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                <small><div><!--ET_BEGIN from-->|От|*: [#from#]<!--ET_END from--></div></small>
                                <small><div><!--ET_BEGIN to-->|До|*: [#to#]<!--ET_END to--></div></small>
                                <small><div><!--ET_BEGIN storeId-->|Склад|*: [#storeId#]<!--ET_END storeId--></div></small>
                                </fieldset><!--ET_END BLOCK-->"));
        
        if (isset($data->rec->from)) {
            $fieldTpl->append('<b>' .$Date->toVerbal($data->rec->from) . '</b>', 'from');
        }
        
        if (isset($data->rec->to)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->to) . '</b>', 'to');
        }
        
        
        $marker = 0;
        if (isset($data->rec->storeId)) {
            foreach (type_Keylist::toArray($data->rec->storeId) as $storeId) {
                $marker++;
                
                $storeVerb .= store_Stores::getTitleById($storeId);
                
                if ((countR(type_Keylist::toArray($data->rec->storeId))) - $marker != 0) {
                    $storeVerb .= ', ';
                }
            }
            
            $fieldTpl->append('<b>' . $storeVerb . '</b>', 'storeId');
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
