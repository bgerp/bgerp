<?php

/**
 * Интерфейс за създаване на отчети от различни източници в системата
 *
 *
 * @category  bgerp
 * @package   frame
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс на драйвер на I/O контролер
 */
class frame_ReportSourceIntf
{

    /**
     *  Извлича и подготвя данните за даден отчет
     *
     * @param  stdClass $filter Данни необходими за генерирането на отчета - данните от формата/филтъра
     *
     * @return  stdClass Извлечените данни
     */
    function prepareReportData($filter)
    {
        return $this->class->prepareReportData($filter);
    }

    
    /**
     * Рендира данните за даден отчет 
     *
     * @param  stdClass $filter Филтъра за данни, въведен от потребителя
     * @param  stdClass $data   Данни извлечени с предходния метод
     *                   
     * @return et Попълнен шаблон с изгледа на отчета. При рендирането се отчитат параметрите в Mode
     */
    function renderReportData($filter, $data)
    {
        return $this->class->renderReportData($filter, $data);
    }


    /**
     * Подготвя форма с изходящи параметри на отчета, като добавя полета с $form->FLD(....)
     *
     * @param core_Form форма на която трябва да се поставят полетата с изходящите параметри
     */
    function prepareReportForm($form)
    {
        return $this->class->prepareReportForm($form);
    }
   
    
    /**
     * Проверява след  субмитване формата с параметри на отчета
     * Тук може да сигнализира за грешки и предупреждения, в случай на 
     * некоректни конфигурационни данни използвайки $form->setError() и $form->setWarning()
     *
     * @param   core_Form   форма с въведени данни от заявката (след $form->input)
     */
    function checkReportForm($form)
    {
        return $this->class->checkReportForm($form);
    }
    
    
    /**
     * Дали потребителя има права да избере източника
     */
    function canSelectSource($userId = NULL)
    {
    	return $this->class->canSelectDriver($userId);
    }
}