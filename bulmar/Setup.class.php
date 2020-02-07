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
 * Папка за входящи фактури в Bulmar Office
 */
defIfNot('BULMAR_PURINV_CONTR_FOLDER', '3');


/**
 * Кредитна сметка на покупката на Bulmar Office
 */
defIfNot('BULMAR_PURINV_CREDIT_PURCHASE', 'K401');


/**
 * Дебитна сметка за засклаждане на стоки 
 */
defIfNot('BULMAR_PURINV_DEBIT_PURCHASE_PRODUCTS', 'D304');


/**
 * Дебитна сметка за засклаждане на услуги
 */
defIfNot('BULMAR_PURINV_DEBIT_PURCHASE_SERVICES', 'D304');


/**
 * Дебитна сметка за ДДС от покупки
 */
defIfNot('BULMAR_PURINV_DEBIT_PURCHASE_VAT', 'D4531');


/**
 * Кредитна сметка за ДДС от покупки
 */
defIfNot('BULMAR_PURINV_CREDIT_CASE', 'K501');


/**
 * Дебитна сметка за плащане
 */
defIfNot('BULMAR_PURINV_DEBIT_PAYMENT', 'D401');


/**
 * Номер на счетоводната операция на плащането в Bulmar Office
 */
defIfNot('BULMAR_PURINV_PAYMENT_OPERATION', '4');


/**
 * Номер на счетоводната операция на плащането в Bulmar Office
 */
defIfNot('BULMAR_PURINV_DEBIT_DOWNPAYMENT', 'D402');


/**
 * Номер на счетоводната операция за авансово плащане в Bulmar Office
 */
defIfNot('BULMAR_PURINV_DOWNPAYMENT_OPERATION', '88');


/**
 * Номер на счетоводната операция на продажбата на стока в Bulmar Office
 */
defIfNot('BULMAR_PURINV_PURCHASE_PRODUCTS_OPER', '1');


/**
 * Номер на счетоводната операция на продажбата на услуги в Bulmar Office
 */
defIfNot('BULMAR_PURINV_PURCHASE_SERVICES_OPER', '3');


/**
 * Номер на счетоводната операция на продажбата на услуга в Bulmar Office
 */
defIfNot('BULMAR_INV_TAX_OPERATION_SALE_SERVICES', '3');




/**
 * class bulmar_Setup
 *
 * Инсталиране/Деинсталиране на
 * Драйвър за импортиране / експортиране към Bulmar Office
 *
 *
 * @category  bgerp
 * @package   bulmar
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
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
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'bulmar_InvoiceExport, bulmar_PurchaseInvoiceExport';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'BULMAR_INV_CONTR_FOLDER' => array('int', 'caption=Експорт на ИЗХОДЯЩИ фактури->Папка'),
        'BULMAR_INV_TAX_OPERATION_SALE_PRODUCTS' => array('varchar(10)', 'caption=Експорт на ИЗХОДЯЩИ фактури->Сч. Операция на продажба на стока №'),
        'BULMAR_INV_TAX_OPERATION_SALE_SERVICES' => array('varchar(10)', 'caption=Експорт на ИЗХОДЯЩИ фактури->Сч. Операция на продажба на услуга №'),
        'BULMAR_INV_TAX_OPERATION_PAYMENT' => array('varchar(10)', 'caption=Експорт на ИЗХОДЯЩИ фактури->Сч. Операция на плащане №'),
        'BULMAR_INV_AV_OPERATION' => array('varchar(10)', 'caption=Експорт на ИЗХОДЯЩИ фактури->Сч. Операция на авансово плащане'),
        'BULMAR_INV_DEBIT_SALE' => array('varchar(10)', 'caption=Експорт на ИЗХОДЯЩИ фактури->Дебитна сметка на клиента №'),
        'BULMAR_INV_FIRST_CREDIT_SALE_PRODUCTS' => array('varchar(10)', 'caption=Експорт на ИЗХОДЯЩИ фактури->Дебитна сметка продажба на стока'),
        'BULMAR_INV_FIRST_CREDIT_SALE_SERVICES' => array('varchar(10)', 'caption=Експорт на ИЗХОДЯЩИ фактури->Дебитна сметка продажба на услуга'),
        'BULMAR_INV_SECOND_CREDIT_SALE' => array('varchar(10)', 'caption=Експорт на ИЗХОДЯЩИ фактури->Кредитна сметка ДДС'),
        'BULMAR_INV_DEBIT_PAYMENT' => array('varchar(10)', 'caption=Експорт на ИЗХОДЯЩИ фактури->Дебитна сметка плащане'),
        'BULMAR_INV_CREDIT_PAYMENT' => array('varchar(10)', 'caption=Експорт на ИЗХОДЯЩИ фактури->Кредитна сметка плащане'),
        'BULMAR_INV_CREDIT_AV' => array('varchar(10)', 'caption=Експорт на ИЗХОДЯЩИ фактури->Кредитна сметка за авансово плащане'),
    
        'BULMAR_PURINV_CONTR_FOLDER' => array('int', 'caption=Експорт на ВХОДЯЩИ фактури->Папка'),
        'BULMAR_PURINV_PURCHASE_PRODUCTS_OPER' => array('varchar(10)', 'caption=Експорт на ВХОДЯЩИ фактури->Сч. Операция на покупка на стока №'),
        'BULMAR_PURINV_PURCHASE_SERVICES_OPER' => array('varchar(10)', 'caption=Експорт на ВХОДЯЩИ фактури->Сч. Операция на покупка на услуги №'),
        'BULMAR_PURINV_PAYMENT_OPERATION' => array('varchar(10)', 'caption=Експорт на ВХОДЯЩИ фактури->Сч. Операция на плащане №'),
        'BULMAR_PURINV_DOWNPAYMENT_OPERATION' => array('varchar(10)', 'caption=Експорт на ВХОДЯЩИ фактури->Сч. Операция на авансово плащане'),
        'BULMAR_PURINV_CREDIT_PURCHASE' => array('varchar(10)', 'caption=Експорт на ВХОДЯЩИ фактури->Кредитна сметка на доставчика №'),
        'BULMAR_PURINV_DEBIT_PURCHASE_PRODUCTS' => array('varchar(10)', 'caption=Експорт на ВХОДЯЩИ фактури->Дебитна сметка за покупка на стоки'),
        'BULMAR_PURINV_DEBIT_PURCHASE_SERVICES' => array('varchar(10)', 'caption=Експорт на ВХОДЯЩИ фактури->Дебитна сметка за покупка на услуги'),
        'BULMAR_PURINV_DEBIT_PURCHASE_VAT' => array('varchar(10)', 'caption=Експорт на ВХОДЯЩИ фактури->Дебитна сметка за ДДС от покупки'),
        'BULMAR_PURINV_CREDIT_CASE' => array('varchar(10)', 'caption=Експорт на ВХОДЯЩИ фактури->Кредитна сметка плащане'),
        'BULMAR_PURINV_DEBIT_PAYMENT' => array('varchar(10)', 'caption=Експорт на ВХОДЯЩИ фактури->Дебитна сметка плащане'),
        'BULMAR_PURINV_DEBIT_DOWNPAYMENT' => array('varchar(10)', 'caption=Експорт на ВХОДЯЩИ фактури->Дебитна сметка за авансово плащане'),
    );
}
