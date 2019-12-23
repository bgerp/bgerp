<?php


/**
 * Интерфейс за логически действия, които могат да бъдат добавяни в Логическите блокове
 *
 * Класовете, които поддържат този интерфейс представляват едно просто действие, което
 * може да се записва, редактира, показва и изпълнява в Логическите блокове на контролерите
 *
 *
 * @category  bgerp
 * @package   sens2
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sens2_script_ActionIntf
{

    public $oldClassName = 'sens2_ScriptActionIntf';
    
    
    /**
     * Подготвя форма с настройки на екшъна, като добавя полета с $form->FLD(....)
     *
     * @param core_Form форма на която трябва да се поставят полетата с конфигурацията на контролера (IP, port, pass, ...)
     */
    public function prepareActionForm($form)
    {
        return $this->class->prepareActionForm($form);
    }
    
    
    /**
     * Проверява след  субмитване формата с настройки на екшъна
     * Тук контролера може да зададе грешки и предупреждения, в случай на
     * некоректни конфигурационни данни използвайки $form->setError() и $form->setWarning()
     *
     * @param core_Form   форма с въведени данни от заявката (след $form->input)
     */
    public function checkActionForm($form)
    {
        return $this->class->checkActionForm($form);
    }
    
    
    /**
     * Показва вербално представяне на действието
     */
    public function toVerbal($rec)
    {
        return $this->class->toVerbal($rec);
    }
    
    
    /**
     * Извършва действието, с параметрите, които са в $rec
     */
    public function run($rec)
    {
        return $this->class->run($rec);
    }
}
