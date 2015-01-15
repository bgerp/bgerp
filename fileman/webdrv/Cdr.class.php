<?php


/**
 * Драйвер за работа с .cdr файлове.
 * 
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Cdr extends fileman_webdrv_Image
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
     * Връща шаблон с превюто на файла
     * 
     * @param object $fRec - Записите за файла
     * 
     * @return core_Et|string - Шаблон с превюто на файла
     * 
     * @Override
     * @see fileman_webdrv_Image::getThumbPrev
     */
    static function getThumbPrev($fRec) 
    {
        try {
            
            // Опитваме се да вземем thumbnail на файла
            try {
                // Инстанция на архива
                $zip = static::getArchiveInst($fRec);
                
                // Вземаме съдържанието на thumbnail файла
                $fileContent = $zip->getFromName('metadata/thumbnails/thumbnail.bmp');
                
                // Очакваме да има съдържание
                expect($fileContent, 'Thumbnail файлът няма съдържание.');
                
                // Инстанция на fileman
                $filesInst = cls::get('fileman_Files');
                
                // Вземаме името на файла
                $nameArr = fileman_Files::getNameAndExt($fRec->name);
                
                // Създаваме новото име на файла
                $name = $nameArr['name'] . '_thumb.bmp';
                
                // Добавяме файла в кофата
                $fh = $filesInst->addNewFileFromString($fileContent, 'archive', $name);
                    
                // Вземаме записа за новосъздадения файл
                $nRec = fileman_Files::fetchByFh($fh);
                
                // Стартираме процеса на конвертиране към JPG формат
                fileman_webdrv_Bmp::convertToJpg($nRec);
                
                // Показваме thumbnail'а
                return fileman_webdrv_Bmp::getThumbPrev($nRec);
            } catch (fileman_Exception $e) {
                   
                // Връщаме грешката
                return $e->getMessage();    
                
            }
        } catch (core_exception_Expect $e) {
            
            return "Не може да се покаже прегледа на файла.";
        }
    }
}
