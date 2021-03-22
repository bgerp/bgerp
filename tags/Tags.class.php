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
     * Връща името на тага и span с цвета
     *
     * @param integer $tagId
     * @return array
     */
    public static function getTagNameArr($tagId)
    {
        $resArr = array();

        if (!$tagId) {

            return $resArr;
        }

        $rec = self::fetch($tagId);

        if (!$rec) {

            return $resArr;
        }

        $resArr['name'] = self::recToVerbal($rec, 'name')->name;

        $resArr['span'] = '<span';

        if ($rec->color) {
            $resArr['color'] = $rec->color;

            $color = phpcolor_Adapter::checkColor($rec->color, 'dark') ? '#fff' : '#000';

            $resArr['span'] .= " style='background-color: {$rec->color}; color: {$color}'";
        }
        $resArr['span'] .= '>' . $resArr['name'];

        $resArr['span'] .= '</span>';

        return $resArr;
    }


    /**
     * Връща масив с таговоте за добавя в опциите
     *
     * @return array
     */
    public static function getTagsOptions($userId = null, $oldTagArr = array())
    {
        $tagsArr = array();
        $tQuery = self::getQuery();
        $tQuery->where("#state = 'active'");

        if (!empty($oldTagArr)) {
            $tQuery->in('id', $oldTagArr, false, true);
        }

        if (isset($userId)) {
            $tQuery->where(array("#userOrRole = '[#1#]'", $userId));
            $tQuery->orWhere(array("#userOrRole = '[#1#]'", type_UserOrRole::getAllSysTeamId()));

            if (!empty($oldTagArr)) {
                $tQuery->in('id', $oldTagArr, false, true);
            }
        }

        $tQuery->orderBy('name', 'ASC');
        $tQuery->show('id, name, color');

        while ($tRec = $tQuery->fetch()) {
            $opt = new stdClass();
            $opt->title = tags_tags::getVerbal($tRec, 'name');
            $color = $tRec->color;
            if (!$color) {
                $color = ' '; // Прозрачен `background`
            }

            $opt->attr = array('data-color' => $color);
            $optArr[$tRec->id] = $opt;

            $tagsArr[$tRec->id] = $opt;
        }

        return $tagsArr;
    }


    /**
     * Помощна фунцкия за декорира и вземане на маркерите
     *
     * @param stdClass $rec
     *
     * @return string
     */
    public static function decorateTags($tArr)
    {
        $tags = '';

        if (!is_array($tArr)) {
            $tArr = type_Keylist::toArray($tArr);
        }

        if (empty($tArr)) {

            return $tArr;
        }

        foreach ($tArr as $tId) {
            $tRecArr = tags_Tags::getTagNameArr($tId);
            $tags .= $tRecArr['span'];
        }

        $tags = "<span class='documentTags'>" . $tags . "</span>";

        return $tags;
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
