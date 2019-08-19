<?php


/**
 * Драйвер за работа с .json файлове.
 *
 * @category  vendors
 * @package   fileman
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class fileman_webdrv_Json extends fileman_webdrv_Code
{
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
        
        $content = json_encode(json_decode($content), JSON_PRETTY_PRINT); 

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
