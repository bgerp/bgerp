<?php



/**
 * Мениджър на ембеднати обекти, за които отговарят драйвери
 *
 *
 * @category  bgerp
 * @package   embed
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class embed_Manager extends core_Master
{
	
	
	/**
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $driverInterface;
	
		
	
	/**
	 * Кеш на инстанцираните вградени класове
	 */
	protected $Drivers = array();
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Master &$mvc)
	{
        // Проверки дали са попълнени задължителните полета
		expect($mvc->driverInterface, $mvc);
		expect(is_subclass_of($mvc->driverInterface, 'embed_DriverIntf'), $mvc->driverInterface);
		
		// Добавяме задължителните полета само ако не е дефинирано че вече съществуват
		if(!isset($mvc->fields['driverClass'])){
			$mvc->FLD('driverClass', "class(interface={$mvc->driverInterface}, allowEmpty, select=title)", "caption=Вид,mandatory,silent,refreshForm,after=id");
		}
		
		if(!isset($mvc->fields['driverRec'])){
			$mvc->FLD('driverRec', "blob(1000000, serialize, compress)", "caption=Филтър,input=none,column=none,single=none");
		}
		
		// Кои полета да се помнят след изтриване
		$fieldsBeforeDelete = "id, driverClass, driverRec";
		$mvc->fetchFieldsBeforeDelete = $fieldsBeforeDelete;
	}
	
	
 
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	public function prepareEditForm_($data)
	{
        
        $data = parent::prepareEditForm_($data);

		$form = &$data->form;
		$rec = &$form->rec;

        // Ако има източник инстанцираме го
		if($rec->driverClass) {
            // Ако има съществуващ запис - полето не може да се сменя
            if($id = $rec->id) {
			    $form->setReadOnly('driverClass');
            }
			
            if(cls::load($rec->driverClass, TRUE)){
            	$driver = cls::get($rec->driverClass);
            	$driver->addFields($form);
            }
            
            $form->input(NULL, 'silent');

		} else {
            // Зареждаме опциите за интерфейса
            $interfaces = core_Classes::getOptionsByInterface($this->driverInterface, 'title');
            if(count($interfaces)){
                foreach ($interfaces as $id => $int){
                    if(!cls::load($id, TRUE)) continue;
                    
                    $driver = cls::get($id);
                    
                    // Ако потребителя не може да го избира, махаме го от масива
                    if(!$driver->canSelectDriver()){
                        unset($interfaces[$id]);
                    }
                }
            }

            // Ако няма достъпни драйвери полето е readOnly иначе оставяме за избор само достъпните такива
            if(!count($interfaces)) {
                redirect(array($this), NULL, 'Липсват възможни видове ' . $mvc->title);
            } else {
                $form->setOptions('driverClass', $interfaces);
            }
        }

        return $data;
	}


    /**
	 * Изпълнява се след извличане на запис чрез ->fetch()
	 */
	public static function on_AfterRead($mvc, $rec)
	{
        try {
            if(cls::load($rec->driverClass, TRUE)){

                $driverRec = $rec->driverRec;

                if(is_array($driverRec)) {
                    foreach($driverRec as $field => $value) {
                        $rec->{$field} = $value;
                    }
                }

                $driver = cls::get($rec->driverClass);
 
                return $driver->invoke('AfterRead', array(&$rec));
            }
        } catch(core_exception_Expect $e) {}
	}

    

    /**
	 * Преди запис в модела, компактираме полетата
	 */
	public function save_(&$rec, $fields = NULL, $mode = NULL)
	{
		if($driverClass = $rec->driverClass) {
		
            $driver = cls::get($driverClass);
            
            $addFields = self::getDriverFields($driver);
 
            foreach($addFields as $name => $caption) {
                $driverRec[$name] = $rec->{$name};
            }

            $rec->driverRec = $driverRec;
        }

        return parent::save_($rec, $fields, $mode);
	}




	/**
	 * Изпълнява се след подготовка на единичните полета
	 */
	public function prepareSingleFields_($data)
	{
        parent::prepareSingleFields_($data);

        if($driver = self::getDriver($data->rec->id)){
        	
        	// Инстанцираме драйвера
        	$driver = cls::get($data->rec->driverClass);
        	
        	$driverFields = self::getDriverFields($driver);
        	
        	$data->singleFields += $driverFields;
        }
	}


    /**
     * Добавяме полетата от драйвера, ако са указани
     */
    static function recToVerbal_($rec, &$fields = '*')
    {
        $row = parent::recToVerbal_($rec, $fields);

		if($rec->driverClass && is_array($fields) && cls::load($rec->driverClass, TRUE)){
			
            // Инстанцираме драйвера
            $driver = cls::get($rec->driverClass);
            
            $fieldset = cls::get('core_Fieldset');
            $driver->addFields($fieldset);
 
            foreach($fieldset->fields as $name => $field) {
                if(!isset($row->{$name}) && $fields[$name] && isset($rec->{$name})) {
                    $row->{$name} = $field->type->toVerbal($rec->{$name});
                }
            }
        }

        return $row;
    }



    static function getDriverFields($driver)
    {
        $fieldset = cls::get('core_Fieldset');
        $driver->addFields($fieldset);
        
        $res = array(); 
        if(is_array($fieldset->fields)) {
            foreach($fieldset->fields as $name => $f) {
                $res[$name] = $f->caption;
            }
        }

        return $res;
    }


    /**
     * Подменяне на входния метод за генериране на събития
     */
    function invoke($event, $args = array())
    {

        $status = parent::invoke($event, $args);

        if($status !== FALSE) {
            switch(strtolower($event)) {
                // public static function on_AfterSave(core_Mvc $this, &$id, $rec)
                case 'aftersave':
                //public static function on_AfterRecToVerbal($this, &$row, $rec)
                case 'afterrectoverbal': 
                    $driverClass = $args[1]->driverClass;
                    break;
                
                // public static function on_AfterGetRequiredRoles($this, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
                case 'aftergetrequiredroles':
                    if(is_object($args[2])) {
                        $driverClass = $args[2]->driverClass;
                    }
                    break;

                //public static function on_AfterPrepareEditForm($this, &$res, $data)
                case 'afterprepareeditform':
                    $driverClass = $args[0]->form->rec->driverClass;
                    break;

                case 'afterrendersinglelayout':
                case 'afterrendersingletitle':
                case 'afterrendersingletoolbar':
                case 'beforerendersinglelayout':
                case 'beforerendersingle':
                case 'afterrendersingle':
                case 'beforerendersingle':
                case 'afterrendersingle':
                case 'beforepreparesingle':
                case 'afterpreparesingle':
                case 'beforepreparesinglefields':
                case 'afterpreparesinglefields':
                case 'beforepreparesingletoolbar':
                case 'afterpreparesingletoolbar':

                    $driverClass = $args[1]->rec->driverClass;

                    break;

                //public static function on_AfterInputEditForm($this, &$form)
                case 'afterinputeditform':
                    $driverClass = $args[0]->rec->driverClass;
                    break;

                //static function on_AfterRead($this, $rec)
                case 'afterread': 
                    $driverClass = $args[0]->driverClass;
            }

            if($driverClass && cls::load($driverClass, TRUE)) {
                $driver = cls::get($driverClass);
                $status2 = $driver->invoke($event, $args);
                if($status2 === FALSE) {
                    $status = FALSE;
                } elseif($status == -1 && $status2 === TRUE) {
                    $status = TRUE;
                }
            }
        }
        
        return $status;
    }
	
    
    /**
     * Връща инстанция на драйвера на класа
     * 
     * @param int $id
     * @return mixed - инстанция на драйвера или FALSE ако не може се инстанцира
     */
    public function getDriver($id)
    {
    	if(empty($this->Drivers[$id])){
    		$rec = static::fetch($id);
    		
    		// Ако има драйвер и той може да се зареди, инстанцираме го
    		if(isset($rec->driverClass) && cls::load($rec->driverClass, TRUE)){
    		
    			$this->Drivers[$rec->id] = cls::get($rec->driverClass);
    		} else {
    			return FALSE;
    		}
    	}
    	
    	return $this->Drivers[$id];
    }
}