<?php


/**
 * Максимален размер на архивите, които ще се обработват - разглеждане и разархивиране
 * 200 mB
 */
defIfNot('ARCHIVE_MAX_LEN', 209715200);


/**
 * В трябва да се зададе пътя до изпълнимия файл за 7zip
 */
defIfNot('ARCHIVE_7Z_PATH', '');


/**
 * Клас 'archive_Setup'
 *
 * Исталиране/деинсталиране на Apachetika
 *
 *
 * @category  bgerp
 * @package   archive
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class archive_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Инструмент за работа с архиви';
    
    
    /**
     * Пакет без инсталация
     */
    public $noInstall = true;
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'ARCHIVE_MAX_LEN' => array('fileman_FileSize', 'caption=Максимален размер на архивите|*&comma;| които ще се обработват->Размер, suggestions=100 MB|200 MB|500 MB'),
    );
    

    /**
     *  Връща командата към изпълнимия файл на 7zip
     */
    public static function get_ARCHIVE_7Z_PATH()
    {
        if(defined('ARCHIVE_7Z_PATH') && ARCHIVE_7Z_PATH) {
            $cli = ARCHIVE_7Z_PATH;
        } elseif(substr(PHP_OS, 0, 3) === 'WIN') {
            $z7 = getenv('ProgramFiles') . '/7-Zip/7z.exe';
            if(is_executable($z7)) {
                $cli = "\"{$z7}\"";
            } else {
                $z7 = getenv('ProgramFiles(86)') . '/7-Zip/7z.exe';
                if(is_executable($z7)) {
                    $cli = "\"{$z7}\"";
                }
            }
        } else {
            if(is_executable('/usr/local/bin/7z')) {
                $cli = realpath('/usr/local/bin/7z');
            } else {
                $cli = '7z';
            }
        }
        
        return $cli;
    }
}
