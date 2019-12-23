<?php


/**
 * class tremol_Setup
 *
 * Инсталиране/Деинсталиране на
 * пакет за работа с фискални принтери на Тремол
 *
 *
 * @category  bgerp
 * @package   tremol
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class tremol_Setup extends core_ProtoSetup
{
    /**
     * Необходими пакети
     */
    public $depends = 'peripheral=0.1, webkittopdf=0.1';
    
    
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
    public $info = 'Фискален принтер на Тремол';
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'tremol_FiscPrinterDriverWeb, tremol_FiscPrinterDriverIp';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array();
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        $html .= fileman_Buckets::createBucket('electronicReceipts', 'Електронни фискални бонове', 'pdf,png,jpg,jpeg', '104857600', 'every_one', 'every_one');
        
        return $html;
    }
}
