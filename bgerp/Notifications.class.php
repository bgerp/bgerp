<?php

/**
 * Мениджър за известявания
 *
 *
 * @category   bgERP 2.0
 * @package    bgerp
 * @title:     Известявания
 * @author     Димитър Минеков <mitko@extrapack.com>
 * @copyright  2006-2011 Experta Ltd.
 * @license    GPL 2
 * @since      v 0.1
 */
class bgerp_Notifications extends core_Manager
{
    /**
     *  Необходими мениджъри
     */
    var $loadList = 'plg_Modified, bgerp_Wrapper';
    
    
    /**
     *  Титла
     */
    var $title = 'Известия';
    
    
    /**
     * Права за писане
     */
    var $canWrite = 'admin';
    
    
    /**
     *  Права за запис
     */
    var $canRead = 'admin';
    
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('msg', 'varchar(128)', 'caption=Съобщение, mandatory');
        $this->FLD('url', 'url', 'caption=Извиквач');
        $this->FLD('state', 'enum(active=Активно, closed=Затворено)', 'caption=Състояние');
        $this->FLD('userId', 'key(mvc=core_Users)', 'caption=Отговорник');
        $this->FLD('priority', 'enum(normal, warning, alert)', 'caption=Приоритет');
        
        $this->setDbUnique('url, userId');

    }
    
    
    /**
     * 
     * Добавя известие за настъпило събитие 
     * @param varchar $msg
     * @param url $url
     * @param integer $userId
     * @param enum $priority
     */
    function add($msg, $url, $userId, $priority)
    {
    	$rec = new stdClass();
    	$rec->msg = $msg;
    	$rec->url = $url;
    	$rec->userId = $userId;
    	$rec->priority = $priority;
    	
    	bgerp_Notifications::save($rec);
    }
    
    /**
     * 
     * Тук правим проверката ако 1 съобщение се повтаря
     * да се обнови само modifiedOn, modifiedBy и state
     * @param stdClass $mvc
     * @param int $id
     * @param stdObject $rec
     */
    function on_BeforeSave($mvc, &$id, $rec)
    {
    	$query = $this->getQuery();
    	$query->where("#userId = {$rec->userId} AND #url = '{$rec->url}'");
    	$query->show('id');
    	$r = $query->fetch();
    	$rec->id = $r->id;
    	$rec->state = 'active';
    }
    
    
    /**
     * 
     * Връща известяванията за текущият потребител
     */
    function getNotificationsByUser()
    {
    	$rec = new stdClass();
    	
    	$rec = bgerp_Notifications::fetch("#userId='". Users::getCurrent()."'");
    	
    	return $rec; 
    }
    
    /**
     * 
     * Какво правим след сетъпа на модела?
     */
    function on_AfterSetupMVC()
    {

    }
    
 }
