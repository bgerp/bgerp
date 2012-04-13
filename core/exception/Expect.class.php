<?php
class core_exception_Expect extends Exception
{
    protected $debug;
    
    protected $errorTitle;
    
    public function __construct($message = NULL, $debug = NULL, $errorTitle = 'ГРЕШКА В ПРИЛОЖЕНИЕТО')
    {
        parent::__construct($message);
        
        $this->debug      = $debug;
        $this->errorTitle = $errorTitle;
    }
    
    public function args()
    {
        return $this->args;
    }
    
    public function getAsHtml()
    {
        if (isDebug() && isset($this->debug)) {
            core_App::_bp(core_Html::arrayToHtml(array($this->errorTitle, $this->getMessage(), $this->debug)), $this->getTrace());
        }
    
        $text = core_App::isDebug() ? $this->getMessage() : $this->errorTitle;
        
        core_Message::redirect($text, 'tpl_Error');
    }
    
    
    public static function getTraceAsHtml($trace)
    {
        $trace = static::prepareTrace($trace);
        
        $result = '<pre><table border="1" style="border-collapse: collapse;" cellpadding="3">';
        
        foreach ($trace as $row) {
            $result .= '<tr>';
            foreach ($row as $cell) {
                $result .= '<td>' . $cell . '</td>';
            }
            $result .= '</tr>';
        }
        
        $result .= '</table></pre>';
        
        return $result;
    }
    

    private static function prepareTrace($trace)
    {
        $rtn = array();
        
        foreach ($trace as $count => $frame) {
            $file = 'unknown';
            if (!empty($frame['file'])) {
                $file = str_replace(EF_ROOT_PATH . '/', '', $frame['file']);
                $file = sprintf('<a href="#%s:%s">%s:%s</a>', $frame['file'], $frame['line'], $file, $frame['line']);
            }
            $args = "";
            if (isset($frame['args'])) {
                $args = array();
                foreach ($frame['args'] as $arg) {
                    $args[] = static::formatValue($arg);
                }   
                $args = join(", ", $args);
            }
            
            $rtn[] = array(
                $file,
                sprintf("%s(%s)", 
                    isset($frame['class']) ? 
                        $frame['class'].$frame['type'].$frame['function'] : 
                        $frame['function'],
                     $args 
                ),
            );
        }
        
        return $rtn;
    }
    
    private static function formatValue($v)
    {
        $result = '';
        
        if (is_string($v)) {
            $result = "'" . htmlentities($v, ENT_COMPAT | ENT_IGNORE, 'UTF-8') . "'";
        } elseif (is_array($v)) {
            $result = static::arrayToString($v);
        } elseif (is_null($v)) {
            $result = 'NULL';
        } elseif (is_bool($v)) {
            $result = ($v) ? "TRUE" : "FALSE";
        } elseif (is_object($v)) {
            $result = get_class($v);
        } elseif (is_resource($v)) {
            $result = get_resource_type($v);
        } else {
            $result = $v;
        }
        
        return $result;
    }

    private static function arrayToString($arr)
    {
        foreach ($arr as $i=>$v) {
            $arr[$i] = static::formatValue($v);
        }
        
        return '[' . implode(', ', $arr) . ']';
    }
}



/**
 * Генерира грешка, ако аргумента не е TRUE
 * Може да има още аргументи, чийто стойности се показват
 * в случай на прекъсване. Вариант на asert()
 */
function expect($expr)
{
    //    ($expr == TRUE) || error('Неочакван аргумент', func_get_args());
    if (!$expr) {
        throw new core_exception_Expect('Неочакван аргумент', func_get_args());
    }

}