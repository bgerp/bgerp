<?php



/**
 * Дефолтно общо условие за продажба
 */
defIfNot('TRANSSRV_SALE_DEFAULT_CONDITION', '');


/**
 * Клас 'transsrv_Setup' 
 *
 *
 * @category  bgerp
 * @package   transsrv
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   Property
 * @since     v 0.1
 */
class transsrv_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'transsrv_TransportModes';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'drdata=0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Добавя артикул за транспорт";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'transsrv_TransportModes',
            'transsrv_TransportUnits',
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'transsrv';
    

    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    		'TRANSSRV_SALE_DEFAULT_CONDITION' => array("text", 'caption=Общо условие за продажба по подразбиране->Условие'),
    );

    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = "transsrv_ProductDrv";
         

    
}
