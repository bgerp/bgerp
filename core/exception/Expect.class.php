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
            _bp(array($this->errorTitle, $this->getMessage(), $this->debug), $this->getTrace());
        }
    
        $text = isDebug() ? $this->getMessage() : $this->errorTitle;
        
        core_Message::redirect($text, 'tpl_Error');
    }
    
    
    public function getAsHtml1()
    {
        $result = '';
        
        $result .= '<h1>' . $this->getMessage() . '</h1>';
        
        $result .= '<h2>Debug Info</h2>';
        $result .= '<pre>';
        ob_start();
        var_dump($this->args[1]);
        $result .= ob_get_clean();
        $result .= '</pre>';

        $result .= '<h2>Trace</h2>';
        $result .= '<pre>';
        $result .= $this->getTraceAsHtml();
        $result .= '</pre>';
        
        $result .= '<pre>';
        $result .= Debug::getLog();
        $result .= '</pre>';
        
        return $result;
    }
    
    
    public function getTraceAsHtml()
    {
        $trace = $this->prepareTrace();
        
        $result = '<table border="1" style="border-collapse: collapse;" cellpadding="3">';
        
        foreach ($trace as $row) {
            $result .= '<tr>';
            foreach ($row as $cell) {
                $result .= '<td>' . htmlentities($cell) . '</td>';
            }
            $result .= '</tr>';
        }
        
        $result .= '</table>';
        
        return $result;
        
    }
    
    private function prepareTrace()
    {
        $rtn = array();
        
        foreach ($this->getTrace() as $count => $frame) {
            if (!empty($frame['file'])) {
                $frame['file'] = str_replace(EF_ROOT_PATH . '/', '', $frame['file']);
            }
            $args = "";
            if (isset($frame['args'])) {
                $args = array();
                foreach ($frame['args'] as $arg) {
                    $args[] = $this->formatValue($arg);
                }   
                $args = join(", ", $args);
            }
            
            $rtn[] = array(
                sprintf("%s:%s", 
                    isset($frame['file']) ? $frame['file'] : 'unknown file',
                    isset($frame['line']) ? $frame['line'] : '?'),
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
    
    private function formatValue($v)
    {
        $result = '';
        
        if (is_string($v)) {
            $result = "'" . $v . "'";
        } elseif (is_array($v)) {
            $result = $this->arrayToString($v);
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

    private function arrayToString($arr)
    {
        foreach ($arr as $i=>$v) {
            $arr[$i] = $this->formatValue($v);
        }
        
        return '[' . implode(', ', $arr) . ']';
    }
}