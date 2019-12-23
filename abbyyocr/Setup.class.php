<?php


/**
 * Пътя до abbyyocr
 */
defIfNot('ABBYYOCR_PATH', 'abbyyocr9');


/**
 * Езици за търсене
 */
defIfNot('ABBYYOCR_LANGUAGES', 'Bulgarian English');


/**
 * Инсталатор на плъгин за добавяне на бутона за разпознаване на текст с abbyyocr
 *
 * @category  vendors
 * @package   abbyyocr
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class abbyyocr_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = '';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Адаптер за ABBYY FineReader CLI for Linux - разпознаване на текст в сканирани документи';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'abbyyocr_Converter',
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        'ABBYYOCR_LANGUAGES' => array('varchar', 'caption=Езици за търсене'),
    
    );
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        $html = parent::deinstall();
        
        // Вземаме конфига
        $conf = core_Packs::getConfig('fileman');
        
        $data = array();
        
        // Ако текущия клас е избран по подразбиране
        if ($conf->_data['FILEMAN_OCR'] == core_Classes::getId('abbyyocr_Converter')) {
            
            // Премахваме го
            $data['FILEMAN_OCR'] = null;
            
            // Добавяме в записите
            core_Packs::setConfig('fileman', $data);
            
            $html .= "<li class=\"green\">Премахнат е 'abbyyocr_Converter' от конфигурацията</li>";
        }
        
        return $html;
    }
    
    
    /**
     * Проверява дали програмата е инсталирана в сървъра
     *
     * @return null|string
     */
    public function checkConfig()
    {
        if (fconv_Remote::canRunRemote('abbyyocr9')) {
            
            return;
        }
        
        $conf = core_Packs::getConfig('abbyyocr');
        
        $abbyocr = escapeshellcmd($conf->ABBYYOCR_PATH);
        
        if (core_Os::isWindows()) {
            $res = @exec($abbyocr . ' --help', $output, $code);
            if ($code != 0) {
                $haveError = true;
            }
        } else {
            $res = @exec('which ' . $abbyocr, $output, $code);
            if (!$res) {
                $haveError = true;
            }
        }
        
        if ($haveError) {
            
            return 'Програмата ' . type_Varchar::escape($conf->ABBYYOCR_PATH) . ' не е инсталирана.';
        }
    }
}
