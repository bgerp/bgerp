<?php



/**
 * Клас 'page_Empty' - Шаблон за празна страница
 *
 * Файлът може да се подмени с друг
 *
 *
 * @category  ef
 * @package   page
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class page_Empty extends page_Html
{
    
    
    /**
     * Генериране на изход, съдържащ само $content, без никакви доп. елементи
     */
    function output($content)
    {
        $this->replace("UTF-8", 'ENCODING');
        
        $this->push(array(
                Mode::is('screenMode', 'narrow') ? "css/narrowCommon.css" : 'css/wideCommon.css',
                Mode::is('screenMode', 'narrow') ? "css/narrowApplication.css" : 'css/wideApplication.css'
            ), 'CSS');
        $this->push('js/efCommon.js', 'JS');
        
        $this->appendOnce("\n<link  rel=\"shortcut icon\" href=" . sbf("img/favicon.ico") . ">", "HEAD");
        
        parent::output($content, 'PAGE_CONTENT');
    }
}