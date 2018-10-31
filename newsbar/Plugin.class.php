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
    /**
     *
     * @param core_ET $invoker
     */
    public static function on_Output(&$invoker)
    {
        if (!($invoker instanceof cms_page_External)) {
            
            return ;
        }
        
        // взимаме всички нови новини
        $newsArr = newsbar_News::getAllNews();
        
        $haveFooter = $haveHeader = false;
        
        foreach ($newsArr as $nRec) {
            if ($nRec->news !== null && $nRec->color !== null && $nRec->transparency !== null) {
                if (!$nRec->eshopProducts && !$nRec->eshopGroups && !$nRec->menu && !$nRec->articles && !$nRec->headerAndFooter) {
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
                        $htmlFooter = self::getMarqueeText($nRec, 'newsbarCustom');
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
     * След като е готово вербалното представяне
     *
     * @param core_Mvc $mvc
     * @param core_ET  $res
     * @param stdClass $rec
     * @param string   $part
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
            
            $html = self::getMarqueeText($nRec, 'newsbarCustom articleNewsbar');
            
            if ($res instanceof core_ET) {
                $res->prepend($html);
            } else {
                $res = $html . $res;
            }
            
            break;
        }
    }
    
    
    /**
     * След като се приготви менюто
     *
     * @param core_Mvc $mvc
     * @param core_ET  $res
     */
    public static function on_AfterGetLayout($mvc, &$res)
    {
        if (!($mvc instanceof cms_Content)) {
            
            return ;
        }
        
        $cMenuId = Mode::get('cMenuId');
        
        $newsArr = newsbar_News::getAllNews();
        
        foreach ($newsArr as $nRec) {
            if (!$nRec->menu) {
                continue;
            }
            $menusArr = type_Keylist::toArray($nRec->menu);
            
            if (!$menusArr[$cMenuId]) {
                continue;
            }
            
            $html = self::getMarqueeText($nRec, 'newsbarCustom');
            
            if ($res instanceof core_ET) {
                $res->prepend($html);
            } else {
                $res = $html . $res;
            }
            
            break;
        }
    }
    
    
    /**
     * След като се рендира групата в магазина
     *
     * @param core_Mvc $mvc
     * @param core_ET  $res
     * @param stdClass $data
     */
    public static function on_AfterRenderGroup($mvc, &$res, $data)
    {
        if (!($mvc instanceof eshop_Groups)) {
            
            return ;
        }
        
        if (!$data->rec->id) {
            
            return ;
        }
        
        $newsArr = newsbar_News::getAllNews();
        
        foreach ($newsArr as $nRec) {
            if (!$nRec->eshopGroups) {
                continue;
            }
            $eshopGroupsArr = type_Keylist::toArray($nRec->eshopGroups);
            
            if (!$eshopGroupsArr[$data->rec->id]) {
                continue;
            }
            
            $html = self::getMarqueeText($nRec, 'newsbarCustom eshopGroupsNewsbar');
            
            if ($res instanceof core_ET) {
                $res->prepend($html);
            } else {
                $res = $html . $res;
            }
            
            break;
        }
    }
    
    
    /**
     * След като се рендира продукта в онлайн магазина
     *
     * @param core_Mvc $mvc
     * @param core_ET  $res
     * @param stdClass $data
     */
    public static function on_AfterRenderProduct($mvc, &$res, $data)
    {
        if (!($mvc instanceof eshop_Products)) {
            
            return ;
        }
        
        $newsArr = newsbar_News::getAllNews();
        
        foreach ($newsArr as $nRec) {
            if (!$nRec->eshopProducts) {
                continue;
            }
            $eshopGroupsArr = type_Keylist::toArray($nRec->eshopProducts);
            
            if (!$eshopGroupsArr[$data->rec->id]) {
                continue;
            }
            
            $html = self::getMarqueeText($nRec, 'newsbarCustom eshopGroupsNewsbar');
            
            if ($res instanceof core_ET) {
                $res->prepend($html);
            } else {
                $res = $html . $res;
            }
            
            break;
        }
    }
    
    
    /**
     * Помощна функция за подготвяне на текста
     *
     * @param stdClass $nRec
     * @param string   $class
     * @param bool     $marquee
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
}
