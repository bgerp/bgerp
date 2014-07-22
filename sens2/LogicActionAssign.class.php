<?php

/**
 * Логическо действие за присвояване на стойност
 *
 *
 * @category  bgerp
 * @package   sens2
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sens2_LogicActionAssign
{

    /**
     * Поддържани интерфейси
     */
    var $interfaces ="sens2_LogicActionIntf";


    /**
     * Наименование на действието
     */
    var $title = 'Присвояване';


    /**
     * Подготвя форма с настройки на контролера, като добавя полета с $form->FLD(....)
     *
     * @param core_Form форма на която трябва да се поставят полетата с конфигурацията на контролера (IP, port, pass, ...)
     */
    function prepareActionForm(&$form)
    {
        $form->FLD('var', 'varchar', 'caption=Променлива,mandatory');
        $form->FLD('expr', 'text(rows=2)', 'caption=Израз,width=100%,mandatory');
        $form->FLD('cond', 'text(rows=2)', 'caption=Условие,width=100%');  
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
    }

    function toVerbal($rec)
    {
        $res = "<font color='#999999'>Променлива:</font> <b>{$rec->var}</b><br>";
        $res .= "<font color='#999999'>Израз:</font> <b>{$rec->expr}</b><br>";
        if($rec->cond) {
            $res .= "<font color='#999999'>Израз:</font> <b>{$rec->cond}</b><br>";
        }

        return $res;
    }

    /**
     * Извършва действието, с параметрите, които са в $rec
     */
    function process($rec)
    {
    }
}