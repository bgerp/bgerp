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
            $logo = "<img src=" . sbf($logoPath, '"', TRUE) . " alt='Logo'  width='750' height='100'>";
            
            $blank->replace($logo, 'blankImage');
            
            // Дали режимът е печат?
            $isPrinting = Mode::is('printing');
            
            // ID на контейнера
            $cid = $data->rec->containerId;
            
            // URL за за src="..." атрибута, на <img> тага на QR баркода
            $qrImgSrc = new ET(toUrl(array('L', 'B', $cid), 'absolute') . '?m=[#mid#]');
             
            // Създаваме <img> елемент за QR баркода
            $qrImg = ht::createElement('img', array('alt' => 'QR code', 'width' => 100, 'height' => 100, 'src' => $qrImgSrc, FALSE, TRUE));
            
            // URL за линка, който стои под QR кода
            $qrLinkUrl = new ET(toUrl(array('L', 'S', $cid), 'absolute') . '?m=[#mid#]');

            // Под картинката с QR баркод, слагаме хипервръзка към документа
            $qrA = ht::createElement('a', array('target' => '_blank',  'href' => $qrLinkUrl), $qrImg, FALSE, TRUE);
            
            //Заместваме стойностите в шаблона
            $blank->replace($qrA, 'blankQr');
            
            //Заместваме placeholder' a бланк
            $tpl->replace($blank, 'blank');
        }
    }
}