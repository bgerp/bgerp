<?php


/**
 * Драйвер за показване на съдържанието на папки
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Папка
 */
class doc_drivers_FolderPortal extends core_BaseClass
{
    /**
     * Максимален брой блокове, които да могат да се поакзват в портала
     */
    public $maxCnt;
    
    
    public $interfaces = 'bgerp_PortalBlockIntf';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('folderId', 'key2(mvc=doc_Folders,allowEmpty, restrictViewAccess=yes)', 'caption=Папка, removeAndRefreshForm=documentClassId, mandatory, silent');
        $fieldset->FLD('search', 'varchar', 'caption=Ключови думи,inputmode=search');
        $fieldset->FLD('fOrder', 'enum(' . doc_Threads::filterList . ')', 'caption=Подредба');
        $fieldset->FLD('documentClassId', 'class(interface=doc_DocumentIntf,select=title,allowEmpty)', 'caption=Вид документ');
        $fieldset->FLD('perPage', 'int(min=1, max=20)', 'caption=Редове, mandatory');
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
        if ($data->form->rec->folderId) {
            $documentsInThreadOptions = doc_Threads::getDocumentTypesOptionsByFolder($data->form->rec->folderId);
            if (count($documentsInThreadOptions)) {
                $documentsInThreadOptions = array_map('tr', $documentsInThreadOptions);
                $data->form->setOptions('documentClassId', $documentsInThreadOptions);
            }
        }
        
        $data->form->setDefault('perPage', 5);
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
        
        expect($dRec->folderId);
        
        $fRec = doc_Folders::fetch($dRec->folderId);
        
        if (!doc_Folders::haveRightFor('single', $fRec)) {
            
            return $resData;
        }
        
        $resData->cacheKey = $this->getCacheKey($dRec, $userId);
        $resData->cacheType = $this->getCacheTypeName($userId);
        
        $resData->tpl = core_Cache::get($resData->cacheType, $resData->cacheKey);
        
        if (!$resData->tpl) {
            $resData->data = new stdClass();
            
            $dQuery = doc_Threads::getQuery();
            
            $filter = new stdClass();
            $filter->folderId = $dRec->folderId;
            $filter->order = $dRec->fOrder;
            $filter->search = $dRec->search;
            $filter->documentClassId = $dRec->documentClassId;
            
            doc_Threads::applyFilter($filter, $dQuery);
            
            $Threads = cls::get('doc_Threads');
            $Threads->addRowClass = false;
            $Threads->addThreadStateClassToLink = true;
            
            $Threads->loadList = arr::make($Threads->loadList, true);
            unset($Threads->loadList['plg_RefreshRows']);
            unset($Threads->_plugins['plg_RefreshRows']);
            unset($Threads->loadList['plg_Select']);
            unset($Threads->_plugins['plg_Select']);
            
            $data = new stdClass();
            
            $Threads->listItemsPerPage = $dRec->perPage ? $dRec->perPage : 5;
            
            $data->listFields = arr::make('title=Заглавие,author=Автор,last=Последно', true);
            
            $data->query = $dQuery;
            $data->rejQuery = clone $dQuery;
            
            // Подготвяме навигацията по страници
            $Threads->prepareListPager($data);
            
            $data->pager->pageVar = $this->getPageVar($dRec->originIdCalc);
            
            // Подготвяме записите за таблицата
            $Threads->prepareListRecs($data);
            
            // Подготвяме редовете на таблицата
            $Threads->prepareListRows($data);
            
            foreach ($data->rows as $row) {
                if (is_string($row->title)) {
                    $row->title .= "<div style='float:right'><small>{$row->author}, {$row->last}</small></div>";
                } elseif ($row->title instanceof core_Et) {
                    $row->title->append("<div style='float:right'><small>{$row->author}, {$row->last}</small></div>");
                }
            }
            
            $resData->data = $data;
            
            $resData->folderTitle = $this->getFolderLink($dRec, 42);
            
            $dRec->search = trim($dRec->search);
            if ($dRec->search) {
                $resData->folderTitle .= ' (' . type_Varchar::escape($dRec->search) . ')';
            }
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
        if (!$data->tpl && $data->data) {
            $data->tpl = new ET('<div class="clearfix21 portal" style="margin-bottom:25px;">
                                <div class="legend">[#folderTitle#]</div>
                                [#PortalTable#]
                            	[#PortalPagerBottom#]
                                </div>
                              ');
            
            $Threads = cls::get('doc_Threads');
            
            
            $data->data->listFields = array('title' => 'Заглавие');
            
            $data->tpl->append($Threads->renderListTable($data->data), 'PortalTable');
            $data->tpl->append($Threads->renderListPager($data->data), 'PortalPagerBottom');
            
            $data->tpl->append($data->folderTitle, 'folderTitle');
            
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
        $fTitle = doc_Folders::fetchField($dRec->folderId, 'title');
        
        $maxLength = 42;
        
        $fTitle = str::limitLen($fTitle, $maxLength, (int) ($maxLength/2));
        
        return type_Varchar::escape($fTitle);
    
    }
    
    
    /**
     * Помощна фунцкия за вземана на линк към папката със сътоветния филтър
     * 
     * @param stdClass $dRec
     * @param integer $len
     * 
     * @return core_ET
     */
    protected function getFolderLink($dRec, $len = 42)
    {
        $attrArr = array();
        if (doc_Folders::haveRightFor('single', $dRec->folderId)) {
            $attrArr['url'] = array('doc_Threads', 'list', 'folderId' => $dRec->folderId);
            if ($dRec->search) {
                $attrArr['url']['search'] = $dRec->search;
            }
            if ($dRec->fOrder) {
                $attrArr['url']['order'] = $dRec->fOrder;
            }
            if ($dRec->documentClassId) {
                $attrArr['url']['documentClassId'] = $dRec->documentClassId;
            }
        } else {
            $attrArr['url'] = array();
        }
        
        return doc_Folders::getLink($dRec->folderId, 42, $attrArr);
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
        
        return 'Portal_Folder_' . $userId;
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
        
        $fRec = doc_Folders::fetch($dRec->folderId);
        $cArr[] = $fRec->last;
        $cArr[] = serialize($fRec->statistic);
        
        $pageVar = $this->getPageVar($dRec->originIdCalc);
        $pageVarVal = Request::get($pageVar);
        $pageVarVal = isset($pageVarVal) ? $pageVarVal : 1;
        $cArr[] = $pageVarVal;
        
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
