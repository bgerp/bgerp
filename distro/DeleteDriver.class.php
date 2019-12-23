<?php


/**
 * Изтриване на файлове
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
class distro_DeleteDriver extends core_Mvc
{
    /**
     * Поддържа интерфейса за драйвер
     */
    public $interfaces = 'distro_ActionsDriverIntf';
    
    
    /**
     * Заглавие на драйвера
     */
    public $title = 'Изтриване';
    
    
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
        $fieldset->FLD('delSourceFh', 'fileman_FileType(bucket=' . distro_Group::$bucket . ')', 'caption=Файл, input=none');
        $fieldset->FLD('delFileName', 'varchar', 'caption=Име на файла, input=none');
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
            
            return '';
        }
        
        $DFiles = cls::get('distro_Files');
        
        $fPath = $DFiles->getRealPathOfFile($rec->fileId, $rec->repoId);
        $fPath = escapeshellarg($fPath);
        
        $deleteExec = "rm {$fPath}";
        
        return $deleteExec;
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
        
        $rec->delSourceFh = $fRec->sourceFh;
        $rec->delFileName = $fRec->name;
        $rec->StopExec = true;
        
        distro_Actions::save($rec);
        
        distro_Files::delete($fRec->id);
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
        return array('ef_icon' => 'img/16/delete.png', 'warning' => 'Сигурни ли сте, че искате да изтриете файла?');
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
     * След преобразуване на записа в четим за хора вид.
     *
     * @param distro_DeleteDriver $mvc
     * @param distro_Actions      $embeder
     * @param stdClass            $row     Това ще се покаже
     * @param stdClass            $rec     Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, $embeder, &$row, $rec)
    {
        if ($rec->delFileName) {
            $fileName = '"' . type_Varchar::escape($rec->delFileName) . '"';
            
            if (trim($rec->delSourceFh)) {
                $fileName = fileman::getLinkToSingle($rec->delSourceFh, false, array(), $rec->name);
            }
            
            $row->Info = tr($mvc->title) . ' ' . tr('на') . ' ' . $fileName;
            
            if ($rec->repoId) {
                $row->Info .= ' ' . tr('от') . ' ' . distro_Repositories::getLinkToSingle($rec->repoId, 'name');
            }
        }
    }
}
