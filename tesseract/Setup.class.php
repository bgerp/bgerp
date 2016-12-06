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
 * Езици за търсене
 */
defIfNot('TESSERACT_PAGES_MODE', '4');


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
        'tesseract_Converter',
    );
    
    
        
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
       'TESSERACT_LANGUAGES' => array ('varchar', 'caption=Езици за разпознаване, title=Инсталирани езици за разпознаване'),
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
            $res = exec($tesseract . ' --help', $output, $code);
            if ($code != 0) {
                $haveError = TRUE;
            }
        } else {
            $res = exec('which ' . $tesseract, $output, $code);
            if (!$res) {
                $haveError = TRUE;
            }
        }
        
        if ($haveError) {
            
            return "Програмата " . type_Varchar::escape(self::get('PATH')) . " не е инсталирана.";
        }
    }
}
