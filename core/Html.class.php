<?php



/**
 * Клас 'core_Html' ['ht'] - Функции за генериране на html елементи
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
class core_Html
{
    
    
    /**
     * Композира xHTML елемент
     */
    static function createElement($name, $attributes, $body = "", $closeTag = FALSE)
    {
        if ($name) {
            
            if (count($attributes)) {
                foreach ($attributes as $atr => $content) {
                    // Смятаме, че всички атрибути с имена, започващи със '#'
                    // са вътрешни и поради това не ги показваме в елемента
                    if ($atr{0} == '#')
                    continue;
                    
                    if (is_string($content)) {
                        $content = str_replace(array('&', "\""), array('&amp;', "&quot;"), $content);
                    }
                    
                    $attrStr .= " " . $atr . "=\"" . $content . "\"";
                }
            }
            
            if (empty($body) && !$closeTag) {
                $element = "<{$name}{$attrStr}>";
            } else {
                $element = "<{$name}{$attrStr}>{$body}</{$name}>";
            }
        } else {
            // Ако нямаме елемент, т.е. елемента е празен, връщаме само тялото
            $element = $body;
        }
        
        return new ET('[#1#]', $element);
    }
    
    
    /**
     * Създава редактируем комбо-бокс, съчетавайки SELECT с INPUT
     */
    static function createCombo($name, $value, $attr, $options)
    {
        $tpl = new ET();
        
        $suffix = '_comboSelect';
        
        // За съвместимост с IE
        $tpl->appendOnce("\n<!--[if IE 7]><STYLE>Select.combo {margin-top:1px !important;}</STYLE><![endif]-->", 'HEAD');
        $tpl->appendOnce("\n<!--[if IE 6]><STYLE>Select.combo {margin-top:1px !important;}</STYLE><![endif]-->", 'HEAD');
        
        $attr['name'] = $name;
        
        ht::setUniqId($attr);
        
        $attr['class'] .= ' combo';
        $attr['value'] = $value;
        $id = $attr['id'];
        
        if ($attr['ajaxAutoRefreshOptions']) {
            $attr['onkeydown'] = "focusSelect(event, '{$id}{$suffix}');";
            $attr['onkeyup'] = "  if(typeof(this.proc) != 'undefined') {clearTimeout(this.proc); delete this.proc;} this.proc = setTimeout( \"  document.getElementById('" . $id . "').onchange();\", 1500); ";
            $attr['onchange'] .= "if(typeof(this.proc) != 'undefined') {clearTimeout(this.proc); delete this.proc;} ajaxAutoRefreshOptions('{$id}','{$id}{$suffix}'" . ", this, {$attr['ajaxAutoRefreshOptions']});";
            unset($attr['ajaxAutoRefreshOptions']);
        }
        
        unset($attr['onblur']);
        
        $tpl->append(ht::createElement('input', $attr));
        
        $attr['onchange'] = "comboSelectOnChange('" . $attr['id'] . "',this.value);";
        
        $tpl->appendOnce("comboBoxInit('{$attr['id']}', '{$suffix}'); ", "ON_LOAD");
        
        $attr['id'] = $attr['id'] . $suffix;
        
        // Долното кара да не работи селекта в firefox-mobile
        //$attr['tabindex'] = "-1";
        
        unset($attr['size'], $attr['onkeypress'], $attr['onclick'], $attr['ondblclick']);
        
        $tpl->prepend(ht::createSelect($name, $options, $value, $attr));
        
        return $tpl;
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
            if ($atr{0} == '#')
            continue;
            
            if (is_string($content)) {
                $content = str_replace(array('&', "\""), array('&amp;', "&quot;"), $content);
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
                            //$select->append("</optgroup>", 'OPTIONS');
                        }
                        $element = 'optgroup';
                        $attr = $title->attr;
                        $attr['label'] = $title->title;
                        $option = ht::createElement($element, $attr);
                        $select->append($option, 'OPTIONS');
                        $select->append("</optgroup>", 'OPTIONS');
                        $openGroup = TRUE;
                        continue;
                    } else {
                        $attr = $title->attr;
                        $title = $title->title;
                    }
                }
                
                if (!isset($attr['value'])) {
                    $attr['value'] = $id;
                }
                
                if ($attr['value'] == $selected) {
                    $attr['selected'] = 'selected';
                }
                
                // Хак за добавяне на плейс-холдер
                if($selAttr['placeholder'] &&
                    empty($attr['value']) && !trim($title)) {
                    $title = $selAttr['placeholder'];
                    $attr['style'] .= 'color:#666;';
                }
                
                $option = ht::createElement($element, $attr, $title);
                $select->append("\n", 'OPTIONS');
                $select->append($option, 'OPTIONS');
            }
        }
        
        if($openGroup) {
            // затваряме групата                
            // $select->append("</optgroup>", 'OPTIONS');
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
        $optionsCnt = ht::countOptions($options);
        
        if($optionsCnt <= 1) {
            // Когато имаме само една опция, правим readOnly <input>
            
            expect($optionsCnt>0, "'Липсват опции за '{$name}'");
            
            foreach($options as $id => $opt) {
                
                if(is_object($opt) && $opt->group) continue;
                
                $value = is_object($opt) ? $opt->title : $opt;
                $attr = is_object($opt) ? $opt->attr : array();
                
                break;
            }
            
            $input = ht::createElement('select', array(
                    'readonly' => 'readonly',
                    'style' => 'background:#ddd;border:solid 1px #aaa;' .
                    $attr['style']
                ), "<option>$value</option>", TRUE);
            
            $input->append(ht::createElement('input', array(
                        'type' => 'hidden',
                        'name' => $name,
                        'value' => $id
                    )));
        } elseif($optionsCnt <= $maxRadio) {
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
                $tpl = "<table class='keylist'><tr>";
                
                for($i = 1; $i <= $col; $i++) {
                    $tpl .= "<td valign=top>[#OPT" . ($i-1) . "#]</td>";
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
                    
                    $input->append(ht::createElement('div', $opt->attr, $opt->title));
                    
                    $indent = '&nbsp;&nbsp;&nbsp;';
                } else {
                    $title = is_object($opt) ? $opt->title : $opt;
                    $attrLabel = is_object($opt) ? $opt->attr : array();
                    $radioAttr = array('type' => 'radio', 'name' => $name, 'value' => $id);
                    
                    ht::setUniqId($radioAttr);
                    
                    if($value == $id) {
                        $radioAttr['checked'] = 'checked';
                    } else {
                        unset($radioAttr['checked']);
                    }
                    
                    $radioAttr['class'] .= ' radiobutton';
                    
                    $input->append($indent);
                    
                    $input->append(ht::createElement('input', $radioAttr));
                    
                    $attrLabel['for'] = $radioAttr['id'];
                    
                    $input->append(ht::createElement('label', $attrLabel, $title));
                    
                    $input->append("<br>");
                }
                
                $tpl->append($input, 'OPT' . ($i % $col));
                
                $i++;
            }
            
            $input = ht::createElement('div', $attr, $tpl);
        } else {
            $input = ht::createSelect($name, $options, $value, $attr);
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
                expect(is_scalar($value) || !($value), gettype($value));
                $attr = array();
                $attr['name'] = $name;
                $attr['value'] = $value;
                $attr['type'] = 'hidden';
                $tpl->append(ht::createElement('input', $attr));
            }
        }
        
        return $tpl;
    }
    
    
    /**
     * Създава текстов INPUT
     */
    static function createTextInput($name, $value = NULL, $attr = array())
    {
        if ($name) {
            $attr['name'] = $name;
        }
        
        if (isset($value)) {
            $attr['value'] = $value;
        }
        
        // Ако не се намираме в тестов режим, то изключваме по подразбиране 
        // autocomplete за всички INPUT полета
        if(!isDebug()) {
            setIfNot($attr['autocomplete'], 'off');
        }
        
        $input = ht::createElement("input", $attr);
        
        return $input;
    }
    
    
    /**
     * Създава текстово поле
     */
    static function createTextArea($name, $value = "", $attr = array())
    {
        if (!$attr['cols']) {
            // $attr['cols'] = 40;
        }
        
        if (!$attr['rows']) {
            // $attr['rows'] = 15;
        }
        $attr['name'] = $name;
        
        $value = str_replace(array('&', "<" . ">"), array('&amp;', "&gt;", "&lt;"), $value);
        
        return ht::createElement('textarea', $attr, $value, TRUE);
    }
    
    
    /**
     * Създава бутон - хиперлинк
     */
    static function createBtn($title, $url = array(), $warning = FALSE, $newWindow = FALSE, $attr = array())
    {
        $title = tr($title);
        
        $attr = arr::make($attr);
        
        // Ако URL-то е празно - забраняваме бутона
        if((is_array($url) && count($url) == 0) || !$url) {
            $attr['disabled'] = "disabled";
        }
        
        // Правим URL-to
        $url = toUrl($url);
        
        // Подготвяме атрибутите
        $attr['class'] .= ($attr['class'] ? ' ' : '') . 'button';
        
        // Оцветяваме бутона в зависимост от особеностите му
        if(!$attr['disabled']) {
            if ($warning) {
                $attr['style'] .= 'color:#772200;';
            } elseif ($newWindow) {
                $attr['style'] .= 'color:#008800;';
            }
        } else {
            $attr['style'] .= 'color:#888;';
        }
        
        // Ако нямаме JavaScript правим хипервръзка
        if (Mode::is('javascript', 'no')) {
            $attr['href'] = $url;
            
            if ($newWindow)
            $attr['target'] = $newWindow;
            $attr['rel'] = 'nofollow';
            
            return ht::createElement('a', $attr, "$title");
        }
        
        // Вкарваме предупреждението
        if ($warning) {
            $attr['onclick'] .= " if (!confirm('" . str_replace("'", "\'", tr($warning)) . "')) return false; ";
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
        
        $attr['type'] = 'button';
        $attr['value'] = $title;
        
        return ht::createElement('input', $attr);
    }
    
    
    /**
     * Създава submit бутон
     */
    static function createSbBtn($title, $cmd = 'default', $warning = NULL, $newWindow = NULL, $attr = array())
    {
        $title = tr($title);
        
        if (is_string($attr)) {
            $attr = array(
                'style' => $attr
            );
        }
        
        // Вкарваме предупреждението
        if ($warning) {
            $attr['onclick'] = " if (!confirm('" . str_replace("'", "\'", tr($warning)) . "')) return false; " . $attr['onclick'];
        }
        
        $attr['name'] .= "Cmd[{$cmd}]";
        
        if (is_string($newWindow) && ($newWindow != '_blank')) {
            $attr['onclick'] .= "  this.form.target = '{$newWindow}';";
        } elseif ($newWindow) {
            $attr['onclick'] .= "  this.form.target = '_blank';";
        }
        
        $attr['type'] = 'submit';
        
        $attr['value'] = $title;
        
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
        
        $btn = ht::createElement('input', $attr);
        
        $btn->appendOnce("<input type=\"hidden\" name=\"Cmd[default]\" value=1>",
            'FORM_HIDDEN');
        
        return $btn;
    }
    
    
    /**
     * Създава бутон, който стартира javascript функция
     */
    static function createFnBtn($title, $function, $warning = NULL, $attr = array())
    {
        // Вкарваме предупреждението, ако има такова
        if ($warning) {
            $attr['onclick'] .= " if (!confirm('" .
            str_replace("'", "\'", tr($warning)) . "')) return false; ";
        }
        
        $attr['onclick'] .= $function;
        
        $attr['type'] = 'button';
        
        $attr['value'] = tr($title);
        
        // Оцветяваме бутона в зависимост от особеностите му
        if ($warning) {
            $attr['style'] .= 'color:#772200;';
        }
        
        $attr['class'] .= ($attr['class'] ? ' ' : '') . 'button';
        
        $btn = ht::createElement('input', $attr);
        
        return $btn;
    }
    
    
    /**
     * Създава хипервръзка
     */
    static function createLink($title, $url = FALSE, $warning = FALSE, $attr = array())
    {
        $attr = arr::make($attr);
        
        if ($warning) {
            $attr['onclick'] = "if (!confirm('" . str_replace("'", "\'", $warning) .
            "')) return false; " . $attr['onclick'];
        }
        
        if (is_array($url)) {
            if(count($url)) {
                $url = toUrl($url);
            } else {
                $url = '';
            }
        }
        
        if($url) {
            $attr['href'] = $url;
        }
        
        $tpl = ht::createElement('a', $attr, $title);
        
        return $tpl;
    }
    
    
    /**
     * Създава меню, чрез SELECT елемент
     */
    static function createSelectMenu($options, $selected, $button = FALSE, $attr = array())
    {
        if (!Mode::is('screenMode', 'narrow') && count($options) < 10) {
            $selectMenu = new ET('');
            $attr['type'] = 'button';
            $attr['class'] = 'button';
            
            foreach ($options as $url => $title) {
                $attr['onclick'] = '';
                $attr['value'] = '';
                $attr['onclick'] = $url;
                $attr['value'] = $title;
                $selectMenu->append(ht::createElement('input', $attr));
                $selectMenu->append('&nbsp;\n');
            }
        } else {
            $name = "sm" . $i;
            $attr['onchange'] = "document.location =  this.value ";
            $attr['class'] = ($attr['class'] ? $attr['class'] . ' ' : '') . "button";
            $attr['id'] = $name;
            $selectMenu = ht::createSelect($name, $options, $selected, $attr);
            
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
     * Прави html представяне на структурата на обекта, масива или променливата
     */
    static function mixedToHtml($o)
    {
        static $i;
        
        $i++;
        
        if ($i > 4) {
            $i--;
            
            return "...";
        }
        
        $r = gettype($o);
        
        if (is_object($o)) {
            $r = get_class($o);
            $o = get_object_vars($o);
        }
        
        if (is_array($o)) {
            $r = "($r)<div style='margin-left:10px; border-left:solid 1px #ccc; padding-left:3px;'>";
            
            if (count($o)) {
                foreach ($o as $name => $value) {
                    if($name === 'dbPass') {
                        $r .= "$name : ******<br>";
                    } else {
                        $r .= "$name : " . ht::mixedToHtml($value) . "<br>";
                    }
                }
            }
            $r .= "</div>";
        } elseif (is_string($o)) {
            $r = "($r) " . htmlentities($o, ENT_COMPAT | ENT_IGNORE, 'UTF-8');
        } elseif (is_bool($o)) {
            $r = "($r) " . ($o ? 'TRUE' : 'FALSE');
        } else {
            $r = "($r) " . $o;
        }
        $i--;
        
        return $r;
    }
    
    
    /**
     * Задава уникално значение на атрибута $attr['id'] (в текущия хит)
     */
    static function setUniqId(&$attr)
    {
        if ($attr['id'])
        return;
        static $id;
        $id++;
        $name = $attr['name'] ? $attr['name'] : 'autoElement';
        $attr['id'] = $name . $id;
    }
}