<?php
/**
 * Константи за брой дни след които статията се заключва за коментиране
 */
defIfNot('BLOGM_MAX_COMMENT_DAYS', 50 * 24 * 60 * 60);


/**
 *  Константа за тема по-подразбиране на блога
 */
defIfNot('BLOGM_DEFAULT_THEME', 'blogm_DefaultTheme');


/**
 *  Константа за продължителността на живота на бисквитките създадени от блога
 */
defIfNot('BLOGM_COOKIE_LIFETIME', '2592000');


/**
 *  Броя на статии, които да се показват
 */
defIfNot('BLOGM_ARTICLES_PER_PAGE', '5');


/**
 * Думи, срещани в спам моментари
 */
defIfNot('BLOGM_SPAM_WORDS', 'sex, xxx, porn, cam, teen, adult, cheap, sale, xenical, pharmacy, pills, prescription, опционы');


/**
 *  Заглавие на всичките статии
 */
defIfNot('BLOGM_ALL_ARTICLES_IN_PAGE_TITLE', 'Всички статии в блога');


/**
 *  Тип на статиите в блока
 */
defIfNot('BLOGM_TYPE', 'blog');


/**
 *  Показване на "Категории" над списъка с категории
 */
defIfNot('BLOGM_SHOW_CATEGORIES_ROOT', 'yes');


/**
 *  Заглавие на страницата с всички статии->Показване
 */
defIfNot('BLOGM_SHOW_ALL_ARTICLE_CAPTION', 'yes');


/**
 *  Дължина на краткото описание на статиите в списъка->Минимално
 */
defIfNot('BLOGM_ARTICLE_ANNOTATION_MIN_LENGTH', '350');


/**
 *  Дължина на краткото описание на статиите в списъка->Максимално
 */
defIfNot('BLOGM_ARTICLE_ANNOTATION_MAX_LENGTH', '450');


/**
 *  Показване винаги разпънати категориите в навигацията->Избор
 */
defIfNot('BLOGM_SHOW_EXPANDED_CATEGORIES_IN_NAV', 'no');


/**
 *  До колко пътища в категориите на блог статията да се показват->Брой
 */
defIfNot('BLOGM_ARTICLE_NAVIGATION_MAX_PATH', '0');


/**
 * class blogm_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с Блога
 *
 *
 * @category  bgerp
 * @package   blogm
 *
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class blogm_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'blogm_Articles';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'list';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Поддръжка на блог: категории, статии, коментари ...';
    
    
    /**
     * Описание на конфигурационните константи
     */
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'BLOGM_TYPE' => array('enum(blog=Блог,news=Новини)', 'caption=Тема по подразбиране в блога->Предназначение на модула'),
        'BLOGM_DEFAULT_THEME' => array('class(interface=blogm_ThemeIntf,select=title)', 'caption=Тема по подразбиране в блога->Тема'),
        'BLOGM_MAX_COMMENT_DAYS' => array('time(uom=days,suggestions=1 ден|2 дни|5 дни|1 седмица|2 седмици|30 дни|45 дни|50 дни)', 'caption=След колко време статията да се заключва за коментиране?->Време'),
        'BLOGM_ARTICLES_PER_PAGE' => array('int', 'caption=Колко статии да се показват на една страница->Брой'),
        'BLOGM_SPAM_WORDS' => array('text', 'caption=Определяне на SPAM рейтинг на коментар->Думи'),
        'BLOGM_ALL_ARTICLES_IN_PAGE_TITLE' => array('varchar', 'caption=Заглавие на страницата с всички статии->Заглавие'),
        'BLOGM_SHOW_ALL_ARTICLE_CAPTION' => array('enum(yes=Да,no=Не)', 'caption=Заглавие на страницата с всички статии->Показване'),
        'BLOGM_SHOW_CATEGORIES_ROOT' => array('enum(yes=Да,no=Не)', 'caption=Показване на "Категории" над списъка с категории->Избор'),
        'BLOGM_ARTICLE_ANNOTATION_MIN_LENGTH' => array('int', 'caption=Дължина на краткото описание на статиите в списъка->Минимално'),
        'BLOGM_ARTICLE_ANNOTATION_MAX_LENGTH' => array('int', 'caption=Дължина на краткото описание на статиите в списъка->Максимално'),
        'BLOGM_SHOW_EXPANDED_CATEGORIES_IN_NAV' => array('enum(yes=Да,no=Не)', 'caption=Показване винаги разпънати категориите в навигацията->Избор'),
        'BLOGM_ARTICLE_NAVIGATION_MAX_PATH' => array('int(min=0)', 'caption=До колко пътища в категориите на блог статията да се показват->Брой'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'blogm_Articles',
        'blogm_Categories',
        'blogm_Comments',
        'blogm_Links',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'blog';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.52, 'Сайт', 'Блог', 'blogm_Articles', 'list', 'cms, blog, admin, ceo'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket(blogm_Articles::FILE_BUCKET, 'Файлове към блог-статиите', '', '10MB', 'every_one', 'every_one');
        
        // Добавяме класа връщащ темата в core_Classes
        $html .= core_Classes::add('blogm_DefaultTheme');
        
        // Публикуване на чакащите блог статии по крон
        $rec = new stdClass();
        $rec->systemId = 'PublishPendingBlogArt';
        $rec->description = 'Публикуване на чакащите блог статии';
        $rec->controller = 'blogm_Articles';
        $rec->action = 'PublicPending';
        $rec->period = 5;
        $rec->offset = 3;
        $html .= core_Cron::addOnce($rec);

        // Изтриване на СПАМ коментари
        $rec = new stdClass();
        $rec->systemId = 'Delete SPAM comments';
        $rec->description = 'Изтриване на спам коментарите';
        $rec->controller = 'blogm_Comments';
        $rec->action = 'deleteSPAM';
        $rec->period = 24;
        $rec->offset = rand(1, 23);
        $rec->isRandOffset = true;
        $rec->delay = 0;
        $rec->timeLimit = 50;
        $html .= core_Cron::addOnce($rec);
        
        return $html;
    }
}
