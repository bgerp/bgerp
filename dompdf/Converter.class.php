<?php



/**
 * Клас 'dompdf_Converter' - Конвертиране към PDF на HTML код, чрез DOMPDF
 *
 *
 * @category  vendors
 * @package   dompdf
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class dompdf_Converter extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'dompdf';
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_ConvertToPdfIntf';
    
    
    /**
     * Конвертира html към pdf файл
     * 
     * @param string $html - HTML стинга, който ще се конвертира
     * @param string $fileName - Името на изходния pdf файл
     * @param string $bucketName - Името на кофата, където ще се записват данните
     * @param array $jsArr - Масив с JS и JQUERY_CODE
     *
     * @return string $fh - Файлов манипулатор на новосъздадения pdf файл
     */
    static function convert($html, $fileName, $bucketName, $jsArr=array())
    {
    	$conf = core_Packs::getConfig('dompdf');
    	
        // Зареждаме опаковката 
        $wrapperTpl = cls::get('page_Print');
        
        // Вкарва съдържанието в опаковката
        $wrapperTpl->replace($html, 'PAGE_CONTENT');
        
        $html = $wrapperTpl->getContent();
        $html = "\xEF\xBB\xBF" . $html;
        
        
        require_once(__DIR__ . '/' . $conf->DOMPDF_VER . '/' . "dompdf_config.inc.php");
        
        do {
            // Път до временния HTML файл
            $pdfFile = $fileName . '_' . $i . '_.pdf';
            $pdfPath = $conf->DOMPDF_TEMP_DIR . '/' . $pdfFile;
            $i++;
        } while (file_exists($pdfPath));
        
        $dompdf = new DOMPDF(array());
        $dompdf->load_html($html, 'UTF-8');
        $dompdf->set_paper('A4');
        $dompdf->render();
         
        file_put_contents($pdfPath, $dompdf->output());
        
        // Записва новосъздадения PDF файл в посочената кофа
        $Fileman = cls::get('fileman_Files');
        $fh = $Fileman->addNewFile($pdfPath, $bucketName, $fileName);
        
        unlink($pdfPath);
        
        return $fh;
    }
    
    
    /**
     * Подготовка на временната директория, след инсталацията на пакета
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
    	$conf = core_Packs::getConfig('dompdf');
    	
        //Създаваме рекурсивно папката
        $d = $conf->DOMPDF_TEMP_DIR;
        $caption = 'За временни файлове на DOMPDF';
        
        if(!is_dir($d)) {
            if(mkdir($d, 0777, TRUE)) {
                $msg = "<li style='color:green;'> Директорията <b>{$d}</b> е създадена ({$caption})</li>";
            } else {
                $msg = "<li style='color:red;'> Директорията <b>{$d}</b> не може да бъде създадена ({$caption})</li>";
            }
        } else {
            $msg = "<li> Директорията <b>{$d}</b> съществува от преди ({$caption})</li>";
        }
        
        $res .= $msg;
    }
}