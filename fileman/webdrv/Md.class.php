<?php


/**
 * Драйвер за работа с .md файлове.
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
class fileman_webdrv_Md extends fileman_webdrv_Text
{
    /**
     * Кой таб да е избран по подразбиране
     *
     * @Override
     *
     * @see fileman_webdrv_Generic::$defaultTab
     */
    public static $defaultTab = 'view';


    /**
     * Връща всички табове, които ги има за съответния файл
     *
     * @param object $fRec - Записът за файла
     *
     * @return array
     *
     * @Override
     *
     * @see fileman_webdrv_Text::getTabs
     */
    public static function getTabs($fRec)
    {
        $tabsArr = parent::getTabs($fRec);

        $view = static::getView($fRec);

        $resArray = self::getArrows($fRec);
        $prevLink = $resArray['prevLink'];
        $nextLink = $resArray['nextLink'];

        $tabsArr['view'] = (object) array(
            'title' => 'Изглед',
            'html' => "<div class='webdrvTabBody'><div class='webdrvFieldset'>{$prevLink}{$nextLink}{$view}</div></div>",
            'order' => 6,
            'tpl' => $view,
        );

        return $tabsArr;
    }


    /**
     * Връща визуализираното Markdown съдържание на файла
     *
     * @param object $fRec - Записът за файла
     *
     * @return core_ET
     */
    public static function getView($fRec)
    {
        $content = fileman_Files::getContent($fRec->fileHnd);

        $content = i18n_Charset::convertToUtf8($content);
        $content = mb_strcut($content, 0, 1000000);

        $richText = cls::get('type_Richtext');
        $view = $richText->toVerbal("[md]{$content}[/md]");

        // Markdown файловете са външно съдържание и могат да съдържат опасен HTML
        $view->setContent(hclean_Purifier::clean($view->content, 'UTF-8'));

        return $view;
    }
}
