<?php


/**
 * Копиране на файлове
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
class distro_CopyDriver extends core_Mvc
{
    /**
     * Дали да се използва sshpass
     */
    public $useSSHPass = true;
    
    
    /**
     * Поддържа интерфейса за драйвер
     */
    public $interfaces = 'distro_ActionsDriverIntf';
    
    
    /**
     * Заглавие на драйвера
     */
    public $title = 'Копиране';
    
    
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
        $fieldset->FLD('sourceRepoId', 'key(mvc=distro_Repositories, select=name)', 'caption=Копиране в');
        $fieldset->FLD('newFileName', 'varchar', 'caption=Име на файла, input=none');
        $fieldset->FLD('newFileId', 'key(mvc=distro_Files, select=name)', 'caption=Изходен файл, input=none');
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
        // Ако същия файл липсва в другото хранилище, тогава ще има възможност за копиране
        
        if (!distro_Group::canAddDetail($groupId, $userId)) {
            
            return false;
        }
        
        $dFileArr = distro_Files::getRepoWithFile($groupId, $md5, null, true);
        
        $reposArr = distro_Group::getReposArr($groupId);
        
        if (countR($dFileArr) >= countR($reposArr)) {
            
            return false;
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
        if (!$rec->repoId) {
            $rec->repoId = $rec->sourceRepoId;
        }
        
        $fRec = distro_Files::fetch($rec->fileId);
        
        $FileInst = cls::get('distro_Files');
        
        $srcFilePath = $FileInst->getRealPathOfFile($rec->fileId);
        $srcFilePath = escapeshellarg($srcFilePath);
        
        if (isset($rec->NewFilePath)) {
            $destFilePath = $rec->NewFilePath;
        } else {
            $destFilePath = $FileInst->getUniqFileName($rec->fileId, $rec->sourceRepoId);
        }
        
        $destFilePath = escapeshellarg($destFilePath);
        
        $hostParams = distro_Repositories::getHostParams($rec->sourceRepoId);
        
        $host = $hostParams['ip'];
        $port = $hostParams['port'];
        $user = $hostParams['user'];
        $pass = $hostParams['pass'];
        
        $copyExec = '';
        
        if (!$fRec->repoId) {
            $copyExec .= "wget -q --no-check-certificate -O {$destFilePath} {$srcFilePath}";
        } else {
            if ($this->useSSHPass) {
                $copyExec .= "sshpass -p {$pass} ";
            }
            
            $destFilePath = str_replace(' ', '\\ ', $destFilePath);
            $copyExec .= "scp -o StrictHostKeyChecking=no -P{$port} {$srcFilePath} {$user}@{$host}:{$destFilePath};";
        }
        
        return $copyExec;
    }
    
    
    /**
     * Може ли вградения обект да се избере
     *
     * @see distro_ActionsDriverIntf
     */
    public function getLinkParams()
    {
        return array('ef_icon' => 'img/16/copy16.png');
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
        $fRec = distro_Files::fetch($rec->fileId);
        
        $nRec = new stdClass();
        $nRec->groupId = $fRec->groupId;
        $nRec->sourceFh = $fRec->sourceFh;
        $nRec->md5 = $fRec->md5;
        
        $nRec->repoId = $rec->sourceRepoId;
        $nRec->name = $rec->newFileName;
        $nRec->createdBy = $rec->createdBy;
        
        if ($rec->createdBy > 0) {
            $sudoUser = core_Users::sudo($rec->createdBy);
        }
        
        $newFileId = distro_Files::save($nRec, null, 'IGNORE');
        
        core_Users::exitSudo($sudoUser);
        
        if ($newFileId) {
            $rec->newFileId = $newFileId;
            $rec->StopExec = true;
            distro_Actions::save($rec);
        }
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
        return false;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param distro_CopyDriver $mvc
     * @param distro_Actions    $embeder
     * @param stdClass          $data
     */
    public static function on_AfterPrepareEditForm($mvc, $embeder, $data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        $reposArr = array();
        
        // Вземаме масива с хранилищата, които са зададени в мастера
        $reposArr = distro_Group::getReposArr($rec->groupId);
        
        expect(!empty($reposArr));
        
        $fRec = distro_Files::fetch($rec->fileId);
        
        $rArr = distro_Files::getRepoWithFile($rec->groupId, $fRec->md5, null, true);
        
        // Премахваме, хранилищата, които съдържат сътоветния файл
        foreach ($rArr as $rRec) {
            unset($reposArr[$rRec->repoId]);
        }
        
        $form->setOptions('sourceRepoId', $reposArr);
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param distro_CopyDriver $mvc
     * @param distro_Actions    $embeder
     * @param core_Form         $form
     */
    public static function on_AfterInputEditForm($mvc, $embeder, $form)
    {
        if ($form->isSubmitted()) {
            $rec = $form->rec;
            $FileInst = cls::get('distro_Files');
            $rec->NewFilePath = $FileInst->getUniqFileName($rec->fileId, $rec->sourceRepoId);
            
            $rec->newFileName = pathinfo($rec->NewFilePath, PATHINFO_BASENAME);
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param distro_CopyDriver $mvc
     * @param distro_Actions    $embeder
     * @param stdClass          $row     Това ще се покаже
     * @param stdClass          $rec     Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, $embeder, &$row, $rec)
    {
        if ($rec->sourceRepoId) {
            $fileName = $embeder->getFileName($rec);
            $row->Info = tr($mvc->title) . ' ' . tr('на') . ' ' . $fileName;
            
            if ($rec->repoId) {
                $row->Info .= ' ' . tr('от') . ' ' . distro_Repositories::getLinkToSingle($rec->repoId, 'name');
            }
            
            $row->Info .= ' ' . tr('в') . ' ' . distro_Repositories::getLinkToSingle($rec->sourceRepoId, 'name');
            
            if ($rec->newFileId && $rec->newFileName && ($rec->newFileName != $rec->fileName)) {
                $newName = type_Varchar::escape($rec->newFileName);
                
                $row->Info .= ' ' . tr('с нов име') . " \"{$newName}\"";
            }
        }
    }
}
