<?php


/**
 * Досегашният custom build остава версията по подразбиране.
 */
defIfNot('CKEDITOR_VERSION', '5.0.1');


/**
 * Клас 'ckeditor_Setup'
 *
 * Инсталиране/деинсталиране на CKEditor
 *
 *
 * @category  bgerp
 * @package   ckeditor
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
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
     * Избор на локално доставената библиотека.
     */
    public $configDescription = array(
        'CKEDITOR_VERSION' => array('enum(5.0.1, 49.0.1)', 'caption=CKEditor->Версия'),
    );


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
