<?php



/**
 * Клас 'core_Html' ['ht'] - Функции за генериране на html елементи
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Html
{

    /**
     * Композира xHTML елемент
     */
    static function createElement($name, $attributes = array(), $body = NULL, $closeTag = FALSE)
    {   
        $attrStr = '';

        if($name == 'img') {
            if(!is_array($attributes)) {
                $attributes = array();
            }
            if(!isset($attributes['alt'])) {
                $attributes['alt'] = '';   
            }
        }

        if ($name) {

            if (is_array($attributes)) {
                foreach ($attributes as $atr => $content) {
                    // Смятаме, че всички атрибути с имена, започващи със '#'
                    // са вътрешни и поради това не ги показваме в елемента
                    if ($atr{0} == '#') continue;
                    

                    if (is_string($content)) {
                        // $content = htmlspecialchars($content, ENT_COMPAT | ENT_HTML401, 'UTF-8');
                        /**
                         * Необходимо ли е да се ескейпва символи различни от двойни кавички
                         * в стойностите на HTML атрибутите?
                         * 
                         */
                        $content = self::escapeAttr($content);
                    }
                    
                    $attrStr .= " " . $atr . "=\"" . $content . "\"";                 
                }

            }

            if ( ( $body === NULL || $body === FALSE)  && !$closeTag) {
                $element = "<{$name}{$attrStr}>";
            } else {
                if(in_array(strtolower($name), array('textarea', 'option'))) {
                     $body = str_replace(array('&', "<" , ">"), array('&amp;', "&lt;", "&gt;"), $body);
                }
                $element = "<{$name}{$attrStr}>{$body}</{$name}>";
            }
        } else {
            // Ако нямаме елемент, т.е. елемента е празен, връщаме само тялото
            $element = $body;
        }

        return new core_ET('[#1#]', $element);
    }


    /**
     * Ескейпва съдържание на атрибут
     */
    static function escapeAttr($attrContent)
    {
        //$content = str_replace(array('&', "\""), array('&amp;', "&quot;"), $attrContent);
        $content = htmlspecialchars( $attrContent, ENT_QUOTES, NULL);
        $content = str_replace(array("\n"), array('&#10;'), $content);
        return $content;
    }
    

    /**
     * Създава редактируем комбо-бокс, съчетавайки SELECT с INPUT
     */
    static function createCombo($name, $value, $attr = array(), $options = array())
    {
        $attr['name'] = $name;
        
        self::setUniqId($attr);

        if(Mode::is('javascript', 'no')) {
            
            $listId = $attr['id'] . '_list';

            $attr['list'] = $listId;

            $tpl = self::createElement('input', $attr);
            
            $tpl->append(self::createElement('datalist', array('id' =>$listId)));
            if(is_array($options)) {
                unset($options['']);
                foreach($options as $key => $v) {
                    $tpl->append("\n" . self::createElement('option', array('value' => $v)));
                }
            }
            $tpl->append(self::createElement('/datalist'));

        } else {

            $tpl = new ET();

            // За съвместимост с IE
            $tpl->appendOnce("\n<!--[if IE 7]><STYLE>Select.combo {margin-top:1px !important;}</STYLE><![endif]-->", 'HEAD');
            $tpl->appendOnce("\n<!--[if IE 6]><STYLE>Select.combo {margin-top:1px !important;}</STYLE><![endif]-->", 'HEAD');


     
            $attr['class'] .= ' combo';
            $attr['value'] = $value;
            $id = $attr['id'];
            
            $suffix = '_cs';
            list($l, $r) = explode('[', $id);
            $r = rtrim($r, ']');
            $selectId = $l . $suffix . $r;

            if ($attr['ajaxAutoRefreshOptions']) {
                $attr['onkeydown'] = "focusSelect(event, '{$selectId}');";
                $attr['onkeyup'] = "  if(typeof(this.proc) != 'undefined') {clearTimeout(this.proc); delete this.proc;} this.proc = setTimeout( \"  $('#" . $id . "').change();\", 1500); ";
                if($attr['onchange']) {
                    $attr['onchange'] = "if(isOptionExists('" . $selectId . "', this.value)) {" . $attr['onchange'] . "} ";
                }
                $attr['onchange'] .= "if(typeof(this.proc) != 'undefined') {clearTimeout(this.proc); delete this.proc;} ajaxAutoRefreshOptions('{$id}','{$selectId}'" . ", this, {$attr['ajaxAutoRefreshOptions']});";
                unset($attr['ajaxAutoRefreshOptions']);
            }

            unset($attr['onblur']);
            $attr['type'] = 'text';
            $attr['autocomplete'] = 'off';
            $tpl->append(self::createElement('input', $attr));
            
            unset($attr['autocomplete'], $attr['type']);

            $attr['onchange'] = "comboSelectOnChange('" . $attr['id'] . "', this.value, '{$selectId}');";

            jquery_Jquery::run($tpl, "comboBoxInit('{$attr['id']}', '{$selectId}');", TRUE);

            $attr['id'] = $selectId;
            
            $name = $attr['name'];
            list($l, $r) = explode('[', $name);
            $r = rtrim($r, ']');
            $name = $l . $suffix . $r;
            $attr['name'] = $name;
            
            // Долното кара да не работи селекта в firefox-mobile, но е добре за  
            // декстоп-браузърите, когато се работи с tab за превключване на полетата
            if(!Mode::is('screenMode', 'narrow')) {
                $attr['tabindex'] = "-1";
            }

            unset($attr['size'], $attr['onkeypress'], $attr['onclick'], $attr['ondblclick']);
            
            if(!Mode::is('javascript', 'no')) {
                $attr['style'] .= ';visibility: hidden;';
            }

            $tpl->prepend(self::createSelect($name, $options, $value, $attr));
        }

        return $tpl;
    }


    /**
     * Прави групиране на опциите, като за групи използва предната част, преди разделителя
     */
    static function groupOptions($options, $div = '»')
    {        
        if(count($options) > 1){
        	$groups = $newOptions = array();
        	
        	// За всяка опция
            $defaultGroup = '';
        	foreach ($options as $index => $opt){
                if(is_object($opt)) {
                    if($opt->group) {
                        $defaultGroup = trim($opt->title);
                        continue;
                    }
                    $title = $opt->title;
                } else {
                    $title = $opt;
                }
         			
                // Ако в името на класа има '->' то приемаме, че стринга преди знака е името на групата
        	    list($group, $caption) = explode($div, $title);
        			
                if(!$caption) {
                    $caption = $group;
                    $group = $defaultGroup;
                } elseif(!$group) {
                    $group = $lastGroup;
                }
    
                $groups[$lastGroup = trim($group)][$index] = trim($caption);
        	}
            
        	// Ако има поне една намерена OPTGROUP на класовете, Иначе не правим нищо
        	if(count($groups)){
        		if(isset($groups[''])) {
                    asort($groups['']);
                    $newOptions += $groups[''];
                    unset($groups['']);
                }
        		foreach($groups as $group => $optArr) {
        		    // Добавяме името като OPTGROUP
                    if($group) {
                        $newOptions[$group] = (object)array(
                                    'title' => $group,
                                    'group' => TRUE,
                            );
                    }
                    asort($optArr);
                    $newOptions += $optArr;
                }

                $options = $newOptions;
        	}
        }

        return $options;
    }


    /**
     * Създава SELECT елемент
     */
    static function createSelect($name, $options, $selected = NULL, $selAttr = array())
    { 
        $selAttr['name'] = $name;

        foreach ($selAttr as $atr => $content) {
            // Смятаме, че всички атрибути с имена, започващи със '#'
            // са вътрешни и поради това не ги показваме в елемента
            // Същото правим и за атрибутите placeholder и value
            if ($atr{0} == '#' || $atr == 'placeholder' || $atr == 'value') {
                continue;
            }

            if (is_string($content)) {
                $content = self::escapeAttr($content);
            }

            $attrStr .= " " . $atr . "=\"" . $content . "\"";
        }

        $select = new ET("<select{$attrStr}>[#OPTIONS#]</select>");

        $select->append('', 'OPTIONS');
 
        if (is_array($options)) {
            foreach ($options as $id => $title) {
                $attr = array();
                $element = 'option';

                if (is_object($title)) {
                    if ($title->group) {
                        if ($openGroup) {
                            // затваряме групата
                            $select->append("</optgroup>", 'OPTIONS');
                        }
                        $element = 'optgroup';
                        $attr = $title->attr;
                        $attr['label'] = $title->title;
                        $option = self::createElement($element, $attr);
                        $select->append($option, 'OPTIONS');
                        $openGroup = TRUE;
                        continue;
                    } elseif($title instanceof core_ET){
                    	$title = $title->getContent();
                    } else {
                        $attr = $title->attr;
                        $title = $title->title;
                    }
                }
 
                if (!isset($attr['value'])) {
                    $attr['value'] = $id;
                }

                if ($attr['value'] == $selected) {
                    if($selected != NULL || $attr['value'] === '' || $attr['value'] === NULL) {
                        $attr['selected'] = 'selected';
                    }
                }

                // Хак за добавяне на плейс-холдер
                if($selAttr['placeholder'] &&
                    strlen($attr['value'])==0 && !trim($title)) {
                    $title = $selAttr['placeholder'];
                    $attr['style'] .= 'color:#777;';
                }

                $option = self::createElement($element, $attr, $title);

                $select->append("\n", 'OPTIONS');
                $select->append($option, 'OPTIONS');
            }

            if ($openGroup) {
                // затваряме групата
                $select->append("</optgroup>", 'OPTIONS');
            }

        }
       
        return $select;
    }


    /**
     * Преброява колко са действителните опции,
     * без да брои групите
     */
    static function countOptions($options)
    {
        $cnt = 0;

        if(count($options)) {
            foreach($options as $opt) {
                if(!is_object($opt) || !$opt->group) {
                    $cnt++;
                }
            }
        }

        return $cnt;
    }


    /**
     * Прави SELECT, radio или disabled INPUT в зависимост от броя на опциите
     *
     * @param $maxRadio максимален брой опции, при които се създава радио група
     */
    static function createSmartSelect($options, $name, $value = NULL, $attr = array(),
        $maxRadio = 0,
        $maxColumns = 4,
        $columns = NULL)
    {
        $optionsCnt = self::countOptions($options);
        
        setIfNot($attr['data-hiddenName'], $name);
        
        // Очакваме да има поне една опция
        expect($optionsCnt > 0, "'Липсват опции за '{$name}'");
        
        // Когато имаме само една опция, правим readOnly <input>
        if($optionsCnt == 1) {

            foreach($options as $id => $opt) {

                if(is_object($opt) && $opt->group) continue;

                if(is_object($opt)){
                	if($opt instanceof core_ET){
                		$value = $opt->getContent();
                	} else {
                		$value = $opt->title;
                	}
                } else {
                	$value = $opt;
                }
				
                // Запазваме класа и стила на опцията
                if(is_object($opt) && is_array($opt->attr)) {
                    if($opt->attr['class']) {
                        $attr['class'] .= ($attr['class']? ' ' : '') . $opt->attr['class'];
                    }
                    if($opt->attr['style']) {
                        $attr['style'] .= ($attr['style']? ';' : '') . $opt->attr['style'];
                    }
                }

                break;
            }
            
            $attr['readonly'] = 'readonly';
            $attr['class'] = 'readonly';

            if(empty($value)) {
                if($attr['placeholder']) {
                    $value = $attr['placeholder'];
                    $attr['style'] = 'color:#777';
                } else {
                    $value = '&nbsp;';
                }
            }
            
            $input = self::createElement('select', $attr, "<option>$value</option>", TRUE);

            $input->append(self::createElement('input', array(
                        'type' => 'hidden',
                        'name' => $name,
                        'value' => $id
                    )));
        } elseif($optionsCnt <= $maxRadio) {
        	if($optionsCnt < 4 ) {
        		$keyListClass .= 'shrinked';
        	}
            // Когато броя на опциите са по-малко

            // Определяме броя на колоните, ако не са зададени.
            if(count($options) != $optionsCnt) {
                $col = 1;
            } else {
                $col = $columns ? $columns :
                min(max(4, $maxColumns),
                    round(sqrt(max(0, $optionsCnt + 1))));
            }

            if($col > 1) {
                $tpl = "<table class='keylist {$keyListClass}'><tr>";

                for($i = 1; $i <= $col; $i++) {
                    $tpl .= "<td style='vertical-align: top;'>[#OPT" . ($i-1) . "#]</td>";
                }

                $tpl = new ET($tpl . "</tr></table>");
            } else {

                $tpl = new ET("[#OPT0#]");
                $tpl->append("", "OPT0");
            }

            $i = 0;
            
            foreach($options as $id => $opt) {

                $input = new ET();

                if(is_object($opt) && $opt->group) {

                    $input->append(self::createElement('div', $opt->attr, $opt->title));

                    $indent = '&nbsp;&nbsp;&nbsp;';
                } else {
                    $title = is_object($opt) ? (($opt instanceof core_ET) ? type_Varchar::escape($opt->getContent()) : $opt->title) : $opt;
                    $attrLabel = is_object($opt) ? $opt->attr : array();
                    $radioAttr = array('type' => 'radio', 'name' => $name, 'value' => $id);

                    self::setUniqId($radioAttr);

                    if($value == $id) {
                        $radioAttr['checked'] = 'checked';
                    } else {
                        unset($radioAttr['checked']);
                    }

                    $radioAttr['class'] .= ' radiobutton';

                    $input->append($indent);

                    $input->append(self::createElement('input', $radioAttr));

                    $attrLabel['for'] = $radioAttr['id'];

                    $input->append(self::createElement('label', $attrLabel, $title));

                    $input->append("<br>");
                }

                $tpl->append($input, 'OPT' . ($i % $col));

                $i++;
            }
            
            // Добавка (временна) за да не се свиват радио бутоните от w25 - w75
            $attr['style'] .= 'width:100%';

            $input = self::createElement('div', $attr, $tpl);
        } else {
            $input = self::createSelect($name, $options, $value, $attr);
        }

        return $input;
    }


    /**
     * Създава скрити полета
     */
    static function createHidden($variables)
    {
        $hiddens = arr::make($variables);

        $tpl = new ET();

        if (is_array($hiddens) && count($hiddens)) {

            Request::doProtect($hiddens);

            foreach ($hiddens as $name => $value) {
                if(is_array($value)) {
                    foreach($value as $key => $v) {
                        self::addHiden($tpl, $name . '[' . $key . ']', $v);
                    }
                    continue;
                }
                expect(is_scalar($value) || !($value), gettype($value));
                self::addHiden($tpl, $name, $value);
            }
        }

        return $tpl;
    }
    

    /**
     * Добавя hidden input към шаблона
     */
    static function addHiden($tpl, $name, $value) 
    {
        $attr = array();
        $attr['name'] = $name;
        $attr['value'] = $value;
        $attr['type'] = 'hidden';
        $tpl->append(self::createElement('input', $attr));
    }


    /**
     * Създава текстов INPUT
     */
    static function createTextInput($name, $value = NULL, $attr = array())
    {
        $attr = arr::make($attr);

        if ($name) {
            $attr['name'] = $name;
        }

        if (isset($value)) {
            $attr['value'] = $value;
        }

        $input = self::createElement("input", $attr);

        return $input;
    }



   
    /**
     * Създава бутон, който при натискане предизвиква съобщение за грешка
     */
    static function createErrBtn($title, $error, $attr = array())
    {
    	$attr = arr::make($attr);
    	$attr['error'] = $error;
    	$attr['ef_icon'] = 'img/16/error.png';
    	
    	// Url-то се заменя с такова водещо към грешка
    	$url = core_Message::getErrorUrl($error, 'page_Error');
    	return self::createBtn($title, $url, NULL, NULL, $attr);
    }
    
    
    /**
     * Създава бутон - хиперлинк
     */
    static function createBtn($title, $url = array(), $warning = FALSE, $newWindow = FALSE, $attr = array())
    {
        $attr = self::prepareLinkAndBtnAttr($attr, $warning);
       
        $title = tr($title);

        // Ако URL-то е празно - забраняваме бутона
        if((is_array($url) && count($url) == 0) || !$url) {
            $attr['disabled'] = "disabled";
        }
        
        // URL с потвърждение
        if(is_array($url) && $warning) {
            $content = $url[1] . ($url['id'] ? $url['id'] : $url[2]); 
            if($content) {
                $url['Cf'] = core_Request::getSessHash($content); 
            }
        }

        // Правим URL-to
        try {
            $url = toUrl($url);
        } catch(core_exception_Expect $e) {
            $url = NULL;
            $attr['style'] .= ' border:dotted 1px red;';
        }

        // Подготвяме атрибутите
        $attr['class'] .= ($attr['class'] ? ' ' : '') . 'button';

        // Оцветяваме бутона в зависимост от особеностите му
        if(!$attr['disabled']) {
            if($attr['error']){
            	$attr['style'] .= 'color:#9A5919;';
            } elseif($warning) {
                $attr['style'] .= 'color:#772200;';
            } elseif ($newWindow) {
                $attr['style'] .= 'color:#008800;';
            }
        } else {
            $attr['style'] .= 'color:#888;';
        }

        // Добавяме икона на бутона, ако има
        if (!Mode::is('screenMode', 'narrow') ) {
            $attr = self::addBackgroundIcon($attr); 
        } else {
            unset($attr['ef_icon']);
        }
 
        // Ако нямаме JavaScript правим хипервръзка
        if ( Mode::is('javascript', 'no') ) {
            
            $attr['href'] = $url;

            if ($newWindow) {
                $attr['target'] = $newWindow;
            }

            $attr['rel'] = 'nofollow';

            return self::createElement('a', $attr, "$title");
        }
        
        // Вкарваме JavaScript-a
        if ($newWindow) {
            if (is_string($newWindow) && ($newWindow != '_blank')) {
                $attr['onclick'] .= " window.open('{$url}','{$newWindow}')";
            } else {
                $attr['onclick'] .= " window.open('{$url}')";
            }
        } else {
            $attr['onclick'] .= " document.location='{$url}'";
        }
        
        // Ако имаме грешка - показваме я и не продължаваме
        if ($attr['error']) {
        	$attr['error'] = tr($attr['error']);
        	$attr['onclick'] = " alert('{$attr['error']}'); return false; ";
            unset($attr['error']);
        }

        $attr['type'] = 'button';
        $attr['value'] = $title;
        

        return self::createElement('input', $attr);
    }


    /**
     * Създава submit бутон
     */
    static function createSbBtn($title, $cmd = 'default', $warning = NULL, $newWindow = NULL, $attr = array())
    {
        $attr = self::prepareLinkAndBtnAttr($attr, $warning);

        $attr['name'] .= "Cmd[{$cmd}]";

        if (is_string($newWindow) && ($newWindow != '_blank')) {
            $attr['onclick'] .= "  this.form.target = '{$newWindow}';";
        } elseif ($newWindow) {
            $attr['onclick'] .= "  this.form.target = '_blank';";
        }

        $attr['type'] = 'submit';

        $attr['value'] = tr($title);

        // Оцветяваме бутона в зависимост от особеностите му
        if (isset($warning)) {
            $attr['style'] .= 'color:#772200;';
        } elseif ($newWindow) {
            $attr['style'] .= 'color:#008800;';
        }

        if ($attr['class']) {
            $attr['class'] .= ' button';
        } else {
            $attr['class'] = 'button';
        }
        
        // Добавяме икона на бутона, ако има
        if (!Mode::is('screenMode', 'narrow') ) {
            $attr = self::addBackgroundIcon($attr);
        } else {
            unset($attr['ef_icon']);
        }

        $btn = self::createElement('input', $attr);

        $btn->appendOnce("<input type=\"hidden\" name=\"Cmd[default]\" value=1>",
            'FORM_HIDDEN');

        return $btn;
    }


    /**
     * Създава бутон, който стартира javascript функция
     */
    static function createFnBtn($title, $function, $warning = NULL, $attr = array())
    {
        $attr = self::prepareLinkAndBtnAttr($attr, $warning);

        $attr['onclick'] .= $function;

        $attr['type'] = 'button';

        $attr['value'] = tr($title);

        // Оцветяваме бутона в зависимост от особеностите му
        if ($warning) {
            $attr['style'] .= 'color:#772200;';
        }

        $attr['class'] .= ($attr['class'] ? ' ' : '') . 'button';
        
        // Добавяме икона на бутона, ако има
        if (!Mode::is('screenMode', 'narrow') ) {
            $attr = self::addBackgroundIcon($attr);
        } else {
            unset($attr['ef_icon']);
        }

        $btn = self::createElement('input', $attr);

        return $btn;
    }


    /**
     * 
     * Създава хипервръзка
     * 
     * @param string $title
     * @param FALSE|array $url
     * @param FALSE|string $warning
     * @param array $attr
     * 
     * @return core_ET
     */
    static function createLink($title, $url = FALSE, $warning = FALSE, $attr = array())
    {
        $attr = self::prepareLinkAndBtnAttr($attr, $warning);
        
        // URL с потвърждение
        if(is_array($url) && $warning) {
            $content = $url[1] . ($url['id'] ? $url['id'] : $url[2]); 
            if($content) {
                $url['Cf'] = core_Request::getSessHash($content); 
            }
        }

        if (is_array($url)) {
            if(count($url)) {
                try {
                    $url = toUrl($url);
                } catch(core_exception_Expect $e) {
                    $url = NULL;
                    $attr['style'] .= ' border:dotted 1px red;';
                }
            } else {
                $url = '';
            }
        }
        
        if($url) {
            if($warning) {
                $attr['onclick'] .= " document.location='{$url}'";
                $attr['href'] = 'javascript:void(0)';
            } else {
                $attr['href'] = $url;
            }
        }

        if($icon = $attr['ef_icon']) {
            
            if ( (Mode::is('text', 'xhtml') || Mode::is('printing'))) {

                $iconSrc = sbf($icon, '', Mode::is('text', 'xhtml'));
                $srcset  = '';

                if(log_Browsers::isRetina()) {
                    $icon2 = str_replace('/16/', '/32/', $icon);

                    if(getFullPath($icon2)) {
                        $srcset = sbf($icon2, '', Mode::is('text', 'xhtml')) . ' 2x';
                    }
                }
                $icon    = "<img src='$iconSrc' {$srcset} width='16' height='16' style='float:left;margin:1px 5px -3px 6px;' alt=''>";
                $title   = "<span class='linkWithIconSpan'>{$icon}{$title}</span>";
            } else {


                // Добавяме икона на бутона, ако има
                $attr = self::addBackgroundIcon($attr);
            }

            unset($attr['ef_icon']);
        }
        
        if ((!Mode::is('text', 'xhtml') && !Mode::is('printing') && !Mode::is('pdf'))) {
        	// Оцветяваме линка в зависимост от особеностите му
        	if(!$attr['disabled']) {
        		if($warning){
        			$attr['style'] .= ' color:#772200 !important;';
        		} elseif (strpos($url, '://')) {
        			if(!strpos($attr['class'], 'out')) {
        				$attr['class'] .= ' out';
        			}
        		} elseif($attr['target'] == '_blank') {
        			$attr['style'] .= ' color:#008800 !important;';
        		}
        	} else {
        		$attr['style'] .= ' color:#999 !important;';
        	}
        }
        
        $tpl = self::createElement($url ? 'a' : 'span', $attr, $title, TRUE);

        return $tpl;
    }

    
    /**
     * Създава хипервръзка със стрелка в скоби след подаден $title 
     */
	static function createLinkRef($title, $url = FALSE, $warning = FALSE, $attr = array())
	{
		// Ако има зададена иконка в линка, слагаме я преди заглавието
		if(is_array($attr) && isset($attr['ef_icon'])){
			$icon = ht::createElement('img', array('src' => sbf($attr['ef_icon'], '')));
			$title = "{$icon} {$title}";
			unset($attr['ef_icon']);
		}

		if ($url !== FALSE && (is_string($url) || (is_array($url) && count($url)))) {
            $arrowImg = ht::createElement("img", array("src" => sbf("img/16/anchor-image.png", "")));
			$link = self::createLink("<span class='anchor-arrow'>{$arrowImg}</span>", $url, $warning, $attr);
		}

		return "{$title}&nbsp;{$link}";
	}
	
	
    /**
     * Създава меню, чрез SELECT елемент
     */
    static function createSelectMenu($options, $selected, $maxRadio = 0, $attr = array())
    {
        if (count($options) < $maxRadio) {
            self::setUniqId($attr);
            $i = 0;
            $selectMenu = new ET('<div class="selectMenu">[#selectMenu#]</div>');
            foreach($options as $url => $title) {
                 $checked = $url == $selected ? ' checked="checked"' : '';
                 $style   = $url == $selected ? ' style="color:black;"' : '';
                 $i++;
                 $id = $attr['id'] . $i;
                 $selectMenu->append("\n<div class=\"selectMenuItem\">" .
                    "<input type=\"radio\" onclick=\"this.checked=true; setTimeout(function(){openUrl('{$url}', event);}, 10); \" name=\"SM{$attr['id']}\"  id=\"{$id}\"{$checked}>" .
                    "<label for=\"{$id}\"{$style}>{$title}</label></div>", 'selectMenu');
            }
        } else {
            $name = "sm" . $i;
            $attr['onChange'] = "openUrl(this.options[this.selectedIndex].value, event)";
            $attr['onfocus']  = "this.selectedIndex = -1;";
            $attr['class'] = ($attr['class'] ? $attr['class'] . ' ' : '') . "button";
            $attr['id'] = $name;
            $selectMenu = self::createSelect($name, $options, $selected, $attr);

            if ($button) {
                $selectMenu->append("<input type=\"button\" " .
                    "onclick=\"sm = document.getElementById('{$name}');" .
                    "document.location = sm.value;\" value=\"»\" " .
                    "class=\"button\">\n");
            }
        }

        return $selectMenu;
    }


    /**
     * Връща <img ..> таг с подадените атрибути
     */
    public static function createImg($attr)
    {
        if($path = $attr['path']) {
            $src = sbf($path, '');
            unset($attr['path']);
            if((log_Browsers::isRetina())) {
                if($dotPos = mb_strrpos($path, '.')) {
                    $path2x = mb_substr($path, 0, $dotPos) . '2x' . mb_substr($path, $dotPos);
                    if(getFullPath($path2x)) {
                        $url2x = sbf($path2x, '');
                        $attr['srcset']   = "{$url2x} 2x";
                    }
                }

            }
            $attr['src'] = $src;
        }

        if(!isset($attr['alt'])) {
            $attr['alt'] = '';
        }

        $res = self::createElement('img', $attr);

        return $res;
    }


    /**
     * Създава лейаут, по зададени блокове, като плейсхолдери
     */
    static function createLayout($blocks)
    {
        preg_match_all('/\[#([a-zA-Z0-9_]{1,})#\]/', $blocks, $matches);

        $blocksArr = $matches[1];

        foreach($blocksArr as $b) {
            $from = "[#{$b}#]";
            $to = "<!--ET_BEGIN {$b}--><div class='{$b}'>[#{$b}#]</div><!--ET_END {$b}-->";
            $blocks = str_replace($from, $to, $blocks);
        }

        $layout = new ET($blocks);

        return $layout;
    }

    
    /**
     * Създава лейаут, по зададени блокове, като плейсхолдери
     * 
     * @param mixed $body                       - тяло
     * @param title $hint                       - текст на хинта
     * @param notice|warning|error|string $icon - име на иконката
     * @param boolean $appendToEnd              - дали хинта да се добави в края на стринга
     * @param array $attr                       - атрибути на елемента
     * @return core_ET $element                 - шаблон с хинта
     */
    public static function createHint($body, $hint, $icon = 'notice', $appendToEnd = TRUE, $attr = array())
    {
    	if(empty($hint)) return $body;
    	if(Mode::is('printing') || Mode::is('text', 'xhtml') || Mode::is('pdf')) return $body;
    	
    	$hint = strip_tags(tr($hint));
 
    	$iconPath = ($icon == 'notice') ? 'img/16/info-gray.png' : (($icon == 'warning') ? 'img/16/dialog_warning.png' : (($icon == 'error') ? 'img/16/dialog_error.png' : $icon));
    	expect(is_string($iconPath), $iconPath);
    	
    	$attr = arr::make($attr, TRUE) + array('src' => sbf($iconPath, ''));
    	$iconHtml = ht::createElement("img", $attr);
    	
    	if($appendToEnd === TRUE){
    		$elementTpl = "[#body#] <span class='endTooltip' style='position: relative; top: 2px;' title='[#hint#]' rel='tooltip'>[#icon#]</span>";
    	} else {
    		$elementTpl = "<span class='frontToolip' style='position: relative; top: 2px;' title='[#hint#]' rel='tooltip'>[#icon#]</span> [#body#]";
    	}
    	
    	$hint = str_replace("'", '"', $hint);
    	
    	$element = new core_ET($elementTpl);
        $element->append($body, 'body');
        $element->append($hint, 'hint');
        $element->append($iconHtml, 'icon');
        
    	return $element;
    }
    
    
    /**
     * Прави html представяне на структурата на обекта, масива или променливата
     */
    static function wrapMixedToHtml($html, $wholeDocument = FALSE)
    {
        $styles =   "    .dump {font-family: Consolas,Courier New,monospace; monospace; font-size:13px; padding-bottom:5px;}\n" .
                    "    .dump ul {list-style-type: none; margin:0;margin-left:10px; border-left:solid 1px #bbb; padding:0; padding-left:3px;}\n" .
                    "    .dump li {margin-top:3px;display:table;}\n" .
                    "    .dump .trigger {cursor:pointer}\n" .
                    "    .dump {max-width:100%; white-space:nowrap;overflow-x:auto;overflow-y:hidden;}\n" .
                    "    .dump .undefined {color:red}" .
                    "    .dump .static {color:#009900}" .
                    "    .dump .protected {color:#003366}" .
                    "    .dump .private {color:#330066}" .
                    "    .dump .undefined {color:#cc0000}" .
                    "    .dump .unknown {color:#c96}" ;

        $scripts =  "\$('document').ready(function() {\$('.trigger').click(function(event){\n" .
                    "var obj = \$(this).parent().children('ul')[0];\n" .
                    "var sp  = \$(this);\n" .
                    "if(\$(obj).hasClass('hidden')){\n" .
                    "    \$(sp).css('border-bottom', 'none');\n" .
                    "    \$(obj).removeClass('hidden').slideDown();\n" .
                    "} else {\n" .
                    "    \$(sp).css('border-bottom', 'dotted 1px #bbb');\n" .
                    "    \$(obj).addClass('hidden').slideUp();\n" .
                    "}\n" .
                    "event.stopPropagation();});\n" .
                    "});\n";

        if(!$wholeDocument) {
            $tpl = new ET($html);
            $tpl->appendOnce($styles, 'STYLES');
            $tpl->appendOnce($scripts, 'SCRIPTS');
        } else {
            $tpl =  "<!DOCTYPE html>\n" .
                    "<html><head>\n" .
                    "<meta http-equiv=\"Content-Type\" content=\"text/html;charset=UTF-8\">\n" .
                    "<meta name=\"robots\" content=\"noindex,nofollow\">\n" .
                    "<title>BP on " . date("Y-m-d H:i:s") . "</title>\n" .
                    "<script src=\"https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js\"></script>\n" .
                    "<style>\n" .
                    $styles .
                    "</style>\n" .
                    "</head>\n" .
                    "<body>\n" .
                    $html . "\n" .
                    "<script>\n" .
                    $scripts .
                    "</script>\n" .
                    "</body>\n" .
                    "</html>\n";
        }

        return $tpl;
    }


    /**
     * Прави html представяне на структурата на обекта, масива или променливата
     */
    static function mixedToHtml($o, $hideLevel = 3, $maxLevel = 5, $prefix = '')
    {
        static $i = 0;

        $i++;
    
        $r = gettype($o);

        if ($i > $maxLevel) {
            $i--;
            
            if(is_array($o)) {
                $res = '(array)...';
            } elseif(is_object($o)) {
                $res = '(object)...';
            } elseif(is_scalar($o)) {
                $res = htmlentities($o, ENT_COMPAT | ENT_IGNORE, 'UTF-8');
            } else {
                $res = '...';
            }

            return $res;
        }

        $scopeArr = array();

        if (is_object($o)) {

            $res = array();

            $class = $r = get_class($o);
            
            // По-подразбиране променливите имат публична видимост
            $scope = '';

            $res = get_object_vars($o);

            if(strtolower($class) != 'stdclass') {
                $scope = 'undefined';
                do {
                    $reflection = new ReflectionClass($class);
                    foreach($reflection->getProperties(
                                            ReflectionProperty::IS_PUBLIC |
                                            ReflectionProperty::IS_STATIC | 
                                            ReflectionProperty::IS_PROTECTED | 
                                            ReflectionProperty::IS_PRIVATE) as $prop) {
                        $prop->setAccessible(TRUE);
                        $name = $prop->getName();

                        if(!$scopeArr[$name]) {
                            $res[$name] = $prop->getValue($o);
                            if($prop->isStatic()) {
                                $scopeArr[$name] = 'static';
                            } elseif($prop->isPublic()) { 
                                $scopeArr[$name] = '';
                            } elseif($prop->isPrivate()) {
                                $scopeArr[$name] = 'private';
                            } elseif($prop->isProtected()) {
                                $scopeArr[$name] = 'protected';
                            } else {
                                $scopeArr[$name] = 'unknown';
                            }
                        }
                    }
                    
                } while($class = get_parent_class($class));
            }

            if(count($res)) {
                foreach($res as $name => $vR) {
                    if(!isset($scopeArr[$name])) {
                        $scopeArr[$name] = $scope;
                    }
                }
            }

            $o = $res;
        }

        if (is_array($o)) {
            if($i >= $hideLevel + 1) {
                $html = "\n($r)\n<ul class='hidden' style='display:none;'>";
            } else {
                $html = "\n($r)\n<ul>";
            }

            if($i >= $hideLevel && $i < $maxLevel) {
                $style = 'border-bottom:dotted 1px #bbb;';
            } else {
                $style = '';
            }

            if (count($o)) {
                foreach ($o as $name => $value) {
                    
                    $attr = array('class' => '', 'title' => '', 'style' => '');
                    
                    if(isset($scopeArr[$name])) {
                        $attr['class'] = $scopeArr[$name];
                        $attr['title'] = $scopeArr[$name];
                    }

                    if($name === 'dbPass') {
                        $html .= "\n    <li>" . self::createElement('span', $attr, "$name : ******")  . "</li>";
                    } else {
                        if(is_scalar($value) || $value === NULL || (is_array($value) && count($value) ==0)) {
                            $html .= "\n    <li>" . self::createElement('span', $attr, htmlentities($name, ENT_COMPAT | ENT_IGNORE, 'UTF-8')) . " : " . 
                                self::mixedToHtml($value, $hideLevel, $maxLevel) . "</li>";
                        } else {

                            $attr['style'] = $style;
                            if($i < $maxLevel) {
                                $attr['class'] .= ' trigger';
                            }

                            $html .= "\n    <li>" . self::createElement('span', $attr, htmlentities($name, ENT_COMPAT | ENT_IGNORE, 'UTF-8')) . " : " .
                                self::mixedToHtml($value, $hideLevel, $maxLevel) . "</li>";
                        }
                    }
                }
            }
            $html .= "\n</ul>";
        } elseif (is_string($o)) {
            $html = "($r) " . htmlentities($o, ENT_COMPAT | ENT_IGNORE, 'UTF-8');
        } elseif (is_bool($o)) {
            $html = "($r) " . ($o ? 'TRUE' : 'FALSE');
        } else {
            $html = "($r) " . $o;
        }
        $i--;
        

        if($i == 0) {
            $html = "<div class='dump'>{$prefix}{$html}</div>";
        }

        return $html;
    }


    /**
     * Прави dump на масив в html представяне
     */
    public static function arrayToHtml($arr, $openLevels = 3, $viewLevels = 5)
    {
        $result = '';

        foreach ($arr as $id => $item) {
            if($result) {
                $result .= "<div style='margin-top:5px;padding-top:5px;border-top:dotted 1px #999;'>";
            } else {
                $result .= "<div style='margin-top:5px;'>";
            }

            if($id && !is_int($id)) {
                $prefix = "{$id}: ";
            } else {
                $prefix = '';
            }
            $result .= self::mixedToHtml($item, $openLevels, $viewLevels, $prefix);
            $result .= "</div>";
        }

        return $result;
    }


    /**
     * Задава уникално значение на атрибута $attr['id'] (в текущия хит)
     */
    static function setUniqId(&$attr)
    {
        if (!$attr['id']) {
       
            static $id;
            $id++;
            $name = $attr['name'] ? $attr['name'] : 'autoElement';
            $name = str_replace(array('[', ']'), array('_', '_'), $name);
            $attr['id'] = $name . rand(1000, 9999) . '_' .$id;
        }
    }


    /**
     * Извлича текста от посочения HTML
     */
    static function extractText($html)
    {
        $search = array('@<script[^>]*?>.*?</script>@si',  // Strip out javascript 
                        '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags 
                        '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly 
                        '@<![\s\S]*?--[ \t\n\r]*>@',         // Strip multi-line comments including CDATA 
                        '@[\s]+@'
                   ); 
        $text = trim(preg_replace($search, ' ', $html)); 
        
        return $text; 
    } 


    /**
     * Добавя икона като бекграунд в атрибутите
     */
    public static function addBackgroundIcon($attr, $icon = NULL)
    {
        if(!$icon) {
            $icon = $attr['ef_icon'];
            unset($attr['ef_icon']);
        }

        if(!empty($icon) && getFullPath($icon)) {

            $attr['class'] .= ($attr['class'] ? ' ' : '') . 'linkWithIcon';
            
            $attr['style'] = self::getIconStyle($icon, $attr['style']);
        }

        return $attr;
    }


    /**
     * Връща стил с включен бекграунд за икона
     */
    static function getIconStyle($icon, $style = 'background-size:16px 16px;')
    {   
        if(!empty($icon)) {
            if(log_Browsers::isRetina()) {
                $icon2 = str_replace('/16/', '/32/', $icon);
               
                if(getFullPath($icon2)) {
                    $icon = $icon2;
                }
            }

            $iconSrc = sbf($icon, '', Mode::is('text', 'xhtml'));

            $style = rtrim($style, ' ;');

            $style .= ($style ? '; ' : '') . "background-image:url('{$iconSrc}');";
        }

        return $style;
    }
    
    
    /**
     * Подготвя атрибутите на бутон или хипервръзка
     */
    private static function prepareLinkAndBtnAttr($attr, $warning = '')
    {
        $attr = arr::make($attr);
        
        if($attr['title']) {
            $attr['title'] = tr($attr['title']);
        }

        // Вкарваме предупреждението
        if ($warning) {
            $attr['onclick'] .= " if (!confirm('" . str_replace("'", "\'", tr($warning)) . "')) return false; ";
        }

        return $attr;
    }


}
