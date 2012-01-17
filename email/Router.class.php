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
    }
    
    
    /**
     * Определя папката, в която да се рутира писмо от $fromEmail до $toEmail, според правило тип $rule
     *
     * @param string $fromEmail
     * @param string $toEmail има значение само при $type == email_Router::RuleFromTo, в противен
     * 						  случай се игнорира (може да е NULL) 
     * @param string $type email_Router::RuleFromTo | email_Router::RuleFrom | email_Router::RuleDomain
     * @return int key(mvc=doc_Folders) 
     */
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
     * Използва се за простота вместо @see email_Router::route()
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
	    	if (!static::isPublicDomain($domain = type_Email::domain($fromEmail))) {
	    		$keys[self::RuleDomain] = str::convertToFixedKey($domain);
	    	}
    	}
    	
    	if (count($keys) <= 1) {
    		$keys = reset($keys);
    	}
    	
    	return $keys;
    }
    
    
    /**
     * Добавя правило ако е с по-висок приоритет от всички налични правила със същия ключ и тип.
     * 
     * @param stdClass $rule запис на модела email_Router
     */
    static function saveRule($rule)
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
    
    
    /**
     * Изтрива (физически) всички правила за <$objectType, $objectId>
     *
     * @param string $objectType enum(person, company, document)
     * @param int $objectId
     */
    function removeRules($objectType, $objectId)
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
    
    
    /**
     * Генерира приоритет на правило за рутиране според зададена дата
     *
     * @param string $date
     * @param string $importance 'high' | 'mid' | 'low'
     * @param string $dir 'asc' | 'desc' посока на нарастване - при 'asc' по-новите дати 
     * 						генерират по-високи приоритети, при 'desc' - обратно
     */
    static function dateToPriority($date, $importance = 'high', $dir = 'asc')
    {
    	$priority = dt::mysql2timestamp($date);
    	$dir      = strtolower($dir);
    	$importance   = strtolower($importance);
    	
    	$prefixKeywords = array(
    		'high' => '30',
    		'mid'  => '20',
    		'low'  => '10'
    	);
    	
    	if (!empty($prefixKeywords[$importance])) {
    		$importance = $prefixKeywords[$importance];
    	}
    	
    	if ($dir == 'desc') {
    		$priority = PHP_INT_MAX - $priority;
    	}
    	
    	$priority = $importance . $priority;

    	return $priority;
    }
}
