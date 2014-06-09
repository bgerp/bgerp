<?php


/**
 * 
 *
 * @category  ef
 * @package   core
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class core_LoginLog extends core_Manager
{
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Логин лог на потребителите";
    
    
    /**
     * 
     */
    var $canSingle = 'admin';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'no_one';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_SystemWrapper, plg_Created';
    
    
    /**
     * 
     */
    var $listFields = 'userId, status, ip, brid, time, createdOn, createdBy';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('userId', 'user(select=nick)', 'caption=Потребител');
        $this->FLD('ip', 'ip', 'caption=IP');
        $this->FLD('brid', 'varchar', 'caption=BRID');
        $this->FLD('status', 'enum(
        							success=Успешен,
									error=Грешка,
									block=Блокиран,
									reject=Оттеглен,
									draft=Чернова,
									missing_password=Липсва парола,
									wrong_password=Грешна парола,
									pass_reset=Ресетване на парола,
									pass_change=Промяна на парола,
									user_reg=Регистриране,
									user_activate=Активиране,
									change_nick=Промяна на ник
								  )', 'caption=Статус');
        $this->FLD('time', 'datetime()', 'caption=Време, input=none');
    }
    
    
    /**
     * Записва в лога опитите за логване
     * 
     * @param integer $userId
     * @param string $status
     * @param time $time
     */
    static function add($userId, $status, $time=NULL)
    {
        $rec = new stdClass();
        $rec->userId = $userId;
        $rec->ip = core_Users::getRealIpAddr();
        $rec->status = $status;
        $rec->brid = core_Browser::getBrid();
        
        if ($time) {
            $rec->time = dt::timestamp2Mysql($time);
        }
        
        static::save($rec);
        
        return $rec->id;
    }
    
    
    /**
     * 
     * 
     * @param core_LoginLog $mvc
     * @param object $row
     * @param object $rec
     * @param array $fields
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if ($rec->ip){
    	    $row->ip = type_Ip::decorateIp($rec->ip, $rec->createdOn);
    	}
    }
    
    
    /**
     * 
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        // Сортиране на записите по num
        $data->query->orderBy('createdOn', 'DESC');
    }
}
