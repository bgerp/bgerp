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
     *
     * Възможни - svg, bmp, pdf
     */
    public static $fileType = 'bmp';
    
    
    /**
     * Дали да се зададат размери за широчина и височина на изходния файл
     */
    public static $useSizes = true;
    
    
    /**
     * Колко пъти да се увеличи широчината/височината за показване
     * По-добро качество
     */
    public static $qualityFactor = 1.4;
    
    
    /**
     * Стартира конвертиране към PNG формат
     *
     * @param object $fRec   - Записите за файла
     * @param array  $params - Допълнителни параметри
     */
    public static function startConvertingToPng($fRec, $params)
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
        
        $Script->setProgram('dwg2' . self::$fileType, $qcadPath . '/dwg2' . self::$fileType);
        
        // Вземаме името на файла без разширението
        $name = fileman_Files::getFileNameWithoutExt($fRec->fileHnd);

        // Задаваме пътя до изходния файла
        $outFilePath = $Script->tempDir . $name . '.' . self::$fileType;
        
        // Задаваме placeHolder' ите за входния и изходния файл
        $Script->setFile('INPUTF', $fRec->fileHnd);
        $Script->setFile('OUTPUTF', $outFilePath);
        
        $height = static::$pngExportHeight;
        
        $errFilePath = self::getErrLogFilePath($outFilePath);
        
        $lineExec = 'dwg2' . self::$fileType . ' -outfile=[#OUTPUTF#]';
        
        if (self::$useSizes) {
            $lineExec .= ' -platform offscreen -x ' . self::$qualityFactor * fileman_Setup::get('PREVIEW_WIDTH') . ' -y ' . self::$qualityFactor * fileman_Setup::get('PREVIEW_HEIGHT');
        }
        
        $lineExec .= ' [#INPUTF#]';
        
        // Скрипта, който ще конвертира файла в SVG формат
        $Script->lineExec($lineExec, array('errFilePath' => $errFilePath));
        
        // Функцията, която ще се извика след приключване на обработката на файла
        $Script->callBack($params['callBack']);
        
        $params['errFilePath'] = $errFilePath;
        
        // Други необходими променливи
        $Script->params = serialize($params);
        $Script->fName = $name;
        $Script->outFilePath = $outFilePath;
        $Script->fh = $fRec->fileHnd;
        
        $Script->setCheckProgramsArr('dwg2' . self::$fileType);
        
        // Стартираме скрипта синхронно
        if ($Script->run() === false) {
            fileman_Indexes::createError($params);
        }
    }
    
    
    /**
     * Екшън за показване превю
     */
    public function act_Preview()
    {
        expect(in_array(self::$fileType, array('bmp', 'svg', 'pdf')));
        
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
                
                $return = false;
                
                // Ако няма такъв запис
                if ($filesArr === false) {
                    $return = true;
                }
                
                // Ако е обект и има съобщение за грешка
                if (!is_array($filesArr)) {
                    $return = true;
                } else {
                    $newFileHnd = key($filesArr);
                    
                    if (!$newFileHnd) {
                        $return = true;
                    }
                }
                
                $nRec = fileman_Files::fetchByFh($newFileHnd);
                
                if (!$nRec) {
                    $return = true;
                }
                
                if ($return) {
                    return parent::act_Preview();
                }
                
                $clsName = 'fileman_webdrv_' . ucfirst(self::$fileType);
                
                $clsInst = cls::get($clsName);
                
                // Стартираме процеса на конвертиране към JPG формат
                $clsInst->convertToJpg($nRec);
                
                Request::push(array('id' => $nRec->fileHnd));
                
                // Показваме thumbnail'а
                return $clsInst->act_Preview();
            } catch (fileman_Exception $e) {
                
                // Сменяме мода
                Mode::set('wrapper', 'page_PreText');
                
                // Връщаме грешката
                return $e->getMessage();
            }
        } catch (core_exception_Expect $e) {
            
            // Сменяме мода
            Mode::set('wrapper', 'page_PreText');
            
            return 'Не може да се покаже прегледа на файла.';
        }
    }
}
