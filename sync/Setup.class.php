<?php


/**
 * ID на тази система при свързване към експортираща bgERP система.
 * Попълва се само в импортиращата система - изпраща се при всяка заявка
 * към експортиращата, която намира съответния запис в sync_Settings
 * по поле offlineSysId.
 */
defIfNot('SYNC_SYS_ID', '');


/**
 * Парола на тази система при свързване към експортираща bgERP система.
 * Попълва се само в импортиращата система - изпраща се заедно със
 * SYNC_SYS_ID и се сравнява с поле pass в sync_Settings на експортиращата.
 */
defIfNot('SYNC_PASS', '');


/**
 * Групи на фирмите, чиито артикули могат да се синхронизират ръчно (importer-side проверка)
 */
defIfNot('SYNC_COMPANY_GROUPS', '');


/**
 * Име на собствената компания (тази за която ще работи bgERP)
 */
//defIfNot('SYNC_CRM_GROUPS', '');


/**
 * Име на собствената компания (тази за която ще работи bgERP)
 */
//defIfNot('SYNC_ESHOP_GROUPS', '');


/**
 * Име на собствената компания (тази за която ще работи bgERP)
 */
defIfNot('SYNC_CMS_DOMAINS', '');


/**
 * Държавата на собствената компания (тази за която ще работи bgERP)
 */
defIfNot('SYNC_EXPORT_URL', '');


/**
 * Доверени reverse proxy адреси/IPv4 CIDR мрежи за обработка на X-Forwarded-For
 */
defIfNot('SYNC_TRUSTED_PROXIES', '');


/**
 * Временен opt-in за client-first rollout към стар master без HTTPS.
 * Изключва се веднага след задаване на credentials и HTTPS.
 */
defIfNot('SYNC_ALLOW_LEGACY_HTTP', 'no');


/**
 * Експортиране на групи на артикулите->Групи
 */
//defIfNot('SYNC_PROD_GROUPS', '');


/**
 * Позволени IP-та за експорт
 */
//defIfNot('SYNC_EXPORT_ADDR', '');


/**
 * Колко процента да е себестойноста на импортирания артикул спрямо оферираната му цена
 */
defIfNot('SYNC_IMPORTED_PRODUCT_PRIMECOST_DISCOUNT', '0.18');


/**
 * В кой час от деня да се прави автоматичната синхронизация по крон (на импортиращата система).
 * Ако е празно - автоматичната синхронизация се изпълнява всяка минута.
 */
defIfNot('SYNC_AUTO_SYNC_TIME', '');


/**
 * Дали при импорт да се обновяват вече съществуващите записи (на импортиращата система).
 */
defIfNot('SYNC_UPDATE_EXISTING', 'no');


/**
 * Клас 'sync_Setup'
 *
 *
 * @category  bgerp
 * @package   sync
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sync_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';


    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'sync_Map';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
        
    /**
     * Описание на модула
     */
    public $info = 'Синхронизиране на данните между две bgERP системи';


    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'SyncRemoteStocks',
            'description' => 'Синхронизиране на отдалечените складови наличности',
            'controller' => 'sync_StoreStocks',
            'action' => 'SyncRemoteStocks',
            'period' => 1,
            'timeLimit' => 60
        ),
        array(
            'systemId' => 'SyncAutoSync',
            'description' => 'Синхронизиране на данните от експортиращата bgERP система',
            'controller' => 'sync_Settings',
            'action' => 'AutoSync',
            'period' => 1,
            // До 3600 s за remote export + достатъчно време за import/fixes.
            'timeLimit' => 7200
        ),
    );


    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'SYNC_EXPORT_URL' => array('url', 'caption=Импортиране->URL'),
        'SYNC_SYS_ID' => array('varchar(32)', 'caption=Идентификация пред експортиращата система->ID'),
        'SYNC_PASS' => array('password', 'caption=Идентификация пред експортиращата система->Парола'),
        'SYNC_TRUSTED_PROXIES' => array('varchar', 'caption=Експортиране->Доверени reverse proxy IP/CIDR'),
        'SYNC_ALLOW_LEGACY_HTTP' => array('enum(no=Не,yes=Да)', 'caption=Преходен режим->Разрешаване на HTTP без credentials'),
//        'SYNC_EXPORT_ADDR' => array('varchar', 'caption=Позволени IP-та за експорт->IP'),
        'SYNC_COMPANY_GROUPS' => array('keylist(mvc=crm_Groups, select=name, allowEmpty)', 'caption=Ръчна синхронизация на артикули->Групи фирми'),
//        'SYNC_PROD_GROUPS' => array('keylist(mvc=cat_Groups, select=name, allowEmpty)', 'caption=Експортиране на групи на артикулите->Групи'),
        'SYNC_IMPORTED_PRODUCT_PRIMECOST_DISCOUNT' => array('percent(min=0,max=1)', 'caption=Колко % под офертната цена да е себестойността на импортирания артикул->Процент'),
//        'SYNC_CRM_GROUPS' => array('keylist(mvc=crm_Groups, select=name, parentId=parentId)', 'caption=Група контрагенти при експортиране на лица->Група'),
        'SYNC_AUTO_SYNC_TIME' => array('hour', 'caption=Автоматична синхронизация по крон->Час, hint=Празно - всяка минута. При зададен час - веднъж дневно. Разпределяйте клиентите по различни часове'),
        'SYNC_UPDATE_EXISTING' => array('enum(no=Не, yes=Да)', 'caption=Автоматична синхронизация по крон->Обновяване на съществуващите записи'),
    );
    

    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'sync_Map',
        'sync_Settings',
        'sync_Stores',
        'sync_StoreStocks',
        'migrate::migrateSettings2638',
    );


    /**
     * Роли, които създава пакетът
     */
    public $roles = 'sync';
    
    
    /**
     * Връща описанието на web-константите
     *
     * @return array
     */
    public function getConfigDescription()
    {
        $description = parent::getConfigDescription();
        if (core_Packs::isInstalled('eshop')) {
//            $description['SYNC_ESHOP_GROUPS'] = array('keylist(mvc=eshop_Groups, select=name, allowEmpty)', 'caption=Експортиране на е-магазин->Групи, optionsFunc=sync_Eshop::getEshopGroups');

            if (core_Packs::isInstalled('cms')) {
                $description['SYNC_CMS_DOMAINS'] = array('text(rows=3)', 'caption=Съответствие на домейни->Домейни');
            }
        }
        
        return $description;
    }


    /**
     * Паролата към master-а в чист вид
     *
     * Стойността в конфигурацията е обфускирана, но приема и нешифрована -
     * например ако е зададена директно в инсталационния cfg файл.
     *
     * @return string
     */
    public static function getSyncPass()
    {
        return self::decodePass(self::get('PASS'));
    }


    /**
     * Разчита евентуално обфускирана парола
     *
     * @param mixed $value
     *
     * @return string
     */
    protected static function decodePass($value)
    {
        $value = (string) $value;
        if ($value === '') {

            return '';
        }

        $plain = plg_CryptStore::decrypt($value);

        return ($plain === false) ? $value : $plain;
    }


    /**
     * Проверява свързаните client настройки след input на конфигурационната форма
     */
    public function inputConfigDescriptionForm(&$configForm)
    {
        if (!$configForm->isSubmitted()) {

            return;
        }

        $sysId = trim((string) ($configForm->rec->SYNC_SYS_ID ?? ''));
        $submittedPass = $configForm->rec->SYNC_PASS ?? null;
        if ($submittedPass === null) {
            // type_Password връща NULL при "no_change". Връщаме съхранената
            // стойност такава, каквато е записана, иначе core_Packs я зануляваа.
            $stored = self::get('PASS');
            $configForm->rec->SYNC_PASS = $stored;
            $pass = self::decodePass($stored);
        } else {
            $pass = (string) $submittedPass;
        }

        if (($sysId === '') xor ($pass === '')) {
            $configForm->setError(
                'SYNC_SYS_ID,SYNC_PASS',
                'ID и парола се задават или изчистват едновременно'
            );
        }
        if (strlen($pass) > sync_Settings::MAX_PASS_LENGTH) {
            $configForm->setError(
                'SYNC_PASS',
                'Паролата може да бъде най-много ' . sync_Settings::MAX_PASS_LENGTH . ' знака'
            );
        }

        // core_Packs пази конфигурацията сериализирана в чист вид, затова
        // паролата се записва обфускирана със същия механизъм като sync_Settings::pass.
        if ($submittedPass !== null && $pass !== '') {
            $configForm->rec->SYNC_PASS = plg_CryptStore::encrypt($pass);
        }

        $trustedProxies = trim((string) ($configForm->rec->SYNC_TRUSTED_PROXIES ?? ''));
        if ($trustedProxies !== '') {
            foreach (preg_split('/[\s,;]+/', $trustedProxies) as $proxy) {
                $isExactIp = (bool) filter_var($proxy, FILTER_VALIDATE_IP);
                $isIpv4Cidr = false;
                if (strpos($proxy, '/') !== false) {
                    list($network, $prefix) = explode('/', $proxy, 2);
                    $isIpv4Cidr = (bool) filter_var(
                        $network,
                        FILTER_VALIDATE_IP,
                        FILTER_FLAG_IPV4
                    ) &&
                        ctype_digit((string) $prefix) &&
                        (int) $prefix >= 0 &&
                        (int) $prefix <= 32;
                }

                if (!$isExactIp && !$isIpv4Cidr) {
                    $configForm->setError(
                        'SYNC_TRUSTED_PROXIES',
                        "Невалиден trusted proxy IP/CIDR: {$proxy}"
                    );
                }
            }
        }

        $url = trim((string) ($configForm->rec->SYNC_EXPORT_URL ?? ''));
        if ($url !== '') {
            $urlParts = parse_url($url);
            $scheme = is_array($urlParts)
                ? strtolower((string) ($urlParts['scheme'] ?? ''))
                : '';
            if (!is_array($urlParts) ||
                !in_array($scheme, array('http', 'https'), true) ||
                empty($urlParts['host'])) {
                $configForm->setError(
                    'SYNC_EXPORT_URL',
                    'URL адресът към master-а трябва да е валиден HTTP или HTTPS адрес'
                );
            } elseif (($sysId !== '' || $pass !== '') && $scheme != 'https') {
                $configForm->setError(
                    'SYNC_EXPORT_URL',
                    'При credentials връзката към master-а трябва да е HTTPS'
                );
            } elseif ($scheme == 'http' &&
                ($configForm->rec->SYNC_ALLOW_LEGACY_HTTP ?? 'no') != 'yes') {
                $configForm->setError(
                    'SYNC_EXPORT_URL,SYNC_ALLOW_LEGACY_HTTP',
                    'HTTP без credentials изисква изрично временно разрешение'
                );
            }
        }
    }
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('importedProductFiles', 'Файлове от импортирани артикули', null, '1GB', 'user', 'user');

        if(core_Packs::isInstalled('eshop', true)){
            $eSettings = cls::get('eshop_Settings');
            $html .= $eSettings->setupMvc();
        }

        return $html;
    }


    /**
     * Мигрира старите глобални export настройки в новия per-client модел
     */
    public static function migrateSettings2638()
    {
        // core_Packs съдържа draft ред и преди първата инсталация. Само active
        // ред означава, че това е upgrade на работила стара sync инсталация.
        $packRec = core_Packs::fetch("#name = 'sync'");
        $wasInstalled = $packRec && ($packRec->state ?? null) == 'active';
        $conf = core_Packs::getConfig('sync');
        if (!$wasInstalled) {

            return;
        }

        // Старото единично поле се използва и от ръчния product sync на client-а.
        $companyGroups = self::getLegacyConfigValue($conf, 'SYNC_COMPANY_GROUPS');
        if (!$companyGroups && ($companyGroup = self::getLegacyConfigValue($conf, 'SYNC_COMPANY_GROUP'))) {
            $companyGroups = "|{$companyGroup}|";
            expect(
                core_Packs::setConfig('sync', array('SYNC_COMPANY_GROUPS' => $companyGroups)),
                'Не могат да се мигрират групите на фирмите за sync'
            );
        }

        expect(
            core_Packs::setConfig('sync', array('SYNC_ALLOW_LEGACY_HTTP' => 'yes')),
            'Не може да се запази legacy HTTP режимът'
        );

        // Sync 0.2 въвежда SyncAutoSync. Ако междинен билд вече го е създал с
        // по-нисък лимит, го вдигаме - иначе дълъг import ще се сече.
        $cronRec = core_Cron::fetch("#systemId = 'SyncAutoSync'");
        if ($cronRec && ($cronRec->timeLimit ?? 0) < 7200) {
            $cronRec->timeLimit = 7200;
            expect(core_Cron::save($cronRec, 'timeLimit'), 'Не може да се обнови sync cron лимитът');
        }

        // URL има само теглещата система. На нея не е необходим server-side
        // sync_Settings запис; тя се идентифицира пред master-а с ID и парола.
        $exportUrl = trim((string) self::getLegacyConfigValue($conf, 'SYNC_EXPORT_URL'));
        if ($exportUrl !== '') {
            expect(
                core_Packs::setConfig('sync', array(
                    'SYNC_AUTO_SYNC_TIME' => '05:33',
                    'SYNC_UPDATE_EXISTING' => 'no',
                )),
                'Не може да се настрои дневната sync синхронизация'
            );

            return;
        }

        $personsGroups = self::getLegacyConfigValue($conf, 'SYNC_CRM_GROUPS');
        $productGroups = self::getLegacyConfigValue($conf, 'SYNC_PROD_GROUPS');
        $allowedIps = self::getLegacyConfigValue($conf, 'SYNC_EXPORT_ADDR');
        $eshopInstalled = core_Packs::isInstalled('eshop');
        $eshopGroups = $eshopInstalled
            ? self::getLegacyConfigValue($conf, 'SYNC_ESHOP_GROUPS')
            : null;

        // Старият exporter приемаше private IP и при празни филтри. Затова при
        // upgrade създаваме legacy ред и в този случай, за да запазим поведението.
        $rec = sync_Settings::fetch("#offlineSysId = '__legacy__'");
        if (!$rec) {
            $rec = new stdClass();
        }
        if (empty($rec->pass)) {
            $rec->pass = str::getRand(str_repeat('*', 32));
        }
        $rec->offlineSysId = '__legacy__';
        $rec->authType = 'legacyIp';
        $rec->allowedIps = 'private' . ($allowedIps ? ',' . $allowedIps : '');
        $rec->catGroups = $productGroups;
        $rec->productsExportMode = $productGroups ? 'selected' : 'all';
        $rec->allowProductPush = 'no';
        $rec->productPushSourceUrl = null;
        $rec->dictionaryExportMode = 'all';
        $rec->companiesGroups = $companyGroups;
        $rec->companiesExportMode = $companyGroups ? 'selected' : 'none';
        $rec->personsGroups = $personsGroups;
        $rec->personsExportMode = $personsGroups ? 'selected' : 'all';
        if ($eshopInstalled) {
            $rec->syncEshopGroups = $eshopGroups;
            $rec->eshopExportMode = $eshopGroups ? 'selected' : 'all';
        }
        $rec->state = 'active';

        expect(sync_Settings::save($rec), 'Не може да се запише legacy sync настройката');
    }


    /**
     * Връща ефективна стара настройка от DB или от инсталационна константа
     *
     * @param core_ObjectConfiguration $conf
     * @param string                   $key
     *
     * @return mixed
     */
    protected static function getLegacyConfigValue($conf, $key)
    {
        $value = $conf->_data[$key] ?? null;
        if ($value !== null && $value !== '') {

            return $value;
        }

        return defined($key) ? constant($key) : null;
    }
}
