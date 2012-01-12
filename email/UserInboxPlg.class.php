<?php


/**
 * Пощенска кутия по - подразбиране
 */
defIfNot('BGERP_DEFAULT_EMAIL_DOMAIN', 'bgerp.com');


/**
 * Клас 'email_UserInboxPlg' - Създава пощенска кутия след създаване на нов потребител
 *
 * @category   Experta Framework
 * @package    email
 * @author     Yusein Yuseinov
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @since      v 0.1
 */
class email_UserInboxPlg extends core_Plugin 
{
	
    /**
     * id на потребителя, който е inCharge в модела
     */
	var $inCharge = FALSE;
	
	
	/**
     *  Извиква се след вкарване на запис в таблицата на модела users
     */
    function on_AfterSave($mvc, &$id, $rec)
    {
        cls::load('email_Inboxes');
        
        //Ако се добавя потребител
        if($rec->nick) {
            $eRec = new stdClass();
            $eRec->inCharge = $rec->id;
            $eRec->access   = "private";
            $eRec->name     = $rec->nick;
            $eRec->domain   = BGERP_DEFAULT_EMAIL_DOMAIN;
            
            //Добавяме полето имейл, необходима за създаване на корица
            $eRec->email = $rec->nick . '@' . BGERP_DEFAULT_EMAIL_DOMAIN;
            
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
            
            //Проверяваме дали имамеме папка със същото име и дали някой е собственик
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
            
            //Ако ника е валиден идентификатор
            if (!type_Identifier::isValid($form->rec->nick)) {
                $form->setError('nick', "Полето '{$form->fields['nick']->caption}' може да съдържа само латински букви и цифри.");
            }
            
            //Ако редактиаме данните във формата
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
        
        //Името на папката
        $folderTitle = strtolower($rec->nick . '@' . BGERP_DEFAULT_EMAIL_DOMAIN);
        
        //Вземаме id' то на потребителя, който е inCharge
        $this->inCharge = doc_Folders::fetchField("#title = '{$folderTitle}'", 'inCharge');

        return ;
    }    
}