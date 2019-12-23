<?php


/**
 * Помощен детайл подготвящ и обединяващ заедно търговските
 * детайли на фирмите и лицата
 *
 * @category  bgerp
 * @package   crm
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class crm_PersonsDetails extends core_Manager
{
    /**
     * Кой може да вижда личните индикатори на служителите
     */
    public $canSeeindicators = 'powerUser';
    
    
    /**
     * Подготвя ценовата информация за артикула
     */
    public function preparePersonsDetails($data)
    {
        $data->TabCaption = 'Лични данни';
        expect($data->masterMvc instanceof crm_Persons);
        
        $employeeId = crm_Groups::getIdFromSysId('employees');
        if (keylist::isIn($employeeId, $data->masterData->rec->groupList)) {
            $data->Codes = cls::get('planning_Hr');
            $data->TabCaption = 'HR';
        }
        
        // Подготовка на индикаторите
        $data->Indicators = cls::get('hr_Indicators');
        if ($this->haveRightFor('seeindicators', (object) array('personId' => $data->masterId))) {
            $data->Indicators->preparePersonIndicators($data);
        }
        
        $eQuery = planning_Hr::getQuery();
        $eQuery->where("#personId = '{$data->masterId}'");
        
        while ($eRec = $eQuery->fetch()) {
            $keys = keylist::toArray($eRec->departments);
            if (countR($keys) == 1) {
                foreach ($keys as $key) {
                    $data->Cycles = cls::get('hr_WorkingCycles');
                    $data->Cycles->masterId = $key;
                    $data->Cycles->personId = $data->masterId;
                }
            }
        }
        
        $data->Cards = cls::get('crm_ext_IdCards');
        $data->Cards->prepareIdCard($data);
        
        if (isset($data->Cycles)) {
            $data->Cycles->prepareGrafic($data);
            $data->TabCaption = 'HR';
        }
        
        if (isset($data->Codes)) {
            $data->Codes->prepareData($data);
            if (planning_Hr::haveRightFor('add', (object) array('personId' => $data->masterId))) {
                $data->addResourceUrl = array('planning_Hr', 'add', 'personId' => $data->masterId, 'ret_url' => true);
            }
        }
    }
    
    
    /**
     * Подготвя ценовата информация за артикула
     */
    public function renderPersonsDetails($data)
    {
        $tpl = getTplFromFile('crm/tpl/PersonsData.shtml');
        
        // Показване на индикаторите
        if (isset($data->IData)) {
            $resTpl = $data->Indicators->renderPersonIndicators($data);
            $resTpl->removeBlocks();
            $tpl->append($resTpl, 'INDICATORS_TABLE');
        }
        
        // Показване на клиентските карти
        $cardTpl = $data->Cards->renderIdCard($data);
        $cardTpl->removeBlocks();
        $tpl->append($cardTpl, 'IDCARD');
        
        // Показване на кода
        if (isset($data->Codes)) {
            $resTpl = $data->Codes->renderData($data);
            $resTpl->removeBlocks();
            $tpl->append($resTpl, 'CODE');
        }
        
        // Показване на работните цикли
        if (isset($data->Cycles)) {
            $resTpl = $data->Cycles->renderGrafic($data);
            $resTpl->removeBlock('legend');
            $resTpl->removeBlocks();
            $tpl->append($resTpl, 'CYCLES');
            
            if (crm_Persons::haveRightFor('single', (object) array('personId' => $data->masterId))) {
                // правим url  за принтиране
                $url = array('hr_WorkingCycles', 'Print', 'Printing' => 'yes', 'masterId' => $data->Cycles->masterId, 'cal_month' => $data->Cycles->month, 'cal_year' => $data->Cycles->year, 'personId' => $data->masterId);
                $efIcon = 'img/16/printer.png';
                $link = ht::createLink('', $url, false, "title=Печат,ef_icon={$efIcon},style=float:right; height: 16px;");
                $tpl->append($link, 'print');
            }
        }
        
        return $tpl;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        // Кой може да вижда индикаторите
        if ($action == 'seeindicators' && isset($rec)) {
            $personUserId = crm_Profiles::fetchField("#personId = {$rec->personId}", 'userId');
            if ($personUserId != $userId) {
                $requiredRoles = 'hr,ceo';
            }
        }
    }
}
