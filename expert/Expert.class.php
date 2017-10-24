<?php



/**
 * Къде да се съхранява състоянието на експертизата?
 */
defIfNot('EXPERT_SAVE_STATE_IN_CACHE', TRUE);


/**
 * Дефинира колко минути е 'живо' състоянието
 */
defIfNot('EXPERT_STATE_LIFETIME', 145);


/**
 * Тип на записа в кеша
 */
defIfNot('EXPERT_CACHE_TYPE', 'Expert');


/**
 * Клас 'core_Expert'
 *
 * Клас-родител за експертизи
 *
 *
 * @category  vendors
 * @package   expert
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class expert_Expert extends core_FieldSet {
    
    
    /**
     * Персистентно състояние
     */
    var $vals = array();     // Стойностите на променливите
    
    /**
     * Коя променлива на коя стъпка е установена
     */
    var $setInStep = array();
    
    
    /**
     * Дали променливата е получила стойност от диалог
     */
    var $fromDialog = array();
    
    
    /**
     * Коя е текущата стъпка
     */
    var $currentStep = 0;
    
    
    /**
     * Кой е последният изведен диалог
     */
    var $lastDialog = '';
    
    
    /**
     * Списък на изведените до сега диалози: стъпка => етикет
     */
    var $dialogs = array();
    
    
    /**
     * Списък на всички достоверни променливи
     */
    var $trusty = array();
    
    
    /**
     * URL за връщане след експертизата
     */
    var $RetUrl = NULL;     //
    
    /**
     * Създател - mvc обект
     */
    var $mvc;
    
    
    /**
     * Обща форма, в която са дефинициите на всички променливи
     */
    var $form;
    
    
    /**
     * Правила, грешки, предупреждения
     */
    var $knowledge = array();
    
    
    /**
     * Хронология на експертизата
     */
    var $log = array();
    
    
    /**
     * Дали експертната машина е включена
     */
    var $turnOn = TRUE;
    
    
    /**
     * Имаме междинен резултат (грешка, предупреждение, въпрос)
     */
    var $midRes = FALSE;
    
    
    /**
     * Сработило е поне едно правило
     */
    var $ruleOn = FALSE;
    
    
    /**
     * Функции, които могат да се използват в изразите на експертизата
     */
    var $functions = array(
        'val' => '$this->getValue',
        'sin' => 'sin',
        'asin' => 'asin',
        'abs' => 'abs',
        'getDbField' => '$this->getDbField',
        'sqrt' => 'sqrt',
        'pi' => 'pi',
        'cos' => 'cos',
        'round' => 'round',
        'count' => 'count',
        'isset' => '$this->isTrusty'
    );
    
    
    /**
     * Стилове за диалога на нормален екран
     */
    var $wideDialogStyle = ".formSection {height:360px;width:600px;} ";
    
    
    /**
     * Стилове на диалога за мобилен екран
     */
    var $narrowDialogStyle = ".formSection {max-width:600px;} ";
    
    
    /**
     * Инициализиране на обекта-експертиза
     */
    function init($params = array())
    {
        parent::init($params);
        
        $this->form = cls::get('core_Form', array('method' => 'POST'));
        
        // Брояч на знанията
        $this->kInd = 1;
        
        // Командни променливи, които очакваме от Request
        // Cmd - каква команда изпълняваме (ако липсва , значи командата е beggin )
        // Eid - идентификатор на експертизата (ако липсва, значи състоянието е празно)
        // RetUrl - адрес за връщане след експертизата
        
        // Зареждаме състоянието
        if($State = Request::get('State')) {
            $this->setState($State);
        }
        
        if(!$this->Cmd = Request::get('AjaxCmd')) {
            
            $this->Cmd = Request::get('Cmd');
            
            if(is_array($this->Cmd)) {
                if($this->Cmd['back']) {
                    $this->Cmd = 'back';
                } elseif($this->Cmd['cancel']) {
                    $this->Cmd = 'cancel';
                } elseif($this->Cmd['next']) {
                    $this->Cmd = 'next';
                } elseif($this->Cmd['default']) {
                    $this->Cmd = 'next';
                }
            }
            
            if(!$this->Cmd) {
                $this->Cmd = 'beggin';
            }
        }
        
        if($this->Cmd == 'beggin') {
            $this->setDefaults(array('ret_url' => toUrl(getRetUrl())));
            $this->reason[] = 'OK' . toUrl(getRetUrl());
        }
    }
    
    
    /**
     * Задава в междинния резултат връщане към RetUrl
     * Ако не е посочено
     */
    function setRedirect($url = NULL)
    {
        setIfNot($url, $this->getValue('ret_url'), $this->RetUrl, array('Index'));
        
        if(!$this->midRes) {
            $this->midRes = new stdClass();
        }
        $this->midRes->RetUrl = $url ;
    }
    
    
    /**
     * Задава типа на съобщението за редирект
     * 
     * @param enum $type - Типа на съобщението - success, notice, warning, error
     */
    function setRedirectMsgType($type='notice')
    {
        if(!$this->midRes) {
            $this->midRes = new stdClass();
        }
        
        $this->midRes->RedirectMsgType = $type;
    }

    
    /**
     * Връща типа на съобщението след редирект
     * 
     * @return string
     */
    function getRedirectMsgType()
    {
        if (!$this->midRes->RedirectMsgType) {
            
            return 'notice';
        }
        
        return $this->midRes->RedirectMsgType;
    }
    
    
    /**
     * Връща титлата за посочения вид диалог.
     */
    function getTitle($kRec)
    {
        if($kRec->title) {
            return $kRec->title;
        }
        
        if($this->titles[$kRec->element]) {
            return $this->titles[$kRec->element];
        }
        
        return tr(mb_convert_case($kRec->element, MB_CASE_TITLE, "UTF-8"));
    }
    
    
    /**
     * Задава шаблона за посочения вид диалог. Могат да се посочат и
     * блокове, които ако нямат промяна в съдържанието да изчезнат
     */
    function setLayout($tpl, $type = 'question')
    {
        $this->layouts[$type] = new ET($tpl);
    }
    
    
    /**
     * Връща посочения лейаут
     */
    function getLayout($type)
    {
        if($this->layouts[$type]) {
            return $this->layouts[$type];
        } else {
            return $this->layouts['default'];
        }
    }
    
    
    /**
     * Дефиниция на променлива. Ето някои атрибути
     * defaultExpression => израз - стойност по подразбиране
     * mandatory => задължително попълване от потребителя
     * fromRequest => в началото, ако присъства в Request - установява се
     */
    function DEF($vars, $type = 'varchar(65000)', $params = array(), $moreParams = array())
    {
        $vars = arr::make($vars, TRUE);
        
        foreach($vars as $name => $caption) {
            if($caption == $name) $this->trimPrefix($caption);
            $this->trimPrefix($name);
            $params = arr::combine(array(
                    'name' => $name,
                    'type' => $type,
                    'element' => 'def'),
                $params,
                $moreParams,
                array('caption' => $caption)
            );
            $this->setKnowledge($params);
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function CDEF($vars, $type = 'varchar(65000)', $cond = TRUE, $params = array(), $moreParams = array())
    {
        $vars = arr::make($vars, TRUE);
        
        foreach($vars as $name => $caption) {
            if($caption == $name) $this->trimPrefix($caption);
            $params = arr::combine(array(
                    'name' => $name,
                    'type' => $type,
                    'cond' => $cond,
                    'element' => 'cdef'),
                $params,
                $moreParams,
                array('caption' => $caption)
            );
            $this->trimPrefix($name);
            
            $this->setKnowledge($params);
        }
    }
    
    
    /**
     * Задава състоянието на експертизата
     * Входният параметър е сериализиран обект, съдържащ полетата
     */
    function setState($state)
    {
        if(EXPERT_SAVE_STATE_IN_CACHE) {
            
            $state = core_Cache::get(EXPERT_CACHE_TYPE, $state);
        } else {
            
            $Crypt = cls::get('core_Crypt');
            
            $key = Mode::getPermanentKey();
            
            $state = $Crypt->decodeVar($state, $key);
        }
        
        if(is_object($state)) {
            if(count($state->vars)) {
                foreach($state->vars as $name => $value) {
                    $this->vals[$name] = $value[0];
                    $this->setInStep[$name] = $value[1];
                    $this->fromDialog[$name] = $value[2];
                }
            }
            $this->currentStep = $state->currentStep;
            $this->lastDialog = $state->lastDialog;
            $this->dialogs = $state->dialogs;
        }
    }
    
    
    /**
     * Връща състоянието на експертизата
     */
    function getState()
    {
        $state = new stdClass();
        
        foreach($this->vals as $name => $value) {
            $state->vars[$name] = array('0' => $this->vals[$name], '1' => $this->setInStep[$name], '2' => $this->fromDialog[$name]);
        }
        
        $state->currentStep = $this->currentStep;
        $state->lastDialog = $this->lastDialog;
        $state->dialogs = $this->dialogs;
        
        if(EXPERT_SAVE_STATE_IN_CACHE) {
            
            $handler = core_Cache::set(EXPERT_CACHE_TYPE, NULL, $state, EXPERT_STATE_LIFETIME);
            
            return $handler;
        } else {
            
            $Crypt = cls::get('core_Crypt');
            
            $key = Mode::getPermanentKey();
            
            return $Crypt->encodeVar($state, $key);
        }
    }
    
    
    /**
     * Добавя правило
     */
    function RULE($var, $expr, $cond = TRUE)
    {
        $this->trimPrefix($var);
        $args = array('vars' => $var, 'expr' => $expr, 'cond' => $cond, 'element' => 'rule');
        $params = arr::combine($args, $params, $moreParams);
        $this->setKnowledge($params);
    }
    
    
    /**
     * Добавя опции за променлива
     */
    function OPTIONS($var, $expr, $cond = TRUE)
    {
        $this->trimPrefix($var);
        $args = array('vars' => $var, 'expr' => $expr, 'cond' => $cond, 'element' => 'options');
        $params = arr::combine($args, $params, $moreParams);
        $this->setKnowledge($params);
    }
    
    
    /**
     * Добавя предложения за променлива
     */
    function SUGGESTIONS($var, $expr, $cond = TRUE)
    {
        $this->trimPrefix($var);
        $args = array('vars' => $var, 'expr' => $expr, 'cond' => $cond, 'element' => 'suggestions');
        $params = arr::combine($args, $params, $moreParams);
        $this->setKnowledge($params);
    }
    
    
    /**
     * Добавя предположение за стойност на променлива
     */
    function ASSUME($var, $expr, $cond = TRUE)
    {
        $this->trimPrefix($var);
        $args = array('vars' => $var, 'expr' => $expr, 'cond' => $cond, 'element' => 'assume');
        $params = arr::combine($args, $params, $moreParams);
        $this->setKnowledge($params);
    }
    
    
    /**
     * Добавя грешка
     */
    function ERROR($msg, $cond, $params = array(), $moreParams = array())
    {
        $args = array('msg' => $msg, 'cond' => $cond, 'element' => 'error');
        $params = arr::combine($args, $params, $moreParams);
        $this->setKnowledge($params);
    }
    
    
    /**
     * Добавя предупреждение
     */
    function WARNING($msg, $cond, $params = array(), $moreParams = array())
    {
        $args = array('msg' => $msg, 'cond' => $cond, 'element' => 'warning');
        $params = arr::combine($args, arr::make($params, TRUE), arr::make($moreParams, TRUE));
        $this->setKnowledge($params);
    }
    
    
    /**
     * Добавя информация
     */
    function INFO($msg, $cond, $params = array(), $moreParams = array())
    {
        $args = array('msg' => $msg, 'cond' => $cond, 'element' => 'info');
        $params = arr::combine($args, arr::make($params, TRUE), arr::make($moreParams, TRUE));
        $this->setKnowledge($params);
    }
    
    
    /**
     * Добавя въпрос
     */
    function QUESTION($vars, $msg, $cond = TRUE, $params = array(), $moreParams = array())
    {
        $vars = $this->convertNames($vars);
        $args = array('vars' => $vars, 'msg' => $msg, 'cond' => $cond, 'element' => 'question');
        $params = arr::combine($args, arr::make($params, TRUE), arr::make($moreParams, TRUE));
        $this->setKnowledge($params);
    }
    
    
    /**
     * Задава знание за експертизата (правило, въпрос, ...)
     */
    function setKnowledge($params)
    {
        if(is_a($params['expr'], 'core_ET')) {
            $params['expr'] = '"' . str_replace('"', '\"', $params['expr']->getContent()) . '"';
        }
        setIfNot($params['label'], $params['element'] . '_' . $this->kInd++);
        $label = $params['label'];
        
        $this->knowledge[$label] = new stdClass();
        
        foreach($params as $id => $value) {;
            
            if($id) {
                $this->knowledge[$label]->{$id} = $value;
            }
        }
    }
    
    
    /**
     * Проверява дали посочената променлива има достоверна стойност
     */
    function isTrusty($name)
    {
        $this->trimPrefix($name);
        
        return isset($this->setInStep[$name]);
    }
    
    
    /**
     * Конвертира имена на променливи към масив
     */
    function convertNames($names)
    {
        $names = arr::make($names, TRUE);
        
        foreach($names as $key => $val)
        {
            $this->trimPrefix($key);
            $this->trimPrefix($val);
            $res[$key] = $val;
        }
        
        return $res;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function areTrusty($names)
    {
        expect($names);
        
        foreach($names as $name) {
            if(!$this->isTrusty($name)) return FALSE;
        }
        
        return TRUE;
    }
    
    
    /**
     * Показва дали диалогът е сработвал на стъпка по-малка или равна на текущата
     */
    function isDialogUsed($label)
    {
        
        return $this->isTrusty($label);
    }
    
    
    /**
     * Дали променливата има дефолт стойност
     */
    function haveDefault($name)
    {
        $this->trimPrefix($name);
        
        return $this->fromDialog[$name] || $this->isTrusty($name . '_ASSUME_') ;
    }
    
    
    /**
     * Дали променливата има опции
     */
    function haveOptions($name)
    {
        $this->trimPrefix($name);
        
        return $this->isTrusty($name . '_OPTIONS_') ;
    }
    
    
    /**
     * Връща опциите за дадената променлива
     */
    function getOptions($name)
    {
        $this->trimPrefix($name);
        
        return $this->vals[$name . '_OPTIONS_'];
    }
    
    
    /**
     * Дали променливата има предложения
     */
    function haveSuggestions($name)
    {
        $this->trimPrefix($name);
        
        return $this->isTrusty($name . '_SUGGESTIONS_') ;
    }
    
    
    /**
     * Връща предложенията за дадената променлива
     */
    function getSuggestions($name)
    {
        $this->trimPrefix($name);
        
        return $this->vals[$name . '_SUGGESTIONS_'];
    }
    
    
    /**
     * Връща стойността на променливата, без значение дали е достоверна
     * Ако променливата няма стойност, оба
     */
    function getValue($name)
    {
        $this->trimPrefix($name);
        
        if(!$this->vals[$name] && !$this->fromDialog[$name] && $this->vals[$name . '_ASSUME_']) {
            
            return $this->vals[$name . '_ASSUME_'];
        }
        
        return $this->vals[$name];
    }
    
    
    /**
     * Задава стойност на променлива, без значение дали вече има стойност
     */
    function setValue($name, $value)
    {
        $this->trimPrefix($name);
        
        $this->vals[$name] = $value;
        
        $this->setInStep[$name] = $this->currentStep;
        
        $this->reason[] = "{$name}=  " . type_Varchar::escape($value) . " [" . $this->currentStep . "]";
    }
    
    
    /**
     * Задава стойности по подразбиране
     */
    function setDefaults($list)
    {
        $arr = arr::make($list, TRUE);
        
        foreach($arr as $name => $value) {
            $this->trimPrefix($name);
            
            if(!isset($this->vals[$name])) {
                $this->vals[$name] = $value;
                $this->fromDialog[$name] = 1000;
            }
        }
    }
    
    
    /**
     * Връща запис с посочените променливи
     */
    function getRec($vars)
    {
        $arr = arr::make($vars);
        
        foreach($arr as $name)
        {
            $this->trimPrefix($name);
            
            if(isset($this->vals[$name])) {
                $rec->{$name} = $this->vals[$name];
            }
        }
        
        return $rec;
    }
    
    
    /**
     * Връща резултата от експертизата
     */
    function getResult()
    {
        $debug = '';
        
        if (isDebug()) {
            $debug = "<hr style='margin-top:10px;'><small><a href='#' onclick=\"toggleDisplay('expDebug');\">" . tr("Дебъг") . "</a><div id='expDebug' style='padding-left:15px; display:none;'>";
        
            if(count($this->reason)) {
                foreach($this->reason as $l) {
                    $debug .= "<li> $l</li>";
                }
            }
            
            $debug .= "<div></small>";
        }
        
        if(Request::get('AjaxCmd')) {
            
            if($this->midRes->alert) {
                $res->alert = $this->midRes->alert;
            }
            
            if($this->midRes->RetUrl) {
                $res->redirect = toURL($this->midRes->RetUrl) ;
            } else {
                
                $form = $this->midRes->form;
                
                $res->title = $form->renderTitle();
                $res->title = $res->title->getContent();
                
                if(count($form->toolbar->buttons)) {
                    foreach($form->toolbar->buttons as $btn) {
                        $v = $btn->cmd;
                        $res->btn->{$v} = 1;
                    }
                }
                
                $form->FNC('Eid', 'varchar', 'input=hidden');
                
                $form->setHidden('State', $this->getState());
                
                $form->layout = new ET(
                    "<!--ET_BEGIN FORM_STYLE--><style>[#FORM_STYLE#]\n</style><!--ET_END FORM_STYLE-->" .
                    "<form style='margin:0px;' id='expertForm' [#FORM_ATTR#] <!--ET_BEGIN ON_SUBMIT-->onSubmit=\"[#ON_SUBMIT#]\"<!--ET_END ON_SUBMIT-->>\n" .
                    "<!--ET_BEGIN FORM_ERROR--><div class=\"formError\">[#FORM_ERROR#]</div><!--ET_END FORM_ERROR-->" .
                    "<!--ET_BEGIN FORM_INFO--><div class=\"formInfo\">[#FORM_INFO#]</div><!--ET_END FORM_INFO-->" .
                    "<!--ET_BEGIN FORM_FIELDS--><div class=\"formFields\">[#FORM_FIELDS#]</div><!--ET_END FORM_FIELDS-->" .
                    "<!--ET_BEGIN FORM_HIDDEN-->[#FORM_HIDDEN#]<!--ET_END FORM_HIDDEN--> {$debug}" .
                    "</form>\n"
                );
                
                $res->msg = $form->renderHtml();
                $res->msg->append($this->midRes->afterForm);
                $res->msg->prepend($this->midRes->beforeForm);
                
                $js = $res->msg->getArray('JS');
                
                if (!empty($js)) {
                    foreach($js as $file) {
                        if(!$used[$file]) {
                            $res->scripts[] = sbf($file, '', TRUE);
                            $used[$file] = 1;
                        }
                    }
                }
                
                foreach($this->vals as $k => $v) {
                    // $res->msg->append("<li> $k = $v");
                }
                
                // $res->msg->append(Debug::getLog());
                
                $res->msg = $res->msg->getContent();
            }
            
            $res = json_encode($res);
            
            header('Content-type: text/json');
            
            echo $res;
            
            die;
        }
        
        if($this->midRes->RetUrl) {
            
            return new Redirect($this->midRes->RetUrl, $this->midRes->alert, $this->getRedirectMsgType());
        }
        
        if($this->midRes->form) {
            $form = $this->midRes->form;
            
            $form->FNC('Eid', 'varchar', 'input=hidden');
            
            $form->setHidden('State', $this->getState());
            
            $tpl = $form->renderHtml();
            $tpl->append($this->midRes->afterForm);
            $tpl->prepend($this->midRes->beforeForm);
            
            if(Mode::is('screenMode', 'narrow')) {
                $tpl->appendOnce($this->narrowDialogStyle, 'STYLES');
            } else {
                $tpl->appendOnce($this->wideDialogStyle, 'STYLES');
            }
            
            $tpl->append($debug, 'FORM_FIELDS');
            
            return $tpl;
        }

        // Няма междинен резултат ...
        error('Няма междинен резултат', $this);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function setTrusty()
    {
        $this->trusty = array();
        
        foreach($this->setInStep as $name => $step) {
            if($step < $this->currentStep) {
                $this->trusty[$name] = TRUE;
            } else {
                unset($this->setInStep[$name]);
            }
        }
    }
    
    
    /**
     * Прави опит да намери указаната цел
     */
    function solve($goal)
    {   
        // Ако командата е cancel, задаваме редирект и връщаме истина.
        if($this->Cmd == 'cancel') {
            $this->setRedirect();
            
            return 'DIALOG';
        }
        
        // Превръща целта в масив
        $goal = $this->convertNames($goal);
        
        if($this->lastDialog) {
            
            //След грешка не е възможно продължение напред
            expect($kRec->element != 'error' || $this->cmd != 'next');
            
            // Нова текуща стъпка
            
            $this->currentStep++;
            
            // Значи имаме въпрос, предупреждение или грешка
            // в променливата $this->lastDialog е записано точно какъв е типа на диалога
            // Тук възможните команди са: cancel, next, back
            
            $kRec = $this->knowledge[$this->lastDialog];
            
            $this->dialogs[$this->currentStep] = $kRec->element;
            
            // Трябва да имаме знание, което да отговаря на последния диалог
            expect($kRec);
            
            if($kRec->element == 'question') {
                
                $form = $this->getQuestionForm($kRec);
                
                $vals = $form->input();
                
                // Ако имаме грешки на входа и текущата команда не е връщане, показваме формата
                if($form->gotErrors() && !($this->Cmd == 'back')) {
                    if(!is_object($this->midRes)) {
                        $this->midRes = new stdClass();
                    }
                    $this->midRes->form = $form;
                    $this->currentStep--;
                    Debug::log("CurrentStep on error: $this->currentStep");
                    
                    return 'DIALOG';
                }
                
                $vars = arr::make($kRec->vars);
                
                // Ако няма грешки, вкарваме получените променливи
                if(!$form->gotErrors()) {
                    // Вкарваме променливите в състоянието
                    $vals = arr::make($vals);
                    
                    foreach($vars as $name) {
                        
                        $value = $vals[$name];
                        
                        if($value === NULL && $form->fields[$name]->notNull) {
                            $value = $form->fields[$name]->value ? $form->fields[$name]->value : $form->fields[$name]->type->defVal();
                            if($value === NULL) {
                                $value = '';
                            }
                        }

                        if($value !== NULL) {
                            $this->setValue($name, $value);
                            $this->fromDialog[$name] = TRUE;
                        } else {
                            $this->vals[$name] = NULL;
                            unset($this->fromDialog[$name]);
                        }
                    }
                }
            }
            
            // Записваме, че този диалог е сработил
            $this->setValue($this->lastDialog, TRUE);
            
            if($this->Cmd == 'back') {
                $this->currentStep -= 2;
                
                while($this->dialogs[$this->currentStep + 1] == 'warning') {
                    $this->currentStep--;
                }
                
                if($this->currentStep < 0) $this->currentStep = 0;
                
                foreach($this->setInStep as $name => $step) {
                    if($step > $this->currentStep) {
                        unset($this->setInStep[$name]);
                    }
                }
            }
            
            // Очакваме текущата стъпка да е по-голяма от 0
            expect($this->currentStep >= 0);
        } else {
            
            // Щом нямаме предишен диалог, значи сме в началото
            expect($this->Cmd == 'beggin', $this);
            
            // Очакваме текуща стъпка == 0
            expect($this->currentStep == 0);
            
            // Опитваме се да вкараме стойностите на променливите
            // които са дефинирани като fromRequest, и в момента нямат
            // достоверни стойности 
            foreach($this->knowledge as $id => $kRec) {
                if($kRec->fromRequest && (Request::get($kRec->name, $kRec->type) !== NULL)) {
                    $this->setValue($kRec->name, Request::get($kRec->name, $kRec->type));
                }
            }
        }
        
        $this->reason[] = "CurrentStep: $this->currentStep";
        
        // Докато не получим междинен резултат и се е изпълнило поне едно правило,
        // циклим по правилата, предупрежденията и грешките
        do {
            $this->ruleOn = FALSE;
            
            foreach($this->knowledge as $id => $kRec) {
                if(in_array($kRec->element, array('rule', 'error', 'warning', 'info', 'suggestions', 'options', 'assume')) && !$this->midRes) {
                    // Опит да сработи правило, предупреждение или грешка
                    $method = 'do' . $kRec->element;
                    $this->{$method}($kRec);
                }
            }
        } while(!$this->midRes && $this->ruleOn);
        
        // Ако не е достигната целта, и нямаме междинен резултат (грешка, предупреждение)
        // Проверяваме дали няма подходящ въпрос, който да зададем
        if(!$this->areTrusty($goal) && !$this->midRes) {
            foreach($this->knowledge as $id => $kRec) {
                if($kRec->element == 'question') {
                    // Опит да сработи въпрос
                    $method = 'do' . $kRec->element;
                    $this->{$method}($kRec);
                    
                    if($this->midRes) break;
                }
            }
        }
        
        // Изпразваме от съдържание въпросите и знанията,
        // за да можем да запишем следващата порция на чисто
        
        if($this->areTrusty($goal) && !$this->midRes) return 'SUCCESS';
        
        // Ако целта е достигната или имаме междинен резултат, връщаме TRUE
        if($this->midRes) return 'DIALOG';
        
        // Връщаме FALSE, защото експертизата се е провалила
        return 'FAIL';
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function doError($kRec)
    {
        // Ако това предупреждение вече е сработвало - нищо не правим
        if($this->isDialogUsed($kRec->label)) return;
        
        // Достоверно ли е условието на това предупреждение?
        if(!$this->calcExpr($kRec->cond, $res)) return;
        
        // Истина ли е условието на предупреждението?
        if(!$res) return;
        
        // Указваме етикета на последния диалог
        $this->lastDialog = $kRec->label;
        
        // Вземаме информацията
        $info = $this->getInfo($kRec->element, $kRec->msg, $kRec->layout);
        
        $form = clone($this->form);
        
        if($layout = $this->getLayout('error')) {
            $form->layout = $layout;
        }
        
        $form->info = "<div class='formError'>$info</info>";
        
        $form->title = "|*<img width=32 height=32 alt='' align=absmiddle  src=" . sbf('img/32/error.png') . "> " . $this->getTitle($kRec);
        $form->method = 'POST';
        
        $this->setButtons($form, $this->currentStep >= 1, FALSE);
        
        // Междинният резултат е  грешката
        if(!$this->midRes) {
            $this->midRes = new stdClass();
        }
        $this->midRes->form = $form;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function doWarning($kRec)
    {
        // Ако това предупреждение вече е сработвало - нищо не правим
        if($this->isDialogUsed($kRec->label)) return;
        
        // Достоверно ли е условието на това предупреждение?
        if(!$this->calcExpr($kRec->cond, $res)) return;
        
        // Истина ли е условието на предупреждението?
        if(!$res) return;
        
        // Указваме етикета на последния диалог
        $this->lastDialog = $kRec->label;
        
        // Вземаме информацията
        $info = $this->getInfo($kRec->element, $kRec->msg, $kRec->layout);
        
        $form = clone($this->form);
        
        if($layout = $this->getLayout('warning')) {
            $form->layout = $layout;
        }
        
        $form->title = "|*<img width=32 height=32 alt='' align=absmiddle  src=" . sbf('img/32/warning.png', "'", TRUE) . "> " . $this->getTitle($kRec);
        
        $form->method = 'POST';
        
        $form->info = $info;
        
        $this->setButtons($form, $this->currentStep >= 1);
        
        // Междинният резултат е предупреждението
        if(!$this->midRes) {
            $this->midRes = new stdClass();
        }
        $this->midRes->form = $form;
    }
    
    
    /**
     * Изпълнява 'Info' знанията
     */
    function doInfo($kRec)
    {
        // Ако това предупреждение вече е сработвало - нищо не правим
        if($this->isDialogUsed($kRec->label)) return;
        
        // Достоверно ли е условието на това предупреждение?
        if(!$this->calcExpr($kRec->cond, $res)) return;
        
        // Истина ли е условието на предупреждението?
        if(!$res) return;
        
        // Указваме етикета на последния диалог
        $this->lastDialog = $kRec->label;
        
        // Вземаме информацията
        $info = $this->getInfo($kRec->element, $kRec->msg, $kRec->layout);
        
        $form = clone($this->form);
        
        if($layout = $this->getLayout('info')) {
            $form->layout = $layout;
        }
        
        $form->title = "|*<img width=32 height=32 alt='' align=absmiddle  src=" . sbf('img/32/info.png', "'", TRUE) . "> " . $this->getTitle($kRec);
        $form->method = 'POST';
        
        $form->info = $info;
        
        Debug::log("CurrentStep in QUESTION: $this->currentStep");
        
        $this->setButtons($form, $this->currentStep >= 1);
        
        // Междинният резултат е информацията
        if(!$this->midRes) {
            $this->midRes = new stdClass();
        }
        $this->midRes->form = $form;
    }
    
    
    /**
     * Задава въпрос
     */
    function doQuestion($kRec)
    {
        // Ако този въпрос вече е стаботвал - нищо не правим
        if($this->isDialogUsed($kRec->label)) return;
        
        // Ако променливите на този въпрос са достоверни - нущо не правим
        if($this->areTrusty($kRec->vars)) return;
        
        // Достоверно ли е условието на този въпрос?
        if(!$this->calcExpr($kRec->cond, $res)) return;
        
        // Истина ли е условието на предупреждението?
        if(!$res) return;
        
        // Указваме етикета на последния диалог
        $this->lastDialog = $kRec->label;
        
        // Междинният резултат е въпроса
        if(!$this->midRes) {
            $this->midRes = new stdClass();
        }
        $this->midRes->form = $this->getQuestionForm($kRec);
    }
    
    
    /**
     * Опитваме се да приложим правило
     */
    function doRule($kRec)
    {
        // Ако променливата на това правило е достоверна, то не се прилага
        if($this->isTrusty($kRec->vars)) return;
        
        // Достоверно ли е условието на това правило?
        if(!$this->calcExpr($kRec->cond, $res)) return;
        
        // Истина ли е условието?
        if(!$res) return;
        
        // Достоверно ли е заключението/стойността на това правило?
        if(!$this->calcExpr($kRec->expr, $res)) return;
        
        // Задаваме стойността на променливата        
        $this->setValue($kRec->vars, $res);
        
        // Записваме логови съобщения за проследяване на експертизата
        $logMsg = $kRec->vars . "=" . $res . " (" . $kRec->expr . "), TRUE = " . $kRec->cond;
        Debug::log($logMsg);
        $this->log[] = $logMsg;
        
        // Вдигаме флага, че имаме изпълнение на правило
        $this->ruleOn = TRUE;
    }
    
    
    /**
     * Опитваме се да намерим предположение за стойността на дадена променлива
     */
    function doAssume($kRec)
    {
        // Променлива на предположението
        $var = $kRec->vars . "_ASSUME_";
        
        // Ако променливата представляваща опциите е достоверна, то не се прилага
        if($this->isTrusty($var)) return;
        
        // Достоверно ли е условието на тези опции?
        if(!$this->calcExpr($kRec->cond, $res)) return;
        
        // Истина ли е условието?
        if(!$res) return;
        
        // Достоверно ли е заключението/стойността на това предположение?
        if(!$this->calcExpr($kRec->expr, $res)) return;
        
        // Задаваме стойността на променливата        
        $this->setValue($var, $res);
        
        $logMsg = $var . "=" . $res . " (" . $kRec->expr . "), TRUE = " . $kRec->cond;
        
        Debug::log($logMsg);
        
        $this->log[] = $logMsg;
        
        $this->ruleOn = TRUE;
    }
    
    
    /**
     * Опитваме се да намерим опции за стойността на дадена променлива
     */
    function doSuggestions($kRec)
    {
        return $this->doOptions($kRec, '_SUGGESTIONS_');
    }
    
    
    /**
     * Опитваме се да намерим опции за стойността на дадена променлива
     */
    function doOptions($kRec, $suffix = "_OPTIONS_")
    {
        // Променлива на опциите
        $var = $kRec->vars . $suffix;
        
        // Ако променливата представляваща опциите е достоверна, то не се прилага
        if($this->isTrusty($var)) return;
        
        // Достоверно ли е условието на тези опции?
        if(!$this->calcExpr($kRec->cond, $res)) return;
        
        // Истина ли е условието?
        if(!$res) return;
        
        // Ако заключението е стринг, то той се третира като израз
        if(is_string($kRec->expr)) {
            
            // Достоверно ли е заключението/стойността на тези опции?
            if(!$this->calcExpr($kRec->expr, $res)) return;
            
            $opt = $kRec->expr;
        } elseif(is_array($kRec->expr)) {
            
            $res = $kRec->expr;
            
            $opt = 'Array';
        } else {
            // Wrong expert
            error('Wrong expert', $kRec);
        }
        
        $res = arr::make($res);
        
        $this->setValue($var, $res);
        
        $logMsg = $var . "=" . $opt . " [" . count($res) . "], TRUE = " . $kRec->cond;
        
        Debug::log($logMsg);
        
        $this->log[] = $logMsg;
        
        $this->ruleOn = TRUE;
    }
    
    
    /**
     * Връща формата на въпроса
     */
    function getQuestionForm($kRecIn)
    {
        // Вземаме информацията
        $form = clone($this->form);
        
        $kRec = $this->calcExprAttr($kRecIn);
        
        if($layout = $this->getLayout('question')) {
            $form->layout = $layout;
        }
        
        $form->info = $this->getInfo($kRec->element, $kRec->msg, $kRec->layout);
        
        // Задаваме полетата, които ще се показват и техните дефолти
        foreach($kRec->vars as $var) {
            
            $form->showFields[$var] = $var;
            
            $fieldIsSet = FALSE;
            
            // Опит да се зададе полето от CDEF (условните дефиниции)
            if(!$fieldIsSet) {
                foreach($this->knowledge as $id => $skRec) {
                    if($skRec->element == 'cdef') {
                        
                        $name = $this->trimPrefix($skRec->name);
                        
                        if($name == $var) {
                            
                            if(!$this->calcExpr($skRec->cond, $res)) continue;
                            
                            if(!$res) continue;
                            
                            unset($skRec->element);
                            $sskRec = $this->calcExprAttr($skRec);
                            $form->FNC($name, $sskRec->type,  $sskRec);
                            
                            if ($sskRec->value) {
                                $form->setDefault($name, $sskRec->value);
                            }
                            $fieldIsSet = TRUE;
                            break;
                        }
                    }
                }
            }
            
            // Опит да се зададе полето от DEF (дефинициите)
            if(!$fieldIsSet) {
                foreach($this->knowledge as $id => $skRec) {
                    if($skRec->element == 'def') {
                        $name = $this->trimPrefix($skRec->name);
                        
                        if($name == $var) {
                            unset($skRec->element);
                            $sskRec = $this->calcExprAttr($skRec);
                            $form->FNC($name, $skRec->type, $sskRec);
                            
                            if ($sskRec->value) {
                                $form->setDefault($name, $sskRec->value);
                            }
                            $fieldIsSet = TRUE;
                            break;
                        }
                    }
                }
            }
            
            // Опит да се вземе полето от MVC модела
            if(!$fieldIsSet) {
                if($this->mvc->fields[$var]) {
                    $form->fields[$var] = clone($this->mvc->fields[$var]);
                    $fieldIsSet = TRUE;
                }
            }
            
            // Полето се задава като стрингово
            if(!$fieldIsSet) {
                $form->FNC($var, 'varchar(65000)');
            }
            
            if($this->haveDefault($var)) {
                $form->setDefault($var, $this->getValue($var));
            }
            
            if($this->haveOptions($var)) {
                $form->setOptions($var, $this->getOptions($var));
            }
            
            if($this->haveSuggestions($var)) {
                $form->setSuggestions($var, $this->getSuggestions($var));
            }
            
            if($this->isTrusty($var)) {
                $form->setOptions($var, array($this->getValue($var) => $this->getValue($var)));
            }
        }
        
        $form->title = "|*<img width=32 height=32 alt='' align=absmiddle  src=" . sbf('img/32/question.png', "'", TRUE) . "> " . $this->getTitle($kRec);
        $form->method = 'POST';
        
        $this->setButtons($form, $this->currentStep >= 1);
        
        return $form;
    }
    
    
    /**
     * Изчислява стойностите на всички атрибути, които могат да са и израз и стрингова константа
     */
    function calcExprAttr($kRec)
    {
        $rec = new stdClass();
        
        foreach((array) $kRec as $key => $value) {
            
            if(!in_array($key, array('expr', 'vars', 'name', 'cond')) && $value{0} == '=') {
                $value = substr($value, 1);
                $res   = NULL;
                
                if(!$this->calcExpr($value, $res)) {
                    // Не може да се сметне
                    error('Не може да се сметне', $value);
                }

                $value = $res;
            }
            $rec->{$key} = $value;
        }
        
        return $rec;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getInfo($type, $msg, $layout)
    {
        if(is_a($msg, 'ET')) $msg = $msg->getContent();
        
        if($msg{0} == '=') {
            $msg = substr($msg, 1);
            
            if(!$this->calcExpr($msg, $res)) {
                // Не може да се калкулира
                error('Не може да се калкулира', $msg);
            }
            
            return $res;
        }
        
        return "<h4>" . $msg . "</h4>";
        
        // TODO
        
        // Генерираме шаблона за предупреждението предупреждение
        $layout = $this->smartConvert($tpl);
        setIfNot($tpl, $layout, $this->smartConvert($this->getLayout($type)), new ET());
        
        if(is_string($msg)) {
            $data = array('message' => $msg);
        } else {
            $data = arr::make($msg);
        }
        
        // Поставяме съобщението в шаблона
        foreach($data as $place => $str) {
            $str = $this->smartConvert($str);
            $tpl->append($str, $place);
        }
        
        return $tpl;
    }
    
    
    /**
     * Добавя указаните бутони към формата
     */
    function setButtons($form, $back, $next = TRUE, $cancel = TRUE)
    {
        $form->toolbar = cls::get('core_Toolbar');
        
        if(Mode::is('screenMode', 'narrow')) {
            if($back) {
                $form->toolbar->addSbBtn('« Връщане', 'back', 'style=margin-right:10px;,class=noicon');
            } else {
                $form->toolbar->addSbBtn('« Връщане', 'back', 'disabled,style=margin-right:10px;,class=noicon');
            }
            
            if($next) {
                $form->toolbar->addSbBtn('Продължение »', 'next', 'style=margin-right:10px;,class=noicon');
            } else {
                $form->toolbar->addSbBtn('Продължение »', 'next', 'disabled,style=margin-right:10px;,class=noicon');
            }
            
            if($cancel) {
                $form->toolbar->addSbBtn('Отказ', 'cancel', 'class=noicon');
            } else {
                $form->toolbar->addSbBtn('Отказ', 'cancel', 'disabled,class=noicon');
            }
        } else {
            if($next) {
                $form->toolbar->addSbBtn('Продължение »', 'next', 'style=float:right; margin-left:10px;,class=noicon');
            }
            
            if($back) {
                $form->toolbar->addSbBtn('« Връщане', 'back', 'style=float:right,class=noicon');
            }
            
            if($cancel) {
                $form->toolbar->addSbBtn('Отказ', 'cancel', 'class=noicon');
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function smartConvert($msg)
    {
        
        return $msg;
    }
    
    
    /**
     * Премахва префикса преди името на променливата и го връща като резултат
     */
    function trimPrefix(&$name)
    {
        if(!strlen($name)) {
            // Липсващо име на променлива
            error('Липсващо име на променлива', $name);
        }
        
        $prefix = $name{0};
        
        if($prefix == '#') {
            $name = substr($name, 1);
        } else {
            expect(str::isLetter($prefix), $prefix, $this, $name);
        }
        
        return $name;
    }
    
    
    /**
     * Изчислява израз. Стойността се записва в &$result
     * Връща TRUE при достоверно изчисление и FALSE
     * ако в израза участват не-достоверни променливи
     */
    function calcExpr($expr, &$result)
    {
        // Ако израза не е стринг, тогава стойността му е самия израз
        if(!is_string($expr)) {
            $result = $expr;
            
            return TRUE;
        }
        
        $expr1 = $this->expr2php($expr, $usedVars);
        
        if($expr1 === FALSE) error("Некоректен израз", $expr);
        
        if($usedVars && !$this->areTrusty($usedVars)) return FALSE;
        
        $expr1 = 'return ' . $expr1 . ';';
        
        if(!@eval('return TRUE;' . $expr1)) {
            $this->log[] = 'Syntax error: ' . $expr1 ;
            // Некоректен израз
            error('Некоректен израз', $expr1);
        }

        $result = eval($expr1);
        
        return TRUE;
    }
    
    
    /**
     * Поставя на мястото на променливите, започващи със '#'
     * имена на променливи от $this->vals
     */
    function expr2php($expr, &$usedVars)
    {
        
        // В какви части на израза може да сме?
        // Променлива - започва с # и съдържа само латински букви, цифри и _
        // Функция - започва с буква и съдържа само латински букви, цифри и _
        // Стринг2 - започва с ", има произволни символи, и завършва на ", но не и такава, която се предхожда от  \
        // Стринг1 - започва с ', има произволни символи, и завършва на ", но не и такава, която се предхожда от  \
        // Друго - всякакво друго състояние
        
        $state = 'other';
        
        $len = mb_strlen($expr);
        $lastChar = '';
        $start = NULL;
        
        $usedVars = array();
        
        for($i = 0; $i <= $len; $i++) {
            
            // Вземаме поредния символ
            $c = mb_substr($expr, $i, 1);
            
            // Определяме дали със символът може да започва функция
            $fic = str::isLetter($c);;
            
            // Определяме дали символът участва в идентификатор
            $ic = $fic || str::isDigit($c);
            
            switch($state) {
                case 'other' :
                    
                    // Ако очакваме следващия не-празен символ дае или да не е 
                    // отваряща скоба - правим проверка
                    if($c > ' ' && $bc) {
                        if($bc == 'expect' && $c != '(') {
                            // Липсва отваряща скоба
                            error('Липсва отваряща скоба', $c);
                        }
                        
                        if($bc == 'noExpect' && $c == '(') {
                            // Неочаквана отваряща скоба
                            error('Неочаквана отваряща скоба', $expr, $res);
                        }

                        $bc = FALSE;
                    }
                    
                    if($c == '#') {
                        $state = 'var';
                        $start = mb_strlen($res);
                    } elseif ($fic) {
                        $state = 'func';
                        $start = mb_strlen($res);
                    } elseif ($c == '"') {
                        $state = 'str2';
                    } elseif ($c == "'") {
                        $state = 'str1';
                    } elseif ($c == '$') {
                        error("В израза не може да се използва символа $");
                    }
                    
                    break;
                case 'str1' :
                    if($c == "'" && $lastChar != "\\") {
                        $state = 'other';
                    }
                    break;
                case 'str2' :
                    
                    if($c == '"' && $lastChar != "\\") {
                        $state = 'other';
                    }
                    
                    if($c == '$' && $lastChar != "\\") {
                        $res .= "\\";
                    }
                    break;
                case 'func' :
                    if(!$ic) {
                        $userFuncName = mb_strtolower(mb_substr($res, $start));
                        
                        if(in_array($userFuncName, array('true', 'false', 'null'))) {
                            $state = 'other';
                            break;
                        }
                        
                        if(!function_exists($userFuncName)) {
                            $intFuncName = $this->functions[$userFuncName];
                        } else {
                            $intFuncName = $userFuncName;
                        }
                        
                        if(!$intFuncName) {
                            
                            error('Липсваща функция', $intFuncName, $userFuncName, $this);
                            
                            return FALSE;     // Липсваща функция
                        }
                        
                        $res = mb_substr($res, 0, $start) . $intFuncName;
                        
                        $state = 'other';
                        
                        if($c != '(') {
                            $bc = 'expect';
                        }
                    }
                    break;
                case 'var' :
                    if(!$ic) {
                        
                        $var = mb_substr($res, $start + 1);
                        
                        // Променливата задължително трябва да има поне 1 символ
                        if(!strlen($var)) {
                            expect($var, 'minLen');
                            
                            return FALSE;
                        }
                        
                        if($state == 'var') $usedVars[] = $var;
                        
                        $res = mb_substr($res, 0, $start) . "\$this->vals['" . $var . "']";
                        
                        $state = 'other';
                        
                        if($c == ' ') {
                            $bc = 'noExpect';
                        }
                    }
                    
                    break;
            }
            
            $res .= $c;
            $lastChar = $c;
        }
        
        return $res;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function enableAjax(&$tpl)
    {
        jqueryui_Ui::enable($tpl);
        
        $tpl->push("expert/ajaxExpert.js", "JS");
        $dialog = new ET("<div id=\"expertDialog\"  title=\"\" style='display:none;'><p>[#FORM#]</p></div>");
        
        $form = cls::get('core_Form');
        $form->FNC('date', 'date', 'input');
        $form->FNC('string', 'varchar', 'input');
        
        $dialog->append($form->renderHtml(), 'FORM');
        
        $tpl->appendOnce("<div id=\"ajaxLoader\" style=\"position:absolute;top:10%;left:10%;display:none;padding:100px;background:url(" .
            sbf('img/ajax-loader.gif', '') . ") no-repeat 2px;\"></div>", "PAGE_CONTENT");
        $tpl->appendOnce($dialog, "PAGE_CONTENT");
        jquery_Jquery::run($tpl, "$('#expertDialog').dialog({autoOpen: false,height: 400,width: 600,modal: true});");
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function getLink($title, $url, $attr = array())
    {
        $attr = arr::make($attr, TRUE);
        $data->AjaxCmd = 'beggin';
        $data->Ajax = 'On';
        
        $url = toUrl($url);
        
        $data = json_encode($data);
        
        $attr['onclick'] = "expEngine(" . $data . ", '{$url}'); return false;";
        $attr['href'] = '?#';
        
        $a = ht::createElement('a', $attr, $title);
        
        return $a;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function getButton($title, $url, $attr = array())
    {
        $attr = arr::make($attr, TRUE);
        $data->AjaxCmd = 'beggin';
        $data->Ajax = 'On';
        
        $url = toUrl($url);
        
        $data = json_encode($data);
        
        $attr['onclick'] = "expEngine(" . $data . ", '{$url}'); return false;";
        $attr['type'] = 'button';
        $attr['value'] = $title;
        
        $a = ht::createElement('input', $attr);
        
        return $a;
    }
}
