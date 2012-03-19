<?php



/**
 * File: calendar.php | (c) dynarch.com 2004
 *
 * Distributed as part of "The Coolest DHTML Calendar"
 * under the same terms.
 * -----------------------------------------------------------------
 * This file implements a simple PHP wrapper for the calendar.  It
 * allows you to easily include all the calendar files and setup the
 * calendar by instantiating and calling a PHP object.
 *
 *
 * @category  all
 * @package   jscal
 * @author
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class jscal_Import extends core_BaseClass {
    
    
    /**
     * @todo Чака за документация...
     */
    var $calendarLibPath;
    
    
    /**
     * @todo Чака за документация...
     */
    function jscal_Import () {
        
        $this->calendarLibPath = sbf("jscal/src", '');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function loadFiles()
    {
        return new ET($this->getLoadFilesCode());
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getLoadFilesCode()
    {
        $lg = 'en';
        
        $code .= ('<link rel="stylesheet" type="text/css" media="all" href=' .
            sbf("jscal/src/css/jscal2.css") . " >\n");
        
        $code .= ('<link rel="stylesheet" type="text/css" media="all" href="' .
            $this->calendarLibPath . "/css/gold/gold.css" .
            '" >' . "\n");
        $code .= ('<link rel="stylesheet" type="text/css" media="all" href="' .
            $this->calendarLibPath . "/css/border-radius.css" .
            '" >' . "\n");
        
        $code .= ('<script type="text/javascript" src="' .
            $this->calendarLibPath . "/js/jscal2.js" .
            '"></script>' . "\n");
        
        $code .= ('<script type="text/javascript" src="' .
            $this->calendarLibPath . "/js/lang/{$lg}.js" .
            '"></script>' . "\n");
        
        return $code;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function makeCalendar($options)
    {
        $jsOptions = json_encode($options);
        
        $code = "\n";
        $code .= "    function init_{$options['inputField']}()\n";
        $code .= "    {\n";
        $code .= "        var dateInput = document.getElementById('{$options['inputField']}'); \n";
        $code .= "        dateInput.style.width = dateInput.offsetWidth - 30;\n";
        $code .= "        Calendar.setup({$jsOptions});\n";
        $code .= "    }\n";
        
        $tpl = new ET();
        
        $tpl->append($code, 'SCRIPTS');
        
        $tpl->append("init_{$options['inputField']}();", "ON_LOAD");
        
        return $tpl;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function makeInputField($tpl, $options = array(), $attr = array())
    {
        if(!$tpl) {
            ht::setUniqId($attr);
            $attr['type'] = $attr['type'] ? $attr['type'] : 'text';
            $attr['size'] = $attr['size'] ? $attr['size'] : '20';
            
            $tpl = ht::createElement('input', $attr);
        }
        
        $btnImg = "<img align=\"top\" id=\"" . $attr['id'] . '_btn' . "\" border=\"0\" src=\"{$this->calendarLibPath}/img.gif\" alt=\"\" height=\"22\">";
        
        $tpl->append($btnImg);
        
        $options['inputField'] = $attr['id'];
        $options['trigger'] = $attr['id'] . '_btn';
        $options['onSelect'] = 'function() { this.hide() }';
        
        $tpl->append($this->makeCalendar($options));
        
        return $tpl;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function renderHtml_($tpl, $attr = array(), $options = array())
    {
        $attr['name'] = $name;
        $attr['value'] = $value;
        
        if(!$options['dateFormat']) {
            $options['dateFormat'] = "%d-%m-%Y";
        }
        
        $tpl = $this->makeInputField($tpl, $options, $attr) ;
        
        $tpl->appendOnce($this->getLoadFilesCode(), "HEAD");
        
        return $tpl;
    }
};

