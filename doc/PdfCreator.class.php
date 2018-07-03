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
    public $title = 'Генерирани PDF документи';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'doc_Wrapper, plg_Created, plg_RowTools';
    
    
    /**
     * Кой има право да го чете?
     */
    public $canRead = 'admin, ceo';
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'admin';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'admin, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin, ceo';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой има права за имейли-те?
     */
    public $canEmail = 'admin, ceo';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar', 'caption=Име,mandatory');
        $this->FLD('fileHnd', 'fileman_FileType(bucket=' . self::PDF_BUCKET . ')', 'caption=Файл,mandatory');
        $this->FLD('md5', 'varchar(32)', 'caption=MD5');
        
        $this->setDbUnique('md5');
    }
    
    
    /**
     * Създава pdf файл и връща манипулатора му
     */
    public static function convert($html, &$name)
    {
        // Шаблона
        $htmlET = $html;
        
        // Добавяме класа
        $html = "<div class='wide'>" . $html . '</div>';
        
        // Проверяваме дали файла със същото име съществува в кофата
        $md5 = md5($html);
        $fileHnd = self::fetchField("#md5='{$md5}'", 'fileHnd');
        if ($fileHnd && isDebug()) {
            doc_PdfCreator::delete("#fileHnd = '{$fileHnd}'");
            unset($fileHnd);
        }
        
        //Ако не съществува
        if (!$fileHnd) {
            $css = self::getCssStr($htmlET);
            
            $html = self::removeFormAttr($html);
            
            //Добавяме всички стилове inline
            $html = '<div id="begin">' . $html . '<div id="end">';
            
            // Инстанция на класа
            $CssToInlineInst = cls::get(csstoinline_Setup::get('CONVERTER_CLASS'));
            
            // Стартираме процеса
            $html = $CssToInlineInst->convert($html, $css);
            
            $html = str::cut($html, '<div id="begin">', '<div id="end">');
            
            $name = self::createPdfName($name);
            
            $PdfCreatorInst = cls::getInterface('doc_ConvertToPdfIntf', doc_Setup::get('BGERP_PDF_GENERATOR', true));
            
            // Емулираме xhtml режим
            Mode::push('text', 'xhtml');
            
            $jsArr = array();
            
            if ($htmlET instanceof core_ET) {
                // Вземаме всички javascript файлове, които ще се добавят
                $jsArr['JS'] = $htmlET->getArray('JS', false);
                
                // Вземаме всеки JQUERY код, който ще се добави
                $jsArr['JQUERY_CODE'] = $htmlET->getArray('JQUERY_CODE', false);
            }
            
            try {
                // Стартираме конвертирането
                $fileHnd = $PdfCreatorInst->convert($html, $name, self::PDF_BUCKET, $jsArr);
            } catch (core_exception_Expect $e) {
                
                // Връщаме предишната стойност
                Mode::pop('text');
                
                reportException($e);
                
                throw new $e($e->getMessage());
            }
            
            // Връщаме предишната стойност
            Mode::pop('text');
            
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
     * Проверява дали може да се направи конвертирането
     *
     * @return boolean
     */
    public static function canConvert()
    {
        try {
            $PdfCreatorInst = cls::getInterface('doc_ConvertToPdfIntf', doc_Setup::get('BGERP_PDF_GENERATOR', true));
            
            $res = $PdfCreatorInst->isEnabled();
        } catch (core_exception_Expect $e) {
            reportException($e);
            $res = false;
        }
        
        return $res;
    }
    
    
    /**
     * Връща всичкия css
     *
     * @param string|core_ET $html
     *
     * @return string
     */
    public static function getCssStr($html)
    {
        //Вземаме всичките css стилове
        $css = file_get_contents(sbf('css/common.css', '', true)) .
            "\n" . file_get_contents(sbf('css/Application.css', '', true));
        
        // Ако е инстанция на core_ET
        if ($html instanceof core_ET) {
        
            // Вземаме масива с всички чакащи CSS файлове
            $cssArr = $html->getArray('CSS', false);
            foreach ((array) $cssArr as $cssPath) {
                try {
        
                    // Опитваме се да вземаме съдържанието на CSS
                    $css .= "\n" . file_get_contents(sbf($cssPath, '', true));
                } catch (core_exception_Expect $e) {
        
                    // Ако възникне грешка, добавяме в лога
                    self::logErr("Не може да се взема CSS файла: {$cssPath}");
                }
            }
        
            // Вземаме всички стилове
            $styleArr = $html->getArray('STYLES', false);
            foreach ((array) $styleArr as $styles) {
                $css .= "\n" . $styles;
            }
        }
        
        $css .= "\n" . file_get_contents(sbf('css/email.css', '', true)) .
            "\n" . file_get_contents(sbf('css/pdf.css', '', true));
        
        return $css;
    }
    
    
    /**
     * Изчиства всикo което е между <form> ... </form>
     */
    public static function removeFormAttr($html)
    {
        // Шаблон за намиране на <form ... </form>
        $pattern = '/\<form.*\<\/form\>/is';
        
        // Премахваме всикo което е между <form> ... </form>
        $res = preg_replace_callback($pattern, array(get_called_class(), 'removeMatchedFormAttr'), $html);
        
        if (($res === null) || ($res === false)) {
            $res = $html;
        }
        
        return $res;
    }
    
    
    /**
     * Премахва form елементите, ако вътре няма нещо с клас `staticFormView`
     *
     * @param array $matches
     *
     * @return string
     */
    protected static function removeMatchedFormAttr($matches)
    {
        if ($matches[0]) {
            if (!preg_match('/class\s*=\s*(\'|\")staticFormView(\'|\")/i', $matches[0], $m)) {
                return '';
            }
        }
        
        return $matches[0];
    }
    
    
    /**
     * Преобразува името на файла да е с разширение .pdf
     */
    public static function createPdfName($name)
    {
        $name = mb_strtolower($name);
        
        //Проверява разширението дали е PDF
        if (($dotPos = mb_strrpos($name, '.')) !== false) {
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
    public static function on_AfterSetupMVC($mvc, &$res)
    {
        //Създаваме, кофа, където ще държим всички генерирани PDF файлове
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket(self::PDF_BUCKET, 'PDF-и на документи', null, '104857600', 'user', 'user');
    }
}
