<?php



/**
 * class swf_Object
 *
 * Предоставя възможностите на пакета SWFObject2
 *
 *
 * @category  vendors
 * @package   swf
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class swf_Object extends core_BaseClass
{
    public $vars = array();
    
    /**
     * @todo Чака за документация...
     */
    public function init($params = array())
    {
        $this->vars = (object) $this->vars;
        
        $this->vars->minFlashVersion = '9.0.0';
        
        // Задава променливи към флаш плеъра във формат променлива => стойност
        $this->vars->flashvars = array();
        
        // Задава параметрите
        $this->vars->params = array(
            // Specifies whether the movie begins playing immediately on loading in the browser.
            // The default value is true if this attribute is omitted.
            'play' => '', // true, false
            
            // Shows a shortcut menu when users right-click (Windows)
            // or control-click (Macintosh) the SWF file.
            // To show only About Flash in the shortcut menu, deselect this option.
            // By default, this option is set to true.
            'menu' => '', // true, false
            
            // Specifies scaling, aspect ratio, borders,
            // distortion and cropping for if you have changed the document's original width and height.
            'scale' => '', // showall, noborder, exactfit, noscale
            
            // Sets the Window Mode property of the Flash movie for transparency,
            // layering, and positioning in the browser.
            // The default value is 'window' if this attribute is omitted.
            'wmode' => '', // window, opaque, transparent, direct, gpu
            
            // Specifies whether static text objects that the Device Font option has not been selected
            // for will be drawn using device fonts anyway,
            // if the necessary fonts are available from the operating system.
            'devicefont' => '',
            
            // Specifies whether the browser should start Java when loading the Flash Player for the first time.
            // The default value is false if this attribute is omitted.
            // If you use JavaScript and Flash on the same page,
            // Java must be running for the FSCommand to work.
            'swliveconnect' => '', // true, false
            
            // Controls the ability to perform outbound scripting from within a Flash SWF.
            // The default value is 'always' if this attribute is omitted.
            'allowscriptaccess' => '',
            
            // Specifies the base directory or URL used to resolve all relative path statements in the Flash Player movie.
            // This attribute is helpful when your Flash Player movies are kept in a different directory from your other files.
            'base' => '',
            
            // Specifies whether the movie repeats indefinitely or stops when it reaches the last frame.
            // The default value is true if this attribute is omitted.
            'loop' => '', // true, false
            
            // Specifies the trade-off between processing time and appearance.
            // The default value is 'high' if this attribute is omitted.
            'quality' => '', // best, high, medium, autohigh, autolow, low
            
            // Specifies where the content is placed within the application window and how it is cropped.
            'salign' => '', // tl, tr, bl, br, l, t, r, b
            
            // Hexadecimal RGB value in the format #RRGGBB, which specifies the background color of the movie,
            // which will override the background color setting specified in the Flash file.
            'bgcolor' => '',
            
            // Specifies whether users are allowed to use the Tab key to move keyboard focus out of a
            // Flash movie and into the surrounding HTML (or the browser,
            // if there is nothing focusable in the HTML following the Flash movie).
            // The default value is true if this attribute is omitted.
            'seamlesstabbing' => '', // true, false
            
            // Enables full-screen mode. The default value is false if this attribute is omitted.
            // You must have version 9,0,28,0 or greater of Flash Player installed to use full-screen mode.
            'allowfullscreen' => '', // true, false
            
            // Controls a SWF file's access to network functionality.
            // The default value is 'all' if this attribute is omitted.
            'allownetworking' => '' // all, internal, none
        );
        
        // Масив с атрибутите на обекта
        $this->vars->attributes = array(
            
            // Uniquely identifies the Flash movie
            // so that it can be referenced using a scripting language or by CSS
            'id' => '',
            
            // Uniquely names the Flash movie
            // so that it can be referenced using a scripting language
            'name' => '',
            
            // Classifies the Flash movie
            // so that it can be referenced using a scripting language or by CSS
            'styleclass' => '',
            
            // HTML alignment of the object element.
            // If this attribute is omitted, it by default centers the movie and crops edges
            // if the browser window is smaller than the movie.
            'align' => ''  // middle, left, right, top, bottom
        );
    }
    
    
    /**
     * Задава Url към .swf файла
     * @param string $url
     */
    public function setSwfFile($url)
    {
        $this->vars->url = $url;
    }
    
    
    /**
     * Задава алтернативен html, който ще се показва в случай на липса на JS или Flash
     * @param string $html
     */
    public function setAlternativeContent($html)
    {
        $this->vars->altHTML = $html;
    }
    
    
    /**
     * Задава ширина
     * @param integer $width
     */
    public function setWidth($width)
    {
        $this->vars->width = $width;
    }
    
    
    /**
     * Задава височина
     * @param integer $height
     */
    public function setHeight($height)
    {
        $this->vars->height = $height;
    }
    
    
    /**
     * Задава минимално изискваната версия на flash
     * @param string $version
     */
    public function setMinFlashVersion($version)
    {
        $this->vars->minFlashVersion = $version;
    }
    
    
    /**
     * Задава атрибутите на флаш обекта
     * @param array $attr
     */
    public function setAttributes($attr, $value = null)
    {
        if (is_array($attr)) {
            foreach ($attr as $k => $v) {
                $this->setAttributes($k, $v);
            }
        } else {
            $this->vars->attributes["{$attr}"] = $value;
        }
    }
    
    
    /**
     * Задава параметрите, както е показано в документацията на swfobject
     * @param array $params
     */
    public function setParams($param, $value = null)
    {
        if (is_array($param)) {
            foreach ($param as $k => $v) {
                $this->setParams($k, $v);
            }
        } else {
            $this->vars->params["{$param}"] = $value;
        }
    }
    
    
    /**
     * Задава параметрите, които ще бъдат предадени на флаш обекта
     * @param array $flashvars
     */
    public function setFlashvars($flashvar, $value = null)
    {
        if (is_array($flashvar)) {
            foreach ($flashvar as $k => $v) {
                $this->setFlashvars($k, $v);
            }
        } else {
            $this->vars->flashvars["{$flashvar}"] = $value;
        }
    }
    
    
    /**
     * Премахва празните променливи от обекта
     */
    public function clearVars()
    {
        foreach ($this->vars->attributes as $ndx => $value) {
            if (empty($value)) {
                unset($this->vars->attributes["{$ndx}"]);
            }
        }
        
        foreach ($this->vars->params as $ndx => $value) {
            if (empty($value)) {
                unset($this->vars->params["{$ndx}"]);
            }
        }
        
        foreach ($this->vars->flashvars as $ndx => $value) {
            if (empty($value)) {
                unset($this->vars->flashvars["{$ndx}"]);
            }
        }
    }
    
    
    /**
     * Връща шаблон, в който:
     * в него е включено зареждане на скрипта на swfobject
     * в началото е алтернативното съдържание, оградено в <div> с уникален id;
     * javaScript в който се извиква метода на библиотеката
     */
    public function getContent(&$attr = array())
    {
        ht::setUniqId($attr);
        
        $uniqId = $attr['id'];
        
        $installSwfPath = sbf('swf/2.2/expressInstall.swf');
        
        // Махаме всичко, което не е попълнено
        $this->clearVars();
        
        $this->vars->params = json_encode($this->vars->params);
        $this->vars->attributes = json_encode($this->vars->attributes);
        $this->vars->flashvars = json_encode($this->vars->flashvars);
        
        if (isset($this->others['startDelay'])) {
            $startDelay = (int) $this->others['startDelay'];
        } else {
            $startDelay = 0;
        }
        
        $tpl = new ET(
            "<div id='{$uniqId}'>[#content#]</div>
               <script type=\"text/javascript\">
                function " . $uniqId . "()
                {
                    swfobject.embedSWF([#url#], '{$uniqId}', '[#width#]', '[#height#]', '[#minFlashVersion#]', {$installSwfPath},[#flashvars#],[#params#],[#attributes#]);
                }
            </script>"
        
        );
        
        $tpl->appendOnce("setTimeout('" . $uniqId . "();'" . ", 1000*{$startDelay});", 'ON_LOAD');
        $tpl->push('swf/2.2/swfobject.js', 'JS');
        $tpl->placeObject($this->vars);
        
        return $tpl;
    }
}
