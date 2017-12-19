<?php



/**
 * Мениджър на отчети за отклонения от цените
 *
 * @category  bgerp
 * @package   sales
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Продажби » Отклонения от цените
 */
class sales_reports_PriceDeviation extends frame2_driver_TableData
{

    const NUMBER_OF_ITEMS_TO_ADD = 50;
    
    const MAX_POST_ART = 10;
    
    
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,manager,store,planing,purchase,sales';
    
    
    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;
    
    
    /**
     * Полета от таблицата за скриване, ако са празни
     *
     * @var int
     */
    protected $filterEmptyListFields;
    
    
    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     * @var varchar
     */
    protected $hashField;
    
    
    /**
     * Кое поле от $data->recs да се следи, ако има нов във новата версия
     *
     * @var varchar
     */
    protected $newFieldToCheck = 'conditionQuantity';
    
    
    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField;


    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'typeOfQuantity,additional,storeId,groupId';



    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
    	$fieldset->FLD('from', 'date(smartTime)', 'caption=От,after=title,single=none,mandatory');
    	$fieldset->FLD('to',    'date(smartTime)', 'caption=До,after=from,single=none,mandatory');
        $fieldset->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,after=typeOfQuantity');
        $fieldset->FLD('groupId', 'key(mvc=cat_Groups,select=name,allowEmpty)', 'caption=Група продукти,after=storeId,silent,single=none,removeAndRefreshForm');
        $fieldset->FLD('dealers', 'users(rolesForAll=ceo|rep_cat, rolesForTeams=ceo|manager|rep_acc|rep_cat,allowEmpty)', 'caption=Търговци,after=to');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        $rec->flag = TRUE;

    }

    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager $Embedder
     * @param core_Form $form
     * @param stdClass $data
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
     * @return array
     */
    protected function prepareRecs($rec, &$data = NULL)
    {
        $recs = array();

                $query = sales_SalesDetails::getQuery();

               

               

                while ($saleProducts = $query->fetch()) {
                	
                	$saleProductsArr[]=$saleProducts;
                
                    $id = $recProduct->productId;

                    if ($rec->typeOfQuantity == 'FALSE'){
                        $typeOfQuantity = FALSE;
                    }else{
                        $typeOfQuantity = TRUE;
                    }

       //             $quantity = store_Products::getQuantity($id, $recProduct->storeId, $typeOfQuantity);

//                         if (!array_key_exists($id, $recs)) {

//                             $recs[$id] =

//                                 (object)array(

//                                     'measure' => cat_Products::fetchField($id, 'measureId'),
//                                     'productId' => $productId,
//                                     'storeId' => $rec->storeId,
//                                     'quantity' => $quantity,
//                                     'minQuantity' => (int)$products->minQuantity[$key],
//                                     'maxQuantity' => (int)$products->maxQuantity[$key],
//                                     'conditionQuantity' => 'ok',
//                                     'conditionColor' => 'green',
//                                     'code' => $products->code[$key]

//                                 );

//                         } else {

//                         $obj = &$recs[$id];

//                         $obj->quantity += $recProduct->quantity;

//                     }

                }

            bp($saleProductsArr);
        
        
        // подготовка на показател "състояние" //
        foreach ($recs as $k => $v){

            if (($v-> quantity > (int)$v-> maxQuantity)) {

                $v-> conditionQuantity = 'свръх наличност';
                $v-> conditionColor = 'blue';

            }

            if (($v-> quantity < (int)$v-> minQuantity)) {

                $v-> conditionQuantity = 'под минимум';
                $v-> conditionColor = 'red';

            }

            if(((int)$v-> quantity >= (int)$v-> minQuantity) && ((int)$v-> quantity <= (int)$v-> maxQuantity)) {

                $v-> conditionQuantity = 'ok';
                $v-> conditionColor = 'green';

            }

            if ((!$v-> maxQuantity   && $v-> quantity > (int)$v->minQuantity)||(($v-> maxQuantity == 0 && $v-> quantity > (int)$v->minQuantity )) ) {

                $v-> conditionQuantity = 'ok';
                $v-> conditionColor = 'green';

            }

        }

        return $recs;

    }


    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec   - записа
     * @param boolean $export - таблицата за експорт ли е
     * @return core_FieldSet  - полетата
     */
    protected function getTableFieldSet($rec, $export = FALSE)
    {
        $fld = cls::get('core_FieldSet');

        if($export === FALSE){

            $fld->FLD('productId', 'varchar', 'caption=Артикул');
            //  $fld->FLD('storeId', 'varchar', 'caption=Склад,tdClass=centered');
            $fld->FLD('measure', 'varchar', 'caption=Мярка,tdClass=centered');
            $fld->FLD('quantity', 'double(smartRound,decimals=2)', 'caption=Количество,smartCenter');
            $fld->FLD('minQuantity', 'double', 'caption=Минимално,smartCenter');
            $fld->FLD('maxQuantity', 'double', 'caption=Максимално,smartCenter');
            $fld->FLD('conditionQuantity', 'text', 'caption=Състояние,tdClass=centered');
        } else {
            $fld->FLD('productId', 'varchar', 'caption=Артикул');
            //  $fld->FLD('storeId', 'varchar', 'caption=Склад,tdClass=centered');
            $fld->FLD('measure', 'varchar', 'caption=Мярка');
            $fld->FLD('quantity', 'varchar', 'caption=Количество');
            $fld->FLD('minQuantity', 'varchar', 'caption=Минимално');
            $fld->FLD('maxQuantity', 'varchar', 'caption=Максимално');
            $fld->FLD('conditionQuantity', 'varchar', 'caption=Състояние');

        }

        return $fld;

    }


    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec  - записа
     * @param stdClass $dRec - чистия запис
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {

        $Int = cls::get('type_Int');

        $row = new stdClass();

        if(isset($dRec->productId)) {
            $row->productId =  cat_Products::getShortHyperlink($dRec->productId);
        }

        if(isset($dRec->quantity)) {
            $row->quantity =  core_Type::getByName('double(decimals=2)')->toVerbal($dRec->quantity);
        }

        if(isset($dRec->storeId)) {
            $row->storeId = store_Stores::getShortHyperlink($dRec->storeId);
        }else{$row->storeId ='Общо';}

        if(isset($dRec->measure)) {
            $row->measure = cat_UoM::fetchField($dRec->measure,'shortName');
        }

        if(isset($dRec->minQuantity)) {
            $row->minQuantity = $Int->toVerbal($dRec->minQuantity);
        }

        if(isset($dRec->maxQuantity)) {
            $row->maxQuantity =$Int->toVerbal($dRec->maxQuantity);
        }

        if((isset($dRec->conditionQuantity) && ((isset($dRec->minQuantity)) || (isset($dRec->maxQuantity))))){
            $row->conditionQuantity = "<span style='color: $dRec->conditionColor'>{$dRec->conditionQuantity}</span>";
        }

        return $row;
    }


    /**
     *Изчиства повтарящи се стойности във формата
     * @param $arr
     * @return array
     */
    static function removeRpeadValues ($arr)
    {
        $tempArr = (array)$arr;

        $tempProducts = array();
        if (is_array($tempArr['code'])) {

            foreach ($tempArr['code'] as $k => $v) {

                if (in_array($v, $tempProducts)) {

                    unset($tempArr['minQuantity'][$k]);
                    unset($tempArr['maxQuantity'][$k]);
                    unset($tempArr['name'][$k]);
                    unset($tempArr['code'][$k]);
                    continue;

                }

                $tempProducts[$k] = $v;
            }
        }

        $groupNamerr = $tempArr;

        return $arr;

    }

}