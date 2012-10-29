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
    var $loadList = 'email_Wrapper, plg_State, plg_Created, plg_Modified, doc_FolderPlg, plg_RowTools, plg_CryptStore';
    
    
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
    var $canEdit = 'user';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,manager,';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,manager,officer,executive';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,manager,officer,executive';
    
    
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
    var $interfaces =
    // Интерфейс за корица на папка
    'doc_FolderIntf';
    
    
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
    var $singleIcon = 'img/16/inbox-image-icon.png';
    
    
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
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD("email", "email(link=no)", "caption=Имейл");
        $this->FLD("accountId", "key(mvc=email_Accounts, select=email)", 'caption=Сметка');
         
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
     * Преди рендиране на формата за редактиране
     */
    static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $data->form->setDefault('access', 'private');
    }
    
    
    /**
     * Връща масив с ключове - кутите (имейлите) и стойности - id-тата на сметките към които са
     */
    static function getAllInboxes()
    {
        if (!self::$allBoxes) {
            $query = static::getQuery();
            $query->show('id, email, accountId');
            
            while ($rec = $query->fetch()) {
                self::$allBoxes[$rec->email] = $rec->accountId;
            }
        }
        
        return self::$allBoxes;
    }
    
    
    /**
     * Намира първия имейл в стринга, който е записан в системата
     */
    static function findFirstInbox($str)
    {   
        //Всички пощенски кутии
        $allBoxes = static::getAllInboxes();
        
        //Вземаме всички имейли
        $emailsArr = email_Mime::extractEmailsFrom(strtolower($str));
        
        //Ако има имейли
        if (is_array($emailsArr) && count($emailsArr)) {

            // Търсим във всички съществуващи кутии
            foreach ($emailsArr as  $eml) {
                
                //Намираме първото съвпадение на имейл, който е 'internal'
                if ($allBoxes[$eml]) {
                    
                    return $eml;
                }
            }
            
            // Вземаме масив от PowerUsers, като индекса е ника на потребителя
            $powerUsers = static::getPowerUsers();
            
            // Ако имейла е съставен от ник на потребител и домейн на корпоративна сметка
            // тогава създаваме кутия за този имейл, вързана към съответния потребител
            foreach ($emailsArr as $eml) {
                
                list($nick, $domain) = explode('@', $eml);
                
                // Намираме потребител, съответстващ на емейл адреса
                $userRec = $powerUsers[$nick];
                
                // Ако няма такъв потребител - прекратяваме обработката
                if(!$userRec) break;

                // Намираме сметка за входящи писма от корпоративен тип, с домейла на имейла
                $corpAccRec = email_Accounts::getCorporateAcc();
                
                // Ако няма такава сметка - прекратяваме обработката
                if(!$corpAccRec) break;
                
                // Ако домейна на имейла  корпоративния домейн, то 
                // Създаваме кутия (основна) на потребителя, към този домейн
                // и връщаме имейла на тази кутия
                if($corpAccRec->domain == $domain)  {

                    $rec = new stdClass();
                    $rec->email = $eml;
                    $rec->accountId = $corpAccRec->id;
                    $rec->inCharge  = $userRec->id;
                    $rec->access    = "private";
                    
                    self::save($rec);

                    return $rec->email;
                }
            }
        }
        
        return NULL;
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
        if(EF_USSERS_EMAIL_AS_NICK) {

            return FALSE;
        }

        // Ако не сме подали параметър, вземаме id-то на текущия потребител
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
                
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
     *  1. Ако папката в която се намира документа е кутия към сметка, която може да изпраща писма - имейла на кутията
     *  2. Корпоративния общ имейл, ако корпоративната сметка може да изпраща писма
     *  3. Корпоративния имейл на потребителя, ако корпоративната сметка може да изпраща писма
     *  4. Всички шернати инбокс-имейли, които са към сметки, които могат да изпращат писма
     *  5. Всички инбокс-имейли, за които е отбелязано, че могат да се използват за изпращане на писма от всички потребители
     *
     */
    function on_BeforePrepareKeyOptions($mvc, &$options, $type)
    {  
        if($folderId = $type->params['folderId']) {
            
            $options = $mvc->getFromEmailOptions($folderId);
        }
    }

    

    /**
     * Връща списък с [id на кутия] => имейл от които текущия потребител може да изпраща писма от папката
     * Първия имейл е най-предпочитания
     */
    static function getFromEmailOptions($folderId)
    {
        $options = array();

         // 1. Ако папката в която се намира документа е кутия към сметка, която може да изпраща писма - имейла на кутията
         $rec = self::fetch("#folderId = {$folderId} && #state = 'active'");
         if($rec && email_Accounts::canSendEmail($rec->accountId)) {
             $options[$rec->id] = $rec->email;
         }

         
          
         // Намираме сметка за входящи писма от корпоративен тип, с домейла на имейла
         $corpAccRec = email_Accounts::getCorporateAcc();
         
         if(email_Accounts::canSendEmail($corpAccRec->id)) {
             
             // 2. Корпоративния общ имейл, ако корпоративната сметка може да изпраща писма
             $rec = self::fetch("#email = '{$corpAccRec->email}' && #state = 'active'");
                            
             if($rec) {
                 $options[$rec->id] = $rec->email;
             }

             $userEmail = email_Inboxes::getUserEmail();

             if($userEmail && ($rec = self::fetch("#email = '{$userEmail}' && #state = 'active'"))) {
                 $options[$rec->id] = $rec->email;
             }
         }

         // 4. Всички шернати инбокс-имейли, които са към сметки, които могат да изпращат писма
         $cu = core_Users::getCurrent();
         $query = self::getQuery();
         $query->where("#inCharge = {$cu} OR #shared LIKE '%|{$cu}|%'");
         $query->where("#state = 'active'");
 
         while($rec = $query->fetch()) {
             if(email_Accounts::canSendEmail($rec->accountId)) {
                 if(!$options[$rec->id]) {
                     $options[$rec->id] = $rec->email;
                 }
             }
         }
 
         // 5. TODO

         if(!count($options)) {
             error('Липсват възможности за изпращане на писма. Настройте поне една сметка в Документи->Имейли->Сметки');
         }

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
            $powerRoles = core_Roles::keylistFromVerbal('executive,officer,manager,ceo');
            $userQuery->likeKeylist('roles', $roles);
            while($uRec = $userQuery->fetch()) {
                $powerUsers[$userRec->nick] = $uRec;
            }
        }

        return $powerUsers;
    }
}
