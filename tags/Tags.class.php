<?php


/**
 *
 *
 * @category  bgerp
 * @package   tags
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class tags_Tags extends core_Manager
{
    /**
     * Заглавие на модела
     */
    public $title = 'Маркери';


    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, admin';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, admin';


    /**
     * Кой има право да променя?
     */
    public $canEditsysdata = 'ceo, admin';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, admin';


    /**
     * Кой има право да го види?
     */
    public $canView = 'ceo, admin';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, admin';


    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';


    /**
     *
     */
    public $searchFields = 'name';


    /**
     * @var string
     */
    public $canChangestate = 'ceo, admin';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'tags_Wrapper, plg_Created, plg_Search, plg_Sorting, plg_RowTools2, plg_State2, core_UserTranslatePlg';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar', 'caption=Име, mandatory, translate=user|tr|transliterate');
        $this->FLD('userOrRole', 'userOrRole(rolesType=team, showRolesFirst=admin)', 'caption=Потребител, mandatory');
        $this->FLD('color', 'color_Type', 'caption=Цвят');

        $this->setDbUnique('userOrRole, name');
    }


    /**
     * Подготовка на формата за добавяне
     */
    protected static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $data->form->setDefault('userOrRole', type_UserOrRole::getAllSysTeamId());

        if ($data->form->rec->createdBy == '-1') {
            $data->form->setReadonly('name');
            $data->form->setReadonly('userOrRole');
        }
    }


    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->view = 'horizontal';
        $data->listFilter->showFields = 'search';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list', 'show' => Request::get('show')), 'id=filter', 'ef_icon = img/16/funnel.png');

        $data->listFilter->input(null, 'silent');

        $data->query->orderBy('createdOn', 'DESC');
    }


    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    protected static function on_AfterSetupMvc($mvc, &$res)
    {
        $res .= core_Classes::add($mvc);

        $file = 'tags/csv/Tags.csv';
        $fields = array(
            0 => 'name',
            1 => 'color',
        );

        $cntObj = csv_Lib::importOnce($mvc, $file, $fields);
        $res .= $cntObj->html;

        return $res;
    }


    /**
     * Изпълнява се преди импортирването на данните
     */
    public static function on_BeforeImportRec($mvc, &$rec)
    {
        if (!$rec->userOrRole) {
            $rec->userOrRole = type_UserOrRole::getAllSysTeamId();
        }
    }
}
