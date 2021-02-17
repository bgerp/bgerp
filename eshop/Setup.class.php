<?php


/**
 * Колко секунди да се кешира съдържанието за не PowerUsers
 */
defIfNot('ESHOP_BROWSER_CACHE_EXPIRES', 3600);


/**
 * Показване на навигация
 */
defIfNot('ESHOP_SHOW_NAVIGATION', 'yes');


/**
 * Име на кошницата във външната част
 */
defIfNot('ESHOP_CART_EXTERNAL_NAME', 'Количка');


/**
 * Текст в магазина ако артикулът не е наличен
 */
defIfNot('ESHOP_NOT_IN_STOCK_TEXT', 'Няма наличност');


/**
 * Дефолтен шаблон за онлайн продажби на български
 */
defIfNot('ESHOP_SALE_DEFAULT_TPL_BG', '');


/**
 * Дефолтен шаблон за онлайн продажби на английски
 */
defIfNot('ESHOP_SALE_DEFAULT_TPL_EN', '');


/**
 * Кое поле да е задължително при изпращане на запитване или поръчка във външната част
 */
defIfNot('ESHOP_MANDATORY_CONTACT_FIELDS', 'person');


/**
 * Сол за даване на достъп до кошница
 */
defIfNot('ESHOP_CART_ACCESS_SALT', '');


/**
 * Брой артикули на страница
 */
defIfNot('ESHOP_PRODUCTS_PER_PAGE', '20');


/**
 * Сумиране на рейтингите от кога
 */
defIfNot('ESHOP_RATINGS_OLDER_THEN',  12 * core_DateTime::SECONDS_IN_MONTH);


/**
 * Максимална бройка свързани артикули
 */
defIfNot('ESHOP_MAX_NEAR_PRODUCTS', '12');


/**
 * Запитванията и онлайн поръчките, може да се пускат от
 */
defIfNot('ESHOP_MANDATORY_INQUIRY_CONTACT_FIELDS', 'person');


/**
 * Изисквуемо ли е полето за ЕГН в запитванията и онлайн магазина
 */
defIfNot('ESHOP_MANDATORY_EGN', 'no');


/**
 * Изисквуемо ли е полето за ЕИК в запитванията и онлайн магазина
 */
defIfNot('ESHOP_MANDATORY_UIC_ID', 'no');


/**
 * Изисквуемо ли е полето за ДДС № в запитванията и онлайн магазина
 */
defIfNot('ESHOP_MANDATORY_VAT_ID', 'no');




/**
 * class cat_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с артикулите
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class eshop_Setup extends core_ProtoSetup
{
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'eshop_Groups';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Уеб магазин';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'cms=0.1';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'eshop_Groups',
        'eshop_Products',
        'eshop_Settings',
        'eshop_ProductDetails',
        'eshop_Carts',
        'eshop_CartDetails',
        'migrate::updateProductButtons',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'eshop';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.55, 'Сайт', 'Е-маг', 'eshop_Groups', 'default', 'ceo, eshop'),
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'ESHOP_BROWSER_CACHE_EXPIRES' => array('time', 'caption=Кеширане в браузъра->Време'),
        'ESHOP_SHOW_NAVIGATION' => array('enum(yes=С навигация,no=Без навигация)', 'caption=Показване на навигация на групите->Избор'),
        'ESHOP_CART_EXTERNAL_NAME' => array('varchar', 'caption=Стрингове във външната част->Кошница'),
        'ESHOP_NOT_IN_STOCK_TEXT' => array('varchar', 'caption=Стрингове във външната част->Липса на наличност'),
        'ESHOP_SALE_DEFAULT_TPL_BG' => array('key(mvc=doc_TplManager,allowEmpty)', 'caption=Шаблон за онлайн продажба->Български,optionsFunc=sales_Sales::getTemplateBgOptions'),
        'ESHOP_SALE_DEFAULT_TPL_EN' => array('key(mvc=doc_TplManager,allowEmpty)', 'caption=Шаблон за онлайн продажба->Английски,optionsFunc=sales_Sales::getTemplateEnOptions'),
        'ESHOP_CART_ACCESS_SALT' => array('varchar', 'caption=Даване на достъп за присвояване на количка->Сол'),
        'ESHOP_PRODUCTS_PER_PAGE' => array('int(Min=0)', 'caption=Брой артикули на страница в групата->Брой'),
        'ESHOP_RATINGS_OLDER_THEN' => array('time', 'caption=Изчисляване на рейтинги за продажба->Изчисляване от'),
        'ESHOP_MAX_NEAR_PRODUCTS' => array('int(min=0)', 'caption=Максимален брой свързани артикули->Брой,callOnChange=eshop_Setup::updateNearProducts'),
        'ESHOP_MANDATORY_CONTACT_FIELDS' => array('enum(company=Фирма,person=Лице,both=Двете)', 'caption=Задължителни контактни данни за количката->Поле'),
        'ESHOP_MANDATORY_INQUIRY_CONTACT_FIELDS' => array('enum(company=Фирми,person=Частни лица)', 'caption=Запитвания от външната част->Допускат се за'),
        'ESHOP_MANDATORY_EGN' => array('enum(no=Не се изисква,optional=Опционално,mandatory=Задължително)', 'caption=Запитвания и онлайн поръчики->ЕГН'),
        'ESHOP_MANDATORY_UIC_ID' => array('enum(no=Не се изисква,optional=Опционално,mandatory=Задължително)', 'caption=Запитвания и онлайн поръчики->ЕИК'),
        'ESHOP_MANDATORY_VAT_ID' => array('enum(no=Не се изисква,optional=Опционално,mandatory=Задължително)', 'caption=Запитвания и онлайн поръчики->ДДС №'),
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'eshop_driver_BankPayment';
    
    
    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Check Draft Carts',
            'description' => 'Обхождане на черновите колички',
            'controller' => 'eshop_Carts',
            'action' => 'CheckDraftCarts',
            'period' => 60,
            'offset' => 30,
            'timeLimit' => 100
        ),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('eshopImages', 'Илюстрации в емаг', 'jpg,jpeg,png,bmp,gif,image/*', '10MB', 'user', 'every_one');
        
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->installPlugin('Разширяване на външната част за онлайн магазина', 'eshop_plg_External', 'cms_page_External', 'private');
        $html .= $Plugins->installPlugin('Разширяване на потребителите свързана с външната част', 'eshop_plg_Users', 'core_Users', 'private');
        $html .= $Plugins->installPlugin('Разширяване на артикулите вързани с онлайн магазина', 'eshop_plg_ProductSync', 'cat_Products', 'private');
        
        return $html;
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData($itr = '')
    {
        $res = parent::loadSetupData($itr);
        $config = core_Packs::getConfig('eshop');
        
        $tplArr = array();
        $tplArr[] = array('name' => 'Online sale', 'content' => 'eshop/tpl/OnlineSaleEn.shtml', 'lang' => 'en');
        $tplArr[] = array('name' => 'Онлайн продажба', 'content' => 'eshop/tpl/OnlineSaleBg.shtml', 'lang' => 'bg');
        $res .= doc_TplManager::addOnce('sales_Sales', $tplArr);
        
        // Поставяне на първия намерен шаблон на английски за дефолтен на продажбата
        if (strlen($config->ESHOP_SALE_DEFAULT_TPL_BG) === 0) {
            $templateBgId = doc_TplManager::fetchField("#name = 'Онлайн продажба'");
            core_Packs::setConfig('eshop', array('ESHOP_SALE_DEFAULT_TPL_BG' => $templateBgId));
        }
        
        // Поставяне на първия намерен шаблон на английски за дефолтен на продажбата
        if (strlen($config->ESHOP_SALE_DEFAULT_TPL_EN) === 0) {
            $templateEnId = doc_TplManager::fetchField("#name = 'Online sale'");
            core_Packs::setConfig('eshop', array('ESHOP_SALE_DEFAULT_TPL_EN' => $templateEnId));
        }
        
        return $res;
    }
    
    
    /**
     * Метод изпълняващ се след промяна на константата за брой близки артикули
     * 
     * @param core_Type $Type
     * @param mixed $oldValue
     * @param mixed $newValue
     * 
     * @return string
     */
    public static function updateNearProducts($Type, $oldValue, $newValue)
    {
        eshop_Products::saveNearProducts();
        
        return tr('Преизчисляване на свързаните е-артикули');
    }
    
    
    /**
     * Миграция обновяваща полето действия в детайла на е-артикула
     */
    public function updateProductButtons()
    {
        $Details = cls::get('eshop_ProductDetails');
        $Details->setupMvc();
        
        if(!$Details->count()) return;
        
        $save = array();
        $query = $Details->getQuery();
        $query->where("#action IS NULL OR #action = ''");
        while($rec = $query->fetch()){
            $rec->action = 'buy';
            $save[$rec->id] = $rec;
        }
        
        if(countR($save)){
            $Details->saveArray($save, 'id,action');
        }
    }
}
