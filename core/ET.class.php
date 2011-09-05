<?php

/**
 * Клас  'core_ET' ['ET'] - Система от текстови шаблони
 *
 *
 * @category   Experta Framework
 * @package    core
 * @author     Milen Georgiev
 * @copyright  2006-2010 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class core_ET extends core_BaseClass
{
    
    
    /**
     * Съдържание на шаблона
     */
    var $content;
    
    
    /**
     * Копие на шаблона
     */
    var $contentBackup;
    
    
    /**
     * Място за заместване по подразбиране
     */
    var $defaultPlace;
    
    
    /**
     * Масив с блокове
     */
    var $blocks = array();
    
    
    /**
     * Масив с плейсхолдъри
     */
    var $places = array();
    
    
    /**
     * Масив с хешове на съдържание, което се замества еднократно
     */
    var $once = array();
    
    
    /**
     * Чакащи замествания
     */
    var $pending = array();
    
    
    /**
     * 'Изчезваеми' блокове
     */
    var $removableBlocks = array();
    
    
    /**
     * Указател към 'мастер' шаблона
     */
    var $master;
    
    
    /**
     * Името на детайла
     */
    var $detailName;
    
    
    /**
     * Конструктор на шаблона
     */
    function core_ET($content = "")
    {
        if (is_a($content, 'core_ET') || is_a($content, 'ET')) {
            $this->content = $content->content;
            $this->places = $content->places;
            $this->once = $content->once;
            $this->pending = $content->pending;
            $this->blocks = $content->blocks;
            $this->removableBlocks = $content->removableBlocks;
        } else {
            $this->content = $content;
        }
        
        $this->setRemovableBlocks();
        
        // Взема началните плейсхолдъри, за да могат непопълнените да бъдат изтрити
        $rmPlaces = $this->getPlaceHolders();
        
        if(count($rmPlaces)) {
            foreach($rmPlaces as $place) {
                $this->removablePlaces[$place] = $place;
            }
        }
        
        // Всички следващи аргументи, ако има такива се заместват на 
        // плейсхолдъри с имена [#1#], [#2#] ...
        $args = func_get_args();
        
        if (($n = count($args)) > 1) {
            for ($i = 1; $i < $n; $i++) {
                $this->replace($args[$i], $i);
            }
        }
    }
    
    
    /**
     * Добава обгаждащите символи къ даден стринг,
     * за да се получи означение на плейсхолдър
     */
    function toPlace($name)
    {
        return "[#{$name}#]";
    }
    
    
    /**
     * Превръща име към означение за начало на блок
     */
    function toBeginMark($blockName)
    {
        return "<!--ET_BEGIN $blockName-->";
    }
    
    
    /**
     * Превръща име към означение за край на блок
     */
    function toEndMark($blockName)
    {
        return "<!--ET_END $blockName-->";
    }
    
    
    /**
     * Намира позициите на маркетите за начало и край на блок
     */
    function getMarkerPos($blockName)
    {
        $beginMark = $this->toBeginMark($blockName);
        $endMark = $this->toEndMark($blockName);
        $markerPos->beginStart = strpos($this->content, $beginMark);
        
        if ($markerPos->beginStart === FALSE)
        return FALSE;
        $markerPos->beginStop = $markerPos->beginStart + strlen($beginMark);
        $markerPos->endStart = strpos($this->content, $endMark, $markerPos->beginStop);
        
        if ($markerPos->endStart === FALSE)
        return FALSE;
        $markerPos->endStop = $markerPos->endStart + strlen($endMark);
        
        return $markerPos;
    }
    
    
    /**
     * Връща даден блок
     */
    function getBlock($blockName)
    {
        if (is_object($this->blocks[$blockName])) {
            
            return $this->blocks[$blockName];
        }
        
        $placeHolder = $this->toPlace(strtoupper($blockName));
        
        $mp = $this->getMarkerPos($blockName);
        
        expect(is_object($mp), 'Не може да бъде открит блока ' . $blockName);
        
        $newTemplate = new ET(substr($this->content, $mp->beginStop,
        $mp->endStart - $mp->beginStop));
        $newTemplate->master =& $this;
        $newTemplate->detailName = $blockName;
        
        $this->content = substr($this->content, 0, $mp->beginStart) .
        $placeHolder .
        substr($this->content, $mp->endStop, strlen($this->content) - $mp->endStop);
        
        $this->places[strtoupper($blockName)] = 1;
        $this->blocks[$blockName] = $newTemplate;
        $newTemplate->backup();
        
        return $newTemplate;
    }
    
    
    /**
     * ,
     * removeBlocks()
     * ,
     */
    function setRemovableBlocks($blocks = NULL)
    {
        if($blocks) {
            $blocks = arr::make($blocks, TRUE);
            
            foreach ($blocks as $blockName) {
                
                $mp = $this->getMarkerPos($blockName);
                
                expect(is_object($mp));
                
                $content = substr($this->content, $mp->beginStop,
                $mp->endStart - $mp->beginStop);
                $this->removableBlocks[$blockName] = md5($content);
            }
        } else {
            $blocks = $this->getPlaceHolders();
            
            foreach($blocks as $b) {
                
                $mp = $this->getMarkerPos($b);
                
                if(is_object($mp)) {
                    $content = substr($this->content,
                    $mp->beginStop,
                    $mp->endStart - $mp->beginStop);
                    $this->removableBlocks[$b] = md5($content);
                }
            }
        }
    }
    
    
    /**
     * ,
     */
    function removeBlocks()
    {
        if (count($this->removableBlocks)) {
            foreach ($this->removableBlocks as $blockName => $md5) {
                $mp = $this->getMarkerPos($blockName);
                
                if ($mp) {
                    $content = substr($this->content, $mp->beginStop,
                    $mp->endStart - $mp->beginStop);
                    
                    if ($md5 == md5($content)) {
                        $content = '';
                    }
                    $this->content = substr($this->content, 0, $mp->beginStart) .
                    $content .
                    substr($this->content, $mp->endStop,
                    strlen($this->content) - $mp->endStop);
                }
            }
        }
        
        if($this->removablePlaces) {
            
            foreach($this->removablePlaces as $p) {
                $place = $this->toPlace($p);
                $this->content = str_replace($place, '', $this->content);
                // Debug::log('Изтрит плейсхолдър: ' . $place);
            }
        }
    }
    
    
    /**
     *
     */
    function backup()
    {
        $this->contentBackup = $this->content;
    }
    
    
    /**
     *
     */
    function restore()
    {
        $this->content = $this->contentBackup;
        $this->places = array();
        $this->once = array();
        $this->pending = array();
    }
    
    
    /**
     * master-,
     * master-
     */
    function append2Master()
    {
        if (is_object($this->master)) {
            $this->master->append($this, $this->detailName);
            $this->restore();
        }
    }
    
    
    /**
     * master-,       master-
     */
    function replace2Master()
    {
        if (is_object($this->master)) {
            $this->master->replace($this, $this->detailName);
            $this->restore();
        }
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function prepend2Master()
    {
        if (is_object($this->master)) {
            $this->master->prepend($this, $this->detailName);
            $this->restore();
        }
    }
    
    
    /**
     * -
     */
    function preparePlace($place)
    {
        if ($place === NULL) {
            return $this->toPlace($this->defaultPlace);
        } else {
            $this->places[$place] = 1;
            
            return $this->toPlace($place);
        }
    }
    
    
    /**
     * ,    ,   ,
     * - HTML
     */
    function escape($str)
    {
        return str_replace('[#', '&#91;#', $str);
    }
    
    
    /**
     *
     */
    function addSubstitution($str, $place, $once, $mode)
    {
        $i = count($this->pending);
        $this->pending[$i]->str = $str;
        $this->pending[$i]->place = $place;
        $this->pending[$i]->once = $once;
        $this->pending[$i]->mode = $mode;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function push($value, $place, $once = FALSE)
    {
        if (is_array($value)) {
            foreach ($value as $v) {
                $this->addSubstitution($v, $place, $once, 'push');
            }
        } else {
            $this->addSubstitution($value, $place, $once, 'push');
        }
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function getArray($place)
    {
        if (count($this->pending)) {
            foreach ($this->pending as $sub) {
                if ($sub->place == $place && $sub->mode == 'push') {
                    if ($sub->once) {
                        $md5 = md5($sub->str);
                        
                        if ($this->once[$md5])
                        continue;
                        $this->once[$md5] = TRUE;
                    }
                    $res[] = $sub->str;
                }
            }
        }
        
        return $res;
    }
    
    
    /**
     * ,
     */
    function processContent($content)
    {
        if (is_a($content, "et") || is_a($content, "core_Et")) {
            //   
            foreach ($content->pending as $sub) {
                switch ($sub->mode) {
                    case "append":
                        $this->append(new ET($sub->str), $sub->place, $sub->once);
                        break;
                    case "prepend":
                        $this->prepend(new ET($sub->str), $sub->place, $sub->once);
                        break;
                    case "replace":
                        $this->replace(new ET($sub->str), $sub->place, $sub->once);
                        break;
                    case "push":
                        $this->push($sub->str, $sub->place, $sub->once);
                        break;
                }
            }
            
            // Прехвърля в Master шаблона всички appendOnce хешове
            if(count($content->once)) {
                foreach ($content->once as $md5 => $true) {
                    $this->once[$md5] = TRUE;
                }
            }
            
            // Прехвърля в мастер шаблона всички плейсхолдери, които трябва да се заличават
            if(count($content->removablePlaces)) {
                foreach ($content->removablePlaces as $place) {
                    $this->removablePlaces[$place] = $place;
                }
            }
            
            return $content->getContent(NULL, 'CONTENT', FALSE, FALSE);
        } else {
            return $this->escape($content);
        }
    }
    
    
    /**
     *
     */
    function importRemovableBlocks($content)
    {
        if (is_a($content, "et") || is_a($content, "core_Et")) {
            if (count($content->removableBlocks)) {
                foreach ($content->removableBlocks as $name => $md5) {
                    $this->removableBlocks[$name] = $md5;
                }
            }
        }
    }
    
    
    /**
     *
     */
    function sub($content, $placeHolder, $once, $mode)
    {
        if ($content === NULL) return;
        
        if ($once) {
            if (is_a($content, "et") || is_a($content, "core_Et")) {
                $str = serialize($content);
            } else {
                $str = $content;
            }
            $md5 = md5($str);
            
            if ($this->once[$md5]) {
                
                return FALSE;
            }
        }
        
        $this->importRemovableBlocks($content);
        
        $str = $this->processContent($content);
        
        $place = $this->preparePlace($placeHolder);
        
        if (strpos($this->content, $place) !== FALSE) {
            
            if ($once) {
                $this->once[$md5] = TRUE;
            }
            
            switch ($mode) {
                case "append":
                    $new = $str . $place;
                    break;
                case "prepend":
                    $new = $place . $str;
                    break;
                case "replace":
                    $new = $str;
                    break;
            }
            
            $this->content = str_replace($place, $new, $this->content);
        } else {
            if ($placeHolder == NULL) {
                switch ($mode) {
                    case "append":
                        $this->content = $this->content . $str;
                        break;
                    case "prepend":
                        $this->content = $str . $this->content;
                        break;
                    case "replace":
                        $this->content = $str;
                        break;
                }
            } else {
                $this->addSubstitution($str, $placeHolder, $once, $mode);
            }
        }
    }
    
    
    /**
     * Заместване след плейсхолдъра
     */
    function append($content, $placeHolder = NULL, $once = FALSE)
    {
        return $this->sub($content, $placeHolder, $once, "append");
    }
    
    
    /**
     * Заместване след пелйсхолдъра.
     * Всички опити за използване на същото съдържание ще бъдат игнорирани
     */
    function appendOnce($content, $placeHolder = NULL)
    {
        return $this->append($content, $placeHolder, TRUE);
    }
    
    
    /**
     * Заместване преди пелйсхолдъра
     */
    function prepend($content, $placeHolder = NULL, $once = FALSE)
    {
        return $this->sub($content, $placeHolder, $once, "prepend");
    }
    
    
    /**
     * Замества посочения плейсходер със съдържанието. Може да се зададе
     * еднократно вкарване на съдържанието при което всички последващи опити
     * за заместване на същото съдържание, ще бъдат пропуснати
     */
    function replace($content, $placeHolder = NULL, $once = FALSE)
    {
        return $this->sub($content, $placeHolder, $once, "replace");
    }
    
    
    /**
     * Отпечатва текстовото съдържание на шаблона
     */
    function output($content = '', $place = "CONTENT")
    {
        echo $this->getContent($content, $place, TRUE, TRUE);
    }
    
    
    /**
     * Връща текстовото представяне на шаблона, след всички възможни собституции
     */
    function getContent($content = NULL, $place = "CONTENT", $output = FALSE, $removeBlocks = TRUE)
    {
        if ($content) {
            $this->replace($content, $place);
        }
        
        if ($output) {
            $this->invoke('output');
        }
        
        $redirectArr = $this->getArray('_REDIRECT_');
        
        if ($redirectArr[0])
        redirect($redirectArr[0]);
        
        //   -
        if (is_array($this->places)) {
            foreach ($this->places as $place => $dummy) {
                $this->content = str_replace($this->toPlace($place), '', $this->content);
            }
        }
        
        if ($removeBlocks) {
            $this->removeBlocks($removeBlocks);
        }
        
        return $this->content;
    }
    
    
    /**
     * Прави субституция на данни, които могат да бъдат масив с обекти или масив с масиви
     * в указания блок-държач. Ако няма данни, блока държач изчезва, а се появява указания
     * празен блок
     */
    function placeMass($data, $holderBlock = NULL, $emptyBlock = NULL)
    {
        if ($holderBlock) {
            $tpl = $this->getBlock($holderBlock);
        } else {
            $tpl =& $this;
        }
        
        if ($emptyBlock) {
            $empty = $this->getBlock("$emptyBlock");
        }
        
        if (is_array($data)) {
            foreach ($data as $name => $object) {
                if (is_object($object)) {
                    $tpl->placeObject($object);
                    $tpl->append2master();
                } elseif (is_array($object)) {
                    $tpl->placeArray($object);
                    $tpl->append2master();
                }
            }
        } else {
            if ($emptyBlock) {
                $empty->replace2master();
            }
        }
    }
    
    
    /**
     * Прави субституция на елементите на масив в плейсхолдери започващи
     * с посочения префикс. Ако е посочен блок-държач, субституцията се
     * прави само в неговите рамки.
     */
    function placeArray($data, $holderBlock = NULL, $prefix = '')
    {
        // Ако данните са обект - конвертираме ги до масив
        if (is_object($data)) {
            $this->placeArray(get_object_vars($data), $holderBlock, $prefix);
        }
        
        if ($holderBlock) {
            $tpl = $this->getBlock($holderBlock);
        } else {
            $tpl =& $this;
        }
        
        if ($prefix) {
            $prefix .= "_";
        }
        
        if (count($data)) {
            foreach ($data as $name => $object) {
                if(is_array($object) || (is_object($object) && !($object instanceof core_ET))) {
                    $tpl->placeArray($object, NULL, $prefix . $name);
                } else {
                    $tpl->replace($object, $prefix . $name);
                }
            }
        }
        
        if ($holderBlock) {
            $tpl->replace2master();
        }
    }
    
    
    /**
     * Прави субституция на променливите на обект в плейсхолдъри започващи
     * с посочения префикс
     */
    function placeObject($data, $holderBlock = NULL, $prefix = NULL)
    {
        $this->placeArray($data, $holderBlock, $prefix);
    }
    
    
    /**
     * Превежда съдържанието на посочения език, или на текущия
     */
    function translate($lg = NULL)
    {
        $Lg = cls::get('core_Lg');
        
        preg_match_all('/\[#([a-zA-Z0-9_]{1,})#\]/', $this->content, &$matches);
        
        $places = $matches[1];
        
        foreach ($places as $id => $place) {
            $from[$id] = "[#" . $place . "#]";
            $to[$id] = "|*" . $from[$id] . "|";
        }
        
        $this->content = str_replace($from, $to, $this->content);
        
        $this->content = $Lg->translate($this->content, FALSE, $lg);
    }
    
    
    /**
     * Връща плейсхолдерите на шаблона
     */
    function getPlaceHolders()
    {
        preg_match_all('/\[#([a-zA-Z0-9_]{1,})#\]/', $this->content, &$matches);
        
        return $matches[1];
    }
    
    
    /**
     * Конвертира към стринг
     */
    function __toString()
    {
        return $this->getContent();
    }
}
