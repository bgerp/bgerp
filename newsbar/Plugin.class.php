<?php


/**
 * Клас 'newsbar_Plugin'
 *
 * Прихваща събитията на plg_ProtoWrapper и добавя, ако е има помощна информация в newsbar_Nesw, като бар лента
 *
 *
 * @category  bgerp
 * @package   newsbar
 *
 * @author    Gabriela Petrova <gpetrova@experta.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class newsbar_Plugin extends core_Plugin
{
    public static function on_Output(&$invoker)
    {
        if (!($invoker instanceof cms_page_External)) {
            
            return ;
        }
        
        // взимаме всички нови новини
        $newsArr = newsbar_News::getAllNews();
        
        foreach ($newsArr as $nRec) {
            $haveFooter = $haveHeader = false;
            
            if ($nRec->news !== null && $nRec->color !== null && $nRec->transparency !== null) {
                if (!$nRec->catGroups && !$nRec->eshopGroups && !$nRec->menu && !$nRec->articles && !$nRec->headerAndFooter) {
                    $nRec->headerAndFooter = 'header';
                }
                $headerAndFooter = type_Set::toArray($nRec->headerAndFooter);
                
                if (!empty($headerAndFooter)) {
                    
                    if (!$haveHeader && $headerAndFooter['header']) {
                        $html = self::getMarqueeText($nRec);
                        
                        $invoker->appendOnce($html, 'PAGE_HEADER');
                        
                        $haveHeader = true;
                    }
                    
                    if (!$haveFooter && $headerAndFooter['footer']) {
                        $htmlFooter = self::getMarqueeText($nRec, 'newsbarFooter');
                        $invoker->appendOnce($htmlFooter, 'PAGE_FOOTER');
                        
                        $haveFooter = true;
                    }
                }
                
                if ($haveFooter && $haveHeader) {
                    break;
                }
            }
        }
    }
    
    
    /**
     *
     * @param stdClass $nRec
     *
     * @return string
     */
    protected static function getMarqueeText($nRec, $class = 'newsbar', $marquee = true)
    {
        static $resArr = array();
        $hash = md5(serialize($nRec) . '|' . $class . '|' . $marquee);
        
        if (!$resArr[$hash]) {
            $convertText = cls::get('type_Richtext');
            
            $html = newsbar_News::generateHTML($nRec);
            $html->replace($class, 'class');
            if ($marquee) {
                $html->replace("<marquee scrollamount='4'>", 'marquee');
                $html->replace('</marquee>', 'marquee2');
            }
            
            $resArr[$hash] = $html->getContent();
        }
        
        return $resArr[$hash];
    }
    
    
    /**
     * След като е готово вербалното представяне
     */
    public static function on_AfterGetVerbal($mvc, &$res, $rec, $part)
    {
        if (!($mvc instanceof cms_Articles) || ($part != 'body')) {
            
            return ;
        }
        
        $newsArr = newsbar_News::getAllNews();
        
        foreach ($newsArr as $nRec) {
            if (!$nRec->articles) {
                continue;
            }
            $articlesArr = type_Keylist::toArray($nRec->articles);
            
            if (!$articlesArr[$rec->id]) {
                continue;
            }
            
            $html = self::getMarqueeText($nRec, 'articlesNewsbar');
            
            $res->prepend($html);
            
            break;
        }
    }
}
