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
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sens2_ScriptActionIntf
{
    
    public $oldClassName = 'sens2_LogicActionIntf';

    /**
     * Подготвя форма с настройки на контролера, като добавя полета с $form->FLD(....)
     *
     * @param core_Form форма на която трябва да се поставят полетата с конфигурацията на контролера (IP, port, pass, ...)
     */
    function prepareActionForm($form)
    {
        return $this->class->prepareConfigForm($form);
    }
   
    
    /**
     * Проверява след  субмитване формата с настройки на контролера
     * Тук контролера може да зададе грешки и предупреждения, в случай на 
     * некоректни конфигурационни данни използвайки $form->setError() и $form->setWarning()
     *
     * @param   core_Form   форма с въведени данни от заявката (след $form->input)
     */
    function checkActionForm($form)
    {
        return $this->class->checkConfigForm($form);
    }

    
    /**
     * Показва вербално представяне на действието
     */
    function toVerbal($rec)
    {
        return $this->class->process($rec);
    }


    /**
     * Извършва действието, с параметрите, които са в $rec
     */
    function run($data)
    {
        return $this->class->run($data);
    }
}