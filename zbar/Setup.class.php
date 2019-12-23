<?php


/**
 * Клас 'zbar_Setup'
 *
 * Исталиране/деинсталиране на Apachetika
 *
 *
 * @category  bgerp
 * @package   zbar
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class zbar_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Пакет за прочитана на баркодове от файл';
    
    
    /**
     * Пакет без инсталация
     */
    public $noInstall = true;
    
    
    /**
     * Проверява дали програмата е инсталирана в сървъра
     *
     * @return NULL|string
     */
    public function checkConfig()
    {
        $program = 'zbarimg';
        $haveError = false;
        
        if (core_Os::isWindows()) {
            $res = @exec("{$program} --help", $output, $code);
            if ($code !== 0) {
                $haveError = true;
            }
        } else {
            $res = @exec("which {$program}", $output, $code);
            if (!$res) {
                $haveError = true;
            }
        }
        
        if ($haveError) {
            
            return "Програмата '{$program}' не е инсталирана.";
        }
    }
}
