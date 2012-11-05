<?php


/**
 * Драйвер за работа със source файлове
 * 
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Code extends fileman_webdrv_Generic
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
        
        // Вземаме съдържанието
        $contentStr = static::getContent($fRec);
        
        // Таб за съдържанието
		$tabsArr['content'] = (object) 
			array(
				'title'   => 'Съдържание',
				'html'    => "<div class='webdrvTabBody' style='white-space:pre-wrap;'><fieldset class='webdrvFieldset'><legend>Съдържание</legend>{$contentStr}</fieldset></div>",
				'order' => 1,
			);
        
        return $tabsArr;
    }
    
    
    /**
     * Връща съдържанието на файла
     * 
     * @param fileman_Files $frec - Запис на архива
     * @param string $type - Ако е задеден типа на кода
     * 
     * @return string $content - Съдържанието на файла, като код
     */
    static function getContent($fRec) 
    {
        // Вземаме съдържанието на файла
        $content = fileman_Files::getContent($fRec->fileHnd);
        
        // Вземаме разширението на файла, като тип
        $type = strtolower(fileman_Files::getExt($fRec->name));
        
        // Обвиваме съдъжанието на файла в код
        $content = "[code={$type}]{$content}[/code]";    
        
        // Инстанция на класа
        $richTextInst = cls::get('type_Richtext');
        
        // Вземаме съдържанието
        $content = $richTextInst->toVerbal($content);
        
        return $content;
    }
}