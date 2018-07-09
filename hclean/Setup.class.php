<?php


/**
 * Дали да се вкара CSS'а в inline атрибут
 */
defIfNot('HCLEAN_JSSANITIZER_PLACE_CSS_TO_INLINE', 'yes');


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
 * @copyright 2006 - 2012 Experta OOD
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
    public $info = 'Изчистване на HTML';
    
    
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
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        $html = parent::deinstall();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Премахваме от type_Keylist полета
        $Plugins->deinstallPlugin('hclean_HtmlPurifyPlg');
        $html .= "<li>Премахнати са всички инсталации на 'hclean_HtmlPurifyPlg'";
        
        return $html;
    }
}
