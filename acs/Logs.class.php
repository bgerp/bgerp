<?php


/**
 *
 *
 * @category  bgerp
 * @package   acs
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class acs_Logs extends core_Manager
{
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Логове';
    
    
    /**
     * Титлата на обекта в единичен изглед
     */
    public $singleTitle = 'Лог';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Sorting, acs_Wrapper, plg_SelectPeriod';
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'acs, admin';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой има достъп до сингъла
     */
    public $canSingle = 'acs, admin';
    
    
    /**
     * 
     * @var string
     *
     */
    public $allowedActionsArr = 'allowed=Разрешен достъп,denied=Отказан достъп,unknownId=Неразпознат ID, movement=Забелязано движение,
                                suspiciousMovement=Подозрително движение,authErr=Грешна оторизация,empty=Липсва присъствие,
                                openedDoor=Отворена врата,openedWindow=Отворен прозорец,floor=Наводнение,fire=Пожар,technicalErr=Технически проблем,unknown=Неразпознат';
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('time', 'datetime(format=smartTime)', 'caption=Време');
        $this->FLD('companyId', 'key(mvc=crm_Companies, select=name, allowEmpty)', 'caption=Фирма');
        $this->FLD('personId', 'key(mvc=crm_Persons, select=name, allowEmpty)', 'caption=Лице');
        $this->FLD('cardId', 'varchar(128)', 'caption=Карта'); //@todo
        $this->FLD('action', 'enum(,' .$this->allowedActionsArr . ')', 'caption=Действие, oldFieldName=type');
        $this->FLD('zoneId', 'key(mvc=acs_Zones, select=name, allowEmpty)', 'caption=Зона');
        $this->FLD('readerId', 'varchar(64)', 'caption=Четец');

        $this->setDbIndex('time');
        $this->setDbIndex('companyId');
        $this->setDbIndex('personId');
        $this->setDbIndex('cardId');
        $this->setDbIndex('zoneId');
        $this->setDbIndex('action');

        $this->setDbUnique('cardId, time');
    }
    
    
    /**
     * Добавя запис в лога
     * 
     * @param string  $cardId
     * @param integer $zoneId
     * @param string  $action
     * @param null|integer|double $timestamp
     * @param string $readerId
     * @param null|integer $companyId
     * @param null|integer $personId
     */
    public static function add($cardId, $zoneId, string $action, $timestamp = null, $readerId = '', $companyId = null, $personId = null)
    {
        $me = cls::get(get_called_class());
        
        $allowedActArr = arr::make($me->allowedActionsArr);
        
        if (!isset($allowedActArr[$action])) {
            wp($allowedActArr, $action);
            $action = 'unknown';
        }
        
        if (!isset($timestamp)) {
            $timestamp = dt::mysql2timestamp();
        }
        
        // Ако не е подадена фирма или лице
        // Опитваме се да определим последния картодържател за подаденото време
        if (!isset($companyId) && !isset($personId)) {
            $cardHolderArr = acs_Permissions::getCardHolder($cardId, $timestamp);
            $companyId = $cardHolderArr['companyId'];
            $personId = $cardHolderArr['personId'];
        }
        
        $rec = new stdClass();
        $rec->time = dt::timestamp2Mysql($timestamp);
        $rec->companyId = $companyId;
        $rec->personId = $personId;
        $rec->cardId = $cardId;
        $rec->zoneId = $zoneId;
        $rec->action = $action;
        $rec->readerId = $readerId;

        self::save($rec, null, 'IGNORE');
        
        if (!isset($companyId) && !isset($personId)) {
            self::logErr('Карта "' . $cardId . '" без собственик в зона "' . acs_Zones::getVerbal($zoneId, 'nameLoc') . '" с действие ' . mb_strtolower($allowedActArr[$action]), $rec->id);
        }
        
        return $rec->id;
    }
    
    
    /**
     * 
     * 
     * @param stdClass $data
     * 
     * @return stdClass
     */
    function prepareListFilter($data)
    {
        parent::prepareListFilter_($data);
        
        $data->listFilter->FNC('from', 'datetime', 'caption=От, formOrder=-5');
        $data->listFilter->FNC('to', 'datetime', 'caption=До, formOrder=-4');
        
        return parent::prepareListFilter($data);
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    public static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->fields['selectPeriod']->formOrder = -10;
        
        // Да се показва полето за търсене
        $data->listFilter->showFields .= ',companyId, personId, cardId, zoneId, action';
        $data->listFilter->layout = new ET(tr('|*' . getFileContent('acc/plg/tpl/FilterForm.shtml')));
        $data->listFilter->view = 'vertical';
        
        
        //Добавяме бутон "Филтрирай"
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->input();
        
        $rec = $data->listFilter->rec;
        
        if ($rec->from) {
            $from = dt::mysql2timestamp($rec->from);
            $data->query->where(array("#time >= '[#1#]'", $from));
        }
        
        if ($rec->to) {
            $to = dt::mysql2timestamp($rec->to);
            $data->query->where(array("#time <= '[#1#]'", $to));
        }
        
        foreach (array('companyId', 'personId', 'cardId', 'zoneId', 'action') as $fName) {
            if ($rec->{$fName}) {
                $data->query->where(array("#{$fName} = '[#1#]'", $rec->{$fName}));
            }
        }

        $data->query->orderBy('time', 'DESC');
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        $row->ROW_ATTR['class'] = "action-{$rec->action}";

        if ($rec->companyId) {
            if (crm_Companies::haveRightFor('single', $rec->companyId)) {
                $row->companyId = crm_Companies::getLinkToSingle($rec->companyId, 'name');
            }
        }

        if ($rec->personId) {
            if (crm_Persons::haveRightFor('single', $rec->personId)) {
                $row->personId = crm_Persons::getLinkToSingle($rec->personId, 'name');
            }
        }
    }
}
