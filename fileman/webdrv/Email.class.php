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
        $mime = cls::get('email_Mime');
        
        // Вземаме съдържанието на eml файла
        $source = static::getSource($fRec);
        
        $mime->parseAll($source);
   
        $mime->saveFiles();
     
        // Подгорвяме сорса за показване
        $sourceShow = static::prepareSource($source);

        // Вземаме текстовата част
        $textPart = static::getTextPart($mime, TRUE);
        
        // Вземаме HTML частта
        $htmlPartUrl = static::getHtmlPart($mime);
        
        // Вземаме хедърите
        $headersStr = $mime->getHeadersVerbal();
       
        // Добавяме стилове
        $headersStr = "<div class='email-source-holder'><div class='email-source'>{$headersStr}</div><div>";
        
        // Вземаме линковете към файловете
        $filesStr = static::getFiles($mime);
       
        // Подготвяме табовете
        
        // Вземаме съдържанието на таба за HTML
        $htmlPart = static::getHtmlTabTpl($htmlPartUrl);
        
        // Ако няма HTML част
        if ($htmlPart !== FALSE) {
            
            // Таб за HTML част
            $tabsArr['html'] = (object) 
    			array(
    				'title' => 'HTML',
    				'html'  => $htmlPart,
    				'order' => 3,
    			); 
        } else {
            
            // Таба по подразбиране да е текстовия
            $tabsArr['__defaultTab'] = 'text';
        }
        
        
        // Ако има текстова част
        if (trim($textPart)) {
            
            // Таб за текстовата част
            $tabsArr['text'] = (object) 
    			array(
    				'title' => 'Текст',
    				'html'  => "<div class='webdrvTabBody' style='white-space:pre-line;'><div class='webdrvFieldset'><div class='legend'>" . tr("Текстовата част на имейла") . "</div>{$textPart}</div></div>",
    				'order' => 4,
    			);    
        }
        
	    // Ако има прикачени файлове
	    if ($filesStr) {

	        // Таб за преглед
    		$tabsArr['files'] = (object) 
    			array(
    				'title'   => 'Файлове',
    				'html'    => "<div class='webdrvTabBody' style='white-space:pre-line;'><div class='webdrvFieldset'><div class='legend'>" . tr("Прикачените файлове") . "</div>{$filesStr}</div></div>",
    				'order' => 5,
    			);
	    }
			
		// Таб за хедърите
		$tabsArr['headers'] = (object) 
			array(
				'title'   => 'Хедъри',
				'html'    => "<div class='webdrvTabBody' style='white-space:pre-wrap;'><div class='webdrvFieldset'><div class='legend'>" . tr("Хедърите на имейла") . "</div>{$headersStr}</div></div>",
				'order' => 8,
			);
			
        // Таб за сорса
        $tabsArr['source'] = (object) 
			array(
				'title'   => 'Сорс',
				'html'    => "<div class='webdrvTabBody'><div class='webdrvFieldset'><div class='legend'>" . tr("Изходен код на имейла") . "</div>{$sourceShow}</div></div>",
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
     * @param email_Mime $mime
     * @param boolean $escape - Дали да се ескейпва текстовата част
     * 
     * return string - Текстовата част
     */
    static function getTextPart($mime, $escape=TRUE)
    {
        // Текстовата част
        $textPart = $mime->justTextPart;
        
        if(!$textPart && $mime->textPart) {
            Mode::push('text', 'plain');
            $rt = new type_Richtext();
            $textPart = $rt->toHtml($mime->textPart);
            Mode::pop('text');
        }
        
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
     * @param email_Mime $mime
     * 
     * return string - HTML частта на файла
     */
    static function getHtmlPart($mime)
    { 
        $htmlFile = $mime->getHtmlFile();
        
        // Ако липсва HTML част
        if (!$htmlFile) return ;
        
        // Манипулатора на html файла
        $htmlFileHnd = fileman_Files::fetchField($htmlFile, 'fileHnd');
        
        return fileman_Download::getDownloadUrl($htmlFileHnd);
    }
    
    
    /**
     * Връща html стринг с прикачените файлове
     * 
     * @param email_Mime $mime
     * 
     * return string - html стринг с прикачените файлове
     */
    static function getFiles($mime)
    {
        $filesKeyList = $mime->getFiles();
        $filesArr = keylist::toArray($filesKeyList);
        
        // Масив с всички прикачени файлове
        $filesArr = keylist::toArray($filesKeyList);
        
        foreach ($filesArr as $keyD => $dummy) {
            $filesStr .= fileman_Files::getLinkById($keyD) . "\n";
        }

        // Връщаме стринга
        return $filesStr;
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
        $mime = cls::get('email_Mime');
        
        // Вземаме съдържанието на eml файла
        $source = static::getSource($fRec);
        
        $mime->parseAll($source);
        
        // В зависимост от типа пускаме различни методи
        switch ($type) {
            
            // Ако ни трябва текстовата част
            case 'text':
                $content = static::getTextPart($mime, FALSE);
            break;
            
            default:
                
                // Ако типа не съществува, връщаме FALSE
                return FALSE;
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
//        $source = i18n_Charset::convertToUtf8($source);

        // Добавяме сорса в code елемент
        $source = str_replace(array('&', '<', '>'), array('&amp;', '&lt;', '&gt;'), $source);
        
        // Преобразуваме към вербална стойност
        $source = "<div class='email-source-holder'><div class='email-source'>{$source}</div></div>";
 
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
    
    
	/**
     * Извлича текстовата част от файла
     * 
     * @param object $fRec - Записите за файла
     */
    static function extractText($fRec)
    {
        // Параметри необходими за конвертирането
        $params = array(
            'createdBy' => core_Users::getCurrent('id'),
            'type' => 'text',
        );
        
        $dId = self::prepareLockId($fRec);
        
        if (is_object($fRec)) {
            $params['dataId'] = $fRec->dataId;
            $params['fileHnd'] = $fRec->fileHnd;
        }
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = self::getLockId('text', $dId);
        
        // Проверявама дали няма извлечена информация или не е заключен
        if (fileman_Indexes::isProcessStarted($params)) return ;
        
        // Заключваме процеса за определено време
        if (core_Locks::get($params['lockId'], 100, 0, FALSE)) {
        	
            // Вземаме текстовата част
            if (is_object($fRec)) {
                $textPart = self::getInfoContentByFh($fRec->fileHnd, 'text');
            } else {
                // Записите за съответния файл
                $source = !@file_get_contents($fRec);
                
                // Инстанция на класа
                $mime = cls::get('email_Mime');
                
                $mime->parseAll($source);
                $textPart = static::getTextPart($mime, FALSE);
            }
        	
            $textPart = mb_strcut($textPart, 0, 1000000);
            $textPart = i18n_Charset::convertToUtf8($textPart);
        	
            if ($params['fileHnd']) {
                // Обновяваме данните за запис във fileman_Indexes
                $params['content'] = $textPart;
                fileman_Indexes::saveContent($params);
            }
        	
            // Отключваме процеса
            core_Locks::release($params['lockId']);
        	
            return $textPart;
        }
    }
}
