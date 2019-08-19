<?php


/**
 * CMS статии
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgerp_A extends core_Mvc
{
    /**
     * Да не се кодират id-тата
     */
    public $protectId = false;
    
    
    /**
     * Създава пряк път до публичните статии
     */
    public function act_A()
    {
        return Request::forward(array('Ctr' => 'cms_Articles', 'Act' => 'Article'));
    }
    
    
    /**
     * Създава пряк път до групите в онлайн магазина
     */
    public function act_G()
    {
        return Request::forward(array('Ctr' => 'eshop_Groups', 'Act' => 'Show'));
    }
    
    
    /**
     * Създава пряк път до продукти в онлайн магазина
     */
    public function act_P()
    {
        return Request::forward(array('Ctr' => 'eshop_Products', 'Act' => 'Show'));
    }
    
    
    /**
     * Създава пряк път до статиите в блога
     */
    public function act_B()
    {
        return Request::forward(array('Ctr' => 'blogm_Articles', 'Act' => 'Article'));
    }
    
    
    /**
     * Създава watchpoint
     */
    public function act_wp()
    {
        wp(Request::$vars);
        
        return array();
    }
    
    
    /**
     * Връща кратко URL към съдържание на статия
     */
    public static function getShortUrl($url)
    {
        if ($url['Act'] == 'A') {
            $url['Ctr'] = 'cms_Articles';
            $url['Act'] = 'Article';
            $url = cms_Articles::getShortUrl($url);
        } elseif ($url['Act'] == 'G') {
            $url['Ctr'] = 'eshop_Groups';
            $url['Act'] = 'Show';
            $url = eshop_Groups::getShortUrl($url);
        } elseif ($url['Act'] == 'P') {
            $url['Ctr'] = 'eshop_Products';
            $url['Act'] = 'Show';
            $url = eshop_Products::getShortUrl($url);
        } elseif ($url['Act'] == 'B') {
            $url['Ctr'] = 'blogm_Articles';
            $url['Act'] = 'Article';
            $url = blogm_Articles::getShortUrl($url);
        }
        
        return $url;
    }
    
    
    /**
     * Създава пряк път до статиите в блога
     */
    public function act_Default()
    {
        return Request::forward(array('Index'));
    }
}
