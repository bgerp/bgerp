<?php


/**
 * Синхронизиране на е-магазин между bgERP системи
 *
 *
 * @category  bgerp
 * @package   synck
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2020 - 2020 Experta OOD
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
        self::requireRight();

        expect(core_Packs::isInstalled('eshop'));
        
        core_App::setTimeLimit(1000);
        
        $res = array();
        
        core_Users::forceSystemUser();
        
        $eQuery = eshop_Products::getQuery();
        
        $groups = sync_Setup::get('ESHOP_GROUPS');
        
        if ($groups) {
            $eQuery->in('groupId', type_Keylist::toArray($groups));
        }
        
        while ($rec = $eQuery->fetch()) {
            sync_Map::exportRec('eshop_Products', $rec->id, $res, $this);
        }
        
        core_Users::cancelSystemUser();
        
        return self::outputRes($res);
    }


    /**
     * Синхронизира двете системи
     */
    public function act_Import()
    {
        self::requireRight('import');
        
        expect(core_Packs::isInstalled('eshop'));
        
        core_App::setTimeLimit(1000);
        
        $resArr = self::getDataFromUrl(get_called_class());
        
        core_Users::forceSystemUser();
        
        Mode::set('preventNotifications', true);
        Mode::set('syncing', true);
        
        foreach ($resArr as $class => $objArr) {
            foreach ($objArr as $id => $rec) {
                sync_Map::importRec($class, $id, $resArr, $this);
            }
        }
    }
    
    
    /**
     * Експортиране на domain и lang полето
     *
     * @param stdClass $rec
     * @param string $fName
     * @param stdClass $field
     * @param array $res
     * @param string $controller
     */
    public static function fixDomainExport(&$rec, $fName, $field, &$res, $controller)
    {
        $lang = self::$fNewNamePref . 'lang';
        $domain = self::$fNewNamePref . 'domain';
        
        if (!isset($rec->{$lang}) && !isset($rec->{$domain})) {
            $oRec = clone $rec;
            
            foreach ((array)$rec as $fName => $fVal) {
                unset($rec->{$fName});
            }
            
            $rec->{$lang} = $oRec->lang;
            $rec->{$domain} = $oRec->domain;
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
     */
    public static function fixDomainImport(&$rec, $fName, $field, &$res, $controller)
    {
        $lang = self::$fNewNamePref . 'lang';
        $domain = self::$fNewNamePref . 'domain';
        
        $haveDomains = false;
        if (isset($rec->{$lang}) || isset($rec->{$domain})) {
            $domains = trim(sync_Setup::get('CMS_DOMAINS'));
            if ($domains) {
                $dArr = explode("\n", $domains);
                foreach ($dArr as $dStr) {
                    list($remote, $local) = explode('|', $dStr);
                    
                    list($remoteDomain, $remoteLang) = explode(',', $remote);
                    list($localDomain, $localLang) = explode(',', $local);
                    
                    $remoteDomain = trim($remoteDomain);
                    $remoteLang = trim($remoteLang);
                    $localDomain = trim($localDomain);
                    $localLang = trim($localLang);
                    
                    $rec->{$lang} = trim($rec->{$lang});
                    $rec->{$domain} = trim($rec->{$domain});
                    
                    expect($remoteDomain && $remoteLang && $localDomain && $localLang);
                    
                    if (($rec->{$lang} == $remoteLang) && ($rec->{$domain} == $remoteDomain)) {
                        $haveDomains = true;
                        
                        $rec->lang = $localLang;
                        $rec->domain = $localDomain;
                        
                        break;
                    }
                }
            }
            
            if (!$haveDomains) {
                $rec->lang = $rec->{$lang};
                $rec->domain = $rec->{$domain};
            }
            
            foreach ((array)$rec as $fName => $fVal) {
                if (($fName == 'lang') || ($fName == 'domain')) {
                    continue;
                }
                
                unset($rec->{$fName});
            }
            
            expect($rec->lang && $rec->domain, $rec);
            
            $oRec = cms_Domains::fetch(array("#domain = '[#1#]' AND #lang = '[#2#]'", $rec->domain, $rec->lang));
            
            expect($oRec, $rec);
            
            foreach ((array)$oRec as $fName => $fVal) {
                $rec->{$fName} = $fVal;
            }

            unset($rec->{$lang});
            unset($rec->{$domain});
            
            $rec->__continue = true;
            
            $rec->__id = $rec->id;
        }
    }
}
