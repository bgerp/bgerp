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
     * Изключва непозволените домейни преди рекурсивния import и старите mappings.
     */
    public static function on_BeforeSyncImportAll($mvc, &$resArr, $controller, $update = null)
    {
        if (empty($resArr['cms_Domains'])) {

            return;
        }
        $mappings = self::getDomainMappings();
        if ($mappings === false) {
            sync_Map::$importErrors++;
            $failed = array('cms_Domains' => array_fill_keys(array_keys((array) $resArr['cms_Domains']), true));
            self::markDomainBranches($resArr, $controller, $failed, sync_Map::IMPORT_FAILED);

            return;
        }
        if (!$mappings) {

            return;
        }

        $skipped = $failed = $notices = array();
        foreach ((array) ($resArr['cms_Domains'] ?? array()) as $id => $rec) {
            if (!is_object($rec)) {
                $failed['cms_Domains'][$id] = true;
                sync_Map::$importErrors++;
                self::logErr("Невалиден sync запис за cms_Domains::{$id}. Зависимите записи се пропускат");

                continue;
            }
            $domain = $rec->{self::$fNewNamePref . 'domain'} ?? null;
            $lang = $rec->{self::$fNewNamePref . 'lang'} ?? null;
            if (!is_string($domain) || !is_string($lang) || trim($domain) === '' || trim($lang) === '') {
                $failed['cms_Domains'][$id] = true;
                sync_Map::$importErrors++;
                self::logErr("Липсва домейн или език в sync данните за cms_Domains::{$id}. Зависимите записи се пропускат");

                continue;
            }
            $domain = trim($domain);
            $lang = trim($lang);
            if (!self::matchDomainMapping($domain, $lang, $mappings)) {
                $skipped['cms_Domains'][$id] = true;
                $notices[$domain . ',' . $lang] = true;
            }
        }
        self::markDomainBranches($resArr, $controller, $failed, sync_Map::IMPORT_FAILED);
        self::markDomainBranches($resArr, $controller, $skipped, sync_Map::IMPORT_SKIPPED);
        foreach ($notices as $source => $unused) {
            self::logNotice("Пропуснат sync домейн без съответствие: {$source}. Зависимите записи се пропускат");
        }
    }


    /**
     * Разпространява състоянието по единичните зависимости, без промяна на payload-а.
     */
    private static function markDomainBranches($resArr, $controller, $marked, $status)
    {
        if (!$marked) {

            return;
        }

        $references = array();
        foreach ($resArr as $class => $records) {
            if ((!is_array($records) && !is_object($records)) || !cls::load($class, true)) {
                continue;
            }
            foreach (cls::get($class)->selectFields("#kind == 'FLD'") as $name => $field) {
                $type = $field->type ?? null;
                if ($type instanceof type_CustomKey) {
                    continue;
                }
                if ($type instanceof type_Key || $type instanceof type_Key2) {
                    $targetClass = $type->params['mvc'] ?? null;
                    if ($targetClass && empty($controller->globalUniqKeys[$targetClass])) {
                        $references[$class][$name] = $targetClass;
                    }
                } elseif ($type instanceof type_Int && in_array($name, array('saoParentId', 'saoRelative'))) {
                    $references[$class][$name] = $class;
                }
            }
        }

        // Само единичните връзки повличат записа; keylist запазва разрешените членове.
        do {
            $changed = false;
            foreach ($references as $class => $fields) {
                foreach ((array) $resArr[$class] as $id => $rec) {
                    if (!is_object($rec) || isset($marked[$class][$id])) {
                        continue;
                    }
                    foreach ($fields as $name => $targetClass) {
                        $targetId = $rec->{$name} ?? null;
                        if ((is_int($targetId) || is_string($targetId)) && isset($marked[$targetClass][$targetId])) {
                            $marked[$class][$id] = true;
                            $changed = true;
                            break;
                        }
                    }
                }
            }
        } while ($changed);

        foreach ($marked as $class => $ids) {
            foreach ($ids as $id => $unused) {
                // Истинска грешка не се понижава до умишлено пропускане.
                if ((sync_Map::$imported[$class][$id] ?? null) !== sync_Map::IMPORT_FAILED) {
                    sync_Map::$imported[$class][$id] = $status;
                }
            }
        }
    }


    /**
     * @return array|null|false Правила, NULL за автоматичен избор или FALSE при грешка
     */
    private static function getDomainMappings()
    {
        $domains = trim(sync_Setup::get('CMS_DOMAINS') ?? '');
        if ($domains === '') {

            return null;
        }

        $mappings = array();
        foreach (explode("\n", $domains) as $line => $mapping) {
            if (trim($mapping) === '') {
                continue;
            }
            list($remote, $local) = explode('|', $mapping) + array('', '');
            list($remoteDomain, $remoteLang) = array_map('trim', explode(',', $remote)) + array('', '');
            list($localDomain, $localLang) = array_map('trim', explode(',', $local)) + array('', '');
            $allSources = trim($remote) === '*';
            if (!$remoteDomain || (!$remoteLang && !$allSources) || !$localDomain || !$localLang ||
                $localDomain === '*' || $localLang === '*') {
                self::logErr('Невалидно съответствие на домейни в sync, ред ' . ($line + 1) .
                    '. Формат: домейн,език|локален-домейн,език; *,език|локален-домейн,език; *|локален-домейн,език. Зависимите записи се пропускат');

                return false;
            }
            $mappings[] = array($remoteDomain, $remoteLang, $localDomain, $localLang, $allSources);
        }

        return $mappings;
    }


    /**
     * По-конкретното правило има предимство независимо от реда.
     */
    private static function matchDomainMapping($domain, $lang, $mappings)
    {
        $target = null;
        $bestMatch = 0;
        foreach ($mappings as $mapping) {
            list($remoteDomain, $remoteLang, $localDomain, $localLang, $allSources) = $mapping;
            $match = 0;
            if ($domain === $remoteDomain && $lang === $remoteLang) {
                $match = 3;
            } elseif ($remoteDomain === '*' && $lang === $remoteLang) {
                $match = 2;
            } elseif ($allSources) {
                $match = 1;
            }
            if ($match > $bestMatch) {
                $bestMatch = $match;
                $target = array($localDomain, $localLang);
            }
        }

        return $target;
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

            $oRec = null;
            $targetDomain = $sourceDomain;
            $targetLang = $sourceLang;
            $mappings = self::getDomainMappings();
            if ($mappings === false) {

                return false;
            }
            if ($mappings !== null) {
                $target = self::matchDomainMapping($sourceDomain, $sourceLang, $mappings);
                if (!$target) {
                    // Обединеният import вече е пропуснал този клон преди callbacks.
                    return false;
                }
                list($targetDomain, $targetLang) = $target;
            } else {
                // Автоматичният избор важи само при празен списък със съответствия.
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
