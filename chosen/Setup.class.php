<?php


/**
 * Пътя до външния код на chosen
 */
defIfNot('CHOSEN_PATH', 'chosen/0.9.8');


/**
 * Минималния брой елементи, за които няма да сработи Chosen
 */
defIfNot('CHOSEN_MIN_ITEMS', 30);


/**
 * Клас 'chosen_Setup' - Предава по добър изглед на keylist полетата
 *
 *
 * @category  vendors
 * @package   chosen
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link      http://harvesthq.github.com/chosen/
 */
class chosen_Setup extends core_ProtoSetup
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
    public $info = 'Удобно избиране от множества. По-стара алтернатива на Select2';
    
    
    
    public $deprecated = true;
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
    
            // Минималния брой елементи, за които няма да сработи Chosen
            'CHOSEN_MIN_ITEMS' => array('int', 'caption=Минимален брой опции за да сработи Chosen->Опции, suggestions=10|20|30|40|50'),
    
        );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = '';
        if (core_Packs::isInstalled('select2')) {
            $packs = cls::get('core_Packs');
            $html .= $packs->deinstall('select2');
        }
        
        $html .= parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме
        $html .= $Plugins->forcePlugin('Chosen', 'chosen_Plugin', 'type_Keylist', 'private');
        $html .= $Plugins->forcePlugin('ChosenSelect', 'chosen_PluginSelect', 'type_Key', 'private');
        $html .= $Plugins->forcePlugin('ChosenSelectUser', 'chosen_PluginSelect', 'type_User', 'private');
        $html .= $Plugins->forcePlugin('ChosenSelectUsers', 'chosen_PluginSelect', 'type_Users', 'private');
        $html .= $Plugins->forcePlugin('ChosenSelectItem', 'chosen_PluginSelect', 'acc_type_Item', 'private');
        $html .= $Plugins->forcePlugin('ChosenSelectAccount', 'chosen_PluginSelect', 'acc_type_Account', 'private');
        $html .= $Plugins->forcePlugin('ChosenAccounts', 'chosen_Plugin', 'acc_type_Accounts', 'private');

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
        $Plugins->deinstallPlugin('chosen_Plugin');
        $html .= "<li>Премахнати са всички инсталации на 'chosen_Plugin'";
        
        // Премахваме от type_Key полета
        $Plugins->deinstallPlugin('chosen_PluginSelect');
        $html .= "<li>Премахнати са всички инсталации на 'chosen_PluginSelect'";
       
        return $html;
    }


    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonJs()
    {
        $conf = core_Packs::getConfig('chosen');
        
        return $conf->CHOSEN_PATH . '/chosen.jquery.js';
    }
    
    
    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonCss()
    {
        $conf = core_Packs::getConfig('chosen');
        
        return $conf->CHOSEN_PATH . '/chosen.css';
    }
}
