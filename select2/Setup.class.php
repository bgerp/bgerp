<?php


/**
 * Пътя до външния код на SELECT2
 */
defIfNot('SELECT2_VERSION', '4.0.1');


/**
 * Пътя до външния код на SELECT2toTree
 */
defIfNot('SELECT2_TOTREE_VERSION', '19.03.26');


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
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link      https://select2.github.io/
 */
class select2_Setup extends core_ProtoSetup
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
    public $info = 'Удобно избиране от множества със Select2';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        // Минималния брой елементи, за които няма да сработи SELECT2
        'SELECT2_WIDE_MIN_SEARCH_ITEMS_CNT' => array('int', 'caption=Минимален брой опции за да сработи търсенето в Select2->За широк екран, suggestions=5|10|20|50|100'),
        'SELECT2_NARROW_MIN_SEARCH_ITEMS_CNT' => array('int', 'caption=Минимален брой опции за да сработи търсенето в Select2->За тесен екран, suggestions=5|10|20|50|100'),
        'SELECT2_VERSION' => array('enum(4.0rc2, 4.0.1, 4.0.7, 4.0.13)', 'caption=Версия на Select2->Версия'),
        'SELECT2_TOTREE_VERSION' => array('enum(19.03.26)', 'caption=Версия на Select2ToTree->Версия'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = '';
        
        $html .= parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Плъгини за keylist и наследниците му
        $html .= $Plugins->forcePlugin('Select2 за тип Keylist', 'select2_Plugin', 'type_Keylist', 'private');
        $html .= $Plugins->forcePlugin('Select2 за тип Classes', 'select2_Plugin', 'type_Classes', 'private');

        $html .= $Plugins->forcePlugin('Select2 за тип Accounts', 'select2_Plugin', 'acc_type_Accounts', 'private');
        
        $html .= $Plugins->forcePlugin('Select2 за тип Users', 'select2_PluginSelect', 'type_Users', 'private');
        
        // Плъгини за key и наследниците му
        $html .= $Plugins->forcePlugin('Select2 за тип Key', 'select2_PluginSelect', 'type_Key', 'private');
        $html .= $Plugins->forcePlugin('Select2 за тип CustomKey', 'select2_PluginSelect', 'type_CustomKey', 'private');
        $html .= $Plugins->forcePlugin('Select2 за тип Class', 'select2_PluginSelect', 'type_Class', 'private');
        $html .= $Plugins->forcePlugin('Select2 за тип User', 'select2_PluginSelect', 'type_User', 'private');
        $html .= $Plugins->forcePlugin('Select2 за тип UserOrRole', 'select2_PluginSelect', 'type_UserOrRole', 'private');
        $html .= $Plugins->forcePlugin('Select2 за тип Item', 'select2_PluginSelect', 'acc_type_Item', 'private');
        $html .= $Plugins->forcePlugin('Select2 за тип Account', 'select2_PluginSelect', 'acc_type_Account', 'private');
        
        // Плъгини за enum
        $html .= $Plugins->forcePlugin('Select2 за тип Enum', 'select2_PluginEnum', 'type_Enum', 'private');
        
        $html .= $Plugins->forcePlugin('Select2 за тип SmartSelect', 'select2_PluginSmartSelect', 'core_Form', 'private');


        return $html;
    }
    
    
    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonJs()
    {
        $conf = core_Packs::getConfig('select2');
        $coreConf = core_Packs::getConfig('core');
        return 'select2/js/adapter.js, select2/' . $conf->SELECT2_VERSION . '/select2.min.js, select2/' . $conf->SELECT2_VERSION . '/i18n/' . $coreConf->EF_DEFAULT_LANGUAGE . '.js';
    }
    
    
    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonCss()
    {
        $conf = core_Packs::getConfig('select2');
        
        return 'select2/' . $conf->SELECT2_VERSION . '/select2.min.css';
    }
}
