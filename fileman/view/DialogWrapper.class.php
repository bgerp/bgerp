<?php


/**
 * Клас 'fileman_view_DialogWrapper' -
 *
 * @category  vendors
 * @package   fileman
 *
 * @author    Milen Georgiev <milen@download.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class fileman_view_DialogWrapper extends page_Html
{
    /**
     * Конструктор
     */
    public function __construct()
    {
        parent::__construct();
        $this->replace('UTF-8', 'ENCODING');
        $this->push('fileman/css/default.css', 'CSS');
        $this->push('css/common.css', 'CSS');
        jquery_Jquery::enable($this);
        $this->push('js/efCommon.js', 'JS');
    }
}
