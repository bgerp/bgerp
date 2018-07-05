<?php



/**
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с пакета markdown
 *
 * @category  vendors
 * @package   markdown
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class markdown_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
        
    
    /**
     * Описание на модула
     */
    public $info = 'Конвертиране от markdown текст към HTML';
    

    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за конвертиране от markdown
        $html .= $Plugins->installPlugin('Markdown', 'markdown_RichTextPlg', 'type_Richtext', 'private');
        
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
        
        // Деинсталираме markdown конвертора
        if ($delCnt = $Plugins->deinstallPlugin('markdown_RichTextPlg')) {
            $html .= "<li>Премахнати са {$delCnt} закачания на 'type_Richtext'";
        } else {
            $html .= '<li>Не са премахнати закачания на плъгина';
        }
        
        return $html;
    }
}
