<?php


/**
 * Юнит тестове за email_Mime
 *
 *
 * @category  bgerp
 * @package   email
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see       https://github.com/bgerp/bgerp/issues/115
 */
class email_tests_Mime extends unit_Class
{
    /**
     * Преобразуване от вътрешно представяне към вербална стойност
     */
    public function test_parseHeaders()
    {
        $h ="Subject:
 =?iso-2022-jp?B?GyRCJzAnZidWJ2InZCdRGyhCIBskQidZJ1EbKEIgGyRCJ18nUSdbGyhC?=
 =?iso-2022-jp?B?GyRCJ10nYCdfJ2AnUydaGyhCIBskQidkJ2AnYidSJ1onaSdcJ1obKEI=?=
 =?iso-2022-jp?B?IC0gGyRCJ2MnXidxJ18nURsoQiAbJEInXydRGyhCIBskQidTJ1obKEI=?=
 =?iso-2022-jp?B?GyRCJ1knWidxGyhC?=";
        
        $mime = cls::get('email_Mime');
        
        $res = $mime->parseHeaders($h);
         
        UT::expectEqual($mime::decodeHeader(trim($res['subject'][0])), "Оферта за найлонови торбички - смяна на визия");
    }
    
    
   
}
