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
        $this->FLD('type' , 'enum(fromTo, from, to, domain)', 'caption=Тип');
        $this->FLD('key' , 'varchar(64)', 'caption=Ключ');
        $this->FLD('containerId' , 'key(mvc=doc_Containers)');
        $this->FLD('folderId' , 'key(mvc=doc_Folders)', 'caption=Папка');
        $this->FLD('priority' , 'int', 'caption=Приоритет');
        
        defIfNot('UNSORTABLE_EMAILS', self::UnsortableFolderName);
        defIfNot('UNSORTABLE_COUNTRY_EMAILS', self::UnsortableCountryFolderName);
    }
    
    /**
     * Рутира всички нерутирани до момента писма.
     * 
     * Нерутирани са писмата, намиращи се в специална папка за нерутирани писма
     *
     */
    function routeAll($limit = 1)
    {
    	$incomingQuery    = email_Messages::getQuery();
    	$incomingFolderId = email_Messages::getUnsortedFolder();

    	$incomingQuery->where("#folderId = {$incomingFolderId}");
    	$incomingQuery->limit($limit);
    	
    	while ($emailRec = $incomingQuery->fetch()) {
    		if ($location = $this->route($emailRec)) {
    			// Преместваме нишката, в която е писмото на новоопределената локация (папка, нишка)
    			doc_Threads::move($emailRec->threadId, $location->folderId);
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
     * - Според To правилата
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
//    		'Thread',
    		'BypassAccount',
    		'Recipient',
    		'FromTo',
    		'From',
    		'To',
//    		'Crm',
    		'Domain',
    		'Country',
    		'Account',
    		'Unsorted',
    	);
    	
    	$location = new doc_Location();
    	
    	// Опитваме последователно правилата за рутиране
    	foreach ($routeRules as $rule) {
    		$method = 'routeBy' . ucfirst($rule);
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
//    	expect(FALSE, 'Невъзможно рутиране');
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
    	$location->threadId = $this->extractThreadId($rec);
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
    protected function routeByFrom($rec, $location)
    {
    	return $this->routeByRule('from', $rec, $location);
    }
    
    
    /**
     * Правило за рутиране според изпращача на писмото (type = 'to')
     *
     * @param StdClass $rec запис на модела @link email_Messages
     * @param doc_Location $location новото местоположение на документа
     */
    protected function routeByTo($rec, $location)
    {
    	return $this->routeByRule('to', $rec, $location);
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
    	if ($rec->country) {
    		$location->folderId = $this->forceCountryFolder($rec->country /* key(mvc=drdata_Countries) */);
    	}
    }
    

    /**
     * Прехвърляне на писмо в папката на акаунта, от който то е извлечено.
     * 
     * @param StdClass $rec запис на модела @link email_Messages
     * @param doc_Location $location новото местоположение на документа
     */
    protected function routeByAccount($rec, $location)
    {
    	$location->folderId = $this->forceAccountFolder($rec->accId /* key(mvc=email_Accounts) */);
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
     * @param string $type (fromTo | from | to | domain)
     * @param doc_Location $location новото местоположение на документа
     */
    protected function routeByRule($type, $rec, $location)
    {
    	// изчисляваме ключа според типа (и самото писмо) 
    	$doc  = doc_Containers::getDocument($rec->containerId);
    	$keys = $doc->getRoutingKeys($type);
    	
    	if (empty($keys[$type])) {
    		// Неуспех при изчислението на ключ - правилото пропада.
    		return;
    	}
    	
    	$key = $keys[$type]->key;

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
    	$query = static::getQuery();
    	$query->orderBy('priority', 'DESC');
    	
    	$ruleRec = $query->fetch("#type = '{$type}' AND #key = '{$key}'");
    	
    	return $ruleRec;
    }
    
    
    /**
     * Обновява правилата за рутиране.
     * 
     * Извиква се всеки път след преместване на нишка в друга папка.
     *
     * @param int $containerId key(mvc=doc_Containers)
     * @param int $folderId key(mvc=doc_Folders)
     */
    function updateRoutingRules($containerId, $folderId)
    {
		$doc = doc_Containers::getDocument($containerId);
		
		$keys = $doc->getRoutingKeys();
		
		foreach ($keys as $type=>$data) {
			$query = static::getQuery();
			$query->orderBy('priority', 'DESC');
			
			$rec = $query->fetch("#key = '{$data->key}' AND #type = '{$type}'");
			
			if (!$rec) {
				$rec = new stdClass();
			}
			
			if ($rec->priority < $data->priority) {
				$rec->type        = $type;
				$rec->priority    = $data->priority;
				$rec->key         = $data->key;
				$rec->containerId = $containerId;
				$rec->folderId    = $folderId;

				static::save($rec);
			}
		}
    }
    

    /**
     * Извлича при възможност треда от наличната информация в писмото
     * 
     * Първо се прави опит за извличане на тред от MIME хедърите и ако той пропадне, тогава се
     * прави опит за извличане на тред от subject-а. 
     *
     * @param StdClass $rec запис на модела @link email_Messages
     * @return int key(mvc=doc_Threads) NULL ако треда не може да бъде извлечен
     */
    protected function extractThreadId($rec)
    {
    	$threadId = NULL;
    	
    	// Опит за извличане на ключ на тред от MIME хедърите
    	$threadKeyHdr = $this->extractHdrThreadKey($rec->headers);

    	if (!empty($threadKeyHdr)) {
    		$threadId = static::getThreadByHandle($threadKeyHdr);	
    	}
    	
    	if (empty($threadId)) {
    		// Опит за извличане на ключ на тред от subject. В един събджект може да нула или 
    		// повече кандидати за хендлъри на тред.
    		$threadHnds = static::extractSubjectThreadHnds($rec->subject);
    		
    		// Премахваме кандидата, който е маркиран като хендлър на тред от друга инстанция
    		// на BGERP. Това маркиране става чрез MIME хедъра 'X-Bgerp-Thread'
    		if (!empty($rec->headers['X-Bgerp-Thread']) && !empty($threadHnds[$rec->headers['X-Bgerp-Thread']])) {
    			unset($threadHnds[$rec->headers['X-Bgerp-Thread']]);
    		}
    		
    		// Намираме първия кандидат за тред-хендлър на който съответства съществуващ тред. 
	    	foreach ($threadHnds as $handle) {
	    		$threadId = static::getThreadByHandle($handle);
	    		if (!empty($threadId)) {
	    			break;
	    		}
	    	}
    	}
    	
    	return $threadId;
    }
    
    
    /**
     * Намира тред по хендъл на тред.
     *
     * @param string $handle хендъл на тред
     * @return int key(mvc=doc_Threads) NULL ако няма съответен на хендъла тред
     */
    protected static function getThreadByHandle($handle)
    {
    	return doc_Threads::getByHandle($handle);
    }
    
    
    /**
     * Извлича всички (кандидати за) ключове на тред от събджекта на писмо
     *
     * @param string $subject
     * @return array
     * 
     */
    static function extractSubjectThreadHnds($subject)
    {
    	$key = array();
    	
    	if (preg_match_all('/<([a-z\d]{4,})>/i', $subject, $matches)) {
    		$key = arr::make($matches[1], TRUE);
    	}
    	
    	return $key;
    }
    
    
    /**
     * Извлича ключ на тред от MIME хедърите на писмо (ако има)
     *
     * @param array $headers
     * @return string
     */
    protected function extractHdrThreadKey($headers)
    {
    	$key = FALSE;
    	
    	if (!empty($headers['In-Reply-To'])) {
    		$key = $headers['In-Reply-To'];
    	}
    	
    	return $key;
    }
    
    
    /**
     * Намира ид на тред според ключ на тред.
     *
     * Информация за валидността на тред се съдържа в модела на изпратените писма
     * @see email_Sent
     * 
     * @param string $threadKey ключ на тред
     * @return int key(mvc=doc_Threads) NULL ако на ключа не отговаря съществуващ тред.
     */
    protected function getThreadByKey($threadKey)
    {
    	return email_Sent::fetchField("#threadHnd = '{$threadKeySubject}'", 'threadId');
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
    		$isBypass = (email_Accounts::fetchField($accountId, 'bypassRoutingRules') == 'yes');
    	}
    	
    	return $isBypass;
    }
    
    
    /**
     * Създава при нужда и връща ИД на папката на държава
     *
     * @param int $countryId key(mvc=drdata_Countries)
     * @return int key(mvc=doc_Folders)
     */
    function forceCountryFolder($countryId)
    {
    	$folderId = NULL;
    	
    	/**
    	 * @TODO: Идея: да направим клас email_Countries (или може би bgerp_Countries) наследник 
    	 * на drdata_Countries и този клас да стане корица на папка. Тогава този метод би 
    	 * изглеждал така:
    	 * 
    	 * $folderId = email_Countries::forceCoverAndFolder(
    	 * 		(object)array(
    	 * 			'id' => $countryId
    	 * 		)
    	 * );
    	 * 
    	 * Това е по-ясно, а и зависимостта от константата UNSORTABLE_COUNTRY_EMAILS отива на
    	 * 'правилното' място.
    	 */
    	
    	$countryName = $this->getCountryName($countryId);
    	
    	if (!empty($countryName)) {
    		$folderId = doc_UnsortedFolders::forceCoverAndFolder(
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
    function forceAccountFolder($accountId)
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
		return doc_UnsortedFolders::forceCoverAndFolder(
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
	
	protected function getCountryName($countryId)
	{
    	if ($countryId) {
    		$countryName = drdata_Countries::fetchField($countryId, 'commonName');
    	}
    	
    	return $countryName;
	}
}