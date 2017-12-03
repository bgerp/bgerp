<?php
/**
 * Мениджър на отчети за налични количества
 *
 * @category  bgerp
 * @package   store
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Склад » Артикули налични количества
 */
class store_reports_ProductAvailableQuantity extends frame2_driver_TableData
{

    const NUMBER_OF_ITEMS_TO_ADD = 50;
    
    
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,manager,store,planing,purchase';
    
    
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
        $fieldset->FLD('typeOfQuantity', 'enum(FALSE=Налично,TRUE=Разполагаемо)','caption=Количество за показване,maxRadio=2,columns=2,after=title');
        $fieldset->FLD('additional', 'table(columns=code|name|minQuantity|maxQuantity,captions=Код на атикула|Наименование|Мин к-во|Макс к-во,widths=8em|20em|5em|5em)', "caption=Артикули||Additional,autohide,advanced,after=storeId,single=none");
        $fieldset->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,after=typeOfQuantity');
        $fieldset->FLD('groupId', 'key(mvc=cat_Groups,select=name,allowEmpty)', 'caption=Група продукти,after=storeId,silent,single=none,removeAndRefreshForm');
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

        $form->setDefault('typeOfQuantity', 'TRUE');
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

        $details = (json_decode($form->rec->additional));

        if ($form->isSubmitted()) {
            
            $details = (json_decode($form->rec->additional));

            if(is_array($details->code)) {

                foreach ($details->code as $v) {

                    $v = trim($v);

                    if (!$v) {
                        $form->setError('additional', 'Не попълнен код на артикул');

                    } else {

                        if (!cat_Products::getByCode($v)) {

                            $form->setError('additional', 'Не съществуващ артикул с код: ' . $v);
                        }

                    }

                }


                if (is_array($details->minQuantity)) {

                    foreach ($details->minQuantity as $v) {

                        $v = (int)trim($v);

                        if ($v < 0) {

                            $form->setError('additional', 'Количествата трябва  да са положителни');

                        }

                    }
                }

                if (is_array($details->maxQuantity)) {

                    foreach ($details->maxQuantity as $v) {

                        $v = (int)trim($v);

                        if ($v < 0) {

                            $form->setError('additional', 'Количествата трябва  да са положителни');
                        }
                    }

                }

                foreach ($details->code as $key => $v) {

                    if ($details->minQuantity[$key] && $details->maxQuantity[$key]) {

                        if ($details->minQuantity[$key] > $details->maxQuantity[$key]) {

                            $form->setError('additional', 'Максималното количество не може да бъде по-малко от минималното');

                        }

                    }

                }

                $grDetails = (array)$details;

                foreach ($grDetails['name'] as $k => $detail) {

                        if (!$detail && $grDetails['code'][$k]) {

                            $prId = cat_Products::getByCode($grDetails['code'][$k]);

                            if ($prId->productId) {

                                $prName = cat_Products::getTitleById($prId->productId, $escaped = TRUE);

                                $grDetails['name'][$k] = $prName;
                            }


                        }

                }

                $jDetails = json_encode(self::removeRpeadValues($grDetails));

                $form->rec->additional = $jDetails;

            }

        }else{

            $rec = $form->rec;

            // Добавя цяла група артикули
            if ($form->cmd == 'refresh' && $rec->groupId) {

                $rQuery = cat_Products::getQuery();

                $details = (array)$details;

                $rQuery->where("#groups Like'%|{$rec->groupId}|%'");

                while ($grProduct = $rQuery->fetch()) {

                    $grDetails['code'][] = $grProduct->code;

                    $grDetails['name'][] = cat_Products::getTitleById($grProduct->id);

                    $grDetails['minQuantity'][] = $grProduct->minQuantity;

                    $grDetails['maxQuantity'][] = $grProduct->maxQuantity;

                }

                //Премахва артикули ако вече са добавени
                if (is_array($grDetails['code'])) {
                    foreach ($grDetails['code'] as $k => $v) {

                        if ($details['code'] && in_array($v, $details['code'])) {

                            unset($grDetails['code'][$k]);
                            unset($grDetails['name'][$k]);
                            unset($grDetails['minQuantity'][$k]);
                            unset($grDetails['maxQuantity'][$k]);

                        }
                    }

                }


                //Ограничава броя на артикулите за добавяне
                $count = 0;$countUnset = 0;
                if (is_array($grDetails['code'])) {
                    foreach ($grDetails['code'] as $k => $v){

                        $count++;

                        if ($count > self::NUMBER_OF_ITEMS_TO_ADD) {

                            unset($grDetails['code'][$k]);
                            unset($grDetails['name'][$k]);
                            unset($grDetails['minQuantity'][$k]);
                            unset($grDetails['maxQuantity'][$k]);
                            $countUnset++;
                            continue;

                        }

                       $details['code'][] = $grDetails['code'][$k];
                       $details['name'][] = $grDetails['name'][$k];
                       $details['minQuantity'][] = $grDetails['minQuantity'][$k];
                       $details['maxQuantity'][] = $grDetails['maxQuantity'][$k];

                    }

                    if ($countUnset > 0){
                        $groupName = cat_Products::getTitleById($rec->groupId);
                        $maxArt = self::NUMBER_OF_ITEMS_TO_ADD;

                        $form->setWarning('groupId',"$countUnset артикула от група $groupName няма да  бъдат добавени.
                                                     Максимален брой артикули за еднократно добавяне - $maxArt.  
                                                     Може да добавите още артикули от групата при следваща редакция.");
                    }

                }

                $jDetails = json_encode($details);

               $form->rec->additional = $jDetails;
            }
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

        $tempProducts = array();

        $products = (json_decode($rec->additional, false));

        if (is_array($products->code)) {

            foreach ($products->code as $k => $v) {

                if (in_array($v, $tempProducts)) continue;

                $tempProducts[$k] = $v;

            }

            $products->code = $tempProducts;


            foreach ($products->code as $key => $code) {


                if (!isset($products->code[$key])) {

                    $code= 0;
                }

                $productId = cat_Products::getByCode($code)->productId;

                $keis['keis'][] = array('key'=>$key,"$productId"=>cat_Products::getTitleById($productId));

                $query = store_Products::getQuery();

                $query->where("#productId = $productId");

                if (isset($rec->storeId)) {

                    $query->where("#storeId = $rec->storeId");
                }

                while ($recProduct = $query->fetch()) {

                    $id = $recProduct->productId;


                    if ($rec->typeOfQuantity == 'FALSE'){
                        $typeOfQuantity = FALSE;
                    }else{
                        $typeOfQuantity = TRUE;
                    }

                    $quantity = store_Products::getQuantity($id, $recProduct->storeId, $typeOfQuantity);

                        if (!array_key_exists($id, $recs)) {

                            $recs[$id] =

                                (object)array(

                                    'measure' => cat_Products::fetchField($id, 'measureId'),
                                    'productId' => $productId,
                                    'storeId' => $rec->storeId,
                                    'quantity' => $quantity,
                                    'minQuantity' => (int)$products->minQuantity[$key],
                                    'maxQuantity' => (int)$products->maxQuantity[$key],
                                    'conditionQuantity' => 'ok',
                                    'conditionColor' => 'green',
                                    'code' => $products->code[$key]

                                );

                        } else {

                        $obj = &$recs[$id];

                        $obj->quantity += $recProduct->quantity;

                    }

                }//цикъл за добавяне

            }
        }
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