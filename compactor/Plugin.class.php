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
    function on_AfterPrepareCssFiles($mvc, &$resArr, $cssArr)
    {
        // Резултатния масив
        $resArr = $cssArr;
        
        // Ако няма подададени CSS файлове
        if (!$cssArr) return ;
        
        $conf = core_Packs::getConfig('compactor');
        
        // Всички CSS файлове
        $confCss = strtolower($conf->COMPACTOR_CSS_FILES);
        
        // Масив с файловете
        $confCssArr = arr::make($confCss);
        
        // Ако няма зададени в конфигурацията
        if (!$confCssArr) return ;
        
        // Всички файлове от конфигурацията, които са идват от пуш
        $sArr = static::getSameFiles($confCssArr, $cssArr);
        
        // Ако няма файлове
        if (!$sArr) return ;
        
        // Стринг за имената
        $nameStr = '';
        
        foreach ((array)$sArr as $ePath) {
            
            // Ако има такъв файл
            if ($f = getFullPath($ePath)) {
                
                // Времето на последна промяна
                $time = filemtime($f);
            } else {
                
                // Ако има разширение файла
                if (($dotPos = strrpos($ePath, '.')) !== FALSE) {
                    
                    // Разширението на файла
                    $ext = mb_substr($ePath, $dotPos);
                    
                    // Пътя до файла, без разширенито
                    $filePath = mb_substr($ePath, 0, $dotPos);
                } else {
                    
                    // Пътя до файла
                    $filePath = $ePath;
                }
                
                // Новия файл
                $nPath = $filePath . '.scss';
                
                // Ако има такъв файл
                if ($f = getFullPath($nPath)) {
                    
                    // Времето на последна модификация на директорията
                    $time = core_Os::getLastModified(dirname($f));
                } else {
                    
                    // Ако файла не съществува, го добавяме към CSS файловете
                    $cssArr[] = $ePath;
                }
            }
            
            // Добавяме името на времето към стринга за име на файл
            $nameStr .= $ePath . $time;
        }
        
        // Ако няма име
        if (!$nameStr) return ;
        
        // Хеша от имената и времето на файловете
        $nameHash = md5($nameStr);
        
        // Добавяме разширение
        $newFileName = $nameHash . '.css';
        
        // Пътя до новия файл
        $newPath = static::compactFilesFromArr($newFileName, $sArr, TRUE);
        
        // Ако файла не съществува
        if (!$newPath) return ;
        
        // Добавяме файла в масива
        $resArr = static::addNewFileToArr($newPath, $cssArr);
    }
	
    
    /**
     * Обединява зададените JS файлове
     * 
     * @param core_Mvc $mvc
     * @param array $res
     * @param array $cssArr
     */
    function on_AfterPrepareJsFiles($mvc, &$resArr, $jsArr)
    {
        // Резултатния масив
        $resArr = $jsArr;
        
        // Ако няма подададени JS файлове
        if (!$jsArr) return ;
        
        $conf = core_Packs::getConfig('compactor');
        
        // Всички JS файлове
        $confJs = strtolower($conf->COMPACTOR_JS_FILES);
        
        // Масив с файловете
        $confJSArr = arr::make($confJs);
        
        // Ако няма зададени в конфигурацията
        if (!$confJSArr) return ;
        
        // Всички файлове от конфигурацията, които са идват от пуш
        $sArr = static::getSameFiles($confJSArr, $jsArr);
        
        // Ако няма файлове
        if (!$sArr) return ;
        
        // Стринг за имената
        $nameStr = '';
    
        foreach ((array)$sArr as $ePath) {
            
            // Ако има такъв файл
            if ($f = getFullPath($ePath)) {
                
                // Времето на последна промяна
                $time = filemtime($f);
            } else {
                
                // Ако файла не съществува, го добавяме към JS файловете
                $jsArr[] = $ePath;
            }
            
            // Добавяме името на времето към стринга за име на файл
            $nameStr .= $ePath . $time;
        }
        
        // Ако няма име
        if (!$nameStr) return ;
        
        // Хеша от имената и времето на файловете
        $nameHash = md5($nameStr);
        
        // Добавяме разширение
        $newFileName = $nameHash . '.js';
        
        // Пътя до новия файл
        $newPath = static::compactFilesFromArr($newFileName, $sArr, FALSE);
        
        // Ако файла не съществува
        if (!$newPath) return ;
        
        // Добавяме файла в масива
        $resArr = static::addNewFileToArr($newPath, $jsArr);
    }
    
	
	/**
     * Връща всички еднакви файлове от конфигурационния масив, които ги има в PUSH
     * и ги премахва от там
     * 
     * @param array $confArr
     * @param array $pushArr
     * 
     * @return array
     */
    static function getSameFiles($confArr, &$pushArr)
    {
        // Обхождаме масива със файловете от константата
        foreach ($confArr as $confPath) {
            
            // Пътя зададен в конфигурацията
            $confPath = strtolower($confPath);
            
            // Обхождаме масив от PUSH
            foreach ((array)$pushArr as $key => $path) {
                
                // Пътя в PUSH
                $lPath = strtolower($path);
                
                // Ако пътищата си отговарят
                if ($confPath == $lPath) {
                    
                    // Добавяме в масива
                    $sArr[$path] = $path;
                    
                    // Премахваме от резултатния
                    unset($pushArr[$key]);
                }
            }
        }
        
        return $sArr;
    }
    
    
    /**
     * Обединява масива с файловете в един файл
     * 
     * @param string $newFileName
     * @param array $sArr
     * @param boolean $changePath
     * 
     * @return string
     */
    static function compactFilesFromArr($newFileName, $sArr, $changePath=TRUE)
	{
        $conf = core_Packs::getConfig('compactor');
        
        // Пътя до временния файл
        $tempPath = $conf->COMPACTOR_TEMP_PATH . DIRECTORY_SEPARATOR . $newFileName;
        
        // Ако файла не съществува
        if (!file_exists($tempPath)) {
            
            $content = '';
            
            foreach ((array)$sArr as $ePath) {
                
                // Вземаме съдържанието
                $content .= static::getContentFromPath($ePath, $changePath) . "\n";
            }
            
            // Ако директорията не съществува
            if(!is_dir($conf->COMPACTOR_TEMP_PATH)) {
                
                // Създаваме директорията
                mkdir($conf->COMPACTOR_TEMP_PATH, 0777, TRUE);
            }
            
            // Добавяме във файла
            if (!@file_put_contents($tempPath, $content)) {
                
                // Записваме грешката
                core_Logs::add(get_called_class(), NULL, "Грешка при записване в '{$tempPath}'");
                
                return FALSE;
            } else {
                
                // Имената на файловете
                $filesStr = implode(', ', $sArr);
                
                // Записваме в лога 
                core_Logs::add(get_called_class(), NULL, "Компактиране на '{$filesStr}'");
            }
        }
        
        return $tempPath;
	}
	
	
	/**
	 * Добавяме в пътя до файла в началото на масива
	 * 
	 * @param string $fileName
	 * @param name $arr
	 * 
	 * @return array
	 */
	static function addNewFileToArr($fileName, $arr)
	{
	    // Добавяме в началото на масива
	    array_unshift($arr, $fileName);
	    
	    return $arr;
	}
	
	
	/**
	 * Взема съдържанието на зададения файл
	 * 
	 * @param string $path
	 * @param boolean $changePath
	 * 
	 * @return string
	 */
	static function getContentFromPath($path, $changePath=FALSE)
	{
	    // Съдържанието на файла
	    $content = @file_get_contents(sbf($path, '', TRUE));
	    
	    // Ако е зададено да се преобразуват локоалните линкове от съдържанието в абсолютни
	    if ($changePath) {
	        
	        // Инстанция на този клас
	        $me = cls::get(get_called_class());
	        
	        // Преобразуваме линковете
	        $content = $me->changePaths($content, $path);
	    }
	    
	    return $content;
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
        $this->filePath = $path;
	    
        // Шаблон за намиране на всички линкове, към файлове
        // Трябва да започават с ../
        // Да завършват с .css, .jpg, .jpeg, .png или .gif
        $pattern = '/(\.\.\/)+(.)+((\.css)+|(\.jpg)+|(\.jpeg)+|(\.png)+|(\.gif)+)+/i';
        
        // Заместваме локалните линкове към файловете с абсолютни
	    $text = preg_replace_callback($pattern, array($this, 'changeImgPaths'), $text);
        
	    return $text;
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
        $file = $matches[0];
        
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
        $file = $dir . DIRECTORY_SEPARATOR . $file;
        
        // Вземаме целия път
        $filePath = sbf($file, '', FALSE);
        
        return $filePath;
    }
}
