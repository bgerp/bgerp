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
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_UserInboxPlg extends core_Plugin
{
    /**
     * Извиква се след вкарване на запис в таблицата на модела users
     */
    static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
        //Ако се добавя или редактира потребител
        //При вход в системата не се задейства
        if($rec->nick) {
 
            if($corpAccRec = email_Accounts::getCorporateAcc()) {
                
                //Данни необходими за създаване на папка
                $eRec = new stdClass();
            
                //Добавяме полето имейл, необходима за създаване на корица
                $eRec->email = email_Inboxes::getUserEmail($rec->id);
                $eRec->accountId = $corpAccRec->id;
                
                if($eRec->email) {
                    email_Inboxes::forceCoverAndFolder($eRec);
                }
            }
        }
        
        if($rec->first && $rec->id) {
            core_Users::addRole($rec->id, 'ceo');
            core_Users::addRole($rec->id, BGERP_ROLE_HEADQUARTER);
        }
    }
    
    public static function on_AfterCreate($mvc, $user)
    {
        if (!empty($user->personId) && crm_Profiles::fetch("#personId = {$user->personId}")) {
            // Не можем да асоциираме новия потребител с човек, който вече има профил
            $user->personId = NULL;
        }
        
        // Създава или обновява профилната визитка на новия потребител.
        $personId = crm_Profiles::syncPerson($user->personId, $user);
        
        if ($personId) {
           crm_Profiles::save(
               (object)array(
                   'personId' => $personId,
                   'userId'   => $user->id
               )
           ); 
        }
    }
    
    public static function on_AfterUpdate($mvc, $rec)
    {
        if ($profile = crm_Profiles::fetch("#userId = {$rec->id}")) {
            crm_Profiles::syncPerson($profile->personId, $rec);
        }
    }
    
    
    /**
     * Преди записване на данните
     */
    function on_BeforeSave($mvc, $id, &$rec)
    {
        //Ако добавяме нов потребител
        if (!$rec->id) {
            
            if(!core_Users::fetch('1=1')) {
                $rec->first = TRUE;
            }
            
            //Проверяваме дали имаме папка със същото име и дали някой е собственик
            expect (!$this->checkFolderCharge($rec), "Моля въведете друг Ник. Папката е заета от друг потребител.");
        }
    } 
    
    
    /**
     * След вкарване на записите в едит форматa
     */
    function on_AfterInputEditForm($mvc, &$form)
    {
        //Ако формата е субмитната
        if ($form->isSubmitted()) {

            if(core_Users::fetch('1=1')) {

                //Вземаме броя на срещанията на всички типове роли
                $rolesByTypeArr = core_Roles::getRolesTypeArr($form->rec->roles);
                
                if($rolesByTypeArr['rang'] != 1) {
                    $form->setError('roles', "Потребителя трябва да има точно една роля за ранг! Избрани са <b>" . (int)$rolesByTypeArr['rang']. "</b>.");
                }
                
                if($rolesByTypeArr['team'] < 1) {
                    $form->setError('roles1', "Потребителя трябва да има поне една роля за екип!");
                }
            }
            
            //Ако редактираме данните във формата
            $inCharge = $this->checkFolderCharge($form->rec);
            
            //Ако имаме inCharge
            if ($inCharge) {
                //Ако потребителя не е собственик на новата папка показваме грешка
                if ($form->rec->id != $inCharge) {
                    $form->setError('nick', "Моля въведете друг '{$form->fields['nick']->caption}'. Папката е заета от друг потребител.");
                }
            }
        }
    }
    
    
    public static function on_AfterPrepareEditForm(core_Users $mvc, $data)
    {
        if (empty($data->form->rec->id)) {
            $personId  = Request::get('personId');
            if (!empty($personId) && $personRec = crm_Persons::fetch($personId)) {
                
                $emails = type_Emails::toArray($personRec->email, type_Emails::VALID);
                $email  = $nick = '';
                if (!empty($emails[0])) {
                    $email = $emails[0];
                    $nick  = substr($email, strpos($email, '@'));
                }
                
                $data->form->setDefault('names', $personRec->name);
                $data->form->setDefault('email', $email);
                $data->form->setDefault('email', $email);
                $data->form->setField('names', 'input=hidden');
                
                $data->form->title = 'Създаване на потребител за ' . $personRec->name;
                
                $data->form->FNC('personId', 'key(mvc=crm_Persons,select=name)', 'input=hidden,silent,caption=Визитка');
                $data->form->setDefault('personId', $personId);
            }
            
        }
    }
    
    
    /**
     * Проверяваме дали имаме папка със същото име
     */
    function checkFolderCharge($rec)
    {
        $nick = $rec->nick;
        
        if (EF_USSERS_EMAIL_AS_NICK) {
            $nick = type_Nick::parseEmailToNick($rec->nick);
        }

        $userId = core_Users::fetchField("#nick = '{$nick}'", 'id');

        if(!$userId) return FALSE;
        
        //Името на папката
        $folderTitle = email_Inboxes::getUserEmail($userId);

        
        //Вземаме id' то на потребителя, който е inCharge
        return $inCharge = doc_Folders::fetchField("#title = '{$folderTitle}'", 'inCharge');
    }
}