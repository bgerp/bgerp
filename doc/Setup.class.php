<?php

/**
 * Роля за основен екип
 */
defIfNot('BGERP_ROLE_HEADQUARTER', 'Headquarter');

/**
 * Кой пакет да използваме за генериране на PDF от HTML ?
 */
defIfNot('BGERP_PDF_GENERATOR', 'dompdf');

/**
 * class dma_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с DMA
 *
 *
 * @category  bgerp
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
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    
            // Кой пакет да използваме за генериране на PDF от HTML ?
            'BGERP_PDF_GENERATOR' => array ('enum(dompdf,webkittopdf)', 'mandatory'),
          
        );
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        // Добавяне на ролите за Ранг
        $rangRoles = array(
            'ceo',       // Pъководител на организацията. Достъпни са му всички папки и документите в тях
            
            'manager',   // Ръководител на екип. Достъп до всички папки на екипа, без тези на 'ceo'
            
            'officer',   // Старши член на екип. Достъпни са му всички общи и всички екипни папки, 
            // в допълнение към тези, на които е собственик или са му споделени
            
            'executive', // Изпълнителен член на екип. Достъпни са му само папките, 
            // които са споделени или на които е собственик
            
            'contractor', // Роля за външен член на екип. Достъпни са му само папките, 
            // които са споделени или на които е собственик
        );
        
        foreach($rangRoles as $role) {
            $html .= ($rangRolesSet[$role] = core_Roles::addRole($role, NULL, 'rang')) ?
            "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        }
        
        // Ако няма нито една роля за екип, добавяме екип за главна квартира
        $newTeam = FALSE;
        
        if(!core_Roles::fetch("#type = 'team'")) {
            core_Roles::addRole(BGERP_ROLE_HEADQUARTER, NULL, 'team');
            $html .= "<li style='color:green'>Добавена е роля <b>Headquarter</b></li>";
            $newTeam = TRUE;
        }
        
        // Ако няма потребител с роля 'ceo', добавяме я към всички администратори
        if(!count(core_Users::getByRole('ceo'))) {
            
            $admins = core_Users::getByRole('admin');
            
            if(count($admins)) {
                foreach($admins as $userId) {
                    $uTitle = core_Users::getTitleById($userId);
                    core_Users::addRole($userId, 'ceo');
                    $html .= "<li style='color:green'>На потребителя <b>{$uTitle}</b> e добавен ранг <b>ceo</b></li>";
                    
                    if($newTeam) {
                        core_Users::addRole($userId, BGERP_ROLE_HEADQUARTER);
                        $html .= "<li style='color:green'>Потребителя <b>{$uTitle}</b> e добавен в екипа <b>Headquarter</b></li>";
                    }
                }
            }
        }
        
        // Инсталиране на мениджърите
        $managers = array(
            'doc_UnsortedFolders',
            'doc_Folders',
            'doc_Threads',
            'doc_Containers',
            'doc_Search',
            'doc_Folders',
            'doc_Comments',
            'doc_PdfCreator',
            'doc_ThreadUsers',
            'doc_Files',
        );
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за работа с документи от системата
        // Замества handle' ите на документите с линк към документа
        $html .= $Plugins->installPlugin('Документи в RichEdit', 'doc_RichTextPlg', 'type_Richtext', 'private');
        
        // Замества абсолютните линкове с титлата на документа
        $html .= $Plugins->installPlugin('Вътрешни линкове в RichText', 'bgerp_plg_InternalLinkReplacement', 'type_Richtext', 'private');
        
        // Променя линка за сваляне на файла
        $html .= $Plugins->installPlugin('Линкове за сваляне', 'bgerp_plg_File', 'fileman_Download', 'private');
        
        // Плъгин за работа с файлове в документите
        $html .= $Plugins->installPlugin('Файлове в документи', 'doc_FilesPlg', 'fileman_Files', 'private');
        
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(1.22, 'Документи', 'Всички', 'doc_Folders', 'default', "user");
        
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