<?php

/**
 * Папка в Bulmar Office
 */
defIfNot('BULMAR_INV_CONTR_FOLDER', '4');


/**
 * Номер на счетоводната операция на продажбата в Bulmar Office
 */
defIfNot('BULMAR_INV_TAX_OPERATION_SALE', '702');


/**
 * Номер на счетоводната операция на плащането в Bulmar Office
 */
defIfNot('BULMAR_INV_TAX_OPERATION_PAYMENT', '501');


/**
 * Дебитна на продажбата в Bulmar Office
 */
defIfNot('BULMAR_INV_DEBIT_SALE', 'D4111');


/**
 * кредитна сметка на продажбата в Bulmar Office
 */
defIfNot('BULMAR_INV_FIRST_CREDIT_SALE', 'K7021');


/**
 * Кредитна сметка за ДДС в Bulmar Office
 */
defIfNot('BULMAR_INV_SECOND_CREDIT_SALE', 'K4532');


/**
 * Дебитна сметка на плащането в Bulmar Office
 */
defIfNot('BULMAR_INV_DEBIT_PAYMENT', 'D5011');


/**
 * Кредитна сметка на плащането в Bulmar Office
 */
defIfNot('BULMAR_INV_CREDIT_PAYMENT', 'K4111');


/**
 * class bulmar_Setup
 *
 * Инсталиране/Деинсталиране на
 * Драйвър за импортиране / експортиране към Bulmar Office
 *
 *
 * @category  bgerp
 * @package   bulmar
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bulmar_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версията на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Драйвъри за експорт и импорт към Bulmar Office";
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    		'BULMAR_INV_CONTR_FOLDER'          => array("int", "caption=Експорт на изходящи фактури->Папка"),
    		'BULMAR_INV_TAX_OPERATION_SALE'    => array("varchar(10)", "caption=Експорт на изходящи фактури->Сч. Операция на продажба №"),
    		'BULMAR_INV_TAX_OPERATION_PAYMENT' => array("varchar(10)", "caption=Експорт на изходящи фактури->Сч. Операция на плащане №"),
    		'BULMAR_INV_DEBIT_SALE'            => array("varchar(10)", "caption=Експорт на изходящи фактури->Дебитна сметка продажба"),
    		'BULMAR_INV_FIRST_CREDIT_SALE'     => array("varchar(10)", "caption=Експорт на изходящи фактури->Кредитна сметка продажба"),
    		'BULMAR_INV_SECOND_CREDIT_SALE'    => array("varchar(10)", "caption=Експорт на изходящи фактури->Кредитна сметка ДДС"),
    		'BULMAR_INV_DEBIT_PAYMENT'         => array("varchar(10)", "caption=Експорт на изходящи фактури->Дебитна сметка плащане"),
    		'BULMAR_INV_CREDIT_PAYMENT'        => array("varchar(10)", "caption=Експорт на изходящи фактури->Кредитна сметка плащане"),
    		);
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    { 
        $html = parent::install();
        
        // Добавяме Импортиращия драйвър в core_Classes
        $html .= core_Classes::add('bulmar_InvoiceExport');
        
        return $html;
    }
}