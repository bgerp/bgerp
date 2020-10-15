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
     * Максимален брой блокове, които да могат да се поакзват в портала
     */
    public $maxCnt = 1;
    
    
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
        
        if (!$resData->tpl) {
            
            $tCnt = $dRec->tCnt ? $dRec->tCnt : 20;
            
            $resData->data = new stdClass();
            
            $tQuery = doc_Threads::getQuery();
            doc_Threads::restrictAccess($tQuery, $userId);
            $tQuery->orderBy('last', 'DESC');
            $tQuery->orderBy('id', 'DESC');
            $tQuery->show('id, folderId, firstContainerId, state');
            $tQuery->limit($tCnt);
            
            $resArr = array();
            while ($tRec = $tQuery->fetch()) {
                $resArr[$tRec->folderId][$tRec->id] = $tRec;
            }
            
            $data = new stdClass();
            $data->query = $tQuery;
            $data->res = new ET();
            
            foreach ($resArr as $fId => $tArr) {
                $f = "<div class='portalLatestFoldes'>" . doc_Folders::getLink($fId) . "</div>";
                $data->res->append($f);
                foreach ($tArr as $tId => $tRec) {
                    $tUnsighted = '';
                    $cnt = 0;
                    
                    $cQuery = doc_Containers::getQuery();
                    $cQuery->where(array("#threadId = '[#1#]'", $tId));
                    $cQuery->where("#state != 'rejected'");
                    
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
                        
                        $cnt = $cQuery->count();
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
                        $title = str::limitLen($title, 50);
                        $t = "<div class='portalLatestThreads state-{$tRec->state} {$tUnsighted}'>" . ht::createLink($title, $doc->getSingleUrlArray(), null, array('ef_icon' => $doc->getIcon()));
                        if (--$cnt > 0) {
                            $t .=  ' + ' . tr('още') . ' ' .  $cnt;
                        }
                        $t .= "</div>";
                        
                        $data->res->append($t);
                    } catch (core_exception_Expect $e) {
                        continue;
                    }
                }
            }
            
            $resData->data = $data;
        }
        
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
            
            $data->tpl = new ET(tr('|*<div class="clearfix21 portal"> <div class="legend">|Най-новото|*</div><div class="portalNews"> [#LATEST#]</div></div>'));
            
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
     * @param integer $userId
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
     * @param null|integer $userId
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
        doc_Threads::restrictAccess($tQuery, $userId);
        
        $tQuery->orderBy('last', 'DESC');
        $tQuery->orderBy('id', 'DESC');
        $tQuery->show('last, id');
        $tQuery->limit(1);
        
        if ($tRec = $tQuery->fetch()) {
            $cArr[] = $tRec->id;
            $cArr[] = $tRec->last;
        }
        
        $tArr = type_Keylist::toArray($dRec->threads);
        
        return md5(implode('|', $cArr));
    }
}
