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
 * Текст в магазина ако артикулът е наличен във външен склад
 */
defIfNot('ESHOP_REMOTE_IN_STOCK_TEXT', 'Наличен при партньор');


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
defIfNot('ESHOP_MANDATORY_CONTACT_FIELDS', 'both');


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
 * Дефолтна ценова политика
 */
defIfNot('ESHOP_DEFAULT_POLICY_ID', price_ListRules::PRICE_LIST_CATALOG);


/**
 * Дефолтен ДДС режим
 */
defIfNot('ESHOP_CHARGE_VAT_ID', '');


/**
 * Условия на доставка
 */
defIfNot('ESHOP_DEFAULT_DELIVERY_TERMS', '');


/**
 * Методи на плащане
 */
defIfNot('ESHOP_DEFAULT_PAYMENTS', '');


/**
 * Изтриване на стари любими артикули
 */
defIfNot('ESHOP_ANONYM_FAVOURITE_DELETE_INTERVAL', '604800');


/**
 * Изтриване на стари любими артикули
 */
defIfNot('ESHOP_ANONYM_FAVOURITE_DELETE_INTERVAL', '604800');


/**
 * Колко време след като е свършил крайния срок за онлайн продажбите на артикула той да се махне
 */
defIfNot('ESHOP_REMOVE_PRODUCTS_WITH_ENDED_SALES_DELAY', '43200');


/**
 * Показване на колоната за опаковката в Е-маг ако са само услуги
 */
defIfNot('ESHOP_PUBLIC_PRODUCT_SHOW_PACK_COLUMN_IF_ONLY_SERVICES', 'yes');


/**
 * Показване на основната илюстрация в онлайн магазина
 */
defIfNot('ESHOP_PRODUCT_IMG_LOGIC', 'rotation');


/**
 * Маршрутите за доставка до следващите колко дни да се показвам в количката
 */
defIfNot('ESHOP_SHOW_ROUTES_IN_NEXT_DAYS', '7');


/**
 * До кога да се приемат заявки за доставка по маршрути за следващия работен ден->Час
 */
defIfNot('ESHOP_TOMORROW_DELIVERY_DEADLINE', '15:00');


/**
 * Показване винаги разпънати групи в навигацията->Избор
 */
defIfNot('ESHOP_SHOW_EXPANDED_GROUPS_IN_NAV', 'no');


/**
 * "Очаква се доставка" в онлайн магазина се показва само ако очакваната доставка е със срок до->Избор
 */
defIfNot('ESHOP_SHOW_EXPECTED_DELIVERY_MIN_TIME', 60 * 60 * 24 * 3);


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
        'eshop_Favourites',
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
        'ESHOP_REMOTE_IN_STOCK_TEXT' => array('varchar', 'caption=Стрингове във външната част->Във външен склад'),
        'ESHOP_SALE_DEFAULT_TPL_BG' => array('key(mvc=doc_TplManager,allowEmpty)', 'caption=Шаблон за онлайн продажба->Български,optionsFunc=sales_Sales::getTemplateBgOptions'),
        'ESHOP_SALE_DEFAULT_TPL_EN' => array('key(mvc=doc_TplManager,allowEmpty)', 'caption=Шаблон за онлайн продажба->Английски,optionsFunc=sales_Sales::getTemplateEnOptions'),
        'ESHOP_CART_ACCESS_SALT' => array('varchar', 'caption=Даване на достъп за присвояване на количка->Сол'),
        'ESHOP_PRODUCTS_PER_PAGE' => array('int(Min=0)', 'caption=Брой артикули на страница в групата->Брой'),
        'ESHOP_RATINGS_OLDER_THEN' => array('time', 'caption=Изчисляване на рейтинги за продажба->Изчисляване от'),
        'ESHOP_MAX_NEAR_PRODUCTS' => array('int(min=0)', 'caption=Максимален брой свързани артикули->Брой,callOnChange=eshop_Setup::updateNearProducts'),
        'ESHOP_MANDATORY_CONTACT_FIELDS' => array('enum(company=Фирми (задължително фактуриране),both=Фирми и лица (опционално фактуриране))', 'caption=Онлайн поръчки->Допускат се за'),
        'ESHOP_MANDATORY_INQUIRY_CONTACT_FIELDS' => array('enum(company=Фирми,person=Частни лица)', 'caption=Запитвания от външната част->Допускат се за'),
        'ESHOP_MANDATORY_EGN' => array('enum(no=Не се изисква,optional=Опционално,mandatory=Задължително)', 'caption=Запитвания и онлайн поръчки->ЕГН'),
        'ESHOP_MANDATORY_UIC_ID' => array('enum(no=Не се изисква,optional=Опционално,mandatory=Задължително)', 'caption=Запитвания и онлайн поръчки->ЕИК'),
        'ESHOP_MANDATORY_VAT_ID' => array('enum(no=Не се изисква,optional=Опционално,mandatory=Задължително)', 'caption=Запитвания и онлайн поръчки->ДДС №'),
        'ESHOP_DEFAULT_POLICY_ID' => array('key(mvc=price_Lists,select=title)', 'caption=Дефолти в настройките а онлайн магазина->Политика'),
        'ESHOP_DEFAULT_DELIVERY_TERMS' => array('keylist(mvc=cond_DeliveryTerms,select=codeName)', 'caption=Дефолти в настройките а онлайн магазина->Условия на доставка'),
        'ESHOP_DEFAULT_PAYMENTS' => array('keylist(mvc=cond_PaymentMethods,select=title)', 'caption=Дефолти в настройките а онлайн магазина->Методи на плащане'),
        'ESHOP_ANONYM_FAVOURITE_DELETE_INTERVAL' => array('time', 'caption=Изтриване на любимите артикули на нерегистрирани потребители->Време'),
        'ESHOP_REMOVE_PRODUCTS_WITH_ENDED_SALES_DELAY' => array('time', 'caption=Премахване на артикули от Е-маг след изтичане на онлайн продажбата->Премахване след'),
        'ESHOP_PUBLIC_PRODUCT_SHOW_PACK_COLUMN_IF_ONLY_SERVICES' => array('enum(yes=Да,no=Не)', 'caption=Показване на колоната за опаковката в Е-маг ако са само услуги->Избор'),
        'ESHOP_PRODUCT_IMG_LOGIC' => array('enum(rotation=Ротация на илюстрациите,first=Първата илюстрация)', 'caption=Как се определя основната илюстрация на артикула при показване в Е-маг->Избор'),
        'ESHOP_SHOW_ROUTES_IN_NEXT_DAYS' => array('int(min=0)', 'caption=Показване на маршрутите за доставка за следващите->Дни'),
        'ESHOP_TOMORROW_DELIVERY_DEADLINE' => array('hour', 'caption=До кога да се приемат заявки за доставка по маршрути за следващия работен ден->Час'),
        'ESHOP_SHOW_EXPANDED_GROUPS_IN_NAV' => array('enum(yes=Да,no=Не)', 'caption=Показване винаги разпънати групи в навигацията->Избор'),
        'ESHOP_SHOW_EXPECTED_DELIVERY_MIN_TIME' => array('time', 'caption="Очаква се доставка" в онлайн магазина се показва само ако очакваната доставка е със срок до->Избор'),
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'eshop_driver_BankPayment, eshop_reports_ReferersOfCarts';
    
    
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

        array(
            'systemId' => 'Deleta Favourite Products In Eshop',
            'description' => 'Изтриване на любимите артикули в е-маг',
            'controller' => 'eshop_Favourites',
            'action' => 'DeleteOldFavourites',
            'period' => 1440,
            'offset' => 60,
            'timeLimit' => 100
        ),

        array(
            'systemId' => 'Remove Products From еshop',
            'description' => 'Премахване на артикули от е-маг',
            'controller' => 'eshop_ProductDetails',
            'action' => 'RemoveProductsFromEshop',
            'period' => 1440,
            'offset' => 60,
            'timeLimit' => 100
        ),

        array(
            'systemId' => 'Update Eshop Sellable Products',
            'description' => 'Преизчисляване на е-артикулите дали има детайли с цени',
            'controller' => 'eshop_Products',
            'action' => 'UpdateEshopSellableProducts',
            'period' => 1,
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
        $html .= $Bucket->createBucket('eshopImages', 'Илюстрации в емаг', 'jpg,jpeg,png,bmp,gif,image/*,heic,webp', '10MB', 'user', 'every_one');
        
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
}
