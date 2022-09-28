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
        $this->TAB('planning_DirectProductionNote', 'Протоколи->Производство', 'ceo,production');
        $this->TAB('planning_ConsumptionNotes', 'Протоколи->Влагане', 'ceo,consumption');
        $this->TAB('planning_ReturnNotes', 'Протоколи->Връщане', 'ceo,consumption');
        $this->TAB('planning_Jobs', 'Задания', 'ceo,jobSee');
        $this->TAB('planning_Tasks', 'Операции->Списък', 'ceo,task');
        $this->TAB('planning_ProductionTaskDetails', 'Операции->Прогрес', 'ceo,task,taskWorker');
        $this->TAB('planning_WorkCards', 'Операции->Работни карти', 'ceo,planning,admin');
        $this->TAB('planning_Centers', 'Центрове', 'ceo, jobSee');
        $this->TAB(array('planning_AssetResources', 'type' => 'material'), 'Ресурси->Оборудване', 'ceo,planning');
        $this->TAB(array('planning_AssetResources', 'type' => 'nonMaterial'), 'Ресурси->Нематериални', 'ceo,planning');
        $this->TAB('planning_Hr', 'Ресурси->Хора', 'ceo,planning');
        $this->TAB('planning_AssetGroups', 'Ресурси->Групи', 'ceo,planning');
        $this->TAB('planning_Steps', 'Етапи->Списък', 'ceo,planning');
        $this->TAB('planning_StepConditions', 'Етапи->Зависимости', 'ceo,planning');
        $this->TAB('planning_FoldersWithResources', 'Настройки->Папки с ресурси', 'ceo,planning');
        $this->TAB('planning_AssetResourcesNorms', 'Настройки->Норми', 'ceo,planning');
        $this->TAB('planning_GenericMapper', 'Настройки->Генерични', 'ceo,planning');
        $this->TAB('planning_Points', 'Дебъг->Точки', 'debug');
        
        $this->title = 'Планиране';
    }
}
