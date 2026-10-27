<?php



/**
 * Извличана на информация от файлове
 *
 * @category  vendors
 * @package   apachetika
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class apachetika_Detect
{
    /**
     * Извлича дадена информация от файла
     *
     * @param fileman_Files $fileHnd - Манипулатор на файла
     * @param array         $params  - Други допълнителни параметри
     *                               $params['type'] - Типа, на изходния файл
     *                               $params['callBack'] - Класа и функцията, която ще се извикат след приключване на конвертирането
     *                               $params['fileInfoId'] - id към bgerp_FileInfo
     *                               $params['asynch'] - Дали скрипта да се стартира асинхронно или не
     *
     * @return string
     */
    public static function extract($fileHnd, $params = array())
    {
        $params['type'] = strtolower($params['type'] ?? '');
        $params['asynch'] = $params['asynch'] ?? true;
        // В зависимост от типа на изходния файл
        switch ($params['type']) {
            
            // Ако искаме да извлечем текста
            case 'text':
                $file = 'text.txt';
                $type = 'text';
                break;
                
                // Ако искаме да извлечем HTML
            case 'html':
                $file = 'html.html';
                $type = 'html';
                break;
                
                // Ако искаме да извлечем meta данните
            case 'metadata':
                $file = 'metadata.txt';
                $type = 'metadata';
                break;
                
                // Ако искаме да извлечем xHTML съдържание
            case 'xml':
            case 'xhtml':
                $file = 'xml.html';
                $type = 'xml';
                break;
            
            default:
            expect(false, "{$params['type']} - Не е в допустимите");
            break;
        }
        
        // Инстанция на класа
        $Script = cls::get('fconv_Script');
        
        // Пътя до файла, в който ще се записва получения текст
        $textPath = $Script->tempDir . $file;
        
        // Задаваме файловете и параметрите
        $Script->setFile('INPUTF', $fileHnd);
        $Script->setFile('OUTPUTF', $textPath);
        
        // Вземаме целия път до apachetika
        $apacheTikaPath = apachetika_Setup::getJarPath();
        
        // Задаваме пътя, като параметър
        $Script->setParam('APACHETIKA', $apacheTikaPath, true);
        
        // Добавяме към изпълнимия скрипт
        $lineExecStr = "java -jar [#APACHETIKA#] --{$type} [#INPUTF#] > [#OUTPUTF#]";
        
        $errFilePath = fileman_webdrv_Generic::getErrLogFilePath($textPath);
        
        // C.UTF-8 работи и без инсталиран en_US locale, включително за кирилски файлови имена.
        $Script->lineExec($lineExecStr, array('LANG' => 'C.UTF-8', 'HOME' => $Script->tempPath, 'errFilePath' => $errFilePath));
        
        // Функцията, която ще се извика след приключване на операцията
        $Script->callBack('apachetika_Detect::afterExtract');
        
        $params['errFilePath'] = $errFilePath;
        
        // Други допълнителни параметри
        $Script->outFilePath = $textPath;
        $Script->params = serialize($params);
        
        if (!($params['isPath'] ?? null)) {
            $Script->fh = $fileHnd;
        }
        
        $Script->setCheckProgramsArr('java');
        
        // Стартираме скрипта асинхронно
        if ($Script->run($params['asynch']) === false) {
            if ($params['type'] == 'metadata') {
                $params['content'] = '';
                fileman_Indexes::saveContent($params);
            } else {
                fileman_Indexes::createError($params);
            }
        }
        
        $text = '';
        if (!$params['asynch']) {
            $text = @file_get_contents($textPath);
            $text = $text === false ? '' : $text;
            $text = i18n_Charset::convertToUtf8($text, 'UTF-8');
            
            if (!empty($params['lockId'])) {
                core_Locks::release($params['lockId']);
            }
        }
        
        return $text;
    }
    
    
    /**
     * Получава управелението след приключване на извличането на информация.
     *
     * @param fconv_Script $script - Парамтри
     *
     * @return bool
     */
    public static function afterExtract($script)
    {
        // Десериализираме параметрите
        $params = unserialize($script->params);
        
        // Ако има callBack функция
        if (!empty($params['callBack'])) {
            
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
        
        return true;
    }
}
