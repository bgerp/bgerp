<?php



/**
 * Пощенска кутия по - подразбиране
 */
defIfNot('BGERP_DEFAULT_EMAIL_DOMAIN');


/**
 * Клас 'email_UserInboxPlg' - Създава пощенска кутия след създаване на нов потребител
 *
 *
 * @category  all
 * @package   email
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_UserInboxPlg extends core_Plugin
{
    
    
    /**
     * id на потребителя, който е inCharge в модела
     */
    var $inCharge = FALSE;
    
    
    /**
     * Извиква се след вкарване на запис в таблицата на модела users
     */
    static function on_AfterSave($mvc, &$id, $rec)
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
            $eRec->byPassRoutingRules = 'no';
            
            $nick = $rec->nick;
            
            if (EF_USSERS_EMAIL_AS_NICK) {
                $nick = type_Nick::parseEmailToNick($rec->nick);
            }
            
            //Добавяме полето имейл, необходима за създаване на корица
            $eRec->email = email_Inboxes::getUserEmail($nick);
            $eRec->name = $nick;
            
            email_Inboxes::forceCoverAndFolder($eRec);
        }
    }
    
    
    /**
     * Преди записване на данните
     */
    function on_BeforeSave($mvc, $id, &$rec)
    {
        //Ако добавяме нов потребител
        if (!$rec->id) {
            $this->checkFolderCharge($rec);
            
            //Проверяваме дали имаме папка със същото име и дали някой е собственик
            if ($this->inCharge) {
                
                core_Message::redirect("Моля въведете друг Ник. Папката е заета от друг потребител.", 'tpl_Error', NULL, array('core_Users', 'add'));
            }
        }
    }
    
    
    /**
     * След вкарване на записите в едит форматa
     */
    function on_AfterInputEditForm($mvc, &$form)
    {
        //Ако формата е субмитната
        if ($form->isSubmitted()) {
            
            //Ако редактираме данните във формата
            if ($form->rec->id) {
                $this->checkFolderCharge($form->rec);
                
                //Ако имаме inCharge
                if ($this->inCharge) {
                    
                    //Ако потребителя не е собственик на новата папка показваме грешка
                    if ($form->rec->id != $this->inCharge) {
                        $form->setError('nick', "Моля въведете друг '{$form->fields['nick']->caption}'. Папката е заета от друг потребител.");
                    }
                }
            }
        }
    }
    
    
    /**
     * Проверяваме дали имаме папка със същото име
     */
    function checkFolderCharge($rec)
    {
        if ($this->inCharge !== FALSE) return;
        
        $nick = $rec->nick;
        
        if (EF_USSERS_EMAIL_AS_NICK) {
            $nick = type_Nick::parseEmailToNick($rec->nick);
        }
        
        //Името на папката
        $folderTitle = email_Inboxes::getUserEmail($nick);
        
        //Вземаме id' то на потребителя, който е inCharge
        $this->inCharge = doc_Folders::fetchField("#title = '{$folderTitle}'", 'inCharge');
        
        return ;
    }
}