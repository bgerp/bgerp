<?php


/**
 * Качване на файлове в системата
 *
 * @category  bgerp
 * @package   distro
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class distro_UploadDriver extends core_Mvc
{
    /**
     * Поддържа интерфейса за драйвер
     */
    public $interfaces = 'distro_ActionsDriverIntf';
    
    
    /**
     * Заглавие на драйвера
     */
    public $title = 'Качване';
    
    
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
        return false;
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
                
                return true;
            }
            
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
        $rec->OnlyCallback = TRUE;
        
        return '';
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
        return array();
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
}
