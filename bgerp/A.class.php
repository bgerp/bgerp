<?php



/**
 * CMS статии
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_A extends core_Mvc
{
    var $protectId = FALSE;

    function act_A()
    {
        return Request::forward(array('Ctr' => 'cms_Articles', 'Act' => 'Article'));
    }

    function act_G()
    {
        return Request::forward(array('Ctr' => 'eshop_Groups', 'Act' => 'Show'));
    }
    
    function act_P()
    {
        return Request::forward(array('Ctr' => 'eshop_Products', 'Act' => 'Show'));
    }

}
