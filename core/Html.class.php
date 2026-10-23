<?php


/**
 * Клас 'core_Html' ['ht'] - Функции за генериране на html елементи
 *
 *
 * @category  ef
 * @package   core
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class core_Html
{
    /**
     * Композира xHTML елемент
     */
    public static function createElement($name, $attributes = array(), $body = null, $closeTag = false, $translate = true)
    {
        $attrStr = '';

        $isTitleTranslated = false;
        if (($attributes['title'] ?? null) && $translate && (($attributes['translate'] ?? null) != 'no')) {
            $attributes['title'] = tr($attributes['title']);
            $isTitleTranslated = true;
            $attributes['title'] = str_replace(array("\""), array("&quot;"), $attributes['title']);
        }
        
        if ($name == 'img') {
            if (!is_array($attributes)) {
                $attributes = array();
            }
            if (!isset($attributes['alt'])) {
                $attributes['alt'] = '';
            }
        }
        
        if ($name) {
            if (is_array($attributes)) {
                foreach ($attributes as $atr => $content) {
                    // Смятаме, че всички атрибути с имена, започващи със '#'
                    // са вътрешни и поради това не ги показваме в елемента
                    if (is_string($atr) && isset($atr[0]) && $atr[0] == '#') continue;

                    if (is_array($content)) {
                        $flatContent = array();
                        array_walk_recursive($content, function ($c) use (&$flatContent) {
                            $flatContent[] = $c;
                        });
                        $content = implode(' ', $flatContent);
                    }

                    if (is_string($content)) {
                        /**
                         * Необходимо ли е да се ескейпва символи различни от двойни кавички
                         * в стойностите на HTML атрибутите?
                         */
                        if($atr != 'title' || !$isTitleTranslated){
                            $content = self::escapeAttr($content);
                        }
                    }
                    
                    $attrStr .= ' ' . $atr . '="' . $content . '"';
                }
            }
            
            if (($body === null || $body === false) && !$closeTag) {
                $element = "<{$name}{$attrStr}>";
            } else {
                if (in_array(strtolower($name), array('textarea', 'option'))) {
                    if (is_array($body) || (is_object($body) && !method_exists($body, '__toString'))) {
                        $body = print_r($body, true);
                    }
                    $body = str_replace(array('&', '<', '>'), array('&amp;', '&lt;', '&gt;'), $body ?? '');
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
    public static function escapeAttr($attrContent)
    {
        //$content = str_replace(array('&', "\""), array('&amp;', "&quot;"), $attrContent);
        $content = htmlspecialchars((string) $attrContent, ENT_QUOTES, null);
        $content = str_replace(array("\r\n", "\n\r", "\n"), array('&#13;&#10;', '&#10;&#13;', '&#10;'), $content);
        
        return $content;
    }
    
    
    /**
     * Създаване на даталист с опции
     *
     * @param int   $id      - ид на даталиста
     * @param array $options - опции на листа
     * @param array $attr    - атрибути
     *
     * @return core_ET $tpl  - шаблон на даталиста
     */
    public static function createDataList($id, $options = array(), $attr = array())
    {
        $tpl = new core_ET('');
        $tpl->append(self::createElement('datalist', array('id' => $id)));
        if (is_array($options)) {
            unset($options['']);
            foreach ($options as $key => $v) {
                $tpl->append("\n" . self::createElement('option', array('value' => $v)));
            }
        }
        $tpl->append(self::createElement('/datalist'));
        
        return $tpl;
    }
    
    
    /**
     * Създава редактируем комбо-бокс, съчетавайки SELECT с INPUT
     */
    public static function createCombo($name, $value, $attr = array(), $options = array())
    {
        $attr['name'] = $name;
        
        self::setUniqId($attr);
        
        if (Mode::is('javascript', 'no')) {
            $listId = $attr['id'] . '_list';
            
            $attr['list'] = $listId;
            $tpl = self::createElement('input', $attr);
            $tpl->append(self::createDataList($listId, $options));
        } else {
            $tpl = new ET();
            
            // За съвместимост с IE
            $tpl->appendOnce("\n<!--[if IE 7]><STYLE>Select.combo {margin-top:1px !important;}</STYLE><![endif]-->", 'HEAD');
            $tpl->appendOnce("\n<!--[if IE 6]><STYLE>Select.combo {margin-top:1px !important;}</STYLE><![endif]-->", 'HEAD');


            $attr['class'] = ($attr['class'] ?? '') . ' combo';
            $attr['value'] = $value;
            $id = $attr['id'];
            
            $suffix = '_cs';
            list($l, $r) = array_pad(explode('[', $id), 2, null);
            $r = rtrim($r ?? '', ']');
            $selectId = $l . $suffix . $r;
            
            if (!empty($attr['ajaxAutoRefreshOptions'])) {
                $attr['onkeydown'] = "focusSelect(event, '{$selectId}');";
                $attr['onkeyup'] = "  if(typeof(this.proc) != 'undefined') {clearTimeout(this.proc); delete this.proc;} this.proc = setTimeout( \"  $('#" . $id . "').change();\", 1500); ";
                if (!empty($attr['onchange'])) {
                    $attr['onchange'] = "if(isOptionExists('" . $selectId . "', this.value)) {" . $attr['onchange'] . '} ';
                }
                $attr['onchange'] = ($attr['onchange'] ?? '') . "if(typeof(this.proc) != 'undefined') {clearTimeout(this.proc); delete this.proc;} ajaxAutoRefreshOptions('{$id}','{$selectId}'" . ", this, {$attr['ajaxAutoRefreshOptions']});";
                unset($attr['ajaxAutoRefreshOptions']);
            }
            
            unset($attr['onblur']);
            $attr['type'] = 'text';
            $attr['autocomplete'] = 'off';
            $tpl->append(self::createElement('input', $attr));
            
            unset($attr['autocomplete'], $attr['type']);
            
            $attr['onchange'] = "comboSelectOnChange('" . $attr['id'] . "', this.value, '{$selectId}');";
            
            jquery_Jquery::run($tpl, "comboBoxInit('{$attr['id']}', '{$selectId}');", true);
            
            $attr['id'] = $selectId;
            
            $name = $attr['name'];
            list($l, $r) = array_pad(explode('[', $name), 2, null);
            $r = rtrim($r ?? '', ']');
            $name = $l . $suffix . $r;
            $attr['name'] = $name;
            
            // Долното кара да не работи селекта в firefox-mobile, но е добре за
            // декстоп-браузърите, когато се работи с tab за превключване на полетата
            if (!Mode::is('screenMode', 'narrow')) {
                $attr['tabindex'] = '-1';
            }
            
            unset($attr['size'], $attr['onkeypress'], $attr['onclick'], $attr['ondblclick']);

            if (!Mode::is('javascript', 'no')) {
                if (!isset($attr['style'])) {
                    $attr['style'] = '';
                }
                $attr['style'] .= ';visibility: hidden;';
            }
            
            $tpl->prepend(self::createSelect($name, $options, $value, $attr));
        }
        
        return $tpl;
    }
    
    
    /**
     * Прави групиране на опциите, като за групи използва предната част, преди разделителя
     */
    public static function groupOptions($options, $div = '»')
    {
        $lastGroup = '';
        if (countR($options) > 1) {
            $groups = $newOptions = array();
            
            // За всяка опция
            $defaultGroup = '';
            foreach ($options as $index => $opt) {
                if (is_object($opt)) {
                    if (!empty($opt->group)) {
                        $defaultGroup = trim($opt->title);
                        continue;
                    }
                    $title = $opt->title ?? '';
                } else {
                    $title = $opt;
                }

                // Ако в името на класа има '->' то приемаме, че стринга преди знака е името на групата
                list($group, $caption) = array_pad(explode($div, (string) $title, 2), 2, null);

                if (!$caption) {
                    $caption = $group;
                    $group = $defaultGroup;
                } elseif (!$group) {
                    $group = $lastGroup;
                }
                
                $groups[$lastGroup = trim($group)][$index] = trim($caption);
            }
            
            // Ако има поне една намерена OPTGROUP на класовете, Иначе не правим нищо
            if (countR($groups)) {
                if (isset($groups[''])) {
                    asort($groups['']);
                    $newOptions += $groups[''];
                    unset($groups['']);
                }
                foreach ($groups as $group => $optArr) {
                    // Добавяме името като OPTGROUP
                    if ($group) {
                        $newOptions[$group] = (object) array(
                            'title' => $group,
                            'group' => true,
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
    public static function createSelect($name, $options, $selected = null, $selAttr = array())
    {
        $selAttr['name'] = $name;

        $attrStr = '';

        foreach ($selAttr as $atr => $content) {
            // Смятаме, че всички атрибути с имена, започващи със '#'
            // са вътрешни и поради това не ги показваме в елемента
            // Същото правим и за атрибутите placeholder и value
            if ((is_string($atr) && isset($atr[0]) && $atr[0] == '#') || $atr == 'placeholder' || $atr == 'value') {
                continue;
            }
            
            if (is_string($content)) {
                $content = self::escapeAttr($content);
            }
            
            $attrStr .= ' ' . $atr . '="' . $content . '"';
        }
        
        $select = new ET("<select{$attrStr}>[#OPTIONS#]</select>");
        
        $select->append('', 'OPTIONS');
        
        if (is_array($options)) {
            $openGroup = false;
            foreach ($options as $id => $title) {
                $attr = array();
                $element = 'option';
                
                if (is_object($title)) {
                    if (!empty($title->group)) {
                        if ($openGroup) {
                            // затваряме групата
                            $select->append('</optgroup>', 'OPTIONS');
                        }
                        $element = 'optgroup';
                        $attr = is_array($title->attr ?? null) ? $title->attr : array();
                        $attr['label'] = $title->title ?? '';
                        $option = self::createElement($element, $attr);
                        $select->append($option, 'OPTIONS');
                        $openGroup = true;
                        continue;
                    } elseif ($title instanceof core_ET) {
                        $title = $title->getContent();
                    } else {
                        $attr = is_array($title->attr ?? null) ? $title->attr : array();
                        $title = $title->title ?? '';
                    }
                }
                
                if (!isset($attr['value'])) {
                    $attr['value'] = $id;
                }
                
                if (($attr['value'] . '') == $selected) {
                    if ($selected != null || $attr['value'] === '' || $attr['value'] === null) {
                        $attr['selected'] = 'selected';
                    }
                }
                
                // Хак за добавяне на плейс-холдер
                if (!empty($selAttr['placeholder']) &&
                    strlen((string) $attr['value']) == 0 && !trim((string) $title)) {
                    $title = $selAttr['placeholder'];
                    $attr['style'] = ($attr['style'] ?? '') . 'color:#777;';
                }
                
                $option = self::createElement($element, $attr, $title);
                
                $select->append("\n", 'OPTIONS');
                $select->append($option, 'OPTIONS');
            }
            
            if ($openGroup) {
                // затваряме групата
                $select->append('</optgroup>', 'OPTIONS');
            }
        }
        
        return $select;
    }
    
    
    /**
     * Преброява колко са действителните опции,
     * без да брои групите
     */
    public static function countOptions($options)
    {
        $cnt = 0;
        
        if (countR($options)) {
            foreach ($options as $opt) {
                if (!is_object($opt) || empty($opt->group)) {
                    $cnt++;
                }
            }
        }
        
        return $cnt;
    }
    
    
    /**
     * Прави SELECT, radio или disabled INPUT в зависимост от броя на опциите
     *
     * @param int $maxRadio максимален брой опции, при които се създава радио група
     */
    public static function createSmartSelect(
        $options,
        $name,
        $value = null,
        $attr = array(),
        $maxRadio = null,
        $maxColumns = null,
        $columns = null
    ) {
        $maxRadio = $maxRadio ?? 0;
        $maxColumns = $maxColumns ?? 4;

        $hasEmptyOption = is_array($options) && array_key_exists('', $options);
        $emptyOptionIsDisabled = $hasEmptyOption && is_object($options['']) &&
            is_array($options['']->attr ?? null) && !empty($options['']->attr['disabled']);
        $allowEmpty = !empty($attr['_isAllowEmpty']) || ($hasEmptyOption && !$emptyOptionIsDisabled);
        if ($allowEmpty && !$hasEmptyOption) {
            $options = array('' => '') + $options;
            $hasEmptyOption = true;
        }

        $optionsCnt = self::countOptions($options);
        
        setIfNot($attr['data-hiddenName'], $name);
        
        // Очакваме да има поне една опция
        expect($optionsCnt > 0, "Липсват опции за '{$name}'");

        // Когато имаме само една опция, правим readOnly <input>
        if ($optionsCnt == 1 && (!(bool)($attr['_isAllowEmpty'] ?? false) || array_key_exists('', $options))) {
            foreach ($options as $id => $opt) {
                if (is_object($opt) && !empty($opt->group)) {
                    continue;
                }
                
                if (is_object($opt)) {
                    if ($opt instanceof core_ET) {
                        $value = $opt->getContent();
                    } else {
                        $value = $opt->title ?? '';
                    }
                } else {
                    $value = $opt;
                }
                
                // Запазваме класа и стила на опцията
                if (is_object($opt) && is_array($opt->attr ?? null)) {
                    if (!empty($opt->attr['class'])) {
                        $attr['class'] = ($attr['class'] ?? '') . (($attr['class'] ?? '') ? ' ' : '') . $opt->attr['class'];
                    }
                    if (!empty($opt->attr['style'])) {
                        $attr['style'] = ($attr['style'] ?? '') . (($attr['style'] ?? '') ? ';' : '') . $opt->attr['style'];
                    }
                }
                
                break;
            }
            
            $attr['readonly'] = 'readonly';
            $attr['class'] = trim(($attr['class'] ?? '') . ' readonly');
            
            if (!strlen((string) $value)) {
                if (!empty($attr['placeholder'])) {
                    $value = $attr['placeholder'];
                    $attr['style'] = 'color:#777';
                } else {
                    $value = '&nbsp;';
                }
            }

            unset($attr['_isAllowEmpty']);
            $input = self::createElement('select', $attr, "<option>{$value}</option>", true);
            
            $input->append(self::createElement('input', array(
                'type' => 'hidden',
                'name' => $name,
                'value' => $id
            )));

        } elseif ($optionsCnt <= $maxRadio) {
            $keyListClass = '';
            if ($optionsCnt < 4) {
                $keyListClass = ' shrinked';
            }

            // Празната allowEmpty опция остава видима и получава еднозначен надпис
            if ($allowEmpty && $hasEmptyOption) {
                $emptyOption = $options[''];
                if ($emptyOption instanceof core_ET) {
                    $emptyTitle = trim($emptyOption->getContent());
                } elseif (is_object($emptyOption)) {
                    $emptyTitle = trim($emptyOption->title ?? '');
                } else {
                    $emptyTitle = trim((string) $emptyOption);
                }

                if (!strlen($emptyTitle)) {
                    $emptyTitle = trim($attr['placeholder'] ?? '');
                    $emptyTitle = strlen($emptyTitle) ? $emptyTitle : tr('Без избор');

                    if (is_object($emptyOption) && !($emptyOption instanceof core_ET)) {
                        $emptyOption = clone $emptyOption;
                        $emptyOption->title = $emptyTitle;
                        $options[''] = $emptyOption;
                    } else {
                        $options[''] = $emptyTitle;
                    }
                }
            } elseif ($hasEmptyOption) {
                // Служебният placeholder на задължително поле не е позволен избор
                unset($options['']);
            }

            // Когато броя на опциите са по-малко
            
            // Определяме броя на колоните, ако не са зададени.
            if (countR($options) != $optionsCnt) {
                $col = 1;
            } else {
                $col = $columns ? $columns :
                min(
                    max(4, $maxColumns),
                    round(sqrt(max(0, $optionsCnt + 1)))
                );
            }
            if ($options && is_array($options)) {
                $col = min(countR($options), $col);
            } else {
                $col = 0;
            }
            if ($col > 1) {
                $tpl = "<table class='keylist {$keyListClass}'><tr>";
                
                for ($i = 1; $i <= $col; $i++) {
                    $tpl .= "<td style='vertical-align: top;'>[#OPT" . ($i - 1) . '#]</td>';
                }
                
                $tpl = new ET($tpl . '</tr></table>');
            } else {
                $tpl = new ET('[#OPT0#]');
                $tpl->append('', 'OPT0');
            }
            
            $i = 0;

            foreach ($options as $id => $opt) {
                $input = new ET();

                if (is_object($opt) && !empty($opt->group)) {
                    $input->append(self::createElement('div', is_array($opt->attr ?? null) ? $opt->attr : array(), $opt->title ?? ''));
                    $indent = '&nbsp;&nbsp;&nbsp;';
                } else {
                    $title = is_object($opt)
                        ? (($opt instanceof core_ET) ? type_Varchar::escape($opt->getContent()) : ($opt->title ?? ''))
                        : $opt;
                    $attrLabel = (is_object($opt) && is_array($opt->attr ?? null)) ? $opt->attr : array();
                    if ($id === '' && $allowEmpty) {
                        $labelStyle = trim($attrLabel['style'] ?? '');
                        if (!preg_match('/(?:^|;)\s*color\s*:\s*#777\s*(?:;|$)/i', $labelStyle)) {
                            $attrLabel['style'] = rtrim($labelStyle, ';') . (strlen($labelStyle) ? ';' : '') . 'color:#777;';
                        }
                    }
                    $radioAttr = array('type' => 'radio', 'name' => $name, 'value' => $id);
                    
                    self::setUniqId($radioAttr);
                    
                    if ($value == $id) {
                        $radioAttr['checked'] = 'checked';
                    } else {
                        unset($radioAttr['checked']);
                    }

                    $radioAttr['class'] = ($radioAttr['class'] ?? '') . ' radiobutton';
                    if(isset($attr['onchange'])){
                        $radioAttr['onclick'] = $attr['onchange'];
                    }
                    
                    $input->append($indent ?? '');
                    
                    $input->append(self::createElement('input', $radioAttr));
                    
                    $attrLabel['for'] = $radioAttr['id'];
                    
                    $input->append(self::createElement('label', $attrLabel, $title));
                    
                    $input->append('<br>');
                }
                
                $tpl->append($input, 'OPT' . ($i % $col));
                
                $i++;
            }
            
            // Добавка (временна) за да не се свиват радио бутоните от w25 - w75
            $attr['style'] = ($attr['style'] ?? '') . ';width:100%;';

            if($allowEmpty){
                $attr['class'] = ($attr['class'] ?? '') . ' allowEmptyRadioHolder';
                unset($attr['_isAllowEmpty']);
            } else {
                $attr['class'] = ($attr['class'] ?? '') . ' notAllowEmptyRadioHolder';
            }

            $input = self::createElement('div', $attr, $tpl);
        } else {
            unset($attr['_isAllowEmpty']);
            $input = self::createSelect($name, $options, $value, $attr);
        }
        
        return $input;
    }
    
    
    /**
     * Създава скрити полета
     */
    public static function createHidden($variables)
    {
        $hiddens = arr::make($variables);
        
        $tpl = new ET();
        
        if (is_array($hiddens) && countR($hiddens)) {
            Request::doProtect($hiddens);
            
            foreach ($hiddens as $name => $value) {
                if (is_array($value)) {
                    foreach ($value as $key => $v) {
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
    public static function addHiden($tpl, $name, $value)
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
    public static function createTextInput($name, $value = null, $attr = array())
    {
        $attr = arr::make($attr);
        
        if ($name) {
            $attr['name'] = $name;
        }
        
        if (isset($value)) {
            $attr['value'] = $value;
        }
        
        $input = self::createElement('input', $attr);
        
        return $input;
    }
    
    
    private static function addAccessKey(&$attr, $title)
    {
        if (Mode::is('screenMode', 'narrow')) {
            
            return;
        }
        
        static $accessKeys;
        
        if ($accessKeys === null) {
            $accessKeys = array();
            $defLines = explode("\n", bgerp_Setup::get('ACCESS_KEYS'));
            foreach ($defLines as $l) {
                $l = trim($l);
                if ($l) {
                    list($titles, $c) = array_pad(explode('=', $l, 2), 2, null);
                    $titles = trim($titles);
                    $c = str::utf2ascii(trim($c ?? ''));
                    if (strlen($titles) > 1 && strlen($c) == 1) {
                        $titlesArr = explode(',', $titles);
                        foreach ($titlesArr as $t) {
                            $accessKeys[mb_strtolower(trim($t))] = $c;
                        }
                    }
                }
            }
        }
        
        
        if ($c = ($accessKeys[mb_strtolower($title)] ?? null)) {
            $attr['accesskey'] = $c;
            
            if (substr(log_Browsers::getUserAgentOsName(), 0, 3) == 'Mac') {
                $hint = '[Control][Alt]+' . $c;
            } elseif (log_Browsers::getUserAgentBrowserName() == 'Firefox') {
                $hint = '[Shift][Alt]+' . $c;
            } else {
                $hint = '[Alt]+' . $c;
            }

            $attr['title'] = ($attr['title'] ?? '') . (($attr['title'] ?? '') ? '|* ' : '') . $hint;
        }
    }
    
    
    /**
     * Създава бутон, който при натискане предизвиква съобщение за грешка
     */
    public static function createErrBtn($title, $error, $attr = array())
    {
        $attr = arr::make($attr);
        $icon = $attr['ef_icon'] ?? null;
        if($icon != 'none'){
            $attr['ef_icon'] = 'img/16/error.png';
        }

        // Url-то се заменя с такова водещо към грешка (fallback за режим без JavaScript, виж
        // Mode::is('javascript','no') по-долу в createBtn())
        $url = core_Message::getErrorUrl($error, 'page_Error');

        // Вместо $attr['error'] (който createBtn() превръща в нативен alert()) - стилизиран модал
        // (severity=error, само бутон "ОК", без "Отказ", натискането не води до никакво действие) -
        // виж efAlert() в js/efCommon.js
        $escaped = addcslashes(tr($error), "'\\\n\r");
        $attr['onclick'] = ($attr['onclick'] ?? '') . "if (!efAlert(event, '{$escaped}')) { return false; }";
        $attr['style'] = ($attr['style'] ?? '') . 'color:#772200;color:#9A5919;';

        return self::createBtn($title, $url, null, null, $attr);
    }
    
    
    /**
     * Създава бутон - хиперлинк
     */
    public static function createBtn($title, $url = array(), $warning = false, $newWindow = false, $attr = array())
    {
        $attr = self::prepareLinkAndBtnAttr($attr, $warning);
        
        $title = tr($title);
        
        self::addAccessKey($attr, $title);
        
        // Ако URL-то е празно - забраняваме бутона
        if ((is_array($url) && countR($url) == 0) || !$url) {
            $attr['disabled'] = 'disabled';
        }
        
        // URL с потвърждение
        if (is_array($url) && $warning) {
            $content = ($url[1] ?? '') . (($url['id'] ?? null) ? $url['id'] : ($url[2] ?? ''));
            if ($content) {
                $url['Cf'] = core_Request::getSessHash($content);
            }
        }
        
        // Правим URL-to
        try {
            $url = toUrl($url);
        } catch (core_exception_Expect $e) {
            $url = null;
            $attr['style'] = ($attr['style'] ?? '') . ' border:dotted 1px red;';
        }
        
        // Подготвяме атрибутите
        $attr['class'] = ($attr['class'] ?? '') . (($attr['class'] ?? '') ? ' ' : '') . 'button';

        // Оцветяваме бутона в зависимост от особеностите му
        if (empty($attr['disabled'])) {
            if (!empty($attr['error'])) {
                $attr['style'] = ($attr['style'] ?? '') . 'color:#9A5919;';
            } elseif ($warning) {
                $attr['style'] = ($attr['style'] ?? '') . 'color:#772200;';
            } elseif ($newWindow) {
                $attr['style'] = ($attr['style'] ?? '') . 'color:#008800;';
            }
        } else {
            $attr['style'] = ($attr['style'] ?? '') . 'color:#888;';
        }
        
        // Добавяме икона на бутона, ако има
        if (!Mode::is('screenMode', 'narrow')) {
            $attr = self::addBackgroundIcon($attr);
        } else {
            if (trim($title)) {
                unset($attr['ef_icon']);
            } else {
                $attr = self::addBackgroundIcon($attr);
            }
        }
        
        // Ако нямаме JavaScript правим хипервръзка
        if (Mode::is('javascript', 'no')) {
            $attr['href'] = $url;
            
            if ($newWindow) {
                $attr['target'] = $newWindow;
            }
            
            $attr['rel'] = 'nofollow';
            
            return self::createElement('a', $attr, "{$title}");
        }
        
        // Вкарваме JavaScript-a
        if ($newWindow) {
            if (is_string($newWindow) && ($newWindow != '_blank')) {
                $attr['onclick'] = ($attr['onclick'] ?? '') . " window.open('{$url}','{$newWindow}')";
            } else {
                $attr['onclick'] = ($attr['onclick'] ?? '') . " window.open('{$url}')";
            }
        } else {
            $attr['onclick'] = ($attr['onclick'] ?? '') . " document.location='{$url}'";
        }
        
        // Ако имаме грешка - показваме я и не продължаваме
        if (!empty($attr['error'])) {
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
    public static function createSbBtn($title, $cmd = 'default', $warning = null, $newWindow = null, $attr = array())
    {
        $attr = self::prepareLinkAndBtnAttr($attr, $warning);
        
        $title = tr($title);
        
        self::addAccessKey($attr, $title);

        if (is_array($cmd) && !isset($attr['name'])) {
//            wp('За CMD се подава масив: ', $cmd);
            $cmd = $cmd[1] ?? $cmd[0] ?? '';
        }

        $attr['name'] = (is_scalar($attr['name'] ?? null) ? ($attr['name'] ?? '') : '') . "Cmd[" . (is_scalar($cmd) ? $cmd : '') . "]";

        $attr['onclick'] ??= '';
        if (is_string($newWindow) && ($newWindow != '_blank')) {
            $attr['onclick'] .= "  this.form.target = '{$newWindow}';";
        } elseif ($newWindow) {
            $attr['onclick'] .= "  this.form.target = '_blank';";
        }
        
        $attr['type'] = 'submit';
        
        $attr['value'] = $title;
        
 
       
        // Оцветяваме бутона в зависимост от особеностите му
        $attr['style'] ??= '';
        if (isset($warning)) {
            $attr['style'] .= 'color:#772200;';
        } elseif ($newWindow) {
            $attr['style'] .= 'color:#008800;';
        }

        $attr['class'] ??= '';
        if ($attr['class']) {
            $attr['class'] .= ' button';
        } else {
            $attr['class'] = 'button';
        }
        
        // Добавяме икона на бутона, ако има
        if (!Mode::is('screenMode', 'narrow')) {
            $attr = self::addBackgroundIcon($attr);
        } else {
            unset($attr['ef_icon']);
        }
        
        $btn = self::createElement('input', $attr);
        
        $btn->appendOnce(
            '<input type="hidden" name="Cmd[default]" value=1>',
            'FORM_HIDDEN'
        );
        
        return $btn;
    }
    
    
    /**
     * Създава бутон, който стартира javascript функция
     */
    public static function createFnBtn($title, $function, $warning = null, $attr = array())
    {
        $attr = self::prepareLinkAndBtnAttr($attr, $warning);

        $attr['onclick'] = ($attr['onclick'] ?? '') . $function;
        
        $attr['type'] = 'button';
        
        $attr['value'] = tr($title);
        
        // Оцветяваме бутона в зависимост от особеностите му
        if ($warning) {
            $attr['style'] = ($attr['style'] ?? '') . 'color:#772200;';
        }
        
        $class = $attr['class'] ?? '';
        $attr['class'] = $class . ($class ? ' ' : '') . 'button';
        
        // Добавяме икона на бутона, ако има
        if (!Mode::is('screenMode', 'narrow')) {
            $attr = self::addBackgroundIcon($attr);
        } else {
            unset($attr['ef_icon']);
        }
        
        $btn = self::createElement('input', $attr);
        
        return $btn;
    }
    
    
    /**
     * Създава хипервръзка
     *
     * @param string             $title
     * @param false|array|string $url
     * @param false|string       $warning
     * @param array|string       $attr
     *
     * @return core_ET
     */
    public static function createLink($title, $url = false, $warning = false, $attr = array())
    {
        $attr = self::prepareLinkAndBtnAttr($attr, $warning);
        
        // URL с потвърждение
        if (is_array($url) && $warning) {
            $content = ($url[1] ?? '') . (($url['id'] ?? null) ? $url['id'] : ($url[2] ?? ''));
            if ($content) {
                $url['Cf'] = core_Request::getSessHash($content);
            }
        }
        
        if (is_array($url)) {
            if (countR($url)) {
                try {
                    $url = toUrl($url);
                } catch (core_exception_Expect $e) {
                    $url = null;
                    $attr['style'] = ($attr['style'] ?? '') . ' border:dotted 1px red;';
                }
            } else {
                $url = '';
            }
        }
        
        if ($url) {
            if (($warning || ($attr['rel'] ?? null) == 'nofollow') && ((!Mode::is('text', 'xhtml') && !Mode::is('printing') && !Mode::is('pdf') && !Mode::is('text', 'plain')))) {
                $attr['onclick'] = ($attr['onclick'] ?? '') . " document.location='{$url}'";
                $attr['href'] = 'javascript:void(0)';
            } else {
                $attr['href'] = $url;
            }
        }
        
        if ($icon = ($attr['ef_icon'] ?? null)) {
            if ((Mode::is('text', 'xhtml') || Mode::is('printing'))) {
                $iconSrc = sbf($icon, '', Mode::is('text', 'xhtml'));
                $srcset = '';
                
                if (log_Browsers::isRetina()) {
                    $icon2 = str_replace('/16/', '/32/', $icon);
                    
                    if (getFullPath($icon2)) {
                        $srcset = sbf($icon2, '', Mode::is('text', 'xhtml')) . ' 2x';
                    }
                }
                $icon = "<img src='{$iconSrc}' {$srcset} width='16' height='16' style='float:left;margin:1px 5px -3px 6px;' alt=''>";
                $title = "<span class='linkWithIconSpan'>{$icon}{$title}</span>";
            } else {
                
                
                // Добавяме икона на бутона, ако има
                $attr = self::addBackgroundIcon($attr);
            }
            
            unset($attr['ef_icon']);
        }
        $attr['class'] ??= '';
        $attr['style'] ??= '';
        if ((!Mode::is('text', 'xhtml') && !Mode::is('printing') && !Mode::is('pdf'))) {
            // Оцветяваме линка в зависимост от особеностите му
            if (!isset($attr['disabled'])) {
                if ($warning) {
                    $attr['style'] .= ' color:#772200';
                } elseif (strpos($url ?? '', '://')) {
                    if (!strpos($attr['class'] ?? '', 'out')) {
                        $attr['class'] .= ' out';
                    }
                } elseif (($attr['target'] ?? 'null') == '_blank') {
                    $attr['style'] .= ' color:#008800';
                }
            } else {
                $attr['style'] .= ' color:#999 !important;';
            }
        }
        
        $tpl = self::createElement('a', $attr, $title, true);
        
        return $tpl;
    }
    
    
    /**
     * Създава хипервръзка със стрелка в скоби след подаден $title.
     * С атрибут 'arrowFront' стрелката е пред заглавието и огледално обърната
     */
    public static function createLinkRef($title, $url = false, $warning = false, $attr = array())
    {
        // Нормализация, за да работят флаговете и при подадени атрибути като стринг
        $attr = arr::make($attr, true);

        // Флагът не е html атрибут - маха се, за да не влезе в тага на линка
        $arrowFront = false;
        if (!empty($attr['arrowFront'])) {
            $arrowFront = true;
            unset($attr['arrowFront']);
        }

        // Ако има зададена иконка в линка, слагаме я преди заглавието
        $link = null;
        if (isset($attr['ef_icon'])) {
            $icon = ht::createElement('img', array('src' => sbf($attr['ef_icon'], ''), 'class' => 'linkRefIcon'));
            $title = "{$icon} <span class = 'linkRefText'>{$title}</span>";
            unset($attr['ef_icon']);
        }

        if ($url !== false && (is_string($url) || (is_array($url) && countR($url)))) {

            // Отпред стрелката е без рамка и се мащабира с шрифта, за да е на реда на текста
            $default = ($arrowFront === true) ? 'img/16/anchor-arrow.svg' : 'img/16/anchor-image.png';
            $imgSrc = $attr['ef_icon'] ?? $default;
            $arrowImg = ht::createElement('img', array('src' => sbf($imgSrc, '')));
            $arrowClass = ($arrowFront === true) ? 'anchor-arrow front' : 'anchor-arrow';
            $arrow = "<span class='{$arrowClass}'>{$arrowImg}</span>";

            $link = self::createLink($arrow, $url, $warning, $attr);
        }

        if(!empty($link)){
            if($title instanceof core_ET){
                $layout = ($arrowFront === true) ? "[#link#]&nbsp;[#title#]" : "[#title#]&nbsp;[#link#]";
                $res = new core_ET($layout);
                $res->append($title, 'title');
                $res->append($link, 'link');

                return $res;
            }

            if ($arrowFront === true) {

                return "{$link}&nbsp;{$title}";
            }

            return "{$title}&nbsp;{$link}";
        }

        return $title;
    }
    
    
    /**
     * Създава меню, чрез SELECT елемент
     */
    public static function createSelectMenu($options, $selected, $maxRadio = 0, $attr = array())
    {
        $button = $i = null;
        if (countR($options) < $maxRadio) {
            self::setUniqId($attr);
            $i = 0;
            $selectMenu = new ET('<div class="selectMenu">[#selectMenu#]</div>');
            foreach ($options as $url => $title) {
                $checked = $url == $selected ? ' checked="checked"' : '';
                $style = $url == $selected ? ' style="color:black;"' : '';
                $i++;
                $id = $attr['id'] . $i;
                $selectMenu->append("\n<div class=\"selectMenuItem\">" .
                    "<input type=\"radio\" onclick=\"this.checked=true; setTimeout(function(){openUrl('{$url}', event);}, 10); \" name=\"SM{$attr['id']}\"  id=\"{$id}\"{$checked}>" .
                    "<label for=\"{$id}\"{$style}>{$title}</label></div>", 'selectMenu');
            }
        } else {
            $name = 'sm';
            $attr['onChange'] = 'openUrl(this.options[this.selectedIndex].value, event)';
            $attr['onfocus'] = 'this.selectedIndex = -1;';
            $attr['class'] = (!empty($attr['class']) ? $attr['class'] . ' ' : '') . 'button';
            $attr['id'] = $name;
            $selectMenu = self::createSelect($name, $options, $selected, $attr);
            
            if ($button) {
                $selectMenu->append('<input type="button" ' .
                    "onclick=\"sm = document.getElementById('{$name}');" .
                    'document.location = sm.value;" value="»" ' .
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
        if ($path = $attr['path'] ?? null) {
            $src = sbf($path, '');
            unset($attr['path']);
            if ((log_Browsers::isRetina())) {
                if ($dotPos = mb_strrpos($path, '.')) {
                    $path2x = mb_substr($path, 0, $dotPos) . '2x' . mb_substr($path, $dotPos);
                    if (getFullPath($path2x)) {
                        $url2x = sbf($path2x, '');
                        $attr['srcset'] = "{$url2x} 2x";
                    }
                }
            }
            $attr['src'] = $src;
        }
        
        if (!isset($attr['alt'])) {
            $attr['alt'] = ' ';
        }
        
        $res = self::createElement('img', $attr);
        
        return $res;
    }
    
    
    /**
     * Създава лейаут, по зададени блокове, като плейсхолдери
     */
    public static function createLayout($blocks)
    {
        preg_match_all('/\[#([a-zA-Z0-9_]{1,})#\]/', $blocks, $matches);
        
        $blocksArr = $matches[1];
        
        foreach ($blocksArr as $b) {
            $from = "[#{$b}#]";
            $to = "<!--ET_BEGIN {$b}--><div class='{$b}'>[#{$b}#]</div><!--ET_END {$b}-->";
            $blocks = str_replace($from, $to, $blocks);
        }
        
        $layout = new ET($blocks);
        
        return $layout;
    }
    
    
    /**
     * Създава хинт с иконка към елемент
     *
     * @param mixed  $body        - тяло
     * @param string $hint        - текст на хинта
     * @param string $type        - тип на хинта
     * @param bool   $appendToEnd - дали хинта да се добави в края на стринга
     * @param array  $hintAttr    - атрибути на балончето, плюс настройките на хинта:
     *                             o bool         isHtml      - хинтът е HTML и се показва в балонче, вместо в 'title'
     *                             o array|string iconAttr    - атрибути на иконката
     *                             o array|string url         - адрес, от който съдържанието на балончето се дърпа по ajax
     *                             o string       urlIdParam  - параметър в урл-то с ид-то на балончето, по подразбиране 'replaceField'; null - да не се добавя
     *                             o bool         useHover    - ajax балончето да се показва и при посочване, не само при клик
     *                             o bool         useCache    - ajax заявката да се прави само първия път
     *                             o string       holderClass - допълнителен клас на обвивката
     *                             o string       arrowClass  - клас на стрелката, по подразбиране 'anchor-arrow'
     *                             o string       aiHint      - кратък текст на английски, който в LLM експорта замества хинта с [!bgERP: текст]
     * @param array  $elementArr  - атрибути на елемента
     *
     * @return core_ET $elementTpl  - шаблон с хинта
     */
    public static function createHint($body, $hint, $type = 'notice', $appendToEnd = true, $hintAttr = array(), $elementArr = array())
    {
        // Настройките се изваждат - остатъкът са атрибутите на балончето
        $hintAttr = arr::make($hintAttr, true);
        $isHtml = !empty($hintAttr['isHtml']);
        $iconAttr = $hintAttr['iconAttr'] ?? array();
        $url = $hintAttr['url'] ?? null;
        $urlIdParam = array_key_exists('urlIdParam', $hintAttr) ? $hintAttr['urlIdParam'] : 'replaceField';
        $useHover = !empty($hintAttr['useHover']);
        $useCache = !empty($hintAttr['useCache']);
        $holderClass = $hintAttr['holderClass'] ?? null;
        $arrowClass = array_key_exists('arrowClass', $hintAttr) ? $hintAttr['arrowClass'] : 'anchor-arrow';
        $aiHint = $hintAttr['aiHint'] ?? null;
        unset($hintAttr['isHtml'], $hintAttr['iconAttr'], $hintAttr['url'], $hintAttr['urlIdParam'], $hintAttr['useHover'], $hintAttr['useCache'], $hintAttr['holderClass'], $hintAttr['arrowClass'], $hintAttr['aiHint']);

        // В LLM експорта хинтът се губи при strip_tags, затова се изписва като системна бележка
        if (Mode::is('renderForLlm') && !empty($aiHint)) {
            $res = new core_ET($body);
            $res->append(' ' . doc_plg_LlmExportable::systemNote($aiHint));

            return $res;
        }

        // При ajax хинт балончето е празно, затова се допуска и без текст
        if ((empty($hint) && empty($url)) || Mode::is('printing') || Mode::is('text', 'xhtml') || Mode::is('pdf')) {
            
            return new core_ET($body);
        }

        if (!empty($url)) {
            
            return static::createAjaxHint($body, $hint, $type, $appendToEnd, $hintAttr, $elementArr, $iconAttr, $url, $urlIdParam, $useHover, $useCache, $holderClass, $arrowClass);
        }

        // При инсталиран пакет `floatingui` хинтът се показва в общия слой на страницата
        $useFloatingUi = core_Packs::isInstalled('floatingui');

        if ($isHtml) {
            // При HTML хинт съдържанието се запазва и отива в балончето
            $hint = ($hint instanceof core_ET) ? $hint->getContent() : tr($hint);
        } else {
            $hint = strip_tags(tr($hint));

            // Текстът се вгражда като HTML в слоя, затова ентитетата от вербалното
            // представяне (`5&nbsp;424`) се декодират и се ескейпва наново
            if ($useFloatingUi) {
                $hint = core_Type::escape(html_entity_decode($hint, ENT_QUOTES, 'UTF-8'));
            }
        }

        if ($useFloatingUi) {
            // Съдържанието пътува в `data-hint` - виж floatingui/js/Hints.js
            $hint = ht::escapeAttr($hint);

            if ($type == 'noicon') {
                $element = "<span class='textHint fuiHint' data-hint=\"[#hint#]\">[#body#]</span>";
            } else {
                $iconHtml = static::getHintIcon($type, $iconAttr);
                $iconClass = ($appendToEnd === true) ? 'endTooltip' : 'frontTooltip';
                $trigger = "<span class='{$iconClass} fuiHint' style='position: relative; top: 2px;' data-hint=\"[#hint#]\">[#icon#]</span>";
                $element = ($appendToEnd === true) ? "[#body#] {$trigger}" : "{$trigger} [#body#]";
            }
        } else {
            if ($isHtml) {
                $hintClass = !empty($hintAttr['class']) ? " {$hintAttr['class']}" : '';
                $hintAttr['class'] = "additionalInfo{$hintClass}";
                $hintSpan = ht::createElement('span', $hintAttr);
            }

            if ($type == 'noicon') {
                if ($isHtml) {
                    $element = "<span class='additionalInfo-holder hoverHint'>{$hintSpan}[#hint#]</span><span class='textHint'>[#body#]</span></span>";
                } else {
                    $element = "<span class='textHint' title='[#hint#]' rel='tooltip'>[#body#]</span>";
                }
            } else {
                $iconHtml = static::getHintIcon($type, $iconAttr);
                
                if ($isHtml) {
                    $iconClass = ($appendToEnd === true) ? 'endTooltip' : 'frontTooltip';
                    $holder = "<span class='additionalInfo-holder hoverHint'>{$hintSpan}[#hint#]</span><span class='{$iconClass}' style='position: relative; top: 2px;'>[#icon#]</span></span>";
                    $element = ($appendToEnd === true) ? "[#body#] {$holder}" : "{$holder} [#body#]";
                } elseif ($appendToEnd === true) {
                    $element = "[#body#] <span class='endTooltip' style='position: relative; top: 2px;' title='[#hint#]' rel='tooltip'>[#icon#]</span>";
                } else {
                    $element = "<span class='frontTooltip' style='position: relative; top: 2px;' title='[#hint#]' rel='tooltip'>[#icon#]</span> [#body#]";
                }
            }
        }
        
        $elementTpl = new core_ET($element);
        
        // Ако има атрибути за целия елемент, задават се в span
        $elementArr = arr::make($elementArr, true);
        if (countR($elementArr)) {
            $span = ht::createElement('span', $elementArr);
            $elementTpl->prepend($span);
            $elementTpl->append('</span>');
        }
        
        if (!$isHtml && !$useFloatingUi) {
            $hint = str_replace("'", '"', $hint);
        }
        
        $elementTpl->append($body, 'body');
        $elementTpl->append($hint, 'hint');
        if(!empty($iconHtml)){
            $elementTpl->append($iconHtml, 'icon');
        }

        return $elementTpl;
    }
    
    
    /**
     * Подготвя иконката на хинта според типа му
     *
     * @param string       $type     - тип на хинта или път до иконка
     * @param array|string $iconAttr - атрибути на иконката
     *
     * @return string - html на иконката
     */
    private static function getHintIcon($type, $iconAttr)
    {
        $iconAttr = arr::make($iconAttr, true);
        if (!array_key_exists('src', $iconAttr)) {
            $iconPath = ($type == 'notice') ? 'img/32/info-gray.png' : (($type == 'warning') ? 'img/32/dialog_warning.png' : (($type == 'error') ? 'img/32/dialog_error.png' : $type));
            $iconAttr['src'] = $iconPath;
        }
        $iconAttr['src'] = sbf($iconAttr['src'], '');
        
        return ht::createElement('img', $iconAttr);
    }
    
    
    /**
     * Създава хинт, чието балонче се попълва по ajax от подадения адрес
     *
     * @see ht::createHint()
     *
     * @param mixed        $body        - тяло
     * @param string       $hint        - подсказка на иконката
     * @param string       $type        - тип на хинта
     * @param bool         $appendToEnd - дали иконката да е в края на стринга
     * @param array        $hintAttr    - атрибути на балончето
     * @param array        $elementArr  - атрибути на елемента
     * @param array|string $iconAttr    - атрибути на иконката
     * @param array|string $url         - адрес, от който се дърпа съдържанието на балончето
     * @param string       $urlIdParam  - параметър в урл-то с ид-то на балончето
     * @param bool         $useHover    - показване и при посочване, не само при клик
     * @param bool         $useCache    - заявката да се прави само първия път
     * @param string|null  $holderClass - допълнителен клас на обвивката
     * @param string|null  $arrowClass  - клас на стрелката
     *
     * @return core_ET - шаблон с хинта
     */
    private static function createAjaxHint($body, $hint, $type, $appendToEnd, $hintAttr, $elementArr, $iconAttr, $url, $urlIdParam, $useHover, $useCache, $holderClass, $arrowClass)
    {
        // Ид-то на балончето отива и в урл-то, за да знае екшъна какво да замести
        static $count = 0;
        $count++;
        $id = !empty($hintAttr['id']) ? $hintAttr['id'] : "hint{$count}" . rand(1, 10000);
        $hintAttr['id'] = $id;
        $hintClass = !empty($hintAttr['class']) ? " {$hintAttr['class']}" : '';
        $hintAttr['class'] = "additionalInfo{$hintClass}";
        
        if (is_array($url)) {
            if (!empty($urlIdParam)) {
                $url[$urlIdParam] = $id;
            }
            $url = toUrl($url, 'local');
        }
        
        $arrowClass = !empty($arrowClass) ? "{$arrowClass} " : '';
        $linkAttr = array('class' => "{$arrowClass}tooltip-arrow-link", 'data-url' => $url);
        if (!empty($hint)) {
            $linkAttr['title'] = $hint;
        }
        
        if ($useHover) {
            $linkAttr['data-useHover'] = '1';
        }
        
        if ($useCache) {
            $linkAttr['data-useCache'] = '1';
        }
        
        $iconHtml = static::getHintIcon($type, $iconAttr);
        $linkHtml = ht::createElement('span', $linkAttr, $iconHtml, true);
        
        $holderClass = !empty($holderClass) ? " {$holderClass}" : '';
        $hintSpan = ht::createElement('span', $hintAttr);
        $holder = "<span class='additionalInfo-holder{$holderClass}'>{$hintSpan}</span>[#icon#]</span>";
        
        // Без разделител, ако няма тяло
        $space = ($body === '' || $body === null) ? '' : ' ';
        $element = ($appendToEnd === true) ? "[#body#]{$space}{$holder}" : "{$holder}{$space}[#body#]";
        
        $elementTpl = new core_ET($element);
        
        // Ако има атрибути за целия елемент, задават се в span
        $elementArr = arr::make($elementArr, true);
        if (countR($elementArr)) {
            $span = ht::createElement('span', $elementArr);
            $elementTpl->prepend($span);
            $elementTpl->append('</span>');
        }
        
        $elementTpl->append($body, 'body');
        $elementTpl->append($linkHtml, 'icon');
        
        return $elementTpl;
    }
    
    
    /**
     * Прави html представяне на структурата на обекта, масива или променливата
     */
    public static function wrapMixedToHtml($html, $wholeDocument = false)
    {
        $styles = "    .dump {font-family: Consolas,Courier New,monospace; font-size:13px; padding-bottom:5px;}\n" .
                    "    .dump ul {list-style-type: none; margin:0;margin-left:10px; border-left:solid 1px #bbb; padding:0; padding-left:3px;}\n" .
                    "    .dump li {margin-top:3px;display:table;}\n" .
                    "    .dump .trigger {cursor:pointer}\n" .
                    "    .dump {max-width:100%; white-space:nowrap;overflow-x:auto;overflow-y:hidden;}\n" .
                    '    .dump .undefined {color:red}' .
                    '    .dump .static {color:#009900}' .
                    '    .dump .protected {color:#003366}' .
                    '    .dump .private {color:#330066}' .
                    '    .dump .undefined {color:#cc0000}' .
                    '    .dump .unknown {color:#c96}' ;
        
        $scripts = "$('.trigger').click(function(event){\n" .
                    "var obj = \$(this).parent().children('ul')[0];\n" .
                    "var sp  = \$(this);\n" .
                    "if(\$(obj).hasClass('hidden')){\n" .
                    "    \$(sp).css('border-bottom', 'none');\n" .
                    "    \$(obj).removeClass('hidden').slideDown();\n" .
                    "} else {\n" .
                    "    \$(sp).css('border-bottom', 'dotted 1px #bbb');\n" .
                    "    \$(obj).addClass('hidden').slideUp();\n" .
                    "}\n" .
                    "event.stopPropagation(); });\n";
        
        if (!$wholeDocument) {
            $tpl = new ET($html);
            $tpl->appendOnce($styles, 'STYLES');
            $tpl->appendOnce($scripts, 'JQRUN');
        } else {
            $tpl = "<!DOCTYPE html>\n" .
                    "<html><head>\n" .
                    "<meta http-equiv=\"Content-Type\" content=\"text/html;charset=UTF-8\">\n" .
                    "<meta name=\"robots\" content=\"noindex,nofollow\">\n" .
                    '<title>BP on ' . date('Y-m-d H:i:s') . "</title>\n" .
                    "<script src=\"https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js\"></script>\n" .
                    "<style>\n" .
                    $styles .
                    "</style>\n" .
                    "</head>\n" .
                    "<body>\n" .
                    $html . "\n" .
                    '<script> window.onload = function() {if (window.jQuery) {'. $scripts ." }\n</script>" .
                    "</body>\n" .
                    "</html>\n";
        }
        
        return $tpl;
    }
    
    
    /**
     * Прави html представяне на структурата на обекта, масива или променливата
     */
    public static function mixedToHtml($o, $hideLevel = 3, $maxLevel = 5, $prefix = '')
    {
        ht::fixObject($o);
        static $i = 0;
        
        $i++;
        
        $r = gettype($o);
        
        if ($i > $maxLevel) {
            $i--;
            
            if (is_array($o)) {
                $res = '(array)...';
            } elseif (is_object($o)) {
                $res = '(object)...';
            } elseif (is_scalar($o)) {
                $res = htmlentities($o, ENT_COMPAT | ENT_IGNORE, 'UTF-8');
            } else {
                $res = '...';
            }
            
            return $res;
        }
        
        $scopeArr = array();
        
        if (is_object($o)) {
            $class = $r = get_class($o);
        }
        
        if ($i == 1) {
            $r = "<span class='trigger' style='border-bottom:dotted 1px #bbb;'>" . $r . '</span>';
        }
        
        if (is_object($o)) {
            $res = array();
            
            // По-подразбиране променливите имат публична видимост
            $scope = '';
            
            $res = get_object_vars($o);
            
            if (strtolower($class) != 'stdclass') {
                $scope = 'undefined';
                do {
                    $reflection = new ReflectionClass($class);
                    foreach ($reflection->getProperties(
                                            ReflectionProperty::IS_PUBLIC |
                                            ReflectionProperty::IS_STATIC |
                                            ReflectionProperty::IS_PROTECTED |
                                            ReflectionProperty::IS_PRIVATE
                    ) as $prop) {
                        $prop->setAccessible(true);
                        $name = $prop->getName();

                        if (!isset($scopeArr[$name])) {
                            try {
                                if (method_exists($prop, 'isInitialized') && !$prop->isInitialized($prop->isStatic() ? null : $o)) {
                                    $res[$name] = '(uninitialized)';
                                } else {
                                    $res[$name] = $prop->getValue($prop->isStatic() ? null : $o);
                                }
                            } catch (core_exception_Redirect $e) {
                                throw $e;
                            } catch (Throwable $e) {
                                // Някои вътрешни PHP обекти не разрешават четене на свойствата си
                                $res[$name] = '(unavailable)';
                            }
                            if ($prop->isStatic()) {
                                $scopeArr[$name] = 'static';
                            } elseif ($prop->isPublic()) {
                                $scopeArr[$name] = '';
                            } elseif ($prop->isPrivate()) {
                                $scopeArr[$name] = 'private';
                            } elseif ($prop->isProtected()) {
                                $scopeArr[$name] = 'protected';
                            } else {
                                $scopeArr[$name] = 'unknown';
                            }
                        }
                    }
                } while ($class = get_parent_class($class));
            }
            
            if (countR($res)) {
                foreach ($res as $name => $vR) {
                    if (!isset($scopeArr[$name])) {
                        $scopeArr[$name] = $scope;
                    }
                }
            }
            
            $o = $res;
        }
        
        if (is_array($o)) {
            if ($i >= $hideLevel + 1) {
                $html = "\n({$r})\n<ul class='hidden' style='display:none;'>";
            } else {
                $html = "\n({$r})\n<ul>";
            }
            
            if ($i >= $hideLevel && $i < $maxLevel) {
                $style = 'border-bottom:dotted 1px #bbb;';
            } else {
                $style = '';
            }
            
            if (countR($o)) {
                foreach ($o as $name => $value) {
                    $attr = array('class' => '', 'title' => '', 'style' => '');
                    
                    if (isset($scopeArr[$name])) {
                        $attr['class'] = $scopeArr[$name];
                        $attr['title'] = $scopeArr[$name];
                    }
                    
                    if ($name === 'dbPass') {
                        $html .= "\n    <li>" . self::createElement('span', $attr, "{$name} : ******")  . '</li>';
                    } else {
                        if (is_scalar($value) || $value === null || (is_array($value) && countR($value) == 0)) {
                            $html .= "\n    <li>" . self::createElement('span', $attr, htmlentities($name, ENT_COMPAT | ENT_IGNORE, 'UTF-8')) . ' : ' .
                                self::mixedToHtml($value, $hideLevel, $maxLevel) . '</li>';
                        } else {
                            $attr['style'] = $style;
                            if ($i < $maxLevel) {
                                $attr['class'] .= ' trigger';
                            }
                            
                            $html .= "\n    <li>" . self::createElement('span', $attr, htmlentities($name, ENT_COMPAT | ENT_IGNORE, 'UTF-8')) . ' : ' .
                                self::mixedToHtml($value, $hideLevel, $maxLevel) . '</li>';
                        }
                    }
                }
            }
            $html .= "\n</ul>";
        } elseif (is_string($o)) {
            $html = "({$r}) " . htmlentities($o, ENT_COMPAT | ENT_IGNORE, 'UTF-8');
        } elseif (is_bool($o)) {
            $html = "({$r}) " . ($o ? 'TRUE' : 'FALSE');
        } else {
            $html = "({$r}) " . $o;
        }
        $i--;
        
        
        if ($i == 0) {
            $html = "<div class='dump'>{$prefix}{$html}</div>";
        }
        
        return $html;
    }
    
    
    /**
     * Фиксира  PHPIncompleteClass
     */
    public static function fixObject(&$object)
    {
        if ($object instanceof __PHP_Incomplete_Class) {

            // Името на оригиналния клас се пази в служебно поле на непълния обект
            $vars = (array) $object;
            $class = $vars['__PHP_Incomplete_Class_Name'] ?? null;

            // Възстановяваме обекта, само ако класът вече е зареден
            if (!empty($class) && class_exists($class)) {

                return ($object = unserialize(preg_replace('/^O:\d+:"[^"]++"/', 'O:' . strlen($class) . ':"' . $class . '"', serialize($object))));
            }
        }
        
        return $object;
    }
    
    
    /**
     * Прави dump на масив в html представяне
     */
    public static function arrayToHtml($arr, $openLevels = 3, $viewLevels = 5)
    {
        $result = '';

        // Дъмпът на изключение може да е скалар (напр. стринг)
        if (!is_array($arr) && !is_object($arr)) {
            $arr = array($arr);
        }

        foreach ($arr as $id => $item) {
            if ($result) {
                $result .= "<div style='margin-top:5px;padding-top:5px;border-top:dotted 1px #999;'>";
            } else {
                $result .= "<div style='margin-top:5px;'>";
            }
            
            if ($id && !is_int($id)) {
                $prefix = "{$id}: ";
            } else {
                $prefix = '';
            }
            $result .= self::mixedToHtml($item, $openLevels, $viewLevels, $prefix);
            $result .= '</div>';
        }
        
        return $result;
    }

    /**
     * Връща уникално id за хита започващо с подадения префикс
     */
    public static function getUniqId($prefix = '')
    {
        static $id;
        $id++;

        return $prefix . base_convert($id . rand(1000, 9999), 10, 36);
    }
    
    
    /**
     * Задава уникално значение на атрибута $attr['id'] (в текущия хит)
     */
    public static function setUniqId(&$attr)
    {
        if (empty($attr['id'])) {
            $name = !empty($attr['name']) ? $attr['name'] : 'autoElement';
            $name = str_replace(array('[', ']'), array('_', '_'), $name);
            $attr['id'] = self::getUniqId($name);
        }
    }
    
    
    /**
     * Извлича текста от посочения HTML
     */
    public static function extractText($html)
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
     * Балансира таговете на HTML фрагмент, за да не засяга HTML-а около него
     *
     * Затварящ таг без отворен елемент в обхвата си се премахва, а незатворените
     * елементи се затварят в края. При грешна подредба вътрешните елементи се
     * затварят изрично, а прекъснатото форматиране (b, i, u...) се отваря наново,
     * както го показва браузърът. Коректният HTML остава непроменен.
     *
     * @param string $html
     *
     * @return string
     */
    public static function balanceTags($html)
    {
        $html = (string) $html;
        if (strpos($html, '<') === false) {
            
            return $html;
        }
        
        static $set = null;
        if (!isset($set)) {
            foreach (array(
                // Елементи без затварящ таг
                'void' => 'area,base,basefont,bgsound,br,col,embed,frame,hr,image,img,input,keygen,link,meta,param,source,track,wbr',
                // Съдържанието им е текст до затварящия таг
                'raw' => 'iframe,noembed,noframes,noscript,script,style,textarea,title,xmp',
                // Форматиране, което браузърът продължава след прекъсване
                'format' => 'a,b,big,code,em,font,i,nobr,s,small,strike,strong,tt,u',
                // Блокови елементи ("special" в HTML5) - спират затварящите тагове на останалите
                'special' => 'address,applet,area,article,aside,base,basefont,bgsound,blockquote,body,br,button,caption,center,col,colgroup,dd,details,dir,div,dl,dt,embed,fieldset,figcaption,figure,footer,form,frame,frameset,h1,h2,h3,h4,h5,h6,head,header,hgroup,hr,html,iframe,img,input,keygen,li,link,listing,main,marquee,menu,meta,nav,noembed,noframes,noscript,object,ol,p,param,plaintext,pre,script,search,section,select,source,style,summary,table,tbody,td,template,textarea,tfoot,th,thead,title,tr,track,ul,wbr,xmp',
                // Затварящите им тагове търсят елемента в обхвата
                'block' => 'address,applet,article,aside,blockquote,button,center,dd,details,dialog,dir,div,dl,dt,fieldset,figcaption,figure,footer,form,h1,h2,h3,h4,h5,h6,header,hgroup,li,listing,main,marquee,menu,nav,object,ol,pre,search,section,select,summary,template,ul',
                // Граници на обхвата
                'scope' => 'applet,caption,html,marquee,object,table,td,template,th',
                // Форматирането отвътре не продължава след тях
                'marker' => 'applet,caption,marquee,object,td,template,th',
                // Браузърът ги затваря и без затварящ таг
                'optional' => 'caption,colgroup,dd,dt,li,optgroup,option,rb,rp,rt,rtc,tbody,td,tfoot,th,thead,tr',
                // Части на таблица - извън таблица браузърът ги пропуска
                'table' => 'caption,col,colgroup,tbody,td,tfoot,th,thead,tr',
                // Таблица, в която текстът не възстановява форматирането
                'inTable' => 'table,tbody,tfoot,thead,tr',
                // Не се следят - браузърът ги затваря или пропуска сам
                'skip' => 'body,head,html,p',
                // Блокови, пред които браузърът отваря наново форматирането
                'reopen' => 'applet,area,br,button,embed,img,input,keygen,marquee,object,select,wbr,xmp',
                'heading' => 'h1,h2,h3,h4,h5,h6',
                // Елементи, по които браузърът разбира къде в таблицата е
                'tablePart' => 'caption,colgroup,table,tbody,td,tfoot,th,thead,tr',
                // В тях таблицата се влага, а не затваря отворената
                'cell' => 'caption,td,th',
                // Тагове, които браузърът чете в <select>, и такива, които го затварят
                'inSelect' => 'hr,optgroup,option,template',
                'endSelect' => 'input,keygen,textarea',
            ) as $key => $list) {
                $set[$key] = array_flip(explode(',', $list));
            }
        }
        
        $stack = $pending = array();
        $out = '';
        $markers = $seq = 0;
        
        // Намира отворен елемент с някое от имената, без да излиза от обхвата
        $find = function ($names, $stop, $stopAtSpecial = false) use (&$stack, $set) {
            for ($j = countR($stack) - 1; $j >= 0; $j--) {
                $name = $stack[$j]['name'];
                if (isset($names[$name])) {
                    
                    return $j;
                }
                if (isset($stop[$name]) || ($stopAtSpecial && isset($set['special'][$name]))) {
                    break;
                }
            }
        };
        
        // Затваря елементите над $k, а при $self и самия $k (със затварящ таг при $emit).
        // Прекъснатото форматиране се отваря наново по-късно
        $popTo = function ($k, $self = false, $emit = false) use (&$stack, &$pending, &$out, &$markers, $set) {
            // Незадължителен затварящ таг се пропуска, само ако под елемента няма изрично затварящи се
            $silent = array();
            $explicit = false;
            for ($j = $k + 1; $j < countR($stack); $j++) {
                $silent[$j] = !$explicit && isset($set['optional'][$stack[$j]['name']]);
                $explicit = !$silent[$j];
            }
            $reopen = array();
            while (countR($stack) > ($self ? $k : $k + 1)) {
                $j = countR($stack) - 1;
                $e = array_pop($stack);
                $isTarget = ($j == $k);
                if (isset($set['marker'][$e['name']])) {
                    $markers--;
                    $reopen = array();
                    foreach ($pending as $i => $p) {
                        if ($p['level'] > $markers) {
                            unset($pending[$i]);
                        }
                    }
                }
                if ($isTarget ? $emit : !$silent[$j]) {
                    $out .= '</' . $e['name'] . '>';
                }
                if (!$isTarget && isset($set['format'][$e['name']])) {
                    $reopen[] = $e;
                }
            }
            foreach ($reopen as $e) {
                $e['level'] = $markers;
                $pending[] = $e;
            }
        };
        
        // Затваря форматиране. Блоковете над него браузърът оставя отворени и затваря
        // само форматирането (adoption agency), затова тагът остава на мястото си
        $adopt = function ($k, $tok) use (&$stack, &$out, $set, $popTo) {
            for ($s = countR($stack) - 1; $s > $k && !isset($set['special'][$stack[$s]['name']]); $s--) {
            }
            $popTo($s, $s == $k);
            $out .= $tok;
            for ($j = $s - 1; $j >= $k; $j--) {
                $e = $stack[$j]['name'];
                if ($j == $k || !(isset($set['format'][$e]) || isset($set['special'][$e]))) {
                    array_splice($stack, $j, 1);
                }
            }
        };
        
        // Отваря наново прекъснатото форматиране преди новото съдържание
        $flush = function () use (&$stack, &$pending, &$out, &$markers) {
            usort($pending, function ($a, $b) {
                
                return $a['seq'] - $b['seq'];
            });
            foreach ($pending as $i => $e) {
                if ($e['level'] == $markers) {
                    $out .= $e['tag'];
                    $stack[] = $e;
                    unset($pending[$i]);
                }
            }
        };
        
        // Текст преди следващия таг
        $text = function ($str) use (&$stack, &$pending, &$out, $set, $flush) {
            if ($str === '') {
                
                return;
            }
            if ($pending && !isset($set['inTable'][$stack ? $stack[countR($stack) - 1]['name'] : ''])) {
                $flush();
            }
            $out .= $str;
        };
        
        // Атрибутите се разпознават като в браузъра, включително незатворените кавички
        $attrs = '(?:[\s/]++|[^\s/>][^\s/>=]*+(?(?=\s*+=)\s*+=\s*+(?:"[^"]*+"|\'[^\']*+\'|(?![\'"])[^\s>]*+)))*+';
        $pattern = '~<!--(?:-?>|.*?(?:--!?>|\z))|<[!?][^>]*+>?|</(?![a-zA-Z])[^>]*+>?|<(/?)([a-zA-Z][^\s/>]*+)(' . $attrs . ')>|<(?=/?[a-zA-Z])~s';
        
        $pos = 0;
        $len = strlen($html);
        while ($pos < $len && preg_match($pattern, $html, $m, PREG_OFFSET_CAPTURE, $pos)) {
            $tok = $m[0][0];
            $text(substr($html, $pos, $m[0][1] - $pos));
            $pos = $m[0][1] + strlen($tok);
            
            if (!isset($m[2]) || $m[2][1] < 0) {
                if ($tok == '<') {
                    // Незавършен таг би погълнал HTML-а след фрагмента
                    $out .= '&lt;';
                } elseif (strpos($tok, '<!--') === 0) {
                    $out .= $tok . (preg_match('/^<!---?>$|--!?>$/', $tok) ? '' : '-->');
                } else {
                    $out .= $tok . (substr($tok, -1) == '>' ? '' : '>');
                }
                continue;
            }
            
            $name = strtolower($m[2][0]);
            
            // В <select> браузърът пропуска почти всички тагове
            for ($j = countR($stack) - 1; $j >= 0 && ($stack[$j]['name'] == 'option' || $stack[$j]['name'] == 'optgroup'); $j--) {
            }
            if ($j >= 0 && $stack[$j]['name'] == 'select' && !isset($set['inSelect'][$name]) && $name != 'script' && $m[1][0] . $name != '/select') {
                if ($m[1][0] != '' || ($name != 'select' && !isset($set['endSelect'][$name]))) {
                    continue;
                }
                
                // Нов <select> или поле за въвеждане затварят отворения
                $popTo($j, true, true);
                if ($name == 'select') {
                    continue;
                }
            }
            
            if ($m[1][0] == '') {
                // Отварящ таг
                if ($name == 'plaintext') {
                    // Превръща в текст всичко след себе си, затова се показва като текст
                    $out .= '&lt;' . substr($tok, 1);
                    continue;
                }
                if (isset($set['skip'][$name])) {
                    $out .= $tok;
                    continue;
                }
                
                // Елементите, които браузърът затваря сам при новия таг
                $k = null;
                $t = (isset($set['table'][$name]) || $name == 'table') ? $find(array('table' => 1), array('html' => 1, 'template' => 1)) : null;
                if (isset($t)) {
                    // Най-близкият табличен елемент определя как браузърът чете тага
                    $c = $find($set['tablePart'], array());
                    $mode = $stack[$c]['name'];
                    if ($name == 'table') {
                        // Таблица извън клетка затваря отворената
                        $k = isset($set['cell'][$mode]) ? null : $t;
                    } elseif ($name == 'td' || $name == 'th') {
                        $k = (isset($set['cell'][$mode]) || $mode == 'colgroup') ? $c : $c + 1;
                    } elseif ($name == 'tr') {
                        $k = $find(array('table' => 1, 'tbody' => 1, 'tfoot' => 1, 'thead' => 1), array()) + 1;
                    } elseif ($name != 'col' || $mode != 'colgroup') {
                        $k = $t + 1;
                    }
                } elseif (isset($set['table'][$name])) {
                    // Извън таблица браузърът пропуска тага, а в чужда клетка би я затворил
                    continue;
                } elseif ($name == 'li' || $name == 'dd' || $name == 'dt') {
                    for ($j = countR($stack) - 1; $j >= 0; $j--) {
                        $e = $stack[$j]['name'];
                        if ($e == $name || ($name != 'li' && ($e == 'dd' || $e == 'dt'))) {
                            $k = $j;
                            break;
                        }
                        if (isset($set['special'][$e]) && $e != 'address' && $e != 'div' && $e != 'p') {
                            break;
                        }
                    }
                } elseif ($name == 'a' || $name == 'nobr' || $name == 'button') {
                    $k = $find(array($name => 1), $set['scope']);
                    foreach ($pending as $i => $p) {
                        if ($p['name'] == $name) {
                            unset($pending[$i]);
                        }
                    }
                } elseif ($stack && (isset($set['heading'][$name]) || $name == 'option' || $name == 'optgroup')) {
                    $top = $stack[countR($stack) - 1]['name'];
                    if (isset($set['heading'][$name]) ? isset($set['heading'][$top]) : ($top == 'option')) {
                        $k = countR($stack) - 1;
                    }
                }
                if (isset($k) && $k < countR($stack)) {
                    if ($name == 'a' || $name == 'nobr') {
                        $adopt($k, "</{$name}>");
                    } else {
                        $popTo($k, true, !isset($set['optional'][$stack[$k]['name']]));
                    }
                }
                
                if ($pending && (isset($set['format'][$name]) || isset($set['reopen'][$name]) || !(isset($set['special'][$name]) || isset($set['block'][$name])))) {
                    $flush();
                }
                
                $out .= $tok;
                
                // Самозатварящ се таг има само в SVG и MathML
                if (isset($set['void'][$name]) || (substr($m[3][0], -1) == '/' && $find(array('svg' => 1, 'math' => 1), array()) !== null)) {
                    continue;
                }
                
                if (isset($set['raw'][$name])) {
                    if (preg_match('~</' . $name . '(?=[\s/>])' . $attrs . '>~i', $html, $em, PREG_OFFSET_CAPTURE, $pos)) {
                        $end = $em[0][1] + strlen($em[0][0]);
                        $out .= substr($html, $pos, $end - $pos);
                        $pos = $end;
                    } else {
                        $out .= substr($html, $pos) . "</{$name}>";
                        $pos = $len;
                    }
                    continue;
                }
                
                $stack[] = array('name' => $name, 'tag' => $tok, 'seq' => $seq++);
                if (isset($set['marker'][$name])) {
                    $markers++;
                }
                continue;
            }
            
            // Затварящ таг
            if ($name == 'br' || isset($set['void'][$name]) || isset($set['skip'][$name])) {
                if ($name == 'br' && $pending) {
                    $flush();
                }
                $out .= $tok;
                continue;
            }
            
            if (isset($set['table'][$name]) || $name == 'table') {
                $k = $find(array($name => 1), array('html' => 1, 'table' => 1, 'template' => 1));
            } else {
                $stop = ($name == 'li') ? $set['scope'] + array('ol' => 1, 'ul' => 1) : $set['scope'];
                $k = $find(isset($set['heading'][$name]) ? $set['heading'] : array($name => 1), $stop, !isset($set['format'][$name]) && !isset($set['block'][$name]));
            }
            
            // Затварящият таг може да се отнася за прекъснато форматиране, което още не е отворено наново
            $p = null;
            foreach ($pending as $i => $e) {
                if ($e['name'] == $name && $e['level'] == $markers && (!isset($p) || $e['seq'] > $pending[$p]['seq'])) {
                    $p = $i;
                }
            }
            if (isset($p) && (!isset($k) || $pending[$p]['seq'] > $stack[$k]['seq'])) {
                unset($pending[$p]);
                continue;
            }
            
            // Без отворен елемент в обхвата браузърът би затворил външен елемент
            if (!isset($k)) {
                continue;
            }
            
            if (isset($set['format'][$name])) {
                $adopt($k, $tok);
            } else {
                $popTo($k, true);
                $out .= $tok;
            }
        }
        
        $text(substr($html, $pos));
        
        for ($j = countR($stack) - 1; $j >= 0; $j--) {
            $out .= '</' . $stack[$j]['name'] . '>';
        }
        
        return $out;
    }
    
    
    /**
     * Добавя икона като бекграунд в атрибутите
     */
    public static function addBackgroundIcon($attr, $icon = null)
    {
        if (!$icon) {
            $icon = $attr['ef_icon'] ?? null;
            unset($attr['ef_icon']);
        }
        
        if (!empty($icon) && getFullPath($icon)) {
            $attr['class'] = ($attr['class'] ?? '') . ' linkWithIcon';
            
            $attr['style'] = self::getIconStyle($icon, $attr['style'] ?? null);
        }
        
        return $attr;
    }
    
    
    /**
     * Връща стил с включен бекграунд за икона
     */
    public static function getIconStyle($icon, $style = 'background-size:16px 16px;')
    {
        if (!empty($icon)) {
            if (log_Browsers::isRetina()) {
                $icon2 = str_replace('/16/', '/32/', $icon);
                
                if (getFullPath($icon2)) {
                    $icon = $icon2;
                }
            }
            
            $iconSrc = sbf($icon, '', Mode::is('text', 'xhtml'));
            
            $style = rtrim($style ?? '', ' ;');
            
            $style .= ($style ? '; ' : '') . "background-image:url('{$iconSrc}');";
        }
        
        return $style;
    }
    
    
    /**
     * Подготвя атрибутите на бутон или хипервръзка
     */
    private static function prepareLinkAndBtnAttr($attr, $warning = '', $trTitle = false)
    {
        $attr = arr::make($attr);
        
        if (($attr['title'] ?? null) && $trTitle) {
            $attr['title'] = tr($attr['title']);
        }
        
        // Вкарваме предупреждението - стилизиран модал вместо нативен confirm() - виж
        // efConfirmClick()/efConfirm() в js/efCommon.js. efConfirmClick() е асинхронен, затова при
        // потвърждение "преиграва" клика (element.click()) - на втория пасаж флагът
        // element.__efConfirmed вече е сложен, минава директно (виж кода на efConfirmClick()).
        // $attr['warningHigh'] е алтернатива на $warning - носи си самия текст, но със
        // severity=warning (по-тревожен, оранжев вид) вместо стандартния notice. Ако е зададен,
        // той има предимство пред $warning.
        $severity = 'notice';
        if (!empty($attr['warningHigh'])) {
            $warning = $attr['warningHigh'];
            $severity = 'warning';
        }
        unset($attr['warningHigh']);

        if ($warning) {
            if (!isset($attr['onclick'])) {
                $attr['onclick'] = '';
            }
            $escaped = addcslashes(tr($warning), "'\\\n\r");
            $attr['onclick'] .= " if (!efConfirmClick(event, this, '{$escaped}', '{$severity}')) { return false; }";
        }

        return $attr;
    }
    
    
    /**
     * Обграждане на стринга, ако подадения стринг е отрицателно число
     *
     * @param mixed        $verbal
     * @param string|float $notVerbal
     *
     * @return string|core_ET $verbal
     */
    public static function styleIfNegative($verbal, $notVerbal)
    {
        if ($notVerbal < 0) {
            if ($verbal instanceof core_ET) {
                $verbal->prepend("<span class='red'>");
                $verbal->append('</span>');
            } else {
                $verbal = "<span class='red'>{$verbal}</span>";
            }
        }
        
        return $verbal;
    }
    
    
    /**
     * Стилизира числото според стойноста му:
     * 		ако е отрицателно го оцветява в червено
     * 		ако е положително не го променя (освен ако не е зададен конкретен цвят)
     * 		ако е 0, го засивява
     *
     * @param mixed $verbal
     * @param float $notVerbal
     * @param string|null $colorIfPositive - с какъв цвят да е оцветено, ако е положително
     * @return mixed $verbal
     */
    public static function styleNumber($verbal, $notVerbal, $colorIfPositive = null)
    {
        if ($notVerbal == 0) {
            if ($verbal instanceof core_ET) {
                $verbal->prepend("<span class='quiet'>");
                $verbal->append('</span>');
            } else {
                $verbal = "<span class='quiet'>{$verbal}</span>";
            }
            
            return $verbal;
        } elseif($notVerbal > 0 && isset($colorIfPositive)){
            $verbal = "<span style='color:{$colorIfPositive}'>{$verbal}</span>";
        }
        
        return self::styleIfNegative($verbal, $notVerbal);
    }


    /**
     * Рендира видео таг за пускане на виде (Само в новите браузъри)
     *
     * @param string $fileHnd    - файл хендлър
     * @param array $params      - параметри за видеото
     *     bool 'controls' - контрол над видеото
     *     bool 'autoplay' - автоматично стартиране
     *     bool 'loop',    - повтаряне
     *     bool 'muted'    - дали да е без звук
     *     string 'poster' - постер
     *     string 'preload' - auto, metadata, none
     *     int  'width'     - ширина (в пиксели)
     *     int  'height'    - височина (в пиксели)
     *     string 'class'   - CSS класове
     * @param string $sourceType - тип на видеото
     *
     * @return core_ET
     * @throws core_exception_Expect
     */
    public static function createVideo($fileHnd, $params = array(), $sourceType = 'video/mp4')
    {
        // Получаваме URL на видеото
        $src = fileman_Download::getDownloadUrl($fileHnd);
        expect($src);

        // Стартиране на видео тага
        $videoTag = '<video';

        // Разрешени параметри за видео тага
        $allowedParams = array('controls', 'autoplay',  'loop', 'muted', 'poster', 'preload', 'width', 'height', 'class');

        // Обработване на зададените параметри
        foreach ($allowedParams as $key) {
            if (isset($params[$key])) {
                $value = $params[$key];
                if ($key === 'class') {
                    $videoTag .= ' class="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
                } elseif ($value === true) {
                    $videoTag .= ' ' . $key;
                } elseif (!is_bool($value)) {
                    $videoTag .= ' ' . $key . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
                }
            }
        }
        $videoTag .= '>';

        // Добавяне на source таг с подадения тип
        $videoTag .= '<source src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" type="' . htmlspecialchars($sourceType, ENT_QUOTES, 'UTF-8') . '">';

        // Добавяне на fallback текст
        $videoTag .= tr('Браузърът не поддържа видео тага');
        $videoTag .= '</video>';

        return new core_ET($videoTag);
    }
}
