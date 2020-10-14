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
    public $maxCnt;
    
    
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
        $eStr = 'enum(';
        for($i=1;$i<=20;$i++) {
            $eStr .= $i . ',';
        }
        $eStr = rtrim($eStr, ',');
        $eStr .= ')';
        
        $fieldset->FLD('tCnt', $eStr, 'caption=Брой нишки, mandatory, removeAndRefreshForm, mandatory');
        $fieldset->FLD('threads', 'keylist(mvc=doc_Threads)', 'caption=Нишки, input=none');
        
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
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm($Driver, embed_Manager $Embedder, &$data)
    {
        $data->form->setDefault('tCnt', 1);
        
        $data->form->input('tCnt');
        
        if ($data->form->rec->tCnt) {
            
            $tArr = array();
            if (!$data->form->cmd && $data->form->rec->id) {
                $tArr = type_Keylist::toArray($data->form->rec->threads);
            }
            
            // Показваме функционални полета за попълване на нишки
            for($i=1; $i<=$data->form->rec->tCnt; $i++) {
                $tName = $Driver->getThreadFncFieldName($i);
                $data->form->FNC($tName, 'key2(mvc=doc_Threads, restrictViewAccess=yes, allowEmpty)',"caption=Нишка->№|* {$i}, input");
                
                // Ако се редактира и има записи, задаваме ги като по попдразбиране
                if (!empty($tArr)) {
                    $data->form->setDefault($tName, array_shift($tArr));
                }
            }
        }
    }
    
    
    /**
     * Помощна функция за вземане на името на полето
     * 
     * @param integer $i
     * 
     * @return string
     */
    protected function getThreadFncFieldName($i)
    {
        
        return '_threadId' . $i;
    }
    
    
    /**
     * Подготвя групите, в които да бъде вкаран продукта
     */
    public static function on_BeforeSave($Driver, &$Embedder, &$id, &$rec, $fields = null)
    {
        $tArr = array();
        
        // Функционалните полета ги вкарваме в keylist за да се запишат в модела
        for($i=1; $i<=$rec->tCnt; $i++) {
            
            $tName = $Driver->getThreadFncFieldName($i);
            $tVal = $rec->{$tName};
            
            if (!$tVal) {
                continue;
            }
            
            $tArr[$tVal] = $tVal;
            
        }
        $rec->threads = type_Keylist::fromArray($tArr);
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
        
        $tArr = type_Keylist::toArray($dRec->threads);
        
        foreach ($tArr as $tKey => $tVal) {
            if (!doc_Threads::haveRightFor('single', $tVal)) {
                unset($tArr[$tKey]);
            }
        }
        
        $resData->cacheKey = $this->getCacheKey($dRec, $userId);
        $resData->cacheType = $this->getCacheTypeName($userId);
        
        $resData->tpl = core_Cache::get($resData->cacheType, $resData->cacheKey);
        
        if (!$resData->tpl) {
            
            $resData->data = new stdClass();
            
            $tQuery = doc_Threads::getQuery();
            
            // Ако няма избрана достъпна нишка, да не показва всичките
            if (empty($tArr)) {
                $tQuery->where("1=2");
            }
            
            $tQuery->in('id', $tArr);
            
            $tQuery->orderBy('last', 'DESC');
            $tQuery->orderBy('id', 'DESC');
            
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
                        $last = doc_Containers::fetchField($tRec->firstContainerId, 'createdOn');
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
                    
                    $t = "<div class='portalLatestThreads state-{$tRec->state} {$tUnsighted}'>" . doc_Containers::getLinkForObject($lRec);
                    if (--$cnt > 0) {
                        $t .=  ' + ' . tr('още') . ' ' .  $cnt;
                    }
                    $t .= "</div>";
                    
                    $data->res->append($t);
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
            
            $data->tpl = new ET(tr('|*<div class="clearfix21 portal"> <div class="legend">|Най-новото|*</div><div style="text-align: center"> [#LATEST#]</div></div>'));
            
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
        
        $tArr = type_Keylist::toArray($dRec->threads);
        
        foreach ($tArr as $tId) {
            if (!$tId) {
                
                continue;
            }
            $cArr[] = doc_Threads::fetchField($tId, 'last');
        }
        
        return md5(implode('|', $cArr));
    }
}
