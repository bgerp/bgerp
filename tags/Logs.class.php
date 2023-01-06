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

        $this->setDbUnique('containerId, tagId, userId');

        $this->setDbIndex('docId, docClassId');
        $this->setDbIndex('containerId');
    }


    /**
     * Добавя ограничение за типа
     *
     * @param $query
     * @param $userId
     */
    protected static function restrictQueryByType(&$query, $userId = null)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }

        $query->EXT('type', 'tags_Tags', 'externalKey=tagId');

        if ($userId) {
            $query->where("#type != 'personal'");
            $query->orWhere(array("#type = 'personal' AND #createdBy = '[#1#]'", $userId));
        }
    }


    /**
     * Помощна функция за вземана на таговете към документи
     *
     * @param mixed $docClassId
     * @param integer $docId
     * @param null|integer $userId
     * @param bool $order
     *
     * @return array
     */
    public static function getTagsFor($docClassId, $docId, $userId = null, $order = true)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }

        $query = self::getQuery();
        $query->where(array("#docId = '[#1#]'", $docId));
        $query->where(array("#docClassId = '[#1#]'", $docClassId));

        self::restrictQueryByType($query, $userId);

        if ($order) {
            $query->orderBy('type', 'DESC');
            $query->EXT('name', 'tags_Tags', 'externalKey=tagId');
            $query->orderBy('name', 'ASC');
        }

        $resArr = array();

        while ($rec = $query->fetch()) {
            $tArr = tags_Tags::getTagNameArr($rec->tagId);

            $resArr[$rec->id]['name'] = $tArr['name'];

            $resArr[$rec->id]['span'] = $tArr['span'];

            $resArr[$rec->id]['spanNoName'] = $tArr['spanNoName'];

            $resArr[$rec->id]['color'] = $tArr['color'];
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
    public static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
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

        if ($fields['-list']) {
            if ($rec->tagId) {
                $tArr = tags_Tags::getTagNameArr($rec->tagId);
                $row->tagId = "<span class='documentTags'>" . $tArr['spanNoName'] . $row->tagId . '</span>';
            }
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
                    try{
                        $document = doc_Containers::getDocument($rec->containerId);
                        if (!$document->haveRightFor('single')) {
                            $requiredRoles = 'no_one';
                        }
                    } catch(core_exception_Expect $e){
                        wp($e, $rec);
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
    }


    /**
     *
     * @param $form
     * @param $cid
     */
    public static function prepareFormForTag(&$form, $cid)
    {
        $document = doc_Containers::getDocument($cid);
        $docClassId = $document->getClassId();
        $docId = $document->that;
        $userId = core_Users::getCurrent();

        $form->FNC('personalTags', 'keylist(mvc=tags_Tags, select=name, select2MinItems=28, columns=2)', 'caption=Тагове->Персонални, class=keylist-wide twoCols, input=input, silent');
        $form->FNC('commonTags', 'keylist(mvc=tags_Tags, select=name, select2MinItems=28, columns=2)', 'caption=Тагове->Общи, class=keylist-wide twoCols, input=input, silent');



        $query = self::getQuery();
        $query->where(array("#docId = '[#1#]'", $docId));
        $query->where(array("#docClassId = '[#1#]'", $docClassId));

        $form->_cQuery = clone $query;

        $query->show('tagId');

        self::restrictQueryByType($query, core_Users::getCurrent());

        $oldTagArr = array();
        while ($oRec = $query->fetch()) {
            $oldTagArr[$oRec->tagId] = $oRec->tagId;
        }

        $form->_oldTagArr = $oldTagArr;
        $tagsArr = tags_Tags::getTagsOptions($oldTagArr, $docClassId);

        $form->setSuggestions('personalTags', $tagsArr['personal']);
        $form->setSuggestions('commonTags', $tagsArr['common']);

        if (!empty($oldTagArr)) {
            $form->setDefault('personalTags', $oldTagArr);
            $form->setDefault('commonTags', $oldTagArr);
        }
    }


    /**
     * @param $form
     * @param $cid
     */
    public static function onSubmitFormForTag($form, $cid)
    {
        $cQuery = $form->_cQuery;
        $oldTagArr = $form->_oldTagArr;

        $document = doc_Containers::getDocument($cid);
        $docClassId = $document->getClassId();
        $docId = $document->that;

        $rec = $form->rec;

        $tArr = type_Keylist::toArray($rec->personalTags) + type_Keylist::toArray($rec->commonTags);

        foreach ($tArr as $tId) {

            if (!$oldTagArr[$tId]) {
                $rec = new stdClass();
                $rec->docClassId = $docClassId;
                $rec->docId = $docId;
                $rec->tagId = $tId;
                $rec->userId = core_Users::getCurrent();
                $rec->containerId = $cid;

                self::save($rec, null, 'IGNORE');
            } else {
                unset($oldTagArr[$tId]);
            }
        }

        // Изтрива старите премахнати записи
        if (!empty($oldTagArr)) {
            $cQuery->in('tagId', $oldTagArr);

            $cu = core_Users::getCurrent();

            while ($oRec = $cQuery->fetch()) {

                $tagType = tags_Tags::fetchField($oRec->tagId, 'type');

                if ($tagType == 'personal') {
                    if ($oRec->createdBy != $cu) {

                        continue;
                    }
                }

                $containerId = $oRec->containerId;

                self::delete($oRec->id);

                self::clearCache($containerId);
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

        $dRec = $document->fetch();

        $this->requireRightFor('tag', $dRec);

        $form = cls::get('core_Form');

        $form->title = 'Промяна на таговете на документ';

        $this->prepareFormForTag($form, $cid);

        $rec = $form->input();

        $retUrl = getRetUrl();
        if (empty($retUrl)) {
            $retUrl = array($document->instance, 'single', $document->that);
        }

        if ($form->isSubmitted()) {

            $this->onSubmitFormForTag($form, $cid);

            doc_Containers::logWrite('Промяна на таг', $cid);

            return new Redirect($retUrl);
        }

        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png');

        return $form->renderHtml();
    }


    /**
     * Изпълнява се след запис на документ
     *
     * @param accda_Da $mvc
     * @param integer $id
     * @param stdClass $rec
     * @param null|string|array $fields
     */
    public static function on_AfterSave($mvc, &$id, $rec, $fields = null)
    {
        if ($rec->containerId) {
            $mvc->clearCache($rec->containerId);
        }
    }


    /**
     * Изчистване на кеша
     *
     * @param $containerId
     */
    public static function clearCache($containerId)
    {
        bgerp_Portal::invalidateCache(null, 'doc_drivers_FolderPortal');
        bgerp_Portal::invalidateCache(null, 'doc_drivers_LatestDocPortal');
        bgerp_Portal::invalidateCache(null, 'bgerp_drivers_Recently');
        bgerp_Portal::invalidateCache(null, 'bgerp_drivers_Tasks');
        bgerp_Portal::invalidateCache(null, 'bgerp_drivers_Calendar');

        doc_DocumentCache::cacheInvalidation($containerId);
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

        $tagsArr = tags_Tags::getTagsOptions();
        $data->listFilter->setOptions('tagId', $tagsArr['all']);

        $data->listFilter->input(null, 'silent');

        if ($data->listFilter->rec->tagId) {
            $data->query->where(array("#tagId = '[#1#]'", $data->listFilter->rec->tagId));
        }

        $data->query->orderBy('createdOn', 'DESC');
    }


    /**
     * Помощна функция за вземана на таговете към документи
     *
     * @param array $arr
     * @param null|integer $userId
     * @param bool $order
     *
     * @return array
     */
    public static function getTagsFromContainers($arr, $userId = null, $order = true)
    {
        $arr = arr::make($arr, true);
        $userId = isset($userId) ? $userId : core_Users::getCurrent();
        $query = self::getQuery();
        if(countR($arr)){
            $query->in("containerId", $arr);
            self::restrictQueryByType($query, $userId);
        } else {
            $query->where("1=2");
        }

        if ($order) {
            $query->orderBy('type', 'DESC');
            $query->EXT('name', 'tags_Tags', 'externalKey=tagId');
            $query->orderBy('name', 'ASC');
        }

        $resArr = $tagCache = array();
        while ($rec = $query->fetch()) {

            // За всеки от изброените документи се групират таговете
            if(!array_key_exists($rec->tagId, $tagCache)){
                $tagCache[$rec->tagId] = tags_Tags::getTagNameArr($rec->tagId);
            }
            $resArr[$rec->containerId][$rec->id]['name'] = $tagCache[$rec->tagId]['name'];
            $resArr[$rec->containerId][$rec->id]['span'] = $tagCache[$rec->tagId]['span'];
            $resArr[$rec->containerId][$rec->id]['spanNoName'] = $tagCache[$rec->tagId]['spanNoName'];
            $resArr[$rec->containerId][$rec->id]['color'] = $tagCache[$rec->tagId]['color'];
        }

        return $resArr;
    }
}
