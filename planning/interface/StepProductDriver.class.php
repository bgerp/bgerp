<?php


/**
 * Драйвър за артикул - производствен етап
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Етап в производството
 */
class planning_interface_StepProductDriver extends cat_GeneralProductDriver
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'planning_interface_StageDriver';


    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, planningMaster';
    
    
    /**
     * Мета данни по подразбиране
     *
     * @param string $defaultMetaData
     */
    protected $defaultMetaData = 'canManifacture,canConvert,canStore';
    
    
    /**
     * Клас екстендър, който да се закача
     *
     * @param string
     */
    public $extenderClass = 'planning_Steps';
    
    
    /**
     * Икона на артикулите
     */
    protected $icon = 'img/16/paste_plain.png';
    
    
    /**
     * Връща дефолтната дефиниция за шаблон на партидна дефиниция
     *
     * @param mixed $id - ид или запис на артикул
     *
     * @return int - ид към batch_Templates
     */
    public function getDefaultBatchTemplate($id)
    {
        $rec = cat_Products::fetchRec($id);
        if($rec->planning_Steps_isFinal == 'yes') return null;

        $templateId = batch_Templates::fetchField("#createdBy = '-1' AND #state = 'active' AND #driverClass =" . batch_definitions_Job::getClassId());
    
        return !empty($templateId) ? $templateId : null;
    }
    
    
    /**
     * Връща цената за посочения продукт към посочения клиент на посочената дата
     *
     * @param mixed                                                                              $productId - ид на артикул
     * @param int                                                                                $quantity  - к-во
     * @param float                                                                              $minDelta  - минималната отстъпка
     * @param float                                                                              $maxDelta  - максималната надценка
     * @param datetime                                                                           $datetime  - дата
     * @param float                                                                              $rate      - валутен курс
     * @param string $chargeVat - начин на начисляване на ддс
     *
     * @return stdClass|float|NULL $price  - обект с цена и отстъпка, или само цена, или NULL ако няма
     */
    public function getPrice($productId, $quantity, $minDelta, $maxDelta, $datetime = null, $rate = 1, $chargeVat = 'no')
    {
        return 0;
    }
    
    
    /**
     * Подготвя групите, в които да бъде вкаран продукта
     */
    public static function on_BeforeSave($Driver, embed_Manager &$Embedder, &$id, &$rec, $fields = null)
    {
        if(empty($rec->id) && $Embedder instanceof cat_Products){
            $groupId = cat_Groups::fetchField("#sysId = 'prefabrications'");
            $rec->groupsInput = keylist::addKey($rec->groupsInput, $groupId);
            $rec->groups = keylist::fromArray($Embedder->expandInput(type_Keylist::toArray($rec->groupsInput)));
        }
    }


    /**
     * Пренасочва URL за връщане след запис към сингъл изгледа
     */
    public static function on_AfterPrepareRetUrl($Driver, embed_Manager &$Embedder, $res, $data)
    {
        // Ако се иска директно контиране редирект към екшъна за контиране
        if (isset($data->form) && $data->form->isSubmitted() && $data->form->rec->id) {
            $retUrl = getRetUrl();

            // Ако се създава от рецепта: да редиректне към нея с вече готовото ид
            if($retUrl['Ctr'] == 'cat_BomDetails' && $retUrl['type'] == 'stage'){
                if(cat_Products::haveDriver($data->form->rec->id, 'planning_interface_StepProductDriver')){
                    if($Driver = cat_Products::getDriver($data->form->rec->id)){
                        if ($Driver->canSelectDriver()) {
                            $retUrl['resourceId'] = $data->form->rec->id;
                            $data->retUrl = $retUrl;
                        }
                    }
                }
            }
        }
    }


    /**
     * Връща информация за данните за производството на артикула
     *
     * @param int $productId
     * @return array
     *          int|null    ['name']                 - наименование
     *          int|null    ['centerId']             - ид на център на дейност
     *          int|null    ['storeIn']              - ид на склад за засклаждане (ако е складируем)
     *          int|null    ['inputStores']          - ид на складове за влагане (ако е складируем)
     *          array|null  ['fixedAssets']          - масив от ид-та на оборудвания (@see planning_AssetResources)
     *          array|null  ['employees']            - масив от ид-та на оператори (@see planning_Hr)
     *          int|null    ['norm']                 - норма за производство
     *          int|null    ['normPackagingId']      - ид на опаковката/мярката на нормата
     *          int|null    ['labelPackagingId']     - ид на опаковка за етикет
     *          double|null ['labelQuantityInPack']  - к-во в опаковка за етикет
     *          string|null ['labelType']            - тип на етикета
     *          int|null    ['labelTemplate']        - шаблон за етикет
     *          array|null  ['planningParams']       - параметри за планиране
     *          array|null  ['actions']              - операции за планиране
     *          string      ['isFinal']              - дали е финална
     *          string      ['showPreviousJobField'] - дали да се изисква предходно задание
     *          string      ['wasteProductId']       - ид на отпадък
     *          string      ['wasteStart']           - начално количество отпадък
     *          string      ['wastePercent']         - процент отпадък
     *          string      ['calcWeightMode']       - изчисляване на тегло или не
     */
    public function getProductionData($productId)
    {
        $rec = planning_Steps::getRec('cat_Products', $productId);
        $measureId = cat_Products::fetchField($productId, 'measureId');
        $res = array('name' => $rec->name, 'centerId' => $rec->centerId, 'storeIn' => $rec->storeIn, 'inputStores' => $rec->inputStores, 'wasteProductId' => $rec->wasteProductId, 'wasteStart' => $rec->wasteStart, 'wastePercent' => $rec->wastePercent);
        if(!empty($rec->norm)){
            $res['norm'] = $rec->norm;
            $res['normPackagingId'] = $measureId;
        }

        $res['fixedAssets'] = !empty($rec->fixedAssets) ? keylist::toArray($rec->fixedAssets) : null;
        $res['employees'] = !empty($rec->employees) ? keylist::toArray($rec->employees) : null;
        $res['planningParams'] = !empty($rec->planningParams) ? keylist::toArray($rec->planningParams) : array();
        $res['actions'] = !empty($rec->planningActions) ? keylist::toArray($rec->planningActions) : array();
        $res['calcWeightMode'] = ($rec->calcWeightMode == 'auto') ? planning_Setup::get('TASK_WEIGHT_MODE') : $rec->calcWeightMode;

        $res['isFinal'] = $rec->isFinal;
        $res['showPreviousJobField'] = ($rec->showPreviousJobField == 'yes');
        if($rec->canStore == 'yes'){
            $res['labelPackagingId'] = $rec->labelPackagingId;
            $res['labelQuantityInPack'] = $rec->labelQuantityInPack;
            $res['labelType'] = $rec->labelType;
            $res['labelTemplate'] = $rec->labelTemplate;
        }

        return $res;
    }


    /**
     * Какви са детайлите на драйвера
     *
     * @param stdClass $rec
     * @param embed_Manager $Embedder
     *
     * @return array()
     */
    public function getDetails($rec, embed_Manager $Embedder)
    {
        return array('planning_StepConditions' => 'planning_StepConditions');
    }

    /**
     * Рендира данните за показване на артикула
     *
     * @param stdClass $data
     *
     * @return core_ET
     */
    public function renderProductDescription($data)
    {
        $tpl = getTplFromFile('planning/tpl/StepBlock.shtml');
        $info = (core_Lg::getCurrent() == 'en') ? (!empty($data->rec->infoInt) ? $data->rec->infoInt : $data->rec->info) : $data->rec->info;
        if (!empty($info)) {
            $data->row->info = core_Type::getByName('richtext')->toVerbal($info);
        }

        $data->rec->photo =  cat_Products::getParams($data->rec->id, 'preview');
        if ($data->rec->photo) {
            $size = array(280, 150);
            $Fancybox = cls::get('fancybox_Fancybox');
            $data->row->image = $Fancybox->getImage($data->rec->photo, $size, array(550, 550));
        }

        $tpl->placeObject($data->row);

        if ($data->noChange !== true || countR($data->params)) {
            $paramTpl = cat_products_Params::renderParams($data);
            $tpl->append($paramTpl, 'PARAMS');
        }

        return $tpl;
    }


    /**
     * Извиква се преди изпълняването на екшън
     *
     * @param cat_ProductDriver $Driver
     * @param cat_Products $Embedder
     * @param mixed $res
     * @param string $action
     */
    public static function on_BeforeAction(cat_ProductDriver $Driver, cat_Products $Embedder, &$res, $action)
    {
        if($action == 'editplanned'){
            $Embedder->requireRightFor('editplanned');
            expect($id = Request::get('id', 'int'));
            expect($rec = $Embedder->fetch($id));
            $Embedder->requireRightFor('editplanned', $rec);

            // Подготовка на формата
            $form = cls::get('core_Form');
            $form->title = "Промяна на планиращи параметри на|* " . $Embedder->getFormTitleLink($rec->id);
            $form->info = tr("Оборудване|*: ") . $Embedder->recToVerbal($rec)->planning_Steps_fixedAssets;
            $form->FLD('planningActions', 'keylist(mvc=cat_Products, select=id)', 'caption=Действия');

            // Достъпните операции за избраните машини
            $actionOptions = array();
            $actionIds = keylist::toArray($rec->planning_Steps_planningActions);
            $fixedAssets = keylist::toArray($rec->planning_Steps_fixedAssets);
            foreach ($fixedAssets as $assetId){
                $actionIds += planning_AssetResourcesNorms::getNormOptions($assetId, array(), true);
            }
            foreach ($actionIds as $actionId){
                $actionOptions[$actionId] = cat_Products::getTitleById($actionId, false);
            }
            $form->setSuggestions('planningActions', $actionOptions);
            $form->setDefault('planningActions', $rec->planning_Steps_planningActions);

            $form->input();
            if($form->isSubmitted()){
                if($exRec = planning_Steps::getRec($Embedder->getClassId(), $rec->id)){
                    $exRec->planningActions = $form->rec->planningActions;
                    cls::get('planning_Steps')->save_($exRec, 'planningActions');
                    $Embedder->logWrite("Промяна на планиращите действия", $rec->id);
                }

                followRetUrl(null, 'Планиращите действия са променени успешно|*!');
            }

            $form->toolbar->addSbBtn('Промяна', 'save', 'ef_icon = img/16/arrow_refresh.png, title = Реконтиране');
            $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');

            $res = $Embedder->renderWrapping($form->renderHtml());

            return false;
        }
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles(cat_ProductDriver $Driver, cat_Products $Embedder, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'editplanned'){
            $requiredRoles = $Embedder->getRequiredRoles('edit', $rec, $userId);
            if(isset($rec) && empty($rec->planning_Steps_fixedAssets)){
                $requiredRoles = 'no_one';
            }
        }
    }
}