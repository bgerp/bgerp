<?php


/**
 * Драйвър за артикул - производствен етап
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Производствен етап
 */
class planning_interface_StageDriver extends cat_GeneralProductDriver
{
    
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
    public $extenderClass = 'planning_Stages';
    
    
    /**
     * Икона на артикулите
     */
    protected $icon = 'img/16/paste_plain.png';
    
    
    /**
     * Подготвяне на вербалните стойности
     *
     * @param cal_Progresses $Driver
     * @param doc_Comments   $mvc
     * @param stdClass       $row
     * @param stdClass       $rec
     */
    protected function on_AfterRecToVerbal(cat_ProductDriver $Driver, $mvc, $row, $rec)
    {
        unset($row->editMetaBtn);
    }
    
    
    /**
     * Връща дефолтната дефиниция за шаблон на партидна дефиниция
     *
     * @param mixed $id - ид или запис на артикул
     *
     * @return int - ид към batch_Templates
     */
    public function getDefaultBatchTemplate($id)
    {
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
}