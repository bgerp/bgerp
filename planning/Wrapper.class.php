<?php


/**
 * Планиране - опаковка
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('planning_DirectProductionNote', 'Протоколи->Производство', 'ceo,production,planningAll');
        $this->TAB('planning_ConsumptionNotes', 'Протоколи->Влагане', 'ceo,consumption,planningAll');
        $this->TAB('planning_ReturnNotes', 'Протоколи->Връщане', 'ceo,consumption,planningAll');
        $this->TAB('planning_WorkInProgress', 'Протоколи->Незавършено производство', 'ceo,planning,production,planningAll');

        $this->TAB('planning_Jobs', 'Задания', 'ceo,jobSee,planningAll');
        $this->TAB('planning_Tasks', 'Операции->Списък', 'ceo,taskSee,planningAll');
        $this->TAB('planning_ProductionTaskDetails', 'Операции->Прогрес', 'ceo,taskSee,planningAll');
        $this->TAB('planning_WorkCards', 'Операции->Работни карти', 'ceo,planning,admin');
        $this->TAB('planning_Centers', 'Центрове', 'ceo, planning, jobSee,planningAll');
        $this->TAB(array('planning_AssetResources', 'type' => 'material'), 'Ресурси->Оборудване', 'ceo,planning');
        $this->TAB(array('planning_AssetResources', 'type' => 'nonMaterial'), 'Ресурси->Нематериални', 'ceo,planning');
        $this->TAB('planning_Hr', 'Ресурси->Хора', 'ceo,planning');
        $this->TAB('planning_AssetGroups', 'Ресурси->Видове', 'ceo,planning');
        $this->TAB('planning_Steps', 'Етапи->Списък', 'ceo,planning');
        $this->TAB('planning_StepConditions', 'Етапи->Зависимости', 'ceo,planning');
        $this->TAB('planning_AssetResourcesNorms', 'Настройки->Норми', 'ceo,planning');
        $this->TAB('planning_AssetSparePartsDetail', 'Настройки->Резерв. части', 'ceo,planning');
        $this->TAB('planning_AssetGroupIssueTemplates', 'Настройки->Готови сигнали', 'ceo,planning');
        $this->TAB('planning_GenericMapper', 'Настройки->Генерични', 'ceo,planning');
        $this->TAB('planning_AssetIdleTimes', 'Настройки->Времена за престой', 'ceo,planning');
        $this->TAB('planning_Points', 'Дебъг->Точки', 'debug');
        $this->TAB('planning_GenericProductPerDocuments', 'Дебъг->По документи', 'debug');
        $this->TAB('planning_AssetResourceFolders', 'Дебъг->Ресурси по папки', 'debug');
        $this->TAB('planning_TaskConstraints', 'Дебъг->Ограничения', 'debug');
        $this->TAB('planning_TaskManualOrderPerAssets', 'Дебъг->Ръчна подредба', 'debug');

        $this->title = 'Планиране';
    }
}
