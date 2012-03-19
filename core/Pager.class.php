<?php



/**
 * Клас 'core_Pager' - Отговаря за странирането на резултати от заявка
 *
 *
 * @category  all
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Pager extends core_BaseClass
{
    
    
    /**
     * Мениджърът, който го използва
     */
    var $mvc;
    
    
    /**
     * Колко са общо резултатите
     */
    var $itemsCount;
    
    
    /**
     * Колко общо страници с резултати има
     */
    var $pagesCount;
    
    
    /**
     * Пореден номер на първия резултат за текущата страница
     */
    var $rangeStart;
    
    
    /**
     * Пореден номер на последния резултат за текущата страница
     */
    var $rangeEnd;
    
    
    /**
     * Колко записа съдържа една страница
     */
    var $itemsPerPage;
    
    
    /**
     * Номера на текущата страница
     */
    var $page;
    
    
    /**
     * На колко страници отстояние от първата и последната да оставя по една междинна
     */
    var $minPagesForMid = 20;
    
    
    /**
     * До колко страници около текущата да показва?
     */
    var $pagesAround = 5;
    
    
    /**
     * Инициализиране на обекта
     */
    function init($params = array())
    {
        parent::init($params);
        setIfNot($this->itemsPerPage, 20);
        setIfNot($this->pageVar, 'P');
        setIfNot($this->page, Request::get($this->pageVar, 'int'), 1);
    }
    
    
    /**
     * Изчислява индексите на първия и последния елемент от текущата страница и общия брой страници
     */
    function calc()
    {
        $this->rangeStart = NULL;
        $this->rangeEnd = NULL;
        $this->pagesCount = NULL;
        
        if (!($this->itemsCount >= 0))
        $this->itemsPerPage = 0;
        
        $maxPages = max(1, round($this->itemsCount / $this->itemsPerPage));
        
        if ($this->page > $maxPages) {
            $this->page = $maxPages;
        }
        
        $this->pagesCount = round($this->itemsCount / $this->itemsPerPage);
        
        if ($this->itemsCount > 0 && $this->pagesCount == 0) {
            $this->pagesCount = 1;
        }
        $this->rangeStart = 0;
        $this->rangeEnd = $this->itemsCount;
        
        $this->rangeStart = $this->itemsPerPage * ($this->page - 1);
        $this->rangeEnd = $this->rangeStart + $this->itemsPerPage;
        
        if (isset($this->itemsCount)) {
            if ($this->page == $this->pagesCount) {
                $this->rangeEnd = $this->itemsCount;
            } else {
                $this->rangeEnd = min($this->rangeEnd, $this->itemsCount);
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getItemsCount()
    {
        return $this->itemsCount;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getPagesCount()
    {
        return $this->pagesCount;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getPage()
    {
        return $this->page;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getRangeStart()
    {
        return $this->rangeStart;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getRangeLength()
    {
        return $this->getRangeEnd() - $this->getRangeStart();
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getRangeEnd()
    {
        return $this->rangeEnd;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function setLimit(&$query)
    {
        $q = clone ($query);
        $this->itemsCount = $q->count();
        $this->calc();
        
        if (isset($this->rangeStart) && isset($this->rangeEnd)) {
            $query->limit($this->rangeEnd - $this->rangeStart);
            $query->startFrom($this->rangeStart);
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getHtml($link = NULL)
    {
        // Ако не е зададен взема текущото URL за линк
        if (!isset($link)) {
            $link = $_SERVER['REQUEST_URI'];
        }
        
        if ($this->url) {
            $link = $this->url;
        }
        
        $start = $this->getPage() - $this->pagesAround;
        
        if ($start < 3) {
            $start = 1;
        }
        
        $end = $this->getPage() + $this->pagesAround - 1;
        
        if (($end > $this->getPagesCount()) || ($this->getPagesCount() - $end) < 2) {
            $end = $this->getPagesCount();
        }
        
        $html = '';
        
        if ($start < $end) {
            //Ако имаме страници, които не се показват в посока към началото, показваме <
            if ($this->getPage() > 1) {
                if ($start > 1) {
                    $html .= "<a href=\"" . Url::addParams($link, array($this->pageVar => 1)) . "\" class=\"pager\">1</a>";
                    
                    if ($start > $this->minPagesForMid) {
                        $mid = round($start / 2);
                        $html .= " .. ";
                        $html .= "<a href=\"" . Url::addParams($link, array($this->pageVar => $mid)) . "\" class=\"pager\">{$mid}</a>";
                        $html .= " .. ";
                    } else {
                        $html .= " ... ";
                    }
                }
            }
            
            do {
                $sel = "class=\"pager\"";
                
                if ($start == $this->getPage()) {
                    $sel = "class='pager pagerSelected'";
                }
                $html .= "<a href=\"" . Url::AddParams($link, array($this->pageVar => $start)) . "\"  $sel>{$start}</a> ";
            } while ($start++ < $end);
            
            //Ако имаме страници, които не се показват в посока към края, показваме >
            if ($this->getPage() < $this->getPagesCount()) {
                if ($end < $this->getPagesCount()) {
                    $mid = $this->getPagesCount() - $end;
                    
                    if ($mid > $this->minPagesForMid) {
                        $mid = round($mid / 2) + $end;
                        $html .= " .. ";
                        $html .= "<a href=\"" . Url::addParams($link, array($this->pageVar => $mid)) . "\" class=\"pager\">{$mid}</a>";
                        $html .= " .. ";
                    } else {
                        $html .= " ... ";
                    }
                    $html .= "<a href=\"" . Url::addParams($link, array($this->pageVar => $this->getPagesCount())) .
                    "\" class=\"pager\">" . $this->getPagesCount() . "</a>";
                }
            }
        }
        
        $tpl = new ET($html ? "<div style='margin:7px 0px 7px 0px;'>$html</div>" : "");
        
        return $tpl;
    }
}