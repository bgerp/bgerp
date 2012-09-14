<?php


/**
 * Родителски клас на всички имейл документи. Съдържа методите по подразбиране.
 *
 * @todo - Имената на html файла и eml файла трябва да се променят както в email_Inocomings->setEmlAndHtmlFileNames
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
        
        // URL за показване на текстовата част на файловете
        $textPart = toUrl(array('fileman_webdrv_Email', 'text', $fRec->fileHnd), TRUE);
        // Таб за текстовата част
        $tabsArr['text'] = (object) 
			array(
				'title' => 'Текст',
				'html'  => "<div> <iframe src='{$textPart}' class='webdrvIframe'> </iframe> </div>",
				'order' => 2,
			);
        
		$htmlUrl = toUrl(array('fileman_webdrv_Email', 'html', $fRec->fileHnd), TRUE);	
		// Таб за информация
        $tabsArr['html'] = (object) 
			array(
				'title' => 'HTML',
				'html'  => "<div> <iframe src='{$htmlUrl}' class='webdrvIframe'> </iframe> </div>",
				'order' => 4,
			);

        // URL за показване на преглед на файловете
        $filesUrl = toUrl(array('fileman_webdrv_Email', 'files', $fRec->fileHnd), TRUE);
        // Таб за преглед
		$tabsArr['files'] = (object) 
			array(
				'title'   => 'Файлове',
				'html'    => "<div> <iframe src='{$filesUrl}' class='webdrvIframe'> </iframe> </div>",
				'preview' => 5,
			);
			
	    // URL за показване на преглед на файловете
        $headersUrl = toUrl(array('fileman_webdrv_Email', 'headers', $fRec->fileHnd), TRUE);
        // Таб за преглед
		$tabsArr['headers'] = (object) 
			array(
				'title'   => 'Хедъри',
				'html'    => "<div> <iframe src='{$headersUrl}' class='webdrvIframe'> </iframe> </div>",
				'preview' => 5,
			);
			
        return $tabsArr;
    }
    
    
	/**
     * Екшън за показване текстовата част на файла
     */
    function act_Text()
    {
        // Вземаме съдържанието от родителския клас
        $content = parent::act_Text();
        
        // Ако мода ма wrapper' а е page_Waiting връщаме
        if (Mode::is('wrapper', 'page_Waiting')) {
            
            return ;
        }
        
        // Сменяма wrapper'а
        Mode::set('wrapper', 'page_Html'); // Тук може и да се използва page_PreText за подреден текст
        
        // Обработваме съдържанието
        $content = str_replace("\n\n\n", "\n\n", $content);
        $richText = new type_Richtext();
        $content = $richText->toVerbal($content);
        
        return $content;
    }
    
    
	/**
     * Екшън за показване текстовата част на файла
     */
    function act_Files()
    {
        // Манупулатора на файла
        $fileHnd = Request::get('id'); 
        
        // Вземаме текста
        $content = fileman_Indexes::getInfoContentByFh($fileHnd, 'files');
        
        // Ако нама такъв запис
        if ($content === FALSE) {
            
            // Сменяме мода на page_Waiting
            Mode::set('wrapper', 'page_Waiting');
            
            return ;
        }

        // Сменяма wrapper'а да е празна страница
        Mode::set('wrapper', 'page_PreText');
        
        // Връщаме съдържанието
        return $content;
    }
    
    
	/**
     * Екшън за показване текстовата част на файла
     */
    function act_Headers()
    {
        // Манупулатора на файла
        $fileHnd = Request::get('id'); 
        
        // Вземаме текста
        $content = fileman_Indexes::getInfoContentByFh($fileHnd, 'headers');
        
        // Санитаризираме данните
        $content = type_Varchar::escape($content);
        
        // Ако нама такъв запис
        if ($content === FALSE) {
            
            // Сменяме мода на page_Waiting
            Mode::set('wrapper', 'page_Waiting');
            
            return ;
        }

        // Сменяма wrapper'а да е празна страница
        Mode::set('wrapper', 'page_PreText');
        
        // Връщаме съдържанието
        return $content;
    }
    
    
    /**
     * Стартира извличането на информациите за файла
     * 
     * @param object $fRec - Записите за файла
     * 
     * @Override
     * @see fileman_webdrv_Generic::startProcessing
     */
    static function startProcessing($fRec) 
    {
        parent::startProcessing($fRec);
        static::getTextPart($fRec);
        static::getHtmlPart($fRec);
        static::getFiles($fRec);
        static::getHeaders($fRec);
    }
    
    
    /**
     * Извлича текстовата част от файла
     * 
     * @param object $fRec - Записите за файла
     */
    static function getTextPart($fRec)
    {
        // Извикваме функцията за стартиране на извличането
        static::startGettingInfo($fRec, 'afterGetTextPart', 'text');
    }
    
    
    /**
     * Извиква се след приключване на извличането на текстовата част
     * 
     * @param object $script - Данни необходими за извличането и записването на текста
     * 
     * @return TRUE - Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
     * и записа от таблицата fconv_Process
     * 
     * @access protected
     */
    static function afterGetTextPart($script)
    {
        // Масива с параметрите
        $params = unserialize($script->params);
        
        // Тук парсираме писмото и проверяваме дали не е системно
        $mime = new email_Mime();
            
        // Очакваме да има такъв запис
        expect($emlRec = $mime->getEmail(fileman_Files::getContent($params['fileHnd'])));
            
        // Сериализираме масива и обновяваме данните за записа в fileman_Info
        $rec = new stdClass();
        $rec->dataId = $params['dataId'];
        $rec->type = $params['type'];
        $rec->content = static::prepareContent($emlRec->textPart);
        $rec->createdBy = $params['createdBy'];
        
        // Записваме данните
        $saveId = fileman_Indexes::save($rec);    
        
        // Отключваме процеса
        core_Locks::release($params['lockId']);
        
        if ($saveId) {

            // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
            // и записа от таблицата fconv_Process
            return TRUE;
        } else {

            // Записваме в лога съобщението за грешка
            static::createErrorLog($params['dataId'], $params['type']);
        }
    }
    

	/**
     * Извлича HTML частта от файла
     * 
     * @param object $fRec - Записите за файла
     */
    static function getHtmlPart($fRec)
    {
        // Извикваме функцията за стартиране на извличането
        static::startGettingInfo($fRec, 'afterGetHtmlPart', 'html');
    }
    
    
    /**
     * Извиква се след приключване на извличането на HTML частта
     * 
     * @param object $script - Данни необходими за извличането и записването на текста
     * 
     * @return TRUE - Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
     * и записа от таблицата fconv_Process
     * 
     * @access protected
     */
    static function afterGetHtmlPart($script)
    {
        // Масива с параметрите
        $params = unserialize($script->params);
        
        // Тук парсираме писмото и проверяваме дали не е системно
        $mime = new email_Mime();
            
        // Очакваме да има такъв запис
        expect($emlRec = $mime->getEmail(fileman_Files::getContent($params['fileHnd'])));

        $htmlFileHnd = fileman_Files::fetchField($emlRec->htmlFile, 'fileHnd');
        
        // Вземаме съдъжанието на файла, който е генериран след обработката към .txt формат
        $html = fileman_Files::getContent($htmlFileHnd);
        
        // Сериализираме масива и обновяваме данните за записа в fileman_Info
        $rec = new stdClass();
        $rec->dataId = $params['dataId'];
        $rec->type = $params['type'];
        $rec->content = static::prepareContent($html);
        $rec->createdBy = $params['createdBy'];
        
        // Записваме данните
        $saveId = fileman_Indexes::save($rec);    
        
        // Отключваме процеса
        core_Locks::release($params['lockId']);
        
        
        if ($saveId) {

            // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
            // и записа от таблицата fconv_Process
            return TRUE;
        } else {

            // Записваме в лога съобщението за грешка
            static::createErrorLog($params['dataId'], $params['type']);
        }
    }
    
    
    /**
     * Извлича HTML частта от файла
     * 
     * @param object $fRec - Записите за файла
     */
    static function getFiles($fRec)
    {
        // Извикваме функцията за стартиране на извличането
        static::startGettingInfo($fRec, 'afterGetFiles', 'files');
    }
    
    
    /**
     * Извиква се след приключване на вземането на файловете
     * 
     * @param object $script - Данни необходими за извличането и записването на текста
     * 
     * @return TRUE - Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
     * и записа от таблицата fconv_Process
     * 
     * @access protected
     */
    static function afterGetFiles($script)
    {
        // Масива с параметрите
        $params = unserialize($script->params);
        
        // Тук парсираме писмото и проверяваме дали не е системно
        $mime = new email_Mime();
            
        // Очакваме да има такъв запис
        expect($emlRec = $mime->getEmail(fileman_Files::getContent($params['fileHnd'])));
        
        $filesArr = type_Keylist::toArray($emlRec->files);
                
        foreach ($filesArr as $keyD) {
            $filesStr .= fileman_Download::getDownloadLinkById($keyD) . "\n";
        }
        
        if($emlRec->htmlFile) {
            $filesStr .= fileman_Download::getDownloadLinkById($emlRec->htmlFile);
        }
        
        // Сериализираме масива и обновяваме данните за записа в fileman_Info
        $rec = new stdClass();
        $rec->dataId = $params['dataId'];
        $rec->type = $params['type'];
        $rec->content = static::prepareContent($filesStr);
        $rec->createdBy = $params['createdBy'];
        
        // Записваме данните
        $saveId = fileman_Indexes::save($rec);    
        
        // Отключваме процеса
        core_Locks::release($params['lockId']);
        
        if ($saveId) {

            // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
            // и записа от таблицата fconv_Process
            return TRUE;
        } else {

            // Записваме в лога съобщението за грешка
            static::createErrorLog($params['dataId'], $params['type']);
        }
    }
    
    
    /**
     * Извлича HTML частта от файла
     * 
     * @param object $fRec - Записите за файла
     */
    static function getHeaders($fRec)
    {
        // Извикваме функцията за стартиране на извличането
        static::startGettingInfo($fRec, 'afterGetHeaders', 'headers');
    }
    
    
	/**
     * Извиква се след приключване на вземането на файловете
     * 
     * @param object $script - Данни необходими за извличането и записването на текста
     * 
     * @return TRUE - Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
     * и записа от таблицата fconv_Process
     * 
     * @access protected
     */
    static function afterGetHeaders($script)
    {
        // Масива с параметрите
        $params = unserialize($script->params);
        
        // Тук парсираме писмото и проверяваме дали не е системно
        $mime = new email_Mime();
            
        // Очакваме да има такъв запис
        expect($emlRec = $mime->getEmail(fileman_Files::getContent($params['fileHnd'])));
        $emlFileHnd = fileman_Files::fetchField($emlRec->emlFile, 'fileHnd');
        
        // Вземаме хедърите от EML файла
        $headersArr = $mime->getHeadersFromEmlFile($emlFileHnd);
        
        // Сериализираме масива и обновяваме данните за записа в fileman_Info
        $rec = new stdClass();
        $rec->dataId = $params['dataId'];
        $rec->type = $params['type'];
        $rec->content = static::prepareContent($headersArr['string']);
        $rec->createdBy = $params['createdBy'];
        
        // Записваме данните
        $saveId = fileman_Indexes::save($rec);    
        
        // Отключваме процеса
        core_Locks::release($params['lockId']);
        
        //$mime->parseAll($emlFileContent);
        
        if ($saveId) {

            // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
            // и записа от таблицата fconv_Process
            return TRUE;
        } else {

            // Записваме в лога съобщението за грешка
            static::createErrorLog($params['dataId'], $params['type']);
        }
    }
    
    
    
    
    
    /**
     * 
     * @param object $fRec - Записите за файла
     * @param string $func - Функцията, която да се стартира
     * @param string $type $type - Типа на записа, който ще се извлече
     * 
     * @access protected
     */
    static function startGettingInfo($fRec, $func, $type)
    {
        // Параметри необходими за конвертирането
        $params = array(
//            'callBack' => 'fileman_webdrv_Email::$func',
            'dataId' => $fRec->dataId,
//        	'asynch' => TRUE,
            'createdBy' => core_Users::getCurrent('id'),
            'type' => $type,
            'fileHnd' => $fRec->fileHnd,
        );
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = static::getLockId($params['type'], $fRec->dataId);

        // Проверявама дали няма извлечена информация или не е заключен
        if (static::isProcessStarted($params)) return ;
        
        // Заключваме процеса за определено време
        if (core_Locks::get($params['lockId'], 100, 0, FALSE)) {
            
            $script = new stdClass();
            $script->params = serialize($params);
            
            // Това е направено с цел да се запази логиката на работа на системата и възможност за раширение в бъдеще
            static::$func($script);    
        } else {
            
            // Записваме грешката
            static::createErrorLog($params['dataId'], $params['type']);
        }
    }
}