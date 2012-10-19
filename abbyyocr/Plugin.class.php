<?php


/**
 * Плъгин за добавяне на бутона за разпознаване на текст с abbyyocr
 *
 * @category  vendors
 * @package   abbyyocr
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class abbyyocr_Plugin extends core_Plugin
{
    
    
    /**
     * Добавя бутон за разглеждане на документи
     */
    function on_AfterPrepareSingleToolbar($mvc, &$res, &$data)
    {
        // Ако има права за single
        if ($mvc->haveRightFor('single', $data->rec)) {
            try {
                $rec = $data->rec;
                
                //Разширението на файла
                $ext = strtolower(fileman_Files::getExt($rec->name));
                
                // Позволените разширения
                $allowedExt = array('pdf', 'bmp', 'pcx', 'dcx', 'jpeg', 'jpg', 'tiff', 'tif', 'gif', 'png');
                
                // Ако разширението не е в позволените
                if (!in_array($ext, $allowedExt)) return ;
                
                // Ако е извлечена текстовата част
                $params['type'] = 'text';
                $params['dataId'] = $rec->dataId;
                $procText = fileman_Indexes::isProcessStarted($params, TRUE);
                
                if ($procText) return ;
                
                // Ако е извлечена текстовата част с OCR
                $paramsOcr['type'] = 'textOcr';
                $paramsOcr['dataId'] = $rec->dataId;
                $procTextOcr = fileman_Indexes::isProcessStarted($paramsOcr);

                if ($procTextOcr) {
                    
                    // Правим бутона на disabled
                    $btnParams['disabled'] = 'disabled';    
                }
                
                $btnParams['order'] = 60;
                
                $url = toUrl(array($mvc, 'getTextByOcr', $rec->fileHnd, 'type' => 'abbyy', 'ret_url' => FALSE)); 
                 
                // Добавяме бутона
                $data->toolbar->addBtn('OCR', $url, 
                	"class=btn-ocr", 
                    $btnParams
                ); 
            } catch (core_Exception_Expect $expect) {}
        }
    }
    
    
    /**
     * Функция по подразбиране, за извличане на текстовата част
     */
    function on_AfterGetTextByAbbyyOcr($mvc, $res, $fh)
    {
        // Вземаме записа за файла
        $rec = fileman_Files::fetchByFh($fh);
        
        // Параметри необходими за конвертирането
        $params = array(
            'callBack' => 'abbyyocr_Plugin::afterGetTextByAbbyyOcr',
            'dataId' => $rec->dataId,
        	'asynch' => TRUE,
            'createdBy' => core_Users::getCurrent('id'),
            'type' => 'textOcr',
        );
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = fileman_webdrv_Generic::getLockId($params['type'], $rec->dataId);

        // Проверявама дали няма извлечена информация или не е заключен
        if (fileman_Indexes::isProcessStarted($params)) return ;

        // Заключваме процеса за определено време
        if (core_Locks::get($params['lockId'], 100, 0, FALSE)) {
            
            // Стартираме извличането
            static::getText($rec->fileHnd, $params);
        }
    }
    
    
    /**
     * Вземаме текстова част от подадения файл
     * 
     * @param fileHnd $fileHnd - Манипулатора на файла
     * @param array $params - Допълнителни параметри
     */
    static function getText($fileHnd, $params)
    {
        // Инстанция на класа
        $Script = cls::get(fconv_Script);
        
        // Пътя до файла, в който ще се записва получения текст
        $textPath = $Script->tempDir . 'text.txt';
        
        // Задаваме файловете и параметрите
        $Script->setFile('INPUTF', $fileHnd);
        $Script->setFile('OUTPUTF', $textPath);
        
        // Задаваме параметрите
        $Script->setParam('LANGUAGE', 'Bulgarian English', TRUE);
        
        // Добавяме към изпълнимия скрипт
        $lineExecStr = "abbyyocr9 -rl [#LANGUAGE#] -if [#INPUTF#] -tet UTF8 -f Text -of [#OUTPUTF#]";
        
        // Скрипта, който ще конвертира
        $Script->lineExec($lineExecStr, array('LANG' => 'en_US.UTF-8', 'HOME' => $Script->tempPath));

        // Функцията, която ще се извика след приключване на операцията
        $Script->callBack($params['callBack']);
        
        // Други допълнителни параметри
        $Script->outFilePath = $textPath;
        $Script->params = serialize($params);
        $Script->fh = $fileHnd;
        
        // Стартираме скрипта
        $Script->run($params['asynch']);
        
        // Добавяме съобщение
        core_Statuses::add('Стартирано е извличането на текст с OCR');
    }
    
    
    /**
     * Изпълнява се след приключване на обработката
     * 
     * @param fconv_Script $sctipt - Обект с данние
     * 
     * @param boolena
     */
    function afterGetTextByAbbyyOcr($script)
    {
        // Десериализираме нужните помощни данни
        $params = unserialize($script->params);
        
        // Проверяваме дали е имало грешка при предишното конвертиране
        if (fileman_Indexes::haveErrors($script->outFilePath, $params['type'], $params)) {
            
            // Отключваме процеса
            core_Locks::release($params['lockId']);
            
            return FALSE;
        }
        
        // Вземаме съдържанието на файла
        $params['content'] = file_get_contents($script->outFilePath);
        
        // Записваме данните
        $saveId = fileman_Indexes::saveContent($params);
        
        // Отключваме процеса
        core_Locks::release($params['lockId']);
        
        if ($saveId) {

            // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
            // и записа от таблицата fconv_Process
            return TRUE;
        }
        
        return FALSE;
    }
}