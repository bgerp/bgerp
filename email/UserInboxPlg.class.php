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
            //Данни необходими за създаване на папка
            $eRec = new stdClass();
            $eRec->inCharge = $rec->id;
            $eRec->access = "private";
            
            $eRec->domain = BGERP_DEFAULT_EMAIL_DOMAIN;
            $eRec->type = 'internal';
            $eRec->applyRouting = 'no';
            
            $nick = $rec->nick;
            
            if (EF_USSERS_EMAIL_AS_NICK) {
                $nick = type_Nick::parseEmailToNick($rec->nick);
            }
            
            if($corpAccRec = email_Accounts::getCorporateAcc()) {
            
                //Добавяме полето имейл, необходима за създаване на корица
                $eRec->email = email_Inboxes::getUserEmail($rec->id);
                $eRec->name = $nick;
                $eRec->accountId = $corpAccRec->id;
            
                email_Inboxes::forceCoverAndFolder($eRec);
            }
        }
        
        if($rec->first && $rec->id) {
            core_Users::addRole($rec->id, 'ceo');
            core_Users::addRole($rec->id, BGERP_ROLE_HEADQUARTER);
        }
    }
    
    public static function on_AfterCreate($mvc, $rec)
    {
        crm_Profiles::createProfile($rec);
    }
    
    public static function on_AfterUpdate($mvc, $rec)
    {
        crm_Profiles::updatePerson($rec);
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