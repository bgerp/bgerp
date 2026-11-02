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
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
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
     * Избира библиотеката през стандартната конфигурация на пакета.
     */
    public $configDescription = array(
        'HLJS_VERSION' => array('enum(7.3,9.15.9=9.15.9 - без езици,11.12.0)', 'caption=Версия на Highlight.js->Версия'),
    );
    
    
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
}
