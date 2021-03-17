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
class tags_Logs extends core_Manager
{
    /**
     * Заглавие на модела
     */
    public $title = 'Логове';


    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, admin';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';


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
     * Кой има право да го изтрие?
     */
    public $canTag = 'powerUser';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'tags_Wrapper, plg_Created, plg_Search, plg_Sorting';


    /**
     * @var string
     */
    public $listFields = 'id, docLink=Документ, tagId, userId';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('docClassId', 'class(interface=doc_DocumentIntf,select=title,allowEmpty)', 'caption=Документ->Клас');
        $this->FLD('docId', 'int', 'caption=Документ->Обект');
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption = Контейнер');
        $this->FLD('tagId', 'key(mvc=tags_Tags, select=name, allowEmpty)', 'caption=Таг, refreshForm');
        $this->FLD('userId', 'user', 'caption=Потребител');

        $this->setDbUnique('docClassId, docId, tagId, userId');
    }


    public static function getTagsFor($docClassId, $docId, $userId = null, $order = true)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }

        $query = self::getQuery();
        $query->where(array("#docClassId = '[#1#]'", $docClassId));
        $query->where(array("#docId = '[#1#]'", $docId));
        $query->where(array("#userId = '[#1#]'", $userId));

        $resArr = array();

        while ($rec = $query->fetch()) {
            $v = self::recToVerbal($rec, 'tagId')->tagId;
            $resArr[$rec->id] = $v;
        }

        if ($order) {
            asort($resArr);
        }

        return $resArr;
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Manager $mvc
     * @param stdClass     $row Това ще се покаже
     * @param stdClass     $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if (cls::load($rec->docClassId)) {
            $inst = cls::get($rec->docClassId);
            if ($inst->haveRightFor('single')) {
                $row->docLink = $inst->getLink($rec->docId, 0);
            }
        }

        if (!$row->docLink) {
            $row->docLink = tr('Липсващ документ');
        }
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * Забранява изтриването на вече използвани сметки
     *
     * @param core_Manager  $mvc
     * @param string        $requiredRoles
     * @param string        $action
     * @param stdClass|NULL $rec
     * @param int|NULL      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'tag') {
            if ($rec && ($requiredRoles != 'no_one')) {
                if ($rec->containerId) {
                    $document = doc_Containers::getDocument($rec->containerId);
                    if (!$document->haveRightFor('single')) {
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
    }


    /**
     * Екшън за редакция на таговете
     *
     * @return Redirect
     */
    function act_Tag()
    {
        $this->requireRightFor('tag');

        $cid = Request::get('id', 'int');

        $document = doc_Containers::getDocument($cid);
        $docClassId = $document->getClassId();
        $docId = $document->that;
        $userId = core_Users::getCurrent();

        $dRec = $document->fetch();

        $this->requireRightFor('tag', $dRec);

        $form = cls::get('core_Form');

        $form->FLD('tags', 'keylist(mvc=tags_Tags, select=name)', 'caption=Маркери');
        $form->title = 'Промяна на маркери на документ';

        $query = self::getQuery();
        $query->where(array("#docClassId = '[#1#]'", $docClassId));
        $query->where(array("#docId = '[#1#]'", $docId));
        $query->where(array("#userId = '[#1#]'", $userId));

        $query->show('tagId');

        $cQuery = clone $query;

        $oldTagArr = array();
        while ($oRec = $query->fetch()) {
            $oldTagArr[$oRec->tagId] = $oRec->tagId;
        }

        // Добавяме в избора само достъпните тагове
        $tQuery = tags_Tags::getQuery();
        $tQuery->where("#state = 'active'");
        $tQuery->in('id', $oldTagArr, false, true);

        $tQuery->where(array("#userOrRole = '[#1#]'", $userId));
        $tQuery->orWhere(array("#userOrRole = '[#1#]'", type_UserOrRole::getAllSysTeamId()));
        $tQuery->in('id', $oldTagArr, false, true);

        $teamsArr = type_Keylist::toArray(core_Users::getUserRolesByType($userId, 'team'));
        foreach ($teamsArr as $teamId) {
            $tQuery->orWhere(array("#userOrRole = '[#1#]'", type_UserOrRole::getSysRoleId($teamId)));
        }
        $tQuery->show('id, name');
        $tagsArr = array();
        while ($tRec = $tQuery->fetch()) {
            $tagsArr[$tRec->id] = $tRec->name;
        }
        $form->setSuggestions('tags', $tagsArr);

        if (!empty($oldTagArr)) {
            $form->setDefault('tags', $oldTagArr);
        }

        $rec = $form->input();

        $retUrl = getRetUrl();
        if (empty($retUrl)) {
            $retUrl = array($document->instance, 'single', $document->that);
        }

        if ($form->isSubmitted()) {

            $tArr = type_Keylist::toArray($rec->tags);
            foreach ($tArr as $tId) {

                if (!$oldTagArr[$tId]) {
                    $rec = new stdClass();
                    $rec->docClassId = $docClassId;
                    $rec->docId = $docId;
                    $rec->tagId = $tId;
                    $rec->userId = $userId;
                    $rec->containerId = $cid;

                    $this->save($rec, null, 'IGNORE');
                } else {
                    unset($oldTagArr[$tId]);
                }
            }

            // Изтрива старите премахнати записи
            if (!empty($oldTagArr)) {
                $cQuery->in('tagId', $oldTagArr);

                while ($oRec = $cQuery->fetch()) {
                    $this->delete($oRec->id);
                }
            }

            return new Redirect($retUrl);
        }

        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png');

        return $form->renderHtml();
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
        $data->listFilter->showFields = 'tagId';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list', 'show' => Request::get('show')), 'id=filter', 'ef_icon = img/16/funnel.png');

        $data->listFilter->input(null, 'silent');

        if ($data->listFilter->rec->tagId) {
            $data->query->where(array("#tagId = '[#1#]'", $data->listFilter->rec->tagId));
        }

        $data->query->orderBy('createdOn', 'DESC');
    }
}
