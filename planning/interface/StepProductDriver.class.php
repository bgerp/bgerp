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
     *          int|null    ['centerId']             - ид на център на дейност
     *          int|null    ['storeIn']              - ид на склад за засклаждане (ако е складируем)
     *          int|null    ['inputStores']          - ид на складове за влагане (ако е складируем)
     *          array|null  ['fixedAssets']          - масив от ид-та на оборудвания (@see planning_AssetResources)
     *          array|null  ['employees']            - масив от ид-та на оператори (@see planning_Hr)
     *          int|null    ['norm']                 - норма за производство
     *          int|null    ['labelPackagingId']     - ид на опаковка за етикет
     *          double|null ['labelQuantityInPack']  - к-во в опаковка за етикет
     *          string|null ['labelType']            - тип на етикета
     *          int|null    ['labelTemplate']        - шаблон за етикет
     *          array|null  ['planningParams']       - параметри за планиране
     *          string      ['isFinal']              - дали е финална
     *          string      ['showPreviousJobField'] - дали да се изисква предходно задание
     */
    public function getProductionData($productId)
    {
        $rec = cat_Products::fetch($productId);
        $res = array('centerId' => $rec->planning_Steps_centerId, 'storeIn' => $rec->planning_Steps_storeIn, 'inputStores' => $rec->planning_Steps_inputStores, 'norm' => $rec->planning_Steps_norm);
        $res['fixedAssets'] = !empty($rec->planning_Steps_fixedAssets) ? keylist::toArray($rec->planning_Steps_fixedAssets) : null;
        $res['employees'] = !empty($rec->planning_Steps_employees) ? keylist::toArray($rec->planning_Steps_employees) : null;
        $res['planningParams'] = !empty($rec->planning_Steps_planningParams) ? keylist::toArray($rec->planning_Steps_planningParams) : array();
        $res['isFinal'] = $rec->planning_Steps_isFinal;
        $res['showPreviousJobField'] = ($rec->planning_Steps_showPreviousJobField == 'yes');
        if($rec->canStore == 'yes'){
            $res['labelPackagingId'] = $rec->planning_Steps_labelPackagingId;
            $res['labelQuantityInPack'] = $rec->planning_Steps_labelQuantityInPack;
            $res['labelType'] = $rec->planning_Steps_labelType;
            $res['labelTemplate'] = $rec->planning_Steps_labelTemplate;
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
        $tpl->placeObject($data->row);

        if ($data->noChange !== true || countR($data->params)) {
            $paramTpl = cat_products_Params::renderParams($data);
            $tpl->append($paramTpl, 'PARAMS');
        }

        return $tpl;
    }
}