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
        $url = rtrim($url, '/') . '/' . $expAdd . '/export';
        $res = file_get_contents($url);
        $resArr = unserialize(gzuncompress($res));
        
        return $resArr;
    }
    
    
}
