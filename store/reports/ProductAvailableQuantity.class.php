<?php


/**
 * Мениджър на отчети за налични количества
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Склад » Артикули наличности и лимити
 */
class store_reports_ProductAvailableQuantity extends frame2_driver_TableData
{
    const NUMBER_OF_ITEMS_TO_ADD = 50;
    
    const MAX_POST_ART = 10;
    
    
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
     * Кое поле от $data->recs да се следи, ако има нов във новата версия
     *
     * @var string
     */
    protected $newFieldToCheck = 'conditionQuantity';
    
    
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
        $fieldset->FLD('limmits', 'enum(no=Без лимити,yes=С лимити)', 'caption=Вид на справката,removeAndRefreshForm,after=title,silent');
        
        $fieldset->FLD('typeOfQuantity', 'enum(FALSE=Налично,TRUE=Разполагаемо)', 'caption=Количество за показване,maxRadio=2,columns=2,after=limmits');
        
        $fieldset->FLD('additional', 'table(columns=code|name|minQuantity|maxQuantity,captions=Код на артикула|Наименование|Мин к-во|Макс к-во,widths=8em|20em|5em|5em)', 'caption=Артикули||Additional,autohide,advanced,after=storeId,single=none');
        
        $fieldset->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,after=typeOfQuantity');
        $fieldset->FLD('groupId', 'key(mvc=cat_Groups,select=name,allowEmpty)', 'caption=Група продукти,after=storeId,silent,single=none,removeAndRefreshForm');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     *                                      $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        $rec->flag = true;
        
        $form->setDefault('typeOfQuantity', 'TRUE');
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
        if ($form->rec->limmits == 'yes') {
            if(is_string($form->rec->additional)) {
                $details = json_decode($form->rec->additional);
            } else {
                $details = $form->rec->additional;
            }
        } else {
            $form->setField('additional', 'input=none');
        }
        
        if ($form->isSubmitted()) {
            if ($form->rec->limmits == 'no') {
                $form->rec->additional = array();
            }
            
            if ($form->rec->limmits == 'yes') {
                
                if (is_array($details->code)) {
                    $maxPost = ini_get('max_input_vars') - self::MAX_POST_ART;
                    
                    $arts = count($details->code);
                    
                    if ($arts > $maxPost) {
                        $form->setError('droupId', 'Лимита за следени продукти е достигнат.
            				За да добавите нов артикул трябва да премахнете поне един от вече включените. ');
                    }
                    
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
                            $v = (int) trim($v);
                            
                            if ($v < 0) {
                                $form->setError('additional', 'Количествата трябва  да са положителни');
                            }
                        }
                    }
                    
                    if (is_array($details->maxQuantity)) {
                        foreach ($details->maxQuantity as $v) {
                            $v = (int) trim($v);
                            
                            if ($v < 0) {
                                $form->setError('additional', 'Количествата трябва  да са положителни');
                            }
                        }
                    }
                    
                    foreach ($details->code as $key => $v) {
                        if ($details->minQuantity[$key] && $details->maxQuantity[$key]) {
                            if ((double) $details->minQuantity[$key] > (double) $details->maxQuantity[$key]) {
                                $form->setError('additional', 'Максималното количество не може да бъде по-малко от минималното');
                            }
                        }
                    }
                    
                    $grDetails = (array) $details;
                    
                    foreach ($grDetails['name'] as $k => $detail) {
                        if (!$detail && $grDetails['code'][$k]) {
                            $prId = cat_Products::getByCode($grDetails['code'][$k]);
                            
                            if ($prId->productId) {
                                $prName = cat_Products::getTitleById($prId->productId, $escaped = true);
                                
                                $grDetails['name'][$k] = $prName;
                            }
                        }
                    }
                    
                    $jDetails = json_encode(self::removeRpeadValues($grDetails));
                    
                    $form->rec->additional = $jDetails;
                }
            }
        } else {
            $rec = $form->rec;
            
            if ($form->rec->limmits == 'no') {
                $form->rec->additional = array();
            }
            
            if ($form->rec->limmits == 'yes') {
                if ($form->cmd == 'refresh' && $rec->groupId) {
                    $maxPost = ini_get('max_input_vars') - self::MAX_POST_ART;
                    
                    $arts = count($details->code);
                    
                    $grInArts = cat_Groups::fetch($rec->groupId)->productCnt;
                    
                    $groupName = cat_Products::getTitleById($rec->groupId);
                    
                    $prodForCut = ($arts + $grInArts) - $maxPost;
                    
                    if (($arts + $grInArts) > $maxPost) {
                        $form->setError('droupId', "Лимита за следени продукти е достигнат.
            				За да добавите група \" ${groupName}\" трябва да премахнете ${prodForCut} артикула ");
                    } else {
                        
                        // Добавя цяла група артикули
                        
                        $rQuery = cat_Products::getQuery();
                        
                        $details = (array) $details;
                        
                        $rQuery->where("#groups Like'%|{$rec->groupId}|%'");
                        
                        while ($grProduct = $rQuery->fetch()) {
                       
                            $grDetails['code'][] = $grProduct->code;
                            
                            $grDetails['name'][] = cat_Products::getTitleById($grProduct->id);
                            
                            $grDetails['minQuantity'][] = $grProduct->minQuantity;
                            
                            $grDetails['maxQuantity'][] = $grProduct->maxQuantity;
                        }
                        
                        // Премахва артикули ако вече са добавени
                        
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
                        
                        // Премахване на нестандартнитв артикули
                        
                        if (is_array($grDetails['name'])) {
                            foreach ($grDetails['name'] as $k => $v) {
                                if ($grDetails['code'][$k]) {
                                    $isPublic = (cat_Products::fetch(cat_Products::getByCode($grDetails['code'][$k])->productId)->isPublic);
                                }
                                
                                if (!$grDetails['code'][$k] || $isPublic == 'no') {
                                    unset($grDetails['code'][$k]);
                                    unset($grDetails['name'][$k]);
                                    unset($grDetails['minQuantity'][$k]);
                                    unset($grDetails['maxQuantity'][$k]);
                                }
                            }
                        }
                        
                        // Ограничава броя на артикулите за добавяне
                        
                        $count = 0;
                        $countUnset = 0;
                        
                        if (is_array($grDetails['code'])) {
                            foreach ($grDetails['code'] as $k => $v) {
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
                            
                            if ($countUnset > 0) {
                                $groupName = cat_Groups::getTitleById($rec->groupId);
                                $maxArt = self::NUMBER_OF_ITEMS_TO_ADD;
                                
                                $form->setWarning('groupId', "${countUnset} артикула от група ${groupName} няма да  бъдат добавени.
            						Максимален брой артикули за еднократно добавяне - ${maxArt}.
            						Може да добавите още артикули от групата при следваща редакция.");
                            }
                        }
                        
                        $jDetails = json_encode($details);
                        
                        $form->rec->additional = $jDetails;
                    }
                }
            }
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
        
        $codes = array();
        
        if(is_string($rec->additional)) {
            $additional = json_decode($rec->additional, false);
        } else {
            $additional = (object) $rec->additional;
        }
         
        $minQuantity = $maxQuantity = array();

        // Подготвяме заявката за извличането на записите от store_Products
        $sQuery = store_Products::getQuery();
        $sQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
        $sQuery->EXT('measureId', 'cat_Products', 'externalName=measureId,externalKey=productId');
        $sQuery->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');

        if ($rec->limmits == 'no') {
            // Филтриране по група продукти
            $sQuery->where("#groups LIKE '%|{$rec->groupId}|%'");
        } else {
            // Филтриране по кодове
            if (is_array($additional->code)) {
                foreach ($additional->code as $rowId => $code) {
                    $code = mb_strtolower($code);
                    $codes[$code] = $code;
                    $minQuantity[$code] = $additional->minQuantity[$rowId];
                    $maxQuantity[$code] = $additional->maxQuantity[$rowId];
                }
                $codeList = '|' . implode('|', $codes) . '|';
            }
            $sQuery->where(array("'[#1#]' LIKE CONCAT('%|', LOWER(#code), '|%')", $codeList));
        }
        
        // Филтриране по склад, ако е зададено
        if (isset($rec->storeId)) {
            $sQuery->where("#storeId = {$rec->storeId}");
        }
            
        while ($recProduct = $sQuery->fetch()) {
            
            $productId = $recProduct->productId;

            if ($rec->typeOfQuantity == 'TRUE') {
                // Гледаме разполагаемото количество
                $quantity = $recProduct->quantity - $recProduct->reservedQuantity;
            } else {
                // Гледаме наличното количество
                $quantity = $recProduct->quantity;
            }
                            
            if ($obj = &$recs[$productId]) {
                $obj->quantity += $quantity;
            } else {
                $key = mb_strtolower($recProduct->code);
                $recs[$productId] = (object) array(                                    
                                    'measure' => $recProduct->measureId,
                                    'productId' => $productId,
                                    'storeId' => $rec->storeId,
                                    'quantity' => $quantity,
                                    'minQuantity' => (int) $minQuantity[$key],
                                    'maxQuantity' => (int) $maxQuantity[$key],
                                    'code' => $recProduct->code,
                                );
            }
        }
            
        // Определяне на индикаторите за "свръх наличност" и "под минимум";
        foreach ($recs as $productId => $prodRec) {
            $prodRec->conditionQuantity = 'ok';
            $prodRec->conditionColor = 'green';
            if($prodRec->maxQuantity == 0 && $prodRec->minQuantity == 0) {
                continue;
            }
            if ($prodRec->quantity > $prodRec->maxQuantity) {
                $prodRec->conditionQuantity = 'свръх наличност';
                $prodRec->conditionColor = 'blue';
            } elseif($prodRec->quantity < $prodRec->minQuantity) {
                $prodRec->conditionQuantity = 'под минимум';
                $prodRec->conditionColor = 'red';
            }
        }            

        return $recs;
    }
    
    
    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec
     *                         - записа
     * @param bool     $export
     *                         - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');
        
        if ($export !== false) {
            $fld->FLD('code', 'varchar', 'caption=Код');
        }
        $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
        $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
        $fld->FLD('quantity', 'double(smartRound,decimals=2)', 'caption=Количество,smartCenter');
        
        if ($rec->limmits == 'yes') {
            $fld->FLD('minQuantity', 'double(smartRound,decimals=2)', 'caption=Минимално,smartCenter');
            $fld->FLD('maxQuantity', 'double(smartRound,decimals=2)', 'caption=Максимално,smartCenter');
            $fld->FLD('conditionQuantity', 'text', 'caption=Състояние,tdClass=centered');
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
        $Int = cls::get('type_Int');
        
        $row = new stdClass();
        $row->productId = cat_Products::getShortHyperlink($dRec->productId, true);
        
        if (isset($dRec->quantity)) {
            $row->quantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->quantity);
            $row->quantity = ht::styleIfNegative($row->quantity, $dRec->quantity);
        }
        
        if (isset($dRec->measure)) {
            $row->measure = cat_UoM::fetchField($dRec->measure, 'shortName');
        }
        
        if (isset($dRec->minQuantity)) {
            $row->minQuantity = core_Type::getByName('double(smartRound,decimals=3)')->toVerbal($dRec->minQuantity);
        }
        
        if (isset($dRec->maxQuantity)) {
            $row->maxQuantity = core_Type::getByName('double(smartRound,decimals=3)')->toVerbal($dRec->maxQuantity);
        }
        
        if ((isset($dRec->conditionQuantity) && ((isset($dRec->minQuantity)) || (isset($dRec->maxQuantity))))) {
            $row->conditionQuantity = "<span style='color: {$dRec->conditionColor}'>{$dRec->conditionQuantity}</span>";
        }
        
        return $row;
    }
    
    
    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver
     * @param stdClass            $res
     * @param stdClass            $rec
     * @param stdClass            $dRec
     */
    protected static function on_AfterGetCsvRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec)
    {
        $code = cat_Products::fetchField($dRec->productId, 'code');
        $res->code = (!empty($code)) ? $code : "Art{$dRec->productId}";
    }
    
    
    /**
     * Изчиства повтарящи се стойности във формата
     *
     * @param
     *            $arr
     *
     * @return array
     */
    public static function removeRpeadValues($arr)
    {
        $tempArr = (array) $arr;
        
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
