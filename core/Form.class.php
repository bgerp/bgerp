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
    public $tpl;
    
    
    /**
     * Заглавие на формата
     */
    public $title;
    
    
    /**
     * Стойности на полетата на формата
     */
    public $rec;
    
    
    /**
     * Общ лейаут на формата
     */
    public $layout;
    
    
    /**
     * Лейаут на инпут-полетата
     */
    public $fieldsLayout;
    
    
    /**
     * Cmd-то на бутона с който е субмитната формата
     */
    public $cmd;
    
    
    /**
     * Атрибути на елемента <FORM ... >
     */
    public $formAttr = array();
    
    
    /**
     * Редове с дефиниции [Селектор на стила] => [Дефиниция на стила]
     */
    public $styles = array();
    
    
    /**
     * Кои полета от формата да се показват
     */
    public $showFields;
    
    
    /**
     * В каква посока да са разположени полетата?
     */
    public $view = 'vertical';
    
    
    /**
     * CSS class на формата
     */
    public $class;
    
    
    /**
     * Тулбар на формата
     * 
     * @param core_Toolbar
     */
    public $toolbar;


    /**
     * Съобщение за потребителя при редниране на формата
     */
    public $info;
    
    
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
            // За улесняване на тесването
            if(isset($cmd['default']) && $cmd['default'] == 'refresh') {
                $cmd['refresh'] = 1;
            }
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
        
        // Ако има функции за викане за генериране на опции
        $optionsFunc = $this->selectFields("#optionsFunc");
        if ($optionsFunc) {
            foreach ($optionsFunc as $name => $field) {
                if ($field->type instanceof type_Varchar || $field->type instanceof type_Keylist || $field->type instanceof type_Set) {
                    $field->type->suggestions = cls::callFunctArr($field->optionsFunc, array($field->type, $field->type->suggestions));
                } else {
                    $field->type->options = cls::callFunctArr($field->optionsFunc, array($field->type, $field->type->options));
                }
            }
        }
 
        // Ако не е тихо въвеждане и нямаме тихо въвеждане, 
        // връщаме въведено към момента
        if((!$this->cmd) && !$silent) return $this->rec;
        
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
            
            $captions = str_replace('->', '|* » |', $field->caption);
            
            // Ако $silent, не сме критични към празните стойности
            if ($silent) {
                if ($value === NULL) continue;
                
                // Когато полето е скрито и няма стойност, гледаме да не е NULL
                if ($field->input == 'hidden' && !$value && ($field->type->toVerbal($value) === NULL)) continue;
            }
            
            if ($value === "" && $field->mandatory && $this->cmd != 'refresh') {
                $this->setError($name, "Непопълнено задължително поле" .
                    "|* <b>'|{$captions}|*'</b>!");

                $this->fields[$name]->input = 'input';
                
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
            if (is_array($options) && !is_a($type, 'type_Key') && !is_a($type, 'type_Key2')) {
                
                // Не могат да се селектират неща които не са опции  
                if ((!array_key_exists($value, $options) && $this->cmd != 'refresh') || (is_object($options[$value]) && $options[$value]->group)) {
                    $this->setError($name, "Невъзможна стойност за полето" .
                        "|* <b>|{$captions}|*</b>!");
                    $this->fields[$name]->input = 'input';
                    continue;
                }
                
                // Не могат да се селектират групи!
                if (is_object($options[$value]) && $options[$value]->group) {
                    $this->setError($name, "Група не може да бъде стойност за полето" .
                        "|* <b>|{$captions}|*</b>!");
                    $this->fields[$name]->input = 'input';
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
                
                if (($value === NULL || $value === '') && $field->mandatory && $this->cmd != 'refresh') {
                    $this->setError($name, "Непопълнено задължително поле" .
                        "|* <b>'|{$captions}|*'</b>!");
                    
                    $this->fields[$name]->input = 'input';
                    
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
            
            if($this->cmd != 'refresh' || is_array($value) || strlen($value)) {
                $this->rec->{$name} = $value; 
            }
        }
   
        return $this->rec;
    }
    
    
    /**
     * Валидиране полетата на форма с възможност за други стойности
     * 
     * @param string|array $fields
     * @param boolean $silent
     * @param array $values
     * @return boolean
     */
    public function validate($fields = NULL, $silent = FALSE, $values = NULL)
    {
        $fields = $fields ? $fields : $this->showFields;
        
        if ($fields) {
            $fields = $this->selectFields("", $fields);
        } elseif($silent) {
            $fields = $this->selectFields("#silent == 'silent'");
        } else {
            $fields = $this->selectFields("#input != 'none'");
        }
        
        // Ако има функции за викане за генериране на опции
        $optionsFunc = $this->selectFields("#optionsFunc");
        if ($optionsFunc) {
            
            foreach ($optionsFunc as $name => $field) {
                if ($field->type instanceof type_Varchar || $field->type instanceof type_Keylist || $field->type instanceof type_Set) {
                    $field->type->suggestions = cls::callFunctArr($field->optionsFunc, array($field->type, $field->type->suggestions));
                } else {
                    $field->type->options = cls::callFunctArr($field->optionsFunc, array($field->type, $field->type->options));
                }
            }
        }
        
        if (!count($fields)) return FALSE;
        
        foreach ($fields as $name => $field) {
        
            expect($this->fields[$name], "Липсващо поле във формата '{$name}'");
        
            $value = isset($values[$name]) ? $values[$name] : Request::get($name);
        
            // Ако $silent, не сме критични към празните стойности
            if ($silent) {
                if ($value === NULL) continue;
                
                // Когато полето е скрито и няма стойност, гледаме да не е NULL
                if ($field->input == 'hidden' && !$value && ($field->type->toVerbal($value) === NULL)) continue;
            }
           
            $captions = str_replace('->', '|* » |', $field->caption);
            
            if ($value === "" && $field->mandatory) {
                $this->setError($name, "Непопълнено задължително поле" .
                    "|* <b>'|{$captions}|*'</b>!");
                
                $this->fields[$name]->input = 'input';
                
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
            if (is_array($options) && !is_a($type, 'type_Key') && !is_a($type, 'type_Key2')) {
                // Не могат да се селектират неща които не са опции
                if (!isset($options[$value]) || (is_object($options[$value]) && $options[$value]->group)) {
                    $this->setError($name, "Невъзможна стойност за полето" .
                        "|* <b>|{$captions}|*</b>!");
                    $this->fields[$name]->input = 'input';
                    continue;
                }
        
                // Не могат да се селектират групи!
                if (is_object($options[$value]) && $options[$value]->group) {
                    $this->setError($name, "Група не може да бъде стойност за полето" .
                        "|* <b>|{$captions}|*</b>!");
                    $this->fields[$name]->input = 'input';
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
                        "|* <b>'|{$captions}|*'</b>!");
                    
                    $this->fields[$name]->input = 'input';
                    
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
        $captions = str_replace('->', '|* » |', $field->caption);
        
        if ($result['warning'] && !$result['error']) {
            $haveErr = TRUE;
            $this->setWarning($name, "Възможен проблем с полето|" .
                "* <b>'|" . $captions .
                "|*'</b>!<br><small>" . "|" .
                $result['warning'] . "|*</small>");
        }
        
        if ($result['error']) {
            $haveErr = TRUE;
            $this->setError($name, "Некоректна стойност на полето|" .
                "* <b>'|" . $captions .
                "|*'</b>!<br><small style='color:red'>" . "|" .
                $result['error'] .
                ($result['warning'] ? ("|*<br>|" .
                        $result['warning']) : "") . "|*</small>");
        }
        
        if ($haveErr && $field->input == 'hidden') {
            $field->input = 'input';
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
                    "<form <!--ET_BEGIN CLASS-->class = '[#CLASS#]'<!--ET_END CLASS--> [#FORM_ATTR#] " .
                    "<!--ET_BEGIN ON_SUBMIT-->onSubmit=\"[#ON_SUBMIT#]\"<!--ET_END ON_SUBMIT-->>\n" .
                    "\n<div  class='clearfix21 horizontal' style='margin-top:5px;'>" .
                    "<!--ET_BEGIN FORM_ERROR-->[#FORM_ERROR#]<!--ET_END FORM_ERROR-->" .
                    "<!--ET_BEGIN FORM_INFO--><div class=\"formInfo\">[#FORM_INFO#]</div><!--ET_END FORM_INFO-->" .
                    "<!--ET_BEGIN FORM_HIDDEN-->[#FORM_HIDDEN#]<!--ET_END FORM_HIDDEN-->" .
                    "\n" .
                    "<!--ET_BEGIN FORM_FIELDS--><div class='formFields'>[#FORM_FIELDS#]</div><!--ET_END FORM_FIELDS-->\n" .
                    "<!--ET_BEGIN FORM_TOOLBAR--><div class='formToolbar'>[#FORM_TOOLBAR#]</div><!--ET_END FORM_TOOLBAR-->\n" .
                    "</div></form>\n" .
                    "\n"
                );
            } else {
                $this->layout = new ET(
                    "<form <!--ET_BEGIN CLASS-->class = '[#CLASS#]'<!--ET_END CLASS--> [#FORM_ATTR#] " .
                    "<!--ET_BEGIN ON_SUBMIT-->onSubmit=\"[#ON_SUBMIT#]\"<!--ET_END ON_SUBMIT-->>\n" .
                    "[#BEFORE_MAIN_TABLE#]" . 
                    "\n<div  class='clearfix21 vertical' style='margin-top:5px;'>" .
                    "\n<table class=\"formTable\">\n" .
                    "\n<!--ET_BEGIN FORM_TITLE--><tr><td class=\"formTitle\">[#FORM_TITLE#]</td></tr><!--ET_END FORM_TITLE-->" .
                    "\n<tr><td class=\"formSection\">" .
                    "<!--ET_BEGIN FORM_ERROR-->\n[#FORM_ERROR#]<!--ET_END FORM_ERROR-->" .
                    "<!--ET_BEGIN FORM_INFO-->\n<div class=\"formInfo\">[#FORM_INFO#]</div><!--ET_END FORM_INFO-->" .
                    "<!--ET_BEGIN FORM_FIELDS-->\n<div class=\"formFields\">[#FORM_FIELDS#]</div><!--ET_END FORM_FIELDS-->" .
                    "<!--ET_BEGIN FORM_HIDDEN-->\n[#FORM_HIDDEN#]<!--ET_END FORM_HIDDEN-->" .
                    "\n</td></tr><!--ET_BEGIN FORM_TOOLBAR-->\n<tr><td style='padding:0px;'><div class=\"formToolbar\">[#FORM_TOOLBAR#]</div></td></tr><!--ET_END FORM_TOOLBAR--></table>" .
                    "[#AFTER_MAIN_TABLE#]" .
                    "\n</div>" .
                    "\n</form>\n");
                
                jquery_Jquery::run($this->layout, "setFormElementsWidth();");
                jquery_Jquery::runAfterAjax($this->layout, "setFormElementsWidth");
                jquery_Jquery::run($this->layout, "markElementsForRefresh();");
                jquery_Jquery::run($this->layout, "$(window).resize(function(){setFormElementsWidth();});");
            }
            
            // Ако има зададен клас за формата, добавяме го
            if(isset($this->class)){
            	$this->layout->append($this->class, 'CLASS');
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
                <!--ET_BEGIN ERRORS_TITLE--><div class=\"formError\">\n" .
                "<b>[#ERRORS_TITLE#]:</b><ul>[#ERROR_ROWS#]</ul>\n" .
                "<!--ET_END ERRORS_TITLE--></div>" .
                "<!--ET_BEGIN WARNINGS_TITLE--><div class=\"formWarning\">\n" .
                "<b>[#WARNINGS_TITLE#]:</b><ul>[#WARNING_ROWS#]</ul>\n" .
                "<div style='border-top:solid 1px blue;padding-top:5px;'>[#IGNORE#]</div>\n" .
                "</div><!--ET_END WARNINGS_TITLE-->\n");
            
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
        if (!$this->info) return NULL;
        
        // Обръщаме инфото в шаблон и го експейваме
        return new core_ET('[#1#]', $this->info);
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
            
            if($this->defOrder) {
                $this->orderField();
                $newFields = array();
                foreach($this->fields as $name => $field) {
                    if(isset($fields[$name])) {
                        $newFields[$name] = $fields[$name];
                    }
                }
                $fields = $newFields;
            }

            $i = 1;
            
            foreach ($fields as $name => $field) {
                expect(is_object($fields[$name]), $fields, $name);
                $fields[$name]->formOrder = (float) $field->formOrder ? $field->formOrder : $i++;
            }
            
            uasort($fields, array($this, 'cmpFormOrder'));

            $vars = $this->prepareVars($this->renderVars);

            if(Mode::is('staticFormView')) {
                foreach($fields as $name => $field) {
                    if(!isset($vars[$name])) {
                        unset($fields[$name]);
                    }
                    
                    $captionArr = explode('->', $field->caption);
                    if(count($captionArr) == 2){
                    	$field->caption = $captionArr[1];
                    }
                }
            }
            
            // Скрива полетата, които имат само една опция и атрибут `hideIfOne`
            foreach ($fields as $name => $field) {
            	if($field->hideIfOne) {
            	    
                    if ($field->type instanceof type_Key) {
                        $field->type->prepareOptions();
                    }
                    
                    $options = $field->options;
                    
                    if (($field->type instanceof type_Key2) && (!isset($options) || empty($options))) {
                        $options = $field->type->getOptions();
                    }
                    
	                if((isset($options) && count($options) == 1)) {
	                	unset($fields[$name]);
	                } elseif(isset($field->type->options) && count($field->type->options) == 1) {
	                	unset($fields[$name]);
	                }
            	}
            }

            $fieldsLayout = $this->renderFieldsLayout($fields, $vars);
            
            // Създаваме input - елементите
            foreach($fields as $name => $field) {
                
                expect($field->kind, $name, 'Липсващо поле');

                if(Mode::is('staticFormView')) {
                    $value = $field->type->toVerbal($vars[$name]);
                    $attr = array('class' => 'formFieldValue');
                    $value = ht::createelement('div', $attr, $value);
                    $fieldsLayout->replace($value, $name);
                }

                if(strtolower($field->autocomplete) == 'off') {
                    $this->formAttr['autocomplete'] = 'off';
                }
                
                $options = $field->options;
                
                $attr = $field->attr;
                
                if ($field->hint) {
                    $attr['title'] = tr($field->hint);
                }

                if ($field->class) {
                    $attr['class'] = trim($attr['class']) . " {$field->class}";
                }
               
                if ($field->height) {
                    $attr['style'] .= "height:{$field->height};";
                }
                
                if(strtolower($this->getMethod()) == 'get') {
                    if($field->autoFilter || $field->refreshForm) {
                        $attr['onchange'] = 'this.form.submit();';
                    }
                } else {
                    if($field->removeAndRefreshForm ) {
                        $rFields = str_replace('|', "', '", trim($field->removeAndRefreshForm, '|'));
                        $attr['onchange'] .= "refreshForm(this.form, ['{$rFields}']);";
                    } elseif($field->refreshForm) { 
                        $attr['onchange'] .= "refreshForm(this.form);";
                    }
                }
                                
                if ($field->placeholder) {
                    $attr['placeholder'] = tr($field->placeholder);
                } elseif ($this->view == 'horizontal') {
                    $captions = str_replace('->', '|* » |', $field->caption);
                    $attr['placeholder'] = tr($captions);
                }
                
                $type = clone($field->type);
                
                if ($this->gotErrors($name)) {
                    
                    if($this->errors[$name]->ignorable) {
                        $attr['class'] .= ' inputWarning';
                        $attr['errorClass'] .= ' inputWarning';
                    } else {
                        $attr['class'] .= ' inputError';
                        $attr['errorClass'] .= ' inputError';
                    }
                    
                    if (!$firstError) {
                        ht::setUniqId($attr);
                        $idForFocus = $attr['id'];
                        $firstError = TRUE;
                    }

                    $type->error = TRUE;
                }
                
                
                
                // Стойността на полето
                $value = $vars[$name];
                
                // Ако нямаме стойност и има грешка за полето, 
                // вземаме стойността от Request-а
                if ($this->gotErrors($field->name)) {
                    $value = $attr['value'] = Request::get($field->name);
                }
                
                if (!isset($value) && $field->value && $field->notNull) {
                    $value = $attr['value'] = $field->value;
                }
                
                // Ако полето има свойството да поема фокуса
                // фокусираме на него
                if(!$firstError && $field->focus) {
                    ht::setUniqId($attr);
                    $idForFocus = $attr['id'];
                }
                
                // Задължителните полета, които имат една опция - тя да е избрана по подразбиране
                if(count($options) == 2 && $type->params['mandatory'] && empty($value) && $options[key($options)] === '') {
                    list($o1, $o2) = array_keys($options);
                    if(!empty($o2)) {
                        $value = $o2;
                    } elseif(!empty($o1)) {
                        $value = $o1;
                    }
                }

                // Рендиране на select или input полето
                if ((count($options) > 0 && !is_a($type, 'type_Key') && !is_a($type, 'type_Key2') && !is_a($type, 'type_Enum')) || $type->params['isReadOnly']) {
                    
                    unset($attr['value']);
                    $this->invoke('BeforeCreateSmartSelect', array($input, $type, $options, $name, $value, &$attr));
                    
                    // Гупиране по часта преди посочения разделител
                    if($div = $field->groupByDiv) {
                        $options = ht::groupOptions($options, $div);
                    }

                    $input = ht::createSmartSelect($options, $name, $value, $attr,
                        $type->params['maxRadio'],
                        $type->params['maxColumns'],
                        $type->params['columns']);
                    $this->invoke('AfterCreateSmartSelect', array($input, $type, $options, $name, $value, &$attr));
                } else {
                    $input = $type->renderInput($name, $value, $attr);
                }
                
                $fieldsLayout->replace($input, $name);
            }
        
            if(Mode::is('staticFormView')) {
            	$fieldsLayout->prepend("<div class='staticFormView'>");
            	$fieldsLayout->append("</div>");
            } else {
            	if ($idForFocus) {
            		jquery_Jquery::run($fieldsLayout, "$('#{$idForFocus}').focus();", TRUE);
            	}
            }
        }

        return $fieldsLayout;
    }


    /**
     * Подготвя шаблона за инпут-полетата
     */
    function renderFieldsLayout($fields, $vars)
    {
    	if ($this->fieldsLayout) return new ET($this->fieldsLayout);
        
        if($this->view == 'horizontal') {
            
            $tpl = new ET("[#FIELDS#]");
            
            foreach ($fields as $name => $field) {
                
                $fld = new ET("<div class='hFormField' >[#{$field->name}#][#UNIT#]</div>");
                
                $fld->replace($field->unit ? ('&nbsp;' . tr($field->unit)) : '', 'UNIT');
                
                $tpl->append($fld, 'FIELDS');
            }
        } else {
            
            $lastCaptionArr = array();
            
            $tpl = new ET('<table class="vFormField">[#FIELDS#]</table>');
            
            $fsId = 0; $fsArr = array(); $fsRow = '';

            $plusUrl = sbf("img/16/toggle1.png", "");
            $plusImg =  ht::createElement("img", array('src' => $plusUrl, 'class' => 'btns-icon plus'));
            foreach ($fields as $name => $field) {
                
                if($field->rowStyle) {
                    $rowStyle = " style=\"" . $field->rowStyle . "\"";
                } else {
                    $rowStyle = '';
                }

                expect($field->kind, $name, 'Липсващо поле');
                
                $captionArr = explode('->', ltrim($field->caption, '@'));
                $captionArrCount = count($captionArr);
                $emptyRow = count($lastCaptionArr) - $captionArrCount;
                $headerRow = $space = '';
                
                foreach ($captionArr as $id => $c) {
                    $captionArr[$id] = $caption = $c1 = tr($c);
                    
                    // Удебеляваме имената на задължителните полета
                    if ($field->mandatory || ($id != ($captionArrCount - 1))) {
                        $caption = "<b>$caption</b>";
                    } else {
                        $caption = "$caption";
                    }
                    
                    if ($lastCaptionArr[$id] != $c1 && $id != ($captionArrCount - 1)) {
                        $headerRow .= "<div class=\"formGroup\" >{$space}{$caption}";
                        $space .= "&nbsp;&nbsp;&nbsp;";
                        $group = $c;
                        if(strpos($group, '||')) {
                            list($group, $en) = explode('||', $group);
                        }
                    }
                }
                
                $lastCaptionArr = $captionArr;

                if($headerRow) {
                    $fsId++;
                    $fsArr[$fsId] = $group;
                    $fsRow  = " [#FS_ROW{$fsId}#]";
                    $fsHead = " [#FS_HEAD{$fsId}#]";
                    $headerRow .= "[#FS_IMAGE{$fsId}#]</div>";
                } elseif($emptyRow > 0) {
                    $fsRow  = '';
                    $fsHead = '';
                }

                 
                $caption = core_ET::escape($caption);
                $fUnit = tr($field->unit);
                $fUnit = core_ET::escape($fUnit);
                
                if (Mode::is('screenMode', 'narrow')) {

                    if ($emptyRow > 0) {
                        $tpl->append("\n<tr><td></td></tr>", 'FIELDS');
                    }

                    if ($headerRow) {
                        $tpl->append(new ET("\n<tr{$fsHead}><td>{$headerRow}</td></tr>"), 'FIELDS');
                    }
                    
                    $unit = $fUnit ? (', ' . $fUnit) : '';

                    $fld = new ET("\n<tr class='filed-{$name} {$fsRow}'{$rowStyle}><td class='formCell[#{$field->name}_INLINETO_CLASS#]' nowrap style='padding-top:5px;'><small>{$caption}{$unit}</small><br>[#{$field->name}#]</td></tr>");
                } else {

                    if ($emptyRow > 0) {
                        $tpl->append("\n<tr class='{$fsRow}'><td colspan=2></td></tr>", 'FIELDS');
                    } 
                    
                    if ($headerRow) {
                        $tpl->append(new ET("\n<tr{$fsHead}><td colspan=2>{$headerRow}</td></tr>"), 'FIELDS');
                    }
                    
                    $unit = $fUnit ? ('&nbsp;' . $fUnit) : '';

                    $fld = new ET("\n<tr class='filed-{$name} {$fsRow}'{$rowStyle}><td class='formFieldCaption'>{$caption}:</td><td class='formElement[#{$field->name}_INLINETO_CLASS#]'>[#{$field->name}#]{$unit}</td></tr>");
                }

                if($field->inlineTo) {
                    $fld = new ET(" {$caption} [#{$field->name}#]{$unit}");
                    $tpl->prepend($fld, $field->inlineTo);
                    $tpl->prepend(' inlineTo', $field->inlineTo . '_INLINETO_CLASS');  
                } else {
                    $tpl->append($fld, 'FIELDS');
                }
            }
            
            $usedGroups = self::getUsedGroups($this, $fields, $vars, $vars, 'input');

            // Заменяме състоянието на секциите
            foreach($fsArr as $id => $group) { 
                if(!$usedGroups[$group] && !Mode::is('javascript', 'no')) {
                    $tpl->replace(" fs{$id}  hiddenFormRow", "FS_ROW{$id}");
                    $tpl->replace(" class='fs-toggle{$id}' style='cursor: pointer;' onclick=\"toggleFormGroup({$id});\"", "FS_HEAD{$id}");
                    $tpl->replace(" {$plusImg}", "FS_IMAGE{$id}");
                } 
            }
        }

        return $tpl;
    }
    
    
    /**
     * Подготвя масива със стойностите на полетата
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
        
        // Защита на id - параметъра
        if($vars['id']) {
            $mvc = $this->getMvc();
            $vars['id'] = $mvc->protectId($vars['id']);
        }
 
        return $vars;
    }


    /**
     * Връща MVC класа, асоцииран към формата
     */
    function getMvc()
    {
        if(!($mvc = $this->mvc)) {
            $ctr = $this->action['Ctr'];
            if(!$ctr) {
                expect($ctr = $this->action[0]);
            }

            $mvc = cls::get($ctr);
        }
        
        return $mvc;
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
            core_Request::addUrlHash($hiddens);
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
    function renderAttr_()
    {
        $this->formAttr['method'] = $this->getMethod();
        $this->formAttr['action'] = $this->action ? toUrl($this->action) : "";

        foreach ($this->formAttr as $attr => $content) {
            if($content === TRUE) {
                $content = $attr;
            }
            if (trim($content)) {
                $content = ht::escapeAttr($content);
                $attrStr .= " " . $attr . "=\"" . $content . "\"";
            }            
        }

        return $attrStr;
    }
     
    
    /**
     * Рендира лентата с инструменти под формата
     */
    function renderToolbar_()
    {
        expect(is_a($this->toolbar, 'core_Toolbar'), 'Очаква се core_Toolbar');
        if(defined('TEST_MODE') && TEST_MODE) {
            $this->toolbar->addSbBtn('Refresh', 'refresh');
        }
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
     * Рендира формата като статичен html
     */
    public function renderStaticHtml($fields = NULL, $vars = NULL)
    {
    	Mode::push('staticFormView', TRUE);
    	$html = $this->renderHtml_($fields, $vars);
    	Mode::pop();
    	
    	return $html;
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
            'ATTR',
        );
        
        foreach ($views as $view) {
            $method = 'render' . $view;
            $tpl->append($this->$method(), "FORM_{$view}");
        }

        if($this->cmd == 'refresh' && Request::get('ajax_mode')) {
            $this->ajaxOutput($tpl);
        }
        
        return $tpl;
    }
    

    /**
     * Отпечатва съдържанието на шаблона като JSPN масив за ajax
     */
    public function ajaxOutput($tpl)
    {
        $res = new stdClass();
        $res->css = array_keys(array_flip($tpl->getArray('CSS')));
        foreach($res->css as $key => $file) {
            $res->css[$key] = sbf($file, '');
        }
        
        $res->js = array_keys(array_flip($tpl->getArray('JS')));
        
        foreach($res->js as $key => $file) {
            $res->js[$key] = sbf($file, '');
        }
        $ajaxPage = new ET("[#1#]<!--ET_BEGIN JQRUN-->\n<script type=\"text/javascript\">[#JQRUN#]\n[#ON_LOAD#]</script><!--ET_END JQRUN-->" .
        "<!--ET_BEGIN SCRIPTS-->\n<script type=\"text/javascript\">[#SCRIPTS#]\n</script><!--ET_END SCRIPTS-->", $tpl);
        $res->html = str_replace("</form>", '', $ajaxPage->getContent()) . '</form>';
        $res->html = substr($res->html, strpos($res->html, '<form'));

        core_App::getJson($res);
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
                if(!$this->rec->{$name}) {
                    $this->rec->{$name} = $value;
                }
            }
        }
    }
    
    
    /**
     * Задава стойности по подразбиране
     */
    function setDefault($var, $value)
    {
        expect($var, '$var не може да бъде празно');
        if(!isset($this->rec->{$var})) {
            $this->rec->{$var} = $value;
        }
    }
    
    
    /**
     * Вдига флаг за грешка на посоченото поле
     */
    function setError($field, $msg, $ignorable = FALSE, $oncePerField = TRUE)
    {
        if(haveRole('no_one')) {
            $ignorable = TRUE;
        }

        // Премахваме дублиращи се съобщения
        if(is_array($this->errors) && $oncePerField) {
            foreach($this->errors as $errRec) {
                if(($errRec->msg == $msg) && ($ignorable == $errRec->ignorable)) {
                    $msg = FALSE;
                }
            }
        }
        
        // Добавяме еднократно грешката и маркираме всички полета
        $arr = arr::make($field);
        foreach($arr as $f) {
            $errRec = new stdClass();
            $errRec->msg = $msg;
            $errRec->ignorable = $ignorable;
            
            if(!$this->errors[$f] || ($this->errors[$f]->ignorable && !$ignorable)) {
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
            expect($this->getMethod() == $_SERVER['REQUEST_METHOD'], $this->getMethod(), $_SERVER['REQUEST_METHOD']);
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
        $field = $this->fields[$name];
        
        if (!isset($value)) {
            $value = empty($this->rec->{$name}) ? '' : $this->rec->{$name};
        }
        
        unset($field->type->params['allowEmpty']);

        Mode::push('text', 'plain');
        $verbal = $field->type->toVerbal($value);
        Mode::pop();

        $this->setOptions($name, array(
                "{$value}" => $verbal
            ));
        
        $field->type->params['isReadOnly'] = TRUE;  
    }


    /**
     * Кои групи да се показват
     */
    public static function getUsedGroups($fieldset, $fields, $rec1, $row, $mode = 'single')
    { 
        $res = array();
        $group = '';

        if(is_array($rec1)) {
            $rec = (object) $rec1;
        } else {
            $rec = $rec1;
        }
        
        if(is_array($fields)){
			foreach($fields as $name => $caption1) {
        		if(is_object($caption1)) {
        			$caption = $caption1->caption ? $caption1->caption : $name;
        		} else {
        			$caption = $caption1;
        		}
        		if(!isset($fieldset->fields[$name])) continue;
        		if($fieldset->fields[$name]->{$mode} == 'none') continue;
        		if($mode == 'single' && !isset($row->{$name})) continue;
        	
        		if($fieldset->fields[$name]->autohide == 'any') continue;
        		if($fieldset->fields[$name]->autohide == 'autohide' || $fieldset->fields[$name]->autohide == $mode) {
        			if(!$rec->{$name}) { 
                        continue;
                    }
        			$type = $fieldset->fields[$name]->type;
        			if(isset($type->options) && is_array($type->options) && key( $type->options) == $rec->{$name}) {
                        continue;
                    }
        		}
        	
        		if(strpos($caption, '->')) {
        			list($group, $caption) = explode('->', $caption);
        		}
        	
        		$res[$group] = TRUE;
        		if(strpos($group, '||')) {
        			list($bg, $en) = explode('||', $group);
        			$res[$bg] = TRUE;
        			$res[$bg] = TRUE;
        		}
        	}
        }
 
        return $res;
    }
}