<?php



/**
 * @todo Чака за документация...
 */
defIfNot(BGERP_COMPANY_LOGO, 'bgerp/img/companyLogo.png');


/**
 * @todo Чака за документация...
 */
defIfNot(BGERP_COMPANY_LOGO_BG, 'bgerp/img/companyLogoBg.png');


/**
 * Добавя бланка в началото на документите, които се изпращат или принтират
 *
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
    function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
        //Ако принтираме или пращаме документа
        if ((Mode::is('text', 'xhtml')) || (Mode::is('printing'))) {
            
            //Добавяме бланка в началото на документа
            $blank = new ET(getFileContent('/bgerp/tpl/Blank.shtml'));
            
            //Създаваме и заместваме логото на фирмата
            $logoPath = core_Lg::getCurrent() == 'bg' ? BGERP_COMPANY_LOGO_BG : BGERP_COMPANY_LOGO;
            $logo = "<img src=" . sbf($logoPath, '"', TRUE) . " alt='Лого'  width='750' height='100'>";
            
            $blank->replace($logo, 'blankImage');
            
            $isPrinting = Mode::is('printing');
            
            $midParam = 'mid=[#mid#]';
            if ($isPrinting) {
                // Ако сме в режим принтиране използваме pid вместо mid
                $midParam = 'pid=[#pid#]';
            }
            
            $midParam = new ET($midParam);
            
            //Линк където ще сочи при натискане
            $qrLinkUrl = self::createQrLink($data->rec->containerId);
            $pixelPerPoint = 3;
            $outerFrame = 0;
            
            //Защитата, кода да не се използва от външни лица
            $salt = barcode_Qr::getProtectSalt($qrLinkUrl, $pixelPerPoint, $outerFrame);
            
            //Линк за създаване на бар код
            $qrImgUrl = toUrl(
                array(
                    'barcode_Qr',
                    'generate',
                    'pixelPerPoint' => $pixelPerPoint,
                    'outerFrame' => $outerFrame,
                    'protect' => $salt,
                    'text' => $qrLinkUrl, // text трябва да е последно
                ),
                'absolute'
            );
            
            //За да работи emogrifier коректно
//            $qrImgUrl = htmlentities($qrImgUrl);
            
            $qrLink = new ET('
                <a target="_blank" href="[#1#]&[#3#]">
                    <img src="[#2#]%26[#3#]" alt="QR код"  width="100" height="100" />
                </a>',
                $qrLinkUrl,
                $qrImgUrl,
                $midParam
            );
            
            //За да работи emogrifier коректно
            $qrLink = str_replace('&', htmlentities('&'), $qrLink);
            
            //Заместваме стойностите в шаблона
            $blank->replace($qrLink, 'blankQr');
            
            //Заместваме placeholder' a бланк
            $tpl->replace($blank, 'blank');
        }
    }
    
    
    /**
     * Създаваме линк, където ще сочи QR кода при сканиране и/или натискане
     */
    static function createQrLink($cid)
    {
        $link = toUrl(array('D', 'S', 'cid' => $cid, ), 'absolute');
        
        return $link;
    }
}