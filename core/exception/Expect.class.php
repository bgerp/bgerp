<?php
class core_exception_Expect extends Exception
{
    public $debug;

    protected $type;

    public function __construct($message = NULL, $debug = NULL, $type = 'Изключение')
    {
        parent::__construct($message);

        $this->debug  = $debug;
        $this->type   = $type;
    }

    public function args($i = NULL)
    {
        if (!isset($i)) {
            return $this->debug;
        }
        
        if (isset($this->debug[$i])) {
            return $this->debug[$i];
        }
        
        return NULL;
    }


    /**
     * Връща html страница, отговаряща за събитието
     */
    public function getAsHtml()
    { 
    	$msg = $this->getMessage();
    	 
    	if (isDebug()) { 

       		$p1 = core_Html::arrayToHtml(array($this->type, $msg, $this->debug));
        } else {
        	$this->logError(); 
        }

        $res = core_App::_bp($p1, $this->getTrace(), $this->type);
 
        return $res;
    }


    public function showMessage()
    {
        if(!($httpStatusCode = (int) $this->getMessage())) {
            $httpStatusCode = 500;
        }

        switch($httpStatusCode) {
            case 400: 
                $httpStatusMsg = 'Bad Request';
                break;
            case 401: 
                $httpStatusMsg = 'Unauthorized';
                break;
            case 403: 
                $httpStatusMsg = 'Forbidden';
                break;
            case 404: 
                $httpStatusMsg = 'Not Found';
                break;
            default:
                $httpStatusMsg = 'Internal Server Error';
                break;
         }

         header($_SERVER["SERVER_PROTOCOL"]." {$httpStatusCode} {$httpStatusMsg}");
         
         header('Content-Type: text/html; charset=UTF-8');

         echo $this->getAsHtml();
    }


    /**
     * Записва грешка в core_Logs ако има грешка приз аписа, записва се в error file-a
     */
    public function logError()
    {
    	$p1 = core_Html::arrayToHtml(array($this->type, $this->getMessage(), $this->debug));
        $html = core_App::getErrorHtml($p1, $this->getTrace());

        @file_put_contents(EF_TEMP_PATH . '/err.log',  "\n\n" . 'Стек: ' . $html . "\n\n", FILE_APPEND);
    }


    public function matchPtr($ptr)
    {
        return preg_match($ptr, $this->message);
    }
    
    
    public static function getTraceAsHtml($trace)
    {
        $trace = static::prepareTrace($trace);

        $result = '<div><table border="1" style="border-collapse: collapse;" cellpadding="3">';

        foreach ($trace as $row) {
            $result .= '<tr>';
            foreach ($row as $cell) {
                $result .= '<td>' . $cell . '</td>';
            }
            $result .= '</tr>';
        }

        $result .= '</table></div>';

        return $result;
    }


    private static function prepareTrace($trace)
    {
        $rtn = array();

        foreach ($trace as $count => $frame) {
            $file = 'unknown';
            if (!empty($frame['file'])) {
                $file = str_replace(EF_ROOT_PATH . '/', '', $frame['file']);
                $hash = md5($frame['file'] . ':' . $frame['line']);
                $file = sprintf('<a href="#%s">%s:%s</a>', $hash, $file, $frame['line']);
                if ($rUrl = static::getRepoSourceUrl($frame['file'], $frame['line'])) {
                    $file = sprintf('<a target="_blank" class="octocat" href="%s"><img valign="middle" src=%s /></a>&nbsp;', $rUrl, sbf('img/16/github.png')) . $file;
                }
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


    /**
     * Разбива име на файл на име на репозитори и локално (за репозиторито) име
     *
     * @param string $file
     * @return array|boolean масив с два елемента (репо и файл) или FALSE при проблем
     */
    private static function extractRepo($file)
    {
        static $repos = array(
            EF_APP_PATH => 'bgerp'
        );

        foreach ($repos as $path=>$repo) {
            if (strpos($file, $path) === 0) {
                $local = ltrim(str_replace($path, '', $file), '/');
                return array($repo, $local);
            }
        }

        return FALSE;
    }


    /**
     * URL на сорс-код файл в централизирано репозитори
     *
     * @param string $file
     * @param int $line
     * @return string|boolean FALSE при проблем, иначе пълно URL
     */
    private static function getRepoSourceUrl($file, $line)
    {
        static $REPO_BASE_TPL = 'https://github.com/bgerp/%s/blob/master/%s#L%d';

        if (!$data = static::extractRepo($file)) {
            return FALSE;
        }

        $data[] = $line;

        return vsprintf($REPO_BASE_TPL, $data);
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
    
    public function getDebug()
    {
        return $this->debug;
    }
}