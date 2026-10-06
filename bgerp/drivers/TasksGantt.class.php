<?php


/**
 * Гант на филтрирани задачи в портала
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Задачи - Гант
 */
class bgerp_drivers_TasksGantt extends core_BaseClass
{
    public $interfaces = 'bgerp_PortalBlockIntf';

    public $maxCnt;


    public function addFields(core_Fieldset &$fieldset)
    {
        $Tasks = cls::get('cal_Tasks');
        $fieldset->FLD('title', 'varchar', 'caption=Заглавие');
        $fieldset->FLD('period', 'enum(thisMonth=Този месец,nextMonth=Следващият месец,aroundMonth=±1 месец от днес,thisYear=Тази година,relative=Дни назад/напред,fixed=Фиксиран период)', 'caption=Период->Избор,mandatory,silent,refreshForm');
        $fieldset->FLD('daysBefore', 'int(min=0,max=3660)', 'caption=Период->Дни назад');
        $fieldset->FLD('daysAfter', 'int(min=0,max=3660)', 'caption=Период->Дни напред');
        $fieldset->FLD('dateFrom', 'date', 'caption=Период->От');
        $fieldset->FLD('dateTo', 'date', 'caption=Период->До');
        $fieldset->FLD('selectedUsers', "users(rolesForAll={$Tasks->filterRolesForAll})", 'caption=Филтри->Потребител', array(
            'hint' => 'Включва задачите, създадени от избрания потребител, възложени на него или споделени с него. Гант показва всички изпълнители на тези задачи.'
        ));
        $fieldset->FLD('search', 'varchar', 'caption=Филтри->Ключови думи,inputmode=search,hint=Търсене в заглавието и описанието както в списъка със задачи');
        $fieldset->FLD('stateTask', cal_Tasks::getStateTaskFilterType(), 'caption=Филтри->Състояние');
        $fieldset->FLD('folder', 'key2(mvc=doc_Folders,forceReplica,allowEmpty,selectSourceArr=doc_Folders::getSelectArr)', 'caption=Филтри->Папка');
        $fieldset->FLD('assetResourceId', cal_Tasks::getAssetResourceFilterType(), 'caption=Филтри->Ресурси');
        $fieldset->FLD('stepId', clone $Tasks->getFieldType('stepId'), 'caption=Филтри->Относно');
        $fieldset->FLD('progress', 'percent(min=0,max=1,decimals=0)', 'caption=Филтри->Минимален прогрес');
        $fieldset->FLD('taskType', 'class(interface=cal_TaskTypeIntf,select=title,allowEmpty)', 'caption=Филтри->Вид');
        $fieldset->FLD('taskOrder', 'enum()', 'caption=Филтри->Подредба');
        $options = array('' => '');
        foreach ($Tasks->listOrderBy as $key => $attr) {
            $options[$key] = $attr[0];
        }
        $fieldset->setOptions('taskOrder', $options);
    }


    protected static function on_AfterPrepareEditForm($Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form ?? null;
        expect($form instanceof core_Form);
        $form->setDefault('period', 'thisMonth');
        $form->setDefault('stateTask', 'all');
        $form->setDefault('selectedUsers', keylist::fromArray(array(core_Users::getCurrent() => true)));
        if (!empty($form->rec->assetResourceId)) {
            $form->rec->assetResourceId = keylist::fromArray(keylist::toArray($form->rec->assetResourceId));
        }
        $form->setOptions('stepId', array('' => '') + doc_UnsortedFolderSteps::getOptionArr());
        $period = $form->rec->period ?? 'thisMonth';
        if ($period != 'fixed') {
            $form->setField('dateFrom,dateTo', 'input=none');
        } else {
            $form->setField('dateFrom,dateTo', 'mandatory');
        }
        if ($period != 'relative') {
            $form->setField('daysBefore,daysAfter', 'input=none');
        } else {
            $form->setDefault('daysBefore', 30);
            $form->setDefault('daysAfter', 30);
            $form->setField('daysBefore,daysAfter', 'mandatory');
        }
    }


    protected static function on_AfterInputEditForm($Driver, embed_Manager $Embedder, &$form)
    {
        if ($form->isSubmitted() && ($form->rec->period ?? null) == 'fixed' &&
            ($form->rec->dateFrom ?? '') > ($form->rec->dateTo ?? '')) {
            $form->setError('dateFrom,dateTo', 'Началото трябва да е преди края на периода');
        }
    }


    public function canSelectDriver($userId = null)
    {
        return cal_Tasks::haveRightFor('list', null, $userId);
    }


    /**
     * Относителните периоди се преизчисляват при всяко показване.
     */
    public static function getPeriodRange($rec, $today = null)
    {
        $today = $today ?? dt::today();
        $monthStart = substr($today, 0, 7) . '-01';
        switch ($rec->period ?? 'thisMonth') {
            case 'fixed':
                return array($rec->dateFrom ?? null, $rec->dateTo ?? null);
            case 'relative':
                return array(dt::addDays(-max(0, (int) ($rec->daysBefore ?? 30)), $today, false),
                    dt::addDays(max(0, (int) ($rec->daysAfter ?? 30)), $today, false));
            case 'aroundMonth':
                return array(dt::addMonths(-1, $today, false), dt::addMonths(1, $today, false));
            case 'thisYear':
                $year = substr($today, 0, 4);
                return array($year . '-01-01', $year . '-12-31');
            case 'nextMonth':
                $monthStart = dt::addMonths(1, $monthStart, false);
                break;
        }

        return array($monthStart, dt::getLastDayOfMonth($monthStart));
    }


    public function prepare($dRec, $userId = null)
    {
        $userId = $userId ?? core_Users::getCurrent();
        $res = (object) array('dRec' => $dRec, 'userId' => $userId, 'data' => null);
        // Подготовката и линковете трябва да са с правата на зрителя, включително при споделен блок.
        if ($userId != core_Users::getCurrent() || !$this->canSelectDriver($userId)) {
            return $res;
        }

        list($from, $to) = self::getPeriodRange($dRec);
        if (!$from || !$to || $from > $to) {
            return $res;
        }

        $Tasks = clone cls::get('cal_Tasks');
        $Tasks->useFilterDateOnFilter = false;
        $data = (object) array('action' => 'list', 'query' => $Tasks->getQuery(), 'listFields' => array());
        $form = $Tasks->getForm();
        $data->listFilter = $form;
        foreach ($form->selectFields('#mandatory') as $name => $field) {
            $form->setField($name, array('mandatory' => null));
        }
        $filter = array('from' => $from, 'to' => $to,
            'selectedUsers' => !empty($dRec->selectedUsers)
                ? $dRec->selectedUsers : keylist::fromArray(array($userId => $userId)),
            'search' => $dRec->search ?? '', 'stateTask' => $dRec->stateTask ?? 'all',
            'folder' => $dRec->folder ?? '', 'assetResourceId' => $dRec->assetResourceId ?? '',
            'stepId' => $dRec->stepId ?? '', 'progress' => $dRec->progress ?? '',
            'order' => $dRec->taskOrder ?? '', $Tasks->driverClassField => $dRec->taskType ?? '',
            'Chart' => 'Gantt', 'View' => '', 'selectPeriod' => 'select');
        $form->rec = (object) $filter;
        // URL параметрите на портала не променят запазените филтри и не преминават към друг блок.
        $request = array_fill_keys(array_keys($form->fields), '');
        $request = array_merge($request, array('Cmd' => '', 'Rejected' => '', 'selectPeriod' => 'select'), $filter);
        Request::push($request, 'portalTasksGantt');
        try {
            $Tasks->prepareListFilter($data);
            if (!$form->gotErrors()) {
                $Tasks->prepareListRecs($data);
                $res->data = $data;
            }
        } finally {
            Request::pop('portalTasksGantt');
        }

        $res->from = $from;
        $res->to = $to;
        $res->cacheKey = $this->getCacheKey($dRec, $userId);

        return $res;
    }


    public function render($data)
    {
        if (($data->userId ?? null) != core_Users::getCurrent() || !$this->canSelectDriver()) {
            return new ET('');
        }
        $chartData = $data->data ?? null;
        if (empty($chartData)) {
            return new ET(tr('Невалидни настройки за Гант на задачите'));
        }

        $dRec = $data->dRec ?? new stdClass();
        $tpl = new ET('<div class="portal portal-tasks-gantt" style="margin-bottom:25px"><div class="legend">[#TITLE#] <span class="gantt-period">[#PERIOD#]</span></div><div class="gantt-portal-content">[#CHART#]</div></div>');
        $form = $chartData->listFilter ?? null;
        $filter = (array) ($form->rec ?? null);
        $url = array('cal_Tasks', 'list', 'Chart' => 'Gantt', 'selectPeriod' => 'select');
        foreach (array('from', 'to', 'selectedUsers', 'search', 'stateTask', 'folder', 'assetResourceId', 'stepId', 'progress', 'order', 'driverClass') as $field) {
            $url[$field] = $filter[$field] ?? '';
        }
        $tpl->replace(ht::createLink($this->getBlockTabName($dRec), $url), 'TITLE');
        if (!empty($chartData->recs)) {
            $chartData->ganttId = 'ganttTablePortal' . (int) ($dRec->originIdCalc ?? $dRec->id ?? 0);
            $chartData->ganttFrom = $data->from ?? null;
            $chartData->ganttTo = $data->to ?? null;
            $chartData->ganttPortal = true;
            $tpl->replace(cal_Tasks::getGantt($chartData), 'CHART');
        } else {
            $tpl->replace(tr('Няма задачи за избраните филтри и период'), 'CHART');
        }
        $tpl->replace(dt::mysql2verbal($chartData->ganttVisibleFrom ?? $data->from ?? '', 'd.m.Y') . ' - ' .
            dt::mysql2verbal($chartData->ganttVisibleTo ?? $data->to ?? '', 'd.m.Y'), 'PERIOD');
        $tpl->push('gantt/lib/ganttCustom.css', 'CSS');

        return $tpl;
    }


    public function getBlockTabName($dRec)
    {
        return type_Varchar::escape(($dRec->title ?? null) ?: tr('Задачи - Гант'));
    }


    public function getCacheTypeName($userId = null)
    {
        return 'Portal_TasksGantt_' . ($userId ?? core_Users::getCurrent());
    }


    public function getCacheKey($dRec, $userId = null)
    {
        // Няма кеш на задачите: правата и филтрите се прилагат и при всяко AJAX обновяване.
        return md5(serialize(array(bgerp_Portal::getPortalCacheKey($dRec, $userId), self::getPeriodRange($dRec))));
    }
}
