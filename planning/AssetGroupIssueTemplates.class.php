<?php


/**
 * Мениджър за готови сигнали за оборудванията
 *
 * @category  bgerp
 * @package   support
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     0.12
 */
class planning_AssetGroupIssueTemplates extends core_Detail
{

    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'groupId';


    /**
     * Заглавие
     */
    public $title = 'Готови сигнали';


    /**
     * Единично заглавие
     */
    public $singleTitle = 'Готов сигнал';


    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    public $loadList = 'planning_Wrapper,plg_State2,plg_SaveAndNew,plg_RowTools2';


    /**
     * Кой може да редактира
     */
    public $canWrite = 'ceo, planningMaster';


    /**
     * Кой може да изтрива
     */
    public $canDelete = 'ceo, planningMaster';


    /**
     * Полета в лист изгледа
     */
    public $listFields = 'groupId,string,lastUsedOn,state';


    /**
     * Кой има достъп до лист изгледа
     */
    public $canList = 'ceo, planning';


    /**
     * Предлог в формата за добавяне/редактиране
     */
    public $formTitlePreposition = 'към';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('groupId', 'key(mvc=planning_AssetGroups,select=name)', 'remember,input=hidden,silent,mandatory,caption=Група');
        $this->FLD('string', 'varchar(64)', 'caption=Готов сигнал,mandatory,tdClass=leftCol');
        $this->FLD('lastUsedOn', 'datetime(format=smartTime)', 'caption=Последно,input=none');

        $this->setDbUnique('groupId,string');
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->groupId = planning_AssetGroups::getHyperlink($rec->groupId, true);
    }


    /**
     * След подготовка на полетата
     */
    protected static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
        if ($data->masterMvc) {
            unset($data->listFields['groupId']);
        }
    }


    /**
     * Подготовка на Детайлите
     */
    public function prepareDetail_($data)
    {
        $data->TabCaption = tr('Готови сигнали');

        parent::prepareDetail_($data);
    }


    /**
     * Рендиране на детайла
     *
     * @param stdClass $data
     * @return core_ET $resTpl
     */
    public function renderDetail_($data)
    {
        $tpl = parent::renderDetail_($data);
        $resTpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        $resTpl->append($tpl, 'content');
        $resTpl->append(tr("Готови сигнали"), 'title');

        return $resTpl;
    }


    /**
     * Наличните за избор готови сигнали
     *
     * @param int $assetId
     * @param int|null $exId
     * @return array $options
     */
    public static function getAvailableIssues($assetId, $exId = null)
    {
        $options = array();
        $assetGroupId = planning_AssetResources::fetchField($assetId, 'groupId');

        $query = planning_AssetGroupIssueTemplates::getQuery();
        $query->where("#groupId = {$assetGroupId} AND #state != 'rejected'");
        if(isset($exId)){
            $query->orWhere("#id = {$exId}");
        }
        while($rec = $query->fetch()){
            $options[$rec->id] = $rec->string;
        }

        return $options;
    }


    /**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if($action == 'delete' && isset($rec->lastUsedOn)){
            $res = 'no_one';
        }
    }
}