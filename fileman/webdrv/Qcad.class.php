<?php


/**
 * Път до директорията на QCAD
 */
defIfNot('QCAD_PATH', '');


/**
 * Драйвер за обработка на файлове с `QCad`
 * 
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Qcad extends fileman_webdrv_Inkscape
{
    
    
    /**
     * Типа на изходния файл
     */
    static $fileType = 'svg';
    
    
    /**
     * Стартира конвертиране към PNG формат
     * 
     * @param object $fRec - Записите за файла
     * @param array $params - Допълнителни параметри
     */
    static function startConvertingToPng($fRec, $params)
    {
        if (!defined('QCAD_PATH') || !QCAD_PATH) {
            
            fileman_webdrv_Qcad::logAlert('Няма зададена стойност за "QCAD_PATH"');
            
            core_Locks::release($params['lockId']);
            
            fileman_Indexes::createError($params);
            
            return ;
        }
        
        $qcadPath = rtrim(QCAD_PATH, '/');
        
        // Инстанция на класа
        $Script = cls::get('fconv_Script');
        
        $Script->setProgram('dwg2svg', $qcadPath . '/dwg2svg');
        
        // Вземаме името на файла без разширението
        $name = fileman_Files::getFileNameWithoutExt($fRec->fileHnd);

        // Задаваме пътя до изходния файла
        $outFilePath = $Script->tempDir . $name . '.svg';
        
        // Задаваме placeHolder' ите за входния и изходния файл
        $Script->setFile('INPUTF', $fRec->fileHnd);
        $Script->setFile('OUTPUTF', $outFilePath);
        
        $height = static::$pngExportHeight;
        
        // Скрипта, който ще конвертира файла в SVG формат
        $Script->lineExec("dwg2svg -outfile=[#OUTPUTF#] [#INPUTF#]");
        
        // Функцията, която ще се извика след приключване на обработката на файла
        $Script->callBack($params['callBack']);
        
        // Други необходими променливи
        $Script->params = serialize($params);
        $Script->fName = $name;
        $Script->outFilePath = $outFilePath;
        $Script->fh = $fRec->fileHnd;
        
        // Стартираме скрипта Aсинхронно
        $Script->run();
    }
    
    
	/**
     * Екшън за показване превю
     */
    function act_Preview()
    {
        // Очакваме да има права за виждане
        $this->requireRightFor('view');
        
        // Манипулатора на файла
        $fileHnd = Request::get('id');
        
        // Вземаме записа за файла
        $fRec = fileman_Files::fetchByFh($fileHnd);
        
        // Очакваме да има права за разглеждане на записа
        $this->requireRightFor('view', $fRec);
        
        try {
            
            // Опитваме се да вземем thumbnail на файла
            try {
                
                $filesArr = self::getInfoContentByFh($fileHnd, 'jpg');
                
                $return = FALSE;
                
                // Ако няма такъв запис
                if ($filesArr === FALSE) {
                    $return = TRUE;
                }
                
                // Ако е обект и има съобщение за грешка
                if (!is_array($filesArr)) {
                    $return = TRUE;
                } else {
                    $newFileHnd = key($filesArr);
                    
                    if (!$newFileHnd) {
                        $return = TRUE;
                    }
                }
                
                $nRec = fileman_Files::fetchByFh($newFileHnd);
                
                if (!$nRec) {
                    $return = TRUE;
                }
                
                if ($return) {
                    
                    return parent::act_Preview();
                }
                
                $svgInst = cls::get('fileman_webdrv_Svg');
                
                // Стартираме процеса на конвертиране към JPG формат
                $svgInst->convertToJpg($nRec);
                
                Request::push(array('id' => $nRec->fileHnd));
                
                // Показваме thumbnail'а
                return $svgInst->act_Preview();
            } catch (fileman_Exception $e) {
                
                // Сменяме мода
                Mode::set('wrapper', 'page_PreText');
                
                // Връщаме грешката
                return $e->getMessage();    
            }
        } catch (core_exception_Expect $e) {
            
            // Сменяме мода
            Mode::set('wrapper', 'page_PreText');
            
            return "Не може да се покаже прегледа на файла.";
        }
    }
}
