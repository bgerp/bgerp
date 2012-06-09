<?php



/**
 * Клас 'core_Form' - представя една уеб-форма
 *
 * Клас за форми
 * Формите поддържат методи за:
 * - описание на полетата на формата;
 * - вход на на данните от заявката;
 * - валидиране и верифициране на данните
 * - рендиране на формата
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
class core_Form extends core_FieldSet
{
    
    
    /**
     * ET шаблон за формата
     */
    var $tpl;
    
    
    /**
     * Заглавие на формата
     */
    var $title;
    
    
    /**
     * Стойности на полетата на формата
     */
    var $rec;
    
    
    /**
     * Общ лейаут на формата
     */
    var $layout;
    
    
    /**
     * Лейаут на инпут-полетата
     */
    var $fieldsLayout;
    
    
    /**
     * Cmd-то на бутона с който е субмитната формата
     */
    var $cmd;
    
    
    /**
     * Атрибути на елемента <FORM ... >
     */
    var $formAttr = array();
    
    
    /**
     * Редове с дефиниции [Селектор на стила] => [Дефиниция на стила]
     */
    var $styles = array();
    
    
    /**
     * Кои полета от формата да се показват
     */
    var $showFields;
    
    
    /**
     * В каква посока да са разположени полетата?
     */
    var $view = 'vertical';
    
    
    /**
     * Инициализира формата с мениджърския клас и лейаута по подразбиране
     */
    function init($params = array())
    {
        parent::init($params);
        
        if(isset($this->mvc) && count($this->mvc->fields)) {
            foreach($this->mvc->fields as $key => $value) {
                $this->fields[$key] = clone($this->mvc->fields[$key]);
            }
        }
        
        $this->toolbar = cls::get('core_Toolbar');
        
        $this->rec = new stdClass();
    }
    
    
    /**
     * Връща обект, чийто полета са променливите на формата,
     * а стойностите им са от заявката (Request)
     * Същите стойности се записват във вътрешното поле $form->rec
     * Ако формата не е събмитване (изпращане), връща False
     */
    function input_($fields = NULL, $silent = FALSE)
    {
        // Каква е командата на submita?
        $cmd = Request::get('Cmd');
        
        if (is_array($cmd)) {
            // Ако е изпратена от HTML бутон, то вземаме 
            // командата като ключ от масив
            if (count($cmd) > 1)
            unset($cmd['default']);
            $this->cmd = key($cmd);
        } elseif(isset($cmd)) {
            // Ако изпращането е от JS, то за команда 
            // вземаме частта до разделителя
            list($this->cmd) = explode('|', $cmd);
        }
        
        // Ако не е тихо въвеждане и нямаме тихо въвеждане, 
        // връщаме въведено към момента
        if((!$this->cmd || $this->cmd == 'refresh') && !$silent) return $this->rec;
        
        // Отбелязан ли е чекбоксът "Игнорирай предупрежденията?"
        $this->ignore = Request::get('Ignore');
        
        $fields = $fields ? $fields : $this->showFields;
        
        if ($fields) {
            $fields = $this->selectFields("", $fields);
        } elseif($silent) {
            $fields = $this->selectFields("#silent == 'silent'");
        } else {
            $fields = $this->selectFields("#input != 'none'");
        }
        
        if (!count($fields)) return FALSE;
        
        foreach ($fields as $name => $field) {
            
            expect($this->fields[$name], "Липсващо поле във формата '{$name}'");
            
            $value = Request::get($name);
            
            // Ако $silent, не сме критични към празните стойности
            if(($value === NULL) && $silent) continue;
            
            if ($value === "" && $field->mandatory) {
                $this->setError($name, "Непопълнено задължително поле" .
                    "|* <b>'|{$field->caption}|*'</b>!");
                continue;
            }
            
            $type = $field->type;
            
            // Предаваме някои свойства на полето на типа
            $options = $field->options;
            
            // Ако във формата има опции, те отиват в типа
            if(count($options)) {
                $type->options = $options;
            }
            
            // Правим проверка, дали избраната стойност е от множеството
            if (is_array($options) && !is_a($type, 'type_Key')) {
                // Не могат да се селектират неща които не са опции  
                if (!isset($options[$value]) || (is_object($options[$value]) && $options[$value]->group)) {
                    $this->setError($name, "Невъзможна стойност за полето" .
                        "|* <b>|{$field->caption}|*</b>!");
                    continue;
                }
                
                // Не могат да се селектират групи!
                if (is_object($options[$value]) && $options[$value]->group) {
                    $this->setError($name, "Група не може да бъде стойност за полето" .
                        "|* <b>|{$field->caption}|*</b>!");
                    continue;
                }
                
                // Празна опция се приема според типа. Числата стават NULL
                if($options[$value] === '' && $value === '') {
                    $value = $type->fromVerbal($value);
                }
            } else {
                
                $value = $type->fromVerbal($value);
                
                // Вдигаме грешка, ако стойността от Request 
                // не може да се конвертира към вътрешния тип
                if ($type->error) {
                    
                    $result = array('error' => $type->error);
                    
                    $this->setErrorFromResult($result, $field, $name);
                    
                    continue;
                }
                
                if (($value === NULL || $value === '') && $field->mandatory) {
                    $this->setError($name, "Непопълнено задължително поле" .
                        "|* <b>'|{$field->caption}|*'</b>!");
                    continue;
                }
                
                // Валидиране на стойността чрез типа
                $result = $type->isValid($value);
                
                // Ако имаме нова стойност след валидацията - присвояваме я.
                // По този начин стойността се 'нормализира'
                if ($result['value']) {
                    $value = $result['value'];
                }
                
                $this->setErrorFromResult($result, $field, $name);
            }
            
            $this->rec->{$name} = $value;
        }
        
        return $this->rec;
    }
    
    
    /**
     * Задава екшън-а на формата
     */
    function setAction($params)
    {
        if(is_array($params)) {
            $this->action = $params;
        } else {
            $this->action = func_get_args();
        }
    }
    
    
    /**
     * Ако резултата от Request::get() за дадено поле съдържа
     * грешка/предупреждение то се пренася във формата
     */
    function setErrorFromResult($result, $field, $name)
    {
        if ($result['warning'] && !$result['error']) {
            $this->setWarning($name, "Възможен проблем с полето|" .
                "* <b>'|" . $field->caption .
                "|*'</b>!<br><small style='color:red'>" . "|" .
                $result['warning'] . "|*</small>");
        }
        
        if ($result['error']) {
            $this->setError($name, "Некоректна стойност на полето|" .
                "* <b>'|" . $field->caption .
                "|*'</b>!<br><small style='color:red'>" . "|" .
                $result['error'] .
                ($result['warning'] ? ("|*<br>|" .
                        $result['warning']) : "") . "|*</small>");
        }
    }
    
    
    /**
     * Рендира общия план на формата
     */
    function renderLayout_()
    {
        ht::setUniqId($this->formAttr);
        
        if (!$this->layout) {
            if($this->view == 'horizontal') {
                $this->layout = new ET(
                    "<form style='margin:0px;' id='" .
                    $this->formAttr['id'] .
                    "' method=\"[#FORM_METHOD#]\" action=\"[#FORM_ACTION#]\" <!--ET_BEGIN ON_SUBMIT-->onSubmit=\"[#ON_SUBMIT#]\"<!--ET_END ON_SUBMIT-->>\n" .
                    "\n<div  class='clearfix21' style='margin-top:5px;'>" .
                    "<!--ET_BEGIN FORM_ERROR--><div class=\"formError\">[#FORM_ERROR#]</div><!--ET_END FORM_ERROR-->" .
                    "<!--ET_BEGIN FORM_INFO--><div class=\"formInfo\">[#FORM_INFO#]</div><!--ET_END FORM_INFO-->" .
                    "<!--ET_BEGIN FORM_HIDDEN-->[#FORM_HIDDEN#]<!--ET_END FORM_HIDDEN-->" .
                    "\n" .
                    "<!--ET_BEGIN FORM_FIELDS--><div style='float:left;'>[#FORM_FIELDS#]</div><!--ET_END FORM_FIELDS-->\n" .
                    "<!--ET_BEGIN FORM_TOOLBAR--><div style='float:left;width:5px;'>&nbsp;</div><div style='float:left;'>[#FORM_TOOLBAR#]</div><!--ET_END FORM_TOOLBAR-->\n" .
                    "</div></form>\n" .
                    "\n"
                );
            } else {
                $this->layout = new ET(
                    "<form style='margin:0px;' id='" .
                    $this->formAttr['id'] .
                    "' method=\"[#FORM_METHOD#]\" action=\"[#FORM_ACTION#]\" <!--ET_BEGIN ON_SUBMIT-->onSubmit=\"[#ON_SUBMIT#]\"<!--ET_END ON_SUBMIT-->>\n" .
                    "<table cellspacing=0 cellpadding=0 class=\"formTable\">\n" .
                    "<!--ET_BEGIN FORM_TITLE--><tr><td class=\"formTitle\">[#FORM_TITLE#]</td></tr><!--ET_END FORM_TITLE-->" .
                    "<tr><td class=\"formSection\">" .
                    "<!--ET_BEGIN FORM_ERROR--><div class=\"formError\">[#FORM_ERROR#]</div><!--ET_END FORM_ERROR-->" .
                    "<!--ET_BEGIN FORM_INFO--><div class=\"formInfo\">[#FORM_INFO#]</div><!--ET_END FORM_INFO-->" .
                    "<!--ET_BEGIN FORM_FIELDS--><div class=\"formFields\">[#FORM_FIELDS#]</div><!--ET_END FORM_FIELDS-->" .
                    "<!--ET_BEGIN FORM_HIDDEN-->[#FORM_HIDDEN#]<!--ET_END FORM_HIDDEN-->" .
                    "</td></tr><!--ET_BEGIN FORM_TOOLBAR--><tr><td style='padding:0px;'><div class=\"formToolbar\">[#FORM_TOOLBAR#]</div></td></tr><!--ET_END FORM_TOOLBAR--></table>" .
                    "</form>\n");
            }
        }
        
        if (count($this->styles)) {
            foreach ($this->styles as $selector => $style) {
                $this->layout->appendOnce("\n#" . $this->formAttr['id'] . " {$selector} {{$style}}", "STYLES");
            }
        }
        
        return $this->layout;
    }
    
    
    /**
     * Рендира титлата на формата
     */
    function renderTitle_()
    {
        if (!$this->title)
        return NULL;
        
        return new ET('[#1#]', tr($this->title));
    }
    
    
    /**
     * Рендира грешките
     */
    function renderError_()
    {
        if (count($this->errors)) {
            $tpl = new ET("
                <!--ET_BEGIN ERRORS_TITLE-->\n" .
                "<b>[#ERRORS_TITLE#]:</b><ul>[#ERROR_ROWS#]</ul>\n" .
                "<!--ET_END ERRORS_TITLE-->" .
                "<!--ET_BEGIN WARNINGS_TITLE-->\n" .
                "<b>[#WARNINGS_TITLE#]:</b><ul>[#WARNING_ROWS#]</ul>\n" .
                "<div style='border-top:solid 1px #aa7;padding-top:5px;'>[#IGNORE#]</div>\n" .
                "<!--ET_END WARNINGS_TITLE-->\n");
            
            $cntErr = 0;
            $cntWrn = 0;
            
            foreach ($this->errors as $field => $errRec) {
                if($errRec->msg) {
                    if (!$errRec->ignorable) {
                        $tpl->append("<li>" . tr($errRec->msg) . "</li>", 'ERROR_ROWS');
                        $cntErr++;
                    } else {
                        $tpl->append("<li>" . tr($errRec->msg) . "</li>", 'WARNING_ROWS');
                        $cntWrn++;
                    }
                }
            }
            
            if ($cntErr) {
                $tpl->append(tr($cntErr > 1 ? 'Грешки' : 'Грешка'), 'ERRORS_TITLE');
            }
            
            if ($cntWrn) {
                if($cntWrn>1) {
                    $label = tr('Игнорирай предупрежденията');
                    $title = tr('Предупреждения');
                } else {
                    $label = tr('Игнорирай предупреждениeто');
                    $title = tr('Предупреждение');
                }
                $tpl->append($title, 'WARNINGS_TITLE');
                $tpl->append(
                    "<input type='checkbox' class='checkbox' name='Ignore' id='Ignore'" .
                    "value='1'" . ($this->ignore ? " checked='checked'" : "") .
                    "><label for='Ignore'>{$label}</label>", 'IGNORE');
            }
            
            return $tpl;
        } else {
            return NULL;
        }
    }
    
    
    /**
     * Рендира информацията
     */
    function renderInfo_()
    {
        if (!$this->info)
        return NULL;
        
        return new ET($this->info);
    }
    
    
    /**
     * Използва се при подреждането на полетата
     */
    function cmpFormOrder($a, $b)
    {
        if ($a->formOrder == $b->formOrder) {
            return 0;
        }
        
        return ($a->formOrder > $b->formOrder) ? + 1 : -1;
    }
    
    
    /**
     * Рендира полетата на формата
     * Те се задават чрез обект от клас FieldSet
     */
    function renderFields_()
    {
        // Полетата
        if ($this->showFields) {
            $fields = $this->selectFields("#input != 'hidden'", $this->showFields);
        } else {
            $fields = $this->selectFields("#input == 'input' || (#kind == 'FLD' && #input != 'none' && #input != 'hidden')");
        }
        
        if (count($fields)) {
            
            $i = 1;
            
            foreach ($fields as $name => $field) {
                $fields[$name]->formOrder = (float) $field->formOrder ? $field->formOrder : $i++;
            }
            
            uasort($fields, array($this, 'cmpFormOrder'));
            
            $fieldsLayout = $this->renderFieldsLayout($fields);
            
            $vars = $this->prepareVars($this->renderVars);
            
            // Създаваме input - елементите
            foreach ($fields as $name => $field) {
                
                expect($field->kind, $name, 'Липсващо поле');
                
                $options = $field->options;
                
                $attr = $field->attr;
                
                if ($field->hint) {
                    $attr['title'] = tr($field->hint);
                }
                
                if ($field->width) {
                    $attr['style'] .= "width:{$field->width};";
                }
                if ($field->class) {
                    $attr['class'] = trim($attr['class']) . " {$field->class}";
                }
               
                if ($field->height) {
                    $attr['style'] .= "height:{$field->height};";
                }
                
                if ($field->placeholder) {
                    $attr['placeholder'] = tr($field->placeholder);
                } elseif ($this->view == 'horizontal') {
                    $attr['placeholder'] = tr($field->caption);
                }
                
                if ($this->gotErrors($name)) {
                    
                    if($this->errors[$name]->ignorable) {
                        $attr['class'] .= ' inputWarning';
                    } else {
                        $attr['class'] .= ' inputError';
                    }
                    
                    if (!$firstError) {
                        ht::setUniqId($attr);
                        $idForFocus = $attr['id'];
                        $firstError = TRUE;
                    }
                }
                
                $type = clone($field->type);
                
                if ($field->maxRadio) {
                    setIfNot($type->params['maxRadio'], $field->maxRadio);
                }
                
                if ($field->maxColumns) {
                    setIfNot($type->params['maxColumns'], $field->maxColumns);
                }
                
                if ($field->columns) {
                    setIfNot($type->params['columns'], $field->columns);
                }
                
                if ($field->options) {
                    $type->options = $field->options;
                }
                
                // Стойността на полето
                $value = $vars[$name];
                
                // Ако нямаме стойност и има грешка за полето, 
                // вземаме стойността от Request-а
                if ($this->gotErrors($field->name)) {
                    $value = $attr['value'] = Request::get($field->name);
                }
                
                // Ако полето има свойството да поема фокуса
                // фокусираме на него
                if(!$firstError && $field->focus) {
                    ht::setUniqId($attr);
                    $idForFocus = $attr['id'];
                }
                
                // Рендиране на select или input полето
                if (count($options) > 0 && !is_a($type, 'type_Key')) {
                    unset($attr['value']);
                    
                    $input = ht::createSmartSelect($options, $name, $value, $attr,
                        $type->params['maxRadio'],
                        $type->params['maxColumns'],
                        $type->params['columns']);
                } else {
                    $input = $type->renderInput($name, $value, $attr);
                }
                
                $fieldsLayout->replace($input, $name);
            }
        }
        
        if ($idForFocus) {
            $fieldsLayout->appendOnce("document.getElementById('{$idForFocus}').focus();", 'ON_LOAD');
        }
        
        return $fieldsLayout;
    }
    
    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput($name)
    {
        $field = $this->fields[$name];
        
        expect($field->kind, $name, 'Липсващо поле');
        
        $options = $field->options;
        
        $attr = $field->attr;
        
        if ($field->hint) {
            $attr['title'] = tr($field->hint);
        }
        
        if ($field->width) {
            $attr['style'] .= "width:{$field->width};";
        }
        
        if ($field->height) {
            $attr['style'] .= "height:{$field->height};";
        }
        
        if ($field->placeholder) {
            $attr['placeholder'] = tr($field->placeholder);
        } elseif ($this->view == 'horizontal') {
            $attr['placeholder'] = tr($field->caption);
        }
        
        if ($this->gotErrors($name)) {
            
            if($this->errors[$name]->ignorable) {
                $attr['class'] .= ' inputWarning';
            } else {
                $attr['class'] .= ' inputError';
            }
        }
        
        $type = clone($field->type);
        
        if ($field->maxRadio) {
            setIfNot($type->params['maxRadio'], $field->maxRadio);
        }
        
        if ($field->maxColumns) {
            setIfNot($type->params['maxColumns'], $field->maxColumns);
        }
        
        if ($field->columns) {
            setIfNot($type->params['columns'], $field->columns);
        }
        
        if ($field->options) {
            $type->options = $field->options;
        }
        
        // Стойността на полето
        $value = isset($this->rec->{$name}) ? $this->rec->{$name} : $field->value;
        
        // Ако нямаме стойност и има грешка за полето, 
        // вземаме стойността от Request-а
        if ($this->gotErrors($field->name)) {
            $value = $attr['value'] = Request::get($field->name);
        }
        
        // Рендиране на select или input полето
        if (count($options) > 0 && !is_a($type, 'type_Key')) {
            unset($attr['value']);
            
            $input = ht::createSmartSelect($options, $name, $value, $attr,
                $type->params['maxRadio'],
                $type->params['maxColumns'],
                $type->params['columns']);
        } else {
            $input = $type->renderInput($name, $value, $attr);
        }
        
        return $input;
    }
    
    
    /**
     * Подготвя шаблона за инпут-полетата
     */
    function renderFieldsLayout($fields)
    {
        if ($this->fieldsLayout) return new ET($this->fieldsLayout);
        
        if($this->view == 'horizontal') {
            
            $tpl = new ET("[#FIELDS#]");
            
            foreach ($fields as $name => $field) {
                
                $fld = new ET("<div style='float:left;margin-right:7px;margin-bottom:2px;'>[#{$field->name}#][#UNIT#]</div>");
                
                $fld->replace($field->unit ? ('&nbsp;' . $field->unit) : '', 'UNIT');
                
                $tpl->append($fld, 'FIELDS');
            }
        } else {
            
            $lastCaptionArr = array();
            
            $tpl = new ET("<table cellpadding=\"3\" border=0 cellspacing=\"0\" width='100%'>[#FIELDS#]</table>");
            
            foreach ($fields as $name => $field) {
                
                expect($field->kind, $name, 'Липсващо поле');
                
                $captionArr = explode('->', ltrim($field->caption, '@'));
                $captionArrCount = count($captionArr);
                $emptyRow = count($lastCaptionArr) - $captionArrCount;
                $headerRow = $space = '';
                
                foreach ($captionArr as $id => $c) {
                    $caption = tr($c);
                    
                    // Удебеляваме имената на задължителните полета
                    if ($field->mandatory) {
                        $caption = "<b>$caption</b>";
                    } else {
                        $caption = "<b style='color:#666;'>$caption</b>";
                    }
                    
                    if ($lastCaptionArr[$id] != $c && $id != ($captionArrCount - 1)) {
                        $headerRow .= "<div class=\"formGroup\">{$space}$caption</div>";
                        $space .= "&nbsp;&nbsp;&nbsp;";
                    }
                }
                
                $lastCaptionArr = $captionArr;
                
                if (Mode::is('screenMode', 'narrow')) {
                    if ($emptyRow > 0) {
                        $tpl->append("<tr><td></td></tr>", 'FIELDS');
                    }
                    
                    if ($headerRow) {
                        $tpl->append("<tr><td>$headerRow</td></tr>", 'FIELDS');
                    }
                    $fld = new ET("<tr><td nowrap style='padding-top:5px;'><small>[#CAPTION#][#UNIT#]</small><br>[#{$field->name}#]</td></tr>");
                    $fld->replace($field->unit ? (', ' . $field->unit) : '', 'UNIT');
                    $fld->replace($caption, 'CAPTION');
                } else {
                    if ($emptyRow > 0) {
                        $tpl->append("<tr><td colspan=2></td></tr>", 'FIELDS');
                    }
                    
                    if ($headerRow) {
                        $tpl->append("<tr><td colspan=2>$headerRow</td></tr>", 'FIELDS');
                    }
                    $fld = new ET("<tr><td  align=right valign=top class='formFieldCaption'>[#CAPTION#]:</td><td>[#{$field->name}#][#UNIT#]</td></tr>");
                    $fld->replace($field->unit ? ('&nbsp;' . $field->unit) : '', 'UNIT');
                    $fld->replace($caption, 'CAPTION');
                }
                
                $tpl->append($fld, 'FIELDS');
            }
        }
        
        return $tpl;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function prepareVars($params)
    {
        $vars = arr::make($params);
        $rec = arr::make($this->rec);
        
        if (count($this->fields)) {
            foreach ($this->fields as $name => $field) {
                if (!array_key_exists($field->name, $vars)) {
                    $vars[$field->name] = isset($rec[$field->name]) ? $rec[$field->name] : NULL;
                }
            }
        }
        
        return $vars;
    }
    
    
    /**
     * Рендира hidden полетата на формата
     */
    function renderHidden_()
    {
        $vars = $this->prepareVars($this->renderVars);
        
        // Определяме скритите полета
        
        if (count($this->fields)) {
            foreach ($this->fields as $field) {
                if ($field->input == 'hidden') {
                    $hiddens[$field->name] = $vars[$field->name];
                }
            }
        }
        
        // Вкарваме скритите полета
        $tpl = ht::createHidden($hiddens);
        
        return $tpl;
    }
    

    /**
     * Връща метода на заявката на формата
     */
    function getMethod()
    {
        return $this->method ? strtoupper($this->method) : 'POST';
    }

    
    /**
     * Рендира метода на формата
     */
    function renderMethod_()
    {
        return new ET($this->getMethod());
    }
    
    
    /**
     * Рендира екшън-а на формата
     */
    function renderAction_()
    {
        $tpl = new ET($this->action ? toUrl($this->action) : "");
        
        return $tpl;
    }
    
    
    /**
     * Рендира лентата с инструменти под формата
     */
    function renderToolbar_()
    {
        expect(is_a($this->toolbar, 'core_Toolbar'), 'Очаква се core_Toolbar');
        
        return $this->toolbar->renderHtml();
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function smartSet($name, $val)
    {
        if ($val) {
            $this->{$name} = $val;
        }
    }
    
    
    /**
     * Рендира формата
     */
    function renderHtml_($fields = NULL, $vars = NULL)
    {
        $this->smartSet('showFields', arr::make($fields, TRUE));
        $this->smartSet('renderVars', arr::make($vars, TRUE));
        
        // Вземаме общия лейаут
        $tpl = $this->layout ? new ET($this->layout) : $this->renderLayout();
        
        $views = array(
            'TITLE',
            'ERROR',
            'INFO',
            'FIELDS',
            'HIDDEN',
            'TOOLBAR',
            'METHOD',
            'ACTION'
        );
        
        foreach ($views as $view) {
            $method = 'render' . $view;
            $tpl->append($this->$method(), "FORM_{$view}");
        }
        
        return $tpl;
    }
    
    
    /**
     * Добавя стойност/и на атрибут за INPUT елемент. Ако не е посочен елемент, то е за всички
     */
    function addAttr($name, $attr)
    {
        if (is_string($attr)) {
            $attr = array(
                'style' => $attr
            );
        }
        
        if ($name == '*') {
            foreach ($this->fields as $field) {
                if ($field->input == 'input') {
                    $this->fields[$field->name]->attr = arr::union($field->attr, $attr);
                }
            }
        } else {
            $name = arr::make($name, TRUE);
            
            foreach ($name as $n) {
                $this->fields[$n]->attr = arr::union($this->fields[$n]->attr, $attr);
            }
        }
    }
    
    
    /**
     * Създава (ако е необходимо) скрити полета във формата и им присвоява стойности
     */
    function setHidden($fields, $val = NULL)
    {
        $fields = arr::make($fields);
        
        foreach ($fields as $n => $v) {
            if (is_int($n)) {
                $name = $v;
                $value = $val;
            } else {
                $name = $n;
                $value = $v;
            }
            
            if ($this->fields[$name]) {
                // Ако имаме такова поле, само му задаваме дефолт стойност
                $this->fields[$name]->input = 'hidden';
            } else {
                // Ако нямаме -> създаваме скрито поле
                $this->FNC($name, 'varchar(65000)', array(
                        'input' => 'hidden'
                    ));
            }
            
            $this->setDefault($name, $value);
        }
    }
    
    
    /**
     * Задава кои полета, ще се показват във формата
     */
    function setDefaults($defaults)
    {
        $arr = arr::make($defaults);
        
        if (count($arr)) {
            foreach ($arr as $name => $value) {
                $this->rec->{$name} = $value;
            }
        }
    }
    
    
    /**
     * Задава стойности по подразбиране
     */
    function setDefault($var, $value)
    {
        expect($var, '$var не може да бъде празно');
        $this->rec->{$var} = $value;
    }
    
    
    /**
     * Вдига флаг за грешка на посоченото поле
     */
    function setError($field, $msg, $ignorable = FALSE)
    {
        $arr = arr::make($field);
        
        foreach($arr as $f) {
            $errRec = new stdClass();
            $errRec->msg = $msg;
            $errRec->ignorable = $ignorable;
            
            if(!$this->errors[$f]) {
                $this->errors[$f] = $errRec;
                $msg = FALSE;
            }
        }
    }
    
    
    /**
     * Вдига флаг за предупреждение на посоченото поле
     */
    function setWarning($field, $msg)
    {
        $this->setError($field, $msg, 'ignorable');
    }
    
    
    /**
     * Връща истина, ако формата е изпратена без грешки
     */
    function isSubmitted()
    {
        $status = $this->cmd && $this->cmd != 'refresh' && !$this->gotErrors();

        if($status) {
            expect($this->getMethod() == $_SERVER['REQUEST_METHOD']);
        }

        return $status;
    }
    
    
    /**
     * Връща истина, ако има грешки, или предупреждения,
     * без в Request да им ignore_warnings
     */
    function gotErrors($field = NULL)
    {
        if (count($this->errors)) {
            if ($field) {
                $fieldArr = arr::make($field);
                
                foreach($fieldArr as $f) {
                    if ($this->errors[$f]) {
                        if (!$this->errors[$f]->ignorable || !$this->ignore) {
                            
                            return TRUE;
                        }
                    }
                }
            } else {
                foreach ($this->errors as $field => $errRec) {
                    if (!$errRec->ignorable || !$this->ignore) {
                        
                        return TRUE;
                    }
                }
            }
        }
        
        return FALSE;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function setReadOnly($name, $value = NULL)
    {
        $field = $this->getField($name);
        
        if (!isset($value)) {
            $value = empty($this->rec->{$name}) ? '' : $this->rec->{$name};
        }
        
        $verbal = $field->type->toVerbal($value);
        
        $this->setOptions($name, array(
                $value => $verbal
            ));
    }
}