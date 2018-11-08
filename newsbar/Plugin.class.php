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
     * Името на полето в сесията
     */
    public static $newsArrToShowName = 'newsArrToShow';
    
    
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
        
        // Ако е зададено да се показва във външната част
        foreach ($newsArr as $nRec) {
            if (!$nRec->eshopProducts && !$nRec->eshopGroups && !$nRec->menu && !$nRec->articles && !$nRec->headerAndFooter) {
                self::addNewsToShow($nRec);
            }
        }
        
        // Показваме всички добавяни данни в сесията
        $newsArr = Mode::get(self::$newsArrToShowName);
        if ($newsArr) {
            foreach ($newsArr as $nRec) {
                switch ($nRec->position) {
                    case 'bottomHeader':
                        $placeholderName = 'BOTTOM_HEADER';
                        break;
                    case 'topPage':
                        $placeholderName = 'TOP_PAGE';
                        break;
                    case 'bottomMenu':
                        $placeholderName = 'BOTTOM_MENU';
                        break;
                    case 'topConten':
                        $placeholderName = 'TOP_CONTENT';
                        break;
                    case 'bottomContent':
                        $placeholderName = 'BOTTOM_CONTENT';
                        break;
                    case 'topNav':
                        $placeholderName = 'TOP_NAV';
                        break;
                    case 'bottomNav':
                        $placeholderName = 'BOTTOM_NAV';
                        break;
                    case 'beforeFooter':
                        $placeholderName = 'BEFORE_FOOTER';
                        break;
                    case 'footer':
                        if (Mode::is('screenMode', 'narrow')) {
                            $placeholderName = 'FOOTER_CENTER_NARROW';
                        } else {
                            $placeholderName = 'FOOTER_CENTER_WIDE';
                        }
                        break;
                    case 'afterFooter':
                        $placeholderName = 'AFTER_FOOTER';
                        break;
                    default:
                        $placeholderName = 'BOTTOM_HEADER';
                        break;
                }
                $className = ($placeholderName == 'BOTTOM_HEADER') ? 'newsbar' : 'newsbarCustom';
                $html = self::getTextToShow($nRec, $className);
                $invoker->appendOnce($html, $placeholderName);
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
            
            self::addNewsToShow($nRec);
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
            
            self::addNewsToShow($nRec);
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
            
            self::addNewsToShow($nRec);
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
            
            self::addNewsToShow($nRec);
        }
    }
    
    
    /**
     * Помощна функция за добавяне на записа в сесията
     *
     * @param stdClass $nRec
     */
    protected static function addNewsToShow($nRec)
    {
        $newsArr = Mode::get(self::$newsArrToShowName);
        if (!$newsArr) {
            $newsArr = array();
        }
        $newsArr[$nRec->position] = $nRec;
        
        Mode::set(self::$newsArrToShowName, $newsArr);
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
    protected static function getTextToShow($nRec, $class = 'newsbar')
    {
        static $resArr = array();
        $hash = md5(serialize($nRec) . '|' . $class);
        
        if (!$resArr[$hash]) {
            $convertText = cls::get('type_Richtext');
            
            $html = newsbar_News::generateHTML($nRec);
            $html->replace($class, 'class');
            if ($nRec->moving != 'no') {
                $html->replace("<marquee scrollamount='4'>", 'marquee');
                $html->replace('</marquee>', 'marquee2');
            }
            
            $resArr[$hash] = $html->getContent();
        }
        
        return $resArr[$hash];
    }
}
