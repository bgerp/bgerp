<?php


/**
 * Сметка за каса в лева
 */
defIfNot('FSD_BGN_ACCOUNT', '501');


/**
 * Сметка каса ПОС устройство
 */
defIfNot('FSD_POS_ACCOUNT', '5011');


/**
 * Сметка каса за отложено плащане
 */
defIfNot('FSD_BGN_DELAYED_ACCOUNT', '5013');


/**
 * Сметка каса за отложено плащане
 */
defIfNot('FSD_BGN_POSTAL_ACCOUNT', '5014');


/**
 * Сметка за каса във валута
 */
defIfNot('FSD_NON_BGN_ACCOUNT', '502');


/**
 * Сметка за клиенти вътрешен пазар
 */
defIfNot('FSD_CONTRAGENTS_BG_ACCOUNT', '411');


/**
 * Сметка за клиенти външен пазар
 */
defIfNot('FSD_CONTRAGENTS_NON_BG_ACCOUNT', '4111');


/**
 * Сметка за клиенти по аванси вътрешен пазар по банка
 */
defIfNot('FSD_DP_CONTRAGENTS_BG_ACCOUNT', '412');


/**
 * Сметка за клиенти по аванси външен пазар
 */
defIfNot('FSD_DP_CONTRAGENTS_NON_BG_ACCOUNT', '4121');


/**
 * Сметка за приходи бъдещи периоди
 */
defIfNot('FSD_FUTURE_INCOME_ACCOUNT', '704');


/**
 * Папка в която да се експортират фактурите
 */
defIfNot('FSD_FOLDER_ID', '8');


/**
 * Папка в която да се експортират фактурите
 */
defIfNot('FSD_DOC_INVOCIE_TYPE', '12');


/**
 * Папка в която да се експортират фактурите
 */
defIfNot('FSD_DOC_DEBIT_NOTE_TYPE', '10');


/**
 * Папка в която да се експортират фактурите
 */
defIfNot('FSD_DOC_CREDIT_NOTE_TYPE', '11');


/**
 * Номер на вид сделка за българския пазар
 */
defIfNot('FSD_DEAL_TYPE_BG', '21');


/**
 * Номер за вид сделка за ЕС
 */
defIfNot('FSD_DEAL_TYPE_EU', '23');


/**
 * Номер за вид сделка извън ЕС
 */
defIfNot('FSD_DEAL_TYPE_NON_EU', '22');


/**
 * Сметка начислен ДДС по продажби
 */
defIfNot('FSD_VAT_ACCOUNT', '4532');


/**
 * Начален номер на ПБД
 */
defIfNot('FSD_PBD_NUM', 1);


/**
 * Тип на сделките по чл. 163А от ЗДДС
 */
defIfNot('FSD_ZDDS_CHL163A_DEAL_TYPE', 28);


/**
 * ФСД номер на сделките по чл. 163А от ЗДДС
 */
defIfNot('FSD_ZDDS_CHL163A', 88);


/**
 * class bnav_Setup
 *
 * Инсталиране/Деинсталиране на
 * Драйвър за импортиране от csv файл на Бизнес навигатор
 *
 *
 * @category  bgerp
 * @package   bnav
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bnav_Setup extends core_ProtoSetup
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
    public $defClasses = 'bnav_bnavExport_ContragentsExport,bnav_bnavExport_ItemsExport,bnav_bnavExport_SalesInvoicesExport,bnav_bnavExport_PurchaseInvoicesExport';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Драйвър за импорт от "Бизнес навигатор"';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'FSD_FOLDER_ID' => array('int', 'caption=Експорт->Каса в лева'),
        'FSD_DOC_INVOCIE_TYPE' => array('int', 'caption=Експорт->Документ фактура'),
        'FSD_DOC_DEBIT_NOTE_TYPE' => array('int', 'caption=Експорт->Документ дебитно известие'),
        'FSD_DOC_CREDIT_NOTE_TYPE' => array('int', 'caption=Експорт->Документ кредитно известие'),
        'FSD_DEAL_TYPE_BG' => array('int', 'caption=Експорт->Сделка вътрешен пазар'),
        'FSD_DEAL_TYPE_EU' => array('int', 'caption=Експорт->Сделка за ЕС'),
        'FSD_DEAL_TYPE_NON_EU' => array('int', 'caption=Експорт->Сделка извен ЕС'),
        'FSD_BGN_ACCOUNT' => array('varchar', 'caption=Експорт сметки->Каса в лева'),
        'FSD_NON_BGN_ACCOUNT' => array('varchar', 'caption=Експорт сметки->Каса във валута'),
        'FSD_POS_ACCOUNT' => array('varchar', 'caption=Експорт сметки->Каса POS'),
        'FSD_BGN_DELAYED_ACCOUNT' => array('varchar', 'caption=Експорт сметки->Каса отложено плащане'),
        'FSD_BGN_POSTAL_ACCOUNT' => array('varchar', 'caption=Експорт сметки->Пощенски паричен превод'),
        'FSD_CONTRAGENTS_BG_ACCOUNT' => array('varchar', 'caption=Експорт сметки->Клиенти вътрешен пазар'),
        'FSD_CONTRAGENTS_NON_BG_ACCOUNT' => array('varchar', 'caption=Експорт сметки->Клиенти външен пазар'),
        'FSD_DP_CONTRAGENTS_BG_ACCOUNT' => array('varchar', 'caption=Експорт сметки->Клиенти по аванси вътрешен пазар'),
        'FSD_DP_CONTRAGENTS_NON_BG_ACCOUNT' => array('varchar', 'caption=Експорт сметки->Клиенти по аванси в чужбина'),
        'FSD_FUTURE_INCOME_ACCOUNT' => array('varchar', 'caption=Експорт сметки->Приходи за бъдещи периоди'),
        'FSD_VAT_ACCOUNT' => array('varchar', 'caption=Експорт сметки->Начислен ДДС за проджби'),
        'FSD_PBD_NUM' => array('int', 'caption=Приходни банкови документи->Последователен номер'),
        'FSD_ZDDS_CHL163A' => array('int', 'caption=Сделка по чл 163 А ЗДДС->FSD номер'),
        'FSD_ZDDS_CHL163A_DEAL_TYPE' => array('int', 'caption=Сделка по чл 163 А ЗДДС->Тип сделка'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме клавиатурата към password полета
        $html .= $Plugins->installPlugin('bnavPlugin', 'bnav_Plugin', 'cat_Products', 'private');
        
        // Добавяме Импортиращия драйвър в core_Classes
        $html .= core_Classes::add('bnav_bnavImporter');
        $html .= cls::get('cat_Products')->setupMvc();
        
        return $html;
    }
}
