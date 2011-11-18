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

    var $listFields = 'id, type, key, folderId';

    var $canRead   = 'admin,email';
    var $canWrite  = 'admin,email';
    var $canReject = 'admin,email';
    
    /**
     *  Име на папката, където отиват писмата неподлежащи на сортиране
     */ 
    const UnsortableFolderName = 'Unsorted - Internet';

    /**
     *  Шаблон за име на папките, където отиват писмата от дадена държава и неподлежащи на 
     *  по-адекватно сортиране
     */ 
    const UnsortableCountryFolderName = 'Unsorted - %s';


    function description()
    {
        $this->FLD('type' , 'enum(fromTo, from, sent, domain)', 'caption=Тип');
        $this->FLD('key' , 'varchar(64)', 'caption=Ключ');
        $this->FLD('folderId' , 'key(mvc=doc_Folders)', 'caption=Папка');
        
        defIfNot('UNSORTABLE_EMAILS', self::UnsortableFolderName);
        defIfNot('UNSORTABLE_COUNTRY_EMAILS', self::UnsortableCountryFolderName);
    }
    
    /**
     * Рутира всички нерутирани до момента писма.
     * 
     * Нерутирани са писмата, намиращи се в специална папка за нерутирани писма
     *
     */
    function routeAll($limit = 10)
    {
    	$incomingQuery    = email_Messages::getQuery();
    	$incomingFolderId = email_Messages::getUnsortedFolder();

    	$incomingQuery->where("#folderId = {$incomingFolderId}");
    	$incomingQuery->limit($limit);
    	
    	while ($emailRec = $incomingQuery->fetch()) {
    		if ($location = $this->route($emailRec)) {
    			email_Messages::move($emailRec, $location);
    		}
    	}
    }
    
    function act_RouteAll() {
    	$this->routeAll();
    }
    
    /**
     * Рутира писмо.
     * 
     * Формално, задачата на този метод е да определи максимално смислени стойности на полетата
     * $rec->folderId и $rec->threadId.
     * 
     * Определянето на тези полета зависи от предварително дефинирани правила
     * (@see https://github.com/bgerp/bgerp/issues/108):
     * 
     * - Според треда или InReplayTo хедъра. Информация за треда - от email_Sent
     * - Според пощенската кутия на получателя, ако тя не е generic
     * - Според FromTo правилата
     * - Според From правилата
     * - Според Sent правилата
     * - Според наличните данни във визитките (Това е за отделен клас)
     * - Според domain правилата
     * - Според държавата на изпращача (unsorted държава()
     * - Останалите несортирани в Unsorted - Internet.
     *
     * @param StdClass $rec запис на модела @link email_Messages
     * @return doc_Location новото местоположение на документа
     * @throws core_Exception_Expect когато рутирането е невъзможно
     */
    function route($rec)
    {
    	static $routeRules = array(
    		'Thread',
    		'BypassAccount',
    		'Recipient',
    		'FromTo',
    		'Sender',
    		'Sent',
    		'Crm',
    		'Domain',
    		'Country',
    		'Account',
    		'Unsorted',
    	);
    	
    	$location = new doc_Location();
    	
    	// Опитваме последователно правилата за рутиране
    	foreach ($routeRules as $rule) {
    		$method = 'routeBy' . $rule;
    		if (method_exists($this, $method)) {
    			$this->{$method}($rec, $location);
    			if (!is_null($location->folderId) || !is_null($location->threadId)) {
    				// Правило сработи. Запомняме го и прекратяваме обиколката на правилата.
    				// Писмото е рутирано.
    				$location->routeRule = $rule;
    				return $location;
    			}
    		}
    	}
    	
    	// Задължително поне едно от правилата би трябвало да сработи!
    	expect(FALSE, 'Невъзможно рутиране');
    }
    
    /**
     * Правило за рутиране към съществуваща нишка (thread).
     *
     * Извлича при възможност нишката в която да отиде писмото.
     *
     * @param StdClass $rec запис на модела @link email_Messages
     * @param doc_Location новото местоположение на документа
     */
    protected function routeByThread($rec, $location)
    {
    	/*
    	 * @TODO: 
    	 * 
    	 * инспектиране на InReplyTo: MIME хедъра; 
    	 * инспектиране на Subject
    	 * 
    	 * ако има валиден тред - това е резултата
    	 * 
    	 * Информация за валидността на тред се съдържа в модела на изпратените писма
    	 * @see email_Sent
    	 * 
    	 */
    }
    
    /**
     * Рутиране на писма, изтеглени от "bypass account"
     * 
     * Bypass account e запис от модела @see email_Accounts, за който е указано, че писмата му
     * не подлеждат на стандартното сортиране и се разпределят директно в папкана на акаунта.
     *
     * @param StdClass $rec запис на модела @link email_Messages
     * @param doc_Location $location новото местоположение на документа
     */
    protected function routeByBypassAccount($rec, $location)
    {
    	if ($this->isBypassAccount($rec->accId)) {
	    	$location->folderId = $this->forceAccountFolder($rec->accId); 
    	}
    }
    
    
    /**
     * Правило за рутиране според пощенската кутия на получателя
     * 
     * Правилото сработва само за НЕ-основни пощенски кутии на получател.
     *
     * @param StdClass $rec запис на модела @link email_Messages
     * @param doc_Location $location новото местоположение на документа
     */
    protected function routeByRecipient($rec, $location)
    {
    	if (!$this->isGenericRecipient($rec->to)) {
    		$location->folderId = $this->getRecipientFolder($rec->to);
    	}
    }
    
    
    /**
     * Правило за рутиране според <From, To> (type = 'fromTo')
     *
     * @param StdClass $rec запис на модела @link email_Messages
     * @param doc_Location $location новото местоположение на документа
     */
    protected function routeByFromTo($rec, $location)
    {
    	if (!$this->isGenericRecipient($rec->to)) {
    		$this->routeByRule('fromTo', $rec, $location);
    	}
    }
    
    
    /**
     * Правило за рутиране според изпращача на писмото (type = 'from')
     *
     * @param StdClass $rec запис на модела @link email_Messages
     * @param doc_Location $location новото местоположение на документа
     */
    protected function routeBySender($rec, $location)
    {
    	return $this->routeByRule('from', $rec, $location);
    }
    
    
    /**
     * Правило за рутиране според изпращача на писмото (type = 'sent')
     *
     * @param StdClass $rec запис на модела @link email_Messages
     * @param doc_Location $location новото местоположение на документа
     */
    protected function routeBySent($rec, $location)
    {
    	return $this->routeByRule('sent', $rec, $location);
    }
    
    
    /**
     * Правило за рутиране според данните за изпращача, налични в CRM
     *
     * @param StdClass $rec запис на модела @link email_Messages
     * @param doc_Location $location новото местоположение на документа
     */
    protected function routeByCrm($rec, $location)
    {
    	if ($folderId = $this->getCrmFolderId($rec->from)) {
    		$location->folderId = $folderId;
    	}
    }
    
    
    /**
     * Правило за рутиране според домейна на имейл адреса на изпращача (type = 'domain')
     *
     * @param StdClass $rec запис на модела @link email_Messages
     * @param doc_Location $location новото местоположение на документа
     */
    protected function routeByDomain($rec, $location)
    {
    	return $this->routeByRule('domain', $rec, $location);
    }
    
    
    /**
     * Правило за рутиране според държавата на изпращача.
     * 
     * @param StdClass $rec запис на модела @link email_Messages
     * @param doc_Location $location новото местоположение на документа
     */
    protected function routeByCountry($rec, $location)
    {
    	// $rec->country съдържа key(mvc=drdata_Countries)

    	$location->folderId = $this->forceCountryFolder($rec->country); 
    }
    

    /**
     * Прехвърляне на писмо в папката на акаунта, от който то е извлечено.
     * 
     * @param StdClass $rec запис на модела @link email_Messages
     * @param doc_Location $location новото местоположение на документа
     */
    protected function routeByAccount($rec, $location)
    {
    	$location->folderId = $this->getAccountFolderId($rec->accId);
    }
    
    
    /**
     * Прехвърляне на писмо в нарочна папка за несортируеми писма (@see email_Router::UnsortableFolderName)
     * 
     * Последната инстанция в процеса за сортиране на писма. Това правило сработва безусловно,
     * ако никое друго не е дало резултат. Идеята писмата, нерутираните писма (поради грешки в 
     * системата или поради неконсистентни данни) все пак да влязат (формално) коректно в 
     * документната система. Ако всичко е наред, папката с несортирани писма трябва да бъде
     * празна.
     *
     * @param StdClass $rec запис на модела @link email_Messages
     * @return doc_Location новото местоположение на документа.
     */
    protected function routeByUnsorted($rec, $location)
    {
    	$location->folderId = $this->forceOrphanFolder();
    }
    
    /**
     * Намира и прилага за писмото записано правило от даден тип.
     *
     * @param string $type (fromTo | from | sent | domain)
     * @param doc_Location $location новото местоположение на документа
     */
    protected function routeByRule($type, $rec, $location)
    {
    	// изчисляваме ключа според типа (и самото писмо) 
    	$key = $this->getRuleKey($type, $rec);
    	
    	if ($key === FALSE) {
    		// Неуспех при изчислението на ключ - правилото пропада.
    		return;
    	}

    	// Извличаме (ако има) правило от тип $type и с ключ $key
    	$ruleRec = $this->fetchRule($type, $key);
    	
    	if ($ruleRec->folderId) {
    		$location->folderId = $ruleRec->folderId;
    	}

    	return $location;
    }
    
    /**
     * Извлича от БД правило от определен тип и с определен ключ 
     *
     * @param string $type
     * @param string $key
     */
    protected function fetchRule($type, $key)
    {
    	$query = $this->getQuery();
    	$ruleRec = $query->fetch("#type = '{$type}' AND #key = '{$key}'");
    }
    
    
    /**
     * Намира ключа от даден тип за писмото $rec
     * 
     * Ключа се определя от типа и данни в самото писмо.
     *
     * @param string $type (fromTo | from | sent | domain)
     * @param StdClass $rec запис на модела @link email_Messages
     */
    protected function getRuleKey($type, $rec)
    {
    	$key = false;
    	
    	switch ($type) {
    		case 'fromTo':
    			if ($rec->from && $rec->to) {
    				$key = $rec->from . '|' . $rec->to;
    			}
    			break;
    		case 'from':
    			if ($rec->from) {
    				$key = $rec->from;
    			}
    			break;
    		case 'sent':
    			if ($rec->from) {
    				$key = $rec->from;
    			}
    			break;
    		case 'domain':
    			$key = $this->extractDomain($rec->from);
    			break;
    	}
    	
    	return $key;
    }
    
    /**
     * Извлича домейна на имейл адрес
     *
     * @param string $email
     * @return string FALSE при проблем с извличането на домейна
     */
    protected function extractDomain($email)
    {
    	list(, $domain) = explode('@', $email, 2);
    	
    	if (empty($domain)) {
    		$domain = FALSE;
    	}
    	return $domain;
    }
    
    /**
     * Маркиран ли е акаунта като "байпас акаунт"?
     *
     * @param int $accountId - key(mvc=email_Accounts)
     * @return bool TRUE - да, байпас акаунт; FALSE - не, "нормален" акаунт
     */
    protected function isBypassAccount($accountId)
    {
    	$isBypass = FALSE;
    	
    	if ($accountId) {
    		$isBypass = email_Accounts::fetchField($accountId, 'bypassRoutingRules');
    	}
    	
    	return $isBypass;
    }
    
    
    /**
     * Създава при нужда и връща ИД на папката на държава
     *
     * @param int $countryId key(mvc=drdata_Countries)
     * @return int key(mvc=doc_Folders)
     */
    protected function forceCountryFolder($countryId)
    {
    	$folderId = NULL;
    	
    	if ($countryId) {
    		$countryName = drdata_Countries::fetchField($countryId);
    	}
    	
    	if (!empty($countryName)) {
    		$folderId = email_Unsorted::forceCoverAndFolder(
    			(object)array(
    				'name' => sprintf(UNSORTABLE_COUNTRY_EMAILS, $countryName)
    			)
    		);
    	}
    	
    	return $folderId;
    }
    
    
    /**
     * Създава при нужда и връща ИД на папката на акаунт
     *
     * @param int $accountId - key(mvc=email_Accounts)
     * @return int key(mvc=doc_Folders)
     */
    protected function forceAccountFolder($accountId)
    {
    	return email_Accounts::forceCoverAndFolder(
    		(object)array(
    			'id' => $accountId
    		)
    	);
    }
    
    
    /**
     * Създава (ако липсва) и връща папката за писма с проблемно сортиране.
     *
     * @return int key(mvc=doc_Folders)
     */
    protected function forceOrphanFolder()
    {
		return email_Unsorted::forceCoverAndFolder(
    		(object)array(
    			'name' => UNSORTABLE_EMAILS
    		)
    	);    	
    }
    
    
    /**
     * Проверка дали даден имейл адрес е основен или не.
     *
     * @param string $email
     * @return boolean
     */
    protected function isGenericRecipient($email)
    {
    	/**
    	 * @TODO 
    	 */
    }
    
    
    /**
     * Папката асоциирана с (наш) имейл адрес
     *
	 * Ако разпознае имейл адреса - форсира папката му. В противен случай папка не се създава и 
	 * резултата е NULL. 
	 * 
     * @param string $email
     * @return int key(mvc=doc_Folders) NULL, ако няма съответстваща папка
     */
    protected function getRecipientFolder($email)
    {
    	/**
    	 * @TODO 
    	 */
	}
	
	
	/**
	 * Папката, асоциирана с CRM визитка
	 * 
	 * Ако намери визитка, форсира папката й. В противен случай папка не се създава и резултата 
	 * е NULL.
	 *
	 * @param string $email
	 * @return int key(mvc=doc_Folders) NULL ако няма съответстваща визитка в CRM
	 */
	protected function getCrmFolderId($email)
	{
		/**
		 * @TODO
		 */
	}
	
	/**
	 * Папката асоциирана с email_Account
	 * 
	 * Ако акаунта е валиден, форсира папката му. В противен случай папка не се създава и 
	 * резултата е NULL.
	 *
	 * @param int $accId key(mvc=email_Accounts)
	 * @return int key(mvc=doc_Folders) NULL при несъщесвуващ или невалиден акаунт
	 */
	protected function getAccountFolderId($accId)
	{
		/**
		 * @TODO
		 */
	}
}