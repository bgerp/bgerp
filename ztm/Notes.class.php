<?php 

/**
 * Бележки за устройствата
 *
 * @category  bgerp
 * @package   ztm
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.11
 */
class ztm_Notes extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Бележки';

    
    /**
     * Кой има право да променя?
     */
    protected $canEdit = 'ztm, ceo, admin';

    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'ceo, admin';


    /**
     * Кой има право да оттегля системните данни?
     */
    public $canRejectsysdata = 'ztm, ceo, admin';

    
    /**
     * Кой има право да добавя?
     */
    protected $canAdd = 'ztm, ceo, admin';

    
    /**
     * Кой може да го разглежда?
     */
    protected $canList = 'ztm, ceo, admin';
    
    
    /**
     * Кой може да го оттегля?
     */
    protected $canReject = 'ztm, ceo, admin';


    /**
     * Кой може да го изтрие?
     */
    protected $canDelete = 'no_one  ';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'ztm_Wrapper, plg_Created, plg_Modified, plg_Rejected, plg_RowTools2';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 20;


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'device, note, importance';



    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('device', 'customKey(mvc=ztm_Devices, key=name, select=name)', 'caption=Списък, silent');
        $this->FLD('state', 'enum(active=Активен,rejected=Оттеглен)', 'caption=Състояние,input=none');
        $this->FLD('note', 'richtext(rows=6, bucket=Notes)', 'caption=Бележка, mandatory');
        $this->FLD('importance', 'enum(low=Ниска, normal=Нормална, high=Висока, critical=Критична)', 'caption=Важност');
    }


    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $data->form->setDefault('importance', 'normal');
        $data->form->setDefault('state', 'active');
        $data->form->setReadonly('device');
    }


    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('createdOn', 'DESC');
        $data->query->orderBy('id', 'DESC');
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->ROW_ATTR['class'] = "state-{$rec->state}";
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
        // След 1 час, да не може да се редактират бележките
        if (($action == 'edit') && $rec) {
            if (dt::subtractSecs(60*60) > $rec->createdOn) {
                $requiredRoles = 'no_one';
            } elseif ($rec->state == 'rejected') {
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Подготовка на детайла
     *
     * @param stdClass $data
     */
    public function prepareDetail_($data)
    {
        $data->TabCaption = tr('Бележки');

        // Подготовка на записите
        $query = self::getQuery();
        $query->where(array("LOWER(#device) = '[#1#]'", mb_strtolower($data->masterData->rec->name)));
        $query->orderBy('createdOn', 'DESC');
        $query->orderBy('id', 'DESC');
        $data->recs = $data->rows = array();
        while ($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $data->rows[$rec->id] = $this->recToVerbal($rec);
        }

        // Има ли права за добавяне на нова бележка
        if ($this->haveRightFor('add', (object) array('device' => $data->masterId))) {
            $data->addUrl = array($this, 'add', 'device' => $data->masterData->rec->name, 'ret_url' => true);
        }

        return $data;
    }


    /**
     * Рендиране на детайла
     *
     * @param stdClass $data
     * @return core_ET $resTpl
     */
    public function renderDetail_($data)
    {
        $tpl = new core_ET('');

        // Рендиране на таблицата с оборудването
        $data->listFields = arr::make('note=Бележка,importance=Важност,createdOn=Създадено->На,createdBy=Създадено->От');
        $listTableMvc = clone $this;

        $table = cls::get('core_TableView', array('mvc' => $listTableMvc));
        $this->invoke('BeforeRenderListTable', array($tpl, &$data));
        $tpl->append($table->get($data->rows, $data->listFields));

        // Бутон за добавяне на ново оборудване
        if (isset($data->addUrl)) {
            $btn = ht::createBtn('Бележка', $data->addUrl, false, false, "ef_icon={$this->singleIcon},title=Добавяне на нова бележка");
            $tpl->append($btn, 'toolbar');
        }

        $resTpl = getTplFromFile('ztm/tpl/NotesDetail.shtml');
        $resTpl->append($tpl, 'content');
        $resTpl->append(tr("Бележки"), 'title');

        return $resTpl;
    }
}
