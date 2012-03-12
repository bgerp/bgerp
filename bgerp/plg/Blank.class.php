<?php


/**
 * 
 */
defIfNot(BGERP_COMPANY_LOGO, 'bgerp/img/companyLogo.png');


/**
 *
 */
defIfNot(BGERP_COMPANY_LOGO_BG, 'bgerp/img/companyLogoBg.png');


/**
 * Добавя бланка в началото на документите, които се изпращат или принтират
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_plg_Blank extends core_Plugin
{
    

    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    function on_AfterRenderSingleLayout($mvc, $tpl, $data)
    {
        //Ако принтираме или пращаме документа
        if ((Mode::is('text', 'xhtml')) || (Mode::is('printing') )) {
            
            //Добавяме бланка в началото на документа
            $blank = new ET(getFileContent('/bgerp/tpl/Blank.shtml'));
            
            //Създаваме и заместваме логото на фирмата
            $logoPath = core_Lg::getCurrent() == 'bg' ? BGERP_COMPANY_LOGO_BG : BGERP_COMPANY_LOGO;
            $logo = "<img src=" . sbf($logoPath, '"', TRUE) . " alt='Лого'  width='750' height='100'>";

            $linkLogo = HT::createLink($logo, getBoot(TRUE), NULL, array('target' => '_blank'));
            $blank->replace($linkLogo, 'blankImage');
            
            //Създаваме и заместваме бар кода
            
            //Линк където ще сочи при натискане
            $qrLinkUrl = self::createQrLink($data->rec->containerId, '[#mid#]');
            $pixelPerPoint = 3;
            $outerFrame = 0;
            
            //Защитата, кода да не се използва от външни лица
            $salt = barcode_Qr::getProtectSalt($qrLinkUrl, $pixelPerPoint, $outerFrame);
            
            //Линк за създаване на бар код
            $qrImgUrl = toUrl(
                array(
                	'barcode_Qr', 
                	'generate', 
                	'text' => $qrLinkUrl, 
                	'pixelPerPoint' => $pixelPerPoint, 
                	'outerFrame' => $outerFrame, 
                	'protect' => $salt
                ), 
                'absolute'
            );

            //Създаваме линка към генериране на изображението
            $qrImg = "<img src='" . $qrImgUrl . "' alt='QR код'  width='100' height='100'>";
            
            //Задаваме изображението да е линк
            $qrLink = HT::createLink($qrImg, $qrLinkUrl, NULL, array('target' => '_blank'));
            
            //Заместваме стойностите в шаблона
            $blank->replace($qrLink, 'blankQr');
            
            //Заместваме placeholder' a бланк
            $tpl->replace($blank, 'blank');
        }
    }
    
    
    /**
     * Създаваме линк, където ще сочи QR кода при сканиране и/или натискане
     */
    static function createQrLink($cid, $mid)
    {
        $link = toUrl(array('D', 'S', 'cid' => $cid, 'mid' => $mid), 'absolute');
                
        return $link;
    }
}
