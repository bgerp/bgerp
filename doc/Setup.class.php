<?php



/**
 * class dma_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с DMA
 *
 *
 * @category  all
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_Setup
{
    
    
    /**
     * Версията на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'doc_Folders';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Документи и папки";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'doc_UnsortedFolders',
            'doc_Folders',
            'doc_Threads',
            'doc_Containers',
            'doc_Search',
            'doc_Folders',
            'doc_Tasks',
            'doc_Comments',
            'doc_Log',
            'doc_PdfCreator',
        );
        
        // Роля ръководител на организация 
        // Достъпни са му всички папки и документите в тях
        $role = 'ceo';
        $html .= core_Roles::addRole($role, NULL, 'rang') ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        // Роля за ръководител на екип. 
        // Достъпни са му всички папки на членовете на екипа, без тези на 'ceo'
        $role = 'manager';
        $html .= core_Roles::addRole($role, NULL, 'rang') ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        // Роля за старши член на екип 
        // Достъпни са му всички общи и всички екипни папки, в допълнение към тези, на които е собственик или са му споделени
        $role = 'officer';
        $html .= core_Roles::addRole($role, NULL, 'rang') ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        // Роля за изпълнителен член на екип 
        // Достъпни са му само папките, които са споделени или на които е собственик
        $role = 'executive';
        $html .= core_Roles::addRole($role, NULL, 'rang') ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        // Роля за външен член на екип 
        // Достъпни са му само папките, които са споделени или на които е собственик
        $role = 'contractor';
        $html .= core_Roles::addRole($role, NULL, 'rang') ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за работа с документи от системата
        $Plugins->installPlugin('Documents in RichEdit', 'doc_RichTextPlg', 'type_Richtext', 'private');
        $html .= "<li>Закачане на doc_RichTextPlg към полетата за RichEdit - (Активно)";
        
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(1, 'Документи', 'Папки', 'doc_Folders', 'default', "user");
        
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