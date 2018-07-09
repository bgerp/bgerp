<?php


/**
 * Клас 'page_PreText' - Шаблон за празна страница с подреден текст
 *
 * @category  ef
 * @package   page
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class page_PreText extends page_Empty
{
    /**
     * Генериране на изход, съдържащ само $content, без никакви доп. елементи с подреден текст
     */
    public function output($content = '', $place = 'PAGE_CONTENT')
    {
        $this->appendOnce('body{white-space: pre-wrap; width: auto; overflow: auto; background-color: transparent;}', 'STYLES');
        
        parent::output($content, $place);
    }
}
