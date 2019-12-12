<?php


/**
 * Драйвер за показване на последните документи
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
 * @title     Последно
 */
class bgerp_drivers_Recently extends core_BaseClass
{
    
	
    /**
     * Максимален брой блокове, които да могат да се поакзват в портала
     */
    public $maxCnt = 1;
    
    
    public $interfaces = 'bgerp_PortalBlockIntf';
    
    
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
        $resData = new stdClass();
        
        if (empty($userId)) {
            expect($userId = core_Users::getCurrent());
        }
        
        $resData->cacheKey = $this->getCacheKey($dRec, $userId);
        $resData->cacheType = $this->getCacheTypeName($userId);
        
        $resData->tpl = core_Cache::get($resData->cacheType, $resData->cacheKey);
        
        if (!$resData->tpl) {
            
            $Recently = cls::get('bgerp_Recently');
            
            $Recently->searchInputField = bgerp_Portal::getPortalSearchInputFieldName($Recently->searchInputField, $dRec->originIdCalc);
            
            // Създаваме обекта $data
            $data = new stdClass();
            
            // Създаваме заявката
            $data->query = $Recently->getQuery();
            
            // Подготвяме полетата за показване
            $data->listFields = 'last,title';
            
            $data->query->where("#userId = {$userId} AND #hidden != 'yes'");
            $data->query->orderBy('last=DESC');
            
            // Подготвяме филтрирането
            $Recently->prepareListFilter($data);
            
            $data->listFilter->showFields = $Recently->searchInputField;
            bgerp_Portal::prepareSearchForm($Recently, $data->listFilter);
            
            $Recently->listItemsPerPage = $dRec->perPage ? $dRec->perPage : 15;
            
            $data->usePortalArrange = false;
            
            // Подготвяме навигацията по страници
            $Recently->prepareListPager($data);
            
            $data->pager->pageVar = $this->getPageVar($dRec->originIdCalc);
            
            // Подготвяме записите за таблицата
            $Recently->prepareListRecs($data);
            
            // Подготвяме редовете на таблицата
            $Recently->prepareListRows($data);
            
            if (!Mode::is('screenMode', 'narrow')) {
                // Подготвяме заглавието на таблицата
                $data->title = tr('Последно||Recently');
            }
            
            // Подготвяме лентата с инструменти
            $Recently->prepareListToolbar($data);
            
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
            
            // Рендираме изгледа
            $data->tpl = $this->renderPortal($data->data);
            
            $data->tpl->push('js/PortalSearch.js', 'JS');
            jquery_Jquery::run($data->tpl, 'portalSearch();', true);
            jquery_Jquery::runAfterAjax($data->tpl, 'portalSearch');
            
            $cacheLifetime = doc_Setup::get('CACHE_LIFETIME') ? doc_Setup::get('CACHE_LIFETIME') : 5;
            
            core_Cache::set($data->cacheType, $data->cacheKey, $data->tpl, $cacheLifetime);
        }
        
        return $data->tpl;
    }
    
    
    /**
     * Рендира блок в портала с последните документи и папки, посетени от даден потребител
     */
    public function renderPortal($data)
    {
        $Recently = cls::get('bgerp_Recently');
        
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
        $tpl->append($data->title, 'PortalTitle');
        
        if ($data->listFilter) {
            $tpl->append($data->listFilter->renderHtml(), 'ListFilter');
        }
        
        // Попълваме долния страньор
        $tpl->append($Recently->renderListPager($data), 'PortalPagerBottom');
        
        // Попълваме таблицата с редовете
        $tpl->append($Recently->renderListTable($data), 'PortalTable');
        
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
        $data->form->setDefault('perPage', 10);
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
        
        return tr('Последно');
    }
    
    
//     $resData->cacheKey = '_' . Request::get($Recently->searchInputField) );
    /**
     * Помощна функция за вземане на ключа за кеша
     *
     * @param stdClass $dRec
     * @param null|integer $userId
     *
     * @return string
     */
    protected function getCacheKey($dRec, $userId = null)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        $Recently = cls::get('bgerp_Recently');
        
        $cArr = bgerp_Portal::getPortalCacheKey($dRec, $userId);
        
        // Намираме времето на последния запис
        $query = $Recently->getQuery();
        $query->where(array("#userId = '[#1#]'", $userId));
        $query->limit(1);
        $query->orderBy('#last', 'DESC');
        $query->show('last');
        $lastRec = $query->fetch();
        if ($lastRec) {
            $cArr[] = $lastRec->last;
        }
        
        $pageVar = $this->getPageVar($dRec->originIdCalc);
        $pageVarVal = Request::get($pageVar);
        $pageVarVal = isset($pageVarVal) ? $pageVarVal : 1;
        $cArr[] = $pageVarVal;
        
        $sVal = bgerp_Portal::getPortalSearchInputFieldName($Recently->searchInputField, $dRec->originIdCalc);
        $nSearchVal = Request::get($sVal);
        $nSearchVal = isset($nSearchVal) ? $nSearchVal : '';
        $cArr[] = $nSearchVal;
        
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
    
    
    /**
     * Името на стойността за кеша
     *
     * @param integer $oIdCalc
     *
     * @return string
     */
    protected function getCacheTypeName($userId = null)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        return 'Portal_RecentDoc_' . $userId;
    }
}
