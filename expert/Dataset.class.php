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
class expert_Dataset extends core_Mvc {
    
    
    /**
     * Стойности на променливите
     */
    public $vars = array();
    

    /**
     * Номер на текущата стъпка
     */
    public $step = 0;
    

    /**
     * Коя променлива, на коя стъпка е сетната
     */
    public $setOnStep = array();


    /**
     * Масив със всички праила за сетване на променливи
     */
    public $rules = array();


    /**
     * Задава стойност на променлива
     */
    public function addRule($name, $expr, $cond = NULL)
    {
        $rule = (object) array('name' => $name, 'expr' => $expr, 'cond' => $cond);
        $rule->exprVars = $this->extractVars($expr);

        if($cond !== NULL) {
            $rule->condVars = $this->extractVars($cond);
        }

        if($rule->name{0} == '$') {
            $rule->name = substr($rule->name, 1);
        }

        $this->rules[] = $rule;
    }
    
    
    /**
     * Мегически метод, който се извиква, ако обекта се използва като функция
     */
    public function __invoke($name, $expr, $cond = NULL)
    {
        static $files;

        $stack = debug_backtrace();
 
        if(!$files[$stack[0]['file']]) {
            $files[$stack[0]['file']] = explode("\n", file_get_Contents($stack[0]['file']));
        }
 
        $line = trim($files[$stack[0]['file']][$stack[0]['line']-1]);

        if(strpos($line, ', "')) {
            $this->log[] = "Warning: Възможен проблем с двойни кавички в правилото <b>$line</b>";
        }

        $this->addRule($name, $expr, $cond);
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
     * Проверка дали ВСИЧКИ зададени променливи са сетнати
     */
    private function issetVars($vars = array())
    {
        if(is_scalar($vars)) {
            $vars = array($vars => $vars);
        }

        expect(is_array($vars));

        foreach($vars as $var) {
            if(strpos($var, '[]')) return FALSE;
 
            if(!isset($this->setOnStep[$var])) return FALSE;
            
        }

        return TRUE;
    }


    /**
     * Задава стойност на посочената променлива
     */
    private function setVar($var, $value)
    {
        if(!strpos($var, '[]')) {
            $this->vars[$var] = $value;
            $this->setOnStep[$var] = $this->step;
        } else {  
            expect(substr($var, -2) == '[]');
            $var = substr($var, 0, strlen($var)-2);
            $this->vars[$var][] = $value;
        }
    }


    /**
     * Връща стойността на дадена променлива
     */
    private function getVar($var, $force = FALSE)
    {
        if(!$force && (!isset($this->setOnStep[$var]) || $this->setOnStep[$var] <= $this->step)) return NULL;

        return $this->vars[$var];
    }


    /**
     * Опитва се да приложи правилото към данните
     */
    private function doRule(&$rule)
    {
        if(isset($rule->usedOnStep) && $rule->usedOnStep <= $this->step) return FALSE;

        if($this->issetVars($rule->name)) {
            $rule->usedOnStep = $this->step;
            return FALSE;
        }
        
        // Липсват всички променливи за израза
        if(!$this->issetVars($rule->exprVars)) {
            return FALSE;
        }
        
        if($rule->cond !== NULL) {
            // Липсват всички променливи за условието
            if(!$this->issetVars($rule->condVars)) {
                return FALSE;
            }
            
            // Изчисляването на условието връща FALSE
            if(!$this->calc($rule->cond, $rule->condVars)) {
                return FALSE;
            }
        }
        $this->setVar($rule->name, $this->calc($rule->expr, $rule->exprVars));

        $rule->usedOnStep = $this->step;

        return TRUE;
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

        $code = "return {$expr};";
 
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
        // Нова стъпка
        $this->step++;

        // Записваме променливите от $rec
        if(is_object($rec) || is_array($rec)) {
            foreach((array) $rec as $name => $value) {
                if($value !== NULL) {
                    $this->setVar($name, $value);
                }
            }
        }
 
        // Прилагаме правилата, докато направим цикъл в който да няма нито едно приложено правило
        do {
            $activeRule = FALSE;
            foreach($this->rules as $rule) {
                if($this->doRule($rule)) {
                    $activeRule = TRUE;
                    break;
                }
            }
            //bp($activeRule);
        } while($activeRule);

        return $this->vars;
    }


    function act_Test()
    {
      
        $this('p', '$a+$b+$c');
        $this('a', '$p-$b-$c');
        $this('b', '$p-$a-$c');
        $this('c', '$p-$a-$b');
        $this('c_opt[]', '$p');
        $this('c_opt[]', 'arr::make("as,as")');

        bp($this->run(array('p' => 33, 'a'=>11, 'b' => 11)), $this);
    }
}