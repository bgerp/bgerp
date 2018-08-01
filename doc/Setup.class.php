<?php


/**
 * Роля за основен екип
 */
defIfNot('BGERP_ROLE_HEADQUARTER', 'Headquarter');


/**
 * Кой пакет да използваме за генериране на графики ?
 */
defIfNot('DOC_CHART_ADAPTER', 'googlecharts_Adapter');


/**
 * Кой пакет да използваме за генериране на PDF от HTML ?
 */
defIfNot('BGERP_PDF_GENERATOR', 'webkittopdf_Converter');


/**
 * Начално време на нотифициране за незавършени действия с документи
 */
defIfNot('DOC_NOTIFY_FOR_INCOMPLETE_FROM', '7200');


/**
 * Крайно време на нотифициране за незавършени действия с бизнес документи
 */
defIfNot('DOC_NOTIFY_FOR_INCOMPLETE_TO', '3600');


/**
 * Крайно време на нотифициране за незавършени действия с бизнес документи
 */
defIfNot('DOC_NOTIFY_FOR_INCOMPLETE_BUSINESS_DOC', 2678400);


/**
 * Колко папки от последно отворените да се показват при търсене
 */
defIfNot('DOC_SEARCH_FOLDER_CNT', 5);


/**
 * Колко колко документа максимално да се показват
 */
defIfNot('DOC_SEARCH_LIMIT', 1000);


/**
 * Време на отклонения за поправка на документ (в секунди)
 * Докумените създадени преди това време ще се проверяват за поправка
 */
defIfNot('DOC_REPAIR_DELAY', 120);


/**
 * Дали да се проверяват всички документи за поправка
 */
defIfNot('DOC_REPAIR_ALL', 'yes');


/**
 * Задължително показване на документи -> В началото на нишката
 */
defIfNot('DOC_SHOW_DOCUMENTS_BEGIN', 3);


/**
 * Нотификация за добавен документ в нишка
 */
defIfNot('DOC_NOTIFY_FOR_NEW_DOC', 'default');


/**
 * Нотификация за добавен контиран документ
 */
defIfNot('DOC_NOTIFY_FOR_CONTO', 'default');


/**
 * Известяване на споделените потребители на папка
 */
defIfNot('DOC_NOTIFY_FOLDERS_SHARED_USERS', 'default');


/**
 * Нотификация за създадени чакащи документи
 */
defIfNot('DOC_NOTIFY_PENDING_DOC', 'default');


/**
 * Известяване при нов документ->Задължително
 */
defIfNot('DOC_NOTIFY_NEW_DOC_TYPE', '');


/**
 * Известяване при нов документ->Никога
 */
defIfNot('DOC_STOP_NOTIFY_NEW_DOC_TYPE', '');


/**
 * Задължително показване на документи -> В края на нишката
 */
defIfNot('DOC_SHOW_DOCUMENTS_END', 3);


/**
 * Задължително показване на документи -> По-нови от
 */
defIfNot('DOC_SHOW_DOCUMENTS_LAST_ON', 259200); // 3 дни


/**
 * След колко символа да не се показва текста
 */
defIfNot('DOC_HIDE_TEXT_AFTER_LENGTH', 20000);


/**
 * Колко секунди в кеша максимално да живеят документите
 */
defIfNot('DOC_CACHE_LIFETIME', 5 * 60);


/**
 * Стрингове, които да се замества с точка при повторение
 */
defIfNot('DOC_STRING_FOR_REDUCE', 'За,Отн,Относно,回复,转发,SV,VS,VS,VL,RE,FW,FRW,TR,AW,WG,ΑΠ,ΣΧΕΤ,ΠΡΘ,R,RIF,I,SV,FS,SV,VB,RE,RV,RES,ENC,Odp,PD,YNT,İLT');


/**
 * Потребители, които ще се нотифицират за отворени теми в папки на оттеглени потребители
 * По-подразбиране са всички администратори
 */
defIfNot('DOC_NOTIFY_FOR_OPEN_IN_REJECTED_USERS', '');


/**
 * След колко време да се изтриват оттеглените нишки
 */
defIfNot('DOC_DELETE_REJECTED_THREADS_PERIOD', core_DateTime::SECONDS_IN_MONTH);


/**
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с DOC
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_Setup extends core_ProtoSetup
{
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'doc_Folders';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Документи и папки';
    
    
    /**
     * Описание на системните действия
     */
    public $systemActions = array(
        array('title' => 'Ключови думи', 'url' => array('doc_Containers', 'repairKeywords', 'ret_url' => true), 'params' => array('title' => 'Ре-индексиране на документите')),
        array('title' => 'Поправки', 'url' => array('doc_Containers', 'repair', 'ret_url' => true), 'params' => array('title' => 'Поправка на развалени документи'))
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        // Кой пакет да използваме за генериране на PDF от HTML ?
        'BGERP_PDF_GENERATOR' => array('class(interface=doc_ConvertToPdfIntf,select=title)', 'mandatory, caption=Кой пакет да се използва за генериране на PDF?->Пакет'),
        'DOC_CHART_ADAPTER' => array('class(interface=doc_chartAdapterIntf,select=title, allowEmpty)', 'caption=Кой пакет да се използва за показване на графики?->Пакет, placeholder=Автоматично'),
        'DOC_NOTIFY_FOR_INCOMPLETE_FROM' => array('time', 'caption=Период за откриване на незавършени действия с документи->Начало,unit=преди проверката'),
        'DOC_NOTIFY_FOR_INCOMPLETE_TO' => array('time', 'caption=Период за откриване на незавършени действия с документи->Край,unit=преди проверката'),
        'DOC_NOTIFY_FOR_INCOMPLETE_BUSINESS_DOC' => array('time', 'caption=Период за откриване на неконтирани бизнес документи->Край,unit=преди проверката'),
        
        'DOC_REPAIR_ALL' => array('enum(yes=Да (бавно), no=Не)', 'caption=Дали да се проверяват всички документи за поправка->Избор'),
        'DOC_SEARCH_FOLDER_CNT' => array('int(Min=0)', 'caption=Колко папки от последно отворените да се показват при търсене->Брой'),
        'DOC_SEARCH_LIMIT' => array('int(Min=0)', 'caption=Колко документ/нишки да се показват при търсене->Брой'),
        
        'DOC_NOTIFY_FOR_NEW_DOC' => array('enum(default=Автоматично, yes=Винаги, no=Никога)', 'caption=Известяване за добавен документ в нишка->Избор, customizeBy=powerUser'),
        'DOC_NOTIFY_NEW_DOC_TYPE' => array('keylist(mvc=core_Classes, select=title)', 'caption=Известяване при нов документ->Задължително, customizeBy=powerUser, optionsFunc=doc_Setup::getAllDocClassOptions'),
        'DOC_STOP_NOTIFY_NEW_DOC_TYPE' => array('keylist(mvc=core_Classes, select=title)', 'caption=Известяване при нов документ->Никога, customizeBy=powerUser, optionsFunc=doc_Setup::getAllDocClassOptions'),
        'DOC_NOTIFY_FOR_CONTO' => array('enum(default=Автоматично, yes=Винаги, no=Никога)', 'caption=Известяване при контиране->Избор, customizeBy=powerUser'),
        'DOC_NOTIFY_FOLDERS_SHARED_USERS' => array('enum(default=Автоматично, yes=Винаги, no=Никога)', 'caption=Известяване на споделените потребители на папка->Избор, customizeBy=powerUser'),
        'DOC_NOTIFY_PENDING_DOC' => array('enum(default=Автоматично, yes=Винаги, no=Никога)', 'caption=Известяване за създадени документи->Заявки, customizeBy=powerUser'),
        
        'DOC_SHOW_DOCUMENTS_BEGIN' => array('int(Min=0)', 'caption=Задължително показване на документи в нишка->В началото, customizeBy=user'),
        'DOC_SHOW_DOCUMENTS_END' => array('int(Min=0)', 'caption=Задължително показване на документи в нишка->В края, customizeBy=user'),
        'DOC_SHOW_DOCUMENTS_LAST_ON' => array('time(suggestions=1 ден|3 дни|5 дни|1 седмица)', 'caption=Задължително показване на документи в нишка->По-нови от, customizeBy=user'),
        'DOC_HIDE_TEXT_AFTER_LENGTH' => array('int(min=0)', 'caption=Брой символи над които текста ще е скрит->Брой, customizeBy=user'),
        'DOC_CACHE_LIFETIME' => array('time(suggestions=0 мин.|2 мин.|3 мин.|4 мин.|5 мин.|6 мин.|7 мин.|8 мин.|9 мин.)', 'caption=Кеширане на документите->Време'),
        'DOC_NOTIFY_FOR_OPEN_IN_REJECTED_USERS' => array('userList', 'caption=Известяване за отворени теми в папки на оттеглени потребители->Потребители'),
        'DOC_DELETE_REJECTED_THREADS_PERIOD' => array('time(suggestions=15 дни|1 месец|6 месеца|1 година)', 'caption=След колко време да се изтриват оттеглените нишки->Време'),
    );
    
    
    // Инсталиране на мениджърите
    public $managers = array(
        'doc_UnsortedFolders',
        'doc_Folders',
        'doc_Threads',
        'doc_Containers',
        'doc_Folders',
        'doc_Comments',
        'doc_Notes',
        'doc_PdfCreator',
        'doc_ThreadUsers',
        'doc_Files',
        'doc_TplManager',
        'doc_HiddenContainers',
        'doc_DocumentCache',
        'doc_Likes',
        'doc_ExpensesSummary',
        'doc_Prototypes',
        'doc_UsedInDocs',
        'doc_View',
        'doc_Linked',
        'doc_LinkedTemplates',
        'doc_FolderResources',
    );
    
    
    /**
     * Нагласяне на крон
     */
    public $cronSettings = array(
        array(
            'systemId' => doc_Threads::DELETE_SYSTEM_ID,
            'description' => 'Изтриване на оттеглени и документи нишки',
            'controller' => 'doc_Threads',
            'action' => 'DeleteThread',
            'period' => 5,
            'timeLimit' => 200,
        ),
        array(
            'systemId' => 'deleteOldObject',
            'description' => 'Изтриване на остарелите информации за обектите в документ',
            'controller' => 'doc_UsedInDocs',
            'action' => 'deleteOldObject',
            'period' => 1440,
            'offset' => 66,
            'timeLimit' => 120,
        ),
        array(
            'systemId' => 'AutoClose',
            'description' => 'Автоматично затваряне на папки',
            'controller' => 'doc_Folders',
            'action' => 'autoClose',
            'period' => 1440,
            'offset' => 111,
            'timeLimit' => 400
        )
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'doc_reports_Docs,doc_reports_SearchInFolder,doc_reports_DocsByRols, doc_ExpandComments';
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html .= core_Roles::addOnce('powerUser', null, 'system');
        
        // Добавяне на ролите за Ранг
        $rangRoles = array(
            
            // Изпълнителен член на екип. Достъпни са му само папките,
            // които са споделени или на които е собственик
            'executive',
            
            // Старши член на екип. Достъпни са му всички общи и всички екипни папки,
            // в допълнение към тези, на които е собственик или са му споделени
            'officer',
            
            // Ръководител на екип. Достъп до всички папки на екипа, без тези на 'ceo'
            'manager',
            
            // Ръководител на организацията. Достъпни са му всички папки и документите в тях
            'ceo',
        );
        
        foreach ($rangRoles as $role) {
            $inherit = trim('powerUser,' . $lastRole, ',');
            $lastRole = $role;
            $html .= core_Roles::addOnce($role, $inherit, 'rang');
        }
        
        // Роля за външен член на екип. Достъпни са му само папките,
        // които са споделени или на които е собственик
        $html .= core_Roles::addOnce('partner', null, 'rang');
        
        $html = parent::install();
        
        // Ако няма нито една роля за екип, добавяме екип за главна квартира
        $newTeam = false;
        
        if (!core_Roles::fetch("#type = 'team'")) {
            $html .= core_Roles::addOnce(BGERP_ROLE_HEADQUARTER, null, 'team');
            $newTeam = true;
        }
        
        // Ако няма потребител с роля 'ceo', добавяме я към всички администратори
        if (!count(core_Users::getByRole('ceo'))) {
            $admins = core_Users::getByRole('admin');
            
            if (count($admins)) {
                foreach ($admins as $userId) {
                    $uTitle = core_Users::getTitleById($userId);
                    core_Users::addRole($userId, 'ceo');
                    $html .= "<li style='color:green'>На потребителя <b>{$uTitle}</b> e добавен ранг <b>ceo</b></li>";
                    
                    if ($newTeam) {
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
        $html .= bgerp_Menu::addOnce(1.22, 'Документи', 'Всички', 'doc_Folders', 'default', 'user');
        
        return $html;
    }
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'currency';
    
    
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
     *
     * @param type_Keylist $type
     * @param array        $otherParams
     *
     * @return array
     */
    public static function getAllDocClassOptions($type, $otherParams = array())
    {
        return core_Classes::getOptionsByInterface('doc_DocumentIntf', 'title');
    }
}
