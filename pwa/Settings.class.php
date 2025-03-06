<?php
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 *
 *
 * @category  bgerp
 * @package   pwa
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pwa_Settings extends core_Manager
{


    /**
     * Заглавие на мениджъра
     */
    public $title = 'Настройки';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, plg_Modified, pwa_Wrapper, plg_State2';


    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'pwa, admin';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'pwa, admin';


    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'pwa, admin';


    /**
     * Кой може да изтрива системните данни
     */
    public $canDeletesysdata = 'pwa, admin';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'pwa, admin';


    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';


    /**
     * Кой може да променя състоянието
     */
    public $canChangestate = 'pwa, admin';


    /**
     * Броя на преките пътища, които може да се създадат
     */
    protected $shortcutCnt = 3;


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('domainId', 'key(mvc=cms_Domains, select=titleExt,allowEmpty)', 'caption=Домейн,mandatory,autoFilter');
        $this->FLD('name', 'varchar(255)', 'caption=Име на приложението->Дълго,mandatory');
        $this->FLD('shortName', 'varchar(128)', 'caption=Име на приложението->Кратко');
        $this->FLD('description', 'varchar', 'caption=Описание');
        $this->FLD('display', 'enum(fullscreen, standalone, minimal-ui, browser)', 'caption=Показване->Екран,mandatory');
        $this->FLD('displayOverride', 'set(browser, fullscreen, minimal-ui, standalone, tabbed, window-controls-overlay)', 'caption=Показване->Презапис');
        $this->FLD('backgroundColor', 'color_Type', 'caption=Цвят->Фон');
        $this->FLD('themeColor', 'color_Type', 'caption=Цвят->Тема');
        $this->FLD('startUrl', 'varchar(255)', 'caption=Начално URL');
        $this->FLD('icons', 'fileman_FileType(bucket=pwaZip)', 'caption=Икони');
        $this->FLD('clientMode', 'set(auto, focus-existing, navigate-existing, navigate-new)', 'caption=Клиент->Режим');
        $this->FLD('orientation', 'enum(any, natural, landscape, landscape-primary, landscape-secondary, portrait, portrait-primary, portrait-secondary)', 'caption=Клиент->Ориентация');
        $this->FLD('scope', 'varchar(128)', 'caption=Обхват');

        for ($i = 1; $i <= $this->shortcutCnt; $i++) {
            $this->FLD("sc{$i}Name", 'varchar(255)', "caption=Пряк път {$i}->Име");
            $this->FLD("sc{$i}ShortName", 'varchar(128)', "caption=Пряк път {$i}->Кратко име");
            $this->FLD("sc{$i}Description", 'varchar', "caption=Пряк път {$i}->Описание");
            $this->FLD("sc{$i}Url", 'varchar(255)', "caption=Пряк път {$i}->URL");
            $this->FLD("sc{$i}Icon", 'fileman_FileType(bucket=pwa)', "caption=Пряк път {$i}->Икона");
        }

        $this->setDbUnique('domainId');
    }


    /**
     * Връща домейните, които се използват
     *
     * @return array
     */
    public static function getDomains()
    {
        $resArr = array();
        $query = self::getQuery();
        $query->where("#state != 'closed'");

        while ($rec = $query->fetch()) {
            $resArr[$rec->domainId] = cms_Domains::fetchField($rec->domainId, 'domain');
        }

        return $resArr;
    }


    /**
     * Подготвя манифест файла за PWA за съответния домейн
     *
     * @param $domainId
     * @return false|string
     */
    public static function getPWAManifest($domainId = null)
    {
        if (!isset($domainId)) {
            $domainId = cms_Domains::getCurrent('id', false);
        }

        $rec = self::fetch(array("#domainId = '[#1#]'", $domainId));
        if (!$rec) {

            return false;
        }

        $json = core_Cache::get('pwaManifest', 'manifest', 100000, array('pwa_Settings'));

        if ($json === false) {
            $iconInfoArr = array();
            if ($rec->icons) {
                $archiveInst = cls::get('archive_Adapter', array('fileHnd' => $rec->icons));
                $entriesArr = $archiveInst->getEntries();
                // Всички файлове в архива
                foreach ($entriesArr as $key => $entry) {
                    $size = $entry->getSize();
                    if (!$size) {

                        continue;
                    }

                    // Гледаме размера след разархивиране да не е много голям
                    // Защита от "бомби" - от препълване на сървъра
                    if ($size > archive_Setup::get('MAX_LEN')) {
                        continue;
                    }

                    $path = $entry->getPath();

                    try {
                        $extractedPath = $archiveInst->extractEntry($path);
                    } catch (ErrorException $e) {
                        continue;
                    }

                    $ext = fileman_Files::getExt($path);
                    $ext = strtolower($ext);

                    if (!in_array($ext, array('png', 'svg', 'jpg', 'jpeg', 'webp', 'ico'))) {

                        continue;
                    }

                    // Get file name from path
                    $fName = basename($path);
                    // Ако има число и име на файл или число x число и име на файл, вземаме числото
                    $matches = array();
                    preg_match("/^(?'size'(?'sizeA'\d+)(x(?'sizeB'\d+))?)?(?:\s*[-_]\s*)*(?'fName'.*)/iu", $fName, $matches);

                    if ($matches['sizeB']) {
                        $sizes = $matches['size'];
                    } elseif ($matches['sizeA']) {
                        $sizes = $matches['sizeA'] . 'x' . $matches['sizeA'];
                    } else {
                        $sizes = null;
                    }

                    $fNameArr = fileman::getNameAndExt($matches['fName']);
                    $fName = "pwa-{$fNameArr['name']}-{$sizes}.{$fNameArr['ext']}";
                    $content = @file_get_contents($extractedPath);
                    core_Webroot::register($content, '', $fName, $domainId);

                    $iconInfoArr[] = array('src' => "/{$fName}", 'sizes' => $sizes, 'type' => fileman::getType($fName));
                }
            } else {
                $iconInfoArr[] = array('src' => 'favicon.ico', 'sizes' => '512x512', 'type' => fileman::getType('favicon.ico'));
            }

            // Допълваме липсващите размери
            if (!empty($iconInfoArr)) {
                $iconSizes = array(72, 96, 128, 144, 152, 192, 384, 512);
                foreach ($iconSizes as $key => $iSize) {
                    foreach ($iconInfoArr as $iInfo) {
                        if ($iInfo['sizes'] == $iSize . 'x' . $iSize) {

                            unset($iconSizes[$key]);

                            continue;
                        }
                    }
                }

                if (!empty($iconSizes)) {
                    foreach ($iconSizes as $key => $iSize) {
                        $iconInfoArr[] = array('src' => $iconInfoArr[0]['src'], 'sizes' => $iSize . 'x' . $iSize, 'type' => $iconInfoArr[0]['type']);
                    }
                }
            }

            $shortcuts = array();
            $me = cls::get(get_called_class());
            for ($i = 1; $i <= $me->shortcutCnt; $i++) {
                if (!trim($rec->{"sc{$i}Name"})) {

                    continue;
                }

                $shortcuts[] = (object)array(
                    'name' => tr($rec->{"sc{$i}Name"}),
                    'short_name' => tr($rec->{"sc{$i}ShortName"}),
                    'description' => tr($rec->{"sc{$i}Description"}),
                    'url' => $rec->{"sc{$i}Url"},
                    'icons' => array(
                        (object)array(
                            'src' => fileman_Download::getDownloadUrl($rec->{"sc{$i}Icon"}, 10000, 'handler', false),
                            'sizes' => '512x512',
                        ),
                    ),
                );
            }

            $json = array(
                'short_name' => tr($rec->shortName),
                'name' => tr($rec->name),
                'description' => tr($rec->description),
                'display' => $rec->display,
                'background_color' => $rec->backgroundColor,
                'theme_color' => $rec->themeColor,
                'start_url' => $rec->startUrl,
                'shortcuts' => $shortcuts,
                'id' => $rec->startUrl,
                'scope' => $rec->scope,
                'icons' => $iconInfoArr,
                'share_target' => array(
                    'action' => '/pwa_Share/Target',
                    'method' => 'POST',
                    'enctype' => 'multipart/form-data',
                    'params' => array(
                        'title' => 'name',
                        'text' => 'description',
                        'url' => 'link',
                        'files' => array(
                            array('name' => 'file',
                                'accept' => array('*/*')
                            ),
                        ),
                    )
                ),
            );

            core_Cache::set('pwaManifest', 'manifest', $json, 100000, array('pwa_Settings'));
        }

        return json_encode($json);
    }


    /**
     * Помощна фунцкция за проверка дали може да се използва PWA
     *
     * @return string - yes|no
     *
     * @deprecated
     */
    public static function canUse($dId = null)
    {
        $defSettings = pwa_Settings::getDomains();
        if (empty($defSettings)) {

            return 'no';
        }

        if (isset($dId) && $dId > 0) {
            $pDomain = cms_Domains::fetchField($dId, 'domain');
        } else {
            $pDomain = cms_Domains::getPublicDomain('domain');
        }

        foreach ($defSettings as $domainId => $domainName) {
            if ($pDomain == $domainName) {

                return 'yes';
            }
        }

        return 'no';
    }


    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;

        $appTitle = tr(core_Setup::get('EF_APP_TITLE', true));
        $text = tr('интегрирана система за управление');

        $form->setDefault('name', $appTitle . ' - ' . $text);
        $form->setDefault('description', $appTitle . ' - ' . $text);
        $form->setDefault('shortName', $appTitle);
        $form->setDefault('display', 'standalone');
        $form->setDefault('backgroundColor', '#fff');
        $form->setDefault('themeColor', '#ddd');
        $form->setDefault('startUrl', '/?isPwa=yes');
        $form->setDefault('scope', '/');
        $form->setDefault('orientation', 'any');

        $form->setDefault('sc1Name', 'Сканиране на баркод');
        $form->setDefault('sc1ShortName', 'Баркод');
        $form->setDefault('sc1Description', 'Сканиране и търсене на информация за баркод');
        $form->setDefault('sc1Url', '/barcode_Search');

        $form->setDefault('state', 'active');
    }
}