<?php


/**
 * Драйвер за показване на нотификациите
 *
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Известия
 */
class bgerp_drivers_Notifications extends core_BaseClass
{
    /**
     * Максимален брой блокове, които да могат да се поакзват в портала
     */
    public $maxCnt = 1;
    
    
    public $interfaces = 'bgerp_PortalBlockIntf';
    
    
    /**
     * Колко време да се показват в началото, вече отворените известия
     */
    protected $showOpenTopTime = 180;
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('perPage', 'int(min=1, max=50)', 'caption=Редове, mandatory');
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
        if (empty($userId)) {
            expect($userId = core_Users::getCurrent());
        }
        
        $resData = new stdClass();
        
        $Notifications = cls::get('bgerp_Notifications');
        
        $resData->cacheKey = $this->getCacheKey($dRec, $userId);
        $resData->cacheType = $this->getCacheTypeName($userId);
        
        $resData->tpl = core_Cache::get($resData->cacheType, $resData->cacheKey);
        
        if (!$resData->tpl) {
            
            $modifiedBefore = dt::subtractSecs($this->showOpenTopTime);
            
            $Notifications->searchInputField = bgerp_Portal::getPortalSearchInputFieldName($Notifications->searchInputField, $dRec->originIdCalc);
            
            // Създаваме обекта $data
            $data = new stdClass();
            
            // Създаваме заявката
            $data->query = $Notifications->getQuery();
            
            $data->query->show('msg,state,userId,priority,cnt,url,customUrl,modifiedOn,modifiedBy,searchKeywords');
            
            // Подготвяме полетата за показване
            $data->listFields = 'modifiedOn=Време,msg=Съобщение';
            
            $data->query->where("#userId = {$userId} AND #hidden != 'yes'");
            
            $data->query->XPR('modifiedOnTop', 'datetime', "IF((((#modifiedOn >= '{$modifiedBefore}') || (#state = 'active') || (#lastTime >= '{$modifiedBefore}'))), IF(((#state = 'active') || (#lastTime > #modifiedOn)), #modifiedOn, #lastTime), NULL)");
            $data->query->orderBy('modifiedOnTop', 'DESC');
            
            $data->query->orderBy('modifiedOn=DESC');
            
            if (Mode::is('screenMode', 'narrow') && !Request::get('noticeSearch')) {
                $data->query->where("#state = 'active'");
                
                // Нотификациите, модифицирани в скоро време да се показват
                $data->query->orWhere("#modifiedOn >= '{$modifiedBefore}'");
                $data->query->orWhere("#lastTime >= '{$modifiedBefore}'");
            }
            
            // Подготвяме филтрирането
            $Notifications->prepareListFilter($data);
            
            $data->listFilter->showFields = $Notifications->searchInputField;
            bgerp_Portal::prepareSearchForm($Notifications, $data->listFilter);
            
            $Notifications->listItemsPerPage = $dRec->perPage ? $dRec->perPage : 20;
            
            // Подготвяме навигацията по страници
            $Notifications->prepareListPager($data);
            
            $data->pager->pageVar = $this->getPageVar($dRec->originIdCalc);
            
            // Подготвяме записите за таблицата
            $Notifications->prepareListRecs($data);
            
            // Подготвяме редовете на таблицата
            $Notifications->prepareListRows($data);
            
            // Подготвяме заглавието на таблицата
            $data->title = tr('Известия');
            
            // Подготвяме лентата с инструменти
            $Notifications->prepareListToolbar($data);
            
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
            $Notifications = cls::get('bgerp_Notifications');
            
            // Рендираме изгледа
            $data->tpl = $this->renderPortal($data->data);
            
            $cacheLifetime = doc_Setup::get('CACHE_LIFETIME') ? doc_Setup::get('CACHE_LIFETIME') : 5;
            
            $data->tpl->push('js/PortalSearch.js', 'JS');
            jquery_Jquery::run($data->tpl, 'portalSearch();', true);
            jquery_Jquery::runAfterAjax($data->tpl, 'portalSearch');
            
            core_Cache::set($data->cacheType, $data->cacheKey, $data->tpl, $cacheLifetime);
        }
        
        //Задаваме текущото време, за последно преглеждане на нотификациите
        Mode::setPermanent('lastNotificationTime', time());
        
        return $data->tpl;
    }
    
    
    /**
     * Рендира портала
     */
    protected function renderPortal($data)
    {
        $Notifications = cls::get('bgerp_Notifications');
        
        $tpl = new ET("
            <div class='clearfix21 portal'>
            <div class='legend'><div style='float:left'>[#PortalTitle#]</div>
            [#ListFilter#]<div class='clearfix21'></div></div>
                        
            <div>
                <!--ET_BEGIN PortalTable-->
                    [#PortalTable#]
                <!--ET_END PortalTable-->
            </div>
            
            [#PortalPagerBottom#]
            </div>
        ");
        
        // Попълваме титлата
        if (!Mode::is('screenMode', 'narrow')) {
            $tpl->append($data->title, 'PortalTitle');
        }
        
        if ($data->listFilter) {
            $formTpl = $data->listFilter->renderHtml();
            $formTpl->removeBlocks();
            $formTpl->removePlaces();
            $tpl->append($formTpl, 'ListFilter');
        }
        
        // Попълваме долния страньор
        $tpl->append($Notifications->renderListPager($data), 'PortalPagerBottom');
        
        // Попълваме таблицата с редовете
        $tpl->append($Notifications->renderListTable($data), 'PortalTable');
        jquery_Jquery::runAfterAjax($tpl, 'getContextMenuFromAjax');
        
        return $tpl;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param bgerp_drivers_Recently $Driver
     *                                         $Driver
     * @param embed_Manager          $Embedder
     * @param stdClass               $data
     */
    protected static function on_AfterPrepareEditForm($Driver, embed_Manager $Embedder, &$data)
    {
        $data->form->setDefault('perPage', 20);
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
        return tr('Известия');
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
        
        return 'Portal_Notifications_' . $userId;
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
        
        $pageVar = $this->getPageVar($dRec->originIdCalc);
        $pageVarVal = Request::get($pageVar);
        $pageVarVal = isset($pageVarVal) ? $pageVarVal : 1;
        $cArr[] = $pageVarVal;
        
        $Notifications = cls::get('bgerp_Notifications');
        $sVal = bgerp_Portal::getPortalSearchInputFieldName($Notifications->searchInputField, $dRec->originIdCalc);
        $nSearchVal = Request::get($sVal);
        $nSearchVal = isset($nSearchVal) ? $nSearchVal : '';
        $cArr[] = $nSearchVal;
        
        // Намираме времето на последния запис
        $query = bgerp_Notifications::getQuery();
        $query->where("#userId = ${userId}");
        $query->limit(1);
        
        $cQuery = clone $query;
        
        $query->XPR('modifiedOnTop', 'datetime', 'IF((#modifiedOn > #lastTime), #modifiedOn, #lastTime)');
        $query->orderBy('#modifiedOnTop', 'DESC');
        
        $lastRec = $query->fetch();
        $lRecModifiedOnTop = $lastRec->modifiedOnTop;
        
        $now = dt::now();
        
        // Ако времето на промяна съвпада с текущото
        if ($lRecModifiedOnTop >= $now) {
            $lRecModifiedOnTop = dt::subtractSecs(5, $now);
        }
        
        $lastModifiedOnKey = $lRecModifiedOnTop;
        $lastModifiedOnKey .= '|' . $lastRec->id;
        
        // Инвалидиране на кеша след 5 минути
        $lastModifiedOnKey .= '|' . (int) (dt::mysql2timestamp($lRecModifiedOnTop) / 300);
        
        $modifiedBefore = dt::subtractSecs($this->showOpenTopTime);
        
        // Инвалидиране на кеша след запазване на подредбата - да не стои запазено до следващото инвалидиране
        $cQuery->where(array("#modifiedOn > '[#1#]'", $modifiedBefore));
        $cQuery->orWhere(array("#lastTime > '[#1#]'", $modifiedBefore));
        $cQuery->limit(1);
        $cQuery->orderBy('modifiedOn', 'DESC');
        $cQuery->orderBy('lastTime', 'DESC');
        if ($cLastRec = $cQuery->fetch()) {
            $lRecLastTime = $lastRec->lastTime;
            
            // Ако времето на промяна съвпада с текущото
            if ($lRecLastTime >= $now) {
                $lRecLastTime = dt::subtractSecs(5, $now);
            }
            
            $lastModifiedOnKey .= '|' . $lRecLastTime;
            $lastModifiedOnKey .= '|' . $cLastRec->id;
        }
        
        $cArr[] = $lastModifiedOnKey;
        
        return md5(implode('|', $cArr));
    }
    
    
    /**
     * Помощна функция за вземане на името за страниране
     *
     * @param integer $oIdCalc
     * @return string
     */
    protected function getPageVar($oIdCalc)
    {
        return 'P_' . get_called_class() . '_' . $oIdCalc;
    }
}
