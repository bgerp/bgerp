<?php


/**
 * Драйвер за работа с .kmz файлове.
 * 
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Kmz extends fileman_webdrv_Kml
{
    
    
	/**
     * Връща всички табове, които ги има за съответния файл
     * 
     * @param object $fRec - Записите за файла
     * 
     * @return array
     * 
     * @Override
     * @see fileman_webdrv_Office::getTabs
     */
    static function getTabs($fRec)
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
				'title'   => 'Съдържание',
				'html'    => "<div class='webdrvTabBody' style='white-space:pre-wrap;'><div class='webdrvFieldset'><div class='legend'>" . tr("Съдържание") . "</div>{$contentStr}</div></div>",
				'order' => 7,
			);
        
        return $tabsArr;
    }
    
    
    /**
     * Преглед на kml файла
     * 
     * @param object $fRec - Записите за файла
     * 
     * @return ET
     */
    public static function renderView($fRec)
    {
        $kml = fileman_Indexes::getInfoContentByFh($fRec->fileHnd, 'kml');
        
        $error = FALSE;
        
        if ($kml === FALSE) {
            
            // throw fileman_Exception - ако размера е над допустимия за обработка,
            // трябва да го прихванеш
            try {
                $archiveInst = fileman_webdrv_Archive::getArchiveInst($fRec);
            } catch(fileman_Exception $e) {
                self::logWarning($e->getMessage());
                $error = TRUE;
            }
            
            if (!$error) {
                try {
                    $entriesArr = $archiveInst->getEntries();
                } catch (ErrorException $e) {
                    self::logWarning($e->getMessage());
                    $error = TRUE;
                }
            }
            
            if (!$error) {
                foreach ($entriesArr as $key => $entry) {
                    $size = $entry->getSize();
                
                    if (!$size) continue;
                
                    // Гледаме размера след разархивиране да не е много голям
                    // Защита от "бомби" - от препълване на сървъра
                    if ($size > ARCHIVE_MAX_FILE_SIZE_AFTER_EXTRACT) continue;
                
                    $path = $entry->getPath();
                
                    $ext = pathinfo($path, PATHINFO_EXTENSION);
                
                    if (!$ext) continue;
                
                    $ext = strtolower($ext);
                
                    if ($ext != 'kml') continue;
                
                    // След като открием файла който ще пратим към VT
                    try {
                        $extractedPath = $archiveInst->extractEntry($path);
                    } catch (ErrorException $e) {
                        continue;
                    }
                
                    if (!is_file($extractedPath)) {
                        continue;
                    }
                    $kml = fileman::absorb($extractedPath, 'archive');
                
                    if ($kml) {
                        $archiveInst->deleteTempPath();
                        break;
                    } else {
                        continue;
                    }
                }
                
                $params = array();
                $params['dataId'] = $fRec->dataId;
                $params['type'] = 'kml';
                $params['createdBy'] = core_Users::getCurrent();
                
                if ($kml) {
                    $params['content'] = fileman_Indexes::prepareContent(array($kml));
                    fileman_Indexes::saveContent($params);
                } else {
                    fileman_Indexes::createError($params);
                }
            }
        } else {
            $kmlArr = fileman_Indexes::decodeContent($kml);
            $kml = $kmlArr[0];
        }
        
        $kmlRec = fileman::fetchByFh($kml);
        
        if ($kmlRec) {
            
            return parent::renderView($kmlRec);
        } else {
            
            return tr("Грешка при показване на KML файл");
        }
    }
}
