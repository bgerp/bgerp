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
        'AJUR_DOC_INVOCIE_TYPE' => array('int', 'caption=Експорт->Документ фактура'),
        'AJUR_DOC_DEBIT_NOTE_TYPE' => array('int', 'caption=Експорт->Документ дебитно известие'),
        'AJUR_DOC_CREDIT_NOTE_TYPE' => array('int', 'caption=Експорт->Документ кредитно известие'),
        'AJUR_DOC_INTER_INVOICE_TYPE' => array('int', 'caption=Експорт->Документ вътрешна фактура'),
        'AJUR_DOC_SALES_REPORT_TYPE' => array('int', 'caption=Експорт->Документ отчет за продажбите'),

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
