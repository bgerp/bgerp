<?php


/**
 * Роля за основен екип
 */
defIfNot('BGERP_ROLE_HEADQUARTER', 'Headquarter');


/**
 * Кой пакет да използваме за генериране на PDF от HTML ?
 */
defIfNot('BGERP_PDF_GENERATOR', 'webkittopdf_Converter');


/**
 * Начално време на нотифициране за незавършени действия с докуемнти
 */
defIfNot('DOC_NOTIFY_FOR_INCOMPLETE_FROM', '7200');


/**
 * Крайно време на нотифициране за незавършени действия с докуемнти
 */
defIfNot('DOC_NOTIFY_FOR_INCOMPLETE_TO', '3600');



/**
 * Колко папки от последно отворените да се показват при търсене
 */
defIfNot('DOC_SEARCH_FOLDER_CNT', 5);


/**
 * Време на отклонения за поправка на документ
 */
defIfNot('DOC_REPAIR_DELAY', 120);


/**
 * Дали да се поправят състояниеята на документите
 */
defIfNot('DOC_REPAIR_STATE', 'no');


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
class doc_Setup extends core_ProtoSetup
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
        'BGERP_PDF_GENERATOR' => array ('class(interface=doc_ConvertToPdfIntf,select=title)', 'mandatory, caption=Кой пакет да се използва за генериране на PDF?->Пакет'),
        'DOC_NOTIFY_FOR_INCOMPLETE_FROM' => array ('time', 'caption=Период за откриване на незавършени действия с документи->Начало,unit=преди проверката'),
        'DOC_NOTIFY_FOR_INCOMPLETE_TO' => array ('time', 'caption=Период за откриване на незавършени действия с документи->Край,unit=преди проверката'),
        'DOC_REPAIR_DELAY' => array ('time(suggestions=10 сек.|30 сек.|60 сек.|120 сек.)', 'caption=Отклонение при поправка на документи->Време'),
        'DOC_REPAIR_STATE' => array ('enum(yes=Да, no=Не)', 'caption=Дали да се поправят състоянията на документите->Избор'),
        'DOC_SEARCH_FOLDER_CNT' => array ('int(Min=0)', 'caption=Колко папки от последно отворените да се показват при търсене->Брой'),
    );
    
    
    /**
     * Път до js файла
     */
//    var $commonJS = 'doc/js/accordion.js';
        
    
    /**
     * Път до css файла
     */
//    var $commonCSS = 'doc/tpl/style.css, doc/css/dialogDoc.css';
    
    
    // Инсталиране на мениджърите
    var $managers = array(
        'doc_UnsortedFolders',
        'doc_Folders',
        'doc_Threads',
        'doc_Containers',
        'doc_Search',
        'doc_Folders',
        'doc_Comments',
        'doc_Notes',
        'doc_PdfCreator',
        'doc_ThreadUsers',
        'doc_Files',
    	'doc_TplManager',
        'migrate::repairAllBrokenRelations'
    );
    
        
    /**
     * Инсталиране на пакета
     */
    function install()
    {   
        $html = parent::install();
        $html .= core_Roles::addOnce('powerUser', NULL, 'system');

        // Добавяне на ролите за Ранг
        $rangRoles = array(
            
            // Роля за външен член на екип. Достъпни са му само папките, 
            // които са споделени или на които е собственик
            'contractor', 
            
            // Изпълнителен член на екип. Достъпни са му само папките,
            // които са споделени или на които е собственик
            'executive',  
            
            // Старши член на екип. Достъпни са му всички общи и всички екипни папки, 
            // в допълнение към тези, на които е собственик или са му споделени
            'officer',
            
            // Ръководител на екип. Достъп до всички папки на екипа, без тези на 'ceo'
            'manager',   
            
            // Pъководител на организацията. Достъпни са му всички папки и документите в тях
            'ceo',       
        );
        
        foreach($rangRoles as $role) {
            $inherit = ($role != 'contractor') ? 'powerUser,' . $lastRole : '';
            $lastRole = $role;
            $html .= core_Roles::addOnce($role, $inherit, 'rang');
        }
        
        // Ако няма нито една роля за екип, добавяме екип за главна квартира
        $newTeam = FALSE;
        
        if(!core_Roles::fetch("#type = 'team'")) {
            $html .= core_Roles::addOnce(BGERP_ROLE_HEADQUARTER, NULL, 'team');
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
                        $html .= "<li class=\"green\">Потребителя <b>{$uTitle}</b> e добавен в екипа <b>Headquarter</b></li>";
                    }
                }
            }
        }
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за работа с документи от системата
        // Замества handle' ите на документите с линк към документа
        $html .= $Plugins->installPlugin('Документи в RichEdit', 'doc_RichTextPlg', 'type_Richtext', 'private');
        
        // Закачане на плъгина за прехвърляне на собственотст на системни папки към core_Users
        $html .= $Plugins->installPlugin('Прехвърляне на собственост на папки', 'doc_plg_TransferOwnership', 'core_Users', 'private');
        
        // Замества абсолютните линкове с титлата на документа
        $html .= $Plugins->installPlugin('Вътрешни линкове в RichText', 'bgerp_plg_InternalLinkReplacement', 'type_Richtext', 'private');
        
        // Променя линка за сваляне на файла
        $html .= $Plugins->installPlugin('Линкове на файлове след изпращане', 'bgerp_plg_File', 'fileman_Files', 'private');
        
        // Променя линка към картинките в plain режим
        $html .= $Plugins->installPlugin('FancyBox линкове', 'bgerp_plg_Fancybox', 'fancybox_Fancybox', 'private');
        
        // Плъгин за работа с файлове в документите
        $html .= $Plugins->installPlugin('Файлове в документи', 'doc_FilesPlg', 'fileman_Files', 'private');
        
        // Добавяме елемент в менюто
        $html .= bgerp_Menu::addOnce(1.22, 'Документи', 'Всички', 'doc_Folders', 'default', "user");
        
        return $html;
    }
    
    /**
     * Роли за достъп до модула
     */
    var $roles = 'currency';
              
        
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
    
    
    /**
     * Миграция за поправане на развалени връзки в папки, нишки и контейнери
     * 
     * @return string
     */
    static function repairAllBrokenRelations()
    {
        try {
            
            $conf = core_Packs::getConfig('doc');
            $res .= '';
            
            $repArr = array();
            $repArr['folder'] = doc_Folders::repair(NULL, NULL, $conf->DOC_REPAIR_DELAY);
            $repArr['thread'] = doc_Threads::repair(NULL, NULL, $conf->DOC_REPAIR_DELAY);
            $repArr['container'] = doc_Containers::repair(NULL, NULL, $conf->DOC_REPAIR_DELAY);
            
            foreach ($repArr as $name => $repairedArr) {
                if (!empty($repairedArr)) {
                    
                    if ($name == 'folder') {
                        $res .= "<li class='green'>Поправки в папките: </li>\n";
                    } elseif ($name == 'thread') {
                        $res .= "<li class='green'>Поправки в нишките: </li>\n";
                    } else {
                        $res .= "<li class='green'>Поправки в контейнерите: </li>\n";
                    }
                    
                    foreach ((array)$repairedArr as $field => $cnt) {
                        if ($field == 'del_cnt') {
                            $res .= "\n<li class='green'>Изтирите са {$cnt} записа</li>";
                        } else {
                            $res .= "\n<li>Поправени развалени полета '{$field}' - {$cnt} записа</li>";
                        }
                    }
                }
            }
        
        } catch (Exception $e) {
            
            return ;
        }
        
        return $res;
    }
}
