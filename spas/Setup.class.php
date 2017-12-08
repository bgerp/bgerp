<?php



/**
 * Хост
 */
defIfNot('SPAS_HOSTNAME', 'localhost');


/**
 * Хост
 */
defIfNot('SPAS_PORT', 783);


/**
 * Потребител
 */
defIfNot('SPAS_USER', '');


/**
 * class spas_Setup
 *
 * Интерфейс за SpamAssassin
 *
 * @category  bgerp
 * @package   spas
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class spas_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'spas_Test';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = '';
    
    
    /**
     * Описание на модула
     */
    var $info = "Интеграция със SpamAssassin";
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
        
        // Host
        'SPAS_HOSTNAME' => array ('varchar', 'caption=Връзка със SpamAssassin->Host'),
        
        // Порт
        'SPAS_PORT' => array ('int', 'caption=Връзка със SpamAssassin->Port'),

        // Потребител
        'SPAS_USER' => array ('varchar', 'caption=Връзка със SpamAssassin->User'),

     );
		
		
    /**
     * След първоначално зареждане на данните
     */
    function loadSetupData($itr = '')
    {
        $sa = spas_Test::getSa();
        
        $resStr = '';
        
        try {
            $res = $sa->ping();
        } catch(spas_client_Exception $e) {
            $resStr .= "<li class='error'>Грешка: {$e->getMessage()}</li>";
        }
        
        if ($res === TRUE) {
            $resStr .= "<li style='color:green'>Има връзка със SPAS</li>";
        }
        
        return $resStr;
    }
}
