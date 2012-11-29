<?php


/**
 * Родителски клас на всички WEB документи. Съдържа методите по подразбиране.
 *
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Webpage extends fileman_webdrv_Generic
{
    
    
	/**
     * Връща всички табове, които ги има за съответния файл
     * 
     * @param object $fRec - Записите за файла
     * 
     * @return array
     * 
     * @Override
     * @see fileman_webdrv_Generic::getTabs
     */
    static function getTabs($fRec)
    {
        // Вземаме табовете от родителя
        $tabsArr = parent::getTabs($fRec);
        
        // Url към HTML файла
        $htmlPartUrl = static::getHtmlPart($fRec);
        
        // Текстовата част
        $textPart = static::getRichTextPart($fRec);
        
        // Вземаме съдържанието на таба за HTML
        $htmlPart = static::getHtmlTabTpl($htmlPartUrl);
        
        // Подготвяме табовете
        
        // Таб за информация
        $tabsArr['html'] = (object) 
			array(
				'title' => 'HTML',
                'html'  => $htmlPart,
				'order' => 1,
			);
        
        // Таб за текстовата част
        $tabsArr['text'] = (object) 
			array(
				'title' => 'Текст',
				'html'  => "<div class='webdrvTabBody'><fieldset class='webdrvFieldset'><legend>Текст</legend>{$textPart}</fieldset></div>",
				'order' => 2,
			);
			
        return $tabsArr;
    }
    
    
    /**
     * Връща HTML частта от файла
     * 
     * @param object $emlRec - Данните за имейла
     * 
     * return url - Връща URL, което да се визуализра
     */
    static function getHtmlPart($fRec)
    {
        
        return fileman_Download::getDownloadUrl($fRec->fileHnd);
    }
    
    
     /**
     * Връща текстовата част (richEdit) на файла
     * 
     * @param object $emlRec - Данните за имейла
     * 
     * return string - Текстовата част
     */
    static function getRichTextPart($fRec)
    {    
        // Вземаме съдържанието на файла
        $content = fileman_Files::getContent($fRec->fileHnd);
        
        // Инстанция на richtext типа
        $richText = cls::get('type_Richtext');

        // Вземаме текстовата част в richEdit тип
        $textPart = $richText->toVerbal(html2text_Converter::toRichText($content));
        
        return $textPart;
    }
    
    
     /**
     * Връща текстовата част на файла
     * 
     * @param object $emlRec - Данните за имейла
     * 
     * return string - Текстовата част
     */
    static function getTextPart($fRec)
    {
        // Съдържанието на файла
        $content = fileman_Files::getContent($fRec->fileHnd);
        
        // Интанция към класа
        $html2text = cls::get('html2text_Html2Text');
        
        // Сетваме текстовата чат
        $html2text->set($content);
        
        // Вземаме текстовата част
        $textPart = $html2text->get_text();
        
        return $textPart;
    }

    
    /**
     * Връща информация за съответния файл и съответния тип
     * 
     * @param fileHandler $fileHnd - Манипулатор на файла
     * @param string $type - Типа на файла
     * 
     * @return mixed $content - Десериализирания стринг
     */
    static function getInfoContentByFh($fileHnd, $type)
    {
        // Записите за съответния файл
        $fRec = fileman_Files::fetchByFh($fileHnd);
        
        // В зависимост от типа пускаме различни методи
        switch ($type) {
            
            // Ако ни трябва текстовата част
            case 'text':
                $content = static::getTextPart($fRec);
            break;
            
            default:
                
                // Ако типа не съществува, връщаме FALSE
                return FALSE;
            break;
        }
        
        return $content;
    }
}