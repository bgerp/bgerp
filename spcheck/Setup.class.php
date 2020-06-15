<?php


/**
 * Над колко пъти съвпадени, дадена дума да се взема за коректна
 */
defIfNot('SPCHECK_AUTO_CORRECT_CNT', 200);


/**
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с продуктите
 *
 *
 * @category  bgerp
 * @package   spcheck
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class spcheck_Setup extends core_ProtoSetup
{
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'spcheck_Dictionary';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Проверка на правопис';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
            'SPCHECK_AUTO_CORRECT_CNT' => array('int(Min=0)', 'caption=Брой съвпадение над които думата ще се счиата за коректна->Съвпадения'),
            
    );
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'spcheck_Dictionary',
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function checkConfig()
    {
        $modulName = 'pspell';
        
        $activePhpModules = get_loaded_extensions();
        
        if (!in_array($modulName, $activePhpModules)) {
            
            return "Не е инсталиран PHP модулът '{$modulName}'";
        }
    }
    
    
    /**
     * Скрипт за инсталиране
     */
    public function install()
    {
        $html = parent::install();
        
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме на плъгина за проверка на правописа
        $html .= $Plugins->forcePlugin('Spell Check', 'spcheck_Plugin', 'core_Master', 'family');
        
        return $html;
    }
}
