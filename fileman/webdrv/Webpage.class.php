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
        
        // Url към HTML файла
        $htmlPartUrl = static::getHtmlPart($fRec);
        
        // Текстовата част
        $textPart = static::getRichTextPart($fRec);
        
        // Вземаме съдържанието на таба за HTML
        $htmlPart = static::getHtmlTabTpl($htmlPartUrl);
        
        // Подготвяме табовете
        
        if (trim($htmlPart)) {
            // Таб за информация
            $tabsArr['html'] = (object)
            array(
                    'title' => 'HTML',
                    'html'  => $htmlPart,
                    'order' => 3,
            );
        }
        
        $tPart = strip_tags($textPart);
        if (trim($tPart)) {
            // Таб за текстовата част
            $tabsArr['text'] = (object)
            array(
                    'title' => 'Текст',
                    'html'  => "<div class='webdrvTabBody'><div class='webdrvFieldset'><div class='legend'>" . tr("Текст") . "</div>{$textPart}</div></div>",
                    'order' => 4,
            );
        }
			
        return $tabsArr;
    }
    
    
    /**
     * Връща HTML частта от файла
     * 
     * @param object $fRec - Данните за файла
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
     * @param object $fRec - Данните за файла
     * 
     * return string - Текстовата част
     */
    static function getRichTextPart($fRec)
    {    
        // Вземаме съдържанието на файла
        $content = fileman_Files::getContent($fRec->fileHnd);
        
        $content = i18n_Charset::convertToUtf8($content, array(), TRUE);
        
        // Инстанция на richtext типа
        $richText = cls::get('type_Richtext');
        
        // Вземаме текстовата част в richEdit тип
        
        $textPart = html2text_Converter::toRichText($content);
        
        $textPart = mb_substr($textPart, 0, 100000);
        
        $textPart = $richText->toVerbal($textPart);
        
        return $textPart;
    }
    
    
     /**
     * Връща текстовата част на файла
     * 
     * @param object $fRec - Данните за файла
     * 
     * return string - Текстовата част
     */
    static function getTextPart($fRec)
    {
        if (is_object($fRec)) {
            // Съдържанието на файла
            $content = fileman_Files::getContent($fRec->fileHnd);
        } elseif (is_file($fRec)) {
            $content = @file_get_contents($fRec);
        }
        
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
        }
        
        return $content;
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
            if ($params['fileHnd']) {
                $htmlPart = self::getInfoContentByFh($fRec->fileHnd, 'text');
            } else {
                $htmlPart = self::getTextPart($fRec);
            }
        	
            $htmlPart = mb_strcut($htmlPart, 0, 1000000);
            $htmlPart = i18n_Charset::convertToUtf8($htmlPart);
        	
            if ($params['fileHnd']) {
                // Обновяваме данните за запис във fileman_Indexes
                $params['content'] = $htmlPart;
                fileman_Indexes::saveContent($params);
            }
        	
            // Отключваме процеса
            core_Locks::release($params['lockId']);
        	
            return $htmlPart;
        }
    }
}
