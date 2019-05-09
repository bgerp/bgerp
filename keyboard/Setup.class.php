<?php


/**
 * @todo Чака за документация...
 */
defIfNot('VKI_version', '1.28');


/**
 * Клас 'keyboard_Setup' -
 *
 *
 * @category  vendors
 * @package   keyboard
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class keyboard_Setup extends core_ProtoSetup
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
    public $info = 'Виртуална клавиатура. Показва се с двоен клик в десния край на полето';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        'VKI_version' => array('enum(1.28)', 'mandatory, caption=Версията на програмата->Версия')
    
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        $html .= $Plugins->installPlugin('Nick VKB', 'keyboard_Plugin', 'type_Nick', 'private');
        
        // Инсталираме клавиатурата към password полета
        $html .= $Plugins->installPlugin('Pass VKB', 'keyboard_Plugin', 'type_Password', 'private');
        
        // Инсталиране към всички полета, но без активиране
        $html .= $Plugins->installPlugin('All VKB', 'keyboard_Plugin', 'core_Type', 'family', 'stopped');
        
        return $html;
    }
    
    
    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonJs()
    {
        $conf = core_Packs::getConfig('keyboard');
        
        return 'keyboard/' . $conf->VKI_version . '/keyboard.js';
    }
    
    
    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonCss()
    {
        $conf = core_Packs::getConfig('keyboard');
        
        return 'keyboard/' . $conf->VKI_version . '/keyboard.css';
    }
}
