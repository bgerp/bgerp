<?php


/**
 * Помощен клас за синхронизиране между две bgERP системи
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
 * @title     Помощен клас за синхронизиране между две bgERP системи
 */
class sync_Helper extends core_Manager
{
    
    /**
     * Какво друго да експортираме?
     */
    public $exportAlso = array();
    
    
    /**
     * На кои класове да се търси аналог в системата
     */
    public $mapClass = array();
    
    
    /**
     * Глобални уникални ключове
     */
    public $globalUniqKeys = array(
            'drdata_Countries' => 'letterCode2',
            'core_Roles' => 'role',
            'core_Classes' => 'name',
            'currency_Currencies' => 'code',
    );
    
    
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
    );
    
    
    /**
     * Префикс за имената на променливите
     */
    protected static $fNewNamePref = '__';
    
    
    /**
     * Проверка за права за пускане
     */
    public static function requireRight($type = 'export')
    {
        expect(core_Packs::isInstalled('sync'));
        
        if ($type == 'export') {
            if (haveRole('user')) {
                requireRole('admin');
            } else {
                expect($remoteAddr = $_SERVER['REMOTE_ADDR']);
                
                if (defined('SYNC_EXPORT_ADDR')) {
                    expect(SYNC_EXPORT_ADDR == $remoteAddr);
                } else {
                    expect(core_Url::isPrivate($remoteAddr));
                }
            }
        } else {
            requireRole('admin');
        }
    }
    
    
    /**
     * Отпечатва резултата
     * 
     * @param array $resArr
     * @param boolean $usersFirst
     */
    public static function outputRes($resArr, $usersFirst = true)
    {
        $resArr = array_reverse($resArr, true);
        
        if ($usersFirst) {
            if ($resArr['core_Users'] && (countR($resArr) > 1)) {
                $resArr2 = array();
                $resArr2['core_Users'] = $resArr['core_Users'];
                unset($resArr['core_Users']);
                $resArr2 += $resArr;
                $resArr = $resArr2;
            }
        }
        
        $resArr = gzcompress(serialize($resArr));
        
        echo $resArr;
        
        shutdown();
    }
    
    
    /**
     * Връща данните от експорт адреса
     * 
     * @param string $expAdd
     * 
     * @return array
     */
    public static function getDataFromUrl($expAdd)
    {
        ini_set('default_socket_timeout', 600);
        
        $url = sync_Setup::get('EXPORT_URL');
        expect($url);
        $url = rtrim($url, '/') . '/' . $expAdd . '/export';
        $res = file_get_contents($url);
        $resArr = unserialize(gzuncompress($res));
        
        return $resArr;
    }
    
    
    /**
     * Експортиране на folderId
     * 
     * @param stdClass $rec
     * @param string $fName
     * @param stdClass $field
     * @param array $res
     * @param string $controller
     */
    public static function fixFolderIdExport(&$rec, $fName, $field, &$res, $controller)
    {
        if (!isset($rec->{$fName})) {
            unset($rec->{$fName});
            
            return ;
        }
        
        $fRec = doc_Folders::fetch($rec->{$fName});
        
        if (!$fRec) {
            unset($rec->{$fName});
            
            return ;
        }
        
        $coverClassName = self::$fNewNamePref . 'coverClass';
        $coverIdName = self::$fNewNamePref . 'coverId';
        
        $rec->{$coverClassName} = cls::get($fRec->coverClass)->className;
        $rec->{$coverIdName} = $fRec->coverId;
        
        $rec->{$fName} = null;
        
        sync_Map::exportRec($fRec->coverClass, $fRec->coverId, $res, $controller);
    }
    
    
    /**
     * Импортиране на folderId
     * 
     * @param stdClass $rec
     * @param string $fName
     * @param stdClass $field
     * @param array $res
     * @param string $controller
     */
    public static function fixFolderIdImport(&$rec, $fName, $field, &$res, $controller)
    {
        $coverClassName = self::$fNewNamePref . 'coverClass';
        $coverIdName = self::$fNewNamePref . 'coverId';
        
        if (!isset($rec->{$coverClassName}) || !isset($rec->{$coverIdName})) {
            unset($coverClassName);
            unset($coverIdName);
            
            return ;
        }
        
        $iRecId = sync_Map::importRec($rec->{$coverClassName}, $rec->{$coverIdName}, $res, $controller);
        if ($iRecId) {
            if (cls::load($rec->{$coverClassName}, true) && cls::haveInterface('doc_FolderIntf', $rec->{$coverClassName})) {
                $inst = cls::get($rec->{$coverClassName});
                
                if (($iRecFetch = $inst->fetch($iRecId)) && ($folderId = $inst::forceCoverAndFolder($iRecFetch))) {
                    $rec->folderId = $folderId;
                }
            }
        }
        
        
        unset($coverClassName);
        unset($coverIdName);
    }
}
