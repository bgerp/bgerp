<?php


/**
 * Родителски клас на всички офис документа. Съдържа методите по подразбиране.
 *
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Office extends fileman_webdrv_Generic
{
    
    
    /**
     * Кой таб да е избран по подразбиране
     * @Override
     * @see fileman_webdrv_Generic::$defaultTab
     */
    public static $defaultTab = 'preview';
    

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
    public static function getTabs($fRec)
    {
        // Вземаме табовете от родителя
        $tabsArr = parent::getTabs($fRec);
        
        // URL за показване на преглед на файловете
        $previewUrl = toUrl(array('fileman_webdrv_Office', 'preview', $fRec->fileHnd), true);
        
        // Таб за преглед
        $tabsArr['preview'] = (object)
            array(
                'title' => 'Преглед',
                'html' => "<div class='webdrvTabBody'><div class='webdrvFieldset'><div class='legend'>" . tr('Преглед') . "</div> <iframe src='{$previewUrl}' frameBorder='0' ALLOWTRANSPARENCY='true' class='webdrvIframe'> </iframe></div></div>",
                'order' => 2,
            );
        
        // URL за показване на текстовата част на файловете
        $textPart = toUrl(array('fileman_webdrv_Office', 'text', $fRec->fileHnd), true);
        
        if (self::canShowTab($fRec->fileHnd, 'text') || self::canShowTab($fRec->fileHnd, 'textOcr', true, true)) {
            // Таб за текстовата част
            $tabsArr['text'] = (object)
            array(
                    'title' => 'Текст',
                    'html' => "<div class='webdrvTabBody'><div class='webdrvFieldset'><div class='legend'>" . tr('Текст') . "</div> <iframe src='{$textPart}' frameBorder='0' ALLOWTRANSPARENCY='true' class='webdrvIframe'> </iframe></div></div>",
                    'order' => 4,
            );
        }
        
        if (self::canShowTab($fRec->fileHnd, 'html')) {
            $content = fileman_Indexes::getInfoContentByFh($fRec->fileHnd, 'html');
            
            if (($content === false) || (is_object($content) && $content->errorProc)) {
                $htmlUrl = toUrl(array('fileman_webdrv_Office', 'html', $fRec->fileHnd), true);
                
                // Таб за информация
                $tabsArr['html'] = (object)
                array(
                        'title' => 'HTML',
                        'html' => "<div class='webdrvTabBody'><div class='webdrvFieldset'><div class='legend'>" . tr('HTML') . "</div> <iframe src='{$htmlUrl}' frameBorder='0' ALLOWTRANSPARENCY='true' class='webdrvIframe'> </iframe></div></div>",
                        'order' => 3,
                );
                
                return $tabsArr;
            }
            
            $fName = $fRec->fileHnd . rand();
            
            $path = fileman::getTempDir() . "/{$fName}.html";
            
            @file_put_contents($path, $content);
            
            // Вземаме съдържанието на таба за HTML
            $htmlPart = static::getHtmlTabTpl($path);
            
            if (trim($htmlPart)) {
                // Таб за информация
                $tabsArr['html'] = (object)
                array(
                        'title' => 'HTML',
                        'html' => $htmlPart,
                        'order' => 3,
                );
            }
            
            @unlink($path);
        }

        return $tabsArr;
    }
    
    
    /**
     * Стартира извличането на информациите за файла
     *
     * @param object $fRec - Записите за файла
     *
     * @Override
     * @see fileman_webdrv_Generic::startProcessing
     */
    public static function startProcessing($fRec)
    {
        parent::startProcessing($fRec);
        static::extractText($fRec);
        static::convertToJpg($fRec);
        static::convertToHtml($fRec);
    }
    
    
    /**
     * Извлича текстовата част от файла
     *
     * @param object $fRec - Записите за файла
     */
    public static function extractText($fRec)
    {
        // Параметри необходими за конвертирането
        $params = array(
                'callBack' => 'fileman_webdrv_Office::afterExtractText',
                'createdBy' => core_Users::getCurrent('id'),
                'type' => 'text',
        );
        
        if (is_object($fRec)) {
            $params['dataId'] = $fRec->dataId;
            $params['asynch'] = true;
            $file = $fRec->fileHnd;
        } else {
            $params['asynch'] = false;
            $params['isPath'] = true;
            $file = $fRec;
        }
        
        $lId = self::prepareLockId($fRec);
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = static::getLockId($params['type'], $lId);

        // Проверявама дали няма извлечена информация или не е заключен
        if (fileman_Indexes::isProcessStarted($params)) {
            
            return ;
        }
        
        // Заключваме процеса за определено време
        if (core_Locks::get($params['lockId'], 100, 0, false)) {
            
            // Извличаме текстовата част с Apache Tika
            return apachetika_Detect::extract($file, $params);
        }
    }
    
    
    /**
     * Извиква се след приключване на извличането на текстовата част
     *
     * @param object $script - Данни необходими за извличането и записването на текста
     *
     * @return TRUE - Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове
     *              и записа от таблицата fconv_Process
     *
     * @access protected
     */
    public static function afterExtractText($script)
    {
        // Десериализираме нужните помощни данни
        $params = unserialize($script->params);
        
        // Проверяваме дали е имало грешка при предишното конвертиране
        if (fileman_Indexes::haveErrors($script->outFilePath, $params)) {
            
            // Отключваме процеса
            core_Locks::release($params['lockId']);
            
            return false;
        }
        
        // Вземаме съдъжанието на файла, който е генериран след обработката към .txt формат
        $text = file_get_contents($script->outFilePath);
        
        // Поправяме текста, ако има нужда
        $text = i18n_Charset::convertToUtf8($text, 'UTF-8');
        
        // Текстовата част
        $params['content'] = $text;

        // Обновяваме данните за запис във fileman_Indexes
        $savedId = fileman_Indexes::saveContent($params);

        // Отключваме процеса
        core_Locks::release($params['lockId']);
        
        if ($savedId) {

            // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове
            // и записа от таблицата fconv_Process
            return true;
        }
    }
    
    
    /**
     * Конвертиране в JPG формат
     *
     * @param object $fRec - Записите за файла
     */
    public static function convertToJpg($fRec)
    {
        return self::convertToFile($fRec);
    }
    
    
    /**
     * Конвертира файла към съответното разширение
     *
     * @param  stdClass    $fRec
     * @param  string      $toExt
     * @param  boolean     $asynch
     * @param  string      $callBack
     * @param  string      $outType
     * @return NULL|string
     */
    public static function convertToFile($fRec, $toExt = 'pdf', $asynch = true, $callBack = 'fileman_webdrv_Office::afterConvertDocToJpg', $outType = 'jpg')
    {
        // Параметри необходими за конвертирането
        $params = array(
                'callBack' => $callBack,
                'dataId' => $fRec->dataId,
                'asynch' => $asynch,
                'createdBy' => core_Users::getCurrent('id'),
                'type' => 'docTo' . ucfirst($toExt),
        );
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = static::getLockId($params['type'], $fRec->dataId);
        
        // Проверявама дали няма извлечена информация или не е заключен
        if (fileman_Indexes::isProcessStarted($params)) {
            
            return ;
        }
        
        // Параметри за проверка дали е стартиран процеса на конвертиране на получения $toExt документ към $outType
        $paramsOut = $params;
        $paramsOut['type'] = $outType;
        $paramsOut['lockId'] = static::getLockId($paramsOut['type'], $fRec->dataId);
        
        // Проверявама дали няма извлечена информация или не е заключен
        if (fileman_Indexes::isProcessStarted($paramsOut)) {
            
            return ;
        }
        
        $outFilePath = '';
        
        // Заключваме процеса за определено време
        if (core_Locks::get($params['lockId'], 100, 0, false)) {
            
            // Конфигурационните константи
            $conf = core_Packs::getConfig('docoffice');
            
            // Класа, който ще конвертира
            $ConvClass = $conf->OFFICE_CONVERTER_CLASS;
            
            // Инстанция на класа
            $inst = cls::get($ConvClass);
            
            $params['outType'] = $outType;
            
            // Стартираме конвертирането
            $outFilePath = $inst->convertDoc($fRec->fileHnd, $toExt, $params);
        }
        
        return $outFilePath;
    }
    
    
    /**
     * Функция, която получава управлението след конвертирането на офис докуемнта към PDF
     *
     * @param object $script - Обект със стойности
     *
     * @return boolean TRUE - Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове
     *                 и записа от таблицата fconv_Process
     *
     * @access protected
     */
    public static function afterConvertDocToJpg($script)
    {
        // Десериализираме параметрите
        $params = unserialize($script->params);
        
        // Проверяваме дали е имало грешка при предишното конвертиране
        $error = fileman_Indexes::haveErrors($script->outFilePath, $params);
        
        // Отключваме предишния процес
        core_Locks::release($params['lockId']);
        
        // Ако има грешка кода не се изпълнява
        if ($error) {
            
            return false;
        }
        
        // Параметри необходими за конвертирането
        $params['callBack'] = 'fileman_webdrv_Office::afterConvertToJpg';
        $params['type'] = 'jpg';
        $params['asynch'] = false;
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = static::getLockId($params['type'], $params['dataId']);

        // Проверявама дали няма извлечена информация или не е заключен
        if (fileman_Indexes::isProcessStarted($params)) {
            
            return ;
        }
        
        // Заключваме процеса за определно време
        if (core_Locks::get($params['lockId'], 100, 0, false)) {
            
            // Стартираме конвертирането синхронно
            $started = static::convertPdfToJpg($script->outFilePath, $params);
    
            // Отключваме заключения процес за конвертиране от офис към pdf формат
            core_Locks::release($params['lockId']);
        }

        if ($started) {

            // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове
            // и записа от таблицата fconv_Process
            return true;
        }
    }
    
    
    /**
     * Конвертиране на PDF документи към JPG с помощта на imageMagic
     *
     * @param fileHandler $fileHnd - Манупулатора на файла, който ще се конвертира
     * @param array       $params  - Други параметри
     *                             $params['callBack'] - Класа и функцията, която ще се извикат след приключване на конвертирането
     *                             $params['asynch'] - Дали скрипта да се стартира асинхронно или не
     *                             и др.
     */
    public static function convertPdfToJpg($fileHnd, $params = array())
    {
        // Инстанция на класа
        $Script = cls::get('fconv_Script');
        
        // Конфигурационните данни
        $conf = core_Packs::getConfig('fileman');
        
        // Вземаме името на файла без разширението
        $name = fileman_Files::getFileNameWithoutExt($fileHnd);

        // Задаваме пътя до изходния файла
        $outFilePath = $Script->tempDir . $name . '-%d.jpg';
        
        // Задаваме placeHolder' ите за входния и изходния файл
        $Script->setFile('INPUTF', $fileHnd, true);
        $Script->setFile('OUTPUTF', $outFilePath);
        
        // Ако има зададен път до gs, използваме него
        $Script->setProgram('gs', fileman_Setup::get('GHOSTSCRIPT_PATH'));
        
        $errFilePath = self::getErrLogFilePath($outFilePath);
        
        // Скрипта, който ще конвертира файла от PDF в JPG формат
        $Script->lineExec('gs -dSAFER -dNOPAUSE -dNOCACHE -sDEVICE=jpeg -dGraphicsAlphaBits=4 -dTextAlphaBits=4 -sOutputFile=[#OUTPUTF#] -dBATCH -r200 [#INPUTF#]', array('errFilePath' => $errFilePath));
        
        // Функцията, която ще се извика след приключване на обработката на файла
        $Script->callBack($params['callBack']);
        
        $params['errFilePath'] = $errFilePath;
        
        // Други необходими променливи
        $Script->params = serialize($params);
        $Script->fName = $name;
        $Script->fh = $fileHnd;
        
        $Script->outFilePath = $outFilePath;
        
        // Ако е подаден параметър за стартиране синхронно
        // Когато се геририра от офис документи PDF, и от полученич файл
        // се генерира JPG тогава трябва да се стартира синхронно
        // В другите случаи трябва да е асинхронно за да не чака потребителя
        $Script->setCheckProgramsArr('gs');
        if ($Script->run($params['asynch']) === false) {
            fileman_Indexes::createError($params);
        }
        
        return true;
    }
    
    
    /**
     * Функция, която получава управлението след конвертирането на файл в JPG формат
     *
     * @param object $script     - Обект със стойности
     * @param output $fileHndArr - Масив, в който след обработката ще се запишат получените файлове
     *
     * @return boolean TRUE - Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове
     *                 и записа от таблицата fconv_Process
     *
     * @access protected
     */
    public static function afterConvertToJpg($script, &$fileHndArr = array())
    {
        // Вземаме всички файлове във временната директория
        $files = scandir($script->tempDir);

        // Шаблон за намиране на името на файла
        $pattern = '/^' . preg_quote($script->fName, '/') . "\-(?'num'[0-9]+)\.jpg$/i";
        
        $filesArr = array();
        
        // Обхождаме всички отркити файлове
        foreach ($files as $file) {
            
            // Ако няма съвпадение, връщаме
            if (!preg_match($pattern, $file, $matches)) {
                continue;
            }
            
            // Записваме номера и името на файла
            $filesArr[$matches['num']] = $file;
        }
        
        // Сортираме масива по ключ
        ksort($filesArr);
        
        $maxFilesCnt = fileman_Setup::get('FILEINFO_MAX_PREVIEW_PAGES', true);
        
        $otherFilesCnt = 0;
        
        foreach ($filesArr as $file) {
            
            // При достигане на лимита, спираме качването
            if ($maxFilesCnt-- <= 0) {
                $otherFilesCnt++;
                
                continue;
            }
            
            // Ако възникне грешка при качването на файла (липса на права)
            try {
                
                // Качваме файла в кофата и му вземаме манипулатора
                $fileHnd = fileman::absorb($script->tempDir . $file, 'fileIndex');
            } catch (core_exception_Expect $e) {
                continue;
            }
            
            // Ако се качи успешно записваме манипулатора в масив
            if ($fileHnd) {
                $fileHndArr[$fileHnd] = $fileHnd;
            }
        }
        
        if ($otherFilesCnt) {
            $fileHndArr['otherPagesCnt'] = $otherFilesCnt;
        }
        
        // Десериализираме нужните помощни данни
        $params = unserialize($script->params);
        
        // Ако има генерирани файлове, които са качени успешно
        if (count($fileHndArr)) {
            
            // Текстовата част
            $params['content'] = $fileHndArr;
    
            // Обновяваме данните за запис във fileman_Indexes
            $savedId = fileman_Indexes::saveContent($params);
        } else {
        
            // Проверяваме дали е имало грешка при предишното конвертиране
            $error = fileman_Indexes::haveErrors($script->outFilePath, $params);
        }
        
        // Отключваме процеса
        core_Locks::release($params['lockId']);
        
        // Ако има грешка кода не се изпълнява
        if ($error) {
            
            return false;
        }
        
        if ($savedId) {

            // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове
            // и записа от таблицата fconv_Process
            return true;
        }
    }
    
    
    /**
     * Конвертираме в HTML
     *
     * @param object $fRec - Записите за файла
     */
    public static function convertToHtml($fRec)
    {
        // Параметри необходими за конвертирането
        $params = array(
            'callBack' => 'fileman_webdrv_Office::afterConvertToHtml',
            'dataId' => $fRec->dataId,
            'asynch' => true,
            'createdBy' => core_Users::getCurrent('id'),
            'type' => 'html',
        );
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = static::getLockId($params['type'], $fRec->dataId);

        // Проверявама дали няма извлечена информация или не е заключен
        if (fileman_Indexes::isProcessStarted($params)) {
            
            return ;
        }

        // Заключваме процеса за определено време
        if (core_Locks::get($params['lockId'], 100, 0, false)) {
            
            // Извличаме HTML частта с Apache Tika
            apachetika_Detect::extract($fRec->fileHnd, $params);
        }
    }
    
    
    /**
     * Получава управлението след извличане на HTML' а
     *
     * @param fconv_Script $script - Данни необходими за извличането и записването на текста
     */
    public static function afterConvertToHtml($script)
    {
        // Десериализираме нужните помощни данни
        $params = unserialize($script->params);
        
        // Проверяваме дали е имало грешка при предишното конвертиране
        if (fileman_Indexes::haveErrors($script->outFilePath, $params)) {
            
            // Отключваме процеса
            core_Locks::release($params['lockId']);
            
            return false;
        }
        
        // Вземаме съдъжанието на файла, който е генериран след обработката към .txt формат
        $html = file_get_contents($script->outFilePath);
        
        // Ако енкодинга е ascii /За htmlentites/
        if (strtolower(mb_detect_encoding($html)) == 'ascii') {
            
            // Конвертираме текста в UTF-8
            $html = mb_convert_encoding($html, 'UTF-8', 'HTML-ENTITIES');
        }
        
        // Вземаме тялото на HTML' а
        $html = str::cut($html, '<body>', '</body>');

        // Поправяме текста, ако има нужда
        $html = i18n_Charset::convertToUtf8($html, 'UTF-8', true);
        
        // Текстовата част
        $params['content'] = $html;

        // Обновяваме данните за запис във fileman_Indexes
        $savedId = fileman_Indexes::saveContent($params);

        // Отключваме процеса
        core_Locks::release($params['lockId']);
        
        if ($savedId) {

            // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове
            // и записа от таблицата fconv_Process
            return true;
        }
    }
    
    
    /**
     * Връща масив с височината и ширината за прегледа на изображението
     *
     * @return array
     */
    public static function getPreviewWidthAndHeight()
    {
        //Вземема конфигурационните константи
        $conf = core_Packs::getConfig('docoffice');
        
        // В зависимост от широчината на екрана вземаме размерите на thumbnail изображението
        if (mode::is('screenMode', 'narrow')) {
            $thumbWidth = $conf->OFFICE_PREVIEW_WIDTH_NARROW;
            $thumbHeight = $conf->OFFICE_PREVIEW_HEIGHT_NARROW;
        } else {
            $thumbWidth = $conf->OFFICE_PREVIEW_WIDTH;
            $thumbHeight = $conf->OFFICE_PREVIEW_HEIGHT;
        }
        
        // Добавяме в масива
        $arr = array();
        $arr['width'] = $thumbWidth;
        $arr['height'] = $thumbHeight;
        
        return $arr;
    }
}
