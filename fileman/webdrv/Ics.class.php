<?php


/**
 * Драйвер за работа с *.ics файлове
 * 
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Ics extends fileman_webdrv_Code
{
    
    
    /**
     * Кой таб да е избран по подразбиране
     * @Override
     * @see fileman_webdrv_Code::$defaultTab
     */
    static $defaultTab = 'events';
    
    
    /**
     * Връща всички табове, които ги има за съответния файл
     * 
     * @param object $fRec - Записите за файла
     * 
     * @return array
     * 
     * @Override
     * @see fileman_webdrv_Code::getTabs
     */
    static function getTabs($fRec)
    {
        // Вземаме табовете от родителя
        $tabsArr = parent::getTabs($fRec);
        
        // Вземаме съдържанието
        $events = static::getEvents($fRec);
        
        // Таб за съдържанието
		$tabsArr['events'] = (object) 
			array(
				'title'   => 'Събития',
				'html'    => "<div class='webdrvTabBody' style='white-space:pre-wrap;'><div class='webdrvFieldset'><div class='legend'>" . tr("Събития") . "</div>{$events}</div></div>",
				'order' => 3,
				'tpl' => $events,
			);
        
        return $tabsArr;
    }
    
    
    /**
     * Връща съдържанието на файла
     * 
     * @param object $fRec - Запис на архива
     * 
     * @return core_ET - Съдържанието на файла, като код
     */
    static function getEvents($fRec) 
    {
        // Вземаме съдържанието на файла
        $content = fileman_Files::getContent($fRec->fileHnd);
        
        $content = trim($content);
        
        $content = mb_strcut($content, 0, 1000000);
        
    	$content = i18n_Charset::convertToUtf8($content);
    	
    	$parsedTpl = ical_Parser::renderEvents($content);
    	
    	return $parsedTpl;
    }
}
