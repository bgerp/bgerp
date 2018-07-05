<?php



/**
 * Клас 'cms_CopyTextPlg' - Плъгин за добавяне на линк към текущата страница при копиране на текст
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_CopyTextPlg extends core_Plugin
{
    
    /**
     * При аутпут
     */
    public static function on_Output(&$invoker)
    {
        // Взимане на конфигурацията на пакета
        $conf = core_Packs::getConfig('cms');
        
        // Текста, който трябва да се показва преди линка
        $textOnCopy = tr($conf->CMS_COPY_DEFAULT_TEXT);
        
        // За кои роли да е забранено показването на линка
        $disableFor = $conf->CMS_COPY_DISABLE_FOR;

        $symbolCount = $conf->CMS_COPY_ON_SYMBOL_COUNT;
    
        // Ако потребителя има някоя от забранените роли, не се добавя линка при копиране
        if (!haveRole($disableFor)) {
            $cUrl = cms_Content::getShortUrl();
            
            if (is_array($cUrl)) {
                $selfUrl = urlencode(toUrl($cUrl, 'absolute'));
                
                // подаване на съкратеното URL
                jquery_Jquery::run($invoker, "getShortURL('{$selfUrl}');");
                
                // Слагане на функцията при копиране
                jquery_Jquery::run($invoker, "document.oncopy = function(){addLinkOnCopy('{$textOnCopy}',{$symbolCount});}");
            }
        }
    }
}
