<?php


/**
 * Максимален размер на архивите, които ще се обработват - разглеждане и разархивиране
 * 200 mB
 */
defIfNot('ARCHIVE_MAX_LEN', 209715200);


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
}
