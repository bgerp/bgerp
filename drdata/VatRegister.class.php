<?php


/**
 * Регистър на лицата, регистрирани по ЗДДС, синхронизиран с публичния бюлетин на НАП
 *
 *
 * @category  vendors
 * @package   drdata
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class drdata_VatRegister extends core_Manager
{
    /**
     * Адрес на публичния бюлетин по чл. 169 ЗДДС (7z архив с CSV)
     */
    const BULLETIN_URL = 'https://nra.bg/wps/wcm/connect/nra.bg25863/72495c8e-0963-47de-94cf-18b0758f92a8/VATCompanies.7z?MOD=AJPERES';


    /**
     * Ключ в core_Permanent за данните от последната синхронизация
     */
    const SYNC_KEY = 'drdataVatRegisterSync';


    /**
     * С колко процента най-много може да намалее броят на номерата спрямо предишния импорт
     */
    const MAX_DROP_PERCENT = 5;


    /**
     * Брой опити за сваляне при временен мрежов отказ
     */
    const DOWNLOAD_ATTEMPTS = 3;


    /**
     * До колко дни назад може да е бюлетинът, за да се разчита на регистъра
     */
    const ACTUAL_DAYS = 30;



    /**
     * Заглавие
     */
    public $title = 'Регистрирани по ЗДДС лица';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Регистрирано по ЗДДС лице';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'drdata_Wrapper, plg_Sorting, plg_Search';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'vatNo,name,state,regDate,deregDate,updatedOn';


    /**
     * Полета за търсене
     */
    public $searchFields = 'vatNo,name';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';


    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('vatNo', 'varchar(15)', 'caption=ДДС №');
        $this->FLD('name', 'varchar(255)', 'caption=Наименование');
        $this->FLD('state', 'enum(registered=Регистриран,deregistered=Дерегистриран)', 'caption=Състояние');
        $this->FLD('regDate', 'date', 'caption=Дата на регистрация');
        $this->FLD('deregDate', 'date', 'caption=Дата на дерегистрация');
        $this->FLD('updatedOn', 'datetime(format=smartTime)', 'caption=Обновено на');

        $this->setDbUnique('vatNo');
    }


    /**
     * Дали регистърът е синхронизиран с достатъчно скорошен бюлетин
     */
    public static function isActual()
    {
        $syncData = core_Permanent::get(self::SYNC_KEY);
        $actualOn = is_array($syncData) ? ($syncData['actualOn'] ?? null) : null;

        return !empty($actualOn) && $actualOn >= dt::addDays(-1 * self::ACTUAL_DAYS);
    }


    /**
     * Дали въведеното име е на същата фирма като името от регистър (допуска правописни грешки)
     *
     * @param string $name    - въведеното име
     * @param string $regName - името от регистъра на НАП или от Търговския регистър
     *
     * @return bool|null - null, ако имената не могат да се сравнят
     */
    public static function isSameCompany($name, $regName)
    {
        // НАП добавя правната форма накрая като "/ЕООД/", а при ЕТ и "вкл. в качеството му на ..."
        $regName = trim(preg_replace('/\/[^\/]*\/\s*$/u', '', $regName ?? ''));
        $variants = preg_split('/,?\s*вкл\.\s*в качеството му на\s*/iu', $regName);

        $res = null;
        foreach ($variants as $variant) {
            $same = marketing_Router::isSameCompanyName($name, $variant);
            if ($same) {

                return true;
            }
            $res = $res ?? $same;
        }

        return $res;
    }


    /**
     * Името от регистъра във вида за фактура: "ВИТАЛИТИ АКТИВ /ЕООД/" -> "ВИТАЛИТИ АКТИВ ЕООД"
     *
     * Правната форма в наклонени черти се премества след името, а други пояснения (напр. "/Осигурител/") се махат.
     *
     * @param string $regName
     *
     * @return string
     */
    public static function getInvoiceName($regName)
    {
        $regName = trim($regName ?? '');
        if (!preg_match('/^(.*?)\s*\/([^\/]*)\/\s*$/u', $regName, $m)) {

            return $regName;
        }

        $legalForm = trim($m[2]);
        $isLegalForm = preg_match('/^(ЕООД|ООД|ЕАД|АД|ЕТ|КД|КДА|СД|ДЗЗД|АДСИЦ)$/iu', $legalForm);

        return trim($m[1]) . ($isLegalForm ? " {$legalForm}" : '');
    }


    /**
     * Бутон за регенериране на регистъра (само за debug)
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if (haveRole('debug')) {
            $data->toolbar->addBtn('Регенериране', array($mvc, 'regenerate', 'ret_url' => true), 'warning=Наистина ли искате да изтриете регистъра и да го заредите наново от бюлетина на НАП?,ef_icon=img/16/arrow_refresh.png,title=Изтриване и ново зареждане от бюлетина на НАП');
        }
    }


    /**
     * Оцветяване на реда според състоянието на регистрацията
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->ROW_ATTR['class'] = (($rec->state ?? null) == 'registered') ? 'state-active' : 'state-closed';
    }


    /**
     * Изтрива регистъра и го зарежда наново от бюлетина на НАП
     */
    public function act_Regenerate()
    {
        requireRole('debug');

        $this->truncate();
        core_Permanent::remove(self::SYNC_KEY);
        $this->logWrite('Регенериране на регистъра');

        $res = $this->cron_SyncWithNra();

        followRetUrl(null, '|Регенериране|*: ' . $res);
    }


    /**
     * Филтър на листовия изглед
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        // Отделно поле с изрична опция "Всички", за да не се губи изборът при подразбиране "Регистрирани"
        $data->listFilter->FNC('stateFilter', 'enum(registered=Регистрирани,deregistered=Дерегистрирани,all=Всички)', 'caption=Състояние,input,silent');
        $data->listFilter->setDefault('stateFilter', 'registered');
        $data->listFilter->showFields = 'search,stateFilter';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input();

        $state = $data->listFilter->rec->stateFilter ?? 'registered';
        if (in_array($state, array('registered', 'deregistered'))) {
            $data->query->where(array("#state = '[#1#]'", $state));
        }

        $data->query->orderBy('vatNo');
    }


    /**
     * Ежедневна синхронизация с бюлетина на НАП
     */
    public function cron_SyncWithNra()
    {
        $syncData = core_Permanent::get(self::SYNC_KEY);
        $syncData = is_array($syncData) ? $syncData : array();

        $dir = fileman::getTempPath();
        try {
            $archive = $dir . '/VATCompanies.7z';
            $etag = $this->downloadBulletin($archive, $syncData['etag'] ?? null);
            if ($etag === null) {

                return 'Бюлетинът не е променян';
            }
            if ($etag === false) {

                return 'Неуспешно сваляне на бюлетина';
            }

            $cmd = archive_Setup::get_ARCHIVE_7Z_PATH() . ' e ' . escapeshellarg($archive) . ' -o' . escapeshellarg($dir) . ' -y';
            exec($cmd, $output, $return);
            // В архива има и отделен файл за режима за малки предприятия (_SME), който не се ползва
            $csvFile = $dir . '/VATCompanies.csv';
            if ($return !== 0 || !is_file($csvFile)) {
                $this->logWarning("Неуспешно разархивиране на бюлетина ({$return})");

                return 'Неуспешно разархивиране';
            }

            $parsed = self::parseBulletin($csvFile);
            if ($parsed === false) {
                $this->logWarning('Непознат формат на бюлетина');

                return 'Непознат формат на бюлетина';
            }

            // Ако НАП са подменили файла без нови данни, не се синхронизира
            if (!empty($syncData['actualOn']) && $syncData['actualOn'] == $parsed->actualOn) {
                $syncData['etag'] = $etag;
                core_Permanent::set(self::SYNC_KEY, $syncData, core_Permanent::FOREVER_VALUE);

                return 'Няма нови данни';
            }

            // Непълен файл не бива да изтрие регистъра
            $newCount = 0;
            foreach ($parsed->data as $groupData) {
                $newCount += countR($groupData);
            }
            $exCount = $this->getQuery()->count();
            if (!$newCount || $newCount < $exCount * (1 - self::MAX_DROP_PERCENT / 100)) {
                $this->logWarning("Бюлетинът изглежда непълен: {$newCount} номера срещу {$exCount} в регистъра");

                return 'Бюлетинът изглежда непълен';
            }

            $counts = $this->sync($parsed->data);
            core_Permanent::set(self::SYNC_KEY, array('etag' => $etag, 'actualOn' => $parsed->actualOn, 'syncedOn' => dt::now()), core_Permanent::FOREVER_VALUE);

            $msg = "Синхронизация към {$parsed->actualOn}: добавени {$counts['insert']}, обновени {$counts['update']}, изтрити {$counts['delete']}, пропуснати редове {$parsed->skipped}";
            $this->logInfo($msg);

            return $msg;
        } finally {
            core_Os::deleteDir($dir);
        }
    }


    /**
     * Сваля архива на бюлетина, с няколко опита при временен мрежов отказ
     *
     * @param string      $file - къде да се запише
     * @param string|null $etag - ETag от последното сваляне
     *
     * @return string|null|false - ETag на новия файл, null ако не е променян, false при неуспех
     */
    protected function downloadBulletin($file, $etag)
    {
        $error = '';
        for ($attempt = 1; $attempt <= self::DOWNLOAD_ATTEMPTS; $attempt++) {
            if ($attempt > 1) {
                sleep(10);
            }

            $fp = fopen($file, 'w');
            expect($fp, 'Не може да се създаде временен файл');

            $newEtag = '';
            $ch = curl_init(self::BULLETIN_URL);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
            curl_setopt($ch, CURLOPT_TIMEOUT, 300);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            if (!empty($etag)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("If-None-Match: {$etag}"));
            }
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) use (&$newEtag) {
                if (stripos($header, 'ETag:') === 0) {
                    $newEtag = trim(substr($header, 5));
                }

                return strlen($header);
            });
            $ok = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            fclose($fp);

            if ($code == 304) {

                return null;
            }

            // При отказ сървърът връща HTML страница вместо архива
            $signature = (string) @file_get_contents($file, false, null, 0, 6);
            if ($ok && $code == 200 && $signature === "7z\xBC\xAF\x27\x1C") {

                return $newEtag;
            }

            $error = "HTTP {$code} {$curlError}";
        }

        $this->logWarning("Неуспешно сваляне на бюлетина след " . self::DOWNLOAD_ATTEMPTS . " опита: {$error}");

        return false;
    }


    /**
     * Парсира CSV файла на бюлетина
     *
     * Файлът е cp1251, всеки ред е обвит в "..." с удвоени вътрешни кавички, а първото поле
     * (името) не е в кавички - затова полетата се вземат отдясно наляво. Нов ред вътре в
     * поле е изнесен като `";` + нов ред или `";"` - тези редове се долепват.
     *
     * @param string $file
     *
     * @return false|stdClass
     *                        o actualOn - към коя дата са данните
     *                        o data     - [ДДС № => обект с name, state, regDate, deregDate], групирани по първите две цифри
     *                        o skipped  - брой неразпознати записи
     */
    public static function parseBulletin($file)
    {
        $fh = fopen($file, 'r');
        expect($fh, 'Не може да се отвори файлът на бюлетина');

        $first = iconv('cp1251', 'UTF-8', (string) fgets($fh));
        $header = iconv('cp1251', 'UTF-8', (string) fgets($fh));
        if (!preg_match('/(\d\d)\.(\d\d)\.(\d{4}) (\d\d:\d\d:\d\d)/', $first, $m) || strpos($header, 'Идентификационен номер по ЗДДС') === false) {
            fclose($fh);

            return false;
        }

        $res = (object) array('actualOn' => "{$m[3]}-{$m[2]}-{$m[1]} {$m[4]}", 'data' => array(), 'skipped' => 0);
        $buf = '';
        $parts = 0;
        while (($line = fgets($fh)) !== false) {
            $line = rtrim($line, "\r\n");
            if ($line === '') {
                continue;
            }

            // Самостоятелен запис изоставя недовършения, иначе би се долепил към него
            $fields = self::parseLine($line);
            if ($fields) {
                if ($buf !== '') {
                    $res->skipped++;
                    $buf = '';
                }
                if (!self::addRow($res->data, $fields)) {
                    $res->skipped++;
                }
                continue;
            }

            if ($buf === '') {
                $buf = $line;
                $parts = 1;
                continue;
            }

            $buf = self::joinLines($buf, $line);
            $fields = self::parseLine($buf);
            if ($fields) {
                if (!self::addRow($res->data, $fields)) {
                    $res->skipped++;
                }
                $buf = '';
            } elseif (++$parts > 10) {
                $res->skipped++;
                $buf = '';
            }
        }
        fclose($fh);

        if ($buf !== '') {
            $res->skipped++;
        }

        return $res;
    }


    /**
     * Долепва продължението на запис, разбит на няколко реда
     */
    protected static function joinLines($buf, $line)
    {
        if (substr($buf, -2) == '";') {
            $buf = substr($buf, 0, -2);
        } elseif (substr($buf, -1) == ';') {
            $buf = substr($buf, 0, -1);
        }

        // Продължението понякога започва с излишна отваряща кавичка
        if (substr($line, 0, 1) == '"' && substr($line, 0, 2) != '""') {
            $line = substr($line, 1);
        }

        return $buf . ' ' . $line;
    }


    /**
     * Разбива един (сурово кодиран) ред на бюлетина на полета
     *
     * @return false|array - 12-те колони на бюлетина
     */
    protected static function parseLine($line)
    {
        static $re;
        if (!isset($re)) {
            $q = '"((?:[^"]|"")*)"';
            $re = '/^(.*),' . implode(',', array_fill(0, 11, $q)) . '$/su';
        }

        $line = str_replace('";"', ' ', iconv('cp1251', 'UTF-8//IGNORE', $line));
        if (substr($line, 0, 1) !== '"') {

            return false;
        }

        if (substr($line, -2) == '";') {
            $line = substr($line, 1, -2);
        } elseif (substr($line, -1) == '"') {
            $line = substr($line, 1, -1);
        } else {

            return false;
        }

        if (!preg_match($re, str_replace('""', '"', $line), $m)) {

            return false;
        }

        $fields = array();
        foreach (array_slice($m, 1) as $value) {
            $fields[] = trim(preg_replace('/\s+/u', ' ', str_replace('""', '"', $value)));
        }

        return $fields;
    }


    /**
     * Добавя ред от бюлетина към данните, като пази по един запис на ДДС номер
     *
     * Активна регистрация има предимство пред прекратена. От активните се взема най-ранната,
     * а от прекратените - последно прекратената.
     *
     * @return bool - false, ако редът е с невалиден номер или дата
     */
    protected static function addRow(&$data, $fields)
    {
        $vatNo = $fields[1];
        if (!preg_match('/^BG\d{9,10}$/', $vatNo)) {

            return false;
        }

        // Невалидна дата на прекратяване не бива да се тълкува като активна регистрация
        $regDate = self::toMysqlDate($fields[6]);
        $deregDate = self::toMysqlDate($fields[8]);
        if ($regDate === false || $deregDate === false) {

            return false;
        }
        $state = ($deregDate === null) ? 'registered' : 'deregistered';
        $group = substr($vatNo, 2, 2);

        $ex = $data[$group][$vatNo] ?? null;
        if (isset($ex)) {
            list($exState, $exRegDate, $exDeregDate) = explode("\t", $ex);
            if ($exState == 'registered' && $state == 'deregistered') {

                return true;
            }
            if ($exState == $state) {
                if ($state == 'registered' && $exRegDate <= $regDate) {

                    return true;
                }
                if ($state == 'deregistered' && $exDeregDate >= $deregDate) {

                    return true;
                }
            }
        }

        // Пази се като низ, за да не изяжда паметта при 600+ хил. номера
        $data[$group][$vatNo] = "{$state}\t{$regDate}\t{$deregDate}\t{$fields[0]}";

        return true;
    }


    /**
     * Преобразува дата от вида ДД.ММ.ГГГГ
     *
     * @return string|null|false - null при празна дата, false при невалидна
     */
    protected static function toMysqlDate($date)
    {
        if ($date === '') {

            return null;
        }

        if (!preg_match('/^(\d\d)\.(\d\d)\.(\d{4})$/', $date, $m) || !checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {

            return false;
        }

        return "{$m[3]}-{$m[2]}-{$m[1]}";
    }


    /**
     * Синхронизира таблицата с данните от бюлетина на групи по първите две цифри на номера
     *
     * @param array $data - резултатът от parseBulletin()
     *
     * @return array - брой добавени, обновени и изтрити записи
     */
    public function sync($data)
    {
        core_App::setTimeLimit(900);
        $counts = array('insert' => 0, 'update' => 0, 'delete' => 0);
        $now = dt::now();

        for ($i = 0; $i <= 99; $i++) {
            $group = sprintf('%02d', $i);

            $newRecs = array();
            foreach ($data[$group] ?? array() as $vatNo => $str) {
                list($state, $regDate, $deregDate, $name) = explode("\t", $str, 4);
                $rec = (object) array('vatNo' => $vatNo, 'name' => $name, 'state' => $state, 'regDate' => $regDate ?: null, 'deregDate' => $deregDate ?: null, 'updatedOn' => $now);

                // saveArray не минава през plg_Search, затова ключовите думи се правят тук
                $rec->searchKeywords = plg_Search::purifyKeywods(plg_Search::normalizeText($vatNo) . ' ' . substr($vatNo, 2) . ' ' . plg_Search::normalizeText($name));
                $newRecs[] = $rec;
            }
            unset($data[$group]);

            $query = $this->getQuery();
            $query->where(array("#vatNo LIKE '[#1#]%'", "BG{$group}"));
            $query->show('id,vatNo,name,state,regDate,deregDate');
            $exRecs = $query->fetchAll();

            $synced = arr::syncArrays($newRecs, $exRecs, 'vatNo', 'name,state,regDate,deregDate');

            if (countR($synced['insert'])) {
                $this->saveArray($synced['insert']);
            }

            if (countR($synced['update'])) {
                $this->saveArray($synced['update'], 'id,name,state,regDate,deregDate,searchKeywords,updatedOn');
            }

            if (countR($synced['delete'])) {
                $ids = arr::makeIds(array_values($synced['delete']));
                $this->delete('#id IN (' . implode(',', $ids) . ')');
            }

            $counts['insert'] += countR($synced['insert']);
            $counts['update'] += countR($synced['update']);
            $counts['delete'] += countR($synced['delete']);
        }

        return $counts;
    }
}
