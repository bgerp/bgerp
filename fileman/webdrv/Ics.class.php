<?php


/**
 * Драйвер за работа с *.ics файлове
 *
 * @category  vendors
 * @package   fileman
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class fileman_webdrv_Ics extends fileman_webdrv_Code
{
    /**
     * Кой таб да е избран по подразбиране
     *
     * @Override
     *
     * @see fileman_webdrv_Code::$defaultTab
     */
    public static $defaultTab = 'events';
    
    
    /**
     * Връща всички табове, които ги има за съответния файл
     *
     * @param object $fRec - Записите за файла
     *
     * @return array
     *
     * @Override
     *
     * @see fileman_webdrv_Code::getTabs
     */
    public static function getTabs($fRec)
    {
        // Вземаме табовете от родителя
        $tabsArr = parent::getTabs($fRec);
        
        // Вземаме съдържанието
        $events = static::getEvents($fRec);
        
        // Таб за съдържанието
        $tabsArr['events'] = (object)
            array(
                'title' => 'Събития',
                'html' => "<div class='webdrvTabBody' style='white-space:pre-wrap;'><div class='legend'>" . tr('Събития') . "</div><div class='webdrvFieldset'>{$events}</div></div>",
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
    public static function getEvents($fRec)
    {
        // Вземаме съдържанието на файла
        $content = fileman_Files::getContent($fRec->fileHnd);

        $content = trim($content);
        
        $content = mb_strcut($content, 0, 1000000);
        
        $content = i18n_Charset::convertToUtf8($content, 'UTF-8');
        
        $parsedTpl = ical_Parser::renderEvents($content);
        
        return $parsedTpl;
    }


    /**
     * Връща съдържанието на файла
     *
     * @param object $fRec - Запис на архива
     *
     * @return string - Съдържанието на файла, като код
     */
    public static function getContent($fRec)
    {
        // Вземаме съдържанието на файла
        $content = fileman_Files::getContent($fRec->fileHnd);

        $content = i18n_Charset::convertToUtf8($content, 'UTF-8');

        // Вземаме разширението на файла, като тип
        $type = strtolower(fileman_Files::getExt($fRec->name));

        $content = mb_strcut($content, 0, 1000000);

        $content = i18n_Charset::convertToUtf8($content, array('UTF-8' => 2, 'CP1251' => 0.5), true);

        $content = core_Type::escape($content);

        // Обвиваме съдъжанието на файла в код
        $content = "<div class='richtext'><pre class='rich-text code {$type}'><code>{$content}</code></pre></div>";

        $tpl = hljs_Adapter::enable('github');
        $tpl->append($content);

        return $tpl;
    }
}
