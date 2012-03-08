<?php


/**
 * 
 */
defIfNot(BGERP_COMPANY_LOGO, 'bgerp/img/logo.jpg');


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
    function on_AfterRenderSingle($mvc, $tpl, $data)
    {
        //Ако принтираме или пращаме документа
        if ((Mode::is('text', 'xhtml')) || (Mode::is('printing') )) {
            
            //Добавяме бланка в началото на документа
            $blank = new ET(getFileContent('/bgerp/tpl/Blank.shtml'));
            
            //Създаваме и заместваме логото на фирмата
            $logo = "<img src=" . sbf(BGERP_COMPANY_LOGO, '"', TRUE) . " alt='Лого'>";
            $linkLogo = HT::createLink($logo, getBoot(TRUE), NULL, array('target' => '_blank'));
            $blank->replace($linkLogo, 'blankImage');
            
            //Създаваме и заместваме бар кода
            //Линк където ще сочи при натискане
            $qrLinkUrl = toUrl(array('D', 'S', 'cid' => $data->rec->containerId, 'mid' => '[#mid#]'), 'absolute');
            
            //Задаваме get параметрите да се кодират
//            Request::setProtected('mid,cid');

            //Линк за създаване на бар код
            $qrImgUrl = urlencode(toUrl(array('Qr', 'C', 'cid' => $data->rec->containerId, 'mid' => '[#mid#]'), 'absolute'));
            
            //Създаваме линка към генериране на изображението
            $qrImg = "<img src='" . $qrImgUrl . "' alt='QR код'>";
            
            //Задаваме изображението да е линк
            $qrLink = HT::createLink($qrImg, $qrLinkUrl, NULL, array('target' => '_blank'));
            
            //Заместваме стойностите в шаблона
            $blank->replace($qrLink, 'blankQr');
            
            //Заместваме placeholder' a бланк
            $tpl->replace($blank, 'blank');
        }
    }
}