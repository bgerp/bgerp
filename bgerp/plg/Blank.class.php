<?php


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
            $logoPath = self::getCompanyLogoUrl();
            $logo = "<img src=" . $logoPath . " alt='Logo'  width='750' height='100'>";

            $blank->replace($logo, 'blankImage');
            
            // Дали режимът е печат?
            $isPrinting = Mode::is('printing');
            
            // ID на контейнера
            $cid = $data->rec->containerId;
            
            // URL за за src="..." атрибута, на <img> тага на QR баркода
            $qrImgSrc = toUrl(array('L', 'B', $cid, 'm' => doc_DocumentPlg::getMidPlace()), 'absolute');
             
            // Създаваме <img> елемент за QR баркода
            $qrImg = ht::createElement('img', array('alt' => 'QR code', 'width' => 100, 'height' => 100, 'src' => $qrImgSrc));
            
            // URL за линка, който стои под QR кода
            $qrLinkUrl = toUrl(array('L', 'S', $cid, 'm' => doc_DocumentPlg::getMidPlace()), 'absolute');

            // Под картинката с QR баркод, слагаме хипервръзка към документа
            $qrA = ht::createElement('a', array('target' => '_blank',  'href' => $qrLinkUrl), $qrImg);
            
            //Заместваме стойностите в шаблона
            $blank->replace($qrA, 'blankQr');
            
            //Заместваме placeholder' a бланк
            $tpl->replace($blank, 'blank');
        }
    }
    
    
    /**
     * Връща логото на нашата компания
     */
    static function getCompanyLogoUrl()
    {
        //Вземема конфигурационните константи
        $conf = core_Packs::getConfig('bgerp');
        
        //Ако езика е на български
        if (core_Lg::getCurrent() == 'bg') {
            
            //Ако не е дефинирана константата за българско лого
            if (!$conf->BGERP_COMPANY_LOGO) {
                //Логото на компанията по поразбиране (BG)
                $companyLogo = 'bgerp/img/companyLogo.png';
            }
        } else {
            
            //Ако не е дефинирана константата за английско лого
            if (!$conf->BGERP_COMPANY_LOGO_EN) {
                
                //Логото на компанията по поразбиране (EN)
                $companyLogo = 'bgerp/img/companyLogoEn.png';
            }
        }
        
        //Ако е дефинирана логото на компанията
        if ($companyLogo) {
            
            //Намираме абсолютния файл до файла
            $companyLogoPath = sbf($companyLogo, '"', TRUE);
        } else {
            
            //Ако сме дефинирали логото на компанията с fileHandler
            //Масив със стойности, необходими за създаване на thumbnail
            $attr = array('baseName' => 'companyLogo', 'isAbsolute' => TRUE, 'qt' => '"');
            
            //Размера на thumbnail изображението
            $size = array('750', '100');
            
            //Създаваме тумбнаил с параметрите
            $companyLogoPath = thumbnail_Thumbnail::getLink($conf->BGERP_COMPANY_LOGO, $size, $attr);
        }
        
        return $companyLogoPath;
    }
}
