<?php


/**
 * Време на бездействие на таба, преди което съобщението ще се маркира, като прочетено
 */
defIfNot('SPEEDY_OFFICE_LOCATOR_URL', "https://www.speedy.bg/speedy_office_locator_widget/googleMaps.php?lang=bg&id=[#NUM#]&src=sws");


/**
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с speedy
 *
 * @category  bgerp
 * @package   speedy
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class speedy_Setup extends core_ProtoSetup
{
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Необходими пакети
     * @todo да се махне като направим да работи с API-то
     */
    public $depends = 'tcost=0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'speedy_Offices';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Интеграция с "speedy"';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array('SPEEDY_OFFICE_LOCATOR_URL' => array('varchar', 'caption=Локатор на офис'),);
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array('speedy_Offices');
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(1.99999, 'Система', 'SPEEDY', 'speedy_Offices', 'default', 'admin'),
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'speedy_interface_DeliveryToOffice';
}