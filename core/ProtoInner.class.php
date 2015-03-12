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
class core_ProtoInner extends core_BaseClass {

    /**
     * Обект с информация за ембедъра
     */
    public $EmbedderRec;


    /**
     * Вътрешно, изчислено състояние на драйвъра
     */
    public $state;
    
    
    /**
     * Записа на формата, с която е създаден/модифициран драйвера
     */
    public $formRec;

    
    /**
     *
     */
    public function canSelectInnerObject($userId = NULL)
	{
		return TRUE;
	}


    /**
     *
     */
    public function setInnerForm($form)
    {
        $this->formRec = $form;
    }
    
    
    /**
     *
     */
    public function setInnerState($state)
    {
        $this->state = $state;
    }

    
    /**
     *
     */
    public function addEmbeddedFields($form)
    {
    }
    
    
    /**
     *
     */
    public function prepareEmbeddedForm($form)
    {
    }
    
    
    /**
     *
     */
    public function checkEmbeddedForm($form)
    {
    }
    
    
    /**
     *
     */
    public function prepareEmbeddedData()
    {
    }


    /**
     *
     */
    public function renderEmbeddedData($data)
    {
    }

}