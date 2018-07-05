<?php

/**
 * Клас 'page_Dialog' - Страница за диалогови прозорци
 *
 * @category  ef
 * @package   page
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class page_Dialog extends page_Html
{
    
    
    /**
     * Конструктор
     */
    public function __construct()
    {
        parent::__construct();
        $this->replace('UTF-8', 'ENCODING');
        $this->push('css/common.css', 'CSS');
        $this->push('css/dialog.css', 'CSS');
        $this->push('css/default-theme.css', 'CSS');
        jquery_Jquery::enable($this);
        $this->push('js/efCommon.js', 'JS');
    }
}
