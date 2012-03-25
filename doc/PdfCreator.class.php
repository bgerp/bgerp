<?php


/**
 * Кофата по подразбиране за генерирани pdf' и
 */
defIfNot(BGERP_PDF_BUCKET, 'pdf');


/**
 * Генериране на PDF файлове от HTML файл чрез web kit
 * 
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class doc_PdfCreator extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Генерирани PDF документи";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'doc_Wrapper, plg_Created';
    
    
    /**
     * Кой има право да го чете?
     */
    var $canRead = 'admin, ceo';
    
    
    /**
     * Кой има право да го променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin, ceo';
    
    
    /**
     * Кой има право да изтрива?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой има права за имейли-те?
     */
    var $canEmail = 'admin, ceo';

    
    /**
     * 
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Име,mandatory');
        $this->FLD('fileHnd', 'varchar(8)', 'caption=Файл,mandatory');
        $this->FLD('md5', 'varchar(32)', 'caption=MD5');

        $this->setDbUnique('md5');
    }
    
    
    /**
     * Създава pdf файл и връща манипулатора му
     */
    static function convert($html, &$name)
    {
        $name = self::createPdfName($name);
        
        $md5 = md5($html);

        //Проверяваме дали файла със същото име съществува в кофата
        $fileHnd = doc_PdfCreator::fetchField("#md5='{$md5}'", 'fileHnd');
        
        //Ако не съществува
        if (!$fileHnd) {
            //Вземаме fileHandler' а на новосъздадения pdf
            $fileHnd = dompdf_Converter::convert($html, $name, BGERP_PDF_BUCKET);
            
            //Записваме данните за текущия файл
            $rec = new stdClass();
            $rec->name = $name;
            $rec->md5 = $md5;
            $rec->fileHnd = $fileHnd;
            
            doc_PdfCreator::save($rec);
        }
        
        return $fileHnd;
    }
    
    
    /**
     * Преобразува името на файла да е с разширение .pdf
     */
    static function createPdfName($name)
    {
        $name = mb_strtolower($name);
        
        //Проверява разширението дали е PDF
        if (($dotPos = mb_strrpos($name, '.')) !== FALSE) {
            //Вземаме разширението
            $ext = mb_strtolower(mb_substr($name, $dotPos + 1));
            
            //Ако разширението е pdf връщаме
            if ($ext == 'pdf') {
                
                return $name;
            }
        }
        
        $name = $name . '.pdf';
        
        return $name;
    }  
    
    
    /**
     * 
     */
    function on_AfterSetupMVC($mvc, &$res)
    {
        cls::get('webkittopdf_Converter');
        
        if (!is_file(WEBKIT_TO_PDF_BIN)) {
            $res .= '<li><font color=red>' . tr('Липсва програмата') . ' "' . WEBKIT_TO_PDF_BIN . '</font>';
        }
        
        //инсталиране на кофата
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket(BGERP_PDF_BUCKET, 'Генерирани PDF файлове', NULL, '300 MB', 'user', 'user');
    }
}