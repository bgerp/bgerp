<?php


/**
 * Редактиране на файлове
 *
 * @category  bgerp
 * @package   distro
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class distro_RenameDriver extends core_Mvc
{
    
    
	/**
     * Поддържа интерфейса за драйвер
     */
    public $interfaces = 'distro_ActionsDriverIntf';
    
    
    /**
     * Заглавие на драйвера
     */
    public $title = 'Редактиране';
    
    
    /**
     * Плъгини и класове за зареждане
     */
    public  $loadList = 'distro_Wrapper';
    
    
    /**
	 * Добавя полетата на драйвера към Fieldset
	 * 
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
	    $fieldset->FLD('newFileName', 'varchar', 'caption=Име');
	    $fieldset->FLD('oldFileName', 'varchar', 'caption=Файл, input=none');
	    $fieldset->FLD('newFileInfo', 'varchar', 'caption=Информация');
	    $fieldset->FLD('oldFileInfo', 'varchar', 'caption=Информация, input=none');
	}
    
    
    /**
     * Може ли вградения обект да се избере
     *
     * @see distro_ActionsDriverIntf
     */
    public function canSelectDriver($userId = NULL)
    {
        
        return TRUE;
    }
    
    
    /**
     * Дали може да се направи действието в екшъна към съответния файл
     * 
     * @param integer $groupId
     * @param integer $repoId
     * @param integer $fileId
     * @param string|NULL $name
     * @param string|NULL $md5
     * @param integer|NULL $userId
     * 
     * @return boolean
     * 
     * @see distro_ActionsDriverIntf
     */
    function canMakeAction($groupId, $repoId, $fileId, $name = NULL, $md5 = NULL, $userId = NULL)
    {
        
        return TRUE;        
    }
    
    
    /**
     * Връща стринга, който ще се пуска за обработка
     * 
     * @param stdObject $rec
     * 
     * @return string
     * 
     * @see distro_ActionsDriverIntf
     */
    function getActionStr($rec)
    {
        if (!$rec->RenameFile) return 'mv --help';
        
        $DFiles = cls::get('distro_Files');
        
        $fPath = $DFiles->getRealPathOfFile($rec->fileId, $rec->repoId);
        $fPath = escapeshellarg($fPath);
        
        $destPath = $DFiles->getRealPathOfFile($rec->fileId, $rec->repoId, NULL, $rec->newFileName);
        $destPath = escapeshellarg($destPath);
        
        $renameExec = "mv -i {$fPath} {$destPath}";
        
        return $renameExec;
    }
    
	
    /**
     * Вика се след приключване на обработката
     * 
     * @param stdObject $rec
     *
     * @see distro_ActionsDriverIntf
     */
    function afterProcessFinish($rec)
    {
        $fRec = distro_Files::fetch($rec->fileId);
        
        if ($fRec->name != $rec->newFileName) {
            $fRec->name = $rec->newFileName;
        }
        
        if ($fRec->info != $rec->newFileInfo) {
            $fRec->info = $rec->newFileInfo;
        }
        
        if ($rec->createdBy > 0) {
            $sudoUser = core_Users::sudo($rec->createdBy);
        }
        
        distro_Files::save($fRec, 'name, info, modifiedOn, modifiedBy');
        
        core_Users::exitSudo($sudoUser);
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
        
        return array('ef_icon' => 'img/16/edit-icon.png');
    }
    
    
    /**
     * Дали може да се форсира записването
     * 
     * @return boolean
     *
     * @see distro_ActionsDriverIntf
     */
    public function canForceSave()
    {
        
        return FALSE;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param distro_RenameDriver $mvc
     * @param distro_Actions $embeder
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, $embeder, &$data)
    {
        $fRec = distro_Files::fetch((int) $data->form->rec->fileId);
        $data->form->setDefault('newFileName', $fRec->name);
        $data->form->setDefault('newFileInfo', $fRec->info);
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param distro_RenameDriver $mvc
     * @param distro_Actions $embeder
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, $embeder, &$form)
    {
        if ($form->isSubmitted()) {
            $fRec = distro_Files::fetch((int) $form->rec->fileId);
            
            $haveChange = FALSE;
            
            // Ако е преименуван файла, проверяваме да няма файл, със същото име
            if ($fRec->name != $form->rec->newFileName) {
                $form->rec->oldFileName = $fRec->name;
                
                if ($form->rec->fileId && $form->rec->repoId) {
                    $sshObj = distro_Repositories::connectToRepo($form->rec->repoId);
                    
                    if ($sshObj) {
                        $dFile = cls::get('distro_Files');
                        $filePath = $dFile->getRealPathOfFile($form->rec->fileId, $form->rec->repoId, NULL, $form->rec->newFileName);
                        
                        $filePath = escapeshellarg($filePath);
                        
                        $sshObj->exec("if [ -f {$filePath} ]; then echo 'EXIST'; fi", $res);
                        
                        if (trim($res) == "EXIST") {
                            $form->setError('newFileName', 'Файлът със същото име съществува в хранилището');
                        }
                    }
                }
                
                $haveChange = TRUE;
                $form->rec->RenameFile = TRUE;
            }
            
            // Ако е редактирана информацията за файла
            if ($fRec->info != $form->rec->newFileInfo) {

                $haveChange = TRUE;
                $form->rec->oldFileInfo = $fRec->info;
            }
            
            // Ако няма никакви промени по полетата, да не се пускат обработки
            if (!$haveChange) {
                $form->setError('newFileName, newFileInfo', "Не сте направили никакви промени");
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param distro_RenameDriver $mvc
     * @param distro_Actions $embeder
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, $embeder, &$row, $rec)
    {
        $fRec = distro_Files::fetch($rec->fileId);
        
        $fileName = $embeder->getFileName($rec);
        
        if ($rec->oldFileName && $rec->newFileName != $rec->oldFileName) {
            $fileName .= ' (' . tr('старо име') . ' "' . type_Varchar::escape($rec->oldFileName) . '"' . ')';
        }
        
        $row->Info = tr($mvc->title) . ' ' . tr('на') . ' ' . $fileName . ' ' . tr('в') . ' ' . distro_Repositories::getLinkToSingle($rec->repoId, 'name');
    }
}
