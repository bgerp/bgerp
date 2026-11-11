<?php


/**
 * Синхронизиране на е-магазин между bgERP системи
 *
 *
 * @category  bgerp
 * @package   synck
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2020 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Синхронизиране на е-магазин между bgERP системи
 */
class sync_Eshop extends sync_Helper
{
    
    
    /**
     * Полета от моделите, които не трябва да се експортират
     */
    public $fixedExport = array(
            '*::createdOn' => null,
            '*::createdBy' => null,
            '*::modifiedOn' => null,
            '*::modifiedBy' => null,
            '*::searchKeywords' => null,
            '*::folderId' => null,
            'cat_Products::folderId' => 'sync_Helper::fixFolderId',
            'price_Lists::folderId' => 'sync_Helper::fixFolderId',
            '*::containerId' => null,
            '*::originId' => null,
            '*::threadId' => null,
            '*::ps5Enc' => null,
            '*::exSysId' => null,
            '*::lastLoginTime' => null,
            '*::lastLoginTime' => null,
            '*::lastLoginIp' => null,
            '*::lastActivityTime' => null,
            '*::lastUsedOn' => null,
            '*::id' => null,
            'eshop_Products::nearProducts' => null,
            'cms_Domains::domain' => 'sync_Eshop::fixDomain',
            'cms_Domains::lang' => 'sync_Eshop::fixDomain',
    );
    
    
    /**
     * Какво друго да експортираме?
     */
    public $exportAlso = array(
            'eshop_Products' => array(
                    array('eshop_ProductDetails' => 'eshopProductId'),
            ),
            'price_Lists' => array(
                    array('price_ListRules' => 'listId'),
            ),
            'cat_Products' => array(
                    array('cat_products_Packagings' => 'productId'),
                    array('price_ListRules' => 'productId'),
                    array('cat_products_Params' => 'classId|productId'),
            ),
    );
    
    
    /**
     *  Връща Json-a на филтрираните обекти
     */
    public function act_Export()
    {
        self::requireRight('export', true);
        sync_Settings::guardExportRun(
            sync_Settings::getRequestSettings(false, true)
        );

        expect(core_Packs::isInstalled('eshop'));

        core_App::setTimeLimit(1000);

        $res = array();

        core_Users::forceSystemUser();
        try {
            self::collectExport($res);
        } finally {
            core_Users::cancelSystemUser();
        }

        return self::outputRes($res);
    }


    /**
     * Събира артикулите от е-магазина (по настроените в sync_Settings групи) в масива за експорт
     *
     * @param array         $res
     * @param stdClass|null $controller
     */
    public static function collectExport(&$res, $controller = null)
    {
        $settingsRec = sync_Settings::getRequestSettings(true, true);
        $mode = sync_Settings::getExportMode($settingsRec, 'eshop');
        if ($mode == 'none') {

            return;
        }

        $me = $controller ?: cls::get(get_called_class());

        $eQuery = eshop_Products::getQuery();

        if ($mode == 'selected') {
            if (empty($settingsRec->syncEshopGroups)) {

                return;
            }

            $eQuery->in('groupId', type_Keylist::toArray($settingsRec->syncEshopGroups));
        }

        while ($rec = $eQuery->fetch()) {
            sync_Map::exportRec('eshop_Products', $rec->id ?? null, $res, $me);
        }
    }


    /**
     * Синхронизира двете системи
     */
    public function act_Import()
    {
        self::requireRight('import');
        
        expect(core_Packs::isInstalled('eshop'));

        core_App::setTimeLimit(1000);
        // Ръчният import може да върви успоредно с други; вдигаме лимита,
        // защото payload-ът се държи целият в паметта.
        ini_set('memory_limit', '2048M');

        $update = (Request::get('update') == 'none') ? false : true;

        $resArr = self::getDataFromUrl(get_called_class());

        core_Users::forceSystemUser();
        try {
            sync_Settings::importData($resArr, $update);
        } finally {
            core_Users::cancelSystemUser();
        }

        return;
    }
    
    
    /**
     * Експортиране на domain и lang полето
     *
     * @param stdClass $rec
     * @param string $fName
     * @param stdClass $field
     * @param array $res
     * @param string $controller
     * @param stdClass|null $exportState
     */
    public static function fixDomainExport(&$rec, $fName, $field, &$res, $controller, $exportState = null)
    {
        $lang = self::$fNewNamePref . 'lang';
        $domain = self::$fNewNamePref . 'domain';
        
        if (!isset($rec->{$lang}) && !isset($rec->{$domain})) {
            $oRec = clone $rec;
            
            foreach ((array)$rec as $fName => $fVal) {
                unset($rec->{$fName});
            }
            
            $rec->{$lang} = $oRec->lang ?? null;
            $rec->{$domain} = $oRec->domain ?? null;
        }
    }
    
    
    /**
     * Импортиране на domain и lang полето
     *
     * @param stdClass $rec
     * @param string $fName
     * @param stdClass $field
     * @param array $res
     * @param string $controller
     * @return void|false false при неразрешим локален домейн
     */
    public static function fixDomainImport(&$rec, $fName, $field, &$res, $controller, $update = null)
    {
        $lang = self::$fNewNamePref . 'lang';
        $domain = self::$fNewNamePref . 'domain';
        
        if (isset($rec->{$lang}) || isset($rec->{$domain})) {
            $sourceLang = trim($rec->{$lang} ?? '');
            $sourceDomain = trim($rec->{$domain} ?? '');
            if (!$sourceLang || !$sourceDomain) {
                self::logErr('Липсва домейн или език в sync данните за cms_Domains. Зависимите записи се пропускат');

                return false;
            }

            // Единствената локална цел не изисква съответствия на входящите имена.
            $query = cms_Domains::getQuery();
            $query->limit(2);
            $candidate = $query->fetch();
            $oRec = $candidate && !$query->fetch() ? $candidate : null;

            if (!$oRec) {
                $query = cms_Domains::getQuery();
                $query->where(array("#lang = '[#1#]'", $sourceLang));
                $query->limit(2);
                $candidate = $query->fetch();
                if ($candidate && !$query->fetch()) {
                    $oRec = $candidate;
                }
            }

            $targetDomain = $sourceDomain;
            $targetLang = $sourceLang;
            $domains = $oRec ? '' : trim(sync_Setup::get('CMS_DOMAINS') ?? '');
            if ($domains !== '') {
                $bestMatch = 0;
                $dArr = explode("\n", $domains);
                foreach ($dArr as $line => $dStr) {
                    if (trim($dStr) === '') {
                        continue;
                    }
                    list($remote, $local) = explode('|', $dStr) + array('', '');
                    
                    list($remoteDomain, $remoteLang) = explode(',', $remote) + array('', '');
                    list($localDomain, $localLang) = explode(',', $local) + array('', '');
                    
                    $remoteDomain = trim($remoteDomain);
                    $remoteLang = trim($remoteLang);
                    $localDomain = trim($localDomain);
                    $localLang = trim($localLang);
                    
                    $allSources = trim($remote) === '*';
                    if (!$remoteDomain || (!$remoteLang && !$allSources) ||
                        !$localDomain || !$localLang || $localDomain === '*' || $localLang === '*') {
                        self::logErr('Невалидно съответствие на домейни в sync, ред ' . ($line + 1) .
                            '. Формат: домейн,език|локален-домейн,език; *,език|локален-домейн,език; *|локален-домейн,език. Зависимите записи се пропускат');

                        return false;
                    }
                    
                    // По-конкретното правило има предимство независимо от реда.
                    $match = 0;
                    if ($sourceLang === $remoteLang && $sourceDomain === $remoteDomain) {
                        $match = 3;
                    } elseif ($sourceLang === $remoteLang && $remoteDomain === '*') {
                        $match = 2;
                    } elseif ($allSources) {
                        $match = 1;
                    }
                    if ($match > $bestMatch) {
                        $bestMatch = $match;
                        $targetLang = $localLang;
                        $targetDomain = $localDomain;
                    }
                }
            }

            if (!$oRec) {
                $oRec = cms_Domains::fetch(array("#domain = '[#1#]' AND #lang = '[#2#]'", $targetDomain, $targetLang));
            }

            if (!$oRec) {
                self::logErr("Няма еднозначен локален домейн за sync: {$sourceDomain},{$sourceLang} -> {$targetDomain},{$targetLang}. " .
                    'Проверете "Съответствие на домейни" в настройките на sync. Зависимите записи се пропускат');

                return false;
            }

            foreach ((array) $rec as $name => $value) {
                unset($rec->{$name});
            }
            
            foreach ((array)$oRec as $fName => $fVal) {
                $rec->{$fName} = $fVal;
            }

            unset($rec->{$lang});
            unset($rec->{$domain});
            
            $rec->__continue = true;
            
            $rec->__id = $rec->id ?? null;
        }
    }
    
    
    /**
     * Помощна функция за подготвяне на всички групи от електронния магазин
     * 
     * @param type_Keylist $type
     * @param NULL|array $options
     * 
     * @return array
     */
    public static function getEshopGroups($type, $options)
    {
        // core_Form извиква optionsFunc и за полета с input=none.
        if (!core_Packs::isInstalled('eshop')) {

            return array();
        }

        $gQuery = eshop_Groups::getQuery();
        
        $gQuery->where("#state != 'rejected'");
        
        $gQuery->EXT('domainId', 'cms_Content', 'externalName=domainId,externalKey=menuId');
        
        $gQuery->show('name, state, domainId, menuId');
        
        $gQuery->orderBy('domainId', 'DESC');
        $gQuery->orderBy('modifiedOn', 'DESC');
        
        $resArr = array();
        while ($gRec = $gQuery->fetch()) {
            $resArr[$gRec->id] = eshop_Groups::getVerbal($gRec, 'name') . ' (' . cms_Content::getVerbal($gRec->menuId, 'domainId') . ')';
            
            if ($gRec->state != 'active') {
                $resArr[$gRec->id] .= ' - ' . eshop_Groups::getVerbal($gRec, 'state');
            }
        }
        
        return $resArr;
    }
}
