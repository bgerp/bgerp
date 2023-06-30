<?php


/**
 * Драйвер за най-новите нишки в папките
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Най-новото
 */
class doc_drivers_LatestDocPortal extends core_BaseClass
{
    /**
     * Интерфейси
     */
    public $interfaces = 'bgerp_PortalBlockIntf';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('tCnt', 'int(min=1, max=25)', 'caption=Брой нишки, mandatory');
        $fieldset->FLD('docClassId', 'classes(interface=doc_DocumentIntf, select=title, allowEmpty)', 'caption=Първи документ в нишката->Вид');
        $fieldset->FLD('tags', 'keylist(mvc=tags_Tags, select=name, allowEmpty)', 'caption=Маркери в документите->Маркер');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm($Driver, embed_Manager $Embedder, &$data)
    {
        $data->form->setDefault('tCnt', 20);
    }


    /**
     * След вербализирането на данните
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param stdClass          $row
     * @param stdClass          $rec
     * @param array             $fields
     */
    protected static function on_AfterRecToVerbal($Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
        $row->tags = tags_Tags::decorateTags($rec->tags);
    }
    
    
    /**
     * Може ли вградения обект да се избере
     *
     * @param NULL|int $userId
     *
     * @return bool
     */
    public function canSelectDriver($userId = null)
    {
        return true;
    }
    
    
    /**
     * Подготвя данните
     *
     * @param stdClass $dRec
     * @param null|int $userId
     *
     * @return stdClass
     */
    public function prepare($dRec, $userId = null)
    {
        $resData = new stdClass();
        
        if (empty($userId)) {
            expect($userId = core_Users::getCurrent());
        }
        
        $resData->cacheKey = $this->getCacheKey($dRec, $userId);
        $resData->cacheType = $this->getCacheTypeName($userId);
        
        $resData->tpl = core_Cache::get($resData->cacheType, $resData->cacheKey);

        $containerArr = array();

        if (!$resData->tpl) {
            $tCnt = $dRec->tCnt ? $dRec->tCnt : 20;
            $resData->data = new stdClass();

            $resArr = array();

            if ($dRec->tags) {
                $cQuery = doc_Containers::getQuery();
                $cQuery->where("#state != 'rejected'");
                if ($dRec->docClassId) {
                    $cQuery->in('docClass', type_Keylist::toArray($dRec->docClassId));
                }

                $tagsArr = type_Keylist::toArray($dRec->tags);

                $personalTags = tags_Tags::getPersonalTags($tagsArr, false);

                if (!empty($personalTags)) {
                    foreach ($personalTags as $tagId) {
                        unset($tagsArr[$tagId]);
                    }
                }

                $cQuery->EXT('tags', 'tags_Logs', 'externalName=tagId, remoteKey=containerId');

                $orIn = false;
                if (!empty($tagsArr)) {
                    $cQuery->in('tags', $tagsArr);
                    $orIn = true;
                }

                if (!empty($personalTags)) {
                    $cQuery->EXT('tagsCreatedBy', 'tags_Logs', 'externalName=createdBy, remoteKey=containerId');

                    // Ескейпване на стойности
                    array_walk($personalTags, function (&$a) {
                        $a = "'" . $a . "'";
                    });

                    // Обръщане на масива в стринг
                    $personalTagsVal = implode(',', $personalTags);

                    $cQuery->where(array("(#tags IN ($personalTagsVal) AND #tagsCreatedBy = '[#1#]')", $userId), $orIn);
                }

                $cQuery->EXT('tagsCreatedOn', 'tags_Logs', 'externalName=createdOn, remoteKey=containerId');
                $cQuery->orderBy('tagsCreatedOn', 'DESC');

                $cQuery->limit(min(50 * $tCnt, 1000));

                $cQuery->show('id, folderId, threadId, tags');

                $cQuery->orderBy('modifiedOn', 'DESC');
                $cQuery->orderBy('id', 'DESC');

                while ($cRec = $cQuery->fetch()) {
                    $doc = doc_Containers::getDocument($cRec->id);

                    if (!$doc->haveRightFor('single')) {

                        continue;
                    }

                    if ($resArr[$cRec->folderId][$cRec->threadId . '|' . $cRec->id]) {

                        continue;
                    }

                    $resArr[$cRec->folderId][$cRec->threadId . '|' . $cRec->id] = doc_Threads::fetch($cRec->threadId);

                    $containerArr[$cRec->threadId . '|' . $cRec->id][$cRec->id] = $cRec->id;

                    if (!--$tCnt) {
                        break;
                    }
                }
            } else {
                $tQuery = doc_Threads::getQuery();
                $tQuery->orderBy('last', 'DESC');
                $tQuery->orderBy('id', 'DESC');
                $tQuery->show('id, folderId, firstContainerId, state, folderId, shared');
                if ($dRec->docClassId) {
                    $tQuery->EXT('docClass', 'doc_Containers', 'externalName=docClass,externalKey=firstContainerId');
                    $tQuery->in('docClass', type_Keylist::toArray($dRec->docClassId));
                }

                $tQuery->where("#state != 'rejected'");

                $tQuery->limit(min(50 * $tCnt, 1000));

                while ($tRec = $tQuery->fetch()) {
                    if (!doc_Threads::haveRightFor('single', $tRec)) {
                        continue;
                    }

                    $resArr[$tRec->folderId][$tRec->id] = $tRec;

                    if (!--$tCnt) {
                        break;
                    }
                }
            }

            $data = new stdClass();
            $data->res = new ET();
            
            foreach ($resArr as $fId => $tArr) {
                $docRowArr = array();
                foreach ($tArr as $tIdOrig => $tRec) {
                    list($tId) = explode('|', $tIdOrig);

                    $tUnsighted = '';

                    $cQuery = doc_Containers::getQuery();
                    $cQuery->where(array("#threadId = '[#1#]'", $tId));
                    $cQuery->where("#state != 'rejected'");

                    if (!empty($containerArr[$tIdOrig])) {
                        $cQuery->in('id', $containerArr[$tIdOrig]);
                    }

                    // Вземаме последното вижда не нишката от текущия потребител
                    $rQuery = bgerp_Recently::getQuery();
                    $rQuery->where(array("#threadId = '[#1#]'", $tId));
                    $rQuery->where(array("#userId = '[#1#]'", core_Users::getCurrent()));
                    $rQuery->limit(1);
                    $rQuery->orderBy('last', 'DESC');
                    $rQuery->show('last');
                    $rRec = $rQuery->fetch();
                    $last = $rRec->last;
                    
                    $cloneQ = clone $cQuery;
                    
                    // Ако нишката никога не е виждана
                    if (!$last) {
                        try {
                            $last = doc_Containers::fetchField($tRec->firstContainerId, 'createdOn');
                        } catch (core_exception_Expect $e) {
                            continue;
                        }
                        
                        $last = dt::subtractSecs(1, $last);
                    }
                    
                    // Ако има документ, който е добавен след последното разглеждане на нишката
                    if ($last) {
                        $cQuery->orderBy('createdOn', 'ASC');
                        $cQuery->orderBy('id', 'ASC');
                        $cQuery->where(array("#createdOn > '[#1#]'", $last));
                        $cQuery->where("#state != 'draft'");
                        $cQuery->limit(1);
                    }
                    
                    $lRec = $cQuery->fetch();
                    
                    // Ако няма нов документ, линка да сочи към последно модифицирания
                    if (!$lRec) {
                        $cloneQ->orderBy('modifiedOn', 'DESC');
                        $cloneQ->orderBy('id', 'DESC');
                        $cloneQ->limit(1);
                        $lRec = $cloneQ->fetch();
                    } else {
                        $tUnsighted = 'tUnsighted';
                    }
                    
                    try {
                        $doc = doc_Containers::getDocument($lRec->id);
                        $dRow = $doc->getDocumentRow();

                        $title = $dRow->recTitle ? $dRow->recTitle: $dRow->title;
                        $title = trim($title);
                        if (!$title) {
                            $title = '[' . tr('Липсва заглавие') . ']';
                        }

                        $subTitle = $dRow->subTitle;
                        $subTitleClass = 'withoutSubtitle';
                        if (strip_tags($subTitle)) {
                            $subTitleClass = '';
                        }

                        $dRowStr = "<div class='portalLatestThreads state-{$tRec->state} {$tUnsighted} {$subTitleClass}'>" . ht::createLink(str::limitLen($title, 50), $doc->getSingleUrlArray(), null, array('ef_icon' => $doc->getIcon())) . '</div>';
                        if ($subTitle) {
                            $dRowStr .= "<div class='threadSubTitle {$subTitleClass}'>{$subTitle}</div>";
                        }

                        $docRowArr[] = $dRowStr;
                    } catch (core_exception_Expect $e) {
                        continue;
                    }
                }
                
                if (!empty($docRowArr)) {
                    $data->res->append("<div class='portalLatestFoldes'>" . doc_Folders::getLink($fId) . '</div>');
                    
                    foreach ($docRowArr as $dRow) {
                        $data->res->append($dRow);
                    }
                }
            }
            
            $resData->data = $data;
        }

        $resData->blockTitle = '|*' . tags_Tags::decorateTags($dRec->tags, "<span class='portalHeaderTitle'>|Най-новото|*</span>");

        return $resData;
    }
    
    
    /**
     * Рендира данните
     *
     * @param stdClass $data
     *
     * @return core_ET
     */
    public function render($data)
    {
        if (!$data->tpl) {

            $data->tpl = new ET(tr('|*<div class="clearfix21 portal"> <div class="legend">' . $data->blockTitle . '</div><div class="portalNews"> [#LATEST#]</div></div>'));
            
            $data->tpl->replace($data->data->res, 'LATEST');
            
            $cacheLifetime = doc_Setup::get('CACHE_LIFETIME') ? doc_Setup::get('CACHE_LIFETIME') : 5;
            
            core_Cache::set($data->cacheType, $data->cacheKey, $data->tpl, $cacheLifetime);
        }
        
        return $data->tpl;
    }
    
    
    /**
     * Връща заглавието за таба на съответния блок
     *
     * @param stdClass $dRec
     *
     * @return string
     */
    public function getBlockTabName($dRec)
    {
        return tr('Най-новото');
    }
    
    
    /**
     * Името на стойността за кеша
     *
     * @param int $userId
     *
     * @return string
     */
    public function getCacheTypeName($userId = null)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        return 'Portal_Latest_Doc_' . $userId;
    }
    
    
    /**
     * Помощна функция за вземане на ключа за кеша
     *
     * @param stdClass $dRec
     * @param null|int $userId
     *
     * @return string
     */
    public function getCacheKey($dRec, $userId = null)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        $cArr = bgerp_Portal::getPortalCacheKey($dRec, $userId);
        
        $tQuery = doc_Threads::getQuery();
        
        $tQuery->orderBy('last', 'DESC');
        $tQuery->orderBy('id', 'DESC');
        $tQuery->show('last, id, firstContainerId');
        $tQuery->limit(1);

        if ($dRec->tags) {
            $tagQuery = tags_Logs::getQuery();
            $tagQuery->in('tagId', $dRec->tags);
            $tagQuery->orderBy('id', 'DESC');

            $tagQuery->show('createdOn, id, containerId');
            $tagQuery->limit(1);
            if ($tagRec = $tagQuery->fetch()) {
                $cArr[] = $tagRec->id;
                $cArr[] = $tagRec->createdOn;
                $cArr[] = $tagRec->containerId;
            }
        }


        if ($tRec = $tQuery->fetch()) {
            $cArr[] = $tRec->id;
            $cArr[] = $tRec->last;
            $cArr[] = $tRec->firstContainerId;
        }
        
        $tArr = type_Keylist::toArray($dRec->threads);
        
        return md5(implode('|', $cArr));
    }
}
