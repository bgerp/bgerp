<?php 

/**
 * Обединява JS и CSS файловете в един
 *
 * @category  vendors
 * @package   compactor
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class compactor_Plugin extends core_Plugin
{
    /**
     * Пътя до файла, който се използва за базов за генериране абсолютни линкове от URL-тата във файла
     */
    protected $filePath = '';
    
    
    /**
     * Обединява зададените CSS файлове
     *
     * @param core_Mvc $mvc
     * @param array    $res
     * @param array    $cssArr
     */
    public function on_BeforeAppendFiles($et, &$res, &$files)
    {
        $conf = core_Packs::getConfig('compactor');
        
        $files->css = $this->compactFiles($files->css, $conf->COMPACTOR_CSS_FILES, EF_SBF_PATH . '/css', array($this, 'changePaths'));
        $files->js = $this->compactFiles($files->js, $conf->COMPACTOR_JS_FILES, EF_SBF_PATH . '/js');
    }
    
    
    /**
     * Ако двата масива с файлове имат сечение, то се подготва, ако е необходимо обединение на
     * файловете от $configFilesArr и то, заедно с файловете от $filesArr, които не са в сечение с
     * $configFilesArr се връщат като резултат
     *
     * @param array    $filesArr       Текъщия набор от файлове
     * @param array    $configFilesArr конфигурационния набор от файлове
     * @param callable $callback       Финкция, която да обработи текста на файла, преди компактиране
     *
     * @return array
     */
    public function compactFiles($filesArr, $configFilesArr, $baseDir, $callback = null)
    {
        if (is_array($filesArr)) {
            foreach ($filesArr as $fKey => $fVal) {
                if (is_object($fVal)) {
                    // Script атрибутите и CDN fallback-ът изискват оригиналния ред.
                    return $filesArr;
                }
            }
        }
        $filesArr = arr::make($filesArr, true);
        $configFilesArr = arr::make($configFilesArr, true);

        // Migrate трябва да е непосредствено след jQuery, преди останалите плъгини.
        $configWithDependencies = array();
        foreach ($configFilesArr as $file) {
            $configWithDependencies[$file] = $file;
            if (preg_match('#^jquery/([^/]+)/jquery\.min\.js$#', $file, $matches)) {
                $jqueryVersion = strpos($matches[1], '[#') !== false ? jquery_Setup::get('VERSION') : $matches[1];
                if ($migrateFile = jquery_Jquery::getMigrateFile($jqueryVersion)) {
                    unset($configWithDependencies[$migrateFile]);
                    $configWithDependencies[$migrateFile] = $migrateFile;
                }
            }
        }
        $configFilesArr = $configWithDependencies;
        
        // Не правим нищо, ако конфигурационните файлове и текущите нямат сечение
        if (!countR(array_intersect_key($filesArr, $configFilesArr))) {
            
            return $filesArr;
        }
        
        // Съдържанието и редът дават същия адрес и след пресъздаване на SBF копията.
        $cacheFiles = array();
        $originalFiles = $filesArr;
        $contentFilePaths = array();
        
        $extension = '';
        $compacted = '';
        
        foreach ($configFilesArr as $file) {
            
            // Ако достигне до тук без да са заместени плейсхолдерите
            // Може да се стигне до тук ако е закачен плъгина, но не е инсталиран пакета
            if ((strpos($file, '[#') !== false) && (strpos($file, '#]') !== false)) {
                $file = compactor_Setup::preparePacksPath('compactor', $file);
                log_System::add(get_called_class(), "В компактора има неинсталиран пакет: {$file}", null, 'notice');
            }
            
            sbf($file);
            $sbfFilePath = core_Sbf::getSbfFilePath($file);
            $memoryContent = file_exists($sbfFilePath) ? null : core_Sbf::getMemoryFileContent($sbfFilePath);
            if ($memoryContent === null && !file_exists($sbfFilePath)) {
                sleep(1);
                Debug::log('Sleep 1 sec. in ' . __CLASS__);
            }
            
            if ($memoryContent === null && !file_exists($sbfFilePath)) {
                Debug::log("Skip file {$sbfFilePath} " . __CLASS__);
                
                log_System::add('compactor_Plugin', 'Липсващ файл: ' . $file, null, 'warning');
                
                continue;
            }
            $contentFilePaths[] = array($file, $sbfFilePath, $memoryContent);
        }

        // Първо събираме наличните входове, за да различим липса от последваща грешка при четене.
        foreach ($contentFilePaths as list($file, $sbfFilePath, $memoryContent)) {
            $content = $memoryContent ?? @file_get_contents($sbfFilePath);
            if ($content === false) {
                return $originalFiles;
            }
            if ($callback) {
                $content = call_user_func($callback, $content, $sbfFilePath);
            }
            if ($extension === '') {
                $extension = pathinfo($sbfFilePath, PATHINFO_EXTENSION);
            }
            // Нов ред сам по себе си не разделя JS израз и следващ IIFE.
            $compacted .= ($extension === 'js' && $cacheFiles ? "\n;\n" : "\n") . $content;
            $cacheFiles[] = array($file, hash('sha256', $content));
            if (isset($filesArr[$file])) {
                unset($filesArr[$file]);
            }
        }
        if (!$cacheFiles) {
            return $originalFiles;
        }
        
        $compactFilePath = $baseDir . '/' . md5(serialize($cacheFiles)) . '.' . $extension;
        
        $compactFile = str_replace(EF_SBF_PATH . '/', '', $compactFilePath);
        array_unshift($filesArr, $compactFile);
        
        if (!file_exists($compactFilePath)) {
            if (!core_Sbf::saveFile_($compacted, $compactFilePath, true)) {
                return $originalFiles;
            }
        }
        
        return $filesArr;
    }
    
    
    /**
     * Пресъздава липсващ сборен файл само ако конфигурацията дава същата версия.
     */
    public static function restoreFile($name)
    {
        if (!preg_match('~^(css|js)/[a-f0-9]{32}\.(css|js)\z~', $name, $matches) || $matches[1] !== $matches[2]) {
            return false;
        }
        $ext = $matches[1];
        $conf = core_Packs::getConfig('compactor');
        $files = $ext === 'css' ? ($conf->COMPACTOR_CSS_FILES ?? '') : ($conf->COMPACTOR_JS_FILES ?? '');
        $plugin = cls::get('compactor_Plugin');
        $callback = $ext === 'css' ? array($plugin, 'changePaths') : null;
        $result = $plugin->compactFiles($files, $files, EF_SBF_PATH . '/' . $ext, $callback);
        return in_array($name, $result, true) && is_file(EF_SBF_PATH . '/' . $name) ? EF_SBF_PATH . '/' . $name : false;
    }


    /**
     * Преобразуват локоалните линкове от съдържанието в абсолютни
     *
     * @param string $text
     * @param string $path
     *
     * @return string
     */
    protected function changePaths($text, $path)
    {
        // Задаваме пътя до файла
        $this->filePath = str_replace(EF_SBF_PATH . '/', '', $path);
        
        // Шаблон за намиране на всички линкове, към файлове
        // Трябва да започават с ../
        // Да завършват с .css, .jpg, .jpeg, .png или .gif
        $pattern = "/url\((?'file'[^\)]{1,200}?)\)/i";
        
        // Заместваме локалните линкове към файловете с абсолютни
        $textChanged = preg_replace_callback($pattern, array($this, 'changeImgPaths'), $text);
        
        if (!$textChanged && $text) {
            log_System::add('compactor_Plugin', 'Грешка при извикване на регулярен израз: ' . preg_last_error(), null, 'err');
        }
        
        return $textChanged;
    }
    
    
    /**
     * Замества откритите локални линкове с абсолютни
     *
     * @param array $matches
     *
     * @return string
     */
    protected function changeImgPaths($matches)
    {
        // Ако не е задаен пътя до файла
        if (!($path = $this->filePath)) {
            
            return $matches[0];
        }
        
        // Открития файла
        $trimFile = $file = trim(trim($matches['file'] ?? ''), "'\"");
        if ($file === '' || preg_match('~^(?:[a-z][a-z0-9+.-]*:|/|#)~i', $file)) {
            return $matches[0];
        }
        $suffix = strpbrk($file, '?#');
        if ($suffix !== false) {
            $file = substr($file, 0, strlen($file) - strlen($suffix));
        }
        
        // Директорията
        $dir = dirname($path);
        
        // Докато има файл за връщане назад
        while (strpos($file, '../') === 0) {
            
            // Изрязваме началото
            $file = (str::crop($file, '../'));
            
            // Вземаме директорията
            $dir = dirname($dir);
        }
        
        // Ако сме достигнали до края
        if ($dir == '.') {
            $dir = '';
        }
        
        // Новия път да е остатъка от директорията и остатъка от файла
        $file = ltrim($dir  . '/' .  $file, '/\\');
        
        // Ако съществува такъв файл
        if (getFullPath($file)) {
            
            // Вземаме целия път
            $filePath = sbf($file, '', false) . ($suffix !== false ? $suffix : '');
        } else {
            
            // Ако няма файла, не се правят промени
            $filePath = $trimFile;
        }
        
        $res = str_ireplace($trimFile, $filePath, $matches[0]);
        
        return $res;
    }
}
