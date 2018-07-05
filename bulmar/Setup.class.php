<?php

/**
 * Папка в Bulmar Office
 */
defIfNot('BULMAR_INV_CONTR_FOLDER', '1');


/**
 * Номер на счетоводната операция на продажбата на стока в Bulmar Office
 */
defIfNot('BULMAR_INV_TAX_OPERATION_SALE_PRODUCTS', '2');


/**
 * Номер на счетоводната операция на продажбата на услуга в Bulmar Office
 */
defIfNot('BULMAR_INV_TAX_OPERATION_SALE_SERVICES', '3');

/**
 * Номер на счетоводната операция на плащането в Bulmar Office
 */
defIfNot('BULMAR_INV_TAX_OPERATION_PAYMENT', '501');


/**
 * Дебитна на продажбата в Bulmar Office
 */
defIfNot('BULMAR_INV_DEBIT_SALE', 'D411');


/**
 * кредитна сметка на продажбата на стока в Bulmar Office
 */
defIfNot('BULMAR_INV_FIRST_CREDIT_SALE_PRODUCTS', 'K702');


/**
 * кредитна сметка на продажбата на услуга в Bulmar Office
 */
defIfNot('BULMAR_INV_FIRST_CREDIT_SALE_SERVICES', 'K703');


/**
 * Кредитна сметка за ДДС в Bulmar Office
 */
defIfNot('BULMAR_INV_SECOND_CREDIT_SALE', 'K4532');


/**
 * Дебитна сметка на плащането в Bulmar Office
 */
defIfNot('BULMAR_INV_DEBIT_PAYMENT', 'D501');


/**
 * Кредитна сметка на плащането в Bulmar Office
 */
defIfNot('BULMAR_INV_CREDIT_PAYMENT', 'K411');


/**
 * Дебитна сметка на плащането в Bulmar Office
 */
defIfNot('BULMAR_INV_AV_OPERATION', '88');


/**
 * Кредитна сметка на плащането в Bulmar Office
 */
defIfNot('BULMAR_INV_CREDIT_AV', 'K412');


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
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Драйвъри за експорт и импорт към "Bulmar Office"';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
            'BULMAR_INV_CONTR_FOLDER' => array('int', 'caption=Експорт на изходящи фактури->Папка'),
            'BULMAR_INV_TAX_OPERATION_SALE_PRODUCTS' => array('varchar(10)', 'caption=Експорт на изходящи фактури->Сч. Операция на продажба на стока №'),
            'BULMAR_INV_TAX_OPERATION_SALE_SERVICES' => array('varchar(10)', 'caption=Експорт на изходящи фактури->Сч. Операция на продажба на услуга №'),
            'BULMAR_INV_TAX_OPERATION_PAYMENT' => array('varchar(10)', 'caption=Експорт на изходящи фактури->Сч. Операция на плащане №'),
            'BULMAR_INV_DEBIT_SALE' => array('varchar(10)', 'caption=Експорт на изходящи фактури->Дебитна сметка на клиента №'),
            'BULMAR_INV_FIRST_CREDIT_SALE_PRODUCTS' => array('varchar(10)', 'caption=Експорт на изходящи фактури->Дебитна сметка продажба на стока'),
            'BULMAR_INV_FIRST_CREDIT_SALE_SERVICES' => array('varchar(10)', 'caption=Експорт на изходящи фактури->Дебитна сметка продажба на услуга'),
            'BULMAR_INV_SECOND_CREDIT_SALE' => array('varchar(10)', 'caption=Експорт на изходящи фактури->Кредитна сметка ДДС'),
            'BULMAR_INV_DEBIT_PAYMENT' => array('varchar(10)', 'caption=Експорт на изходящи фактури->Дебитна сметка плащане'),
            'BULMAR_INV_CREDIT_PAYMENT' => array('varchar(10)', 'caption=Експорт на изходящи фактури->Кредитна сметка плащане'),
            'BULMAR_INV_AV_OPERATION' => array('varchar(10)', 'caption=Експорт на изходящи фактури->Сч. Операция на авансово плащане'),
            'BULMAR_INV_CREDIT_AV' => array('varchar(10)', 'caption=Експорт на изходящи фактури->Кредитна сметка за авансово плащане'),
            );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Добавяме импортиращия драйвър в core_Classes
        $html .= core_Classes::add('bulmar_InvoiceExport');
        
        return $html;
    }
}
