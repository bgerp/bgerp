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
 * Максималният брой елементи, които ще се показват за autocomplete
 */
defIfNot('RTAC_MAX_SHOW_COUNT', 6);


/**
 * Роли, които трябва да има потребителя, за да се покаже в autocomplete
 */
defIfNot('RTAC_DEFAUL_SHARE_USER_ROLES', 'powerUser');


/**
 * Роли, от които трябва да има потребителя, за да може да ползва autocomplete-a за споделяне
 */
defIfNot('RTAC_DEFAUL_USER_ROLES_FOR_SHARE', 'powerUser');


/**
 * Роли, от които трябва да има потребителя, за да може да ползва autocomplete-a за добавяне на текстове
 */
defIfNot('RTAC_DEFAULT_ROLES_FOR_TEXTCOMPLETE', 'user');


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
    public $version = '0.1';
        
    
    /**
     * Описание на модула
     */
    public $info = 'Споделяне чрез тагване на @потребител в ричтекст';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'rtac_yuku_Textcomplete',
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
       'RTAC_AUTOCOMPLETE_CLASS' => array('class(interface=rtac_AutocompleteIntf, select=title)', 'caption=Клас за autocomplete->Клас'),
       'RTAC_YUKU_VERSION' => array('enum(0.2.4)', 'caption=Версия на YUKU->Версия'),
       'RTAC_MAX_SHOW_COUNT' => array('int', 'caption=Максималният брой елементи|*&comma;| които ще се показват за autocomplete->Брой'),
       'RTAC_DEFAUL_SHARE_USER_ROLES' => array('varchar', 'caption=Роли|*&comma;| които трябва да има потребителя|*&comma;| за да се покаже в autocomplete->Роли'),
       'RTAC_DEFAUL_USER_ROLES_FOR_SHARE' => array('varchar', 'caption=Роли|*&comma;| от които трябва да има потребителя|*&comma;| за да може да ползва autocomplete-a за споделяне->Роли'),
       'RTAC_DEFAULT_ROLES_FOR_TEXTCOMPLETE' => array('varchar', 'caption=Роли|*&comma;| от които трябва да има потребителя|*&comma;| за да може да ползва autocomplete-a за текстове->Роли'),
     );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за конвертиране от highlight
        $html .= $Plugins->installPlugin('Richtext autocomplete', 'rtac_Plugin', 'type_Richtext', 'private');
        $html .= $Plugins->installPlugin('Text autocomplete', 'rtac_TextPlugin', 'type_Text', 'private');
        $html .= $Plugins->installPlugin('Richtext text autocomplete', 'rtac_TextPlugin', 'type_Richtext', 'private');
        
        return $html;
    }


    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonJs()
    {
        $conf = core_Packs::getConfig('rtac');
        
        return 'rtac/yuku/' . $conf->RTAC_YUKU_VERSION . '/jquery.textcomplete.js';
    }
    
    
    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonCss()
    {
        return 'rtac/yuku/autocomplete.css';
    }
}
