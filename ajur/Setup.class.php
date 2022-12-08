<?php


/**
 * Константа стойност която да се експортира за документ ФАКТУРА
 */
defIfNot('AJUR_DOC_INVOCIE_TYPE', '1');


/**
 * Константа стойност която да се експортира за документ ДИ
 */
defIfNot('AJUR_DOC_DEBIT_NOTE_TYPE', '3');


/**
 * Константа стойност която да се експортира за документ КИ
 */
defIfNot('AJUR_DOC_CREDIT_NOTE_TYPE', '4');

/**
 * Константа стойност която да се експортира за документ ВЪТРЕШНА ФАКТУРА
 */
defIfNot('AJUR_DOC_INTER_INVOICE_TYPE', '7');

/**
 * Константа стойност която да се експортира за документ ОТЧЕТ ЗА ПРОДАЖБИТЕ
 */
defIfNot('AJUR_DOC_SALES_REPORT_TYPE', '8');

/**
 * Константа стойност която да се експортира за начин на плащане БЕЗ ОПРЕДЕЛЕН НАЧИН
 */
defIfNot('AJUR_DOC_PAYMENT_INDEFINITE_TYPE', ' ');

/**
 * Константа стойност която да се експортира за начин на плащане В БРОЙ
 */
defIfNot('AJUR_DOC_PAYMENT_CASH_TYPE', '0');

/**
 * Константа стойност която да се експортира за начин на плащане ПО СМЕТКА
 */
defIfNot('AJUR_DOC_PAYMENT_ACCOUNT_TYPE', '1');

/**
 * Константа стойност която да се експортира за начин на плащане ДРУГО ФБ
 */
defIfNot('AJUR_DOC_PAYMENT_FB_TYPE', '2');

/**
 * Константа стойност която да се експортира за начин на плащане ДТ КТ КАРТА
 */
defIfNot('AJUR_DOC_PAYMENT_CARD_TYPE', '3');

/**
 * Константа стойност която да се експортира за начин на плащане ЧЕК ВАУЧЕР
 */
defIfNot('AJUR_DOC_PAYMENT_CHECK_VOUCHER_TYPE', '4');

/**
 * Константа стойност която да се експортира за начин на плащане ВНАСЯНЕ ПО СМЕТКА
 */
defIfNot('AJUR_DOC_PAYMENT_DEPOSITACC_TYPE', '8');

/**
 * Константа стойност която да се експортира за начин на плащане ДИРЕКТЕН ДЕБИТ
 */
defIfNot('AJUR_DOC_PAYMENT_DEBITDIRECT_TYPE', '9');

/**
 * Константа стойност която да се експортира за начин на плащане ПАРИЧЕН ПРЕВОД
 */
defIfNot('AJUR_DOC_PAYMENT_MONEY_TRANSFER_TYPE', 'A');

/**
 * Константа стойност която да се експортира за начин на плащане ПОЩЕНСКИ ПРЕВОД
 */
defIfNot('AJUR_DOC_PAYMENT_POST_TRANSFER_TYPE', 'B');

/**
 * Константа стойност която да се експортира за начин на плащане СЪГЛАСНО ЧЛ25(2) НН18
 */
defIfNot('AJUR_DOC_PAYMENT_АРТ25_TYPE', 'R');


/**
 * class ajur_Setup
 *
 * Инсталиране/Деинсталиране на
 * Драйвър за експортиране към Ажур
 *
 *
 * @category  bgerp
 * @package   ajur
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class ajur_Setup extends core_ProtoSetup
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
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        //Вид фактура
        'AJUR_DOC_INVOCIE_TYPE' => array('int', 'caption=Експорт документ->Фактура'),
        'AJUR_DOC_DEBIT_NOTE_TYPE' => array('int', 'caption=Експорт документ->Дебитно известие'),
        'AJUR_DOC_CREDIT_NOTE_TYPE' => array('int', 'caption=Експорт документ->Кредитно известие'),
        'AJUR_DOC_INTER_INVOICE_TYPE' => array('int', 'caption=Експорт документ->Вътрешна фактура'),
        'AJUR_DOC_SALES_REPORT_TYPE' => array('int', 'caption=Експорт документ->Отчет за продажбите'),

        //Начин на плащане
        'AJUR_DOC_PAYMENT_INDEFINITE_TYPE' => array('varchar', 'caption=Начин на плащане->Не е определен'),
        'AJUR_DOC_PAYMENT_CASH_TYPE' => array('varchar', 'caption=Начин на плащане->В брой'),
        'AJUR_DOC_PAYMENT_ACCOUNT_TYPE' => array('varchar', 'caption=Начин на плащане->По сметка'),
        'AJUR_DOC_PAYMENT_FB_TYPE' => array('varchar', 'caption=Начин на плащане->Друго ФБ'),
        'AJUR_DOC_PAYMENT_CARD_TYPE' => array('varchar', 'caption=Начин на плащане->Друго Карта'),
        'AJUR_DOC_PAYMENT_CHECK_VOUCHER_TYPE' => array('varchar', 'caption=Начин на плащане->Друго Чек/Ваучер'),
        'AJUR_DOC_PAYMENT_DEPOSITACC_TYPE' => array('varchar', 'caption=Начин на плащане->Внасяне по сметка'),
        'AJUR_DOC_PAYMENT_DEBITDIRECT_TYPE' => array('varchar', 'caption=Начин на плащане->Директен дебит'),
        'AJUR_DOC_PAYMENT_MONEY_TRANSFER_TYPE' => array('varchar', 'caption=Начин на плащане->Паричен превод'),
        'AJUR_DOC_PAYMENT_POST_TRANSFER_TYPE' => array('varchar', 'caption=Начин на плащане->Пощенски превод'),
        'AJUR_DOC_PAYMENT_АРТ25_TYPE' => array('varchar', 'caption=Начин на плащане->Съгласно чл.25(2) НН18'),

    );


    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';


    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'ajur_SalesInvoicesExport';
    //public $defClasses = 'bnav_bnavExport_ContragentsExport,bnav_bnavExport_ItemsExport,bnav_bnavExport_SalesInvoicesExport,bnav_bnavExport_PurchaseInvoicesExport';


    /**
     * Описание на модула
     */
    public $info = 'Драйвър за експорт към "Ажур"';


    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        'admin',
        'debug',
    );


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();

        return $html;
    }
}
