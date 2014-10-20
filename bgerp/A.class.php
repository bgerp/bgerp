<?php



/**
 * CMS статии
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_A extends core_Mvc
{
    /**
     * Да не се кодират id-тата
     */
    var $protectId = FALSE;
    
    /**
     * Създава пряк път до публичните статии
     */
    function act_A()
    {
        return Request::forward(array('Ctr' => 'cms_Articles', 'Act' => 'Article'));
    }
    
    /**
     * Създава пряк път до групите в онлайн магазина
     */
    function act_G()
    {
        return Request::forward(array('Ctr' => 'eshop_Groups', 'Act' => 'Show'));
    }
    
    /**
     * Създава пряк път до продукти в онлайн магазина
     */
    function act_P()
    {
        return Request::forward(array('Ctr' => 'eshop_Products', 'Act' => 'Show'));
    }
    
    /**
     * Създава пряк път до статиите в блога
     */
    function act_B()
    {
        return Request::forward(array('Ctr' => 'blogm_Articles', 'Act' => 'Article'));
    }
}
