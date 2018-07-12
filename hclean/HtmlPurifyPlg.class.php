<?php


/**
 * Клас 'hclean_HtmlPurifyPlg' - Изчистване на HTML полета
 *
 * Плъгин, който изчиства html полетата с hclean_Purifier
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
class hclean_HtmlPurifyPlg extends core_Plugin
{
    /**
     * Изпълнява се след рендирането на input
     */
    public function on_AfterFromVerbal($type, &$res, $value)
    {
        //Изчиства стойността против XSS атаки
        $res = hclean_Purifier::clean($res, 'UTF-8');
    }
}
