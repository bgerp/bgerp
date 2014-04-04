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
        $newPath = static::compactFilesFromArr($newFileName, $sArr);
        
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
        $newPath = static::compactFilesFromArr($newFileName, $sArr);
        
        // Ако файла не съществува
        if (!$newPath) return ;
        
        // Добавяме файла в масива
        $resArr = static::addNewFileToArr($newPath, $jsArr);//bp($resArr);
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
     * 
     * @return string
     */
    static function compactFilesFromArr($newFileName, $sArr)
	{
        $conf = core_Packs::getConfig('compactor');
        
        // Пътя до временния файл
        $tempPath = $conf->COMPACTOR_TEMP_PATH . DIRECTORY_SEPARATOR . $newFileName;
        
        // Ако файла не съществува
        if (!file_exists($tempPath)) {
            
            $content = '';
            
            foreach ((array)$sArr as $ePath) {
                
                // Вземаме съдържанието
                $content .= @file_get_contents(sbf($ePath, '', TRUE)) . "\n";
            }
            
            // Добавяме във файла
            if (!@file_put_contents($tempPath, $content)) {
                
                return FALSE;
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
}
