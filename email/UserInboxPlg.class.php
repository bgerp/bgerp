<?php


/**
 * Роля за основен екип
 */
defIfNot('BGERP_ROLE_HEADQUARTER', 'Headquarter');


/**
 * Клас 'email_UserInboxPlg' - Създава пощенска кутия след създаване на нов потребител
 *
 *
 * @category  bgerp
 * @package   email
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class email_UserInboxPlg extends core_Plugin
{
    
    
    /**
     * Преди записване на данните
     */
    public function on_BeforeSave($mvc, $id, &$rec)
    {
        //Ако добавяме нов потребител
        if (!$rec->id) {
            if (!core_Users::fetch('1=1')) {
                $rec->First = true;
            }
            
            //Проверяваме дали имаме папка със същото име и дали някой е собственик
            expect(core_Users::isContractor($rec) || !$this->checkFolderCharge($rec), 'Моля въведете друг Ник. Папката е заета от друг потребител.');
        }
        
        // При добавяне или при редакция на ник да се създава корпоративен имейл, ако има такъв акаунт
        if (isset($rec->nick) && ($rec->state == 'active')) {
            if ($corpAccRec = email_Accounts::getCorporateAcc()) {
                if (!$rec->id) {
                    if ($rec->state == 'active') {
                        $rec->CorporateAccId = $corpAccRec->id;
                    }
                } else {
                    if ($oRec = $mvc->fetch($rec->id)) {
                        if (($oRec->nick != $rec->nick) || ($oRec->state != 'active')) {
                            $rec->CorporateAccId = $corpAccRec->id;
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     * Извиква се след вкарване на запис в таблицата на модела users
     */
    public static function on_AfterSave($mvc, &$id, $rec, $saveFileds = null)
    {
        // Ако трябва да се създава корпоративна сметка
        if (isset($rec->CorporateAccId)) {
            // Ако има съответната роля за създаване на корпоративна сметка
            if (haveRole(email_Setup::get('ROLE_FOR_CORPORATE_EMAIL'), $rec->id)) {
                //Данни необходими за създаване на папка
                $eRec = new stdClass();
                
                //Добавяме полето имейл, необходима за създаване на корица
                $eRec->email = email_Inboxes::getUserEmail($rec->id);
                $eRec->accountId = $rec->CorporateAccId;
                $eRec->access = 'private';
                $eRec->inCharge = $rec->id; // Отговорник на новата папка е новосъздадения потребител.
                
                if ($eRec->email) {
                    email_Inboxes::forceCoverAndFolder($eRec);
                }
            }
        }
        
        // Това се прави в doc_Setup -> 107 - 117
        if ($rec->First && $rec->id) {
            // На първия потребител даваме и ceo роля. Необходимо ли е?
            core_Users::addRole($rec->id, 'ceo');
            
            // Първия потребител го присъединяваме към основния екип
            core_Users::addRole($rec->id, BGERP_ROLE_HEADQUARTER);
        }
    }
    
    
    /**
     * Изпълнява се след създаване на потребител
     */
    public static function on_AfterCreate($mvc, $user)
    {
        if (!empty($user->personId) && crm_Profiles::fetch("#personId = {$user->personId}")) {
            // Не можем да асоциираме новия потребител с човек, който вече има профил
            $user->personId = null;
        }
        
        expect($user->names, $user);
        
        // Създава или обновява профилната визитка на новия потребител.
        $personId = crm_Profiles::syncPerson($user->personId, $user);
        
        // Ако няма резултат
        if (!$personId) {
            
            // Опитваме се да вземем от request
            $personId = Request::get('personId', 'int');
        }
        
        if ($personId) {
            crm_Profiles::save(
               (object) array(
                   'personId' => $personId,
                   'userId' => $user->id
               )
           );
            
            // Обратно синхронизиране
            crm_Profiles::syncUser(crm_Persons::fetch($personId));
        }
    }
    
    
    /**
     * Изпълнява се след обновяване на информацията за потребител
     */
    public static function on_AfterUpdate($mvc, $rec, $fields = null)
    {
        $fieldsArr = $mvc->prepareSaveFields($fields, $rec);
        
        if ($fieldsArr['nick'] && $rec->nick) {
            if (($personId = crm_Profiles::fetchField("#userId = {$rec->id}", 'personId'))) {
                crm_Profiles::syncPerson($personId, $rec);
            } else {
                self::on_AfterCreate($mvc, $rec);
            }
        }
    }
    
    
    /**
     * След вкарване на записите в едит формата
     */
    public function on_AfterInputEditForm($mvc, &$form)
    {
        //Ако формата е субмитната
        if ($form->isSubmitted()) {
            
            //Ако редактираме данните във формата
            $inCharge = $this->checkFolderCharge($form->rec);
            
            //Ако имаме inCharge
            if ($inCharge) {
                //Ако потребителя не е собственик на новата папка показваме грешка
                if (core_Users::isPowerUser($form->rec) && ($form->rec->id != $inCharge)) {
                    $form->setError('nick', "Моля въведете друг|* '{$form->fields['nick']->caption}'. |Папката е заета от друг потребител.");
                }
            }
        }
    }
    
    
    /**
     * Попълва данните на формата със подадената визитка
     */
    public static function on_AfterPrepareEditForm(core_Users $mvc, $data)
    {
        $data->form->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Лице->Държава,mandatory,after=email');
        
        if ($data->form->rec->id) {
            $profRec = crm_Profiles::fetch("#userId = {$data->form->rec->id}");
            
            if ($profRec) {
                $pRec = crm_Persons::fetch($profRec->personId);
                if ($pRec) {
                    $data->form->setDefault('country', $pRec->country);
                    $data->form->setreadonly('country');
                }
            }
        }
        
        $data->form->setDefault('country', crm_Companies::fetchOwnCompany()->countryId);
        
        
        if (empty($data->form->rec->id)) {
            $personId = Request::get('personId', 'int');
            if (!empty($personId) && $personRec = crm_Persons::fetch($personId)) {
                $emails = type_Emails::toArray($personRec->email . ' ' . $personRec->buzEmail, type_Emails::VALID);
                $email = $nick = '';
                if (!empty($emails[0])) {
                    $email = $emails[0];
                    $data->form->setDefault('email', $email);
                }
                
                $emails = type_Emails::toArray($personRec->buzEmail . ' ' . $personRec->email, type_Emails::VALID);
                if (!empty($emails[0])) {
                    $tN = cls::get('type_Nick');
                    list($nick, ) = explode('@', $emails[0]);
                    
                    if ($nick) {
                        $nick = $tN->normalize($nick);
                        if ($tN->isValid($nick) && !core_Users::fetch(array("LOWER(#nick) = '[#1#]'", $nick))) {
                            $data->form->setDefault('nick', $nick);
                        }
                    }
                }
                
                $data->form->setDefault('names', $personRec->name);
                
                Request::push(array('names' => $personRec->name, 'email' => $personRec->email));
                $data->form->setField('names', 'input=hidden');
                $data->personRec = $personRec;
                
                $data->form->FNC('personId', 'key(mvc=crm_Persons,select=name)', 'input=hidden,silent,caption=Визитка,forceField');
                $data->form->setDefault('personId', $personId);
            }
        }
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    public static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        if (isset($data->personRec)) {
            $name = crm_Persons::getVerbal($data->personRec, 'name');
            $data->form->title = 'Създаване на потребител за|* ' . $name;
        }
    }
    
    
    /**
     * Проверяваме дали имаме папка със същото име
     */
    public function checkFolderCharge($rec)
    {
        $nick = $rec->nick;
        
        if (EF_USSERS_EMAIL_AS_NICK) {
            $nick = type_Nick::parseEmailToNick($rec->nick);
        }
        
        $userId = core_Users::fetchField("#nick = '{$nick}'", 'id');
        
        if (!$userId) {
            
            return false;
        }
        
        //Името на папката
        $folderTitle = email_Inboxes::getUserEmail($userId);
        
        
        //Вземаме id' то на потребителя, който е inCharge
        return $inCharge = doc_Folders::fetchField(array("#title = '[#1#]'", $folderTitle), 'inCharge');
    }
    
    
    /**
     * Определяне на правата за действия над потребителите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$roles, $action, $uRec, $user = null)
    {
        if ($action == 'delete') {
            if (is_object($uRec) && (($uRec->state != 'draft') || $uRec->lastLoginTime || doc_Folders::fetch("#inCharge = {$uRec->id}"))) {
                $roles = 'no_one';
            }
        }
    }
}
