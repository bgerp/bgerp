<?php


/**
 * Версия на YUKU textcomplete
 */
defIfNot('RTAC_YUKU_VERSION', '0.2.4');


/**
 * Класа, който да се използва за autocomplete
 */
defIfNot('RTAC_AUTOCOMPLETE_CLASS', 'rtac_yuku_Textcomplete');


/**
 * 
 * 
 * @category  vendors
 * @package   rtac
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class rtac_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
        
    
    /**
     * Описание на модула
     */
    var $info = "Autocomplete за ричтекст";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
        'rtac_yuku_Textcomplete',
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
       'RTAC_AUTOCOMPLETE_CLASS' => array ('class(interface=rtac_AutocompleteIntf, select=title)', 'caption=Клас за autocomplete->Клас'),
       'RTAC_YUKU_VERSION' => array ('enum(0.2.4)', 'caption=Версия на YUKU->Версия'),
     );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за конвертиране от highlight
        $html .= $Plugins->installPlugin('Richtext autocomplete', 'rtac_Plugin', 'type_Richtext', 'private');
        
        return $html;
    }
}
