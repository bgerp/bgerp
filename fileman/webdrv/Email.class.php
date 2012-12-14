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
     * Кой таб да е избран по подразбиране
     * @Override
     * @see fileman_webdrv_Generic::$defaultTab
     */
    static $defaultTab = 'html';
    
    
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
        
        // Подгорвяме сорса за показване
        $sourceShow = static::prepareSource($source);
    
        // Очакваме да няма проблем при парсирането
        expect($emlRec = $mime->getEmail($source));

        // Променяме Id' то на EML и HTML файла
        static::changeEmlAndHtmlFileId($emlRec);
        
        // Вземаме текстовата част
        $textPart = static::getTextPart($mime, TRUE);
        
        // Проверяаваме дали има текстова част и дали има съдържание
        $textPartCheck = static::checkTextPart($mime);
        
        // Вземаме HTML частта
        $htmlPartUrl = static::getHtmlPart($mime, $emlRec);
        
        // Проверяваме дали има HTML част и дали има съдържание
        $htmlPartCheck = static::checkHtmlPart($htmlPartUrl);
        
        // Вземаме хедърите
        $headersArr = static::getHeaders($mime, $emlRec);
        $headersStr = type_Varchar::escape($headersArr['string']);
        
        // Добавяме стилове
        $headersStr = "<div style='background-color: transparent; font-family: monospace \"courier new\";'>{$headersStr}</div>";
        
        // Вземаме линковете към файловете
        $filesStr = static::getFiles($mime, $emlRec);
       
        // Подготвяме табовете
        
        // Ако има HTML част
        if ($htmlPartCheck) {
            
            // Вземаме съдържанието на таба за HTML
            $htmlPart = static::getHtmlTabTpl($htmlPartUrl);
            
            // Таб за HTML част
            $tabsArr['html'] = (object) 
    			array(
    				'title' => 'HTML',
    				'html'  => $htmlPart,
    				'order' => 3,
    			);    
        }
        
        // Ако има текстова част
        if ($textPartCheck) {
            
            // Таб за текстовата част
            $tabsArr['text'] = (object) 
    			array(
    				'title' => 'Текст',
    				'html'  => "<div class='webdrvTabBody' style='white-space:pre-line;'><fieldset class='webdrvFieldset'><legend>Текстовата част на имейла</legend>{$textPart}</fieldset></div>",
    				'order' => 4,
    			);    
        }
        
	    // Ако има прикачени файлове
	    if ($filesStr) {

	        // Таб за преглед
    		$tabsArr['files'] = (object) 
    			array(
    				'title'   => 'Файлове',
    				'html'    => "<div class='webdrvTabBody' style='white-space:pre-line;'><fieldset class='webdrvFieldset'><legend>Прикачените файлове</legend>{$filesStr}</fieldset></div>",
    				'order' => 5,
    			);
	    }
			
		// Таб за хедърите
		$tabsArr['headers'] = (object) 
			array(
				'title'   => 'Хедъри',
				'html'    => "<div class='webdrvTabBody' style='white-space:pre-wrap;'><fieldset class='webdrvFieldset'><legend>Хедърите на имейла</legend>{$headersStr}</fieldset></div>",
				'order' => 8,
			);
			
        // Таб за сорса
        $tabsArr['source'] = (object) 
			array(
				'title'   => 'Сорс',
				'html'    => "<div class='webdrvTabBody'><fieldset class='webdrvFieldset'><legend>Сорса на имейла</legend>{$sourceShow}</fieldset></div>",
				'order' => 9,
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
     * @param boolean $escape - Дали да се ескейпва текстовата част
     * 
     * return string - Текстовата част
     */
    static function getTextPart($mime, $escape=TRUE)
    {
        // Текстовата част
        $textPart = $mime->getJustTextPart();
        
        // Ако е зададено да се ескейпва
        if ($escape) {
            
            // Ескейпваме текстовата част
            $textPart = core_Type::escape($textPart);    
        }
        
        return $textPart;
    }
    
    
    /**
     * Връща HTML частта от файла
     * 
     * @param object $emlRec - Данните за имейла
     * 
     * return string - HTML частта на файла
     */
    static function getHtmlPart($mime, $emlRec)
    {
        // Ако липсва HTML част
        if (!$emlRec->htmlFile) return ;
        
        // Манипулатора на html файла
        $htmlFileHnd = fileman_Files::fetchField($emlRec->htmlFile, 'fileHnd');
        
        return fileman_Download::getDownloadUrl($htmlFileHnd);
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
    static function getFiles($mime, $emlRec)
    {
        // Масив с всички прикачени файлове
        $filesArr = type_Keylist::toArray($emlRec->files);
        
        // Масив за HTML файла
        $htmlFile = array();
        
        // Стринг с всички прикачени дайлове
        $filesStr = '';
        
        // Масив с всички линкнатите файлове (cid)
        $linkedFilesArr = $mime->getLinkedFiles();
        
        // Масив с файловете от частите
        $partFiles = $mime->getPartFiles();
        
        // Прикачените файлове, без CID
        $attachedFiles = $mime->getJustAttachedFiles();
        
        // CID файловете
        $cidFiles = $mime->getCidFiles();
        
        // Добавяме в масив HTML файла
        if($emlRec->htmlFile) {
            $htmlFile[$emlRec->htmlFile] = $emlRec->htmlFile;
        }
        
        // Събираме всички масиви
        $allFiles = $htmlFile + $partFiles + $attachedFiles + $cidFiles;
        
        // Съединяваме линкнатите файлове с прикачените файлове
        $filesArr += $linkedFilesArr;
        
        // Обхождаме всички файлове и вземаме линк за сваляне
        foreach ($allFiles as $keyD => $dummy) {
            $filesStr .= fileman_Download::getDownloadLinkById($keyD) . "\n";
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
        return;

        // Ако има html файл
        if ($emlRec->htmlFile) {
            
            // Вземаме данните за HTML файла
            $htmlFileRec = fileman_Files::fetch($emlRec->htmlFile);
            
            // Намираме първия запис
            if ($firstHtmlFileRec = fileman_Files::fetch("#dataId = '{$htmlFileRec->dataId}' AND name != '{$htmlFileRec->name}'")) {
                
                // Изтриваме текущия HTML файл
                fileman_Files::delete($emlRec->htmlFile);
                
                // Променяме id' то да е на пътвия запис
                $emlRec->htmlFile = $firstHtmlFileRec->id;
            }    
        }

        // Ако има eml файл
        if ($emlRec->emlFile) {
            
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
        
        // Инстанция на класа
        $mime = new email_Mime();
        
        // Очакваме да няма проблем при парсирането
        expect($emlRec = $mime->getEmail(static::getSource($fRec)));
        
        // В зависимост от типа пускаме различни методи
        switch ($type) {
            
            // Ако ни трябва текстовата част
            case 'text':
                $content = static::getTextPart($mime, FALSE);
            break;
            
            default:
                
                // Ако типа не съществува, връщаме FALSE
                return FALSE;
            break;
        }
        
        return $content;
    }
    
    
    /**
     * Подготвя сорса за показване
     * 
     * @param string $source - Соурса, който искаме да го добавим
     * 
     * @return type_Richtext $source - Преработения сорс
     */
    static function prepareSource($source)
    {
        // Добавяме сорса в code елемент
        $source = "[code=eml]{$source}[/code]";
        
        // Инстанция на richtext
        $richtextInst = cls::get('type_Richtext');
        
        // Преобразуваме към вербална стойност
        $source = $richtextInst->toVerbal($source);

        return $source;
    }

    
    /**
     * Проверяваме дали има HTML част
     * 
     * @param $link - Линка към файла
     * 
     * @return boolean - Ако има съдържание връща TRUE
     */
    static function checkHtmlPart($link)
    {
        // Ако няма линк кода не се изплълнява
        if (!$link) return ;
        
        // Вземаме съдържанието на линка
        $content = file_get_contents($link);
        
        // Преобразуваме го в текс
        $content = html2text_Converter::toRichText($content);
        
        // След тримване, ако има съдъжание връщаме TRUE
        if (trim($content)) return TRUE;
    }
    
    
    /**
     * Проверяваме дали има текстова част
	 * 
	 * @param email_Mime $mime - Обект
     * 
     * @return boolean - Ако има съдържание връща TRUE
     */
    static function checkTextPart($mime)
    {
        if (trim($mime->getJustTextPart())) return TRUE;
    }
}
