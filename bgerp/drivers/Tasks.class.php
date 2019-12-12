<?php


/**
 * Драйвер за показване на задачите
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
 * @title     Задачи
 */
class bgerp_drivers_Tasks extends core_BaseClass
{
    /**
     * Максимален брой блокове, които да могат да се поакзват в портала
     */
    public $maxCnt;
    
    
    public $interfaces = 'bgerp_PortalBlockIntf';
    
    protected $priorityMap = array(
                                    'low' => 'low|normal|high|critical',
                                    'normal' => 'normal|high|critical',
                                    'high' => 'high|critical',
                                    'critical' => 'critical',
                                    );
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('perPage', 'int(min=1, max=50)', 'caption=Редове, mandatory');
        $fieldset->FLD('from', 'enum(,toMe=За мен,fromMe=От мен)', 'caption=Задачи от/към');
        $fieldset->FLD('taskPriority', 'enum(low=Нисък,normal=Нормален,high=Спешен,critical=Критичен)', 'caption=Минимален приоритет за включване->Задачи');
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
        
        $Tasks = cls::get('cal_Tasks');
        
        $resData->data = new stdClass();
        
        // Създаваме заявката
        $resData->data->query = cal_Tasks::getQuery();
        
        if ($dRec->taskPriority) {
            expect($this->priorityMap[$dRec->taskPriority]);
            $priorityArr = explode('|', $this->priorityMap[$dRec->taskPriority]);
            $resData->data->query->orWhereArr('priority', $priorityArr);
        }
        
        // Подготвяме полетата за показване
        $resData->data->listFields = 'groupDate,title,progress';
        
        if ($this->isFromMe($dRec->from)) {
            $resData->data->query->where(array("#createdBy = '[#1#]'", $userId));
        } else {
            $resData->data->query->likeKeylist('assign', $userId);
        }
        
        $resData->data->query->where("#state = 'active'");
        $resData->data->query->orWhere("#state = 'wakeup'");
        $resData->data->query->orWhere("#state = 'waiting'");
        $resData->data->query->orWhere("#state = 'pending'");
        
        $resData->data->query->where('#timeStart IS NULL');
        $resData->data->query->where('#timeEnd IS NULL');
        
        $resData->cacheKey = $this->getCacheKey($dRec, $userId);
        $resData->cacheType = $this->getCacheTypeName($userId);
        
        $resData->dRecForm = $dRec->from;
        
        $resData->tpl = core_Cache::get($resData->cacheType, $resData->cacheKey);
        
        if (!$resData->tpl) {
            $resData->data->query->XPR('orderByState', 'int', "(CASE #state WHEN 'active' THEN 1 WHEN 'wakeup' THEN 1 WHEN 'waiting' THEN 2 WHEN 'pending' THEN 3 ELSE 4 END)");
            
            $resData->data->query->orderBy('orderByState', 'ASC');
            $resData->data->query->orderBy('modifiedOn', 'DESC');
            $resData->data->query->orderBy('createdOn', 'DESC');
            
            $Tasks->listItemsPerPage = $dRec->perPage ? $dRec->perPage : 15;
            
            $resData->data->usePortalArrange = false;
            
            // Подготвяме навигацията по страници
            $Tasks->prepareListPager($resData->data);
            
            $resData->data->pager->pageVar = $this->getPageVar($dRec->originIdCalc);
            
            // Подготвяме филтър формата
            $Tasks->prepareListFilter($resData->data);
            
            // Подготвяме записите за таблицата
            $Tasks->prepareListRecs($resData->data);
            
            if (is_array($resData->data->recs)) {
                foreach ($resData->data->recs as &$rec) {
                    $rec->savedState = $rec->state;
                    $rec->state = '';
                }
            }
            
            // Подготвяме редовете на таблицата
            $Tasks->prepareListRows($resData->data);
            
            if (is_array($resData->data->recs)) {
                foreach ($resData->data->recs as $id => &$rec) {
                    $row = &$resData->data->rows[$id];
                    
                    $title = str::limitLen(type_Varchar::escape($rec->title), cal_Tasks::maxLenTitle, 20, ' ... ', true);
                    
                    $linkArr = array('ef_icon' => $Tasks->getIcon($rec->id));
                    
                    if ($rec->modifiedOn > bgerp_Recently::getLastDocumentSee($rec->containerId, $userId, false)) {
                        $linkArr['class'] = 'tUnsighted';
                    }
                    
                    // Документа да е линк към single' а на документа
                    $row->title = ht::createLink($title, cal_Tasks::getSingleUrlArray($rec->id), null, $linkArr);
                    
                    if ($row->title instanceof core_ET) {
                        $row->title->append($row->subTitleDiv);
                    } else {
                        $row->title .= $row->subTitleDiv;
                    }
                    
                    if ($rec->savedState) {
                        $sState = $rec->savedState;
                        $row->title = "<div class='state-{$sState}-link'>{$row->title}</div>";
                    }
                }
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
        if (!$data->tpl) {
            $data->tpl = new ET('
                                <div class="clearfix21 portal" style="margin-bottom:25px;">
                                <div class="legend">[#taskTitle#]&nbsp;[#profile#]<!--ET_BEGIN SWITCH_BTN-->&nbsp;[#SWITCH_BTN#]<!--ET_END SWITCH_BTN-->&nbsp;[#ADD_BTN#]&nbsp;[#REM_BTN#]</div>
                                [#PortalTable#]
                            	[#PortalPagerBottom#]
                                </div>
                              ');
            
            // Попълваме таблицата с редовете
            
            if ($data->data->listFilter && $data->data->pager->pagesCount > 1) {
                $formTpl = $data->data->listFilter->renderHtml();
                $formTpl->removeBlocks();
                $formTpl->removePlaces();
                $data->tpl->append($formTpl, 'ListFilter');
            }
            
            $data->tpl->append(cal_Tasks::renderListTable($data->data), 'PortalTable');
            $data->tpl->append(cal_Tasks::renderListPager($data->data), 'PortalPagerBottom');
            
            $switchTitle = '';
            
            // Задачи
            if ($this->isFromMe($data->dRecForm)) {
                $taskTitle = tr('Задачи от');
                if (!$data->dRecForm) {
                    $switchTitle = tr('Задачи към') . ' ' . crm_Profiles::getUserTitle(core_Users::getCurrent('nick'));
                }
            } else {
                $taskTitle = tr('Задачи към');
                if (!$data->dRecForm) {
                    $switchTitle = tr('Задачи от') . ' ' . crm_Profiles::getUserTitle(core_Users::getCurrent('nick'));
                }
            }
            
            $taskTitle = str_replace(' ', '&nbsp;', $taskTitle);
            
            $data->tpl->replace($taskTitle, 'taskTitle');
            $data->tpl->replace(crm_Profiles::createLink(), 'profile');
            
            // Бутон за добавяне на задачи
            $addUrl = array('cal_Tasks', 'add', 'ret_url' => true);
            $addBtn = ht::createLink(' ', $addUrl, null, array('ef_icon' => 'img/16/task-add.png', 'class' => 'addTask', 'title' => 'Добавяне на нова Задача'));
            $data->tpl->append($addBtn, 'ADD_BTN');
            
            $sRetUrl = array('Portal', 'Show');
            
            if (Mode::is('screenMode', 'narrow')) {
                $sRetUrl['#'] = 'taskPortal';
            }
            
            if ($switchTitle) {
                // Бутон за смяна от <-> към
                $addUrl = array('cal_Tasks', 'SwitchByTo', 'ret_url' => $sRetUrl);
                $addBtn = ht::createLink(' ', $addUrl, null, array('ef_icon' => 'img/16/arrow_switch.png', 'class' => 'addTask', 'title' => '|*' . $switchTitle, 'id' => 'switchTasks'));
                $data->tpl->append($addBtn, 'SWITCH_BTN');
            }
            
            // Бутон за смяна от <-> към
            $addUrl = array('cal_Reminders', 'add', 'ret_url' => true);
            $addBtn = ht::createLink(' ', $addUrl, null, array('ef_icon' => 'img/16/alarm_clock_add.png', 'class' => 'addTask', 'title' => 'Добавяне на ново Напомняне'));
            $data->tpl->append($addBtn, 'REM_BTN');
            
            $cacheLifetime = doc_Setup::get('CACHE_LIFETIME') ? doc_Setup::get('CACHE_LIFETIME') : 5;
            
            core_Cache::set($data->cacheType, $data->cacheKey, $data->tpl, $cacheLifetime);
        }
        
        return $data->tpl;
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
        if ($this->isFromMe($dRec->from)) {
            
            return tr('Задачи от мен');
        }
        
        return tr('Задачи към мен');
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
        
        return 'Portal_Tasks_' . $userId;
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
        
        $isFromMe = $this->isFromMe($dRec->from);
        
        $cArr = bgerp_Portal::getPortalCacheKey($dRec, $userId);
        $cArr[] = $isFromMe;
        
        $pageVar = $this->getPageVar($dRec->originIdCalc);
        $pageVarVal = Request::get($pageVar);
        $pageVarVal = isset($pageVarVal) ? $pageVarVal : 1;
        $cArr[] = $pageVarVal;
        
        $cloneQuery = cal_Tasks::getQuery();
        
        if ($isFromMe) {
            $cloneQuery->where(array("#createdBy = '[#1#]'", $userId));
        } else {
            $cloneQuery->likeKeylist('assign', $userId);
        }
        
        $cloneQuery->orderBy('modifiedOn', 'DESC');
        $cloneQuery->limit(1);
        $cloneQuery->show('modifiedOn, id');
        $cRec = $cloneQuery->fetch();
        $cArr[] = $cRec->modifiedOn;
        $cArr[] = $cRec->id;
        
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
     * Помощна фунцкия за проверка дали задачата е от или към текущия потребител
     * 
     * @param string $from
     * 
     * @return boolean
     */
    protected function isFromMe($from)
    {
        if ($from) {
            if ($from == 'fromMe') {
                
                return true;
            }
        } else {
            if (Mode::get('listTasks') == 'by') {
                
                return true;
            }
        }
        
        return false;
    }
}
