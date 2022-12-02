<?php


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
 * @title     Експорт в Ажур » Експорт фактури продажби в Ажур
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
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';


    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses ;
    //public $defClasses = 'bnav_bnavExport_ContragentsExport,bnav_bnavExport_ItemsExport,bnav_bnavExport_SalesInvoicesExport,bnav_bnavExport_PurchaseInvoicesExport';


    /**
     * Описание на модула
     */
    public $info = 'Драйвър за експорт към "Ажур"';


    /**
     * Роли за достъп до модула
     */
    public $roles ;


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();

        return $html;
    }
}
