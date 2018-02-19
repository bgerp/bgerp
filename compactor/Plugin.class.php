<?php 


/**
 * Обединява JS и CSS файловете в един
 *
 * @category  vendors
 * @package   compactor
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
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
     * @param array $res
     * @param array $cssArr
     */
    function on_BeforeAppendFiles($et, &$res, &$files)
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
     * @param array    $filesArr Текъщия набор от файлове
     * @param array    $configFilesArr конфигурационния набор от файлове
     * @param callable $callback Финкция, която да обработи текста на файла, преди компактиране
     *
     * @return array
     */
    function compactFiles($filesArr, $configFilesArr, $baseDir, $callback = NULL)
    {   
        $filesArr = arr::make($filesArr, TRUE);
        $configFilesArr = arr::make($configFilesArr, TRUE);
        
        // Не правим нищо, ако конфигурационните файлове и текущите нямат сечение
        if(!count(array_intersect_key($filesArr, $configFilesArr))) return;
        
        // Акумолатор за конкатиниране времената на последна модификация на файловете
        $times = '';

        // Масив с пътищата до файловете за обединение
        $contentFilePathsArr = array();
        
        // Акумолатор за сборното съдържание на всички файлове
        $content = '';
        
        foreach($configFilesArr as $file) {
            
            // Ако достигне до тук без да са заместени плейсхолдерите
            // Може да се стигне до тук ако е закачен плъгина, но не е инсталиран пакета
            if ((strpos($file, '[#') !== FALSE) && (strpos($file, '#]') !== FALSE)) {
                $file = compactor_Setup::preparePacksPath('compactor', $file);
                log_System::add(get_called_class(), "В компактора има неинсталиран пакет: {$file}", NULL, 'notice');
            }
            
            sbf($file);
            $sbfFilePath = core_Sbf::getSbfFilePath($file);
            if(!file_exists($sbfFilePath)) {
                sleep(1);
                Debug::log('Sleep 1 sec. in ' . __CLASS__);
            }

            if(!file_exists($sbfFilePath)) {
                Debug::log("Skip file {$sbfFilePath} " . __CLASS__);
                continue;
            }

            $times .= @filemtime($sbfFilePath);
            if(isset($filesArr[$file])) {
                unset($filesArr[$file]);
            }
            $contentFilePathsArr[] = $sbfFilePath;
        }
        $sbfFilePathArr = pathinfo($sbfFilePath);

        $compactFilePath = $baseDir . '/' . md5($times) . '.' . $sbfFilePathArr['extension'];
        
        $compactFile = str_replace(EF_SBF_PATH . "/", '', $compactFilePath);
        array_unshift($filesArr, $compactFile);
        
        $force = FALSE;
        
        // Ако файлът е компактиран преди един ден - регенерирам го
        if (file_exists($compactFilePath)) {
            $cFileTime = @filemtime($compactFilePath);
            if ($cFileTime) {
                $cBeforeSec = dt::mysql2timestamp() - $cFileTime;
                if ($cBeforeSec > 86400) {
                    $force = TRUE;
                }
            }
        }
        
        if ($force || !file_exists($compactFilePath)) {
            // Подготвяме сбора на съдържанието на всички файлове
            foreach($contentFilePathsArr as $filePath) {
                $content = file_get_contents($filePath);
                if($callback) {
                    $content = call_user_func($callback, $content, $filePath);
                }
                $compacted .= "\n" . $content;
            }
            
            file_put_contents($compactFilePath, $compacted);
        }
        
        return $filesArr;
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
        $this->filePath = str_replace(EF_SBF_PATH . "/", '', $path); 
	    
        // Шаблон за намиране на всички линкове, към файлове
        // Трябва да започават с ../
        // Да завършват с .css, .jpg, .jpeg, .png или .gif
        $pattern = "/url\((?'file'[^\)]{1,200}?)\)/i";
        
        // Заместваме локалните линкове към файловете с абсолютни
        $textChanged = preg_replace_callback($pattern, array($this, 'changeImgPaths'), $text);
        
	    if (!$textChanged && $text) {
	        log_System::add('compactor_Plugin', "Грешка при извикване на регулярен израз: " . preg_last_error(), NULL, 'err');
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
        if (!($path = $this->filePath)) return $matches[0];

        // Открития файла
        $trimFile = $file = trim($matches['file'], "'\"");
        
        // Директорията
        $dir = dirname($path);
        
        // Докато има файл за връщане назад
        while(strpos($file, '../') !== FALSE) {
            
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
        $file = ltrim($dir  . '/' .  $file, "/\\");

        // Ако съществува такъв файл
        if (getFullPath($file)) {
           
            // Вземаме целия път
            $filePath = sbf($file, '', FALSE);
        } else {
            
            // Ако няма файла, не се правят промени
            $filePath = $trimFile;
        }
         
        $res = str_ireplace($trimFile, $filePath, $matches[0]);
        
        return $res;
    }
}
