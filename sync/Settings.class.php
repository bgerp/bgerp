<?php


/**
 * Настройките за връзка със ситемите
 *
 * @category  bgerp
 * @package   sync
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sync_Settings extends core_Manager
{
    /**
     * Mutex за автоматичния import
     */
    const IMPORT_LOCK_ID = 'sync_AutoSyncImport';


    /**
     * Префикс на mutex-а, който пази един клиент от паралелни export-и
     */
    const EXPORT_LOCK_PREFIX = 'sync_Export_';


    /**
     * Максимална дължина на паролата на клиент.
     *
     * Полето pass е varchar(255) и се записва през plg_CryptStore, който
     * разширява стойността до 'p|' + 4 знака + base64, тоест ~1.34 пъти плюс
     * 6 знака. 180 знака е най-дългото, което гарантирано се побира.
     * Същият лимит важи и за SYNC_PASS на клиента, защото това е една и съща
     * парола - тя трябва да може да се запише и в този запис на master-а.
     */
    const MAX_PASS_LENGTH = 180;


    /**
     * Ключ за последния стартиран опит за автоматичен import
     */
    const LAST_AUTO_SYNC_KEY = 'sync_AutoSyncLastDate';


    /**
     * Име на притежавания MySQL advisory lock и последна проверка
     */
    protected static $importLockName;
    protected static $lastImportLockRenewal;


    /**
     * Заглавие на мениджъра
     */
    public $title = 'Настройки';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, plg_Modified, sync_Wrapper, plg_State2, plg_CryptStore';


    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'sync, admin';


    /**
     * Кой има право да го променя?
     */
    public $canEditsysdata = 'sync, admin';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'sync, admin';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'sync, admin';


    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';


    /**
     * Кой може да променя състоянието?
     */
    public $canChangestate = 'sync, admin';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('offlineSysId', 'varchar(32)', 'caption=Офлайн система->ID, mandatory');
        $this->FLD('authType', 'enum(credentials=ID и парола,legacyIp=Само IP (преходен режим))', 'caption=Офлайн система->Идентификация,notNull,value=credentials,silent,removeAndRefreshForm=pass');
        $this->FLD('pass', 'password(255,autocomplete=off)', 'caption=Офлайн система->Парола,crypt');
        $this->FLD(
            'allowedIps',
            'varchar',
            'caption=Офлайн система->IP,placeholder=203.0.113.10 или 203.0.113.0/24,hint=Точни IPv4/IPv6 адреси и IPv4 CIDR мрежи, разделени със запетая; private е само за преходен legacy режим'
        );
        $this->FLD('stores', 'keylist(mvc=store_Stores, select=name, allowEmpty)', 'caption=Склад');
        $this->FLD('cases', 'keylist(mvc=cash_Cases, select=name, allowEmpty)', 'caption=Каса');
        $this->FLD('posPoints', 'keylist(mvc=pos_Points, select=name, allowEmpty)', 'caption=ПОС');
        $this->FLD('productsExportMode', 'enum(none=Без артикули,all=Всички достигнати артикули,selected=Само достигнатите от избрани групи)', 'caption=Експортиране->Зависими артикули,notNull,value=none,silent,removeAndRefreshForm=catGroups');
        $this->FLD('catGroups', 'keylist(mvc=cat_Groups, select=name, allowEmpty)', 'caption=Група за експортиране->Артикули,input=none,hint=Филтърът важи за артикулите достигнати през фирми и е-магазин');
        $this->FLD('allowProductPush', 'enum(no=Не,yes=Да)', 'caption=Legacy product push->Разрешаване,notNull,value=no,silent,removeAndRefreshForm=productPushSourceUrl');
        $this->FLD('productPushSourceUrl', 'url', 'caption=Legacy product push->Доверен source URL,input=none,placeholder=https://client.example.com');
        $this->FLD('dictionaryExportMode', 'enum(none=Без речник,all=Целият речник)', 'caption=Експортиране->Речник,notNull,value=none');
        $this->FLD('companiesExportMode', 'enum(none=Без фирми,all=Всички фирми,selected=Само от избрани групи)', 'caption=Експортиране->Фирми,notNull,value=none,silent,removeAndRefreshForm=companiesGroups');
        $this->FLD('companiesGroups', 'keylist(mvc=crm_Groups, select=name, allowEmpty, parentId=parentId)', 'caption=Група за експортиране->Фирми,input=none');
        $this->FLD('personsExportMode', 'enum(none=Без лица,all=Всички лица,selected=Само от избрани групи)', 'caption=Експортиране->Лица,notNull,value=none,silent,removeAndRefreshForm=personsGroups');
        $this->FLD('personsGroups', 'keylist(mvc=crm_Groups, select=name, allowEmpty, parentId=parentId)', 'caption=Група за експортиране->Лица,input=none,hint=Филтърът важи само за началните лица; лицата зад зависими inCharge потребители се включват автоматично');
        $eshopInput = core_Packs::isInstalled('eshop') ? '' : ',input=none';
        $this->FLD('eshopExportMode', 'enum(none=Без е-магазин,all=Всички артикули,selected=Само от избрани групи)', 'caption=Експортиране->Е-магазин,notNull,value=none,silent,removeAndRefreshForm=syncEshopGroups' . $eshopInput);
        $this->FLD('syncEshopGroups', 'keylist(mvc=eshop_Groups, select=name, allowEmpty)', 'caption=Група за експортиране->Е-магазин,optionsFunc=sync_Eshop::getEshopGroups,input=none');

        $this->setDbUnique('offlineSysId');
    }


    /**
     * Подготвя условните полета във формата
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form ?? null;
        // При Save core_Manager зарежда DB стойностите след първия silent input.
        $form->input(
            'authType,allowProductPush,productsExportMode,companiesExportMode,personsExportMode,eshopExportMode',
            'silent'
        );
        $rec = $form->rec;

        if (($rec->authType ?? 'credentials') == 'legacyIp') {
            $form->setField('pass', 'input=none');
        } else {
            $form->setField('pass', 'input');
        }
        if (($rec->allowProductPush ?? 'no') == 'yes') {
            $form->setField('productPushSourceUrl', 'input');
        }

        $modeToGroups = array(
            'productsExportMode' => 'catGroups',
            'companiesExportMode' => 'companiesGroups',
            'personsExportMode' => 'personsGroups',
        );
        if (core_Packs::isInstalled('eshop')) {
            $modeToGroups['eshopExportMode'] = 'syncEshopGroups';
        }

        foreach ($modeToGroups as $modeField => $groupsField) {
            if (($rec->{$modeField} ?? null) == 'selected') {
                $form->setField($groupsField, 'input');
            }
        }
    }


    /**
     * Валидира свързаните настройки
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if (!$form->isSubmitted()) {

            return;
        }

        $rec = $form->rec;
        if (($rec->authType ?? 'credentials') == 'credentials') {
            $oldRec = !empty($rec->id) ? $mvc->fetch($rec->id) : null;
            $oldPass = $oldRec->pass ?? null;
            $requiresNewPass = $oldRec && ($oldRec->authType ?? 'credentials') == 'legacyIp';

            if (($rec->pass ?? null) === '' ||
                (!$oldPass && empty($rec->pass)) ||
                ($requiresNewPass && (empty($rec->pass) || hash_equals((string) $oldPass, (string) $rec->pass)))) {
                $form->setError('pass', 'Паролата е задължителна при идентификация с credentials');
            }

            if (isset($rec->pass) && strlen($rec->pass) > self::MAX_PASS_LENGTH) {
                $form->setError(
                    'pass',
                    'Паролата може да бъде най-много ' . self::MAX_PASS_LENGTH . ' знака'
                );
            }
        }

        if (($rec->authType ?? null) == 'legacyIp' && empty($rec->allowedIps)) {
            $form->setError('allowedIps', 'Преходният режим изисква IP адрес, мрежа или стойност private');
        }
        if (!empty($rec->allowedIps)) {
            foreach (preg_split('/[\s,;]+/', trim($rec->allowedIps)) as $allowedIp) {
                $isPrivateKeyword = strtolower($allowedIp) == 'private';
                $isExactIp = (bool) filter_var($allowedIp, FILTER_VALIDATE_IP);
                $isIpv4Cidr = false;
                if (strpos($allowedIp, '/') !== false) {
                    list($network, $prefix) = explode('/', $allowedIp, 2);
                    $isIpv4Cidr = (bool) filter_var($network, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) &&
                        ctype_digit((string) $prefix) && (int) $prefix >= 0 && (int) $prefix <= 32;
                }

                if ((!$isPrivateKeyword && !$isExactIp && !$isIpv4Cidr) ||
                    ($isPrivateKeyword && ($rec->authType ?? null) != 'legacyIp')) {
                    $form->setError('allowedIps', "Невалиден IP адрес или CIDR мрежа: {$allowedIp}");
                }
            }
        }

        $modeToGroups = array(
            'productsExportMode' => 'catGroups',
            'companiesExportMode' => 'companiesGroups',
            'personsExportMode' => 'personsGroups',
        );
        if (core_Packs::isInstalled('eshop')) {
            $modeToGroups['eshopExportMode'] = 'syncEshopGroups';
        }

        foreach ($modeToGroups as $modeField => $groupsField) {
            if (($rec->{$modeField} ?? null) == 'selected' && empty($rec->{$groupsField})) {
                $form->setError($groupsField, 'При режим „Само от избрани групи“ трябва да се избере поне една група');
            }
        }

        if (($rec->allowProductPush ?? 'no') == 'yes') {
            $sourceParts = parse_url((string) ($rec->productPushSourceUrl ?? ''));
            $pushQuery = $mvc->getQuery();
            $pushQuery->where("#allowProductPush = 'yes'");
            if (!empty($rec->id)) {
                $pushQuery->where(array("#id != [#1#]", $rec->id));
            }
            $pushQuery->limit(1);

            if ($pushQuery->fetch()) {
                $form->setError(
                    'allowProductPush',
                    'Legacy product push може да е разрешен само за една source система'
                );
            }
            if (($rec->authType ?? 'credentials') != 'credentials') {
                $form->setError(
                    'allowProductPush,authType',
                    'Legacy product push е разрешен само за client с credentials'
                );
            }
            if (($rec->productsExportMode ?? 'none') != 'all') {
                $form->setError(
                    'allowProductPush,productsExportMode',
                    'Legacy product push изисква режим „Всички артикули“'
                );
            }
            if (!is_array($sourceParts) ||
                strtolower($sourceParts['scheme'] ?? '') != 'https' ||
                empty($sourceParts['host']) ||
                isset($sourceParts['user']) ||
                isset($sourceParts['pass'])) {
                $form->setError(
                    'productPushSourceUrl',
                    'Посочете HTTPS source URL без userinfo'
                );
            }
        }

        if (($rec->productsExportMode ?? 'none') == 'none' &&
            (($rec->companiesExportMode ?? 'none') != 'none' ||
             ($rec->eshopExportMode ?? 'none') != 'none')) {
            $form->setError(
                'productsExportMode',
                'Фирмите и е-магазинът съдържат зависимости към артикули; изберете „Всички“ или групи'
            );
        }
        if (($rec->personsExportMode ?? 'none') == 'none' &&
            (($rec->companiesExportMode ?? 'none') != 'none' ||
             ($rec->eshopExportMode ?? 'none') != 'none')) {
            $form->setError(
                'personsExportMode',
                'Фирмите и е-магазинът съдържат зависимости към потребители/лица; разрешете лицата'
            );
        }
    }


    /**
     * Намира и валидира sync_Settings записа за текущата входяща заявка.
     *
     * Импортиращата система праща syncSysId + syncPass при всяка заявка. Тук се намира
     * съответния запис по offlineSysId и се проверява паролата.
     *
     * @param bool $throwIfMissing - дали да хвърля грешка при липса/невалидност
     *
     * @return stdClass|null
     */
    public static function getRequestSettings($throwIfMissing = true, $allowLegacy = false)
    {
        return sync_Helper::getRequestSettings($throwIfMissing, $allowLegacy);
    }


    /**
     * Връща нормализирания export режим за даден тип данни
     *
     * Fallback-ът пази безопасна семантика при краткия преход, преди да е
     * изпълнена миграцията на новите mode полета.
     *
     * @param stdClass $rec
     * @param string   $type
     *
     * @return string
     */
    public static function getExportMode($rec, $type)
    {
        $fields = array(
            'products' => array('productsExportMode', 'catGroups', 'all'),
            'dictionary' => array('dictionaryExportMode', null, 'none'),
            'companies' => array('companiesExportMode', 'companiesGroups', 'none'),
            'persons' => array('personsExportMode', 'personsGroups', 'none'),
            'eshop' => array('eshopExportMode', 'syncEshopGroups', 'none'),
        );
        expect(isset($fields[$type]), $type);

        list($modeField, $groupsField, $emptyMode) = $fields[$type];
        $mode = $rec->{$modeField} ?? null;

        if (!in_array($mode, array('none', 'all', 'selected'), true)) {
            $mode = $groupsField && !empty($rec->{$groupsField}) ? 'selected' : $emptyMode;
        }

        return $mode;
    }


    /**
     * Дали конкретен артикул е разрешен за текущия клиент
     *
     * @param stdClass $productRec
     * @param stdClass|null $settingsRec
     *
     * @return bool
     */
    public static function canExportProduct($productRec, $settingsRec = null)
    {
        if (!$productRec) {

            return false;
        }

        $settingsRec = $settingsRec ?: self::getRequestSettings(false, true);
        if (!$settingsRec) {

            return true;
        }

        $mode = self::getExportMode($settingsRec, 'products');
        if ($mode == 'none') {

            return false;
        }

        if ($mode == 'all') {

            return true;
        }

        $allowed = type_Keylist::toArray($settingsRec->catGroups);
        $productGroups = type_Keylist::toArray($productRec->groups);

        return !empty($allowed) && (bool) array_intersect($allowed, $productGroups);
    }


    /**
     * Прилага политиката за рекурсивно достигнатите записи.
     *
     * Филтрите за фирми, лица и е-магазин определят само началните записи в
     * съответния collectExport(). Техните задължителни зависимости трябва да
     * останат достъпни. По историческа съвместимост филтърът за артикули важи
     * и за рекурсивно достигнатите cat_Products.
     *
     * @param string        $class
     * @param stdClass      $rec
     * @param stdClass|null $settingsRec
     *
     * @return bool
     */
    public static function canExportRecord($class, $rec, $settingsRec = null)
    {
        if (!$rec) {

            return false;
        }
        if (Mode::get('syncBypassExportPolicy')) {

            return true;
        }

        $class = is_object($class) ? $class->className : $class;

        if ($class == 'cat_Products') {

            return self::canExportProduct($rec, $settingsRec);
        }

        return true;
    }


    /**
     * Унифициран експорт-екшън - вика се от импортиращата система с една заявка.
     *
     * Експортиращата система връща само това, което е конфигурирано в нейния
     * sync_Settings запис за подадения syncSysId (фирми, лица, е-магазин,
     * речник и policy за достигнатите артикули).
     */
    public function act_Export()
    {
        sync_Helper::requireRight();
        $settingsRec = self::getRequestSettings();

        self::guardExportRun($settingsRec);
        core_App::setTimeLimit(3600);

        $res = array();
        $controllers = array();

        if (self::getExportMode($settingsRec, 'companies') != 'none') {
            $controllers[] = cls::get('sync_Companies');
        }
        if (self::getExportMode($settingsRec, 'persons') != 'none') {
            $controllers[] = cls::get('sync_Persons');
        }
        if (core_Packs::isInstalled('eshop') && self::getExportMode($settingsRec, 'eshop') != 'none') {
            $controllers[] = cls::get('sync_Eshop');
        }
        if (core_Packs::isInstalled('replace') &&
            self::getExportMode($settingsRec, 'dictionary') == 'all') {
            $controllers[] = cls::get('sync_Dictionary');
        }

        $controller = sync_Helper::getCompositeController($controllers);

        core_Users::forceSystemUser();
        try {
            // Запазваме предишната семантика: артикулите не са отделен root
            // dataset, а се достигат през фирми/листинги и е-магазина.
            // productsExportMode е твърда policy граница за тези зависимости.
            sync_Companies::collectExport($res, $controller);
            sync_Persons::collectExport($res, $controller);

            if (core_Packs::isInstalled('eshop')) {
                sync_Eshop::collectExport($res, $controller);
            }
            if (core_Packs::isInstalled('replace')) {
                sync_Dictionary::collectExport($res, $controller);
            }
        } finally {
            core_Users::cancelSystemUser();
        }

        // Lock-ът се държи до края на хита: сериализирането, компресията и
        // изпращането в outputRes са най-скъпата по памет част.
        return sync_Helper::outputRes($res);
    }


    /**
     * Синхронизиране на данните от експортиращата система (по крон, на всяка минута).
     *
     * Работи само на импортиращата система (тази с настроен URL към експортиращата). С една
     * заявка (syncSysId + syncPass) се изтегля всичко, което експортиращата реши да върне.
     *
     * @return string|null|false false при неприключил import
     */
    public function cron_AutoSync()
    {
        // Кронът работи само на импортиращата система
        if (!sync_Setup::get('EXPORT_URL')) {

            return;
        }

        // Празен час допуска всяко минутно извикване. Зададеният час ограничава
        // импорта до един опит дневно, независимо от резултата му.
        $syncTime = sync_Setup::get('AUTO_SYNC_TIME');
        $today = dt::mysql2verbal(null, 'Y-m-d');
        $attemptDate = $syncTime ? $today : null;
        if ($syncTime &&
            (dt::mysql2verbal(null, 'H:i') < $syncTime ||
            core_Permanent::get(self::LAST_AUTO_SYNC_KEY) === $today)) {

            return;
        }

        ini_set('memory_limit', '2048M');
        // Осигурява дълъг лимит и при директно извикване. При cron framework-ът
        // вече е дал timeLimit + 20 секунди и core_App не го намалява.
        core_App::setTimeLimit(6900);

        // Разпределяме реалните заявки към master-а с до 59 секунди отместване.
        // Delay на cron записа би се изпълнявал и при всичките му дневни no-op проверки.
        if (!Request::get('forced')) {
            // 60 възможни отмествания с максимум 59 секунди.
            $delay = mt_rand(0, 59);
            if ($delay > 0) {
                sleep($delay);
            }

            // Друг forced/cron процес може да е започнал опита, докато сме изчаквали.
            $today = dt::mysql2verbal(null, 'Y-m-d');
            if ($attemptDate !== null &&
                ($today !== $attemptDate ||
                core_Permanent::get(self::LAST_AUTO_SYNC_KEY) === $attemptDate)) {

                return;
            }
        }

        return self::importAll($attemptDate);
    }


    /**
     * Изтегля и импортира обединените данни от експортиращата система.
     *
     * Дали да се обновяват съществуващите записи се определя от конфига (SYNC_UPDATE_EXISTING)
     * в @see sync_Map::shouldUpdate.
     *
     * @param string|null $autoAttemptDate Дата на дневния опит; NULL без дневно ограничение
     *
     * @return string|null|false false при lock/config/import грешка
     */
    public static function importAll($autoAttemptDate = null)
    {
        // MySQL advisory lock-ът е атомарен и се освобождава автоматично при
        // прекъсване на DB връзката/процеса.
        if (!self::obtainImportLock()) {

            return false;
        }

        Mode::push('syncAutoImport', true);
        try {
            // Claim-ваме дневния опит едва след атомарния mutex. Така паралелен
            // manual/cron import не изразходва опита, без реално да е започнал.
            if ($autoAttemptDate !== null) {
                $today = dt::mysql2verbal(null, 'Y-m-d');
                if ($today !== $autoAttemptDate ||
                    core_Permanent::get(self::LAST_AUTO_SYNC_KEY) === $autoAttemptDate) {

                    return null;
                }
                if (!core_Permanent::set(
                    self::LAST_AUTO_SYNC_KEY,
                    $autoAttemptDate,
                    core_Permanent::FOREVER_VALUE
                )) {
                    self::logErr('Не може да се запише дневният sync marker');

                    return false;
                }
            }

            $sysId = trim((string) sync_Setup::get('SYS_ID'));
            $pass = sync_Setup::getSyncPass();
            if (($sysId === '') xor ($pass === '')) {
                self::logErr('Трябва да са попълнени едновременно SYNC_SYS_ID и SYNC_PASS');

                return false;
            }

            // Без credentials работим в преходен режим със стария Companies
            // endpoint. Така нов client може да се обнови преди master-а.
            $exportController = ($sysId !== '') ? 'sync_Settings' : 'sync_Companies';
            $resArr = sync_Helper::getDataFromUrl($exportController);

            if (!is_array($resArr)) {

                return false;
            }

            if (!self::renewImportLock(true)) {

                return false;
            }
            core_Users::forceSystemUser();
            try {
                $success = self::importData($resArr);
                if (!$success) {
                    self::logErr('Автоматичният sync import приключи с грешки при запис');

                    return false;
                }

                $result = '';
                $Settings = cls::get('sync_Settings');
                $Settings->invoke('AfterSuccessfulSyncImport', array(&$result, &$resArr));

                return $result ?: null;
            } finally {
                core_Users::cancelSystemUser();
            }
        } finally {
            Mode::pop('syncAutoImport');
            self::releaseImportLock();
        }
    }


    /**
     * Подновява mutex-а при дълъг рекурсивен import
     */
    public static function renewImportLock($force = false)
    {
        if (!Mode::get('syncAutoImport') || !self::$importLockName) {

            return true;
        }

        $now = time();
        if (!$force && self::$lastImportLockRenewal && ($now - self::$lastImportLockRenewal) < 60) {

            return true;
        }

        $Settings = cls::get('sync_Settings');
        $lockName = self::$importLockName;
        $dbRes = $Settings->db->query(
            "SELECT (IS_USED_LOCK('{$lockName}') = CONNECTION_ID()) AS owned",
            true
        );
        $row = $dbRes ? $Settings->db->fetchArray($dbRes) : null;
        $owned = $row && (int) $row['owned'] === 1;
        if (!$owned) {
            self::logErr('Изгубен mutex по време на sync import');

            return false;
        }

        self::$lastImportLockRenewal = $now;

        return true;
    }


    /**
     * Атомарно придобива process/connection scoped import lock
     *
     * @return bool
     */
    protected static function obtainImportLock()
    {
        $lockName = self::acquireDbLock(self::IMPORT_LOCK_ID);
        if (!$lockName) {

            return false;
        }

        self::$importLockName = $lockName;
        self::$lastImportLockRenewal = time();

        return true;
    }


    /**
     * Подготвя тежък export: вдига лимита за памет и не допуска втори
     * едновременен export за същия credentials клиент.
     *
     * Клиентът изчаква до час за отговор; ако прекъсне и опита пак, master-ът
     * иначе би сглобявал два пълни графа успоредно. Lock-ът е per-client, за
     * да не си пречат отделните системи, и пада сам с DB връзката.
     *
     * @param stdClass|null $settingsRec
     */
    public static function guardExportRun($settingsRec)
    {
        ini_set('memory_limit', '2048M');

        // Всички стари clients споделят един __legacy__ запис. Lock по
        // него би блокирал различни системи взаимно по време на rollout-а.
        if (!$settingsRec ||
            empty($settingsRec->id) ||
            ($settingsRec->authType ?? 'credentials') == 'legacyIp') {

            return;
        }

        if (!self::acquireDbLock(self::EXPORT_LOCK_PREFIX . $settingsRec->id)) {
            self::logWarning("Отхвърлен паралелен export за sync настройка {$settingsRec->id}");
            expect(false, 'За тази система вече тече export; опитайте по-късно');
        }
    }


    /**
     * Неблокиращо заключване; връща името на lock-а при успех
     *
     * MySQL advisory lock-овете са scoped към DB връзката, затова се
     * освобождават и при убит процес - не остават заседнали.
     *
     * @param string $key
     *
     * @return string|false
     */
    protected static function acquireDbLock($key)
    {
        $Settings = cls::get('sync_Settings');
        $lockName = 'sync_' . md5($Settings->db->dbName . ':' . $key);
        $dbRes = $Settings->db->query("SELECT GET_LOCK('{$lockName}', 0) AS acquired", true);
        $row = $dbRes ? $Settings->db->fetchArray($dbRes) : null;

        return ($row && (int) $row['acquired'] === 1) ? $lockName : false;
    }


    /**
     * Освобождава advisory lock на текущата DB връзка
     *
     * @param string|null $lockName
     */
    protected static function releaseDbLock($lockName)
    {
        if (!$lockName) {

            return;
        }

        $Settings = cls::get('sync_Settings');
        $Settings->db->query("SELECT RELEASE_LOCK('{$lockName}')", true);
    }


    /**
     * Освобождава единствено advisory lock-а на текущата DB връзка
     */
    protected static function releaseImportLock()
    {
        if (!self::$importLockName) {

            return;
        }

        self::releaseDbLock(self::$importLockName);

        self::$importLockName = null;
        self::$lastImportLockRenewal = null;
    }


    /**
     * Инстанциите на мениджърите, които участват в синхронизацията (симетрично на
     * collectExport-ите в act_Export).
     *
     * Всеки от тях може да добави специфична обработка, като дефинира събитията
     * 'BeforeSyncImportRec' и/или 'AfterSyncImportAll' (@see self::importData). Мениджър без
     * такава обработка просто не ги дефинира - не е нужен празен метод.
     *
     * @return array - масив от инстанции на мениджъри
     */
    protected static function getSyncManagers()
    {
        $managers = array(cls::get('sync_Companies'), cls::get('sync_Persons'));

        if (core_Packs::isInstalled('eshop')) {
            $managers[] = cls::get('sync_Eshop');
        }
        if (core_Packs::isInstalled('replace')) {
            $managers[] = cls::get('sync_Dictionary');
        }

        return $managers;
    }


    /**
     * Обединен импорт на вече извлечените данни от експортиращата система.
     *
     * Прави един проход по обединения масив и импортира всеки запис през sync_Map::importRec.
     * Специфичната за отделните мениджъри обработка се делегира през събития (invoke) -
     * 'BeforeSyncImportAll' преди обхода, 'BeforeSyncImportRec' за всеки запис
     * (с възможност за пропускане през $skip) и 'AfterSyncImportAll' накрая -
     * симетрично на collectExport-ите от @see self::act_Export.
     * Дали да се обновяват съществуващите записи се определя от конфига (SYNC_UPDATE_EXISTING)
     * в @see sync_Map::shouldUpdate.
     *
     * @param array $resArr
     */
    public static function importData(&$resArr, $update = null)
    {
        $update = sync_Map::shouldUpdate($update);
        sync_Map::$imported = array();
        sync_Map::$importErrors = 0;

        Mode::push('preventNotifications', true);
        Mode::push('syncing', true);
        Mode::push('syncUpdateExisting', $update);

        try {
            $managers = self::getSyncManagers();
            $controller = sync_Helper::getCompositeController($managers);

            foreach ($managers as $manager) {
                $manager->invoke(
                    'BeforeSyncImportAll',
                    array(&$resArr, $controller, $update)
                );
            }

            foreach ($resArr as $class => $objArr) {
                if (is_object($objArr)) {
                    $objArr = $resArr[$class] = (array) $objArr;
                }
                if (!is_array($objArr)) {
                    sync_Map::$importErrors++;
                    self::logErr("Невалиден sync payload за клас {$class}");

                    continue;
                }

                self::logDebug($class . ': ' . countR($objArr));
                foreach ($objArr as $id => $rec) {
                    // Предишен hook може да е премахнал бъдещ запис от оригиналния масив.
                    if (!isset($resArr[$class][$id])) {

                        continue;
                    }

                    if (!is_object($rec)) {
                        sync_Map::importRec($class, $id, $resArr, $controller, $update);

                        continue;
                    }

                    // Даваме възможност на мениджърите да обработят/пропуснат записа.
                    $skip = false;
                    foreach ($managers as $manager) {
                        $manager->invoke(
                            'BeforeSyncImportRec',
                            array(&$resArr, $class, $id, $rec, &$skip, $controller, $update)
                        );
                        if ($skip) {

                            break;
                        }
                    }

                    if ($skip) {

                        continue;
                    }

                    sync_Map::importRec($class, $id, $resArr, $controller, $update);
                }
            }

            if (sync_Map::$importErrors == 0) {
                if (!self::renewImportLock(true)) {
                    sync_Map::$importErrors++;

                    return false;
                }

                foreach ($managers as $manager) {
                    $manager->invoke('AfterSyncImportAll', array(&$resArr));
                }
            }

            return sync_Map::$importErrors == 0;
        } finally {
            Mode::pop('syncUpdateExisting');
            Mode::pop('syncing');
            Mode::pop('preventNotifications');
        }
    }
}
