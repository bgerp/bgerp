<?php


/**
 * Действие на скрипт за нотифициране
 *
 *
 * @category  bgerp
 * @package   sens2
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sens2_script_ActionNotify
{
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'sens2_script_ActionIntf';
    
    public $oldClassName = 'sens2_ScriptActionNotify';

    /**
     * Наименование на действието
     */
    public $title = 'Известяване на потребители';
    
    
    /**
     * Подготвя форма с настройки на контролера, като добавя полета с $form->FLD(....)
     *
     * @param core_Form форма на която трябва да се поставят полетата с конфигурацията на контролера (IP, port, pass, ...)
     */
    public function prepareActionForm(&$form)
    {
        $form->FLD('message', 'varchar', 'caption=Известяване->Съобщение,mandatory');
        $form->FLD('priority', 'enum(normal=Нормален, warning=Неотложен, alert=Спешен)', 'caption=Известяване->Приоритет,mandatory');
        $form->FLD('users', 'userList', 'caption=Известяване->Потребители,mandatory');
        $form->FLD('cond', 'text(rows=2)', 'caption=Условие за да се изпрати->Израз,mandatory,width=100%');
        
        $suggestions = sens2_script_Helper::getSuggestions($form->rec->scriptId);
        $form->setSuggestions('cond', $suggestions);
        
        $form->setDefault('users', '|' . core_Users::getCurrent() . '|');
    }
    
    
    /**
     * Проверява след  субмитване формата с настройки на контролера
     * Тук контролера може да зададе грешки и предупреждения, в случай на
     * некоректни конфигурационни данни използвайки $form->setError() и $form->setWarning()
     *
     * @param core_Form   форма с въведени данни от заявката (след $form->input)
     */
    public function checkActionForm($form)
    {
    }
    
    
    public function toVerbal($rec)
    {
        $cond = sens2_Scripts::highliteExpr($rec->cond, $rec->scriptId);
        $UL = cls::get('type_UserList');
        $users = $UL->toVerbal($rec->users);
        
        $message = type_Varchar::escape($rec->message);
        
        $EN = core_Type::getByName('type_Enum(normal=Нормален, warning=Предупреждение, alert=Тревога)');
        $priority = $EN->toVerbal($rec->priority);
        
        $res = "Известие ({$priority}) <span style=\"color:green\">`{$message}`</span> към {$users}, ако {$cond}";
        
        return $res;
    }
    
    
    /**
     * Извършва действието, с параметрите, които са в $rec
     */
    public function run($rec)
    {
        // Ако има условие и то не е изпълнено - не правим нищо
        if (trim($rec->cond)) {
            $cond = sens2_Scripts::calcExpr($rec->cond, $rec->scriptId);
            if ($cond === sens2_Scripts::CALC_ERROR) {
                
                return 'stopped';
            }
            if (!$cond) {
                
                return 'closed';
            }
        }
        
        // Заменяме променливите от контекста
        $context = sens2_Scripts::getContext($rec->scriptId);
        $message = strtr($rec->message, $context);

        // Задаваме го на изхода
        $userList = keylist::toArray($rec->users);
        core_Users::sudo(core_Users::SYSTEM_USER);
        foreach ($userList as $userId) {
            $res = bgerp_Notifications::add($message, array('sens2_Scripts', 'Single', $rec->scriptId, 'order' => $rec->order), $userId, $rec->priority);  
        }
        core_Users::exitSudo(core_Users::SYSTEM_USER);
        
        if ($res !== false) {
            
            return 'active';
        }
        
        return 'stopped';
    }
}
