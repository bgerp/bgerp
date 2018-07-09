<?php


/**
 * Версията на chartjs, която се използва
 */
defIfNot('CONTEXT_VERSION', '1.4.0');


/**
 * Клас 'content_Setup' - контекстно меню за бутоните от втория ред на тулбара
 *
 *
 * @category  vendors
 * @package   context
 *
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class context_Setup extends core_ProtoSetup
{
    /**
     * контекстно меню за бутоните
     */
    public $info = 'Контекстно меню за бутоните от тулбара';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        'CONTEXT_VERSION' => array('enum(1.4.0)', 'mandatory, caption=Версията на плъгина->Версия')
    
    );
    
    
    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonJs()
    {
        return 'context/'.  context_Setup::get('VERSION') . '/contextMenu.js';
    }
    
    
    /**
     * Връща CSS файлове, които са подходящи за компактиране
     */
    public function getCommonCss()
    {
        return 'context/'.  context_Setup::get('VERSION') . '/contextMenu.css';
    }
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме контекстното меню към тулбара
        $html .= $Plugins->installPlugin('Контекстно меню', 'context_Plugin', 'core_Toolbar', 'private');
        
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
        
        // Премахваме от type_Date полета
        $Plugins->deinstallPlugin('context_Plugin');
        $html .= "<li>Премахнати са всички инсталации на 'context_Plugin'";
        
        return $html;
    }
}
