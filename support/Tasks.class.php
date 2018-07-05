<?php 


/**
 * Документ с който се сигнализара някакво несъответствие
 *
 * @category  bgerp
 * @package   support
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class support_Tasks extends core_Manager
{
    
    
    /**
     * Заглавие на модела
     */
    public $title = 'Сигнали';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'powerUser';
    
    
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
    public $canView = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, admin, support';
    
    
    
    public $loadList = 'plg_SelectPeriod, support_Wrapper, plg_Search';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Име на таблицата, която ще се използва
     */
    public $dbTableName = 'cal_Tasks';
    
    
    /**
     *
     * @see plg_SelectPeriod
     */
    public $filterDateFrom = 'createdFrom';
    
    
    /**
     *
     * @see plg_SelectPeriod
     */
    public $filterDateTo = 'createdTo';
    
    
    /**
     * Връща асоциирана db-заявка към MVC-обекта
     *
     * @return core_Query
     */
    public function getQuery_($params = array())
    {
        $this->mvc = cls::get('cal_Tasks');
        
        return $this->mvc->getQuery($params);
    }
    
    
    /**
     *
     *
     * @see core_Manager::prepareListFields_()
     */
    public function prepareListFields_(&$data)
    {
        $data->listFields = array();
        
        $data->listFields = arr::make('id=№, title=Заглавие, folderId=Папка, progress=Прогрес, timeStart=Времена->Начало, timeEnd=Времена->Край, timeDuration=Времена->Продължителност, assign=Потребители->Възложени, sharedUsers=Потребители->Споделени', true);
        
        return $data;
    }
    
    
    /**
     * Подготвя редовете във вербална форма
     */
    public function prepareListRows_(&$data)
    {
        return $this->mvc->prepareListRows($data);
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->query->where("#state != 'rejected'");
        
        $data->query->where(array("#{$mvc->mvc->driverClassField} = '[#1#]'", support_TaskType::getClassId()));
        
        // Подреждаме сиганлите активните отпред, затворените отзад а другите по между им
        $data->query->XPR('orderByState', 'int', "(CASE #state WHEN 'active' THEN 1 WHEN 'wakeup' THEN 1 WHEN 'waiting' THEN 2  WHEN 'pending' THEN 2 WHEN 'closed' THEN 4  WHEN 'closed' THEN 4 ELSE 3 END)");
        $data->query->orderBy('orderByState');
        
        $data->query->orderBy('modifiedOn', 'DESC');
        
        $data->listFilter->FNC('systemId', 'key(mvc=support_Systems, select=name, allowEmpty)', 'caption=Система, input=input, silent, autoFilter');
        $data->listFilter->FNC('maintainers', 'type_Users(rolesForAll=support|ceo|admin)', 'caption=Отговорник, input, silent, autoFilter');
        
        $data->listFilter->FNC('createdFrom', 'date', 'caption=От, input,silent, autoFilter, title=Създадено от');
        $data->listFilter->FNC('createdTo', 'date', 'caption=До, input,silent, autoFilter, title=Създадено до');
        
        $data->listFilter->FNC('state', 'enum(, active=Активен, pending=Заявка, stopped=Спрян)', 'caption=Състояние, input, silent, autoFilter, allowEmpty');
        
        $data->listFilter->showFields = 'search, selectPeriod, state, systemId, maintainers';
        $default = $data->listFilter->getField('maintainers')->type->fitInDomain('all_users');
        $data->listFilter->setDefault('maintainers', $default);
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->input();
        
        $rec = $data->listFilter->rec;
        
        // Филтриране по система - по папка
        if ($rec->systemId) {
            $folderId = support_Systems::forceCoverAndFolder($rec->systemId);
            
            if ($folderId) {
                $data->query->where(array("#folderId = '[#1#]'", $folderId));
            }
        }
        
        // Филтриране по споделени/възложени потребители
        if ($rec->maintainers && !type_Keylist::isIn('-1', $rec->maintainers)) {
            $data->query->likeKeylist('sharedUsers', $rec->maintainers);
            $data->query->likeKeylist('assign', $rec->maintainers, true);
        }
        
        if ($rec->createdFrom) {
            $data->query->where(array("#createdOn >= '[#1#] 00:00:00'", $rec->createdFrom));
        }
        
        if ($rec->createdTo) {
            $data->query->where(array("#createdOn <= '[#1#] 23:59:59'", $rec->createdTo));
        }
        
        if ($rec->state) {
            if ($rec->state == 'active') {
                $data->query->where("#state = 'active'");
                $data->query->orWhere("#state = 'wakeup'");
            }
            
            if ($rec->state == 'pending') {
                $data->query->where("#state = 'pending'");
                $data->query->orWhere("#state = 'waiting'");
            }
            
            if ($rec->state == 'stopped') {
                $data->query->where("#state = 'stopped'");
                $data->query->orWhere("#state = 'closed'");
            }
        }
        
        doc_Threads::restrictAccess($data->query);
    }
}
