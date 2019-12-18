<?php


/**
 * Интерфейс за създаване на отчети от различни източници в системата
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgerp_PortalBlockIntf extends embed_DriverIntf
{
    /**
     * Инстанция на класа имплементиращ интерфейса
     */
    public $class;
    
    
    /**
     * Максимален брой блокове, които да могат да се поакзват в портала
     */
    public $maxCnt;
    
    
    /**
     * Подготвя данните
     * 
     * @param stdClass $dRec
     * @param null|integer $userId
     * 
     * @return stdClass
     */
    public function prepare($dRec, $userId = null)
    {
        return $this->class->prepare($dRec, $userId);
    }
    
    
    /**
     * Рендира данните
     * 
     * @param stdClass $data
     * 
     * @return core_ET
     */
    public function render($data)
    {
        return $this->class->render($data);
    }
    
    
    /**
     * Връща заглавието за таба на съответния блок
     *
     * @param stdClass $dRec
     *
     * @return string
     */
    public function getBlockTabName($dRec)
    {
        return $this->class->getBlockTabName($dRec);
    }
    
    
    /**
     * Помощна функция за вземане на ключа за кеша
     *
     * @param stdClass $dRec
     * @param null|integer $userId
     *
     * @return string
     */
    public function getCacheKey($dRec, $userId = null)
    {
        return $this->class->getCacheKey($dRec, $userId);
    }
    
    
    /**
     * Името на стойността за кеша
     *
     * @param integer $userId
     *
     * @return string
     */
    public function getCacheTypeName($userId = null)
    {
        return $this->class->getCacheTypeName($userId);
    }
}
