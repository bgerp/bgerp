<?php


/**
 * Извличана на информация от файлове
 *
 * @category  vendors
 * @package   apachetika
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class apachetika_Detect
{
    
    
    /**
     * Извлича дадена информация от файла
     *
     * @param fileman_Files $fileHnd - Манипулатор на файла
     * @param array $params - Други допълнителни параметри
     * $params['type'] - Типа, на изходния файл
     * $params['callBack'] - Класа и функцията, която ще се извикат след приключване на конвертирането
     * $params['fileInfoId'] - id към bgerp_FileInfo
     * $params['asynch'] - Дали скрипта да се стартира асинхронно или не
     * 
     * @return string
     */
    static function extract($fileHnd, $params = array())
    {
        // В зависимост от типа на изходния файл
        switch (strtolower($params['type'])) {
            
            // Ако искаме да извлечем текста
            case 'text' :
                $file = 'text.txt';
                $type = 'text';
                break;
                
                // Ако искаме да извлечем HTML
            case 'html' :
                $file = 'html.html';
                $type = 'html';
                break;
                
                // Ако искаме да извлечем meta данните
            case 'metadata' :
                $file = 'metadata.txt';
                $type = 'metadata';
                break;
                
                // Ако искаме да извлечем xHTML съдържание
            case 'xml' :
            case 'xhtml' :
                $file = 'xml.html';
                $type = 'xml';
                break;
            
            default :
            expect(FALSE, "{$params['type']} - Не е в допустимите");
            break;
        }
        
        // Инстанция на класа
        $Script = cls::get('fconv_Script');
        
        // Пътя до файла, в който ще се записва получения текст
        $textPath = $Script->tempDir . $file;
        
        // Задаваме файловете и параметрите
        $Script->setFile('INPUTF', $fileHnd);
        $Script->setFile('OUTPUTF', $textPath);
        
        $conf = core_Packs::getConfig('apachetika');
        
        // Вземаме целия път до apachetika
        $apacheTikaPath = getFullPath('apachetika/' . $conf->APACHE_TIKA_VERSION . '/tika-app.jar');
        
        // Задаваме пътя, като параметър
        $Script->setParam('APACHETIKA', $apacheTikaPath, TRUE);
        
        // Добавяме към изпълнимия скрипт
        $lineExecStr = "java -jar [#APACHETIKA#] --{$type} [#INPUTF#] > [#OUTPUTF#]";
        
        $errFilePath = fileman_webdrv_Generic::getErrLogFilePath($textPath);
        
        // Скрипта, който ще конвертира
        $Script->lineExec($lineExecStr, array('LANG' => 'en_US.UTF-8', 'HOME' => $Script->tempPath, 'errFilePath' => $errFilePath));
        
        // Функцията, която ще се извика след приключване на операцията
        $Script->callBack('apachetika_Detect::afterExtract');
        
        $params['errFilePath'] = $errFilePath;
        
        // Други допълнителни параметри
        $Script->outFilePath = $textPath;
        $Script->params = serialize($params);
        
        if (!$params['isPath']) {
            $Script->fh = $fileHnd;
        }
        
        $Script->setCheckProgramsArr('java');
        // Стартираме скрипта Aсинхронно
        if ($Script->run($params['asynch']) === FALSE) {
            
            if (strtolower($params['type']) == 'metadata') {
                $params['content'] = '';
                fileman_Indexes::saveContent($params);
            } else {
                fileman_Indexes::createError($params);
            }
        }
        
        $text = '';
        if (!$params['asynch']) {
            $text = @file_get_contents($textPath);
            $text = i18n_Charset::convertToUtf8($text, 'UTF-8');
            
            core_Locks::release($params['lockId']);
        }
        
        return $text;
    }
    
    
    /**
     * Получава управелението след приключване на извличането на информация.
     *
     * @param fconv_Script $script - Парамтри
     *
     * @return boolean
     */
    static function afterExtract($script)
    {
        // Десериализираме параметрите
        $params = unserialize($script->params);
        
        // Ако има callBack функция
        if ($params['callBack']) {
            
            // Разделяме класа от метода
            $funcArr = explode('::', $params['callBack']);
            
            // Обект на класа
            $object = cls::get($funcArr[0]);
            
            // Метода
            $method = $funcArr[1];
            
            // Извикваме callBack функцията и връщаме резултата
            $result = call_user_func_array(array($object, $method), array($script));
            
            return $result;
        }
        
        return TRUE;
    }
}