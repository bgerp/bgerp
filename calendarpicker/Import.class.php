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
 * @category  vendors
 * @package   calendarpicker
 *
 * @author
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class calendarpicker_Import
{
    /**
     * @todo Чака за документация...
     */
    public $calendarLibPath;
    
    
    /**
     * @todo Чака за документация...
     */
    public $calendarFile;
    
    
    /**
     * @todo Чака за документация...
     */
    public $calendarLangFile;
    
    
    /**
     * @todo Чака за документация...
     */
    public $calendarSetupFile;
    
    
    /**
     * @todo Чака за документация...
     */
    public $calendarThemeFile;
    
    
    /**
     * @todo Чака за документация...
     */
    public $calendarOptions;
    
    
    /**
     * @todo Чака за документация...
     */
    public function __construct(
        $lang = 'auto',
        $theme = 'skins/aqua/theme',
        $stripped = false
    ) {
        if ($stripped) {
            $this->calendarFile = 'calendar_stripped.js';
            $this->calendarSetupFile = 'calendar-setup_stripped.js';
        } else {
            $this->calendarFile = 'calendar.js';
            $this->calendarSetupFile = 'calendar-setup.js';
        }
        
        if ($lang == 'auto') {
            $lg = Mode::get('lg');
            
            if (!$lg || $lg == 'bg') {
                $lg = 'bg-utf8';
            }
            $lang = "{$lg}";
        }
        
        $this->calendarLibPath = 'calendarpicker/';
        
        $this->includeCode = new ET();
        $this->includeCode->push($this->calendarLibPath . $theme . '.css', 'CSS', true);
        $this->includeCode->push($this->calendarLibPath . $this->calendarFile, 'JS', true);
        $this->includeCode->push($this->calendarLibPath . 'lang/calendar-' . $lang . '.js', 'JS', true);
        $this->includeCode->push($this->calendarLibPath . $this->calendarSetupFile, 'JS', true);
        $this->calendarOptions = array('ifFormat' => '%Y/%m/%d', 'daFormat' => '%Y/%m/%d');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function setOption($name, $value)
    {
        $this->calendarOptions[$name] = $value;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function loadFiles()
    {
        return new ET($this->getLoadFilesCode());
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function makeCalendar($options = array())
    {
        $jsOptions = str_replace('"', "'", json_encode(array_merge($this->calendarOptions, $options)));
        
        $tpl = new ET();
        
        jquery_Jquery::run($tpl, "initDateInput('{$options['inputField']}', {$jsOptions});");
        
        return $tpl;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function makeInputField($options, $tpl, $attr)
    {
        ht::setUniqId($attr);
        
        if (!$tpl) {
            $attr['type'] = $attr['type'] ? $attr['type'] : 'text';
            $attr['size'] = $attr['size'] ? $attr['size'] : '20';
            $tpl = ht::createElement('input', $attr);
        }
        
        $imgPath = sbf($this->calendarLibPath . 'img.gif', '');
        
        $btnAttr = array();
        $btnAttr['id'] = $attr['id'] . '_btn';
        $btnImg = "<img style='vertical-align:top;min-height: 26px;' src=\"{$imgPath}\" alt=\"\" id=\"" . $attr['id'] . '_img" >';
        $btnAttr['href'] = '#';
        $btnAttr['style'] = ' visibility:hidden; padding:0px; margin:0px;';
        $tpl->append(ht::createElement('a', $btnAttr, $btnImg, true));
        
        $options['inputField'] = $attr['id'];
        $options['button'] = $btnAttr['id'];
        
        $tpl->append($this->makeCalendar($options));
        
        return $tpl;
    }
    
    
    /**
     * PRIVATE SECTION
     */
    public function _field_id($id)
    {
        return 'f-calendar-field-' . $id;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function _trigger_id($id)
    {
        return 'f-calendar-trigger-' . $id;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function _gen_id()
    {
        static $id = 0;
        
        return ++$id;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function _make_js_hash($array)
    {
        $jstr = '';
        reset($array);
        
        while (list($key, $val) = each($array)) {
            if (is_bool($val)) {
                $val = $val ? 'true' : 'false';
            } elseif (!is_numeric($val)) {
                $val = '"' . $val . '"';
            }
            
            if ($jstr) {
                $jstr .= ',';
            }
            $jstr .= '"' . $key . '":' . $val;
        }
        
        return $jstr;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function _make_html_attr($array)
    {
        $attrstr = '';
        reset($array);
        
        while (list($key, $val) = each($array)) {
            $attrstr .= $key . '="' . $val . '" ';
        }
        
        return $attrstr;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function render($tpl, $attr)
    {
        if (Mode::get('screenMode') == 'narrow') {
            $df = core_Setup::get('EF_DATE_NARROW_FORMAT', true);
        } else {
            $df = core_Setup::get('EF_DATE_FORMAT', true);
        }
        
        $df = preg_replace('/([a-z])/i', '%${1}', $df);
        
        $calOptions = array('ifFormat' => $df, 'daFormat' => $df);
        
        $tpl = new ET($this->makeInputField($calOptions, $tpl, $attr));
        $tpl->append($this->includeCode);
        
        return $tpl;
    }
}
