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
     * Кой таб да е избран по подразбиране
     * @Override
     * @see fileman_webdrv_Generic::$defaultTab
     */
    static $defaultTab = 'content';
    
    
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
        $content = static::getContent($fRec);
        
        // Таб за съдържанието
		$tabsArr['content'] = (object) 
			array(
				'title'   => 'Съдържание',
				'html'    => "<div class='webdrvTabBody' style='white-space:pre-wrap;'><div class='webdrvFieldset'><div class='legend'>" . tr("Съдържание") . "</div>{$content}</div></div>",
				'order' => 7,
				'tpl' => $content,
			);
        
        return $tabsArr;
    }
    
    
    /**
     * Връща съдържанието на файла
     * 
     * @param object $fRec - Запис на архива
     * 
     * @return string - Съдържанието на файла, като код
     */
    static function getContent($fRec) 
    {
        // Вземаме съдържанието на файла
        $content = fileman_Files::getContent($fRec->fileHnd);
        
        // Вземаме разширението на файла, като тип
        $type = strtolower(fileman_Files::getExt($fRec->name));
        
        $content = mb_strcut($content, 0, 1000000);
        
    	$content = i18n_Charset::convertToUtf8($content, array('UTF-8' => 2, 'CP1251' => 0.5), TRUE);
        
        $content = core_Type::escape($content);
        
        // Обвиваме съдъжанието на файла в код
        $content = "<div class='richtext'><pre class='rich-text code {$type}'><code>{$content}</code></pre></div>";    
        
        $tpl = hljs_Adapter::enable('github');
        $tpl->append($content);
        
        return $tpl;
    }
    
    
	/**
     * Извлича текстовата част от файла
     * 
     * @param object $fRec - Записите за файла
     * 
     * @return NULL|string
     */
    static function extractText($fRec)
    {
        
        return fileman_webdrv_Text::extractText($fRec);
    }
}
