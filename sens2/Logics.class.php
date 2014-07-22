<?php


/**
 * Мениджър на логически блокове за управление на контролери
 *
 *
 * @category  bgerp
 * @package   sens2
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sens2_Logics extends core_Master
{
    
    
    /**
     * Необходими плъгини
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools, plg_State2, plg_Rejected, sens2_Wrapper';
                      
    
    /**
     * Заглавие
     */
    var $title = 'Логически блокове';
    
    
    /**
     * Права за писане
     */
    var $canWrite = 'ceo,sens,admin';
    
    
    /**
     * Права за запис
     */
    var $canRead = 'ceo, sens, admin';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'debug';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,admin,sens';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,admin,sens';
    

    /**
     * Детайли на блока
     */
    var $details = 'sens2_LogicDetails';


    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(255)', 'caption=Наименование, mandatory,notConfig');
 
        $this->setDbUnique('name');
    }
    

    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareEditform($mvc, &$data)
    {
    }


    /**
     * Подготвя конфигурационната форма на посочения драйвер
     */
    static function prepareConfigForm($form, $driver)
    {
        $drv = cls::get($driver);
        $drv->prepareConfigForm($form);

        $ports = $drv->getInputPorts();

        if(!$ports) {
            $ports = array();
        }

        expect(is_array($ports));

        foreach($ports as $port => $params) {
            
            $prefix = $params->caption . " ({$port})";

            $form->FLD($port . '_name', 'varchar(32)', "caption={$prefix}->Наименование");
            $form->FLD($port . '_scale', 'varchar(255,valid=sens2_Controllers::isValidExpr)', "caption={$prefix}->Скалиране,hint=Въведете функция на X с която да се скалира стойността на входа");
            $form->FLD($port . '_uom', 'varchar(16)', "caption={$prefix}->Единица");
            $form->FLD($port . '_update', 'time(suggestions=1 min|2 min|5 min|10 min|30 min,uom=minutes)', "caption={$prefix}->Четене през");
            $form->FLD($port . '_log', 'time(suggestions=1 min|2 min|5 min|10 min|30 min,uom=minutes)', "caption={$prefix}->Логване през");
            if(trim($params->uom)) {
                $form->setSuggestions($port . '_uom', arr::combine(array('' => ''), arr::make($params->uom, TRUE)));
            }
        }

        $ports = $drv->getOutputPorts();

        if(!$ports) {
            $ports = array();
        }
        
        foreach($ports as $port => $params) {

            $prefix = $params->caption . " ({$port})";

            $form->FLD($port . '_name', 'varchar(32)', "caption={$prefix}->Наименование");
            $form->FLD($port . '_uom', 'varchar(16)', "caption={$prefix}->Единица");
            if(trim($params->uom)) {
                $form->setSuggestions($port . '_uom', arr::combine(array('' => ''), arr::make($params->uom, TRUE)));
            }
        }
    }
    
    

    /**
     * Изпълнява се след въвеждането на данните от заявката във формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {
    }


}
