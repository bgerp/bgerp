<?php


/**
 * Максимален брой за предложенията за последно използвани стойности на поле
 */
defIfNot('RECENTLY_MAX_SUGGESTION', 20);


/**
 * Максимален брой дни за запазване на стойност след нейната последна употреба
 */
defIfNot('RECENTLY_MAX_KEEPING_DAYS', 60);


/**
 * class recently_Setup
 *
 * Инсталиране/Деинсталиране на
 * Подсказки за инпут полетата
 *
 *
 * @category  vendors
 * @package   recently
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class recently_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'recently_Values';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Запомняне в избрани полета на последно въведените данни';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        // Максимален брой за предложенията за последно използвани стойности на поле
        'RECENTLY_MAX_SUGGESTION' => array('int', 'caption=Максимален брой за предложенията за последно използвани стойности на поле->Брой'),
        
        // Максимален брой дни за запазване на стойност след нейната последна употреба
        'RECENTLY_MAX_KEEPING_DAYS' => array('int', 'caption=Максимален брой дни за запазване на стойност след нейната последна употреба->Дни'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Установяваме мениджъра;
        $Values = cls::get('recently_Values');
        $html .= $Values->setupMVC();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме recently към формите
        $html .= $Plugins->installPlugin('Recently', 'recently_Plugin', 'core_Form', 'private');
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}
