<?php


/**
 * Клас 'ckeditor_Setup'
 *
 * Исталиране/деинсталиране на Apachetika
 *
 *
 * @category  bgerp
 * @package   ckeditor
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class ckeditor_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Текстов редактор за Интернет';
    
 

    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
       

        // Инсталираме плъгина за аватари
        $html .= core_Plugins::installPlugin('Регистриране на HTML', 'ckeditor_Plugin', 'type_Html', 'private');
        
        return $html;
    }

}
