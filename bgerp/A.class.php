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
    
    function act_B()
    {
        return Request::forward(array('Ctr' => 'blogm_Articles', 'Act' => 'Article'));
    }


    /**
     * Връща кратко URL към съдържание, което се линква чрез този редиректор
     */
    function getShortUrl($url)
    {
        $a = strtoupper($url['Act']);

        if($a == 'A') {
            $cls = 'cms_Articles';
        } elseif($a == 'G') {
            $cls = 'eshop_Groups';
        } elseif($a == 'P') {
            $cls = 'eshop_Products';
        } elseif($a == 'B') {
            $cls = 'blogm_Articles';
        }

        $vid = urldecode($url['id']);

        if($vid && $cls) {
            $id = cms_VerbalId::fetchId($vid, $cls); 

            if(!$id) {
                $id = $cls::fetchField(array("#vid = '[#1#]'", $vid), 'id');
            }
            
            $url['id'] = $id;            
        }

        unset($url['PU']);

        return $url;
    }


}
