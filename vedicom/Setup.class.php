<?php



/**
 * class vedicom_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с vedicom везни
 *
 *
 * @category  vendors
 * @package   vedicom
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class vedicom_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'vedicom_Weight';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Чете тегло от Vedicom - VEDIA VDI везни';


    /**
     * Необходими пакети
     */
    public $depends = '';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array();
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
            'vedicom_Weight'
        );
 
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}
