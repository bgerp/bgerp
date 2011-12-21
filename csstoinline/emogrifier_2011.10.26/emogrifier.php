<?php
/*
UPDATES

    2008-08-10  Fixed CSS comment stripping regex to add PCRE_DOTALL (changed from '/\/\*.*\*\//U' to '/\/\*.*\*\//sU')
    2008-08-18  Added lines instructing DOMDocument to attempt to normalize HTML before processing
    2008-10-20  Fixed bug with bad variable name... Thanks Thomas!
    2008-03-02  Added licensing terms under the MIT License
                Only remove unprocessable HTML tags if they exist in the array
    2009-06-03  Normalize existing CSS (style) attributes in the HTML before we process the CSS.
                Made it so that the display:none stripper doesn't require a trailing semi-colon.
    2009-08-13  Added support for subset class values (e.g. "p.class1.class2"). 
                Added better protection for bad css attributes.
                Fixed support for HTML entities.
    2009-08-17  Fixed CSS selector processing so that selectors are processed by precedence/specificity, and not just in order.
    2009-10-29  Fixed so that selectors appearing later in the CSS will have precedence over identical selectors appearing earlier.
    2009-11-04  Explicitly declared static functions static to get rid of E_STRICT notices.
    2010-05-18  Fixed bug where full url filenames with protocols wouldn't get split improperly when we explode on ':'... Thanks Mark!
                Added two new attribute selectors
    2010-06-16  Added static caching for less processing overhead in situations where multiple emogrification takes place
    2010-07-26  Fixed bug where '0' values were getting discarded because of php's empty() function... Thanks Scott!
    2010-09-03  Added checks to invisible node removal to ensure that we don't try to remove non-existent child nodes of parents that have already been deleted
    2011-04-08  Fixed errors in CSS->XPath conversion for adjacent sibling selectors and id/class combinations... Thanks Bob V.!
    2011-06-08  Fixed an error where CSS @media types weren't being parsed correctly... Thanks Will W.!
    2011-08-03  Fixed an error where an empty selector at the beginning of the CSS would cause a parse error on the next selector... Thanks Alexei T.!
    2011-10-13  Fully fixed a bug introduced in 2011-06-08 where selectors at the beginning of the CSS would be parsed incorrectly... Thanks Thomas A.!
    2011-10-26  Added an option to allow you to output emogrified code without extended characters being turned into HTML entities.
                Moved static references to class attributes so they can be manipulated.
                Added the ability to clear out the (formerly) static cache when CSS is reloaded.
*/

define('CACHE_CSS', 0);
define('CACHE_SELECTOR', 1);
define('CACHE_XPATH', 2);

class Emogrifier {

    private $html = '';
    private $css = '';
    private $unprocessableHTMLTags = array('wbr');
    private $caches = array();
    
    // this attribute applies to the case where you want to preserve your original text encoding.
    // by default, emogrifier translates your text into HTML entities for two reasons:
    // 1. because of client incompatibilities, it is better practice to send out HTML entities rather than unicode over email
    // 2. it translates any illegal XML characters that DOMDocument cannot work with
    // if you would like to preserve your original encoding, set this attribute to true.
    public $preserveEncoding = false;

    public function __construct($html = '', $css = '') {
        $this->html = $html;
        $this->css  = $css;
        $this->clearCache();
    }

    public function setHTML($html = '') { $this->html = $html; }
    public function setCSS($css = '') { 
        $this->css = $css; 
        $this->clearCache(CACHE_CSS);
    }
    
    public function clearCache($key = null) {
        if (!is_null($key)) {
            if (isset($this->caches[$key])) $this->caches[$key] = array();
        } else {
            $this->caches = array(
                CACHE_CSS       => array(),
                CACHE_SELECTOR  => array(),
                CACHE_XPATH     => array(),
            );
        }
    }

	// there are some HTML tags that DOMDocument cannot process, and will throw an error if it encounters them.
    // in particular, DOMDocument will complain if you try to use HTML5 tags in an XHTML document.
	// these functions allow you to add/remove them if necessary.
	// it only strips them from the code (does not remove actual nodes).
    public function addUnprocessableHTMLTag($tag) { $this->unprocessableHTMLTags[] = $tag; }
    public function removeUnprocessableHTMLTag($tag) {
        if (($key = array_search($tag,$this->unprocessableHTMLTags)) !== false)
            unset($this->unprocessableHTMLTags[$key]);
    }
    
    // applies the CSS you submit to the html you submit. places the css inline
	public function emogrify() {
	    $body = $this->html;
	    // process the CSS here, turning the CSS style blocks into inline css
	    if (count($this->unprocessableHTMLTags)) {
            $unprocessableHTMLTags = implode('|',$this->unprocessableHTMLTags);
            $body = preg_replace("/<($unprocessableHTMLTags)[^>]*>/i",'',$body);
	    }

//      $encoding = mb_detect_encoding($body);
//		TODO реда е променен
        $encoding = mb_detect_encoding($body, mb_list_encodings());
        $body = mb_convert_encoding($body, 'HTML-ENTITIES', $encoding);

        $xmldoc = new DOMDocument;
		$xmldoc->encoding = $encoding;
		$xmldoc->strictErrorChecking = false;
		$xmldoc->formatOutput = true;
        $xmldoc->loadHTML($body);
		$xmldoc->normalizeDocument();
        
		$xpath = new DOMXPath($xmldoc);

        // before be begin processing the CSS file, parse the document and normalize all existing CSS attributes (changes 'DISPLAY: none' to 'display: none');
        // we wouldn't have to do this if DOMXPath supported XPath 2.0.
        $nodes = @$xpath->query('//*[@style]');
        if ($nodes->length > 0) foreach ($nodes as $node) $node->setAttribute('style',preg_replace('/[A-z\-]+(?=\:)/Se',"strtolower('\\0')",$node->getAttribute('style')));

        // filter the CSS
        $search = array(
            '/\/\*.*\*\//sU', // get rid of css comment code
            '/^\s*@import\s[^;]+;/misU', // strip out any import directives
            '/^\s*@media\s[^{]+{\s*}/misU', // strip any empty media enclosures
            '/^\s*@media\s+((aural|braille|embossed|handheld|print|projection|speech|tty|tv)\s*,*\s*)+{.*}\s*}/misU', // strip out all media types that are not 'screen' or 'all' (these don't apply to email)
            '/^\s*@media\s[^{]+{(.*})\s*}/misU', // get rid of remaining media type enclosures
        );
        
        $replace = array(
            '',
            '',
            '',
            '',
            '\\1',
        );
		
		$css = preg_replace($search, $replace, $this->css);
        
        $csskey = md5($css);
        if (!isset($this->caches[CACHE_CSS][$csskey])) {

            // process the CSS file for selectors and definitions
            preg_match_all('/(^|[^{}])\s*([^{]+){([^}]*)}/mis', $css, $matches, PREG_SET_ORDER);
            
            $all_selectors = array();
            foreach ($matches as $key => $selectorString) {
                // if there is a blank definition, skip
                if (!strlen(trim($selectorString[3]))) continue;

                // else split by commas and duplicate attributes so we can sort by selector precedence
                $selectors = explode(',',$selectorString[2]);
                foreach ($selectors as $selector) {
                    // don't process pseudo-classes
                    if (strpos($selector,':') !== false) continue;
                    $all_selectors[] = array('selector' => trim($selector),
                                             'attributes' => trim($selectorString[3]),
                                             'index' => $key, // keep track of where it appears in the file, since order is important
                    );
                }
            }

            // now sort the selectors by precedence
            usort($all_selectors, array($this,'sortBySelectorPrecedence'));

            $this->caches[CACHE_CSS][$csskey] = $all_selectors;
        }

        foreach ($this->caches[CACHE_CSS][$csskey] as $value) {

            // query the body for the xpath selector
            $nodes = $xpath->query($this->translateCSStoXpath(trim($value['selector'])));

            foreach($nodes as $node) {
                // if it has a style attribute, get it, process it, and append (overwrite) new stuff
                if ($node->hasAttribute('style')) {
                    // break it up into an associative array
                    $oldStyleArr = $this->cssStyleDefinitionToArray($node->getAttribute('style'));
                    $newStyleArr = $this->cssStyleDefinitionToArray($value['attributes']);

                    // new styles overwrite the old styles (not technically accurate, but close enough)
                    $combinedArr = array_merge($oldStyleArr,$newStyleArr);
                    $style = '';
                    foreach ($combinedArr as $k => $v) $style .= (strtolower($k) . ':' . $v . ';');
                } else {
                    // otherwise create a new style
                    $style = trim($value['attributes']);
                }
                $node->setAttribute('style',$style);
            }
        }

		// This removes styles from your email that contain display:none. You could comment these out if you want.
        $nodes = $xpath->query('//*[contains(translate(@style," ",""),"display:none")]');
        // the checks on parentNode and is_callable below are there to ensure that if we've deleted the parent node,
        // we don't try to call removeChild on a nonexistent child node
        if ($nodes->length > 0) foreach ($nodes as $node) if ($node->parentNode && is_callable(array($node->parentNode,'removeChild'))) $node->parentNode->removeChild($node);

        if ($this->preserveEncoding) {
            return mb_convert_encoding($xmldoc->saveHTML(), $encoding, 'HTML-ENTITIES');
        } else {
            return $xmldoc->saveHTML();
        }
	}

    private function sortBySelectorPrecedence($a, $b) {
        $precedenceA = $this->getCSSSelectorPrecedence($a['selector']);
        $precedenceB = $this->getCSSSelectorPrecedence($b['selector']);

        // we want these sorted ascendingly so selectors with lesser precedence get processed first and
        // selectors with greater precedence get sorted last
        return ($precedenceA == $precedenceB) ? ($a['index'] < $b['index'] ? -1 : 1) : ($precedenceA < $precedenceB ? -1 : 1);
    }

    private function getCSSSelectorPrecedence($selector) {
        $selectorkey = md5($selector);
        if (!isset($this->caches[CACHE_SELECTOR][$selectorkey])) {
            $precedence = 0;
            $value = 100;
            $search = array('\#','\.',''); // ids: worth 100, classes: worth 10, elements: worth 1

            foreach ($search as $s) {
                if (trim($selector == '')) break;
                $num = 0;
                $selector = preg_replace('/'.$s.'\w+/','',$selector,-1,$num);
                $precedence += ($value * $num);
                $value /= 10;
            }
            $this->caches[CACHE_SELECTOR][$selectorkey] = $precedence;
        }

        return $this->caches[CACHE_SELECTOR][$selectorkey];
    }

	// right now we support all CSS 1 selectors and /some/ CSS2/3 selectors.
	// http://plasmasturm.org/log/444/
	private function translateCSStoXpath($css_selector) {

        $css_selector = trim($css_selector);
        $xpathkey = md5($css_selector);
        if (!isset($this->caches[CACHE_XPATH][$xpathkey])) {
            // returns an Xpath selector
            $search = array(
                               '/\s+>\s+/', // Matches any element that is a child of parent.
                               '/\s+\+\s+/', // Matches any element that is an adjacent sibling.
                               '/\s+/', // Matches any element that is a descendant of an parent element element.
                               '/(\w)\[(\w+)\]/', // Matches element with attribute
                               '/(\w)\[(\w+)\=[\'"]?(\w+)[\'"]?\]/', // Matches element with EXACT attribute
                               '/(\w+)?\#([\w\-]+)/e', // Matches id attributes
                               '/(\w+|[\*\]])?((\.[\w\-]+)+)/e', // Matches class attributes
            );
            $replace = array(
                               '/',
                               '/following-sibling::*[1]/self::',
                               '//',
                               '\\1[@\\2]',
                               '\\1[@\\2="\\3"]',
                               "(strlen('\\1') ? '\\1' : '*').'[@id=\"\\2\"]'",
                               "(strlen('\\1') ? '\\1' : '*').'[contains(concat(\" \",@class,\" \"),concat(\" \",\"'.implode('\",\" \"))][contains(concat(\" \",@class,\" \"),concat(\" \",\"',explode('.',substr('\\2',1))).'\",\" \"))]'",
            );
            $this->caches[CACHE_SELECTOR][$xpathkey] = '//'.preg_replace($search,$replace,$css_selector);
        }
        return $this->caches[CACHE_SELECTOR][$xpathkey];
	}

	private function cssStyleDefinitionToArray($style) {
	    $definitions = explode(';',$style);
	    $retArr = array();
	    foreach ($definitions as $def) {
            if (empty($def) || strpos($def, ':') === false) continue;
    	    list($key,$value) = explode(':',$def,2);
    	    if (empty($key) || strlen(trim($value)) === 0) continue;
    	    $retArr[trim($key)] = trim($value);
	    }
	    return $retArr;
	}
}
?>