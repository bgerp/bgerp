<?php 


/**
 * Имейл кутии
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_Inboxes extends core_Master
{
    
    
    /**
     * Плъгини за работа
     */
    var $loadList = 'email_Wrapper, plg_State, plg_Created, 
    				 plg_Modified, doc_FolderPlg, plg_RowTools, 
    				 plg_Rejected';
    
    
    /**
     * Да се създаде папка при създаване на нов запис
     */
    var $autoCreateFolder = 'instant';
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Имейл кутии";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, email';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin, email, manager';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin, email, manager';
    
    
    /**
     * 
     */
    var $canSingle = 'powerUser';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,manager,officer,executive';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'powerUser';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin, email';
    
    
    /**
     * Кой има права за
     */
    var $canEmail = 'ceo,manager,officer,executive';
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'doc_FolderIntf'; // Интерфейс за корица на папка
        
    
    /**
     * полета от БД по които ще се търси
     */
    var $searchFields = 'email';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Е-кутия';
    
    
    /**
     * Път към картинка 16x16
     */
    var $singleIcon = 'img/16/cover-inbox.png';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'email/tpl/SingleLayoutInboxes.shtml';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'email';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, email, accountId, inCharge, access, shared, createdOn, createdBy';
    
    
    /**
     * Всички пощенски кутии
     */
    static $allBoxes;
    
    
    /**
     * Дефолт достъп до новите корици
     */
    public $defaultAccess = 'private';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD("email", "email(link=no)", "caption=Имейл, mandatory, silent");
        $this->FLD("accountId", "key(mvc=email_Accounts, select=email)", 'caption=Сметка, refreshForm, mandatory, notNull, silent');
        
        $this->setDbUnique('email');
    }
    
    
    /**
     * Връща името
     */
    function getFolderTitle($id)
    {
        $rec = $this->fetch($id);
        
        $title = $rec->email;
        
        return strtolower($title);
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
        return $rec->email;
    }

    
 	/**
     * Изпълнява се след подготовката на формата за филтриране
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        $form = $data->listFilter;
        
        $form->FLD('userSelect' , 'user(roles=powerUser, rolesForTeams=manager|ceo|admin, rolesForAll=ceo|admin)', 'caption=Отговорник, refreshForm');
        
        $form->setDefault('userSelect', core_Users::getCurrent());
        
        // В хоризонтален вид
        $form->view = 'horizontal';
        
        // Добавяме бутон
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $form->showFields = 'accountId, userSelect';
        
        $form->input('accountId, userSelect', 'silent');
        $form->getFieldType('accountId')->params['allowEmpty'] = TRUE;
       
        if ($form->rec->accountId){
        	$data->query->where(array("#accountId = '[#1#]'", $form->rec->accountId));
        }
        
    	if ($form->rec->userSelect){
        	$data->query->where(array("#inCharge = '[#1#]'", $form->rec->userSelect));
        	$data->query->orLikeKeylist("shared", $form->rec->userSelect);
        }
    }
    
    
    /**
     * Преди рендиране на формата за редактиране
     * 
     * @param email_Inboxes $mvc
     * @param object $data
     */
    static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Вземам всички акаунти за които може да се създаде имейл
        $allAccounts = email_Accounts::getActiveAccounts(array('corporate', 'common'));
        
        if (!$allAccounts) {
            if (email_Accounts::haveRightFor('add')) {
                
                return redirect(array('email_Accounts', 'add'), FALSE, 'Моля добавете активна кутия.');
            } else {
                
                return redirect(array($mvc), FALSE, 'Няма активна кутия, която да се използва');
            }
        }
        
        $optAcc = array();
        foreach ($allAccounts as $id => $accRec) {
            $optAcc[$id] = $accRec->email;
        }
        $data->form->setOptions('accountId', $optAcc);
        
        // По подразбиране да е избрана корпоративната сметка
        $corporateAcc = email_Accounts::getCorporateAcc();
        if ($corporateAcc) {
            $defaultAccId = $corporateAcc->id;
        } else {
            $defaultAccId = key($optAcc);
        }
        
        $data->form->setDefault('accountId', $defaultAccId);
        
        if (!$data->form->rec->email) {
            $accRec = $allAccounts[$data->form->rec->accountId];
            list(, $domain) = explode('@', $accRec->email);
            $data->form->setParams('email', array('placeholder' => '...@' . $domain));
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param email_Inboxes $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        // Показва грешка, ако домейните не съвпадат
        if ($form->isSubmitted()) {
            $accRec = email_Accounts::fetch((int) $form->rec->accountId);
            
            list(,$accDomain) = explode('@', $accRec->email);
            
            list(, $emailDomain) = explode('@', $form->rec->email);
            
            if ($accDomain != $emailDomain) {
                $form->setError('email', 'Домейните на сметката и имейла трябва да съвпадат');
            }
        }
    }
    
    
    /**
     * Връща масив с ключове - кутите (имейлите) и стойности - id-тата на сметките към които са
     * Ако е зададена $accId филтрира и оставя само кутиите, които са към посочената сметка
     */
    static function getAllInboxes($acId = 0)
    {
        if (!self::$allBoxes[$acId]) {
            $query = static::getQuery();
            $query->show('id, email, accountId');
            
            while ($rec = $query->fetch()) {
                if(($accId == 0) || ($accId == $rec->accountId)) {
                    self::$allBoxes[$acId][$rec->email] = $rec->accountId;
                }
            }
        }
        
        return  self::$allBoxes[$acId];
    }
    
    
    /**
     * Намира първия имейл в стринга, който е записан в системата
     */
    static function getToBox($mime, $accId)
    {   
        $accRec = email_Accounts::fetch($accId);

        // Ако сметката е частна, то $toBox е нейния имейл
        if($accRec->type == 'single') {

            return $accRec->email;
        }
        
        // Вземаме всички имейли
        $emailsArr = type_Email::extractEmails(strtolower( 
            $mime->getHeader('X-Original-To', '*') . ' ' .
            $mime->getHeader('Delivered-To', '*') . ' ' .
            $mime->getHeader('To') . ' ' .
            $mime->getHeader('Cc')));

        // Ако няма никакви имейли, към които е изпратено писмото, $toBox е имейла на сметката
        if (!is_array($emailsArr) || !count($emailsArr)) {

            return $accRec->email;
        }

        // Всички вътрешни кутии към тази сметка
        $allBoxes = static::getAllInboxes($accId);
        
        // Търсим във всички съществуващи кутии
        foreach ($emailsArr as  $eml) {
                
            // Първия имейл, който отговаря на кутия е $toBox
            if ($allBoxes[$eml]) {
                    
                return $eml;
            }
        }
        
        // Ако сметката е корпоративна, то разглеждаме и евентуалните не-създадени-още кутии на powerUser-ите
        if($accRec->type == 'corporate') {
            
            // Вземаме масив от PowerUsers, като индекса е ника на потребителя
            $powerUsers = static::getPowerUsers();
            
            list(, $accDomain) = explode('@', $accRec->email);
            
            // Ако имейла е съставен от ник на потребител и домейн на корпоративна сметка
            // тогава създаваме кутия за този имейл, вързана към съответния потребител
            foreach ($emailsArr as $eml) {
                
                list($nick, $domain) = explode('@', $eml);
                
                if(!$nick || !$domain) continue;

                // Намираме потребител, съответстващ на емейл адреса
                $userRec = $powerUsers[$nick];
                
                // Ако няма такъв потребител
                if(!$userRec) continue;
                
                // Ако домейна на имейла  корпоративния домейн, то 
                // Създаваме кутия (основна) на потребителя, към този домейн
                // и връщаме имейла на тази кутия 
                if($accDomain == $domain)  {

                    $rec = new stdClass();
                    $rec->email = $eml;
                    $rec->accountId = $accRec->id;
                    $rec->inCharge  = $userRec->id;
                    $rec->access    = "private";
                    
                    self::save($rec);

                    return $rec->email;
                }
            }            
        }
        
        // По подразбиране, $toBox е емейла на кутията от където се тегли писмото
        return $accRec->email;
    }

    
    /**
     * При създаването на вербалния ред, добавя линк и икона в заглавието на сметката
     */
    function on_AfterRecToVerbal($mvc, $row, $rec, $fields)
    { 
        if(($fields['-list'] || $fields['-single']) && $rec->accountId) {
            
            $accRec = email_Accounts::fetch($rec->accountId);
            
            $accRow = email_Accounts::recToVerbal($accRec, 'id,email,-list');

            $row->accountId = $accRow->email;
        }
    }

    
    
    /**
     * Добавя акаунт, ако има зададен такъв в конфигурационния файл
     * и ако няма запис в базата
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {

    }
    
    
    /**
     * Определя дали един имейл адрес е "ОБЩ" или не е.
     *
     * @param string $email
     * @return boolean
     */
    public static function isGeneric($email)
    {
        $rec = email_Accounts::fetch("#email = '{$email}'");
        
        return (boolean)$rec && ($rec->applyRouting == 'yes');
    }
    
    
    /**
     * Форсира папката, с име този имейл. Ако папката липсва, но това е валиден 
     * имайл на PowerUser 
     *
     * @param string $email
     * @return int key(mvc=doc_Folders)
     */
    public static function forceFolder($email)
    {
        $folderId = NULL;

        $email = strtolower(trim($email));

        $rec = static::fetch("#email = '{$email}'");
        
        if (!$rec) {
            // Ако това е корпоративен имейл - създава кутията и папката към нея
            
            // Вземаме корпоративната сметка
            $corpAccRec = email_Accounts::getCorporateAcc();
        
            // Ако няма корпоративна сметка - връщаме FALSE
            if(!$corpAccRec) return FALSE;
            
            list($user, $domain) = explode('@', $email);
            
            if($domain == $corpAccRec->domain) {
                $powerUsers = email_Inboxes::getPowerUsers();
                if($userRec = $powerUsers[$user]) {

                    $rec = new stdClass();
                    $rec->email = $email;
                    $rec->accountId = $corpAccRec;
                    $rec->inCharge = $userRec->id;
                    $rec->access = 'private';

                    $folderId = static::forceCoverAndFolder($rec->id);
                }
            }
        } else {
            $folderId = static::forceCoverAndFolder($rec->id);
        }
        
        return $folderId;
    }
    
    
    /**
     * Връща id'то на кутия на потребителя, който сме подали.
     * Ако не сме подали параметър тогава връща на текущия потребител
     */
    static function getUserInboxId($userId = NULL)
    {
        //Ако не сме подали параметър, вземаме ник-а на текущия потребител
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        $email = email_Inboxes::getUserEmail($userId);
        
        // Ако потребителя няма корпоративен емейл, връшаме FALSE
        if(!$email) return FALSE;

        $id = email_Inboxes::fetchField("#email = '{$email}'");
        
        return $id;
    }
    
    
    /**
     * Връща имейл-а на потребителя
     * Ако е посочено id' или име на потребителя тогава връща него, в противен случай връща на текущия потребител
     */
    static function getUserEmail($userId = NULL)
    {   
        // Ако потребителите се регистрират с никове == имейлите им, 
        // то не можем да генерираме корпоративен имейл адрес
        if(defined('EF_USSERS_EMAIL_AS_NICK') && EF_USSERS_EMAIL_AS_NICK) {

            return FALSE;
        }

        // Ако не сме подали параметър, вземаме id-то на текущия потребител
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        if (!$userId || ($userId <= 0)) return FALSE;
        
        // Вземаме nick' а на потребителя
        $nick = core_Users::fetchField($userId, 'nick');

        // Вземаме корпоративната сметка
        $corpAccRec = email_Accounts::getCorporateAcc();
        
        // Ако няма корпоративна сметка - връщаме FALSE
        if(!$corpAccRec) return FALSE;
        
        // Генерираме имейл-а
        $email = $nick . '@' . $corpAccRec->domain;
        
        // Превръщаме имейл-а в малки букви
        $email = strtolower($email);
        
        return $email;
    }
    
    
    /**
     * Връща id' то на потребителя, който е inCharge на съответния имейл
     * 
     * @param email $email
     * 
     * @return integer $rec->inCharge
     */
    static function getEmailInCharge($email) 
    {
        // Тримваме имейла
        $email = trim($email);
        
        //Да е с малки букви
        $email = mb_strtolower($email);
        
        //Намираме записа за съответния имейл
        $rec = email_Inboxes::fetch("#email='{$email}'");
        
        //Връщаме inCharge id' то
        return $rec->inCharge;
    }
   


    /**
     *  Един документ ги изпращаме от:
     *
     *  1.0 Имейла, от който последно е изпращал имейл съответния потребитле
     *  1. Ако папката в която се намира документа е кутия към сметка, която може да изпраща писма - имейла на кутията
     *  2. Корпоративния общ имейл, ако корпоративната сметка може да изпраща писма
     *  3. Корпоративния имейл на потребителя, ако корпоративната сметка може да изпраща писма
     *  4. Всички шернати инбокс-имейли, които са към сметки, които могат да изпращат писма
     *  5. Всички инбокс-имейли, за които е отбелязано, че могат да се използват за изпращане на писма от всички потребители
     *
     */
    function on_BeforePrepareKeyOptions($mvc, &$options, $type)
    {
        $folderId = $type->params['folderId'];
        
        $options = array();
        
        if ($folderId) {
            try {
                $options = $mvc->getFromEmailOptions($folderId);
            } catch (Exception $e) {
                // Не се прави нищо
            }
            
            // Ако може да има празен запис
            if ($type->params['allowEmpty']) {
                $options = array('' => '') + $options;
            }
        }
    }

    
    /**
     * Редиректва към добавяне на кутия
     * @redirect
     */
    public static function redirect()
    {
        // Ако има права за добавяне редиректва към добавана на кутия
        if (self::haveRightFor('add')) {
            redirect(array('email_Inboxes', 'add', 'ret_url' => TRUE), FALSE, '|Трябва да добавите кутия за изпращане на имейл');
        } else {
            
            $msg = '|Трябва да имате поне една кутия за изпращане на имейл';
            if (self::haveRightFor('list')) {
                redirect(array('email_Inboxes', 'list', 'ret_url' => TRUE), FALSE, $msg);
            }else {
                $retUrl = getRetUrl();
                if ($retUrl) {
                    redirect($retUrl, FALSE, $msg);
                }
            }
            
            // Не би трябвало да се стигне до тук
            status_Messages::newStatus($msg);
        }
    }
    
    
    /**
     * Връща списък с [id на кутия] => имейл от които текущия потребител може да изпраща писма от папката
     * Първия имейл е най-предпочитания
     */
    static function getFromEmailOptions($folderId=FALSE, $userId=NULL, $personalOnly=FALSE)
    {
        $options = array();
        
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        // Ако е подадена папка и не е зададено да се показват само персоналните
        if ($folderId && !$personalOnly) {
            
            // 1.0 Първи да е имейла от който се е изпращал имейл последно от съответната папка от потребителя
            $key = doc_Folders::getSettingsKey($folderId);
            if ($userId > 0) {
                $settings = core_Settings::fetchKey($key, $userId);
                $defEmailId = (int)$settings['defaultEmail'];
                if ($defEmailId > 0) {
                    $options[$defEmailId] = self::fetchField("#id = '{$defEmailId}' && #state = 'active'", 'email');
                }
            }
            
            // 1. Ако папката в която се намира документа е кутия към сметка, която може да изпраща писма - имейла на кутията
            $rec = self::fetch("#folderId = {$folderId} && #state = 'active'");
            if($rec && email_Accounts::canSendEmail($rec->accountId)) {
                $options[$rec->id] = $rec->email;
            }
        }

        // Намираме сметка за входящи писма от корпоративен тип, с домейла на имейла
        $corpAccRec = email_Accounts::getCorporateAcc();
         
        if($corpAccRec && email_Accounts::canSendEmail($corpAccRec->id)) {
             
            // 2a. Общия корпоративен
            
            // Ако не е зададено да се показват само персоналните
            if (!$personalOnly) {
                
                $rec = self::fetch("#email = '{$corpAccRec->email}' && #state = 'active'");
                                
                if($rec) {
                    $options[$rec->id] = $rec->email;
                }
            }
            
            // 2b. Корпоративния на потребителя
            
            $userEmail = email_Inboxes::getUserEmail($userId);

            if($userEmail && ($rec = self::fetch("#email = '{$userEmail}' && #state = 'active'"))) {
                $options[$rec->id] = $rec->email;
            }
            
            //2a. Общия корпоративен
            //2b. Корпоративния на потребителя
        }

        // 3. Всички шернати инбокс-имейли, които са към сметки, които могат да изпращат писма
        // 3а. Имейлите, на които сме inCharge
        // 3b. Имейлите, които ни са споделени
        $query = self::getQuery();
        $query->where("#inCharge = {$userId}");
        
        // Ако не е зададено да се показват само персоналните
        if (!$personalOnly) {
            $query->orWhere("#shared LIKE '%|{$userId}|%'");
        }
        $query->where("#state = 'active'");
        $inChargeEmailArr = array();
        $sharedEmailArr = array();
        
        while($rec = $query->fetch()) {
            if(email_Accounts::canSendEmail($rec->accountId)) {
                
                // Ако потребителя е отговорник
                if ($rec->inCharge == $userId) {
                    $inChargeEmailArr[$rec->id] = $rec->email;
                } else {
                    
                    // Ако е споделен
                    
                    $sharedEmailArr[$rec->id] = $rec->email;
                }
            }
        }
        
        // Добавяме в резултатния масив
        $options = $options + $inChargeEmailArr + $sharedEmailArr;
        
        // Вече трябва да има открита поне една кутия

        expect(count($options), 'Липсват възможности за изпращане на писма. Настройте поне една сметка в Документи->Имейли->Сметки');
        
        return $options;
    }


    /**
     * Връща потребителите с ранг на корпоративен потребител: ceo, manager, officer, executive
     */
    static function getPowerUsers()
    {
        // Масив за съхранение на потребителите имащи право на пощенска кутия в системата
        static $powerUsers;
        
        // Намираме масив с потребителите, които имат право на вътрешен имейл
        if(!$powerUsers) {
            $userQuery = core_Users::getQuery();
            $powerRoles = core_Roles::getRolesAsKeylist('executive,officer,manager,ceo');
            $userQuery->likeKeylist('roles', $roles);
            while($uRec = $userQuery->fetch()) {
                $powerUsers[$uRec->nick] = $uRec;
            }
        }

        return $powerUsers;
    }
    
    
    /**
     * Премахва всички наши имейли от подададения масив с имейли
     * 
     * @param array $emailsArr - Масив с имейли
     * 
     * @return array $allEmailsArr - Масив с изчистените имейли
     */
    static function removeOurEmails($emailsArr)
    {
        // Данни за кеширане
        $cacheType = 'ourEmails';
        $cacheHandle = 'allEmails';
        $keepMinutes = 1000;
        $depends = array('email_Inboxes', 'email_Accounts');
        
        // Ако няма в кеша или е променен
        if (!$emailForRemove = core_Cache::get($cacheType, $cacheHandle, $keepMinutes, $depends)) {
            
            //Масив с имейли за премахване
            $emailForRemove = array();
            
            // Извличаме всички имейли
            $query = static::getQuery();
            while ($rec = $query->fetch()) {
                
                // Записваме имейлите в масив
                $emailForRemove[] = $rec->email;
            }
            
            // Записваме в кеша
            core_Cache::set($cacheType, $cacheHandle, $emailForRemove, $keepMinutes, $depends);
        }
        
        // Премахваме нашите имейли
        $allEmailsArr = array_diff($emailsArr, $emailForRemove);
        
        if (!$allEmailsArr) return $allEmailsArr;
        
        // Масив с всички корпоративни домейни
        $domainsArr = email_Accounts::getCorporateDomainsArr();
        
        // Обхождаме масива с останалите имейли
        foreach ($allEmailsArr as $key => $email) {
            
            // Вземаме домейна на имейла
            list(, $domain) = explode('@', $email);
            
            // Домейна в долен регистър
            $domain = mb_strtolower($domain);
            
            // Ако домейна съществува в нашите домейни
            if ($domainsArr[$domain]) {
                
                // Премахваме от масива
                unset($allEmailsArr[$key]);
            }
        }

        return $allEmailsArr;
    }
    
    
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако редактирам запис
        if ($action == 'edit' && $rec && $userId) {
            
            // Ако не сме администратор 
            if (!haveRole('admin')) {
                
                // Ако не е наш имейл или не ни е споделен
                if (($rec->inCharge != $userId) && !type_Keylist::isIn($userId, $rec->shared)) {
                    
                    // Не можем да редактираме
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Връща key опциите за достъпните имейли на потребителя
     * 
     * @param core_Type $type
     * 
     * @return array
     */
    static function getAllowedFromEmailOptions($type, $otherParams = array())
    {
        try {
            
            // Личните имейли на текущия потребител
            $emailOptions = email_Inboxes::getFromEmailOptions(FALSE, NULL, TRUE);
        } catch (core_exception_Expect $e) {
            $emailOptions[] = '';
        }
        
        return $emailOptions;
    }
}
