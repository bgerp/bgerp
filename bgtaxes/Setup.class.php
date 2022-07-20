<?php


/**
 * Пакет за Акцизи и Такси в България
 *
 *
 * @category  bgerp
 * @package   taxes
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgtaxes_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';


    /**
     * Необходими пакети
     */
    public $depends = 'sales=0.1';


    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';


    /**
     * Описание на модула
     */
    public $info = 'Български акцизи и такси';


    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'bgtaxes_ProductTaxes',
    );


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();

        cat_Params::force('exciseBgn', 'Акциз', 'double', null, 'лв', false, false, null, "min=0");
        $html .= "Добавен е параметър за акциз";

        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->installPlugin('Акцизи и такси към редовете на изходяща фактура', 'bgtaxes_plg_SaleInvoiceDetail', 'sales_InvoiceDetails', 'private');
        $html .= $Plugins->installPlugin('Акцизи и такси към изходящата фактура', 'bgtaxes_plg_SaleInvoice', 'sales_Invoices', 'private');
        $html .= cls::get('sales_InvoiceDetails')->setupMvc();

        return $html;
    }
}
