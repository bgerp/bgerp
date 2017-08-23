<?php


/**
 * Пътя до tesseract
 */
defIfNot('TESSERACT_PATH', 'tesseract');

 
/**
 * Езици за търсене
 */
defIfNot('TESSERACT_LANGUAGES', 'bul+eng');


/**
 * Стойността на -psm
 */
defIfNot('TESSERACT_PAGES_MODE', '4');



/**
 * Стойността на -oem
 */
defIfNot('TESSERACT_OCR_MODE', '-1');


/**
 * Инсталатор на плъгин за добавяне на бутона за разпознаване на текст с tesseract
 *
 * @category  vendors
 * @package   tesseract
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class tesseract_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = '';
    
    
    /**
     * Описание на модула
     */
    var $info = "Адаптер за tesseract - разпознаване на текст в сканирани документи";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
        'tesseract_Converter'
    );
    
    
        
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
       'TESSERACT_LANGUAGES' => array ('varchar', 'caption=Езици за разпознаване, title=Инсталирани езици за разпознаване'),
       'TESSERACT_PAGES_MODE' => array ('int', 'caption=Стойността на psm, title=Page segmentation modes от настройките'),
       'TESSERACT_OCR_MODE' => array ('int', 'caption=Стойността на oem, title=OCR Engine modes от настройките'),
     );
     
     
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
    	$html = parent::deinstall();
    	
        // Вземаме конфига
    	$conf = core_Packs::getConfig('fileman');
    	
    	$data = array();
    	
    	// Ако текущия клас е избран по подразбиране
    	if ($conf->_data['FILEMAN_OCR'] == core_Classes::getId('tesseract_Converter')) {
    	    
            // Премахваме го
	        $data['FILEMAN_OCR'] = NULL;
			
	        // Добавяме в записите
            core_Packs::setConfig('fileman', $data);
            
            $html .= "<li class=\"green\">Премахнат е 'tesseract_Converter' от конфигурацията</li>";
    	}
        
        return $html;
    }
    

    /**
     * Проверява дали програмата е инсталирана в сървъра
     * 
     * @return NULL|string
     */
    function checkConfig()
    {
        if (fconv_Remote::canRunRemote('tesseract')) return ;
        
        $tesseract = escapeshellcmd(self::get('PATH'));
 		
        if (core_Os::isWindows()) {
            $res = @exec($tesseract . ' --help', $output, $code);
            if ($code != 0) {
                $haveError = TRUE;
            }
        } else {
            $res = @exec('which ' . $tesseract, $output, $code);
            if (!$res) {
                $haveError = TRUE;
            }
        }
        
        if ($haveError) {
            
            return "Програмата " . type_Varchar::escape(self::get('PATH')) . " не е инсталирана.";
        }
        
        $versionArr = self::getVersionAndSubVersion();
        
        if ($versionArr['version'] < 4) {
    
            // Добавяме съобщение
            return "Версията на tesseract e {$versionArr['version']}.{$versionArr['subVersion']}. За по-добро разпознаване трябва да инсталирате версия над 4.x";
        } else {
            
            @exec($tesseract . ' -v', $outputArr, $code);
            
            // Проверка на версията на leptonica - под 1.74 не работи добре с невронна мрежа
            if ($outputArr) {
                foreach ($outputArr as $oStr) {
                    if (stripos($oStr, 'leptonica') === FALSE) continue;
                    
                    list(,$lVersion) = explode('-', $oStr);
                    
                    $lVersion = trim($lVersion);
                    
                    if (!$lVersion) continue;
                    
                    list($v, $sv) = explode('.', $lVersion);
                    
                    if (($v <= 1) && ($sv < 74)) {
                        
                        return "Версията на 'leptonica' e {$lVersion}. С тази версия има проблем.";
                    }
                }
            }
        }
    }
    
    
    /**
     * Връща масив с версията и подверсията
     * 
     * @return array
     * ['version']
     * ['subVersion']
     */
    static function getVersionAndSubVersion()
    {
        $versionArr = array();
        $tesseract = escapeshellcmd(self::get('PATH'));
        @exec($tesseract . " --version", $resArr, $erroCode);
        
        $tVerStr = $resArr[0];
        
        if (!$tVerStr) return $versionArr;
        
        $tVerStrArr = explode(' ', $tVerStr);
        $tVerStrArr = explode('.', $tVerStrArr[1], 2);
        
        $versionArr['version'] = $tVerStrArr[0];
        $versionArr['subVersion'] = $tVerStrArr[1];
        
        return $versionArr;
    }
    
    
    /**
     * Зареждане на данни
     */
    function loadSetupData($itr = '')
    {
        $res = parent::loadSetupData($itr);
        
        $versionArr = self::getVersionAndSubVersion();
        
        // Ако се инсталира четвърта версия (или по-голяма)
        // И се използва tesseract по подразбиране
        // Караме да се регенерират OCR-натит изображения
        if ($versionArr['version'] >= 4) {
            
            $defaultOcr = fileman_Setup::get('OCR');
            
            if ($defaultOcr) {
                $dInst = cls::get($defaultOcr);
                if ($dInst instanceof tesseract_Converter) {
                    $res .= $this->callMigrate('reconvertOcrFiles', 'tesseract');
                }
            }
        }
        
        return $res;
    }
    
    
    /**
     * Миграция, която кара да се реконвертират OCR-натите файлове
     */
    public static function reconvertOcrFiles()
    {
        $dataInst = cls::get('fileman_Data');
        
        $cnt = 0;
        $iQuery = fileman_Indexes::getQuery();
        $iQuery->where("#type = 'textOcr'");
        
        while ($iRec = $iQuery->fetch()) {
            if (!$iRec->dataId) continue;
            
            $dRec = fileman_Data::fetch($iRec->dataId);
            
            $dRec->processed = 'no';
            $dataInst->save_($dRec, 'processed');
            
            fileman_Indexes::delete($iRec->id);
            
            $cnt++;
        }
        
        $res = "Регенериране на {$cnt} OCR файлове";
        
        tesseract_Converter::logDebug($res);
        
        $ocrMode = tesseract_Setup::get('OCR_MODE');
        
        if ($ocrMode == -1) {
            $conf = core_Packs::getConfig('tesseract');
            $confData = $conf->_data;
            $confData['TESSERACT_OCR_MODE'] = 1;
            core_Packs::setConfig('tesseract', $confData);
            
            tesseract_Converter::logDebug("OCR_MODE е променен на 1");
        }
        
        return $res;
    }
}
