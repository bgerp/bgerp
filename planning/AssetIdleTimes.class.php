<?php

/**
 * Детайли на работните цикли на оборудването
 *
 *
 * @category  bgerp
 * @package   hr
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_AssetIdleTimes extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Времена за престой на оборудването';


    /**
     * Работни интервали на оборудването
     */
    public $singleTitle = 'Време за престой на оборудването';


    /**
     * Мастър ключ
     */
    public $masterKey = 'assetId';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_SaveAndNew, plg_Created, plg_Modified, planning_Wrapper';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'assetId,date,duration';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, planningMaster';


    /**
     * Кой може да го изтрие?
     *
     */
    public $canDelete = 'ceo, planningMaster';


    /**
     * Кой може да го изтрие?
     *
     */
    public $canEdit = 'ceo, planningMaster';


    /**
     * Кой може да го листва?
     *
     */
    public $canList = 'ceo, planning';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('assetId', 'key(mvc=planning_AssetResources,select=name,allowEmpty)', 'caption=Оборудване');

        $this->FLD('date', 'datetime(format=smartTime)', 'caption=Начало,mandatory');
        $this->FLD('duration', 'time(min=1,suggestions=00:30|01:00|01:30|02:00|02:30|03:00|03:30|04:00|04:30|05:00|05:30|6:00|6:30|7:00|7:30|8:00|8:30|9:00|9:30|10:00|10:30|11:00|11:30|12:00|24:00,allowEmpty)', 'caption=Продължителност,mandatory,remember');
    }


    /**
     * Сортиране
     */
    public function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('#start', 'DESC');
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;

        if ($form->isSubmitted()) {
            if(empty($rec->id)){
                $until = dt::addSecs($rec->duration, $rec->date);
                if($until < dt::now()) {
                    $form->setError('date,duration', "Не може да добавяте време за престой в миналото!");
                }
            }
        }
    }


    /**
     * Подготовка на Детайлите
     */
    public function prepareDetail_($data)
    {
        $data->TabCaption = tr('График');
        parent::prepareDetail_($data);

        $data->schedule = new stdClass();
        $data->schedule->masterMvc = $data->masterMvc;
        $data->schedule->masterId = $data->masterId;
        hr_Schedules::prepareCalendar($data->schedule);
    }


    /**
     * Рендиране на детайла
     *
     * @param stdClass $data
     * @return core_ET $tpl
     */
    public function renderDetail_($data)
    {
        $tpl = getTplFromFile('planning/tpl/AssetScheduleDetail.shtml');
        $tpl->append(parent::renderDetail_($data), 'INTERVALS');
        $tpl->append(hr_Schedules::getHyperlink($data->schedule->scheduleId, true), 'name');

        // Показване на работните цикли
        if (isset($data->schedule)) {
            $Schedules = cls::get('hr_Schedules');
            $resTpl = $Schedules->renderCalendar($data->schedule);
            $tpl->append($resTpl, 'CYCLES');
        }

        return $tpl;
    }
}
