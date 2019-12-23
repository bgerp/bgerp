<?php


/**
 * Клас 'doc_LinkedIntf' - Интерфейс за връзки между документи и файлове
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за връзки между документи и файлове
 */
class doc_LinkedIntf
{
    /**
     * Връща дейности, които са за дадения документ
     *
     * @param int $cId
     *
     * @return array
     */
    public function getActivitiesForDocument($cId)
    {
        return $this->class->getActivitiesForDocument($cId);
    }
    
    
    /**
     * Връща дейности, които са за дадения файл
     *
     * @param int $cId
     *
     * @return array
     */
    public function getActivitiesForFile($cId)
    {
        return $this->class->getActivitiesForFile($cId);
    }
    
    
    /**
     * Подготвяне на формата за документ
     *
     * @param core_Form $form
     * @param int       $cId
     * @param string    $activity
     */
    public function prepareFormForDocument($form, $cId, $activity)
    {
        return $this->class->prepareFormForDocument($form, $cId, $activity);
    }
    
    
    /**
     * Подготвяне на формата за файл
     *
     * @param core_Form $form
     * @param int       $cId
     * @param string    $activity
     */
    public function prepareFormForFile($form, $cId, $activity)
    {
        return $this->class->prepareFormForFile($form, $cId, $activity);
    }
    
    
    /**
     * След субмитване на формата за документ
     *
     * @param core_Form $form
     * @param int       $cId
     * @param string    $activity
     *
     * @return mixed
     */
    public function doActivityForDocument($form, $cId, $activity)
    {
        return $this->class->doActivityForDocument($form, $cId, $activity);
    }
    
    
    /**
     * След субмитване на формата за файл
     *
     * @param core_Form $form
     * @param int       $cId
     * @param string    $activity
     *
     * @return mixed
     */
    public function doActivityForFile($form, $cId, $activity)
    {
        return $this->class->doActivityForFile($form, $cId, $activity);
    }
}
