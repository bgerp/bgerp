<?php


/**
 * Пътя до външния код на SELECT2
 */
defIfNot('SELECT2_VERSION', '4.0b3');


/**
 * Минимален брой опции за да сработи търсенето в Select2->За широк екран
 */
defIfNot('SELECT2_WIDE_MIN_SEARCH_ITEMS_CNT', 10);


/**
 * Минимален брой опции за да сработи търсенето в Select2->За тесен екран
 */
defIfNot('SELECT2_NARROW_MIN_SEARCH_ITEMS_CNT', 5);


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
        'SELECT2_WIDE_MIN_SEARCH_ITEMS_CNT' => array ('int', 'caption=Минимален брой опции за да сработи търсенето в Select2->За широк екран, suggestions=5|10|20|50|100'),
        'SELECT2_NARROW_MIN_SEARCH_ITEMS_CNT' => array ('int', 'caption=Минимален брой опции за да сработи търсенето в Select2->За тесен екран, suggestions=5|10|20|50|100'),
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
        if (core_Packs::isInstalled('chosen')) {
            $packs = cls::get('core_Packs');
            $html .= $packs->deinstall('chosen');
        }
        
    	$html .= parent::install();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Плъгини за keylist и наследниците му
        $html .= $Plugins->forcePlugin('Select2 за тип Keylist', 'select2_Plugin', 'type_Keylist', 'private');
        $html .= $Plugins->forcePlugin('Select2 за тип Accounts', 'select2_Plugin', 'acc_type_Accounts', 'private');
//        $html .= $Plugins->forcePlugin('Select2 за тип UsersList', 'select2_Plugin', 'type_UserList', 'private');
        
        $html .= $Plugins->forcePlugin('Select2 за тип Users', 'select2_PluginSelect', 'type_Users', 'private');
        
        // Плъгини за key и наследниците му
        $html .= $Plugins->forcePlugin('Select2 за тип Key', 'select2_PluginSelect', 'type_Key', 'private');
        $html .= $Plugins->forcePlugin('Select2 за тип CustomKey', 'select2_PluginSelect', 'type_CustomKey', 'private');
        $html .= $Plugins->forcePlugin('Select2 за тип Class', 'select2_PluginSelect', 'type_Class', 'private');
        $html .= $Plugins->forcePlugin('Select2 за тип User', 'select2_PluginSelect', 'type_User', 'private');
        $html .= $Plugins->forcePlugin('Select2 за тип Item', 'select2_PluginSelect', 'acc_type_Item', 'private');
        $html .= $Plugins->forcePlugin('Select2 за тип Account', 'select2_PluginSelect', 'acc_type_Account', 'private');
        
        // Плъгини за enum
        $html .= $Plugins->forcePlugin('Select2 за тип Enum', 'select2_PluginEnum', 'type_Enum', 'private');
        
        return $html;
    }
}
