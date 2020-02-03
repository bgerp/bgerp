<?php


/**
 * Драйвер за работа с архиви.
 *
 * @category  vendors
 * @package   fileman
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class fileman_webdrv_Archive extends fileman_webdrv_Generic
{
    /**
     * Кой таб да е избран по подразбиране
     *
     * @Override
     *
     * @see fileman_webdrv_Generic::$defaultTab
     */
    public static $defaultTab = 'content';
    
    
    /**
     * Връща всички табове, които ги има за съответния файл
     *
     * @param object $fRec - Записите за файла
     *
     * @return array
     *
     * @Override
     *
     * @see fileman_webdrv_Generic::getTabs
     */
    public static function getTabs($fRec)
    {
        // Вземаме табовете от родителя
        $tabsArr = parent::getTabs($fRec);
        
        // Директорията, в която се намираме вътре в архива
        $path = core_Type::escape(Request::get('path'));
        
        // Вземаме съдържанието
        $contentStr = static::getArchiveContent($fRec, $path);
        
        // Таб за съдържанието
        $tabsArr['content'] = (object)
            array(
                'title' => 'Съдържание',
                'html' => "<div class='webdrvTabBody' style='white-space:pre-wrap;'><div class='legend'>" . tr('Съдържание') . "</div><div class='webdrvFieldset'>{$contentStr}</div></div>",
                'order' => 7,
                'tpl' => $contentStr,
            );
        
        return $tabsArr;
    }
    
    
    /**
     * Връща инстанция на адаптера за работа с архиви
     *
     * @param object $fRec - Записите за файла
     */
    public static function getArchiveInst($fRec)
    {
        // Проверяваме големината на архива
        static::checkArchiveLen($fRec->dataId);
        
        // Връщаме инстанцията
        return cls::get('archive_Adapter', array('fileHnd' => $fRec->fileHnd));
    }
    
    
    /**
     * Връща съдържанието на архива в дървовидна структура
     *
     * @param object $fRec - Записите за файла
     */
    public static function getArchiveContent($fRec, $path = null)
    {
        try {
            // Инстанция на класа
            $inst = static::getArchiveInst($fRec);
        } catch (fileman_Exception $e) {
            
            return $e->getMessage();
        }
        
        // URL' то където да сочат файловете
        $url = array('fileman_webdrv_Archive', 'absorbFileInArchive', $fRec->fileHnd, 'index' => 1);
        
        // Създаваме дървото
        $tree = $inst->tree($url);
        
        // Изтриваме временните файлове
        $inst->deleteTempPath();
        
        // Връщаме дървото
        return $tree;
    }
    
    
    /**
     * Уплоадва файла от архива
     *
     * @param object $fRec  - Записите за файла
     * @param int    $index - Номера на файлам, който ще се екстрактва
     *
     * @return string - Манипулатор на файл
     */
    public static function uploadFileFromArchive($fRec, $index)
    {
        // Инстанция на класа
        $inst = static::getArchiveInst($fRec);
        
        // Качваме съответния файл
        $fh = $inst->getFile($index);
        
        $fileNavArr = Mode::get('fileNavArr');
        
        // Намираме предишния и следващия файл
        try {
            // Вземаме съдържанието
            $entriesArr = $inst->getEntries();
            
            $prev = null;
            $iPrev = null;
            $next = null;
            $findNext = false;
            $allArhiveArr = array();
            $srcDirName = null;
            $cUrlStr = null;
            
            if (!empty($entriesArr)) {
                $eCnt = countR($entriesArr);
                
                foreach ($entriesArr as $eIndex => $entry) {
                    $size = $entry->getSize();
                    
                    if ($size && ($size < archive_Setup::get('MAX_LEN'))) {
                        $ePath = $entriesArr[$eIndex]->getPath();
                        $eUrl = array('fileman_webdrv_Archive', 'absorbFileInArchive', $fRec->fileHnd, 'index' => $eIndex);
                        
                        $urlStr = toUrl($eUrl);
                        
                        $allArhiveArr[$urlStr] = $ePath;
                        
                        if ($eIndex == $index) {
                            $cUrlStr = $urlStr;
                            if ($ePath) {
                                $eDirName = trim(dirname($ePath));
                                if ($eDirName && $eDirName != '.') {
                                    $srcDirName = $eDirName;
                                } else {
                                    $srcDirName = null;
                                }
                            }
                            
                            // Ако сме намерили предишния
                            if (!isset($prev) && isset($iPrev)) {
                                $prev = $iPrev;
                            }
                            $findNext = true;
                            
                            continue;
                        }
                        
                        $iPrev = $eUrl;
                        
                        // Ако сме намерили следващия
                        if ($findNext && !isset($next)) {
                            $next = $eUrl;
                        }
                    }
                }
            }
            
            // Добавяме  новите стойности
            $fileNavArr[$fh]['prev'] = $prev;
            $fileNavArr[$fh]['next'] = $next;
            $fileNavArr[$fh]['srcDirName'] = $srcDirName;
            $fileNavArr[$fh]['allFilesArr'] = $allArhiveArr;
            $fileNavArr[$fh]['current'] = $cUrlStr;
            $fileNavArr[$fh]['src'] = $fRec->fileHnd;
            
            Mode::setPermanent('fileNavArr', $fileNavArr);
        } catch (ErrorException $e) {
            // Не правим нищо
        }
        
        // Изтриваме временните файлове
        $inst->deleteTempPath();
        
        // Връщаме манипулатора на файла
        return $fh;
    }
    
    
    /**
     * Извлича текстовата част от файла
     *
     * @param object|string $fRec - Записите за файла
     *
     * @return string|NULL
     */
    public static function extractText($fRec)
    {
        core_App::setTimeLimit(300);
        
        // Максимален брой файлове, на които ще се прави обработка
        $maxFileExtractCnt = 20;
        
        $params = array();
        $params['type'] = 'text';
        
        $dId = self::prepareLockId($fRec);
        $params['lockId'] = self::getLockId('text', $dId);
        
        if (is_object($fRec)) {
            $params['dataId'] = $fRec->dataId;
        }
        
        if (fileman_Indexes::isProcessStarted($params) || !core_Locks::get($params['lockId'], 1000, 0, false)) {
            
            return ;
        }
        
        // Дали ще се проверява съдържанието на архива
        $checInnerArchive = true;
        if (!is_object($fRec)) {
            $checInnerArchive = false;
        }
        
        if (is_object($fRec)) {
            try {
                $archiveInst = cls::get('archive_Adapter', array('fileHnd' => $fRec->fileHnd));
                
                $dataRec = fileman_Data::fetch($params['dataId']);
                $fLen = $dataRec->fileLen;
            } catch (ErrorException $e) {
                $archiveInst = false;
            }
        } else {
            try {
                $archiveInst = cls::get('archive_Adapter', array('path' => $fRec));
                $fLen = @filesize($fRec);
            } catch (ErrorException $e) {
                $archiveInst = false;
            }
        }
        
        $maxArchiveLen = archive_Setup::get('MAX_LEN');
        
        $text = '';
        
        // Ако не е над допустимия размер
        if ($archiveInst && ($maxArchiveLen > $fLen)) {
            try {
                $entriesArr = $archiveInst->getEntries();
            } catch (ErrorException $e) {
                self::logWarning("Грешка при обработка на архив - {$dId}: " . $e->getMessage());
                $entriesArr = array();
            }
            
            $text = '';
            
            $extractedCnt = 0;
            
            // Всички файлове в архива
            foreach ($entriesArr as $key => $entry) {
                $size = $entry->getSize();
                
                // Гледаме размера след разархивиране да не е много голям
                // Защита от "бомби" - от препълване на сървъра
                if ($size > archive_Setup::get('MAX_LEN')) {
                    continue;
                }
                
                $path = $entry->getPath();
                
                $text .= ' ' . $path;
                
                // Ако достигнем лимита, останалите файлове да не се проверяват
                if ($extractedCnt > $maxFileExtractCnt) {
                    continue;
                }
                
                try {
                    $extractedPath = $archiveInst->extractEntry($path);
                } catch (ErrorException $e) {
                    continue;
                }
                
                $ext = fileman_Files::getExt($path);
                
                $webdrvArr = fileman_Indexes::getDriver($ext);
                if (empty($webdrvArr)) {
                    continue;
                }
                
                $drvInst = false;
                foreach ($webdrvArr as $drv) {
                    if (!$drv) {
                        continue;
                    }
                    
                    if (!method_exists($drv, 'extractText')) {
                        continue;
                    }
                    
                    // За да не зацикля, когато има много архиви в самите архиви
                    if (!$checInnerArchive && ($drv instanceof fileman_webdrv_Archive)) {
                        continue;
                    }
                    
                    $drvInst = $drv;
                    
                    break;
                }
                
                if (!$drvInst) {
                    continue;
                }
                
                $eText = '';
                try {
                    $extractedCnt++;
                    
                    // Извличаме текстовата част от драйвера
                    $eText = $drvInst->extractText($extractedPath);
                } catch (ErrorException $e) {
                    reportException($e);
                }
                
                // Ако няма текст, правим опит да направим OCR
                if (!trim($eText)) {
                    $minSize = fileman_Indexes::$ocrIndexArr[$ext];
                    $eFileLen = @filesize($extractedPath);
                    if (isset($minSize) && ($eFileLen > $minSize) && ($eFileLen < fileman_Indexes::$ocrMax)) {
                        $filemanOcr = fileman_Setup::get('OCR');
                        
                        if ($filemanOcr && cls::load($filemanOcr, true)) {
                            $intf = cls::getInterface('fileman_OCRIntf', $filemanOcr);
                            
                            if ($intf && $intf->canExtract($extractedPath) && $intf->haveTextForOcr($extractedPath)) {
                                try {
                                    $eText = $intf->getTextByOcr($extractedPath);
                                    
                                    fileman_Data::logDebug('OCR обработка на файл в архив - ' . $path, $params['dataId']);
                                } catch (ErrorException $e) {
                                    reportException($e);
                                }
                            }
                        }
                    }
                }
                
                $text .= ' ' . $eText;
                
                // Изтриваме директорията, където е екстрактнат файла
                if (is_file($extractedPath) && is_readable($extractedPath)) {
                    if (isset($archiveInst->dir) && is_dir($archiveInst->dir)) {
                        if (pathinfo($archiveInst->path, PATHINFO_DIRNAME) != $archiveInst->dir) {
                            core_Os::deleteDir($archiveInst->dir);
                        }
                    }
                }
            }
            
            // Изтриваме временните файлове
            if (is_object($fRec)) {
                $archiveInst->deleteTempPath();
            } else {
                if (isset($archiveInst->dir) && is_dir($archiveInst->dir)) {
                    if (pathinfo($archiveInst->path, PATHINFO_DIRNAME) != $archiveInst->dir) {
                        core_Os::deleteDir($archiveInst->dir);
                    }
                }
            }
        }
        
        if (is_object($fRec)) {
            // Обновяваме данните за запис във fileman_Indexes
            $params['createdBy'] = core_Users::getCurrent('id');
            $params['content'] = $text;
            fileman_Indexes::saveContent($params);
        }
        
        core_Locks::release($params['lockId']);
        
        return $text;
    }
}
