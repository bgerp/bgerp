<?php

/**
 *  Константа за тема по-подразбиране на блога
 */
defIfNot('FORUM_DEFAULT_THEME', 'forum_DefaultTheme');

defIfNot('FORUM_THEMES_PER_PAGE', '10');

defIfNot('FORUM_GREETING_MESSAGE', 'Добре дошли в нашия форум');

defIfNot('FORUM_POSTS_PER_PAGE', '10');

/**
 * class forum_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с Форума
 *
 *
 * @category  bgerp
 * @package   forum
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class forum_Setup extends core_ProtoSetup
{


    /**
     * Версия на пакета
     */
    public $version = '0.1';


    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'forum_Boards';


    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';


    /**
     * Описание на модула
     */
    public $info = 'Форум за сайта';

    
    /**
     * Описание на конфигурационните константи за този модул
     */
    public $configDescription = array(
            
            'FORUM_DEFAULT_THEME' => array('class(interface=forum_ThemeIntf,select=title)', 'caption=Тема по подразбиране във форум->Тема'),
         
            'FORUM_THEMES_PER_PAGE' => array('int', 'caption=Темите в една страница->Брой'),
    
            'FORUM_GREETING_MESSAGE' => array('text', 'mandatory, caption=Съобщение за поздрав->Съобщение'),
         
            'FORUM_POSTS_PER_PAGE' => array('int', 'mandatory, caption=Постовете на една страница->Брой'),
        );
    

    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
                'forum_Boards',
                'forum_Postings',
                'forum_Categories',
        );
    

    /**
     * Роли за достъп до модула
     */
    public $roles = 'forum';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
            array(3.54, 'Сайт', 'Форум', 'forum_Boards', 'list', 'cms,forum, admin, ceo'),
        );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Добавяме класа връщащ темата в core_Classes
        $html .= core_Classes::add('forum_DefaultTheme');
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);

        return $res;
    }
    
    
    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonCss()
    {
        return 'forum/tpl/styles.css';
    }
}
