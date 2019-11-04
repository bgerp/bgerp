<?php


/**
 * Роля, която трябва да имат потребителите за да им се създаде корпоративен имейл
 */
defIfNot('EMAIL_ROLE_FOR_CORPORATE_EMAIL', 'powerUser');


/**
 * Максимално време за еднократно фетчване на писма
 */
defIfNot('EMAIL_MAX_FETCHING_TIME', 30);


/**
 * Минимална дължина над която ще се проверява за баркод при сваляне на файл
 */
defIfNot('EMAIL_MIN_FILELEN_FOR_BARCOCE', 15000);


/**
 * Максимална дължина пок която ще се проверява за баркод при сваляне на файл
 */
defIfNot('EMAIL_MAX_FILELEN_FOR_BARCOCE', 250000);


/**
 * Разширения, в които ще се търси баркод при сваляне на имейл
 */
defIfNot('EMAIL_ALLOWED_EXT_FOR_BARCOCE', 'pdf,tif,tiff,jpg,jpeg');


/**
 * Максималната разрешена памет за използване
 */
defIfNot('EMAIL_MAX_ALLOWED_MEMORY', '838860800');


/**
 * Шаблон за име на папките, където отиват писмата от дадена държава и неподлежащи на
 * по-адекватно сортиране
 */
defIfNot('EMAIL_UNSORTABLE_COUNTRY', 'Несортирани - %s');


/**
 * Потребител, който ще е отговорник на несортираните имейли
 */
defIfNot('EMAIL_UNSORTABLE_INCHARGE', '');


/**
 * Максималното време за изчакване на буфера
 */
defIfNot('EMAIL_POP3_TIMEOUT', 2);


/**
 * Максималната големина на файловете, които ще се приемат за CID
 * 10kB
 */
defIfNot('EMAIL_MAXIMUM_CID_LEN', 10240);


/**
 * След колко време (в секунди) след първото изпращане към един имейл да се взема в предвид, че е изпратено преди (Повторно изпращане)
 *
 * По подразбиране 12 часа
 */
defIfNot('EMAIL_RESENDING_TIME', '43200');


/**
 * Максимална дължина на текстовата част на входящите имейли
 */
defIfNot('EMAIL_MAX_TEXT_LEN', '1000000');


/**
 * Дали манипулатора на нишката да е в началото на събджекта на писмото
 */
defIfNot('EMAIL_THREAD_HANDLE_POS', 'BEFORE_SUBJECT');


/**
 * Ограничава рутирането по папки до папките на контрагент и "Несортирани - %"
 */
defIfNot('EMAIL_RESTRICT_ROUTE', 'no');


/**
 * След колко време да не се използват обръщеният по имейл за нова нишка
 */
defIfNot('EMAIL_SALUTATION_EMAIL_TIME_LIMIT', 7776000); // 60*60*24*90 - 90 дни


/**
 * Какъв тип да е генерирания манипулатор за събджект на имейл
 * t0 - <123456>
 * t1 - EML234SGR
 * t2 - #123496
 */
defIfNot('EMAIL_THREAD_HANDLE_TYPE', 'type1');


/**
 * Какъв какви типове манипулатори за събджект на имейл се
 * с минали периоди
 * t0 - <123456> (номер на нишка)
 * t1 - EML234SGR (манипулатор на документ + защита)
 * t2 - #123496 (номер на нишка + защита)
 */
defIfNot('EMAIL_THREAD_HANDLE_LEGACY_TYPES', 'type0');


/**
 * Максимален размер на примкачените файлове при изпращане на имейл
 * 20MB
 */
defIfNot('EMAIL_MAX_ATTACHED_FILE_LIMIT', 20971520);


/**
 * Имейла по подразбиране, при изпращане
 */
defIfNot('EMAIL_DEFAULT_SENT_INBOX', '');


/**
 * Автоматично попълване на имейлите в полето копие
 */
defIfNot('EMAIL_AUTO_FILL_EMAILS_FROM_CC', 0);


/**
 * Автоматично попълване на имейлите в полето До
 */
defIfNot('EMAIL_AUTO_FILL_EMAILS_FROM_TO', 0);


/**
 * Хедъра на имейла на текстовата част, който се генерира автоматично при създаване на изходящ имейл
 */
defIfNot('EMAIL_OUTGOING_HEADER_TEXT', '[#hello#] [#salutation#] [#name#]');


/**
 * Хедъра на имейла на текстовата част, който се генерира автоматично при създаване на изходящ имейл - на английски
 */
defIfNot('EMAIL_OUTGOING_HEADER_TEXT_EN', '[#hello#] [#salutation#] [#name#]');


/**
 * Футъра на имейла на текстовата част, който се генерира автоматично при създаване на изходящ имейл
 */
defIfNot('EMAIL_OUTGOING_FOOTER_TEXT', "Сърдечни поздрави,\n[#name#]\n[#company#]\n[#position#]\nТел.: [#tel#]\nФакс: [#fax#]\n[#email#]\n[#website#]");


/**
 * Футъра на имейла на текстовата част, който се генерира автоматично при създаване на изходящ имейл - на английски
 */
defIfNot('EMAIL_OUTGOING_FOOTER_TEXT_EN', "Best regards,\n[#name#]\n[#company#]\n[#position#]\nTel.: [#tel#]\nFax: [#fax#]\n[#email#]\n[#website#]");


/**
 * Текст по подразбиране при отговор на имейл
 */
defIfNot('EMAIL_INCOMINGS_DEFAULT_EMAIL_BODY', 'Благодаря за имейла от [#DATETIME#]');


/**
 * Текст по подразбиране при отговор на имейл - на английски
 */
defIfNot('EMAIL_INCOMINGS_DEFAULT_EMAIL_BODY_EN', 'Thanks for the email on [#DATETIME#]');


/**
 * Текст по подразбиране при препращане на имейл
 */
defIfNot('EMAIL_FORWARDING_DEFAULT_EMAIL_BODY_FORWARDING', 'Моля запознайте се с препратения имейл [#MSG#]');


/**
 * Текст по подразбиране при препращане на имейл - на английски
 */
defIfNot('EMAIL_FORWARDING_DEFAULT_EMAIL_BODY_FORWARDING_EN', 'Please read the forwarded email [#MSG#]');


/**
 * Имейл домейни за подменяне
 */
defIfNot('EMAIL_REPLACE_DOMAINS', '');


/**
 * Имейл до кото няма да се праща
 */
defIfNot('EMAIL_STOP_SEND_TO', 'no-reply@*,noreply@*');


/**
 * Хедъри, които ще се проверяват за спам скоре
 */
defIfNot('EMAIL_CHECK_SPAM_SCORE_HEADERS', 'x-spam-status,x-spam-score');


/**
 * Стойност, над която имейлите ще се приемат за спам и ще се оттеглят
 */
defIfNot('EMAIL_HARD_SPAM_SCORE', 7);


/**
 * Стойност, над която имейлите ще се приемат за спам и ще се оттеглят
 */
defIfNot('EMAIL_REJECT_SPAM_SCORE', 4);


/**
 * Дали да се показва нишката с имейлите във външната част
 */
defIfNot('EMAIL_SHOW_THREAD_IN_EXTERNAL', 'yes');


/**
 * Имейли изпратени на какъв език да се превеждат?
 */
defIfNot('EMAIL_INCOMINGS_TRANSLATE_LG', '');


/**
 * class email_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с 'email'
 *
 *
 * @category  bgerp
 * @package   email
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class email_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'email_Incomings';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Управление на входящи имейл сметки и вътрешни кутии';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'fileman=0.1,doc=0.1';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        // Максимално време за еднократно фетчване на писма
        'EMAIL_MAX_FETCHING_TIME' => array('time(suggestions=1 мин.|2 мин.|3 мин.)', 'mandatory, caption=Максимално време за получаване на имейли в една сесия->Време'),
        
        // Максималното време за изчакване на буфера
        'EMAIL_POP3_TIMEOUT' => array('time(suggestions=1 сек.|2 сек.|3 сек.)', 'mandatory, caption=Таймаут на POP3 сокета->Време'),
        
        // Максималната разрешена памет за използване
        'EMAIL_MAX_ALLOWED_MEMORY' => array('fileman_FileSize', 'mandatory, caption=Максималната разрешена памет за използване при парсиране на имейли->Размер, suggestions=100 MB|200 MB|400 MB|800 MB|1200 MB'),
        
        // Шаблон за име на папки
        'EMAIL_UNSORTABLE_COUNTRY' => array('varchar', 'mandatory, caption=Шаблон за име на папки с несортирани имейли->Шаблон'),
        
        // Потребител, който ще е отговорник на несортираните имейли
        'EMAIL_UNSORTABLE_INCHARGE' => array('user(roles=powerUser, rolesForTeams=admin, rolesForAll=admin, allowEmpty)', 'caption=Потребител|*&comma;| който ще е отговорник на несортираните имейли->Потребител'),
        
        // Максималната големина на файловете, които ще се приемат за CID
        'EMAIL_MAXIMUM_CID_LEN' => array('int', 'caption=Максималната големина на файловете|*&comma;| които ще се приемат за вградени изображения->Размер'),
        
        // След колко време (в секунди) след първото изпращане към един имейл да се взема в предвид, че е изпратено преди (Повторно изпращане)
        'EMAIL_RESENDING_TIME' => array('time(suggestions=1 часа|2 часа|3 часа|5 часа|7 часа|10 часа|12 часа)', 'caption=Време от първото изпращане на имейл|*&comma;| след което се маркира "Преизпращане"->Време'),
        
        // Максимален брой символи в текстовата част на входящите имейли
        'EMAIL_MAX_TEXT_LEN' => array('int', 'caption=Максимален брой символи в текстовата част на входящите имейли->Символи'),
        
        // Тип на манипулатора в събджекта
        'EMAIL_THREAD_HANDLE_POS' => array('enum(BEFORE_SUBJECT=Преди събджекта,AFTER_SUBJECT=След събджекта)', 'caption=Манипулатор на нишка в събджект на имейл->Позиция'),
        
        // Позиция на манипулатора в събджекта
        'EMAIL_THREAD_HANDLE_TYPE' => array('enum(type0=Тип 0 <1234>,type1=Тип 1 #EML123DEW,type2=Тип 2 #123498,type3=Тип 3 <aftepod>)', 'caption=Манипулатор на нишка в събджект на имейл->Тип'),
        
        // Позиция на манипулатора в събджекта
        'EMAIL_THREAD_HANDLE_LEGACY_TYPES' => array('set(type0=Тип 0 <1234>,type1=Тип 1 #EML123DEW,type2=Тип 2 #123498,type3=Тип 3 <aftepod>)', 'caption=Манипулатор на нишка в събджект на имейл->Наследени,columns=1'),
        
        // Домейни за заменяне
        'EMAIL_REPLACE_DOMAINS' => array('varchar', 'caption=Домейни за заменяне->Списък,columns=1', array('hint' => 'OldDomain1=NewDomain1,OldDomain2=NewDomain2,...')),
        
        // Максимален размер на прикачените файлове и документи
        'EMAIL_MAX_ATTACHED_FILE_LIMIT' => array('fileman_FileSize', 'caption=Максимален размер на прикачените файлове/документи в имейла->Размер, suggestions=10 MB|20 MB|30 MB'),
        
        'EMAIL_INCOMINGS_TRANSLATE_LG' => array('keylist(mvc=drdata_Languages,select=languageName,allowEmpty)', 'caption=Превеждане на имейли от->Езици, customizeBy=powerUser'),
        
        'EMAIL_DEFAULT_SENT_INBOX' => array('key(mvc=email_Inboxes,select=email,allowEmpty)', 'caption=Изпращач на изходящите имейли->От, placeholder=Автоматично,customizeBy=powerUser, optionsFunc=email_Inboxes::getAllowedFromEmailOptions'),
        
        'EMAIL_AUTO_FILL_EMAILS_FROM_CC' => array('int', 'caption=Автоматично попълване на полето имейл от->Копие, customizeBy=powerUser, unit=бр.'),
        
        'EMAIL_AUTO_FILL_EMAILS_FROM_TO' => array('int', 'caption=Автоматично попълване на полето имейл от->До, customizeBy=powerUser, unit=бр.'),
        
        'EMAIL_RESTRICT_ROUTE' => array('enum(yes=Да, no=Не)', 'caption=Ограничаване на рутурането по папки->Избор'),
        
        'EMAIL_OUTGOING_HEADER_TEXT' => array('richtext(rows=5,bucket=Postings)', 'caption=Привет в изходящите имейли->На български, customizeBy=powerUser'),
        
        'EMAIL_OUTGOING_HEADER_TEXT_EN' => array('richtext(rows=5,bucket=Postings)', 'caption=Привет в изходящите имейли->На английски, customizeBy=powerUser'),
        
        'EMAIL_OUTGOING_FOOTER_TEXT' => array('richtext(rows=5,bucket=Postings)', 'caption=Подпис за изходящите имейли->На български, customizeBy=powerUser'),
        
        'EMAIL_OUTGOING_FOOTER_TEXT_EN' => array('richtext(rows=5,bucket=Postings)', 'caption=Подпис за изходящите имейли->На английски, customizeBy=powerUser'),
        
        'EMAIL_SALUTATION_EMAIL_TIME_LIMIT' => array('time(suggestions=30 дни|90 дни|180 дни)', 'caption=След колко време да не се използват обръщенията по имейл за нова нишка->Време'),
        
        'EMAIL_INCOMINGS_DEFAULT_EMAIL_BODY' => array('varchar', 'caption=Текст по подразбиране при отговор на имейл->На български, customizeBy=powerUser'),
        
        'EMAIL_INCOMINGS_DEFAULT_EMAIL_BODY_EN' => array('varchar', 'caption=Текст по подразбиране при отговор на имейл->На английски, customizeBy=powerUser'),
        
        'EMAIL_FORWARDING_DEFAULT_EMAIL_BODY_FORWARDING' => array('varchar', 'caption=Текст по подразбиране при препращане на имейл->На български, customizeBy=powerUser'),
        
        'EMAIL_FORWARDING_DEFAULT_EMAIL_BODY_FORWARDING_EN' => array('varchar', 'caption=Текст по подразбиране при препращане на имейл->На английски, customizeBy=powerUser'),
        
        'EMAIL_STOP_SEND_TO' => array('varchar', 'caption=Шаблон за имейли до които няма да се праща->Шаблон'),
        
        'EMAIL_CHECK_SPAM_SCORE_HEADERS' => array('varchar', 'caption=Проверка на СПАМ рейтинг->Хедъри'),
        
        'EMAIL_HARD_SPAM_SCORE' => array('varchar', 'caption=Проверка на СПАМ рейтинг->Твърд спам'),
        
        'EMAIL_REJECT_SPAM_SCORE' => array('varchar', 'caption=Проверка на СПАМ рейтинг->Оттегляне'),
        
        'EMAIL_SHOW_THREAD_IN_EXTERNAL' => array('enum(yes=Да, no=Не)', 'caption=Преглед на нишката с имейлите във външната част->Показване'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'email_Incomings',
        'email_Outgoings',
        'email_Inboxes',
        'email_Accounts',
        'email_Router',
        'email_Addresses',
        'email_FaxSent',
        'email_Filters',
        'email_Returned',
        'email_Receipts',
        'email_Spam',
        'email_Fingerprints',
        'email_Unparsable',
        'email_Salutations',
        'email_ThreadHandles',
        'email_SendOnTime',
        'email_SpamRules',
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'email_reports_Spam';
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'email, fax';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(1.23, 'Документи', 'Имейли', 'email_Outgoings', 'default', 'powerUser'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        //инсталиране на кофата
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('Email', 'Прикачени файлове в имейлите', null, '104857600', 'user', 'user');
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме
        $html .= $Plugins->installPlugin('UserInbox', 'email_UserInboxPlg', 'core_Users', 'private');
        
        // Инсталираме плъгина за преобразуване на имейлите в линкове
        $html .= $Plugins->installPlugin('EmailToLink', 'email_ToLinkPlg', 'type_Email', 'private');
        
        //
        // Инсталиране на плъгин за автоматичен превод на входящата поща
        //
        $html .= $Plugins->installPlugin('Email Translate', 'email_plg_IncomingsTranslate', 'email_Incomings', 'private');
        
        return $html;
    }
    
    
    /**
     * Проверяваме дали всичко е сетнато, за да работи пакета
     * Ако има грешки, връщаме текст
     */
    public function checkConfig()
    {
        if (!function_exists('imap_open')) {
            
            return 'Не е инсталиран IMAP модула на PHP';
        }
    }
    
    
    /**
     * Зареждане на данни
     */
    public function loadSetupData($itr = '')
    {
        $res = parent::loadSetupData($itr);
        
        $res .= $this->addOurImgData();
        
        return $res;
    }
    
    
    /**
     * Добавя img файлове за нашите файлове
     */
    protected function addOurImgData()
    {
        $oImgDataIdArr = array();
        
        $inc = getFullPath('email/data/OurImgFiles.txt');
        
        $content = file_get_contents($inc);
        
        $dataArr = explode("\n", $content);
        
        foreach ($dataArr as $name) {
            $name = trim($name);
            if (!$name) {
                continue;
            }
            list($md5, $len) = explode('|', $name);
            $dId = fileman_Data::fetchField(array("#fileLen = '[#1#]' AND #md5 = '[#2#]'", $len, $md5));
            if (!$dId) {
                continue;
            }
            $oImgDataIdArr[$dId] = $dId;
        }
        
        // Добавяме и логотата на фирмата
        Mode::push('text', 'xhtml');
        foreach (core_Lg::getLangs() as $lg => $lgVerb) {
            core_Lg::push($lg);
            $logoPath = bgerp_plg_Blank::getCompanyLogoThumbPath();
            core_Lg::pop();
            $nameAndExtArr = fileman::getNameAndExt($logoPath);
            $ext = $nameAndExtArr['ext'];
            if (!$ext) {
                $ext = 'png';
            }
            
            $fName = 'companyLogo' . ucfirst(strtolower($lg)) . '.' . $ext;
            
            $data = @file_get_contents($logoPath);
            
            if ($data) {
                $fh = fileman::absorbStr($data, 'Email', $fName);
            }
            
            if ($fh) {
                $dataId = fileman::fetchByFh($fh, 'dataId');
                $oImgDataIdArr[$dataId] = $dataId;
            }
        }
        
        Mode::pop('text');
        
        core_Permanent::set('ourImgEmailArr', $oImgDataIdArr, 10000000);
    }
}
