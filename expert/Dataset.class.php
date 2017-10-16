<?php


/**
 * Клас 'expert_Dataset'
 *
 * Клас за експертни данни
 *
 *
 * @category  vendors
 * @package   expert
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class expert_Dataset extends core_BaseClass {
    
    
    /**
     * Стойности на променливите
     */
    public $vars = array();
    

    /**
     * Достоверности на променливите
     */
    public $trusts = array();
    

    /**
     * Масив със всички праила за сетване на променливи
     */
    public $rules = array();


    /**
     * Задава стойност на променлива
     */
    public function addRule($name, $expr, $cond = NULL, $priority = NULL)
    {
        // Нормализация на параметрите
        $name = trim($name);
        $cond = trim($cond);
        $expr = trim($expr);
        if($name{0} == '$') {
            $name = substr($name, 1);
        }

        $id = substr(md5($name . $expr . $cond . $priority), 0, 8);

        $rule = (object) array('name' => $name, 'expr' => $expr, 'cond' => $cond, 'state' => 'pending', 'order' => count($this->rules[$name])+1);
        
        if($priority) {
            $rule->priority = $priority;
        }

        $rule->exprVars = $this->extractVars($expr);

        $rule->condVars = $this->extractVars($cond);

        // Не може правило за дадена променлива да зависи от нея
        expect(!$rule->condVars[$rule->name] && !$rule->exprVars[$rule->name]);

        
        if(isset($this->rules[$name][$id])) { 
            $this->log[] = "<br>Warning: Дублиране на правило \${$name} = {$expr} ({$cond}";
        }

        $this->rules[$name][$id] = $rule;
    }
    
    
    /**
     * Мегически метод, който се извиква, ако обекта се използва като функция
     */
    public function __invoke($name, $expr, $cond = NULL, $priority = NULL)
    {
        static $files;

        $stack = debug_backtrace();
 
        if(!$files[$stack[0]['file']]) {
            $files[$stack[0]['file']] = explode("\n", file_get_Contents($stack[0]['file']));
        }
 
        $line = trim($files[$stack[0]['file']][$stack[0]['line']-1]);

        if(strpos($line, ', "')) {
            $this->log[] = "<br>Warning: Възможен проблем с двойни кавички в правилото <b>$line</b>";
        }

        $this->addRule($name, $expr, $cond, $priority);
    }


    /**
     * Връща масив с ключове и стойности - всички променливи, които се срещат в израза
     * Променливире започват с '$' и имат само латински букви.
     */
    private function extractVars($expr)
    {
        $res = array();
        $matches = array();

        $ptr = '/\$([a-z][a-z0-9_]{0,})/i';

        preg_match_all($ptr, $expr, $matches);
        
        foreach($matches[1] as $name) {
            $res[$name] = $name;
        }

        return $res;
    }


    /**
     * Задава стойност на посочената променлива
     */
    private function setVar($var, $value, $trust = 0.6, $log = '')
    {
        if(!strpos($var, '[]')) {
            $this->vars[$var] = $value;
            $this->trusts[$var] = $trust;
        } else {  
            expect(substr($var, -2) == '[]');
            $array = substr($var, 0, strlen($var)-2);
            $this->vars[$array][] = $value;
        }

        $this->log[] = "<li style='color:green;'>{$var} = {$value}; " . round($trust*100) . "% {$log}</li>";
    }


    /**
     * Връща стойността на дадена променлива
     */
    private function getVar($var)
    {
    
        return $this->vars[$var];
    }


    /**
     * Опитва се да приложи правилото към данните
     * Трябва в резултат да получи:
     * $rule->value = стойност на правилото
     * $rule->trust = достоверност
     * $rule->state = fail, pending, used
     */
    private function prepareRule(&$rule)
    { 
        if($rule->state != 'pending') return;

        if($this->trusts[$rule->name]) {
            $rule->state = 'block';
            $rule->reason = "Използвано е друго правило";

            return;
        }
 
        $trust = $maxTrust = max($rule->priority < 0 ? pow(3, $rule->priority/20) : 0.1, 1 + ($rule->expr != '' && $rule->expr != '0' && $rule->expr != '""') - $rule->order/100000 + $rule->priority);
        $div = 3;
        
        $vars = $rule->exprVars + $rule->condVars;
        
        $maxTrust += count($vars);
        $div      += count($vars);

        foreach($vars as $n) {

            // Ако нямаме достоверност за стойността и нямаме правило за нея - правилото е блокирано
            if(!($this->trusts[$n] > 0)) {
                if(!isset($this->rules[$n])) {
                    $rule->state = 'block';
                    $rule->reason = "Липсват правила за {$n}";

                    return;
                } else {
                    $havePending = FALSE;
                    foreach($this->rules[$n] as $id => $rN) {
                        if($rN->state == 'pending' || ($rN->trust && isset($rN->value))) {
                            $havePending = TRUE;
                            break;
                        }
                    }
                    if(!$havePending) {
                        $rule->state = 'block';
                        $rule->reason = "Правилата за {$n} са изчерпани";

                        return;
                    }
                }
            }

            $trust += (1+$this->trusts[$n])/2;
            if(!$this->trusts[$n]) {
                $trust = 0;
                $rule->trustReason = "Няма достоверност за {$n}";
                break;
            }
        }

        $rule->trust = $trust/$div;
        $rule->maxTrust = $maxTrust/$div;

        if($rule->trust > 1) {
            $rule->trust = 1 + log(2+$rule->trust);
        }

        if($rule->maxTrust > 1) {
            $rule->maxTrust = 2 + log(2+$rule->trust);
        }

        if($rule->trust > 0 && !isset($rule->value)) {

            $rule->condVal = empty($rule->cond) ? TRUE : $this->calc($rule->cond, $rule->condVars);
            if(!$rule->condVal) {
                $rule->state = 'fail';
                 
                return;
            }
            $rule->value = $this->calc($rule->expr, $rule->exprVars);
        }

    }


    /**
     * Изчислява израза, което замества посочените променливи с техните свойства
     */
    function calc($expr, $vars)
    {
        if(count($vars)) {
            foreach($vars as $name) {
                $replace['$' . $name] = "\$this->vars['{$name}']";
            }

            $expr = strtr($expr, $replace);
        }

        //$code = "return {$expr};";
        $code = 'return ' . $expr. ';';
        
        if(!@eval('return TRUE;' . $code)) {
            // Некоректен израз
            bp($code);
        }

        $res = eval($code);

        return $res;
    }
    
 

    /**
     * Стартира процес на изчисляване, според зададените правила
     */
    public function run($rec = NULL, $state = NULL)
    {
 
        // Записваме променливите от $rec
        if(is_object($rec) || is_array($rec)) {
            foreach((array) $rec as $name => $value) {
                if($value !== NULL && is_scalar($value)) {
                    $this->setVar($name, $value, 1, "INPUT");
                }
            }
        }
       
        do {
            // Изчисляваме всички правила. Опитваме се да намерим $value, $trust, $maxTrust
            foreach($this->rules as $name => &$rArr) {
                
                foreach($rArr as $id => $r) {
                    $this->prepareRule($r);
                }
            }

            $bestRule = NULL;
            $rated = array();

            // Намираме от всички правила, това, което има достоверност >0 и се изчислява
            // приоритет = достоверност - брой "чакъщи" правила с по-висок или равен ранг
            foreach($this->rules as $name => &$rArr) {
                
                // Прескачаме променливите, които имат стойност
                if($this->trusts[$name]) continue;
               
                foreach($rArr as $id => $r) {

                    // Пропускаме правилата, които не са чакащи и които не са достоверни
                    if($r->state != 'pending' || !($r->trust > 0)) continue;
                    
                    // Колко са правилата, които са чакащи и имат по-голям maxTrust от текущия
                    $l = 0;
                    foreach($rArr as $rI) {
                        if($rI->maxTrust > $r->trust && $rI->state == 'pending' && !($rI->trust > 0)) {
                            $l++;
                        }
                    }

                    // общия рейтинг на текущото правило
                    $r->rate = 9 + $r->trust - $l + $r->priority;
 
                    if(!isset($bestRule) || $bestRule->rate < $r->rate) {
                        $bestRule = $r;
                        $rated[] = $r;
                    }
                }
            }
            
            if($bestRule) {  
//if($this->vars['W'] && $bestRule->name == 'WeightTotal') bp($this->rules, $rated);
                $this->setVar($bestRule->name, $bestRule->value, $bestRule->trust, "[{$bestRule->expr}]" . ($bestRule->cond ?  " ({$bestRule->cond})":''));
                $bestRule->state = 'used';
            }

        } while($bestRule);
        
        if($this->vars['W']) {
        //     bp($this->rules);
        }
        return $this->vars;
    }
}