<?php



/**
 * Клас 'core_Sbf'
 *
 *
 * @category  ef
 * @package   core
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Sbf extends core_Mvc
{

    static function convertUrlToPath($url)
    {
        list($first, $last) = explode('/' . EF_SBF . '/' . EF_APP_NAME . '/', $url);
        $path = EF_SBF_PATH . '/' . $last;
        
        return $path;
    }


    /**
     * Записва посоченото съдържание на указания път
     * Връща FALSE при грешка или пълния път до новозаписания файл
     */
    static function saveFile_($content, $path, $isFullPath = FALSE)
    { 
        if(!$isFullPath) {
            $path = EF_SBF_PATH . '/' . $path;
        }

        if(file_put_contents($path, $content) !== FALSE) {

            return $path;
        }

        return FALSE;
    }


    /**
     * Връща съответстващия път в sbf на зададен вътрешен път
     */
    static function getSbfFilePath_($path)
    {  
        $file = getFullPath($path);
        
        $pathArr = pathinfo($path);

        $timeSuffix = '';

        if($file) {
            $time =  filemtime($file);
            $timeSuffix = "_" . date("mdHis", $time);
        } 
        
        // Новото име на файла, зависещо от времето на последната му модификация
        $sbfPath = EF_SBF_PATH . "/" . $pathArr['dirname'] . '/' . $pathArr['filename'] . $timeSuffix . '.' . $pathArr['extension'];

        return $sbfPath;
    }



    /**
     * Връща URL на Browser Resource File, по подразбиране, оградено с кавички
     *
     * @param string $rPath Релативен път до статичния файл
     * @param string $qt    Символ за ограждане на резултата
     * @param boolean $absolute Дали резултатното URL да е абсолютно или релативно
     *
     * @return string
     */
    public static function getUrl($rPath, $qt = '"', $absolute = FALSE)
    {
        // Ако файла съществува
        if (($sbfPath = core_Sbf::getSbfFilePath($rPath)) && $rPath{0} != '_') {
            
            // Ако файла не съществува в SBF
            if(!file_exists($sbfPath)) {
                
                // Ако директорията не съществува
                if(!is_dir($dir = dirname($sbfPath))) {
                    
                    // Създаваме директория
                    if(!@mkdir($dir, 0777, TRUE)) {
                        
                        // Ако възникне грешка при създаването, записваме в лога
                        core_Logs::add(get_called_class(), NULL, "Не може да се създаде: {$dir}");
                    }
                }
                
                $content = getFileContent($rPath);

                if(core_Sbf::saveFile($content, $sbfPath, TRUE)) {
                    
                    // Записваме в лога, всеки път след като създадам файл в sbf
                    core_Logs::add(get_called_class(), NULL, "Генериране на файл в 'sbf' за '{$rPath}'", 5);
                 } else {
                    
                     // Записваме в лога
                    core_Logs::add(get_called_class(), NULL, "Файла не може да се запише в '{$sbfPath}'.");
                }   

            } 
                
            $sbfArr = pathinfo($sbfPath);
            $rArr = pathinfo($rPath);

            // Пътя до файла
            $rPath = $rArr['dirname'] . '/'. $sbfArr['basename'];
        }
        
        $res = $qt . core_App::getBoot($absolute) . '/' . EF_SBF . '/' . EF_APP_NAME . '/' . $rPath . $qt;
        

        return $res;
    }


    /**
     * Функция, която проверява и ако се изисква, сервира
     * браузърно съдържание html, css, img ...
     *
     * @param string $name
     */
    public static function serveStaticFile($name)
    {
        $file = getFullPath($name);

        // Грешка. Файла липсва
        if (!$file) {
            error_log("EF Error: Mising file: {$name}");

            if (isDebug()) {
                error_log("EF Error: Mising file: {$name}");
                header('Content-Type: text/html; charset=UTF-8');
                header("Content-Encoding: none");
                echo "<script type=\"text/javascript\">\n";
                echo "alert('Error: " . str_replace("\n", "\\n", addslashes("Липсващ файл: *{$name}")) . "');\n";
                echo "</script>\n";
                 
            } else {
                header('HTTP/1.1 404 Not Found');
                 
            }
        } else {

            // Файла съществува и трябва да бъде сервиран
            // Определяне на Content-Type на файла
            $fileExt = strtolower(substr(strrchr($file, "."), 1));
            $mimeTypes = array(
                'css' => 'text/css',
                'htm' => 'text/html',
                'svg' => 'image/svg+xml',
                'html' => 'text/html',
                'xml' => 'text/xml',
                'js' => 'application/javascript',
                'swf' => 'application/x-shockwave-flash',
                'jar' => 'application/x-java-applet',
                'java' => 'application/x-java-applet',

                // images
                'png' => 'image/png',
                'jpe' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'jpg' => 'image/jpeg',
                'gif' => 'image/gif',
                'ico' => 'image/vnd.microsoft.icon'
            );

            $ctype = $mimeTypes[$fileExt];

            if (!$ctype) {
                if (isDebug()) {
                    header('Content-Type: text/html; charset=UTF-8');
                    header("Content-Encoding: none");
                    echo "<script type=\"text/javascript\">\n";
                    echo "alert('Error: " . str_replace("\n", "\\n", addslashes("Unsuported file extention: $file ")) . "');\n";
                    echo "</script>\n";
                } else {
                    header('HTTP/1.1 404 Not Found');
                }
            } else {
                header("Content-Type: $ctype");

                // Хедъри за управлението на кеша в браузъра
                header("Expires: " . gmdate("D, d M Y H:i:s", time() + 3153600) . " GMT");
                header("Cache-Control: public, max-age=3153600");

                if (substr($ctype, 0, 5) == 'text/' || $ctype == 'application/javascript') {
                    $gzip = in_array('gzip', array_map('trim', explode(',', @$_SERVER['HTTP_ACCEPT_ENCODING'])));

                    if ($gzip) {
                        header("Content-Encoding: gzip");

                        // Търсим предварително компресиран файл
                        if (file_exists($file . '.gz')) {
                            $file .= '.gz';
                            header("Content-Length: " . filesize($file));
                        } else {
                            // Компресираме в движение
                            // ob_start("ob_gzhandler");
                        }
                    }
                } else {
                    header("Content-Length: " . filesize($file));
                }
         
                // Изпращаме съдържанието към браузъра
                readfile($file);
                
                flush();

                // Копираме файла за директно сервиране от Apache
                // @todo: Да се минимализират .js и .css
                if(!isDebug()) {
                    $sbfPath = EF_SBF_PATH . '/' . $name;

                    $sbfDir = dirname($sbfPath);

                    mkdir($sbfDir, 0777, TRUE);

                    @copy($file, $sbfPath);
                }
            }
        }

    }

    
}