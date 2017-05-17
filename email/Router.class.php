<?php



/**
 * Рутира всички несортирани писма.
 *
 * Несортирани са всички писма от папка "Несортирани - [Титлата на класа email_Incomings]"
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @see       https://github.com/bgerp/bgerp/issues/108
 */
class email_Router extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, email_Wrapper';
    
    
    /**
     * Заглавие
     */
    var $title    = "Автоматични правила за рутиране";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, type, key, originLink=Източник, priority';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead   = 'admin';
    
    
    /**
     * Кой има право да пише?
     */
    var $canWrite  = 'no_one';
    
    
    /**
     * Кой има право да пише?
     */
    var $canAdd  = 'no_one';
	
	
	/**
	 * Кой може да го разглежда?
	 */
	var $canList = 'admin, debug';
    
	
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'no_one';
    
    
    /**
     * @todo Чака за документация...
     */
    const RuleFromTo = 'fromTo';
    
    
    /**
     * @todo Чака за документация...
     */
    const RuleFrom   = 'from';
    
    
    /**
     * @todo Чака за документация...
     */
    const RuleDomain = 'domain';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('type' , "enum(" . implode(', ', array(self::RuleFromTo, self::RuleFrom, self::RuleDomain)) . ")", 'caption=Тип');
        $this->FLD('key' , 'varchar(64)', 'caption=Ключ');
        $this->FLD('objectType' , 'enum(person, company, document)');
        $this->FLD('objectId' , 'int', 'caption=Обект');
        $this->FLD('priority' , 'varchar(21)', 'caption=Приоритет');

        $this->setDbIndex('objectType,objectId');
        $this->setdbIndex('type,key');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterPrepareListRows($mvc, $data)
    {
        $rows = $data->rows;
        $recs = $data->recs;
        
        if (is_array($recs)) {
            foreach ($recs as $i=>$rec) {
                $row = $rows[$i];
                $row->originLink = $mvc->calcOriginLink($rec);
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function calcOriginLink($rec)
    {
        expect($rec->objectId, $rec);
        
        $url = array();
        
        switch ($rec->objectType) {
            case 'person' :
                if (crm_Persons::haveRightFor('single', $rec->objectId)) {
                    $url = array('crm_Persons', 'single', $rec->objectId);
                }
                break;
            case 'company' :
                if (crm_Companies::haveRightFor('single', $rec->objectId)) {
                    $url = array('crm_Companies', 'single', $rec->objectId);
                }
                break;
            case 'document' :
                
                $doc = doc_Containers::getDocument($rec->objectId);
                if ($doc->haveRightFor('single')) {
                    $url = array($doc->instance, 'single', $doc->that);
                }
                
                break;
            default :
            expect(FALSE, $rec);
        }
        
        return ht::createLink("{$rec->objectType}:{$rec->objectId}", $url);
    }
    
    
    /**
     * Определя папката, в която да се рутира писмо от $fromEmail до $toEmail, според правило тип $rule
     *
     * @param string $fromEmail
     * @param string $toEmail има значение само при $type == email_Router::RuleFromTo, в противен
     * случай се игнорира (може да е NULL)
     * @param string $type email_Router::RuleFromTo | email_Router::RuleFrom | email_Router::RuleDomain
     * @return int key(mvc=doc_Folders)
     */
    public static function route($fromEmail, $toEmail, $type)
    {
        $key = static::getRoutingKey($fromEmail, $toEmail, $type);
        
        $rec = static::fetch(array("#type = '[#1#]' AND #key = '[#2#]'", $type, $key));
        
        $folderId = NULL;
        
        if ($rec && $rec->objectId) {
            // от $rec->objectType и $rec->objectId изваждаме folderId
            switch ($rec->objectType) {
                case 'document' :
                    $folderId = doc_Containers::fetchField($rec->objectId, 'folderId');
                    break;
                case 'person' :
                    if(crm_Persons::fetch($rec->objectId)) {
                        $folderId = crm_Persons::forceCoverAndFolder($rec->objectId);
                    }
                    break;
                case 'company' :
                    if(crm_Companies::fetch($rec->objectId)) {
                        $folderId = crm_Companies::forceCoverAndFolder($rec->objectId);
                    }
                    break;
                default :
                expect(FALSE, $rec->objectType . ' е недопустим тип на обект в правило за рутиране');
            }
            
            // Ако не сме успели да намерим $folderId значи обекта в правилото е невалиден 
            // Изтриваме правилото и отново извикваме тази функция
            if(!$folderId) {
                self::delete($rec->id);

                return self::route($fromEmail, $toEmail, $type);
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
     * @return array масив с индекс 'type' и стойност ключа от съответната тип
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
        
        // Нормализация на имейлите - само малки букви
        $fromEmail = strtolower($fromEmail);
        $toEmail   = strtolower($toEmail);
        
        $toEmail = email_Inboxes::replaceDomains($toEmail);
        
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
     * @param boolean $updateRec
     */
    static function saveRule($rule, $updateRec = TRUE)
    {
        $query = static::getQuery();
        $query->orderBy('priority', 'DESC');
        
        $rec = $query->fetch(array("#key = '[#1#]' AND #type = '[#2#]'", $rule->key, $rule->type));
        
        // Ако няма да се обновява записа и има такъв запис, не променяме стойността
        if (!$updateRec && $rec->id) return ;
        
        if (strcmp("{$rec->priority}", "{$rule->priority}") < 0) {
            // Досегашното правило за тази двойка <type, key> е с по-нисък приоритет
            // Обновяваме го
            $rule->id = $rec->id;
            expect($rule->objectType && $rule->objectId && $rule->key, $rule);
            static::save($rule);
        }
    }
    
    
    /**
     * Изтрива (физически) всички правила за <$objectType, $objectId>
     *
     * @param string $objectType enum(person, company, document)
     * @param int $objectId
     */
    static function removeRules($objectType, $objectId)
    {
        if ($objectType && $objectId) {
            static::delete(array("#objectType = '[#1#]' AND #objectId = '[#2#]'", $objectType, $objectId));
        }
    }
    
    
    /**
     * Дали домейна е на публична е-поща (като abv.bg, mail.bg, yahoo.com, gmail.com)
     *
     * @param string $domain TLD
     * @return boolean
     */
    static function isPublicDomain($domain)
    {
        return drdata_Domains::isPublic($domain);
    }
    
    
    /**
     * Генерира приоритет на правило за рутиране според зададена дата
     *
     * @param string $date
     * @param string $importance 'high' | 'mid' | 'low'
     * @param string $dir 'asc' | 'desc' посока на нарастване - при 'asc' по-новите дати
     * генерират по-високи приоритети, при 'desc' - обратно
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


    /**
     * Рутиране по номер на нишка
     *
     * Извлича при възможност нишката от наличната информация в писмото
     * Местата, където очакваме информация за манипулатор на тред са:
     *     o `In-Reply-To` (MIME хедър)
     *     o `Subject`
     *
     * @param stdClass $rec
     */
    static function doRuleThread($rec)
    {
        $rec->threadId = email_ThreadHandles::extractThreadFromSubject($rec->subject);
        
        if (!$rec->threadId) {
            $rec->threadId = static::extractThreadFromReplyTo($rec);
        }
        
        if ($rec->threadId) {
            if($rec->folderId = doc_Threads::fetchField($rec->threadId, 'folderId')) {
                // Премахване на манипулатора на нишката от събджекта
                $rec->subject = email_ThreadHandles::stripSubject($rec->subject, $rec->threadId);
            } else {
                // Зануляваме треда, защото съответстващата и папка не съществува
                unset($rec->threadId);
            }
        }

        return $rec->folderId;
    }
    
    
    /**
     * Извлича нишката от 'In-Reply-To' MIME хедър
     *
     * @param stdClass $rec
     * @return int първичен ключ на нишка или NULL
     */
    protected static function extractThreadFromReplyTo($rec)
    {
        if (!$rec->inReplyTo) {
            return NULL;
        }
        
        if (!($mid = self::extractMidFromMessageId($rec->inReplyTo))) {
            return NULL;
        }
        
        if (!($sentRec = doclog_Documents::fetchByMid($mid))) {
            return NULL;
        }
        
        // Документа
        $document = doc_Containers::getDocument($sentRec->containerId);
        
        // Ако е бласт имейл, връщаме NULL
        if (strtolower($document->className) == 'blast_emails') return NULL;
        
        $rec->originId = $sentRec->containerId;
        
        return $sentRec->threadId;
    }
    

    /**
     * Генерира MesasgeID за имейл от mid
     * 
     * @param integer $boxFrom
     * 
     * @return string
     */
    static function createMessageIdFromMid($mid, $boxFrom = NULL)
    {   
        $hash = str::addHash($mid, 8, 'MID');
        $hostName = self::getServerHostName($boxFrom);
        
        $res = "<" . $hash . '@' . $hostName . ">";
        
        return $res;
    }
    
    
    /**
     * Определя името на домейна от който изпращаме
     * 
     * @param integer $boxFrom
     * 
     * @return string
     */
    public static function getServerHostName($boxFrom = NULL)
    {
        static $hostNameArr = array();
        
        // Да не се определя повторно
        if ($hostNameArr[$boxFrom]) return $hostNameArr[$boxFrom];
        
        // Ако е подадена кутия, опитваме се да определим от акаунта
        if ($boxFrom) {
            $accId = email_Inboxes::fetchField($boxFrom, 'accountId');
            if ($accId) {
                $accEmail = email_Accounts::fetchField($accId, 'email');
            }
            
            if ($accEmail) {
                list(, $hostNameArr[$boxFrom]) = explode('@', $accEmail);
            }
            
            if ($hostNameArr[$boxFrom]) return $hostNameArr[$boxFrom];
        }
        
        // Ако не е определен опитваме се да определим от конфигуцията на BGERP_DEFAULT_EMAIL_DOMAIN
        if (defined('BGERP_DEFAULT_EMAIL_DOMAIN')) {
            $hostNameArr[$boxFrom] = BGERP_DEFAULT_EMAIL_DOMAIN;
            
            return $hostNameArr[$boxFrom];
        }
        
        // Ако не е определен - от резултата от gethostname
        if (function_exists('gethostname')) {
            $hostName = gethostname();
            if ($hostName !== FALSE) {
                $hostNameArr[$boxFrom] = $hostName;
                
                return $hostNameArr[$boxFrom];
            }
        }
        
        $hostNameArr[$boxFrom] = 'localhost.localdomain';
        
        return $hostNameArr[$boxFrom];
    }
    
    
    /**
     * Връща mid от MessageId на имейл
     */
    static function extractMidFromMessageId($messageId)
    {
        $messageId = trim($messageId, ' <>');
        
        list($messageId) = explode('@', $messageId);
        
        $mid = str::checkHash($messageId, 8, 'MID');
        
        // Deprecated, за съвмесимост със стария формат
        if(!$mid && defined(BGERP_DEFAULT_EMAIL_DOMAIN)) {
            $myDomain = preg_quote(BGERP_DEFAULT_EMAIL_DOMAIN, '/');
            $regex    = "/^(.+)@{$myDomain}\.mid$/";
            
            if (preg_match($regex, $messageId, $matches)) {
                $mid = $matches[1];
            }
        }
        
        return $mid;
    }
     

    /**
     * Рутира по правилото `From`
     */
    static function doRuleFrom($rec)
    {
        $rec->folderId = self::route($rec->fromEml, $rec->toBox, self::RuleFrom);

        return $rec->folderId;
    }

    
    /**
     * Рутира по правилото `FromTo`
     */
    static function doRuleFromTo($rec)
    {
        $rec->folderId = self::route($rec->fromEml, $rec->toBox, self::RuleFromTo);

        return $rec->folderId;
    }
    
    
    /**
     * Рутира по правилото `Domain`
     */
    static function doRuleDomain($rec)
    {
        $rec->folderId = self::route($rec->fromEml, $rec->toBox, self::RuleDomain);

        return $rec->folderId;
    }
    
    
    /**
     * Рутиране според държавата на изпращача
     */
    static function doRuleCountry($rec)
    {
        if ($rec->country) {

        	// Ако се наложи създаване на папка за несортирани писма от държава, 
            // aко е зададено кой да е отговорника взимаме него, иначе отговорника
            // трябва да е отговорника на кутията, до която е изпратено писмото, ако не е зададено в конфига
            
            if (!($inChargeUserId = $rec->inCharge)) {
                $inChargeUserId = email_Setup::get('UNSORTABLE_INCHARGE');
                $inChargeUserId = trim($inChargeUserId);
                
                if (!$inChargeUserId) {
                    $inChargeUserId = email_Inboxes::getEmailInCharge($rec->toBox);
                }
            }
            
            $rec->folderId = static::forceCountryFolder(
                $rec->country /* key(mvc=drdata_Countries) */,
                $inChargeUserId
            );
        }

        return $rec->folderId;
    }



    /**
     * Създава при нужда и връща ИД на папката на държава
     *
     * @param int $countryId key(mvc=drdata_Countries)
     * @return int key(mvc=doc_Folders)
     */
    static function forceCountryFolder($countryId, $inCharge)
    {
        $folderId = NULL;
        
        $countryName = drdata_Countries::getCountryName($countryId);

        if (!empty($countryName)) {
            $folderId = doc_UnsortedFolders::forceCoverAndFolder(
                (object)array(
                    'name'     => sprintf(email_Setup::get('UNSORTABLE_COUNTRY'), $countryName),
                    'inCharge' => $inCharge
                )
            );
        }
        
        return $folderId;
    }
    


    /**
     * Рутиране според `toBox`
     * 
     * Ако е необходимо, форсира се папката, съответстваща на `toBox`
     *
     * @param stdClass $rec
     */
    static function doRuleToBox($rec)
    {
        $rec->folderId = email_Inboxes::forceFolder($rec->toBox);

        if(!$rec->folderId) {
            $accRec = email_Accounts::fetch($rec->accId);
            $rec->folderId = email_Inboxes::forceFolder($accRec->email);
        }

        return $rec->folderId;
    }


    
    static function act_TestDateToPriority()
    {
        $date = dt::now();
        
        ob_start();
        
        echo "<pre>";
        echo "PHP_INT_MAX = " . PHP_INT_MAX . '<br/>';
        echo "dateToPriority('{$date}', 'low', 'desc')  = " . static::dateToPriority($date, 'low', 'desc') . '<br/>';
        echo "dateToPriority('{$date}', 'low', 'asc')   = " . static::dateToPriority($date, 'low', 'asc') . '<br/>';
        echo "dateToPriority('{$date}', 'mid', 'desc')  = " . static::dateToPriority($date, 'mid', 'desc') . '<br/>';
        echo "dateToPriority('{$date}', 'mid', 'asc')   = " . static::dateToPriority($date, 'mid', 'asc') . '<br/>';
        echo "dateToPriority('{$date}', 'high', 'desc') = " . static::dateToPriority($date, 'high', 'desc') . '<br/>';
        echo "dateToPriority('{$date}', 'high', 'asc')  = " . static::dateToPriority($date, 'high', 'asc') . '<br/>';
        echo "</pre>";
        
        return ob_get_clean();
    }


    /**
     * Поправя загубените връзки на данните от този модел
     */
    function repair()
    {
        $query = self::getQuery();
        while($rec = $query->fetch()) {
            if($rec->objectType == 'company') {
                if(!crm_Companies::fetch($rec->objectId)) {
                    self::delete($rec->id);
                    $missedCompanies .= ', ' . $rec->objectId;
                }
            } elseif($rec->objectType == 'person') {
                if(!crm_Persons::fetch($rec->objectId)) {
                    self::delete($rec->id);
                    $missedPersons .= ', ' . $rec->objectId;
                }
            } elseif($rec->objectType == 'document') {
                if(!doc_Containers::fetch($rec->objectId)) {
                    self::delete($rec->id);
                    $missedDocuments .= ', ' . $rec->objectId;
                }
            }
        }
        
        if($missedCompanies) {
            $html .= "<li> Липсващи фирми: {$missedCompanies} </li>";
        }
        
        if($missedPersons) {
            $html .= "<li> Липсващи лица: {$missedPersons} </li>";
        }

        if($missedDocuments) {
            $html .= "<li> Липсващи документи: {$missedDocuments} </li>";
        }

        return $html;
    }
}
