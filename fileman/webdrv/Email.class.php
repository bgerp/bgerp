<?php


/**
 * Родителски клас на всички имейл документи. Съдържа методите по подразбиране.
 *
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Email extends fileman_webdrv_Generic
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
        
        // Инстанция на класа
        $mime = new email_Mime();
        
        // Вземаме съдържанието на eml файла
        $source = static::getSource($fRec);
        
        // Ескейпваме съдържанието (за визуализиране)
        $sourceEsc = type_Varchar::escape($source);
        
        // Очакваме да няма проблем при парсирането
        expect($emlRec = $mime->getEmail($source));

        // Променяме Id' то на EML и HTML файла
        static::changeEmlAndHtmlFileId($emlRec);
        
        // Вземаме текстовата част
        $textPart = static::getTextPart($emlRec);
        
        // Вземаме HTML частта
        $htmlPart = static::getHtmlPart($emlRec);
        
        // Вземаме хедърите
        $headersArr = static::getHeaders($mime, $emlRec);
        $headersStr = type_Varchar::escape($headersArr['string']);
        
        // Вземаме линковете към файловете
        $filesStr = static::getFiles($emlRec);
       
        // Подготвяме табовете
        
        // Таб за информация
        $tabsArr['html'] = (object) 
			array(
				'title' => 'HTML',
				'html'  => "<div class='webdrvIframe'> {$htmlPart} </div>",
				'order' => 1,
			);
        
        // Таб за текстовата част
        $tabsArr['text'] = (object) 
			array(
				'title' => 'Текст',
				'html'  => "<div class='webdrvIframe' style='white-space:pre-line;'> {$textPart} </div>",
				'order' => 2,
			);

        // Таб за преглед
		$tabsArr['files'] = (object) 
			array(
				'title'   => 'Файлове',
				'html'    => "<div class='webdrvIframe' style='white-space:pre-line;'> {$filesStr} </div>",
				'preview' => 3,
			);
			
		// Таб за хедърите
		$tabsArr['headers'] = (object) 
			array(
				'title'   => 'Хедъри',
				'html'    => "<div class='webdrvIframe' style='white-space:pre-wrap;'> {$headersStr} </div>",
				'preview' => 4,
			);
			
        // Таб за сорса
        $tabsArr['source'] = (object) 
			array(
				'title'   => 'Сорс',
				'html'    => "<div class='webdrvIframe' style='white-space:pre-wrap;'> {$sourceEsc} </div>",
				'preview' => 5,
			);
			
        return $tabsArr;
    }

    
    /**
     * Намира и връща соурса на файла
     * 
     * @param fileman_Files $fRec - Обект с данните за съответния файл
     * 
     * @return string - Сорса на EML файла
     */
    static function getSource($fRec)
    {
        // Връщаме соурса на файла
        return fileman_Files::getContent($fRec->fileHnd);
    }
    
    
    /**
     * Връща текстовата част от файла
     * 
     * @param object $emlRec - Данните за имейла
     * 
     * return string - Текстовата част
     */
    static function getTextPart($emlRec)
    {
        return $emlRec->textPart;
    }
    
    
    /**
     * Връща HTML частта от файла
     * 
     * @param object $emlRec - Данните за имейла
     * 
     * return string - HTML частта на файла
     */
    static function getHtmlPart($emlRec)
    {
        // Манипулатора на html файла
        $htmlFileHnd = fileman_Files::fetchField($emlRec->htmlFile, 'fileHnd');
        
        // Вземаме съдъжанието на файла, който е генериран след обработката към .txt формат
        return fileman_Files::getContent($htmlFileHnd);
    }
    
    
    /**
     * Връща хедърите на имейла
     * 
     * @param email_Mime $mimeInst - Инстанция към класа
     * @param object $emlRec - Данните за имейла
     * @param object $parseHeaders - Дали да се парсират хедърите
     * 
     * return array $headersArr - Масив с хедърите
     */
    static function getHeaders($mimeInst, $emlRec, $parseHeaders=FALSE)
    {
        // 
        $emlFileHnd = fileman_Files::fetchField($emlRec->emlFile, 'fileHnd');

        // Вземаме хедърите от EML файла
        $headersArr = $mimeInst->getHeadersFromEmlFile($emlFileHnd);
        
        // Връщаме хедърите
        return $headersArr;
    }
    
    
    /**
     * Връща html стринг с прикачените файлове
     * 
     * @param object $emlRec - Данните за имейла
     * 
     * return string - html стринг с прикачените файлове
     */
    static function getFiles($emlRec)
    {
        // Масив с всички прикачени файлове
        $filesArr = type_Keylist::toArray($emlRec->files);
        
        // Обхождаме всички файлове и вземаме линк за сваляне
        foreach ($filesArr as $keyD) {
            $filesStr .= fileman_Download::getDownloadLinkById($keyD) . "\n";
        }
        
        // Ако има html файл, вземаме линк към него
        if($emlRec->htmlFile) {
            $filesStr .= fileman_Download::getDownloadLinkById($emlRec->htmlFile);
        }
        
        // Връщаме стринга
        return $filesStr;
    }
    
    
    /**
     * Променяме id' тата на EML и HTML файловете, да сочат към първия файл
     * 
     * @param object &$emlRec - Данните за имейла
     */
    static function changeEmlAndHtmlFileId(&$emlRec)
    {
        // Вземаме данните за HTML файла
        $htmlFileRec = fileman_Files::fetch($emlRec->htmlFile);
        
        // Намираме първия запис
        if ($firstHtmlFileRec = fileman_Files::fetch("#dataId = '{$htmlFileRec->dataId}' AND name != '{$htmlFileRec->name}'")) {
            
            // Изтриваме текущия HTML файл
            fileman_Files::delete($emlRec->htmlFile);
            
            // Променяме id' то да е на пътвия запис
            $emlRec->htmlFile = $firstHtmlFileRec->id;
        }
        
        // Вземаме данните за EML файла
        $emlFileRec = fileman_Files::fetch($emlRec->emlFile);
        
        // Намираме първия запис
        if ($firstEmlFileRec = fileman_Files::fetch("#dataId = '{$emlFileRec->dataId}' AND name != '{$emlFileRec->name}'")) {
            
            // Изтриваме текущия HTML файл
            fileman_Files::delete($emlRec->emlFile);
            
            // Променяме id' то да е на пътвия запис
            $emlRec->emlFile = $firstEmlFileRec->id;
        }
    }
}