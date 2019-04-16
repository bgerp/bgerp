<?php


/**
 * Точки за отчитане на производство
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_Points extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Точки за производство';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'planning_Wrapper,plg_Rejected,plg_RowTools2';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'peripheral_TerminalIntf';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'no_one';
    
    
    /**
     * Полета, които се виждат
     */
    public $listFields = 'name=Точка, centerId, fixedAssets, employees, terminal=Вход';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar(16)', 'caption=Наименование, mandatory');
        $this->FLD('centerId', 'key(mvc=planning_Centers,select=name,allowEmpty)', 'caption=Център, mandatory,removeAndRefreshForm=fixedAssets|employees,silent');
        $this->FLD('fixedAssets', 'keylist(mvc=planning_AssetResources,select=name,makeLinks,allowEmpty)', 'caption=Оборудване, input=none');
        $this->FLD('employees', 'keylist(mvc=crm_Persons,select=id,makeLinks,allowEmpty)', 'caption=Оператори, input=none');
        $this->FLD('state', 'enum(active=Контиран,rejected=Оттеглен)', 'caption=Състояние,notNull,value=active,input=none');
        
        $this->setDbIndex('centerId');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        
        if(isset($form->rec->centerId)){
            $folderId = planning_Centers::fetchField($form->rec->centerId, 'folderId');
            
            // Добавяне на избор само на достъпните оператори/оборудване към ПО
            foreach (array('fixedAssets' => 'planning_AssetResources', 'employees' => 'planning_Hr') as $field => $Det) {
                $arr = $Det::getByFolderId($folderId);
                if (!empty($form->rec->{$field})) {
                    $alreadyIn = keylist::toArray($form->rec->{$field});
                    foreach ($alreadyIn as $fId) {
                        if (!array_key_exists($fId, $arr)) {
                            $arr[$fId] = $Det::getTitleById($fId, false);
                        }
                    }
                }
                
                if (count($arr)) {
                    $form->setSuggestions($field, array('' => '') + $arr);
                    $form->setField($field, 'input');
                }
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->ROW_ATTR['class'] = "state-{$rec->state}";
        $row->centerId = planning_Centers::getHyperlink($rec->centerId, true);
        
        if(planning_Points::haveRightFor('openterminal', $rec)){
            $row->terminal = ht::createBtn('Отвори', array('planning_Terminal', 'open', $rec->id), false, true, 'title=Отваряне на терминала за отчитане на производството,ef_icon=img/16/forward16.png');
        }
        
        $peripheralData = (object)array('masterMvc' => clone $mvc, 'masterId' => $rec->id);
        cls::get('peripheral_Terminal')->prepareDetail($peripheralData);
        if(count($peripheralData->rows)){
            $terminalList = '<table class="innerTable">';
            foreach ($peripheralData->rows as $pRow){
                core_RowToolbar::createIfNotExists($pRow->_rowTools);
                $pRow->brid = (!empty($pRow->brid)) ? $pRow->brid : "N/A";
                $pRow->users = (!empty($pRow->users)) ? $pRow->users : "<span class='quiet'>" . tr('Всички') . " </span>";
                $pRow->roles = (!empty($pRow->roles)) ? $pRow->roles : "<span class='quiet'>" . tr('Всички') . " </span>";
                $terminalList .= "<tr><td>{$pRow->_rowTools->renderHtml()}<span class='quiet'>" . tr('Браузър') . "</span>: {$pRow->brid}</td><td><span class='quiet'>" . tr('Пин') . "</span>: {$pRow->usePin}</td><td><span class='quiet'>" . tr('Потребители') . "</span>: {$pRow->users}</td><td><span class='quiet'>" . tr('Роли') . "</span>: {$pRow->roles}</td></tr>";
            }
            $terminalList .= "</table>";
            $row->terminalList = $terminalList;
        }
        
    }


    /**
     * Връща всички достъпни за текущия потребител id-та на обекти, отговарящи на записи
     *
     * @return array
     *
     * @see peripheral_TerminalIntf
     */
    public function getTerminalOptions()
    {
        $options = array();
        $cQuery = self::getQuery();
        $cQuery->where("#state != 'rejected' AND #state != 'closed'");
        while ($cRec = $cQuery->fetch()) {
            $options[$cRec->id] = self::getRecTitle($cRec, false) . " ({$cRec->id})";
        }

        return $options;
    }


    /**
     * Редиректва към посочения терминал в посочената точка и за посочения потребител
     *
     * @return Redirect
     *
     * @see peripheral_TerminalIntf
     */
    public function openTerminal($objectId, $userId)
    {
        return new Redirect(array($this, 'openTerminal', $objectId));
    }
    
    
    /**
     * Екшън форсиращ избирането на точката и отваряне на терминала
     */
    public function act_OpenTerminal()
    {
        expect($objectId = Request::get('id', 'int'));
        
        return new Redirect(array('planning_Terminal', 'open', $objectId));
    }
    
    
    /**
     * Подготовка на детайла
     *
     * @param stdClass $data
     */
    public function prepareDetail_($data)
    {
        $data->TabCaption = 'Точки';
        $data->Order = '1';
        $this->prepareListFields($data);
        unset($data->listFields['centerId']);
        $data->listFields['terminalList'] = '@';
        $data->recs = $data->rows = array();
        $query = self::getQuery();
        $query->where("#centerId = {$data->masterId}");
        
        // Добавяне на точките, вързани към центъра на дейност
        while($rec = $query->fetch()){
            $data->recs[$rec->id] = $rec;
            $row = $this->recToVerbal($rec);
            if ($rec->state != 'closed' && peripheral_Terminal::haveRightFor('add', (object)array('classId' => $this->getClassId, 'pointId' => $rec->id))) {
                core_RowToolbar::createIfNotExists($row->_rowTools);
                $row->_rowTools->addLink('Нов терминал', array('peripheral_Terminal', 'add', 'classId' => $this->getClassId, 'pointId' => $rec->id, 'ret_url' => true), 'alwaysShow,ef_icon=img/16/monitor.png,title=Добавяне на нов терминал към точката за производство');
            }
            
            $data->rows[$rec->id] = $row;
        }
        
        if($this->haveRightFor('add', (object)array('centerId' => $data->masterId))){
            $data->addUrl = array($this, 'add', 'centerId' => $data->masterId, 'ret_url' => true);
        }
    }
    
    
    /**
     * Рендиране на детайла
     *
     * @param stdClass $data
     *
     * @return core_ET $tpl
     */
    public function renderDetail_($data)
    {
        $tpl = getTplFromFile('peripheral/tpl/TerminalDetailLayout.shtml');
        $tpl->append(tr('Точки на производство'), 'title');
        
        // Рендиране на таблицата с точките
        $this->invoke('BeforeRenderListTable', array($tpl, &$data));
        $table = cls::get('core_TableView', array('mvc' => $this));
        $content = $table->get($data->rows, $data->listFields);
        $tpl->append($content, 'content');
        
        if (isset($data->addUrl)) {
            $addBtn = ht::createLink(' ', $data->addUrl, false, 'ef_icon=img/16/add.png,title=Добавяне на нова точка за производство');
            $tpl->append($addBtn, 'AddLink');
        }
        
        return $tpl;
    }
}