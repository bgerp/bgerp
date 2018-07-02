<?php



/**
 * Дефолтна имплементация на вътрешен обект за core_Embedder (драйвер)
 *
 * @category  bgerp
 * @package   core
 * @author    Milen Georgiev (milen2experta.bg)
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class core_ProtoInner extends core_BaseClass
{

    /**
     * Обект с информация за ембедъра
     */
    public $EmbedderRec;


    /**
     * Вътрешно, изчислено състояние на драйвъра
     */
    protected $innerState;
    
    
    /**
     * Записа на формата, с която е създаден/модифициран драйвера
     */
    protected $innerForm;

    
    /**
     * Може ли вградения обект да се избере
     */
    public function canSelectInnerObject($userId = null)
    {
        return true;
    }


    /**
     * Задава вътрешната форма
     *
     * @param mixed $innerForm
     */
    public function setInnerForm($form)
    {
        $this->innerForm = $form;
    }
    
    
    /**
     * Задава вътрешното състояние
     *
     * @param mixed $innerState
     */
    public function setInnerState($state)
    {
        $this->innerState = $state;
    }

    
    /**
     * Добавя полетата на вътрешния обект
     *
     * @param core_Form $fieldset
     */
    public function addEmbeddedFields(core_FieldSet &$form)
    {
    }
    
    
    /**
     * Подготвя формата за въвеждане на данни за вътрешния обект
     *
     * @param core_Form $form
     */
    public function prepareEmbeddedForm(core_Form &$form)
    {
    }
    
    
    /**
     * Проверява въведените данни
     *
     * @param core_Form $form
     */
    public function checkEmbeddedForm(core_Form &$form)
    {
    }
    
    
    /**
     * Подготвя данните необходими за показването на вградения обект
     */
    public function prepareEmbeddedData_()
    {
    }


    /**
     * Рендира вградения обект
     *
     * @param stdClass $data
     */
    public function renderEmbeddedData(&$embedderTpl, $data)
    {
    }

    
    /**
     * Променя ключовите думи
     *
     * @param string $searchKeywords
     */
    public function alterSearchKeywords(&$keywords)
    {
    }
    
    
    /**
     * Кои са полетата на драйвера
     *
     * @param array
     */
    public function getDriverFields()
    {
        $form = cls::get('core_Form');
        $this->addEmbeddedFields($form);
        
        return arr::make(array_keys($form->selectFields()), true);
    }
}
