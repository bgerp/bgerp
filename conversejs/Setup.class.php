<?php


/**
 * Минималния брой елементи, за които няма да сработи Chosen
 */
defIfNot('CONVERSEJS_BOSH_SERVICE_URL', 'https://conversejs.org/http-bind/');


/**
 * Клас 'chosen_Setup' - Предава по добър изглед на keylist полетата
 *
 *
 * @category  bgerp
 * @package   conversejs
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class conversejs_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = '';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Уеб чат за XMPP протокол';
    
        
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
    
            // Минималния брой елементи, за които няма да сработи Chosen
            'CONVERSEJS_BOSH_SERVICE_URL' => array('url', 'caption=BOSH_SERVICE->Url'),
    
        );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме
        $html .= $Plugins->forcePlugin('ConverseJS Chat', 'conversejs_Plugin', 'core_page_InternalModern', 'private');

        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        $html = parent::deinstall();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Премахваме от type_Keylist полета
        $Plugins->deinstallPlugin('conversejs_Plugin');
        $html .= "<li>Премахнати са всички инсталации на 'conversejs_Plugin'";
       
        return $html;
    }
}
