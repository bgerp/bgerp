<?php


/**
 * Архивиране на файлове (качване в системата)
 *
 * @category  bgerp
 * @package   distro
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class distro_ArchiveDriver extends core_Mvc
{
    /**
     * Поддържа интерфейса за драйвер
     */
    public $interfaces = 'distro_ActionsDriverIntf';
    
    
    /**
     * Заглавие на драйвера
     */
    public $title = 'Архивиране';
    
    
    /**
     * Плъгини и класове за зареждане
     */
    public $loadList = 'distro_Wrapper';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
    }
    
    
    /**
     * Може ли вградения обект да се избере
     *
     * @see distro_ActionsDriverIntf
     */
    public function canSelectDriver($userId = null)
    {
        return true;
    }
    
    
    /**
     * Дали може да се направи действието в екшъна към съответния файл
     *
     * @param int         $groupId
     * @param int         $repoId
     * @param int         $fileId
     * @param string|NULL $name
     * @param string|NULL $md5
     * @param int|NULL    $userId
     *
     * @return bool
     *
     * @see distro_ActionsDriverIntf
     */
    public function canMakeAction($groupId, $repoId, $fileId, $name = null, $md5 = null, $userId = null)
    {
        if ($fileId) {
            $fRec = distro_Files::fetch($fileId);
            
            if (isset($fRec->sourceFh)) {
                
                return false;
            }
        }
        
        return true;
    }
    
    
    /**
     * Връща стринга, който ще се пуска за обработка
     *
     * @param stdClass $rec
     *
     * @return string
     *
     * @see distro_ActionsDriverIntf
     */
    public function getActionStr($rec)
    {
        $fRec = distro_Files::fetch($rec->fileId);
        if ($fRec->sourceFh) {
            
            return '';
        }
        
        Request::setProtected(array('repoId', 'fileId'));
        
        $url = toUrl(array($this, 'uploadFile', 'repoId' => $rec->repoId, 'fileId' => $rec->fileId), 'absolute');
        
        $archiveExec = "wget -q --spider --no-check-certificate {$url}";
        
        return $archiveExec;
    }
    
    
    /**
     * Вика се след приключване на обработката
     *
     * @param stdClass $rec
     *
     * @see distro_ActionsDriverIntf
     */
    public function afterProcessFinish($rec)
    {
        // Обновяваме всички файлове със същия хеш, да имат същия sourceFh
        if ($rec->fileId) {
            $fRec = distro_Files::fetch((int) $rec->fileId);
            
            $fQuery = distro_Files::getQuery();
            $fQuery->where(array("#md5 = '[#1#]'", $fRec->md5));
            $fQuery->where("#sourceFh IS NULL OR #sourceFh = ''");

            while ($nRec = $fQuery->fetch()) {
                $nRec->sourceFh = $fRec->sourceFh;
                distro_Files::save($nRec, 'sourceFh');
            }
        }
    }
    
    
    /**
     * Може ли вградения обект да се избере
     *
     * @return array
     *
     * @see distro_ActionsDriverIntf
     */
    public function getLinkParams()
    {
        return array('ef_icon' => 'img/16/upload.png', 'warning' => 'Сигурни ли сте, че искате да архивирате файла?');
    }
    
    
    /**
     * Дали може да се форсира записването
     *
     * @return bool
     *
     * @see distro_ActionsDriverIntf
     */
    public function canForceSave()
    {
        return true;
    }
    
    
    /**
     * Предизвиква уплоад на файла в системата
     */
    public function act_UploadFile()
    {
        Request::setProtected(array('repoId', 'fileId'));
        
        $repoId = Request::get('repoId', 'int');
        $fileId = Request::get('fileId', 'int');
        
        $DFiles = cls::get('distro_Files');
        
        $fRec = $DFiles->fetch($fileId);
        
        expect($fRec);
        
        if ($fRec->sourceFh) {
            
            return false;
        }
        
        $conn = distro_Repositories::connectToRepo($repoId);
        
        if (!$conn) {
            
            return false;
        }
        
        $fPath = $DFiles->getRealPathOfFile($fileId, $repoId);
        
        $data = $conn->getContents($fPath);
        $name = pathinfo($fPath, PATHINFO_BASENAME);
        
        $fileHnd = fileman::absorbStr($data, distro_Group::$bucket, $name);
        
        expect($fileHnd);
        
        $fRec->sourceFh = $fileHnd;
        $DFiles->save($fRec, 'sourceFh');
    }
}
