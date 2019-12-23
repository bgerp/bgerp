<?php


/**
 * Пътя до tnef
 */
defIfNot('TNEF_PATH', 'tnef');


/**
 * Максималната големина на на файловете, които ще се обработват
 * 100 mB
 */
defIfNot('TNEF_MAX_SIZE', 104857600);


/**
 *
 *
 * @category  bgerp
 * @package   tnef
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class tnef_Setup extends core_ProtoSetup
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
    public $info = 'Декодиране на TNEF файлове';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'tnef_Decode',
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'TNEF_MAX_SIZE' => array('fileman_FileSize', 'caption=Максимален размер на файловете->Размер, suggestions=50 MB|100 MB|200 MB|300 MB'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        $Plugins = cls::get('core_Plugins');
        
        $html .= $Plugins->installPlugin('Декодиране на TNEF в имейлите', 'tnef_EmailPlg', 'email_Mime', 'private');
        
        $html .= $Plugins->installPlugin('Декодиране на TNEF файлове', 'tnef_FilesPlg', 'fileman_webdrv_Tnef', 'private');
        
        return $html;
    }
    
    
    /**
     * Проверява дали програмата е инсталирана в сървъра
     *
     * @return bool
     */
    public function checkConfig()
    {
        $conf = core_Packs::getConfig('tnef');
        
        $tnef = escapeshellcmd($conf->TNEF_PATH);
        
        if (core_Os::isWindows()) {
            $res = @exec($tnef . ' --help', $output, $code);
            if ($code != 0) {
                $haveError = true;
            }
        } else {
            $res = @exec('which ' . $tnef, $output, $code);
            if (!$res) {
                $haveError = true;
            }
        }
        
        if ($haveError) {
            
            return 'Програмата ' . type_Varchar::escape($conf->TNEF_PATH) . ' не е инсталирана.';
        }
    }
}
