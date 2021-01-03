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
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'ckeditor_Test';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';

    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'ckeditor_Test',
    );

    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
       

        // Инсталираме плъгина за аватари
        $html .= core_Plugins::installPlugin('Регистриране на RichHTML', 'ckeditor_Plugin', 'type_RichHtml', 'private');
        
        return $html;
    }

}
