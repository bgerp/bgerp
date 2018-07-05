<?php



/**
 * Интерфейс за класовете ползващи перманентни данни
 *
 *
 * @category  vendors
 * @package   permanent
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за перманентни данни
 */
class permanent_SettingsIntf
{
    
    
    /**
     * Връща ключ под който ще се запишат данните
     */
    public function getSettingsKey()
    {
        return $this->class->getSettingsKey();
    }
    
    
    /**
     * Подготвя празна форма, така че да показва полетата
     * за настройките на обекта, заедно с текущите данни.
     *
     * @param object $form
     */
    public function prepareSettingsForm($form)
    {
        return $this->class->prepareSettingsForm($form);
    }
    
    
    /**
     * Извлича данните от формата със заредени от Request данни,
     * като може да им направи специализирана проверка коректност.
     * Ако след извикването на този метод $form->getErrors() връща TRUE,
     * то означава, че данните не са коректни.
     * От формата данните попадат в тази част от вътрешното състояние на обекта,
     * която определя неговите settings
     *
     * @param object $form
     */
    public function setSettingsFromForm($form)
    {
        return $this->class->setSettingsFromForm($form);
    }
    
    
    /**
     * Връща текущите настройки на обекта
     */
    public function getSettings()
    {
        $this->class->getSettings();
    }
    
    
    /**
     * Задава вътрешните настройки на обекта
     * @param object $data
     */
    public function setSettings($data)
    {
        $this->class->setSettings($data);
    }
}
