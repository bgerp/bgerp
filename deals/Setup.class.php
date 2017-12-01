<?php


/**
 * Толеранс за допустимо разминаване в салдото->Сума
 */
defIfNot('DEALS_BALANCE_TOLERANCE', '0.01');


/**
 * class deals_Setup
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class deals_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Екшън - входна точка в пакета.
     */
    var $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'drdata=0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Помощни класове за бизнес документите";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'deals_OpenDeals',
        );

    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    		'DEALS_BALANCE_TOLERANCE' => array("percent(min=0)", 'caption=Процент за допустимо разминаване в салдото според сумата->Процент'),
    );
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = "deals_reports_ArrearsImpl,deals_reports_ReportPaymentDocuments";
    
    
     /**
     * Роли за достъп до модула
     */
    var $roles = 'dealJoin';
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}
