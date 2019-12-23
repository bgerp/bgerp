<?php


/**
 * Добавя бланка в началото на документите, които се изпращат или принтират
 *
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
class bgerp_plg_Blank extends core_Plugin
{
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        if (Mode::is('noBlank', true)) {
            
            return;
        }
        
        //Ако принтираме или пращаме документа
        if ((Mode::is('text', 'xhtml')) || (Mode::is('printing'))) {
            
            //Добавяме бланка в началото на документа
            $blank = new ET(getFileContent('/bgerp/tpl/Blank.shtml'));
            
            //Създаваме и заместваме логото на фирмата
            $logoPath = self::getCompanyLogoUrl();
            $logo = "<img src='" . $logoPath . "' alt='Logo'  width='750' height='87'>";
            
            $blank->replace($logo, 'blankImage');
            
            // Подготовка на QR кода
            $qrA = self::getQrCode($data->rec->containerId, $data->__MID__);
            
            //Заместваме стойностите в шаблона
            $blank->replace($qrA, 'blankQr');
            
            //Заместваме placeholder' a бланк
            $tpl->replace($blank, 'blank');
        }
    }
    
    
    /**
     * Връща QR кода на бланката на документа
     *
     * @param int         $cid
     * @param string|null $mid
     * @param int         $width
     * @param int         $height
     *
     * @return core_ET
     */
    public static function getQrCode($cid, $mid = null, $width = 87, $height = 87)
    {
        // Ако е подаден __MID__, да се използва, вместо плейсхолдера
        if (!$mid) {
            $mid = doc_DocumentPlg::getMidPlace();
        }
        
        // URL за за src="..." атрибута, на <img> тага на QR баркода
        $qrImgSrc = toUrl(array('L', 'B', $cid, 'm' => $mid), 'absolute', true, array('m'));
        
        // Създаваме <img> елемент за QR баркода
        $qrImg = ht::createElement('img', array('alt' => 'View doc', 'width' => 87, 'height' => 87, 'src' => $qrImgSrc));
        
        // URL за линка, който стои под QR кода
        $qrLinkUrl = self::getUrlForShow($cid, $mid);
        
        // Под картинката с QR баркод, слагаме хипервръзка към документа
        $res = ht::createElement('a', array('target' => '_blank',  'href' => $qrLinkUrl), $qrImg);
        
        return $res;
    }
    
    
    /**
     * Връща линк за показване на документа във външната част
     *
     * @param int    $cid
     * @param string $mid
     *
     * @return string
     */
    public static function getUrlForShow($cid, $mid)
    {
        $url = toUrl(array('L', 'S', $cid, 'm' => $mid), 'absolute', true, array('m'));
        
        return $url;
    }
    
    
    /**
     * Връща URL логото на нашата компания
     *
     * @return string
     */
    public static function getCompanyLogoUrl()
    {
        $thumb = self::getCompanyLogoUrlThumbObj();
        $companyLogoUrl = $thumb->getUrl();
        
        return $companyLogoUrl;
    }
    
    
    /**
     * Връща логото на нашата компания
     *
     * @return string
     */
    public static function getCompanyLogoThumbPath()
    {
        $thumb = self::getCompanyLogoUrlThumbObj();
        $companyLogoPath = $thumb->getThumbPath();
        
        return $companyLogoPath;
    }
    
    
    /**
     * Връща логото на нашата компания
     *
     * @return thumb_Img
     */
    protected static function getCompanyLogoUrlThumbObj()
    {
        // Езика на писмото
        $lg = core_Lg::getCurrent();
        
        // Вземема конфигурационните константи
        $conf = core_Packs::getConfig('bgerp');
        
        // Вземам бланката в зависимост от езика
        $companyLogo = core_Packs::getConfigValue($conf, 'BGERP_COMPANY_LOGO');
        $filemanInst = cls::get('fileman_Files');
        
        $sourceType = 'path';

        // Проверяваме дали е манипулатор на файл
        if ($companyLogo && (strlen($companyLogo) == fileman_Setup::get('HANDLER_LEN')) && ($filemanInst->fetchByFh($companyLogo))) {
            $sourceType = 'fileman';        

        } else {
            
            // Ако не е зададено логото
            if (!$companyLogo) {
                if ($lg == 'bg') {
                    $companyLogo = 'bgerp/img/companyLogo.png';
                } else {
                    $companyLogo = 'bgerp/img/companyLogoEn.png';
                }
            }
            
            // Ако не е манипулатор, очакваме да е път
            $companyLogo = core_App::getFullPath($companyLogo);
            
            // Ако логото не се взема от частния пакет или няма частен пакет
            // Използваме генерираното лого от SVG файла
            if (!defined('EF_PRIVATE_PATH') || (strpos($companyLogo, EF_PRIVATE_PATH) !== 0)) {
                $logoFromSvg = core_Packs::getConfigValue($conf, 'BGERP_COMPANY_LOGO_SVG');
                if (trim($logoFromSvg)) {
                    $companyLogo = $logoFromSvg;
                    $sourceType = 'fileman';
                }
            }
        }
        
        $isAbsolute = (boolean) Mode::is('text', 'xhtml');
        
        // Създаваме thumbnail с определени размери
        $thumb = new thumb_Img(array($companyLogo, 750, 87, $sourceType, 'isAbsolute' => $isAbsolute, 'mode' => 'small-no-change', 'verbalName' => 'companyLog'));
        
        return $thumb;
    }
}
