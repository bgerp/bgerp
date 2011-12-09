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
        $this->FLD('count', 'int(4)', 'caption=Брой');
        
        $this->setDbUnique('url, userId');

    }
    
    
    /**
     * 
     * Добавя известие за настъпило събитие 
     * @param varchar $msg
     * @param array $url
     * @param integer $userId
     * @param enum $priority
     */
    function add($msg, $urlArr, $userId, $priority)
    {
    	$rec = new stdClass();
    	$rec->msg = $msg;
    	
    	expect(is_array($urlArr));
    	
    	$rec->url = toUrl($urlArr, 'local');
    	$rec->userId = $userId;
    	$rec->priority = $priority;
		
    	// Ако има такова съобщение - само му вдигаме флага че е активно
    	$query = $this->getQuery();
    	$query->where("#userId = {$rec->userId} AND #url = '{$rec->url}'");
    	$query->show('id, state');
    	$r = $query->fetch();
    	
    	// Ако съобщението е активно от преди това - увеличаваме брояча му
    	if ($r->state == 'active') $rec->count = ++$r->count;
    	
    	$rec->id = $r->id;
    	$rec->state = 'active';
    	
    	bgerp_Notifications::save($rec);
    }
    
	/**
	 * 
	 * Отбелязва съобщение за прочетено
	 */
    function markAsRead($urlArr, $userId)
	{
		$url = toUrl($urlArr, 'local');
    	$query = $this->getQuery();
    	$query->where("#userId = {$userId} AND #url = '{$url}' AND #state = 'active'");
    	$query->show('id, state, userId, url');
    	$rec = $query->fetch();
		if ($rec) {
			$rec->state = 'closed';
			$rec->count = 0;
			bgerp_Notifications::save($rec);
		}
	}   

	
    /**
     * 
     * Какво правим след сетъпа на модела?
     */
    function on_AfterSetupMVC()
    {

    }
    
 }
