<?php

/**
 * Рутира всички несортирани писма.
 * 
 * Несортирани са всички писма от папка "Несортирани - [Титлата на класа email_Messages]"
 *
 * @category   BGERP
 * @package    email
 * @author	   Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @since      v 0.1
 * @see https://github.com/bgerp/bgerp/issues/108
 */
class email_Router extends core_Manager
{   
    var $loadList = 'plg_Created,email_Wrapper';

    var $title    = "Рутер на ел. поща";

    var $listFields = 'id, type, key, objectType, objectId, priority';

    var $canRead   = 'admin,email';
    var $canWrite  = 'admin,email';
    var $canReject = 'admin,email';
    
    /**
     *  Име на папката, където отиват писмата неподлежащи на сортиране
     */ 
    const UnsortableFolderName = 'Unsorted - Internet';
    
    const RuleFromTo = 'fromTo';
    const RuleFrom   = 'from';
    const RuleDomain = 'domain';


    function description()
    {
        $this->FLD('type' , "enum(" . implode(', ', array(self::RuleFromTo, self::RuleFrom, self::RuleDomain)) . ")", 'caption=Тип');
        $this->FLD('key' , 'varchar(64)', 'caption=Ключ');
        $this->FLD('objectType' , 'enum(person, company, document)');
        $this->FLD('objectId' , 'int', 'caption=Обект');
        $this->FLD('priority' , 'varchar(12)', 'caption=Приоритет');
        
        defIfNot('UNSORTABLE_EMAILS', self::UnsortableFolderName);
    }
    
    public static function route($fromEmail, $toEmail, $type)
    {
    	$key = static::getRoutingKey($fromEmail, $toEmail, $type);

    	$rec = static::fetch("#type = '{$type}' AND #key = '{$key}'");
    	
    	$folderId = NULL;
    	
    	if ($rec) {
    		// от $rec->objectType и $rec->objectId изваждаме folderId
    		switch ($rec->objectType) {
    			case 'document':
    			    $folderId = doc_Containers::fetchField($rec->objectId, 'folderId');
    			    break;
    			case 'person':
    				$folderId = crm_Persons::forceCoverAndFolder($rec->objectId);
    				break;
    			case 'company':
    				$folderId = crm_Companies::forceCoverAndFolder($rec->objectId);
    				break;
    			default:
    				expect(FALSE, $rec->objectType . ' е недопустим тип на обект в правило за рутиране');
    		}
    	}
    	
    	return $folderId;
    }
    
    
    /**
     * Определя папката, към която се сортират писмата, изпратени от даден имейл
     *
     * @param string $email
     * @return int key(mvc=doc_Folders)
     */
    public static function getEmailFolder($email)
    {
    	return static::route($email, NULL, email_Router::RuleFrom);
    }
    
    
    /**
     * Връща ключовете, използвани в правилата за рутиране
     *
     * @return array масив с индекс 'type' и стойност ключа от съотв. тип
     * 
     */
    public static function getRoutingKey($fromEmail, $toEmail, $type = NULL)
    {
    	if (empty($type)) {
    		$type = array(
    			self::RuleFromTo, 
    			self::RuleFrom, 
    			self::RuleDomain
    		);
    	}
    	
    	$type = arr::make($type, TRUE);
    	
    	$keys = array();
    	
    	if ($type[self::RuleFromTo]) {
    		$keys[self::RuleFromTo] = str::convertToFixedKey($fromEmail . '|' . $toEmail);
    	} 
    	if ($type[self::RuleFrom]) {
    		$keys[self::RuleFrom] = str::convertToFixedKey($fromEmail);
    	} 
    	if ($type[self::RuleDomain]) {
	    	if (!static::isPublicDomain($domain = static::extractDomain($fromEmail))) {
	    		$keys[self::RuleDomain] = str::convertToFixedKey($domain);
	    	}
    	}
    	
    	if (count($keys) <= 1) {
    		$keys = reset($keys);
    	}
    	
    	return $keys;
    }
    
    
    /**
     * Обновява правилата за рутиране.
     * 
     * @param stdClass $rule запис на модела
     */
    function saveRule($rule)
    {
		$query = static::getQuery();
		$query->orderBy('priority', 'DESC');
		
		$rec = $query->fetch("#key = '{$rule->key}' AND #type = '{$rule->type}'");
		
		if ($rec->priority < $rule->priority) {
			// Досегашното правило за тази двойка <type, key> е с по-нисък приоритет
			// Обновяваме го
			$rule->id = $rec->id;
			static::save($rule);
		}
    }
    
    
    function removeRule($objectType, $objectId)
    {
    	static::delete("#objectType = '{$objectType}' AND #objectId = {$objectId}");
    }
    
    
    /**
     * Дали домейна е на публична е-поща (като abv.bg, mail.bg, yahoo.com, gmail.com)
     *
     * @param string $domain TLD
     * @return boolean
     */
    static function isPublicDomain($domain)
    {
    	return email_PublicDomains::isPublic($domain);
    }

    
    protected static function extractDomain($email)
    {
    	list(, $domain) = explode('@', $email, 2);
    	
    	$domain = empty($domain) ? FALSE : trim($domain); 

    	return $domain;
    }
    
    
    static function dateToPriority($date, $prefix = 'high', $dir = 'asc')
    {
    	$priority = dt::mysql2timestamp($date);
    	$dir      = strtolower($dir);
    	$prefix   = strtolower($prefix);
    	
    	$prefixKeywords = array(
    		'high' => '30',
    		'mid'  => '20',
    		'low'  => '10'
    	);
    	
    	if (!empty($prefixKeywords[$prefix])) {
    		$prefix = $prefixKeywords[$prefix];
    	}
    	
    	if ($dir == 'desc') {
    		$priority = PHP_INT_MAX - $priority;
    	}
    	
    	$priority = $prefix . $priority;

    	return $priority;
    }
}
