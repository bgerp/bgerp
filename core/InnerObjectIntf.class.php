<?php


/**
 * Интерфейс за създаване драйвери за вграждане в други обекти
 *
 *
 * @category  bgerp
 * @package   core
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class core_InnerObjectIntf
{
    /**
     * Инстанция на класа имплементиращ интерфейса
     */
    public $class;
    
    
    /**
     * Задава вътрешната форма
     *
     * @param mixed $innerForm
     */
    public function setInnerForm($innerForm)
    {
        return $this->class->addEmbeddedFields($innerForm);
    }
    
    
    /**
     * Задава вътрешното състояние
     *
     * @param mixed $innerState
     */
    public function setInnerState($innerState)
    {
        return $this->class->addEmbeddedFields($innerState);
    }
    
    
    /**
     * Добавя полетата на вътрешния обект
     *
     * @param core_Form $fieldset
     */
    public function addEmbeddedFields(core_FieldSet &$fieldset)
    {
        return $this->class->addEmbeddedFields($fieldset);
    }
    
    
    /**
     * Подготвя формата за въвеждане на данни за вътрешния обект
     *
     * @param core_Form $form
     */
    public function prepareEmbeddedForm(core_Form &$form)
    {
        return $this->class->prepareEmbeddedForm($form);
    }
    
    
    /**
     * Проверява въведените данни
     *
     * @param core_Form $form
     */
    public function checkEmbeddedForm(core_Form &$form)
    {
        return $this->class->checkEmbeddedForm($form);
    }
    
    
    /**
     * Подготвя вътрешното състояние, на база въведените данни
     *
     * @param core_Form $innerForm
     */
    public function prepareInnerState()
    {
        return $this->class->prepareInnerState();
    }
    
    
    /**
     * Подготвя данните необходими за показването на вградения обект
     */
    public function prepareEmbeddedData()
    {
        return $this->class->prepareEmbeddedData();
    }
    
    
    /**
     * Рендира вградения обект
     *
     * @param stdClass $data
     */
    public function renderEmbeddedData(&$embedderTpl, $data)
    {
        return $this->class->renderEmbeddedData($embedderTpl, $data);
    }
    
    
    /**
     * Може ли вградения обект да се избере
     */
    public function canSelectInnerObject($userId = null)
    {
        return $this->class->canSelectInnerObject($userId = null);
    }
    
    
    /**
     * Променя ключовите думи
     *
     * @param string $searchKeywords
     */
    public function alterSearchKeywords(&$searchKeywords)
    {
        return $this->class->alterSearchKeywords($searchKeywords);
    }
}
