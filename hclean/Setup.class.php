<?php


/**
 * Дали да се вкара CSS'а в inline атрибут
 */
defIfNot('HCLEAN_JSSANITIZER_PLACE_CSS_TO_INLINE', 'yes');


/**
 * Версия на HTML Purifier
 */
defIfNot('HCLEAN_PURIFIER_VERSION', '4.3.0');


/**
 * Клас 'hclean_Setup' - Инсталира плъгина за изчистване на HTML полетата и създава директория,
 *
 * необходима за работа на hclean_Purifier
 *
 *
 * @category  vendors
 * @package   hclean
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class hclean_Setup extends core_ProtoSetup
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
    public $info = 'Почистване на HTML от опасен код';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'HCLEAN_PURIFIER_VERSION' => array('enum(4.3.0, 4.19.1)', 'caption=Версия на HTML Purifier->Версия'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        //Създаваме директорията, необходима за работа на hclean_Purifier
        $html .= hclean_Purifier::mkdir();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме (не го правим автоматично)
        // $html .= $Plugins->installPlugin('HClean', 'hclean_HtmlPurifyPlg', 'type_Html', 'private');
        
        return $html;
    }
}
