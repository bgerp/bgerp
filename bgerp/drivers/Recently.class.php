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
 * @title     Последни документи и папки
 */
class bgerp_drivers_Recently extends core_BaseClass
{
    public $interfaces = 'bgerp_PortalBlockIntf';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('pages', 'int(min=1, max=50)', 'caption=Страници, mandatory');
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
     * @param null|integer $userId
     *
     * @return stdClass
     */
    public function prepare($dRec, $userId = null)
    {
        $resData = new stdClass();
        
        if (empty($userId)) {
            expect($userId = core_Users::getCurrent());
        }
        
        $Recently = cls::get('bgerp_Recently');
        
        // Намираме времето на последния запис
        $query = $Recently->getQuery();
        $query->where(array("#userId = '[#1#]'", $userId));
        $query->limit(1);
        $query->orderBy('#last', 'DESC');
        $lastRec = $query->fetch();
        $resData->cacheKey = md5($dRec->pages . '_' . $userId . '_' . Request::get('ajax_mode') . '_' . Mode::get('screenMode') . '_' . Request::get('P_bgerp_Recently') . '_' . Request::get('recentlySearch') . '_' . core_Lg::getCurrent());
        $resData->cacheType = 'RecentDoc';
        
        list($resData->tpl, $lastCreatedOn) = core_Cache::get($resData->cacheType, $resData->cacheKey);
        
        $resData->lastRecLast = $lastRec->last;
        
        if (!$resData->tpl || $lastCreatedOn != $resData->lastRecLast) {
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
            
            $Recently->listItemsPerPage = $dRec->pages ? $dRec->pages : 15;
            
            $data->usePortalArrange = false;
            
            // Подготвяме навигацията по страници
            $Recently->prepareListPager($data);
            
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
            
            $Recently = cls::get('bgerp_Recently');
            
            // Рендираме изгледа
            $data->tpl = $Recently->renderPortal($data->data);
            
            $cacheLifetime = doc_Setup::get('CACHE_LIFETIME') ? doc_Setup::get('CACHE_LIFETIME') : 5;
            
            core_Cache::set($data->cacheType, $data->cacheKey, array($data->tpl, $data->lastRecLast), $cacheLifetime);
        }
        
        return $data->tpl;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param bgerp_drivers_Recently $Driver
     *                                      $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm($Driver, embed_Manager $Embedder, &$data)
    {
        $data->form->setDefault('pages', 10);
    }
}
