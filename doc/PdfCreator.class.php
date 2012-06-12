<?php
 
/**
 * Генериране на PDF файлове от HTML файл чрез web kit
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_PdfCreator extends core_Manager
{
    
    const PDF_BUCKET = 'pdf';
    
    /**
     * Заглавие
     */
    var $title = "Генерирани PDF документи";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'doc_Wrapper, plg_Created, plg_RowTools';
    
    
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
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Име,mandatory');
        $this->FLD('fileHnd', 'fileman_FileType(bucket=' . self::PDF_BUCKET . ')', 'caption=Файл,mandatory');
        $this->FLD('md5', 'varchar(32)', 'caption=MD5');
        
        $this->setDbUnique('md5');
    }
    
    
    /**
     * Създава pdf файл и връща манипулатора му
     */
    static function convert($html, &$name)
    {
    	$conf = core_Packs::getConfig('doc');
        
        $md5 = md5($html);
        
        //Проверяваме дали файла със същото име съществува в кофата
        $fileHnd = doc_PdfCreator::fetchField("#md5='{$md5}'", 'fileHnd');
        
        //Ако не съществува
        if (!$fileHnd) {

            //Вземаме всичките css стилове
            $css = getFileContent('css/wideCommon.css') .
                "\n" . getFileContent('css/wideApplication.css') . 
                "\n" . getFileContent('css/email.css') . 
                "\n" . getFileContent('css/pdf.css');
            
            $html = self::removeFormAttr($html);
            
            //Добавяме всички стилове inline
            $html = '<div id="begin">' . $html . '<div id="end">';
            $html = csstoinline_Emogrifier::convert($html, $css); 
            $html = str::cut($html, '<div id="begin">', '<div id="end">');
            
            $name = self::createPdfName($name);
            
            // Генерираме PDF и му вземаме файловия манипулатор
            if($conf->BGERP_PDF_GENERATOR == 'dompdf') {
                $fileHnd = dompdf_Converter::convert($html, $name, self::PDF_BUCKET);
            } elseif($conf->BGERP_PDF_GENERATOR == 'webkittopdf') {
                $fileHnd = webkittopdf_Converter::convert($html, $name, self::PDF_BUCKET);
            } else {
                expect(FALSE, $conf->BGERP_PDF_GENERATOR);
            }
            
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
     * След началното установяване на този мениджър, ако е зададено -
     * той сетъпва външния пакет, чрез който ще се генерират pdf-те
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
    	$conf = core_Packs::getConfig('doc');
        
        if($conf->BGERP_PDF_GENERATOR) {
            $Packs = cls::get('core_Packs');
            $res .= $Packs->setupPack($conf->BGERP_PDF_GENERATOR);
        }
        
        //Създаваме, кофа, където ще държим всички прикачени файлове на blast имейлите
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket(self::PDF_BUCKET, 'PDF-и на документи', NULL, '104857600', 'user', 'user');
    }
    
    
	/**
     * Изчиства всикo което е между <form> ... </form>
     */
    static function removeFormAttr($html)
    {
        //Шаблон за намиране на <form ... </form>
        $pattern = '/\<form.*\<\/form\>/is';
        
        //Премахваме всикo което е между <form> ... </form>
        $html = preg_replace($pattern, '', $html);

        return $html;
    }
}