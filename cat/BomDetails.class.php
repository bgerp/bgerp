<?php


/**
 * Мениджър на детайл на технологичната рецепта
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_BomDetails extends doc_Detail
{
    /**
     * Константа за грешка при изчисление
     */
    const CALC_ERROR = 'Грешка при изчисляване';
    
    
    /**
     * Заглавие
     */
    public $title = 'Детайл на технологичната рецепта';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'bomId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_Modified, plg_RowTools2, cat_Wrapper, plg_SaveAndNew, planning_plg_ReplaceEquivalentProducts, plg_PrevAndNext';
    
    
    /**
     * Кои полета да се извличат при изтриване
     */
    public $fetchFieldsBeforeDelete = 'id,bomId,type';
    
    
    /**
     * Активен таб
     */
    public $currentTab = 'Рецепти';
    
    
    /**
     * Заглавие
     */
    public $singleTitle = 'Детайл на технологична рецепта';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,cat,sales';
    
    
    /**
     * Кой има право да променя взаимно заменяемите артикули?
     */
    public $canReplaceproduct = 'ceo,cat,sales';
    
    
    /**
     * Кой има право да разгъва?
     */
    public $canExpand = 'ceo,cat,sales';
    
    
    /**
     * Кой има право да свива?
     */
    public $canShrink = 'ceo,cat,sales';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,cat,sales';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,cat,sales,techno';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'bomId=Рецепта,position=№, resourceId, packagingId=Мярка,propQuantity=Формула,rowQuantity=Вложено->Количество,primeCost,coefficient';
    
    
    /**
     * Поле за заместване на артикул
     *
     * @see planning_plg_ReplaceEquivalentProducts
     */
    public $replaceProductFieldName = 'resourceId';
    
    
    /**
     * Поле за артикула
     */
    public $productFld = 'resourceId';
    
    
    /**
     * Поле за количеството на заместващ артикул
     *
     * @see planning_plg_ReplaceEquivalentProducts
     */
    public $replaceProductQuantityFieldName = 'propQuantity';
    
    
    /**
     * При колко линка в тулбара на реда да не се показва дропдауна
     *
     * @param int
     *
     * @see plg_RowTools2
     */
    public $rowToolsMinLinksToShow = 2;
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('bomId', 'key(mvc=cat_Boms)', 'column=none,input=hidden,silent');
        $this->FLD('resourceId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,maxSuggestions=100,forceAjax)', 'class=w100,caption=Материал,mandatory,silent,removeAndRefreshForm=packagingId|description|inputStores|storeIn|centerId|fixedAssets|employees|norm|labelPackagingId|labelQuantityInPack|labelType|labelTemplate|paramcat');
        $this->FLD('parentId', 'key(mvc=cat_BomDetails,select=id)', 'caption=Подетап на,remember,removeAndRefreshForm=propQuantity,silent');
        $this->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка', 'tdClass=small-field nowrap,smartCenter,silent,removeAndRefreshForm=quantityInPack,mandatory,input=hidden');
        $this->FLD('quantityInPack', 'double(smartRound)', 'input=none,notNull,value=1');
        
        $this->FLD('position', 'int(Min=0)', 'caption=Позиция,tdClass=leftCol');
        $this->FLD('propQuantity', 'text(rows=2, maxOptionsShowCount=20)', 'caption=Формула,tdClass=accCell,mandatory');
        $this->FLD('description', 'richtext(rows=3,bucket=Notes)', 'caption=Допълнително->Описание');

        $this->FLD('centerId', 'key(mvc=planning_Centers,select=name, allowEmpty)', 'caption=Използване в производството->Център на дейност, remember,silent,removeAndRefreshForm=norm|fixedAssets|employees,input=hidden');
        $this->FLD('inputStores', 'keylist(mvc=store_Stores,select=name,allowEmpty,makeLink)', 'caption=Използване в производството->Произвеждане В,input=none');
        $this->FLD('storeIn', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Използване в производството->Материали ОТ,input=none');
        $this->FLD('fixedAssets', 'keylist(mvc=planning_AssetResources,select=name,makeLinks=hyperlink)', 'caption=Използване в производството->Оборудване,input=none');
        $this->FLD('employees', 'keylist(mvc=crm_Persons,select=id,makeLinks)', 'caption=Използване в производството->Оператори,input=none');
        $this->FLD('norm', 'planning_type_ProductionRate', 'caption=Използване в производството->Норма,input=none');

        $this->FLD('labelPackagingId', 'key(mvc=cat_UoM,select=name,allowEmpty)', 'caption=Етикиране в производството->Опаковка,input=hidden,tdClass=small-field nowrap,placeholder=Няма,silent,removeAndRefreshForm=labelQuantityInPack|labelTemplate|labelType');
        $this->FLD('labelQuantityInPack', 'double(smartRound,Min=0)', 'caption=Етикиране в производството->В опаковка,tdClass=small-field nowrap,input=hidden');
        $this->FLD('labelType', 'enum(print=Генериране,scan=Въвеждане,both=Комбинирано)', 'caption=Етикиране в производството->Производ. №,tdClass=small-field nowrap,input=hidden');
        $this->FLD('labelTemplate', 'key(mvc=label_Templates,select=title)', 'caption=Етикиране в производството->Шаблон,tdClass=small-field nowrap,input=hidden');

        $this->FLD('type', 'enum(input=Влагане,pop=Отпадък,stage=Етап)', 'caption=Действие,silent,input=hidden');
        $this->FLD('primeCost', 'double', 'caption=Себестойност,input=none,tdClass=accCell');
        $this->FLD('params', 'blob(serialize, compress)', 'input=none');
        $this->FNC('rowQuantity', 'double(maxDecimals=4)', 'caption=Количество,input=none,tdClass=accCell');
        $this->FLD('coefficient', 'double', 'input=none');
        
        $this->setDbIndex('parentId');
        $this->setDbIndex('resourceId');
        $this->setDbIndex('type');
    }
    
    
    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    protected static function on_AfterPrepareListFields($mvc, $data)
    {
        $baseCurrencyCode = acc_Periods::getBaseCurrencyCode($data->masterData->rec->modifiedOn);
        
        $data->listFields['propQuantity'] = "|К-во влагане за|* {$data->masterData->row->quantity}->|Формула|*";
        $data->listFields['rowQuantity'] = "|К-во влагане за|* {$data->masterData->row->quantity}->|Количество|*";
        $data->listFields['primeCost'] = "|К-во влагане за|* {$data->masterData->row->quantity}->|Сума|* <small>({$baseCurrencyCode})</small>";
        if (!haveRole('ceo, acc, cat, price')) {
            unset($data->listFields['primeCost']);
        }

        if(isset($data->masterMvc)){
            unset($data->listFields['bomId']);
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;

        if(!isset($rec->id)){
            $form->setFieldTypeParams('resourceId', array('forceOpen' => 'forceOpen'));
        } elseif($data->action != 'replaceproduct') {
            $form->setReadOnly('resourceId');
        }

        $matCaption = ($rec->type == 'input') ? 'Артикул' : (($rec->type == 'pop') ? 'Отпадък' : 'Етап');
        $parentIdCaption = ($rec->type == 'stage') ? 'Подетап на' : 'Етап';
        $form->setField('parentId', "caption={$parentIdCaption}");
        $form->setField('resourceId', "caption={$matCaption}");
        
        // Добавяме всички вложими артикули за избор
        $metas = ($rec->type == 'pop') ? 'canConvert,canStore' : 'canConvert';
        $groups = ($rec->type == 'pop') ? cat_Groups::getKeylistBySysIds('waste') : null;
        $onlyProductionStages = ($rec->type != 'stage') ? null : true;
        $form->setFieldTypeParams('resourceId', array('hasProperties' => $metas, 'groups' => $groups, 'onlyProductionStages' => $onlyProductionStages));
        
        $form->setDefault('type', 'input');
        $quantity = $data->masterRec->quantity;
        $originInfo = cat_Products::getProductInfo($data->masterRec->productId);
        $shortUom = cat_UoM::getShortName($originInfo->productRec->measureId);
        
        $propCaption = "Количество->|За|* |{$quantity}|* {$shortUom}";
        $form->setField('propQuantity', "caption={$propCaption}");
        
        // Възможните етапи са етапите от текущата рецепта
        $stages = array();
        $query = $mvc->getQuery();
        $query->where("#bomId = {$rec->bomId} AND #type = 'stage'");
        $query->show('resourceId');
        while ($dRec = $query->fetch()) {
            $stages[$dRec->id] = cat_Products::getTitleById($dRec->resourceId, false);
        }
        unset($stages[$rec->id]);
        
        // Добавяме намерените етапи за опции на етапите
        if (countR($stages)) {
            $form->setOptions('parentId', array('' => '') + $stages);
        } else {
            $form->setReadOnly('parentId');
        }

        if($rec->type == 'stage'){
            if(isset($rec->resourceId)){

                // Ако има данни за производство
                $form->setField('centerId', 'input');
                $form->setField('norm', 'input');
                $form->input('centerId', 'silent');
                $Driver = cat_Products::getDriver($rec->resourceId);
                $productionData = $Driver->getProductionData($rec->resourceId);

                $canStore = cat_Products::fetchField($rec->resourceId, 'canStore');
                if($canStore == 'yes'){
                    // Показване на полетата за етикетиране
                    $form->setField('storeIn', 'input');
                    $form->setField('inputStores', 'input');
                    $form->setField('labelPackagingId', 'input');

                    $productMeasureId = cat_Products::fetchField($rec->resourceId, 'measureId');
                    $packs = planning_Tasks::getAllowedLabelPackagingOptions($productMeasureId, $rec->resourceId, $rec->labelPackagingId);
                    $form->setOptions("labelPackagingId", $packs);
                }

                // Добавяне на дефолтите от производствените данни
                if($form->cmd == 'refresh' || Request::get('resourceId', 'int')){
                    if(empty($rec->centerId) && empty($rec->norm) && empty($rec->storeIn) && empty($rec->inputStores) && empty($rec->fixedAssets) && empty($rec->employees) && empty($rec->labelPackagingId) && empty($rec->labelTemplate) && empty($rec->labelType) && empty($rec->labelQuantityInPack)){
                        foreach (array('centerId', 'norm', 'storeIn', 'inputStores', 'fixedAssets', 'employees', 'labelPackagingId', 'labelQuantityInPack', 'labelType', 'labelTemplate') as $productionFld){
                            $defaultValue = is_array($productionData[$productionFld]) ? keylist::fromArray($productionData[$productionFld]) : $productionData[$productionFld];
                            $form->setDefault($productionFld, $defaultValue);
                            if($data->masterRec->type != 'production') {
                                $form->setField($productionFld, 'autohide=any');
                            }
                        }
                    }
                }

                // Ако има опаковка за етикетиране
                if(isset($rec->labelPackagingId)){
                    $form->setField('labelQuantityInPack', 'input');
                    $form->setField('labelTemplate', 'input');
                    $form->setField('labelType', 'input');

                    // Наличните за избор шаблони
                    $templateOptions = planning_Tasks::getAllAvailableLabelTemplates($rec->labelTemplate);
                    $form->setOptions("labelTemplate", $templateOptions);

                    // К-то в опаковката като хинт
                    $packRec = cat_products_Packagings::getPack($rec->resourceId, $rec->labelPackagingId);
                    $quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
                    $form->setField("labelQuantityInPack", "placeholder={$quantityInPack}");
                }

                // Ако има избран център на дейност да се добавят наличните оборудвания и оператори в него
                if(isset($rec->centerId)){
                    $folderId = planning_Centers::fetchField($rec->centerId, 'folderId');
                    $form->setField('fixedAssets', 'input');
                    $form->setField('employees', 'input');

                    // Налични оборудвания от избрания център
                    $fixedAssets = planning_AssetResources::getByFolderId($folderId, $rec->fixedAssets, 'planning_Tasks', true);
                    $form->setSuggestions("fixedAssets", $fixedAssets);

                    // Наличните човешки ресурси от избрания център
                    $hrAssets = planning_Hr::getByFolderId($folderId, $rec->employees);
                    $form->setSuggestions("employees", $hrAssets);
                }

                $masterRec = cat_Boms::fetch($rec->bomId);
                if(empty($rec->id)){
                    cat_products_Params::addProductParamsToForm($mvc, $rec->id, $masterRec->productId, $rec->resourceId, $form);
                }
            }
        }
    }
    
    
    /**
     * Преди подготовка на заглавието на формата
     */
    protected static function on_BeforePrepareEditTitle($mvc, &$res, $data)
    {
        $rec = &$data->form->rec;
        $data->singleTitle = ($rec->type == 'input') ? 'артикул за влагане' : (($rec->type == 'pop') ? 'отпадък' : 'етап');
    }
    
    
    /**
     * Изчислява израза
     *
     * @param string  $expr   - формулата
     * @param array $params - параметрите
     *
     * @return string $res - изчисленото количество
     */
    public static function calcExpr($expr, $params)
    {
        $expr = preg_replace('/\$Начално\s*=\s*/iu', '1/$T*', $expr);
        $expr = preg_replace('/(\d+)+\,(\d+)+/', '$1.$2', $expr);

        // Да не променяме логиката, не позволяваме на потребителя да въвежда тиражът ръчно
        if (is_array($params)) {
            $expr = str_replace('1/$T*', '_TEMP_', $expr);
            $expr = str_replace('$T', '$Trr', $expr);
            $expr = str_replace('_TEMP_', '1/$T*', $expr);
            $expr = strtr($expr, $params);
        }

        $expr = preg_replace_callback("/(?<=[^a-z0-9а-я\_]|^)+(?'fncName'[a-z0-9\_]+)\(\s*[\'\"]?(?'paramA'.*?)[\'\"]?\s*\,\s*[\'\"]?(?'paramB'.*?)[\'\"]?\s*(\,\s*[\'\"]?(?'paramC'.*?)[\'\"]?\s*)*\)/ui", array(get_called_class(), 'replaceFunctionsInFormula'), $expr);

        if (str::prepareMathExpr($expr) === false) {
            $res = self::CALC_ERROR;
        } else {
            $success = null;
            $res = str::calcMathExpr($expr, $success);
            if ($success === false) {
                $res = self::CALC_ERROR;
            }
        }
        
        return $res;
    }


    /**
     * Callback ф-я за заместване на функции във формулата
     */
    private static function replaceFunctionsInFormula($match)
    {
        $res = $match[0];

        $fncName = strtolower($match['fncName']);
        if($fncName == 'select'){
            if(!empty($match[0]) && !empty($match[1]) && !empty($match[2])){
                if(cls::load($match['paramA'], true)){
                    if(type_Int::isInt($match['paramB'])){
                        try{
                            $res = $match['paramA']::fetchField(trim($match['paramB']), $match['paramC']);
                        } catch(core_exception_Expect $e){}
                    }
                }
            }
        } elseif($fncName == 'defifnot'){
            $val = $match['paramA'];
            $evalSuccess = null;
            $val = str::calcMathExpr($val, $evalSuccess);
            if(!is_numeric($val) || $evalSuccess === false){
                $val = $match['paramB'];
                $evalSuccess = null;
                $val = str::calcMathExpr($val, $evalSuccess);
                if(!is_numeric($val) || $evalSuccess === false) {
                    $val = $match['paramC'];
                    $evalSuccess = null;
                    $val = str::calcMathExpr($val, $evalSuccess);
                }
            }

            if(is_numeric($val)) {
                $res = $val;
            }
        }  elseif($fncName == 'getproductparam') {

            if(is_numeric($match['paramA'])){
                try{
                    $paramVal = cat_Products::getParams($match['paramA'], $match['paramB']);

                    if(is_numeric($paramVal)) {
                        $res = $paramVal;
                    } elseif(strlen($match['paramC'])){
                        $res = $match['paramC'];
                    }
                } catch(core_exception_Expect $e){
                    if (strlen($match['paramC'])) {
                        $res = $match['paramC'];
                    }
                }
            } elseif (strlen($match['paramC'])) {
                $res = $match['paramC'];
            }
        }

        return $res;
    }


    /**
     * Проверява за коректност израз и го форматира.
     */
    public static function highlightExpr($expr, $params, $coefficient)
    {
        $rQuantity = cat_BomDetails::calcExpr($expr, $params);
        if ($rQuantity === self::CALC_ERROR) {
            $style = 'color:red;';
        }
        
        // Намира контекста и го оцветява
        $context = array();
        if (is_array($params)) {
            foreach ($params as $var => $val) {
                if ($val !== self::CALC_ERROR && $var != '$T') {
                    $Double = cls::get('type_Double', array('params' => array('smartRound' => true)));
                    $context[$var] = "<span style='color:blue' title='{$Double->toVerbal($val)}'>{$var}</span>";
                } else {
                    $context[$var] = "<span title='{$val}'>{$var}</span>";
                }
            }
        }
        
        $expr = strtr($expr, $context);
        if (!is_numeric($expr)) {
            $expr = "<span style='{$style}'>{$expr}</span>";
        }
        $expr = preg_replace('/\$Начално\s*=\s*/iu', "<span style='color:blue'>" . tr('Начално') . '</span>=', $expr);
        
        if (isset($coefficient) && $coefficient != 1) {
            $expr = "( {$expr} ) / <span style='color:darkgreen' title='" . tr('Количеството от оригиналната рецепта') . "'>{$coefficient}</span>";
        }
        
        if ($rQuantity === self::CALC_ERROR) {
            $expr = ht::createHint($expr, 'Формулата не може да бъде изчислена', 'warning');
        }
        
        return $expr;
    }
    
    
    /**
     * Търси в дърво, дали даден обект не е баща на някой от бащите на друг обект
     *
     * @param int   $objectId   - ид на текущия обект
     * @param int   $needle     - ид на обекта който търсим
     * @param array $notAllowed - списък със забранените обекти
     * @param array $path
     *
     * @return void
     */
    public function findNotAllowedProducts($objectId, $needle, &$notAllowed, $path = array())
    {
        // Добавяме текущия продукт
        $path[$objectId] = $objectId;
        
        // Ако стигнем до началния, прекратяваме рекурсията
        if ($objectId == $needle) {
            foreach ($path as $p) {
                
                // За всеки продукт в пътя до намерения ние го
                // добавяме в масива notAllowed, ако той, вече не е там
                $notAllowed[$p] = $p;
            }
            
            return;
        }
        
        // Имали артикула рецепта
        if ($bomId = cat_Products::getLastActiveBom($objectId)) {
            $bomInfo = cat_Boms::getResourceInfo($bomId, 1, dt::now());
            
            // За всеки продукт от нея проверяваме дали не съдържа търсения продукт
            if (countR($bomInfo['resources'])) {
                foreach ($bomInfo['resources'] as $res) {
                    $this->findNotAllowedProducts($res->productId, $needle, $notAllowed, $path);
                }
            }
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param cat_BomDetails $mvc
     * @param core_Form      $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;
        $masterProductId = cat_Boms::fetchField($rec->bomId, 'productId');
        
        // Ако има избран ресурс, добавяме му мярката до полетата за количества
        if (isset($rec->resourceId)) {
            $params = cat_Boms::getProductParams($masterProductId);

            $path = $mvc->getProductPath($rec);
            foreach ($path as $pId) {
                $newParams = cat_Boms::getProductParams($pId);
                cat_Boms::pushParams($params, $newParams);
            }
            
            // Добавя допустимите параметри във формулата
            $scope = cat_Boms::getScope($params);
            $scope['$T'] = 1;
            $scope['$Начално='] = '$Начално=';
            
            $rec->params = $scope;
            $context = cat_Params::formulaMapToSuggestions($scope);
            unset($context['$T']);
            $form->setSuggestions('propQuantity', $context);
            $pInfo = cat_Products::getProductInfo($rec->resourceId);
            
            if($form->_replaceProduct !== true){
                $packs = cat_Products::getPacks($rec->resourceId);
                $form->setOptions('packagingId', $packs);
                $form->setDefault('packagingId', key($packs));
            } else {
                $form->rec->packagingId = $pInfo->productRec->measureId;
            }
            
            // Ако артикула не е складируем, скриваме полето за мярка
            if (!isset($pInfo->meta['canStore'])) {
                $measureShort = cat_UoM::getShortName($rec->packagingId);
                $form->setField('propQuantity', "unit={$measureShort}");
            } elseif($form->_replaceProduct !== true) {
                $form->setField('packagingId', 'input');
            }
        }
        
        // Проверяваме дали е въведено поне едно количество
        if ($form->isSubmitted()) {
            $calced = static::calcExpr($rec->propQuantity, $rec->params);
            if ($calced == static::CALC_ERROR) {
                if($form->_replaceProduct === true){
                    $form->setWarning('resourceId', 'При замяна на артикула, формулата за количествата му няма да може да се изчисли');
                } else {
                    $form->setWarning('propQuantity', 'Има проблем при изчисляването на количеството');
                }
            } elseif ($calced <= 0) {
                if($form->_replaceProduct = true){
                    $form->setError('propQuantity', 'При замяна на артикула, формулата за количествата му не може да изчисли положително число');
                } else {
                    $form->setError('propQuantity', 'Изчисленото количество трябва да е положително');
                }
            } else {
                $warning = null;
                if(!deals_Helper::checkQuantity($rec->packagingId, $calced, $warning)){
                    $form->setWarning('propQuantity', $warning);
                }
            }
            
            if (isset($rec->resourceId)) {
                
                // Ако е избран артикул проверяваме дали артикула от рецептата не се съдържа в него
                $productVerbal = cat_Products::getTitleById($masterProductId);
                
                $notAllowed = array();
                $mvc->findNotAllowedProducts($rec->resourceId, $masterProductId, $notAllowed);
                if (isset($notAllowed[$rec->resourceId])) {
                    $form->setError('resourceId', "Артикулът не може да бъде избран, защото в рецептата на някой от материалите му се съдържа|* <b>{$productVerbal}</b>");
                }
            }
            
            // Ако добавяме отпадък, искаме да има себестойност
            if ($rec->type == 'pop') {
                $selfValue = price_ListRules::getPrice(price_ListRules::PRICE_LIST_COST, $rec->resourceId);
                if (!isset($selfValue)) {
                    $form->setWarning('resourceId', 'Отпадъкът няма себестойност');
                }
            } else {
                
                // Материалът може да се използва само веднъж в дадения етап
                $cond = "#bomId = {$rec->bomId} AND #id != '{$rec->id}' AND #resourceId = {$rec->resourceId}";
                $cond .= (empty($rec->parentId)) ? ' AND #parentId IS NULL' : " AND #parentId = '{$rec->parentId}'";
                if (self::fetchField($cond)) {
                    $form->setError('resourceId,parentId', 'Артикулът вече се използва в този етап');
                }
            }
            
            $rec->quantityInPack = ($pInfo->packagings[$rec->packagingId]) ? $pInfo->packagings[$rec->packagingId]->quantity : 1;
            
            // Ако има артикул със същата позиция, или няма позиция добавяме нова
            if (!isset($rec->position)) {
                $rec->position = $mvc->getDefaultPosition($rec->bomId, $rec->parentId);
            }
            
            if ($rec->type == 'stage') {
                if ($mvc->fetchField("#bomId = {$rec->bomId} AND #type = 'stage' AND #resourceId = '{$rec->resourceId}' AND #id != '{$rec->id}'")) {
                    $form->setError('resourceId', 'Един етап може да се среща само веднъж в рецептата');
                }
            }
            
            if (!$form->gotErrors()) {
                
                // Пътя към този артикул
                $thisPath = $mvc->getProductPath($rec);
                unset($thisPath[0]);
                
                $canAdd = true;
                if (isset($rec->parentId)) {
                    
                    // Ако добавяме етап
                    if ($rec->type == 'stage') {
                        $bom = cat_Products::getLastActiveBom($rec->resourceId);
                        if (!empty($bom)) {
                            
                            // и има детайли
                            $detailsToAdd = self::getOrderedBomDetails($bom->id);
                            if (is_array($detailsToAdd)) {
                                
                                // Ако някой от артикулите в пътя който сме се повтаря в пътя на детайла
                                // който ще наливаме забраняваме да се добавя артикула
                                foreach ($detailsToAdd as $det) {
                                    $path = $mvc->getProductPath($det);
                                    
                                    $intersected = array_intersect($thisPath, $path);
                                    if (countR($intersected)) {
                                        $canAdd = false;
                                        break;
                                    }
                                }
                                
                                if (in_array($rec->resourceId, $path)) {
                                    $canAdd = false;
                                }
                            }
                        }
                    }
                    
                    // Ако артикула не може да се избере сетваме грешка
                    if ($canAdd === false) {
                        $form->setError('parentId,resourceId', 'Артикулът не може да се повтаря в нивото');
                    }
                }
            }
        }
    }
    
    
    /**
     * Връща масив с пътя на един запис
     *
     * @param stdClass $rec      - запис
     * @param string   $position - дали да върнем позициите или ид-та на артикули
     *
     * @return array - масив с последователноста на пътя на записа в позиции или ид-та на артикули
     */
    private function getProductPath($rec, $position = false)
    {
        $path = array();
        $path[] = ($position) ? $rec->position : $rec->resourceId;
        
        $parent = $rec->parentId;
        while ($parent && ($pRec = $this->fetch($parent, 'parentId,position,resourceId'))) {
            $path[] = ($position) ? $pRec->position : $pRec->resourceId;
            $parent = $pRec->parentId;
        }
        
        $path = array_reverse($path, true);
        
        return $path;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        // Показваме подробната информация за опаковката при нужда
        deals_Helper::getPackInfo($row->packagingId, $rec->resourceId, $rec->packagingId, $rec->quantityInPack);
        $row->resourceId = cat_Products::getAutoProductDesc($rec->resourceId, null, 'short', 'internal');
        if(isset($fields['bomId'])){
            $row->bomId = cat_Boms::getHyperlink($rec->bomId, true);
        }

        if ($rec->type == 'stage') {
            $row->ROW_ATTR['style'] = 'background-color:#EFEFEF';
            $row->ROW_ATTR['title'] = tr('Етап');
        } else {
            $row->ROW_ATTR['class'] = ($rec->type != 'input') ? 'row-removed' : 'row-added';
        }
        
        if (!Mode::is('text', 'xhtml') && !Mode::is('printing')) {
            $extraBtnTpl = new core_ET("<!--ET_BEGIN BTN--><span style='float:right'>[#BTN#]</span><!--ET_END BTN-->");
            
            // Може ли да се разпъне реда
            if ($mvc->haveRightFor('expand', $rec)) {
                $link = ht::createLink('', array($mvc, 'expand', $rec->id, 'ret_url' => true), false, 'ef_icon=img/16/toggle1.png,title=Направи етап');
                $extraBtnTpl->append($link, 'BTN');
            }
            
            // Може ли да се свие етапа
            if ($mvc->haveRightFor('shrink', $rec)) {
                $link = ht::createLink('', array($mvc, 'shrink', $rec->id, 'ret_url' => true), false, 'ef_icon=img/16/toggle2.png,title=Свиване на етап');
                $extraBtnTpl->append($link, 'BTN');
            }
            
            $row->resourceId .= $extraBtnTpl;
        }
        
        // Генерираме кода според позицията на артикула и етапите
        $codePath = $mvc->getProductPath($rec, true);
        $position = implode('.', $codePath);
        $position = cls::get('type_Varchar')->toVerbal($position);
        $row->position = $position;

        $descriptionArr = array();
        if ($rec->type == 'stage') {
            if(!empty($rec->centerId)){
                $descriptionArr[] = tr("|*<tr><td>|Център на дейност|*:</td><td>") . planning_Centers::getHyperlink($rec->centerId, true) . "</td></tr>";
            }
            if(!empty($rec->storeIn)){
                $descriptionArr[] = tr("|*<tr><td>|Произвеждане В|*:</td><td>") . store_Stores::getHyperlink($rec->storeIn, true) . "</td></tr>";
            }
            if(!empty($rec->inputStores)){
                $descriptionArr[] = tr("|*<tr><td>|Материали ОТ|*:</td><td>") . $mvc->getFieldType('inputStores')->toVerbal($rec->inputStores) . "</td></tr>";
            }
            if(!empty($rec->fixedAssets)){
                $descriptionArr[] = tr("|*<tr><td>|Оборудване|*:</td><td>") . $mvc->getFieldType('fixedAssets')->toVerbal($rec->fixedAssets) . "<td></tr>";
            }
            if(!empty($rec->employees)){
                $descriptionArr[] = tr("|*<tr><td>|Оператори|*:</td><td>") . implode(', ', planning_Hr::getPersonsCodesArr($rec->employees, true)) . "</td></tr>";
            }
            if(!empty($rec->norm)){
                $descriptionArr[] = tr("|*<tr><td>|Норма|*:</td><td>") . $mvc->getFieldType('norm')->toVerbal($rec->norm) . "</td></tr>";
            }

            if(!empty($rec->labelPackagingId)){
                $descriptionArr[] = tr("|*<tr><td>|Опаковка (Етикет)|*:</td><td>") . $mvc->getFieldType('labelPackagingId')->toVerbal($rec->labelPackagingId) . "</td></tr>";

                if(empty($rec->labelQuantityInPack)){
                    $packRec = cat_products_Packagings::getPack($rec->resourceId, $rec->labelPackagingId);
                    $quantityInPackDefault = is_object($packRec) ? $packRec->quantity : 1;
                    $quantityInPackDefault = "<span style='color:blue'>" . core_Type::getByName('double(smartRound)')->toVerbal($quantityInPackDefault) . "</span>";
                    $quantityInPackDefault = ht::createHint($quantityInPackDefault, 'От опаковката/мярката на артикула');
                    $labelQuantityInPack = $quantityInPackDefault;
                } else {
                    $labelQuantityInPack = core_Type::getByName('double(smartRound)')->toVerbal($rec->labelQuantityInPack);
                }

                $descriptionArr[] = tr("|*<tr><td>|В опаковка (Етикет)|*:</td><td>") . $labelQuantityInPack . "</td></tr>";
            }

            if(!empty($rec->labelType)){
                $descriptionArr[] = tr("|*<tr><td>|Производ. №|*:</td><td>") . $mvc->getFieldType('labelType')->toVerbal($rec->labelType) . "</td></tr>";
            }

            if(!empty($rec->labelTemplate)){
                $descriptionArr[] = tr("|*<tr><td>|Шаблон|*:</td><td>") . label_Templates::getHyperlink($rec->labelTemplate, true) . "</td></tr>";
            }
        }

        if (!empty($rec->description)) {
            $descriptionArr[] = "<tr><td colspan='2'>" . $mvc->getFieldType('description')->toVerbal($rec->description) . "</td>";
        }

        if(countR($descriptionArr)){
            $description = implode("", $descriptionArr);
            $row->resourceId .= "<div class='small'><table class='bomProductionStepTable'>{$description}</table></div>";
        }

        if($rec->type == 'stage'){
            $rec->state = cat_Boms::fetchField($rec->bomId, 'state');
            $paramData = cat_products_Params::prepareClassObjectParams($mvc, $rec);
            if (isset($paramData)) {
                $paramTpl = cat_products_Params::renderParams($paramData);
                $row->resourceId .= "<div class='small'>" . $paramTpl->getContent() . "</div>";
            }
        }

        $propQuantity = $rec->propQuantity;
        $coefficient = null;
        
        if (isset($rec->parentId)) {
            $coefficient = $mvc->fetchField($rec->parentId, 'coefficient');
            
            if (isset($coefficient)) {
                $rec->propQuantity = "({$rec->propQuantity}) / ${coefficient}";
            }
        }
        
        $rec->rowQuantity = cat_BomDetails::calcExpr($rec->propQuantity, $rec->params);
        
        $highlightedExpr = static::highlightExpr($propQuantity, $rec->params, $coefficient);
        $row->propQuantity = $highlightedExpr;
        
        if ($rec->rowQuantity == static::CALC_ERROR) {
            $row->rowQuantity = "<span class='red'>???</span>";
            $row->primeCost = "<span class='red'>???</span>";
            $row->primeCost = ht::createHint($row->primeCost, 'Не може да бъде изчислена себестойността', 'warning', false);
        } else {
            $row->rowQuantity = cls::get('type_Double', array('params' => array('decimals' => 2)))->toVerbal($rec->rowQuantity);
        }
        
        if (!isset($rec->primeCost) && $rec->type != 'stage') {
            $row->primeCost = "<span class='red'>???</span>";
            $row->primeCost = ht::createHint($row->primeCost, 'Сумата не може да бъде изчислена', 'warning', false);
        }
        
        $compare = str_replace(',', '.', $rec->propQuantity);
        if (is_numeric($compare)) {
            $row->propQuantity = "<span style='float:right'>{$row->propQuantity}</span>";
        }
        
        if ($rec->type == 'pop') {
            $row->resourceId = ht::createHint($row->resourceId, 'Артикулът е отпадък', 'notice', true, array('src' => 'img/16/recycle.png'));
        }
    }
    
    
    /**
     * Екшън за разпъване на материал като етап с подетапи
     */
    public function act_Expand()
    {
        $this->requireRightFor('expand');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('expand', $rec);
        
        $rec->type = 'stage';
        $rec->primeCost = null;
        
        // Проверка може ли артикулът да бъде разпънат като етап
        $masterRec = cat_Boms::fetch($rec->bomId);
        $notAllowed = array();
        $this->findNotAllowedProducts($rec->resourceId, $masterRec->productId, $notAllowed);
        if (isset($notAllowed[$rec->resourceId])) {
            $productVerbal = cat_Products::getTitleById($masterRec->productId);
            
            return followRetUrl(null, "Артикулът не може да бъде , защото в рецептата на някой от материалите му се съдържа|* <b>{$productVerbal}</b>", 'error');
        }
        
        $bomRec = null;
        cat_BomDetails::addProductComponents($rec->resourceId, $rec->bomId, $rec->id, $bomRec);
        
        if (isset($bomRec)) {
            $rec->coefficient = $bomRec->quantity;
        }
        $this->save($rec, 'type,primeCost,coefficient');
        
        $title = cat_Products::getTitleById($rec->resourceId);
        $msg = "{$title} |вече е етап|*";
        $this->Master->logWrite('Разпъване на вложен артикул', $rec->bomId);
        
        return new Redirect(array('cat_Boms', 'single', $rec->bomId), $msg);
    }
    
    
    /**
     * Екшън за разпъване на материал като етап с подетапи
     */
    public function act_Shrink()
    {
        $this->requireRightFor('shrink');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('shrink', $rec);

        // От етап се свива на обикновен артикул
        $rec->type = 'input';
        $this->delete("#bomId = {$rec->bomId} AND #parentId = {$rec->id}");
        cat_products_Params::delete("#classId = {$this->getClassId()} AND #productId = {$rec->id}");
        $rec->coefficient = null;
        $rec->centerId = $rec->inputStores = $rec->storeIn = $rec->fixedAssets = $rec->employees = $rec->norm = null;
        $this->save($rec);
        
        $title = cat_Products::getTitleById($rec->resourceId);
        $msg = "|Свиване на|* {$title}";
        $this->Master->logRead('Свиване на етап', $rec->bomId);
        
        return new Redirect(array('cat_Boms', 'single', $rec->bomId), $msg);
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        $data->toolbar->removeBtn('btnAdd');
        if ($mvc->haveRightFor('add', (object) array('bomId' => $data->masterId))) {
            $data->toolbar->addBtn('Влагане', array($mvc, 'add', 'bomId' => $data->masterId, 'ret_url' => true, 'type' => 'input'), null, 'title=Добавяне на артикул за влагане,ef_icon=img/16/package.png');
        }
        
        if ($mvc->haveRightFor('add', (object) array('bomId' => $data->masterId, 'type' => 'stage'))) {
            $data->toolbar->addBtn('Етап', array($mvc, 'add', 'bomId' => $data->masterId, 'ret_url' => true, 'type' => 'stage'), null, 'title=Добавяне на етап,ef_icon=img/16/paste_plain.png');
        }
        
        if ($mvc->haveRightFor('add', (object) array('bomId' => $data->masterId, 'type' => 'pop'))) {
            $data->toolbar->addBtn('Отпадък', array($mvc, 'add', 'bomId' => $data->masterId, 'ret_url' => true, 'type' => 'pop'), null, 'title=Добавяне на отпадък,ef_icon=img/16/recycle.png');
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'edit' || $action == 'delete' || $action == 'add' || $action == 'expand' || $action == 'shrink') && isset($rec)) {
            if(isset($rec->bomId)){
                $masterRec = cat_Boms::fetch($rec->bomId, 'state,originId');
                if ($masterRec->state != 'draft') {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        // Може ли записа да бъде разширен
        if (($action == 'expand' || $action == 'shrink') && isset($rec)) {
            
            // Артикула трябва да е производим и да има активна рецепта
            $canManifacture = cat_Products::fetchField($rec->resourceId, 'canManifacture');
            if ($canManifacture != 'yes') {
                $requiredRoles = 'no_one';
            } else {
                $type = cat_Boms::fetchField($rec->bomId, 'type');
                if ($type == 'production') {
                    $aBom = cat_Products::getLastActiveBom($rec->resourceId, 'production');
                }
                if (!$aBom) {
                    $aBom = cat_Products::getLastActiveBom($rec->resourceId, 'sales');
                }
               
               if (!$aBom) {
                    $requiredRoles = 'no_one';
               }
            }
        }
        
        if ($action == 'expand' && isset($rec)) {
            // Само материал може да се разпъва
            if ($rec->type != 'input') {
                $requiredRoles = 'no_one';
            }
        }
        
        if ($action == 'shrink' && isset($rec)) {
            
            // Само етап може да се свива
            if ($rec->type != 'stage') {
                $requiredRoles = 'no_one';
            }
            
            
            if ($requiredRoles != 'no_one') {
                if (!$mvc->checkComponents($rec)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        // Етап не може да се замества
        if ($action == 'replaceproduct' && isset($rec)) {
            if ($rec->type == 'stage') {
                $requiredRoles = 'no_one';
            }
        }
        
        if ($action == 'add' && isset($rec->type)) {
            if($rec->type == 'stage'){
                $options = cat_Products::getProducts(null, null, null, 'canConvert', null, 1, false, null, null, null, null, null, true);
                if(!countR($options)){
                    $requiredRoles = 'no_one';
                }
            } elseif($rec->type == 'pop'){
                $options = cat_Products::getProducts(null, null, null, 'canConvert,canStore', null, 1, false, cat_Groups::getKeylistBySysIds('waste'));
                if(!countR($options)){
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Помощна ф-я връщаща масив със всички записи, които са наследници на даден запис
     */
    private function getDescendents($id, &$res = array())
    {
        $query = $this->getQuery();
        $query->where("#parentId = {$id}");
        $query->show('resourceId,propQuantity,packagingId,quantityInPack');
        $query->orderBy('resourceId', 'ASC');
        
        while ($rec = $query->fetch()) {
            $obj = new stdClass();
            $obj->resourceId = $rec->resourceId;
            $obj->packagingId = $rec->packagingId;
            $obj->propQuantity = trim($rec->propQuantity);
            $res[$rec->resourceId . '|' . $rec->packagingId] = $obj;
            
            if ($rec->type != 'stage') {
                self::getComponents($rec->resourceId, $res);
            }
            $this->getDescendents($rec->id, $res);
        }
        
        return $res;
    }
    
    
    /**
     * Намира компонентите на един артикул
     */
    private function getComponents($productId, &$res = array())
    {
        // Имали последна активна търговска рецепта за артикула?
        $rec = cat_Products::getLastActiveBom($productId, 'sales');
        if (!$rec) {
            
            return $res;
        }
        
        // Кои детайли от нея ще показваме като компоненти
        $details = cat_BomDetails::getOrderedBomDetails($rec->id);
        
        // За всеки
        if (is_array($details)) {
            foreach ($details as $dRec) {
                $obj = new stdClass();
                $obj->resourceId = $dRec->resourceId;
                $obj->packagingId = $dRec->packagingId;
                $obj->propQuantity = trim($dRec->propQuantity);
                $res[$dRec->resourceId . '|' . $dRec->packagingId] = $obj;
                
                if ($dRec->type != 'stage') {
                    self::getComponents($dRec->resourceId, $res);
                }
            }
        }
    }
    
    
    /**
     * Проверява дали подетапите на един етап отговарят точно
     * на рецептата му
     */
    private function checkComponents($rec)
    {
        $children = $bomDetails = array();
        $this->getDescendents($rec->id, $children);
        $this->getComponents($rec->resourceId, $bomDetails);
        ksort($children);
        ksort($bomDetails);
        
        $areSame = true;
        foreach ($children as $index => $obj) {
            $other = $bomDetails[$index];
            if ($obj->propQuantity != $other->propQuantity || $obj->resourceId != $other->resourceId || $obj->packagingId != $other->packagingId) {
                $areSame = false;
                break;
            }
        }
        
        return $areSame;
    }
    
    
    /**
     * След извличане на записите от базата данни
     */
    protected static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
    {
        if (!countR($data->recs)) {
            
            return;
        }

        // Подреждаме детайлите
        $outArr = array();
        self::orderBomDetails($data->recs, $outArr);
        $data->recs = $outArr;
    }


    /**
     * Ако няма записи не вади таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
        $hasSameQuantities = true;
        if (is_array($data->rows)) {

            // Колко е най-голямото закръгляне на използваните мерки
            $usedMeasures = arr::extractValuesFromArray($data->recs, 'packagingId');
            $maxDecimals = cat_UoM::getMaxRound($usedMeasures);

            $Double = core_Type::getByName("double(decimals={$maxDecimals})");
            foreach ($data->rows as $id => &$row) {
                $rec = $data->recs[$id];
                if ($rec->parentId) {
                    if ($rec->rowQuantity != cat_BomDetails::CALC_ERROR) {
                        if ($data->recs[$rec->parentId]->rowQuantity != cat_BomDetails::CALC_ERROR) {
                            $rec->rowQuantity *= $data->recs[$rec->parentId]->rowQuantity;
                        }
                    }
                }

                if ($rec->rowQuantity != $rec->propQuantity) {
                    $hasSameQuantities = false;
                }

                $row->rowQuantity = $Double->toVerbal($rec->rowQuantity);
            }
        }

        // Ако формулите и изчислените к-ва са равни, показваме само едната колонка
        if ($hasSameQuantities === true) {
            unset($data->listFields['propQuantity']);
        }

        unset($data->listFields['coefficient']);
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param core_Manager $mvc
     * @param stdClass     $res
     * @param stdClass     $rec
     */
    protected static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
        // Ако сме добавили нов етап
        if (empty($rec->id) && $rec->type == 'stage') {
            $rec->stageAdded = true;
        }
    }
    
    
    /**
     * Намира следващия най-голяма позиция за нивото
     *
     * @param int $bomId
     * @param int $parentId
     *
     * @return int
     */
    private function getDefaultPosition($bomId, $parentId)
    {
        $query = $this->getQuery();
        $cond = "#bomId = {$bomId} AND ";
        $cond .= (isset($parentId)) ? "#parentId = {$parentId}" : '#parentId IS NULL';
        $query->where($cond);
        $query->XPR('maxPosition', 'int', 'MAX(#position)');
        $position = $query->fetch()->maxPosition;
        ++$position;
        
        return $position;
    }
    
    
    /**
     * Клонира детайлите на рецептата
     *
     * @param int $fromBomId
     * @param int $toBomId
     *
     * @return void
     */
    public function cloneDetails($fromBomId, $toBomId)
    {
        $fromBomRec = cat_Boms::fetchRec($fromBomId);
        if($fromBomRec->state == 'template'){
            $this->cloneDetailsFromBomId($fromBomId, $toBomId);
        } else {
            cat_BomDetails::addProductComponents($fromBomRec->productId, $toBomId, null);
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        // Ако има позиция, шифтваме всички с по-голяма или равна позиция напред
        if (isset($rec->position)) {
            $query = $mvc->getQuery();
            $cond = "#bomId = {$rec->bomId} AND #id != {$rec->id} AND #position >= {$rec->position} AND ";
            $cond .= (isset($rec->parentId)) ? "#parentId = {$rec->parentId}" : '#parentId IS NULL';
            
            $query->where($cond);
            while ($nRec = $query->fetch()) {
                $nRec->position++;
                $mvc->save_($nRec, 'position');
            }
        }
        
        // Ако сме добавили нов етап
        if ($rec->stageAdded === true) {
            $bomRec = null;
            static::addProductComponents($rec->resourceId, $rec->bomId, $rec->id, $bomRec);
            if ($bomRec) {
                $rec->coefficient = $bomRec->quantity;
                $mvc->save_($rec, 'coefficient');
            }
        }

        if(!empty($rec->_params)){
            cat_products_Params::saveParams($mvc, $rec);
        }
    }
    
    
    /**
     * Връща подредените детайли на рецептата
     *
     * @param int $id - ид
     *
     * @return array - подредените записи
     */
    public static function getOrderedBomDetails($id)
    {
        // Извличаме и детайлите
        $dQuery = self::getQuery();
        $dQuery->where("#bomId = '{$id}'");
        $dRecs = $dQuery->fetchAll();
        
        // Подреждаме ги
        $outArr = array();
        self::orderBomDetails($dRecs, $outArr);
        
        return $outArr;
    }
    
    
    /**
     * Добавя компонентите на един етап към рецепта
     *
     * @param int $productId   - ид на артикул
     * @param int $toBomId     - ид на рецепта към която го добавяме
     * @param int $componentId - на кой ред в рецептата е артикула
     *
     * @return void
     */
    public static function addProductComponents($productId, $toBomId, $componentId, &$activeBom = null, $onlyIfQuantitiesAreEqual = false)
    {
        $me = cls::get(get_called_class());
        $toBomRec = cat_Boms::fetch($toBomId);
        
        if ($toBomRec->type == 'production') {
            $activeBom = cat_Products::getLastActiveBom($productId, 'production,instant,sales');
        } elseif($toBomRec->type == 'instant'){
            $activeBom = cat_Products::getLastActiveBom($productId, 'instant,sales');
        } else {
            $activeBom = cat_Products::getLastActiveBom($productId, 'sales');
        }

        // Ако етапа има рецепта
        if ($activeBom) {
            if ($onlyIfQuantitiesAreEqual === true) {
                if ($activeBom->quantity != $toBomRec->quantity) return;
            }

            $me->cloneDetailsFromBomId($activeBom, $toBomRec, $componentId);
        }
    }


    /**
     * Помощна функция
     */
    private function cloneDetailsFromBomId($fromRec, $toRec, $componentId = null)
    {
        $fromRec = cat_Boms::fetchRec($fromRec);
        $toRec = cat_Boms::fetchRec($toRec);

        $outArr = static::getOrderedBomDetails($fromRec->id);
        $cu = core_Users::getCurrent();

        // Копираме всеки запис
        $map = array();
        if (is_array($outArr)) {
            foreach ($outArr as $dRec) {
                $oldId = $dRec->id;

                unset($dRec->id);
                $dRec->modidiedOn = dt::now();
                $dRec->modifiedBy = $cu;
                $dRec->bomId = $toRec->id;
                if (empty($dRec->parentId)) {
                    $dRec->parentId = $componentId;
                } else {
                    $dRec->parentId = $map[$dRec->parentId];
                }

                // Добавяме записа
                $this->save_($dRec);
                $map[$oldId] = $dRec->id;
            }
        }
    }


    /**
     * Подрежда записите от детайла на рецептата по етапи
     *
     * @param array $inArr    - масив от записи
     * @param array $outArr   - подредения масив
     * @param int   $parentId - кой е текущия баща
     *
     * @return void
     */
    private static function orderBomDetails(&$inArr, &$outArr, $parentId = null)
    {
        // Временен масив
        $tmpArr = array();
        
        // Оставяме само тези записи с баща посочения етап
        if (is_array($inArr)) {
            foreach ($inArr as $rec) {
                if ($rec->parentId == $parentId) {
                    $tmpArr[$rec->id] = $rec;
                }
            }
        }
        
        // Сортираме ги по позицията им, ако е еднаква, сортираме по датата на последната модификация
        usort($tmpArr, function ($a, $b) {
            if ($a->position == $b->position) {
                
                return ($a->modifiedOn > $b->modifiedOn) ? -1 : 1;
            }
            
            return ($a->position < $b->position) ? -1 : 1;
        });
        
        // За всеки от тях
        $cnt = 1;
        foreach ($tmpArr as &$tRec) {
            
            // Ако позицията му е различна от текущата опресняваме я
            // така се подсигуряваме че позициите са последователни числа
            if ($tRec->position != $cnt) {
                $tRec->position = $cnt;
                cls::get(get_called_class())->save_($tRec);
            }
            
            // Добавяме реда в изходящия масив
            $outArr[$tRec->id] = $tRec;
            $cnt++;
            
            // Ако реда е етап, викаме рекурсивно като филтрираме само записите с етап ид-то на етапа
            if ($tRec->type == 'stage') {
                self::orderBomDetails($inArr, $outArr, $tRec->id);
            }
        }
    }
    
    
    /**
     * След изтриване на запис
     */
    public static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
        // Ако изтриваме етап, изтриваме всичките редове от този етап, както и добавените параметри към него
        foreach ($query->getDeletedRecs() as $rec) {
            if ($rec->type == 'stage') {
                $mvc->delete("#bomId = {$rec->bomId} AND #parentId = {$rec->id}");
                cat_products_Params::delete("#classId = {$mvc->getClassId()} AND #productId = {$rec->id}");
            }
        }
    }


    /**
     * Подготовка на бутоните на формата за добавяне/редактиране
     */
    protected static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        $rec = $data->form->rec;
        if ($rec->type == 'stage' && !isset($rec->id)) {

            $addStepUrl = cls::get('planning_Steps')->getListAddUrl();
            if(countR($addStepUrl)){
                $addStepUrl['ret_url'] = getCurrentUrl();
                $data->form->toolbar->addBtn('Нов етап', $addStepUrl, 'id=btnReq,order=9.99971', 'ef_icon = img/16/add.png,title=Създаване на артикул за нов етап в производството');
            }
        }
    }
}
