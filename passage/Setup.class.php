<?php



/**
 * Пасаж
 *
 *
 * @category  bgerp
 * @package   passage
 * @author    Kristiyan Serafimov <kristian.plamenov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class passage_Setup extends core_ProtoSetup
{


    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'passage_Texts';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Модул за съхраняваме откъси от текст";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'passage_Texts',
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'ceo, admin';

    

	
	/**
	 * Път до css файла
	 */
//	var $commonCSS = 'trans/tpl/LineStyles.css';
    function install()
    {
        $html = parent::install();

        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');

        // Замества handle' ите на документите с линк към документа
        $html .= $Plugins->installPlugin('Пасажи в RichEdit', 'passage_RichTextPlg', 'type_Richtext', 'private');

        return $html;
	}
	
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}