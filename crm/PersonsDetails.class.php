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
    public function preparePersonsDetails_($data)
    {
        $data->TabCaption = 'Лични данни';
        expect($data->masterMvc instanceof crm_Persons);

        $employeeId = crm_Groups::getIdFromSysId('employees');
        if (keylist::isIn($employeeId, $data->masterData->rec->groupList)) {
            $data->Codes = cls::get('planning_Hr');
            $data->TabCaption = 'HR';
            $ScheduleData = new stdClass();
            $ScheduleData->masterMvc = $data->masterMvc;
            $ScheduleData->masterId = $data->masterId;
            hr_Schedules::prepareCalendar($ScheduleData);
            $data->Schedule = $ScheduleData;
        }

        // Подготовка на индикаторите
        $data->Indicators = cls::get('hr_Indicators');
        if ($this->haveRightFor('seeindicators', (object) array('personId' => $data->masterId))) {
            $data->Indicators->preparePersonIndicators($data);
        }

        $data->Cards = cls::get('crm_ext_IdCards');
        $data->Cards->prepareIdCard($data);
        
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
    public function renderPersonsDetails_($data)
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

        $Schedules = cls::get('hr_Schedules');
        // Показване на работните цикли
        if (isset($data->Schedule)) {
            $resTpl = $Schedules->renderCalendar($data->Schedule);
            $tpl->append($resTpl, 'CYCLES');
            $tpl->append(hr_Schedules::getHyperlink($data->Schedule->scheduleId, true), 'name');

            if (hr_Schedules::haveRightFor('single', $data->Schedule->scheduleId)) {
                // правим url  за принтиране
                $url = array('hr_Schedules', 'Single', $data->Schedule->scheduleId, 'Printing' => 'yes', 'month' => Request::get('month', 'date'));
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
