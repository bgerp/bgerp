<?php



/**
 * Клас 'unit_Tests' - мениджър за провеждане на тестове
 *
 *
 * @category  ef
 * @package   unit
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class unit_Tests extends core_Manager
{

    var $errorLog = '';
    var $testLog = array();

    function on_BeforeAction($mvc, &$res, $act)
    {
        $act = trim(Request::get('Act', 'identifier'), '_');
        
        $classes = array();

        if($act && cls::load($act, TRUE)) {
            $classes[] = $act;
        } else {
            $classes = $this->readClasses(EF_APP_PATH, $act);
        }
        foreach($classes as $testClass) { 
            if(strrpos($testClass, '_tests_')) {
                if(cls::load($testClass, TRUE)) { 
                    $class = str_replace('_tests_', '_', $testClass);
                    if(cls::load($class, TRUE)) {
                        $tests[$class] = $testClass;
                    }
                }
            }
        }
        
        $requestMetod = strtolower(Request::get('id', 'identifier'));
        
        // Правим тестове на всички открити файлове
        if(count($tests)) {
            
            Debug::startTimer('unit_Tests');
            
            foreach($tests as $class => $testClass) {

                $this->testLog[] = "<h3>Тестване на <b style='color:blue;'>{$class}</b></h3><ul>";

                $reflector = new ReflectionClass($testClass);
                $testClass = cls::get($testClass);
                $methods = $reflector->getMethods();
                foreach($methods as $m) {
                    $mName = strtolower($m->name);
                    if(stripos($mName, 'test_') === 0) {
                        $testMethod = substr($mName, 5);
                        if(!$requestMethod || ($requestMethod == $testMethod)) {
                            $unitClass = cls::get($class);
                            try {
                                call_user_func(array($testClass, $mName), $unitClass);
                            } catch (core_Exception_Expect $expect) {
                                $dump = $expect->getDump();
                                $this->errorLog .= ' exception: ' . $expect->getMessage() . " " . $dump[0]; 
                                reportException($expect);
                            }

                            if($this->errorLog) {
                                $msg = "<span class=\"red\">{$this->errorLog}</span>";
                                $errCnt++;
                            } else {
                                $msg = "<span class=\"green\">OK</span>";
                            }

                            $testsCnt++;
                            
                            $methodName = substr($m->name, 5);
                            $methodName{0} = strtolower($methodName{0});

                            $this->testLog[] = "<li>{$class}->" . $methodName . ": {$msg}</li>";
                     
                            $this->errorLog = '';    
                        }
                    }
                } 
                
                $this->testLog[] = "</ul>";
            }
            
            Debug::stopTimer('unit_Tests');
        }

        $res = implode("\n", $this->testLog);

        return FALSE;
     }


     /**
      *
      */
    static function expectEqual($a, $b)
    {
        if($a == $b) {
        } else {
            $me = cls::get('unit_Tests');
            $me->errorLog .= "{$a} != {$b}";
        }

        
    }


    /**
     * Връща масив със всички поддиректории и файлове от посочената начална директория
     *
     * array(
     * 'files' => [],
     * 'dirs'  => [],
     * )
     * @param string $root
     * @result array
     */
    function readClasses($root, $pack = '')
    {
        $directories = array();
        $root = $root . DIRECTORY_SEPARATOR;
        $directories[] = $root . ($pack ?  $pack . DIRECTORY_SEPARATOR : '');
        
        $files = array();
        
        while (sizeof($directories)) {
            
            $dir = array_pop($directories);
            
            if ($handle = @opendir($dir)) {
                while (FALSE !== ($file = readdir($handle))) {
                    if ($file == '.' || $file == '..' || $file == '.git') {
                        continue;
                    }
                    
                    $file = $dir . $file;
                    
                    if (is_dir($file)) {
                        $directory_path = $file . DIRECTORY_SEPARATOR;
                        array_push($directories, $directory_path);
                    } elseif (is_file($file) && strpos($file, '.class.php')) {
                        $file = str_replace($root, "", $file);
                        $file = str_replace(DIRECTORY_SEPARATOR, "_", $file);
                        $file = str_replace('.class.php', "", $file);
                        $files[] = $file;
                    }
                }
                closedir($handle);
            }
        }
        
        return $files;
    }

}