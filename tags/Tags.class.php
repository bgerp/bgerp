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
    public $title = 'Таг';


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
        $this->FLD('type', 'enum(common=Общ, personal=Персонален)', 'caption=Тип, mandatory');
        $colorType = cls::get('color_Type');
        $colorType->tdClass = null;
        $this->FLD('color', $colorType, 'caption=Цвят');
        $this->FLD('classes', 'classes(interface=doc_DocumentIntf,select=title,allowEmpty)', 'caption=Класове');

        $this->setDbUnique('name');
    }


    /**
     * Връща всички тагове
     *
     * @param null|string $typeOrder
     * @param null|string $nameOrder
     * @param null|string $state
     *
     * @return array
     */
    public static function getNamesArr($typeOrder = 'DESC', $nameOrder = 'ASC', $state = null)
    {
        static $namesArr = array();
        if (empty($namesArr)) {
            $query = self::getQuery();
            $query->show('id, name');
            if (isset($typeOrder)) {
                $query->orderBy('type', $typeOrder);
            }

            if (isset($nameOrder)) {
                $query->orderBy('name', $nameOrder);
            }

            if (isset($state)) {
                $query->where(array("#state = '[#1#]'", $state));
            }

            while ($rec = $query->fetchAndCache()) {
                $namesArr[$rec->id] = $rec->name;
            }
        }

        return $namesArr;
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

        $url = toUrl(array('doc_Search'));
        $url = rtrim($url, '/');
        $url .= "/?tags%5B%5D={$rec->id}&tags%5Bselect2%5D=1";

        $resArr['nameLink'] = ht::createLink($rec->name, $url);

        $resArr['span'] = "<span class='tags tagType-{$rec->type}'";

        if ($rec->color) {
            $resArr['color'] = $rec->color;

            $color = phpcolor_Adapter::checkColor($rec->color, 'dark') ? '#fff' : '#000';

            $resArr['span'] .= " style='background-color: {$rec->color}; color: {$color}'";
        }

        $name = $resArr['nameLink'];

        $resArr['spanNoName'] = $resArr['span'] . " title='{$resArr['name']}'></span>";

        $resArr['span'] .= '>' . $name;

        $resArr['span'] .= '</span>';

        return $resArr;
    }


    /**
     * Връща масив с таговоте за добавя в опциите
     *
     * @param array $oldTagArr
     * @param null|int $docClassId
     * @return array
     */
    public static function getTagsOptions($oldTagArr = array(), $docClassId = null)
    {
        $tagsArr = array();
        $tQuery = self::getQuery();
        $tQuery->where("#state = 'active'");
        if(isset($docClassId)){
            $tQuery->where("#classes IS NULL OR LOCATE('|{$docClassId}|', #classes)");
        }

        if (!empty($oldTagArr)) {
            $tQuery->in('id', $oldTagArr, false, true);
        }

        $tQuery->orderBy('name', 'ASC');
        $tQuery->show('id, name, color, type');

        while ($tRec = $tQuery->fetch()) {
            $opt = new stdClass();
            $opt->title = tags_tags::getVerbal($tRec, 'name');
            $color = $tRec->color;
            if (!$color) {
                $color = ' '; // Прозрачен `background`
            }

            $opt->attr = array('data-color' => $color, 'data-colorClass' => 'tagType-' . $tRec->type);
            $opt->insideLabel = "<span class='colorBox tagType-{$tRec->type}' style='background-color:{$color} !important;'></span>";

            $optArr[$tRec->id] = $opt;

            $tagsArr['all'][$tRec->id] = $opt;
            $tagsArr[$tRec->type][$tRec->id] = $opt;
        }

        return $tagsArr;
    }


    /**
     * Помощна фунцкия за декорира и вземане на таговете
     *
     * @param mixed $tArr
     * @param string $prevText
     * @return string $tags
     */
    public static function decorateTags($tArr, $prevText = '')
    {
        $tags = '';

        if (!is_array($tArr)) {
            $tArr = type_Keylist::toArray($tArr);
        }

        foreach ($tArr as $tId) {
            $tRecArr = tags_Tags::getTagNameArr($tId);
            $tags .= $tRecArr['span'];
        }

        $tags = "<span class='documentTags'>" . $prevText . $tags . "</span>";

        return $tags;
    }


    /**
     * От подадения масив връща всички активни персонални тагоаве
     *
     * @param array $pTagsArr
     * @return array
     */
    public static function getPersonalTags($pTagsArr = array(), $onlyActive = true)
    {
        $query = self::getQuery();
        $query->where("#type = 'personal'");

        if ($onlyActive) {
            $query->where("#state = 'active'");
        }

        if (!empty($pTagsArr)) {
            $query->in('id', $pTagsArr);
        }

        $query->show('id');

        $resArr = array();
        while ($rec = $query->fetch()) {
            $resArr[$rec->id] = $rec->id;
        }

        return $resArr;
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if ($rec->color) {
            $cObj = new color_Object($rec->color);

            $bgColor = $cObj->getHex();

            $row->color = "<span class='colorBox tagType-{$rec->type}' style=\"background-color:{$bgColor} !important;\">&nbsp;</span><span class='colorName'>" . tr($rec->color) . "</span>";
        }
    }


    /**
     * Подготовка на формата за добавяне
     */
    protected static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        if ($data->form->rec->createdBy == '-1') {
            $data->form->setReadonly('name');
            $data->form->setReadonly('type');
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
        $data->listFilter->FLD('tagId', 'key(mvc=tags_Tags, select=name, allowEmpty)', 'caption=Таг, refreshForm');

        $data->listFilter->view = 'horizontal';
        $data->listFilter->showFields = 'search,tagId';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list', 'show' => Request::get('show')), 'id=filter', 'ef_icon = img/16/funnel.png');

        $tagsArr = tags_Tags::getTagsOptions();
        $data->listFilter->setOptions('tagId', $tagsArr['all']);

        $data->listFilter->input(null, 'silent');

        if ($data->listFilter->rec->tagId) {
            $data->query->where(array("#id = '[#1#]'", $data->listFilter->rec->tagId));
        }

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
            2 => 'type',
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
        if (!$rec->type) {
            $rec->type = 'common';
        }
    }
}
