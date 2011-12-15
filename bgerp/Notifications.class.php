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
    var $loadList = 'plg_Modified, bgerp_Wrapper, plg_RowTools';
    
    
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
        $this->FLD('url', 'varchar', 'caption=URL');
        $this->FLD('state', 'enum(active=Активно, closed=Затворено)', 'caption=Състояние');
        $this->FLD('userId', 'key(mvc=core_Users)', 'caption=Отговорник');
        $this->FLD('priority', 'enum(normal, warning, alert)', 'caption=Приоритет');
        $this->FLD('cnt', 'int', 'caption=Брой');
        
        $this->setDbUnique('url, userId');
    }
    
    
    /**
     * Добавя известие за настъпило събитие 
     * @param varchar $msg
     * @param array $url
     * @param integer $userId
     * @param enum $priority
     */
    static function add($msg, $urlArr, $userId, $priority)
    {
    	$rec = new stdClass();
    	$rec->msg = $msg;
    	    	
    	$rec->url = toUrl($urlArr, 'local');
    	$rec->userId = $userId;
    	$rec->priority = $priority;
		
    	// Ако има такова съобщение - само му вдигаме флага че е активно
    	$query = bgerp_Notifications::getQuery();
    	$r = $query->fetch("#userId = {$rec->userId} AND #url = '{$rec->url}'");
    	
    	// Ако съобщението е активно от преди това - увеличаваме брояча му
    	if ($r->state == 'active') {
            $rec->cnt = $r->cnt + 1;
        } else {
            $rec->cnt = 1;
        }
    	
    	$rec->id    = $r->id;
    	$rec->state = 'active';
    	
    	bgerp_Notifications::save($rec);
    }

    
	/**
	 * 
	 * Отбелязва съобщение за прочетено
	 */
    function clear($urlArr, $userId = NULL)
	{   
        if(empty($userId)) {
            $userId = core_Users::getCurrent();
        }
		$url = toUrl($urlArr, 'local');  
    	$query = bgerp_Notifications::getQuery();
    	$query->where("#userId = {$userId} AND #url = '{$url}' AND #state = 'active'");
    	$query->show('id, state, userId, url');
    	$rec = $query->fetch();
		if ($rec) {
			$rec->state = 'closed';
			$rec->cnt = 0;
			bgerp_Notifications::save($rec);
		}
	}


    /**
     *
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $url = getRetUrl($rec->url);
        
        if($rec->cnt > 1) {
            $row->msg .= " ({$rec->cnt})";
        }

        if($rec->state == 'active') {
            $attr['style'] = 'font-weight:bold;';
        } else {
            $attr['style'] = 'color:#666;';
        }
        $row->msg = ht::createLink($row->msg, $url, NULL, $attr);
    }


    /**
     *
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->query->orderBy("state,modifiedOn=DESC");
    }

	
    /**
     * 
     * Какво правим след сетъпа на модела?
     */
    function on_AfterSetupMVC()
    {

    }
    
 }
