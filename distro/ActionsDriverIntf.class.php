<?php


/**
 * Интерфейс за услуги, които могат да се ауторизират
 *
 *
 * @category  bgerp
 * @package   remote
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class distro_ActionsDriverIntf extends embed_DriverIntf
{
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
     */
    public function canMakeAction($groupId, $repoId, $fileId, $name = null, $md5 = null, $userId = null)
    {
        return $this->class->canMakeAction($groupId, $repoId, $fileId, $name, $md5, $userId);
    }
    
    
    /**
     * Връща стринга, който ще се пуска за обработка
     *
     * @param stdClass $rec
     */
    public function getActionStr($rec)
    {
        return $this->class->getActionStr($rec);
    }
    
    
    /**
     * Вика се след приключване на обработката
     *
     * @param stdClass $rec
     */
    public function afterProcessFinish($rec)
    {
        return $this->class->afterProcessFinish($rec);
    }
    
    
    /**
     * Връща параметрите на линка
     */
    public function getLinkParams()
    {
        return $this->class->getLinkParams();
    }
    
    
    /**
     * Дали може да се форсира записването
     *
     * @see distro_ActionsDriverIntf
     */
    public function canForceSave()
    {
        return $this->class->canForceSave();
    }
}
