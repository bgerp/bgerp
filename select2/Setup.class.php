<?php


/**
 * Пътя до външния код на SELECT2
 */
defIfNot('SELECT2_VERSION', '4.0b3');


/**
 * Минималния брой елементи, за които няма да сработи SELECT2 - за keylist
 */
defIfNot('SELECT2_KEYLIST_MIN_ITEMS', 30);


/**
 * Минималния брой елементи, за които няма да сработи SELECT2 - за key
 */
defIfNot('SELECT2_KEY_MIN_ITEMS', 30);


/**
 * 
 * 
 * @category  vendors
 * @package   chosen
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link      https://select2.github.io/
 */
class select2_Setup extends core_ProtoSetup {
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = '';
    
    
    /**
     * Описание на модула
     */
    var $info = "Удобно избиране от множества със Select2";
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    
        // Минималния брой елементи, за които няма да сработи SELECT2
        'SELECT2_KEYLIST_MIN_ITEMS' => array ('int', 'caption=Минимален брой опции за да сработи Select2->За keylist, suggestions=20|30|40|50|100'),
        'SELECT2_KEY_MIN_ITEMS' => array ('int', 'caption=Минимален брой опции за да сработи Select2->За key, suggestions=20|30|40|50|100'),
        'SELECT2_VERSION' => array ('enum(4.0b3)', 'caption=Версия на Select2->Версия'),
    );
    
    
    /**
     * Пътища до CSS файлове
     */
    var $commonCSS = "select2/[#SELECT2_VERSION#]/select2.min.css";
    
    
    /**
     * Пътища до JS файлове
     */
    var $commonJS = "select2/[#SELECT2_VERSION#]/select2.min.js, select2/[#SELECT2_VERSION#]/i18n/[#CORE::EF_DEFAULT_LANGUAGE#].js";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме
        $html .= $Plugins->forcePlugin('Select2 за keylist', 'select2_Plugin', 'type_Keylist', 'private');
        $html .= $Plugins->forcePlugin('Select2 за Accounts', 'select2_Plugin', 'acc_type_Accounts', 'private');
        $html .= $Plugins->forcePlugin('Select2 за usersList', 'select2_Plugin', 'type_UserList', 'private');
        
        $html .= $Plugins->forcePlugin('Select2 за key', 'select2_PluginSelect', 'type_Key', 'private');
        $html .= $Plugins->forcePlugin('Select2 за user', 'select2_PluginSelect', 'type_User', 'private');
        $html .= $Plugins->forcePlugin('Select2 за item', 'select2_PluginSelect', 'acc_type_Item', 'private');
        $html .= $Plugins->forcePlugin('Select2 за account', 'select2_PluginSelect', 'acc_type_Account', 'private');
        
        return $html;
    }
}
