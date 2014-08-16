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
    function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        //Ако принтираме или пращаме документа
        if ((Mode::is('text', 'xhtml')) || (Mode::is('printing'))) {
            
            //Добавяме бланка в началото на документа
            $blank = new ET(getFileContent('/bgerp/tpl/Blank.shtml'));
            
            //Създаваме и заместваме логото на фирмата
            $logoPath = self::getCompanyLogoUrl();
            $logo = "<img src=" . $logoPath . " alt='Logo'  width='750' height='87'>";

            $blank->replace($logo, 'blankImage');
            
            // Дали режимът е печат?
            $isPrinting = Mode::is('printing');
            
            // ID на контейнера
            $cid = $data->rec->containerId;
            
            // Ако е подаден __MID__, да се използва, вместо плейсхолдера
            if (!($mid = $data->__MID__)) {
                $mid = doc_DocumentPlg::getMidPlace();
            }
            
            // URL за за src="..." атрибута, на <img> тага на QR баркода
            $qrImgSrc = toUrl(array('L', 'B', $cid, 'm' => $mid), 'absolute', TRUE, array('m'));
             
            // Създаваме <img> елемент за QR баркода
            $qrImg = ht::createElement('img', array('alt' => 'View doc', 'width' => 87, 'height' => 87, 'src' => $qrImgSrc));
            
            // URL за линка, който стои под QR кода
            $qrLinkUrl = toUrl(array('L', 'S', $cid, 'm' => $mid), 'absolute', TRUE, array('m'));

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
        // Езика на писмото
        $lg = core_Lg::getCurrent();
        
        // Прихващаме грешката
        try {
        
            // Ако езика не е английски
            if ($lg != 'en') {
                
                // Вземаме логото на потребителя
                $companyLogoFh = crm_Personalization::getLogo();    
            } else {
                
                // Вземамем логото на потребителя на ЕН
                $companyLogoFh = crm_Personalization::getLogo(FALSE, TRUE);
            }
        
        } catch (Exception $e) {}
        
        // Ако няма лого на потребителя
        if (!$companyLogoFh) {
            
            // Вземема конфигурационните константи
            $conf = core_Packs::getConfig('bgerp');   

            // Ако езика не е английски
            if ($lg != 'en') {
                
                // Ако не е дефинирана константата за българско лого
                if (!$companyLogoFh = $conf->BGERP_COMPANY_LOGO) {
                    
                    // Логото на компанията по поразбиране (BG)
                    $companyLogo = 'bgerp/img/companyLogo.png';
                }    
            } else {
                // Ако не е дефинирана константата за английско лого
                if (!$companyLogoFh = $conf->BGERP_COMPANY_LOGO_EN) {
                    
                    // Логото на компанията по поразбиране (EN)
                    $companyLogo = 'bgerp/img/companyLogoEn.png';
                }
            }
        }
        
        // Ако има открито лог на потребителя
        if ($companyLogoFh) {
            
            // Ако сме дефинирали логото на компанията с fileHandler
            // Масив със стойности, необходими за създаване на thumbnail
            $attr = array('baseName' => 'companyLogo', 'isAbsolute' => TRUE, 'qt' => '"');
            
            // Размера на thumbnail изображението
            $size = array('750', '87');
            
            // Създаваме тумбнаил с параметрите
            $companyLogoPath = thumbnail_Thumbnail::getLink($companyLogoFh, $size, $attr);
        } elseif ($companyLogo) {
            
            // Намираме абсолютния път до файла
            $companyLogoPath = sbf($companyLogo, '"', TRUE);
        } 
        
        // За генериране на PDF логото трябва да е с абсолютен път
        
        return $companyLogoPath;
    }
}
