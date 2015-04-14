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
     * Екшън за показване превю
     */
    function act_Preview()
    {
        // Очакваме да има права за виждане
        $this->requireRightFor('view');
        
        // Манипулатора на файла
        $fileHnd = Request::get('id');
        
        // Вземаме записа за файла
        $fRec = fileman_Files::fetchByFh($fileHnd);
        
        // Очакваме да има права за разглеждане на записа
        $this->requireRightFor('view', $fRec);
        
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
                
                $bmpInst = cls::get('fileman_webdrv_Bmp');
                
                // Стартираме процеса на конвертиране към JPG формат
                $bmpInst->convertToJpg($nRec);
                
                Request::push(array('id' => $nRec->fileHnd));
                
                // Показваме thumbnail'а
                return $bmpInst->act_Preview();
            } catch (fileman_Exception $e) {
                
                // Сменяме мода
                Mode::set('wrapper', 'page_PreText');
                
                // Връщаме грешката
                return $e->getMessage();    
            }
        } catch (core_exception_Expect $e) {
            
            return "Не може да се покаже прегледа на файла.";
        }
    }
}
