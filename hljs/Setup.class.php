<?php


/**
 * Версията на продукта
 */
defIfNot('HLJS_VERSION', '7.3');


/**
 * Инсталиране/Деинсталиране на плъгини свързани с hljs
 *
 * @category  vendors
 * @package   hljs
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hljs_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
        
    
    /**
     * Описание на модула
     */
    public $info = 'Адаптер за highlightjs: Оцветяване на програмен код';
    

    /**
     * Пътища до CSS файлове
     */
//    var $commonCSS = "hljs/[#HLJS_VERSION#]/styles/default.css";
    
    
    /**
     * Пътища до JS файлове
     */
//    var $commonJS = "hljs/[#HLJS_VERSION#]/highlight.pack.js";
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за конвертиране от highlight
        $html .= $Plugins->installPlugin('Highlight', 'hljs_RichTextPlg', 'type_Richtext', 'private');
        
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
        
        // Деинсталираме highlight конвертора
        if ($delCnt = $Plugins->deinstallPlugin('hljs_RichTextPlg')) {
            $html .= "<li>Премахнати са {$delCnt} закачания на 'type_Richtext'";
        } else {
            $html .= '<li>Не са премахнати закачания на плъгина';
        }
        
        return $html;
    }
}
