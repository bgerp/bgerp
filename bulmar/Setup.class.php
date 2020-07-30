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
 * Папка за експорт на приходните и разходните банкови документи
 */
defIfNot('BULMAR_BANK_DOCUMENT_FOLDER', '4.03');


/**
 * Номер на документа за банково плащане
 */
defIfNot('BULMAR_BANK_DOCUMENT_NUMBER', '103');


/**
 * Дебитна сметка за плащане към доставкик
 */
defIfNot('BULMAR_BANK_DOCUMENT_DEBIT_SUPPLIER', 'D401');


/**
 * Кредитна сметка за връщане от доставчик
 */
defIfNot('BULMAR_BANK_DOCUMENT_CREDIT_SUPPLIER', 'K401');


/**
 * Кредитна сметка за банково плащане
 */
defIfNot('BULMAR_BANK_DOCUMENT_CREDIT_BANK', 'K503');


/**
 * Дебитна сметка за банково плащане
 */
defIfNot('BULMAR_BANK_DOCUMENT_DEBIT_BANK', 'D503');


/**
 * Кредитна сметка за банково плащане от клиент
 */
defIfNot('BULMAR_BANK_DOCUMENT_CREDIT_CLIENT', 'K411');


/**
 * Кредитна сметка за връщане на клиент
 */
defIfNot('BULMAR_BANK_DOCUMENT_DEBIT_CLIENT', 'D411');


/**
 * Кредитна сметка за банково плащане към неуточнен
 */
defIfNot('BULMAR_BANK_DOCUMENT_CREDIT_UNKNOWN', 'K490');


/**
 * Дебитна сметка за банково плащане към неуточнен
 */
defIfNot('BULMAR_BANK_DOCUMENT_DEBIT_UNKNOWN', 'D490');


/**
 * Дебитна сметка за банково плащане към неуточнен
 */
defIfNot('BULMAR_BANK_DOCUMENT_OPERATION_TYPE', 4);


/**
 * Съответствие на аналитичностите за банкова сметка
 */
defIfNot('BULMAR_BANK_DOCUMENT_OWN_ACCOUNT_MAP', '');


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
    public $defClasses = 'bulmar_InvoiceExport, bulmar_PurchaseInvoiceExport, bulmar_BankDocumentExport';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'BULMAR_INV_CONTR_FOLDER' => array('double', 'caption=Експорт на ИЗХОДЯЩИ фактури->Папка'),
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
    
        'BULMAR_PURINV_CONTR_FOLDER' => array('double', 'caption=Експорт на ВХОДЯЩИ фактури->Папка'),
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
        
        'BULMAR_BANK_DOCUMENT_FOLDER' => array('double', 'caption=Експорт на Приходни и разходни банкови документи->Папка'),
        'BULMAR_BANK_DOCUMENT_OPERATION_TYPE' => array('int', 'caption=Експорт на Приходни и разходни банкови документи->Операция №'),
        
        'BULMAR_BANK_DOCUMENT_NUMBER' => array('varchar(10)', 'caption=Експорт на Приходни и разходни банкови документи->Документ №'),
        'BULMAR_BANK_DOCUMENT_DEBIT_SUPPLIER' => array('varchar(10)', 'caption=Експорт на Приходни и разходни банкови документи->Дебитна сметка доставчик'),
        'BULMAR_BANK_DOCUMENT_CREDIT_BANK' => array('varchar(10)', 'caption=Експорт на Приходни и разходни банкови документи->Кредитна сметка банка'),
        'BULMAR_BANK_DOCUMENT_DEBIT_BANK' => array('varchar(10)', 'caption=Експорт на Приходни и разходни банкови документи->Дебитна сметка банка'),
        'BULMAR_BANK_DOCUMENT_CREDIT_CLIENT' => array('varchar(10)', 'caption=Експорт на Приходни и разходни банкови документи->Кредитна сметка доставчик'),
        'BULMAR_BANK_DOCUMENT_DEBIT_UNKNOWN' => array('varchar(10)', 'caption=Експорт на Приходни и разходни банкови документи->Дебитна сметка неуточнено плащане'),
        'BULMAR_BANK_DOCUMENT_CREDIT_UNKNOWN' => array('varchar(10)', 'caption=Експорт на Приходни и разходни банкови документи->Кредитна сметка неуточнено плащане'),
     
        'BULMAR_BANK_DOCUMENT_DEBIT_CLIENT' => array('varchar(10)', 'caption=Експорт на Приходни и разходни банкови документи->Дебитна сметка за връщане на клиент'),
        'BULMAR_BANK_DOCUMENT_CREDIT_SUPPLIER' => array('varchar(10)', 'caption=Експорт на Приходни и разходни банкови документи->Кредитна сметка за връщане от доставчик'),
     
        'BULMAR_BANK_DOCUMENT_OWN_ACCOUNT_MAP' => array('table(columns=ownAccountId|itemId,captions=№ сметка|№ в Bulmar Office)', 'caption=Експорт на Приходни и разходни банкови документи->Аналитичности'),
    );
}
