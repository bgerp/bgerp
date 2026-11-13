<?php


/**
 * Клас 'cat_products_Params' - продуктови параметри
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class cat_products_Params extends cat_products_ProtoParams
{
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'productId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,productId=Обект, paramId, paramValue';
    
    
    /**
     * Кой може да добавя
     */
    public $canAdd = 'powerUser';
    
    
    /**
     * Кой може да редактира
     */
    public $canEdit = 'powerUser';
    
    
    /**
     * Кой може да изтрива
     */
    public $canDelete = 'powerUser';


    /**
     * Позволено ли е мастъра да не е наследник на core_Master
     */
    public $requireMasterBeInstanceOfCoreMaster = false;


    /**
     * Кои полета ще извличаме, преди изтриване на заявката
     */
    public $fetchFieldsBeforeDelete = 'id, classId, productId, paramId';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('classId', 'class', 'input=hidden,silent');
        $this->FLD('productId', 'int', 'input=hidden,silent,tdClass=leftCol wrapText');
        $this->addParamFields();
        $this->FLD('type', 'enum(mandatory=Задължителен,optional=Незадължителен,readOnly=Само за четене)', 'input=none,caption=Тип');

        $this->setDbUnique('classId,productId,paramId');
        $this->setDbIndex('classId,productId');
        $this->setDbIndex('productId,classId');
    }
    
    
    /**
     * Кой е мастър класа
     */
    public function getMasterMvc($rec)
    {
        $masterMvc = cls::get($rec->classId);

        return $masterMvc;
    }


    /**
     * Връща ид на класа на обекта, към който е параметърът
     */
    public function getDomainClassId($rec)
    {
        return $rec->classId;
    }


    /**
     * Връща параметрите, които могат да се добавят към обекта
     */
    public function getParamOptions($rec, &$warningMsg)
    {
        if($rec->classId == cat_BomDetails::getClassId()){
            $warningMsg = 'Няма повече планиращи параметри|*!';
        }

        return self::getRemainingOptions($rec->classId, $rec->productId, $rec->id ?? null);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if(isset($fields['-list'])){
            $Class = cls::get($rec->classId);
            if($Class instanceof core_Master){
                $row->productId = $Class->getHyperlink($rec->productId, true);
            } elseif($Class instanceof core_Detail) {
                $row->productId = $Class->Master->getHyperlink($Class->fetchField($rec->productId, $Class->masterKey), true);
            }
        }
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    protected static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;

        // Ако параметърът се добавя към прототипен артикул да може да се посочва параметрите му дали да са опционални
        if($mvc->isTemplateProduct($data)){
            $form->setField('type', 'input,mandatory');
            $form->setDefault('type', 'optional');
        }
    }


    /**
     * Стойността не е задължителна само при прототипен артикул
     */
    public function isParamValueMandatory($data)
    {
        return !$this->isTemplateProduct($data);
    }


    /**
     * Дали параметърът се добавя към прототипен артикул
     */
    protected function isTemplateProduct($data)
    {
        $Master = $this->getMasterMvc($data->form->rec);

        return ($Master instanceof cat_Products) && (($data->masterRec->state ?? null) == 'template');
    }
    
    
    /**
     * Изпълнява се след въвеждане на данните от Request
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
        if ($form->isSubmitted()) {
            $rec = &$form->rec;
            
            // Проверка на теглата (временно решение)
            if ($rec->classId == cat_Products::getClassId()) {
                $pSysId = cat_Params::fetchField($rec->paramId, 'sysId');
                
                if (in_array($pSysId, array('weight', 'weightKg'))) {
                    $weightPackagingsCount = cat_products_Packagings::countSameTypePackagings($rec->productId, 'kg');
                    $p = ($pSysId == 'weight') ? 'weightKg' : 'weight';
                    $otherPValue = cat_Products::getParams($rec->productId, $p);
                    $measureId = cat_Products::fetchField($rec->productId, 'measureId');
                    
                    if (!empty($otherPValue)) {
                        $form->setError('paramId', 'Има вече параметър за тегло');
                    } elseif ($weightPackagingsCount || cat_UoM::isWeightMeasure($measureId)) {
                        $mSysId = ($pSysId == 'weight') ? 'g' : 'kg';
                        $packagingId = cat_UoM::fetchBySysId($mSysId)->id;
                        if(empty($rec->paramValue)){
                            $form->setError('paramValue', 'Теглото не може да е|* 0');
                        } else {
                            $v = 1 / $rec->paramValue;
                            if ($error = cat_products_Packagings::checkWeightQuantity($rec->productId, $packagingId, $v)) {
                                $form->setError('paramValue', $error);
                            }
                        }
                    }
                }
            }

            if (($rec->type ?? null) == 'readOnly' && !strlen($rec->paramValue ?? '')) {
                $form->setError('type,paramValue', 'При опция "Само за четене" не може стойноста да е празна');
            }
        }
    }


    /**
     * Помощна ф-я, която връща заглавие за формата при добавяне на детайл към клас
     * Изнесена е статично за да може да се използва и от класове, които не наследяват core_Detail,
     * Но реално се добавят като детайли към друг клас
     *
     * @param mixed    $master      - ид на класа на мастъра
     * @param int      $masterId    - ид на мастъра
     * @param string   $singleTitle - еденично заглавие
     * @param int|NULL $recId       - ид на записа, ако има
     * @param string   $preposition - предлог
     * @param int|NULL $len         - максимална дължина на стринга
     *
     * @return string $title      - заглавието на формата на 'Детайла'
     */
    public static function getEditTitle($master, $masterId, $singleTitle, $recId, $preposition = null, $len = null)
    {
        if($master instanceof cat_BomDetails){
            $master = cls::get('cat_Boms');
            $masterId = cat_BomDetails::fetchField($masterId, 'bomId');
        }

        return core_Detail::getEditTitle($master, $masterId, $singleTitle, $recId, $preposition);
    }

    
    /**
     * Връща не-използваните параметри за конкретния продукт, като опции
     *
     * @param $productId int ид на продукта
     * @param $id int ид от текущия модел, което не трябва да бъде изключено
     */
    public static function getRemainingOptions($classId, $productId, $id = null)
    {
        $query = self::getQuery();
        $query->where("#classId = {$classId} AND #productId = {$productId}");
        $notIn = arr::extractValuesFromArray($query->fetchAll(), 'paramId');
        $in = array();

        $taskClassId = planning_Tasks::getClassId();
        $bomClassId = cat_BomDetails::getClassId();
        if ($classId == cat_Products::getClassId()) {
            $measureId = cat_Products::fetchField($productId, 'measureId');
            if (cat_UoM::isWeightMeasure($measureId)) {
                $grSysid = cat_Params::fetchIdBySysId('weight');
                $kgSysid = cat_Params::fetchIdBySysId('weightKg');
                $notIn[$grSysid] = $grSysid;
                $notIn[$kgSysid] = $kgSysid;
            }
        } elseif($classId == $taskClassId || $classId == $bomClassId){
            $taskStepId = cls::get($classId)->fetchField($productId, 'productId');
            $Driver = cat_Products::getDriver($taskStepId);
            $pData = $Driver->getProductionData($taskStepId);
            $in = $pData['planningParams'];

            if(!countR($in)) return array();
        }

        $where = '';
        if (countR($notIn)) {
            $notInString = implode(',', $notIn);
            $where = "#id NOT IN ({$notInString})";
        }
        if (countR($in)) {
            $inString = implode(',', $in);
            $where .= (!empty($where) ? ' AND ' : '') . "#id IN ({$inString})";
        }

        $options = cat_Params::makeArray4Select(null, $where);

        return $options;
    }
    
    
    /**
     * Връща стойноста на даден параметър за даден продукт по негово sysId
     *
     * @param string $classId   - ид на ембедъра
     * @param int    $productId - ид на продукт
     * @param int    $sysId     - sysId на параметъра
     * @param bool   $verbal    - вербално представяне
     *
     * @return string $value - стойността на параметъра
     */
    public static function fetchParamValue($classId, $productId, $sysId, $verbal = false)
    {
        $paramId = (is_numeric($sysId)) ? cat_Params::fetchField($sysId) : cat_Params::fetchIdBySysId($sysId);
        $Class = cls::get($classId);

        if (!empty($paramId)) {
            $paramValue = self::fetchField("#productId = {$productId} AND #paramId = {$paramId} AND #classId = {$Class->getClassId()}", 'paramValue');
            if ($verbal === true) {
                $paramValue = cat_Params::toVerbal($paramId, $Class->getClassId(), $productId, $paramValue);
            }
            
            return $paramValue;
        }
    }
    
    
    
    /**
     * Подготвя данните за екстеншъна с параметрите на продукта
     */
    public static function prepareParams(&$data)
    {
        $query = static::getParamQuery();
        $query->where("#productId = {$data->masterId} AND #classId = {$data->masterClassId}");

        // Ако подготвяме за външен документ, да се показват само параметрите за външни документи
        if (in_array($data->documentType ?? null, array('public', 'invoice'), true)) {
            $query->EXT('showInPublicDocuments', 'cat_Params', 'externalName=showInPublicDocuments,externalKey=paramId');
            $query->where("#showInPublicDocuments = 'yes'");
        }

        $me = cls::get(get_called_class());
        $data->params = array();
        $sortRecs = array();
        while ($rec = $query->fetch()) {
            $data->params[$rec->id] = $me->prepareParamRow($rec);
            $sortRecs[$rec->id] = $rec;
        }

        // Наследените от групите параметри се показват в сингъла и в описанието на артикула в документите
        if ($data->masterClassId == cat_Products::getClassId()) {
            static::addGroupParams($data, $sortRecs);
        }
        
        if (self::haveRightFor('add', (object) array('productId' => $data->masterId, 'classId' => $data->masterClassId))) {
            $data->addUrl = array(__CLASS__, 'add', 'productId' => $data->masterId, 'classId' => $data->masterClassId, 'ret_url' => true);
        }
    }
    
    
    /**
     * Добавя наследените от групите параметри, които артикулът няма, подредени заедно със собствените
     * Извън сингъла са като обикновен ред, а за външните документи - само показваните в тях
     *
     * @param stdClass $data
     * @param array    $sortRecs - собствените записи, с ключ ключа на реда
     *
     * @return void
     */
    private static function addGroupParams($data, $sortRecs)
    {
        $groupRecs = cat_GroupParams::getProductParamRecs($data->masterId);
        if (!countR($groupRecs)) return;

        $GroupParams = cls::get('cat_GroupParams');
        $isSingle = !empty($data->isSingle);
        foreach ($sortRecs as $key => $rec) {
            $gRec = $groupRecs[$rec->paramId] ?? null;
            if (!is_object($gRec)) continue;
            unset($groupRecs[$rec->paramId]);
            if (cat_GroupParams::hasValue($rec->paramValue ?? null)) continue;

            // Собственият запис е празен - важи наследената стойност, а редакцията е на собствения
            $editUrl = ($isSingle && static::haveRightFor('edit', $rec)) ? array(get_called_class(), 'edit', $rec->id, 'ret_url' => true) : null;
            $data->params[$key] = static::prepareGroupParamRow($GroupParams, $gRec, $isSingle, $editUrl);
        }

        $isPublic = in_array($data->documentType ?? null, array('public', 'invoice'), true);
        foreach ($groupRecs as $gRec) {
            if ($isPublic && cat_Params::fetchField($gRec->paramId, 'showInPublicDocuments') != 'yes') continue;

            $overrideRec = (object) array('productId' => $data->masterId, 'classId' => $data->masterClassId, 'paramId' => $gRec->paramId);
            $overrideUrl = ($isSingle && static::haveRightFor('add', $overrideRec)) ? array(get_called_class(), 'add', 'productId' => $data->masterId, 'classId' => $data->masterClassId, 'paramId' => $gRec->paramId, 'ret_url' => true) : null;
            $data->params["g{$gRec->id}"] = static::prepareGroupParamRow($GroupParams, $gRec, $isSingle, $overrideUrl);
            $sortRecs["g{$gRec->id}"] = $gRec;
        }

        // Подредба като в заявката: група на параметъра, ред, собствените преди наследените
        $keys = array_keys($sortRecs);
        $pos = array_flip($keys);
        usort($keys, function ($a, $b) use ($sortRecs, $pos) {
            $cmp = strcmp(mb_strtolower($sortRecs[$a]->group ?? ''), mb_strtolower($sortRecs[$b]->group ?? ''));
            if ($cmp == 0) {
                $cmp = (int) ($sortRecs[$a]->orderEx ?? 0) <=> (int) ($sortRecs[$b]->orderEx ?? 0);
            }

            return ($cmp == 0) ? ($pos[$a] <=> $pos[$b]) : $cmp;
        });

        $params = array();
        foreach ($keys as $key) {
            $params[$key] = $data->params[$key];
        }
        $data->params = $params;
    }


    /**
     * Ред за наследен параметър - в сингъла маркиран като наследен, иначе като обикновен ред без бутони
     *
     * @param cat_GroupParams $GroupParams
     * @param stdClass        $gRec        - запис на параметър на група
     * @param bool            $isSingle    - дали е за сингъла на артикула
     * @param array|null      $url         - адрес за предефиниране на стойността
     *
     * @return stdClass $row
     */
    private static function prepareGroupParamRow($GroupParams, $gRec, $isSingle, $url)
    {
        $row = $GroupParams->prepareInheritedRow($gRec, $url);
        if (!$isSingle) {
            unset($row->_inheritedHint);
        }

        return $row;
    }


    /**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        // Ако потрбителя няма достъп до сингъла на артикула, не може да модифицира параметрите
        if (($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec)) {
            if (isset($rec->classId)) {
                if ($rec->classId == planning_Tasks::getClassId()) {
                    $requiredRoles = 'task,ceo';
                } elseif ($rec->classId == marketing_Inquiries2::getClassId()) {
                    $requiredRoles = 'marketing,ceo';
                } elseif($rec->classId == planning_AssetResources::getClassId()){
                    $requiredRoles = 'ceo, planningMaster';
                } elseif ($rec->classId == cat_Products::getClassId()) {
                    $requiredRoles = 'cat,ceo,catEdit,sales,purchase';
                    $isPublic = cat_Products::fetchField($rec->productId, 'isPublic');
                    if ($isPublic == 'yes') {
                        $requiredRoles = 'catEdit,ceo';
                    }
                } elseif ($rec->classId == cat_BomDetails::getClassId()) {
                    $requiredRoles = cat_BomDetails::getRequiredRoles($action, $rec->productId, $userId);
                }
            }
        }
        
        if (isset($rec->productId, $rec->classId)) {
            $Class = cls::get($rec->classId);
            $pRec = $Class->fetch($rec->productId);
            if($rec->classId == cat_Products::getClassId()){
                if ($action == 'add') {
                    $InnerClass = cls::get($pRec->innerClass);
                    if (empty($InnerClass->allowCustomParams)) {
                        $requiredRoles = 'no_one';
                    }
                }

                if ($pRec->state != 'active' && $pRec->state != 'draft' && $pRec->state != 'template') {
                    $requiredRoles = 'no_one';
                }

                if (!$Class->haveRightFor('single', $rec->productId)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }


    /**
     * Връща параметрите, които са счетоводни свойства
     *
     * @param mixed $classId
     * @param int $objectId
     * @return array $features
     */
    public static function getFeatures($classId, $objectId)
    {
        $features = array();
        $query = self::getQuery();
        $classId = cls::get($classId)->getClassId();
        $query->where("#classId = {$classId} AND #productId = '{$objectId}'");
        $query->EXT('isFeature', 'cat_Params', 'externalName=isFeature,externalKey=paramId');
        $query->where("#isFeature = 'yes'");
        
        while ($rec = $query->fetch()) {
            $row = self::recToVerbal($rec, 'paramId,paramValue');
            $features[$row->paramId] = $row->paramValue;
        }
        
        return $features;
    }
    
    
    /**
     * След запис се обновяват свойствата на перата
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        $Class = cls::get($rec->classId);
        if($Class instanceof cat_Products){
            $mvc->syncWithFeature($rec->paramId, $rec->productId);
            cat_products_ParamIndex::markDirty($rec->productId);
        }

        // Параметрите може да се ползват във формули на рецепти
        cat_Boms::clearProductParamsCache();
    }
    
    
    /**
     * Преди изтриване се обновяват свойствата на перата
     */
    public static function on_AfterDelete($mvc, &$res, $query, $cond)
    {
        cat_Boms::clearProductParamsCache();

        $productClassId = cat_Products::getClassId();
        foreach ($query->getDeletedRecs() as $rec) {
            $mvc->syncWithFeature($rec->paramId, $rec->productId);
            if ($rec->classId == $productClassId) {
                cat_products_ParamIndex::markDirty($rec->productId);
            }
        }
    }
    
    
    /**
     * Синхронизира свойствата
     *
     * @param int $paramId
     * @param int $productId
     *
     * @return void
     */
    private function syncWithFeature($paramId, $productId)
    {
        cat_Products::touchRec($productId);
        if (cat_Params::fetchField("#id = '{$paramId}'", 'isFeature') == 'yes') {
            acc_Features::syncFeatures(cat_Products::getClassId(), $productId);
        }
        
        sales_TransportValues::recalcTransportByProductId($productId);
    }
    
    
    /**
     * Връща нередактируемите имена на параметрите при печат на етикети
     * 
     * @param int $classId
     * @param int $productId
     * @return array $notEdittableParamNames
     */
    public static function getNotEditableLabelParamNames($classId, $productId)
    {
        $notEdittableParamNames = array();
        $pQuery = cat_products_Params::getQuery();
        $pQuery->EXT('editInLabel', 'cat_Params', 'externalName=editInLabel,externalKey=paramId');
        $pQuery->where("#editInLabel = 'no' AND #productId = {$productId} AND #classId={$classId}");
        $pQuery->show('paramId');
        
        $notEditableParams = arr::extractValuesFromArray($pQuery->fetchAll(), 'paramId');
        if(countR($notEditableParams)){
            $notEdittableParamNames = cat_Params::getParamNameArr($notEditableParams, true) ;
        }
        
        return $notEdittableParamNames;
    }


    /**
     * Форсира стойност на продуктов параметър
     *
     * @param $objectId              - ид на обект
     * @param $paramName             - име на параметър
     * @param $paramSuffix           - суфикс на параметър
     * @param $paramValue            - стойност на параметър
     * @param string $classId        - домейн на обекта
     * @param string $paramType      - тип на параметъра
     * @param null $paramTypeOptions - опции на параметъра
     * @return mixed|object|void $rec
     * @throws core_exception_Expect
     */
    public static function forceParam($objectId, $paramName, $paramSuffix, $paramValue, $classId = 'cat_Products', $paramType = 'cond_type_varchar', $paramTypeOptions = null)
    {
        $classId = cls::get($classId)->getClassId();
        expect($paramId = cat_Params::force(null, $paramName, $paramType, $paramTypeOptions, $paramSuffix));

        $rec = self::fetch("#productId = {$objectId} AND #paramId = {$paramId} AND #classId = {$classId}");

        if(empty($rec)){
            $rec = (object)array('productId' => $objectId, 'classId' => $classId, 'paramId' => $paramId, 'paramValue' => $paramValue);
            cls::get(get_called_class())->save_($rec);
        }

        return $rec;
    }


    /**
     * Добавяне на параметри за редактиране във формата на обект
     *
     * @param mixed $classId
     * @param int $objectId
     * @param int $productId
     * @param core_Form $form
     * @param int $planningStepProductId
     *
     * @return void
     */
    public static function addProductParamsToForm($classId, $objectId, $productId, $planningStepProductId, &$form)
    {
        // Показване на параметрите за задача във формата, като задължителни полета
        $class = cls::get($classId);

        if(isset($objectId)){
            $params = $paramValues = array();
            $pQuery = cat_products_Params::getQuery();
            $pQuery->where("#classId = {$class->getClassId()} AND #productId = {$objectId}");
            while($pRec = $pQuery->fetch()){
                $params[$pRec->paramId] = $pRec->paramId;
                $pRec->paramValue = cat_Params::getReplacementValueOnClone($pRec->paramId, $classId, $objectId, $pRec->paramValue);
                $paramValues[$pRec->paramId] = $pRec->paramValue;
            }

        } else {
            $paramValues = cat_Products::getParams($productId);
            $params = array_combine(array_keys($paramValues), array_keys($paramValues));
        }


        $stepParams = $prevRecValues = array();
        if(isset($planningStepProductId)){
            if($StepDriver = cat_Products::getDriver($planningStepProductId)){
                $pData = $StepDriver->getProductionData($planningStepProductId);
                if(is_array($pData['planningParams'])){
                    $params = $pData['planningParams'];
                } else {
                    $params = array();
                }
                if(empty($objectId)){
                    $stepParams = cat_Products::getParams($planningStepProductId);
                }
            }

            if($class instanceof planning_Tasks){
                if(countR($params)){
                    $prevRecValues = planning_Tasks::getPrevParamValues($form->rec->originId, $params);
                }
            }
        }

        $plannedProductName = cat_Products::getVerbal($productId, 'name');
        $plannedProductName = str_replace(',', ' ', $plannedProductName);

        // Сортиране на намерените параметри
        if(countR($params)){
            $oQuery = cat_Params::getQuery();
            $oQuery->XPR('orderEx', 'varchar', 'COALESCE(#order, 999999)');
            $oQuery->orderBy('group,orderEx,id', 'ASC');
            $oQuery->in('id', $params);
            $params = arr::extractValuesFromArray($oQuery->fetchAll(), 'id');
        }

        if(!isset($objectId)){
            $domainClassId = cat_Products::getClassId();
            $objectId = $productId;
        } else {
            $domainClassId = $class->getClassId();
        }

        foreach ($params as $pId) {
            if(array_key_exists($pId, $paramValues)){
                $v = $paramValues[$pId];
            } elseif(array_key_exists($pId, $stepParams)){
                $v = $stepParams[$pId];
            } elseif(array_key_exists($pId, $prevRecValues)){
                $v = $prevRecValues[$pId];
            } else {
                $v = cat_Params::getDefaultValue($pId, $domainClassId, $objectId);
            }

            $paramRec = cat_Params::fetch($pId);
            if(in_array($paramRec->state, array('rejected', 'closed'))) continue;

            $name = cat_Params::getVerbal($paramRec, 'name');
            if(!empty($paramRec->group)){
                $groupName = cat_Params::getVerbal($paramRec, 'group');
                $caption = "Параметри за|*: <b>{$groupName}</b>->{$name}";
            } else {
                $caption = "Параметри за планиране на|*: <b>{$plannedProductName}</b>->|{$name}|*";
            }
            $form->FLD("paramcat{$pId}", 'double', "caption={$caption},before=indPackagingId");

            $ParamType = cat_Params::getTypeInstance($pId, $domainClassId, $objectId);
            $form->setFieldType("paramcat{$pId}", $ParamType);
            if($ParamType instanceof type_Key2 || $ParamType instanceof type_Key){
                $form->setField("paramcat{$pId}", 'class=w100');
            }

            if (!empty($paramRec->suffix)) {
                $suffix = cat_Params::getVerbal($paramRec, 'suffix');
                $form->setField("paramcat{$pId}", "unit={$suffix}");
            }

            if (isset($v)) {
                if(!($ParamType instanceof fileman_FileType)) {
                    if(cat_Params::haveDriver($paramRec, 'cond_type_Keylist')){
                        $defaults = keylist::toArray($v);
                        $v = array_intersect_key($ParamType->getSuggestions(), $defaults);
                    }
                }
                $form->setDefault("paramcat{$pId}", $v);
                if($paramRec->valueType == 'readonly'){
                    $form->setReadOnly("paramcat{$pId}");
                }
            }

            if($paramRec->valueType == 'mandatory'){
                $form->setField("paramcat{$pId}", 'mandatory');
            }

            $form->rec->_params["paramcat{$pId}"] = (object) array('paramId' => $pId);
        }
    }


    /**
     * Синхронизиране на параметрите на артикула
     *
     * @param mixed $class
     * @param int $id
     * @param array $params
     * @return void
     */
    public static function syncParams($class, $id, $params)
    {
        $Class = cls::get($class);

        // Извличане на старите записи
        $newRecs = array();
        $exQuery = static::getQuery();
        $exQuery->where("#classId = {$Class->getClassId()} AND #productId = {$id}");
        $exRecs = $exQuery->fetchAll();

        // Кои записи ще се добавят/обновяват
        foreach ($params as $paramId => $val) {
            if (!isset($val)) continue;

            $paramDriver = cat_Params::getDriver($paramId);
            if(($paramDriver instanceof cond_type_Text || $paramDriver instanceof cond_type_Varchar || $paramDriver instanceof cond_type_File || $paramDriver instanceof cond_type_Html || $paramDriver instanceof cond_type_Image || $paramDriver instanceof cond_type_Files) && empty($val)) continue;
            $nRec = (object)array('paramId' => $paramId, 'paramValue' => $val, 'classId' => $Class->getClassId(), 'productId' => $id);
            $newRecs[] = $nRec;
        }

        // Синхронизиране
        $synced = arr::syncArrays($newRecs, $exRecs, 'classId,productId,paramId', 'paramValue');
        if (countR($synced['insert'])) {
            static::saveArray($synced['insert']);
        }

        if (countR($synced['update'])) {
            static::saveArray($synced['update'], 'id,paramValue');
        }

        if (countR($synced['delete'])) {
            $delete = implode(',', $synced['delete']);
            static::delete("#id IN ({$delete})");
        }
    }


    /**
     * Записване на параметрите от подадения обект
     *
     * @param $class
     * @param $rec
     * @param $paramField
     * @return void
     */
    public static function saveParams($class, $rec, $paramField = '_params')
    {
        $params = array();
        if(is_array($rec->{$paramField})){
            foreach ($rec->{$paramField} as $k => $o) {
                $params[$o->paramId] = $rec->{$k};
            }
        }

        static::syncParams($class, $rec->id, $params);
    }


    /**
     * Подготвя параметрите на обекта
     *
     * @param $class
     * @param $objectRec
     * @return stdClass
     */
    public static function prepareClassObjectParams($class, $objectRec)
    {
        $d = new stdClass();
        $d->masterId = $objectRec->id;
        $d->masterClassId = $class::getClassId();

        if($class instanceof cat_BomDetails){
            if(!in_array($objectRec->state, array('draft', 'active'))){
                $d->noChange = true;
            }
        } elseif (in_array($objectRec->state, array('closed', 'stopped', 'rejected'))) {
            $d->noChange = true;
        }
        cat_products_Params::prepareParams($d);

        return $d;
    }
}
