<?php


defIfNot('DOMPDF_VER', '3.0');

/**
 * Дефинира име на папка в която ще се съхраняват временните данни данните
 */
defIfNot('DOMPDF_TEMP_DIR', EF_TEMP_PATH . "/dompdf");

/**
 * Клас 'hclean_HtmlPurifyPlg' - Изчистване на HTML полета
 *
 * Плъгин, който изчиства html полетата с hclean_Purifier
 *
 *
 * @category  all
 * @package   hclean
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class dompdf_Converter extends core_Manager
{
    
    
    /**
     * Изпълнява се след рендирането на input
     */
    function convert($html, $fileName, $bucketName)  
    {   
        // Зареждаме опаковката 
        $wrapperTpl = cls::get('tpl_PrintPage');
        
        // Изпращаме на изхода опаковано съдържанието
        $wrapperTpl->replace($html, 'PAGE_CONTENT');

        $html = $wrapperTpl->getContent();  
        $html = "\xEF\xBB\xBF" . $html; 
        
        defIfNot("DOMPDF_DPI", "120");
        defIfNot("DOMPDF_ENABLE_REMOTE", TRUE);

        require_once( __DIR__ . '/' . DOMPDF_VER . '/' . "dompdf_config.inc.php");
        
        do {
            // Път до временния HTML файл
            $pdfFile = $fileName . '_' . $i . '_.pdf';
            $pdfPath = DOMPDF_TEMP_DIR . '/' . $pdfFile;
            $i++;
        } while (file_exists($pdfPath));


        $dompdf = new DOMPDF(array());
        $dompdf->load_html($html);
        $dompdf->set_paper('A4');
        $dompdf->render();
        
        file_put_contents($pdfPath, $dompdf->output()); 
        
        //Качвания новосъздадения PDF файл
        $Fileman = cls::get('fileman_Files');
        $fh = $Fileman->addNewFile($pdfPath, $bucketName, $fileName);

        unlink($pdfPath);

        return $fh;
    }

    /**
     * Подготовка на временната директория, след инсталацията на пакета
     */
    function on_AfterSetupMVC($mvc, &$res)
    {
        //Създаваме рекурсивно папката
        $d = DOMPDF_TEMP_DIR;
        $caption = 'За временни файлове на DOMPDF';
        if(!is_dir($d)) {
            if(mkdir($d, 0777, TRUE)) {
                $msg = "<li style='color:green;'> Директорията <b>{$d}</b> е създадена ({$caption})";
            } else {
                $msg = "<li style='color:red;'> Директорията <b>{$d}</b> не може да бъде създадена ({$caption})";
            }
        } else {
            $msg = "<li> Директорията <b>{$d}</b> съществува от преди ({$caption})";
        }
        
        $res .= $msg;
    }

}