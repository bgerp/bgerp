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
	 * Как се казва полето за избор на вътрешния клас
	 */
	public $driverClassField = 'driverClass';
	
	
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
		
		// Добавяме задължителните полета само ако не е дефинирано, че вече съществуват
		if(!isset($mvc->fields[$mvc->driverClassField])){  
            $caption = $mvc->driverClassCaption ? $mvc->driverClassCaption : 'Вид';
			$mvc->FLD($mvc->driverClassField, "class(interface={$mvc->driverInterface}, allowEmpty, select=title)", "caption={$caption},mandatory,silent,refreshForm,after=id");
		}
		
		if(!isset($mvc->fields['driverRec'])){
			$mvc->FLD('driverRec', "blob(1000000, serialize, compress)", "caption=Филтър,input=none,column=none,single=none");
		}
		
		// Кои полета да се помнят след изтриване
		$fieldsBeforeDelete = "id, {$mvc->driverClassField}, driverRec";
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

		// Извличаме позволените за избор опции
		$interfaces = static::getAvailableDriverOptions();
		
		// Ако има избран вече склад, но го няма в опциите добавя се
		if($rec->{$this->driverClassField} && !array_key_exists($rec->{$this->driverClassField}, $interfaces)) {
			$name = core_Classes::fetchField($rec->{$this->driverClassField}, 'title');
			$interfaces[$rec->{$this->driverClassField}] = core_Classes::translateClassName($name);
		}
		
		// Ако няма достъпни драйвери редирект със съобщение
		if(!count($interfaces)) {
			followRetUrl(NULL, '|Липсват възможни видове|* ' . $mvc->title, 'error');
		} else {
			$form->setOptions($this->driverClassField, $interfaces);
			
			// Ако е наличен само един драйвер избираме него
			if(count($interfaces) == 1){
				$form->setDefault($this->driverClassField, key($interfaces));
				$form->setReadOnly($this->driverClassField);
			}
		}
		
        // Ако има източник инстанцираме го
		if($rec->{$this->driverClassField}) {
            
            // Ако има съществуващ запис - полето не може да се сменя
            if($id = $rec->id) {
			    $form->setReadOnly($this->driverClassField);
            }
			
            if($driver = $this->getDriver($rec)){
            	$driver = cls::get($rec->{$this->driverClassField}, array('Embedder' => $this));
            	$driver->addFields($form);
            }
            
            $form->input(NULL, 'silent');
		}

        return $data;
	}


	/**
	 * Връща позволените за избор драйвери според класа и потребителя
	 * 
	 * @param mixed $userId - ид на потребител
	 * @return array $interfaces - възможните за избор опции на класове
	 */
	public static function getAvailableDriverOptions($userId = NULL)
	{
		// Ако не е подаден потребител това е текущия
		if(!$userId){
			$userId = core_Users::getCurrent();
		}
		
		// Зареждаме опциите за интерфейса
		$me = cls::get(get_called_class());
		$interfaces = core_Classes::getOptionsByInterface($me->driverInterface, 'title');
		if(count($interfaces)){
			foreach ($interfaces as $id => $int){
				if(!cls::load($id, TRUE)) continue;
		
				$driver = cls::get($id, array('Embedder' => $me));
		
				// Ако потребителя не може да го избира, махаме го от масива
				if(cls::existsMethod($driver, 'canSelectDriver') && !$driver->canSelectDriver($userId)){
					unset($interfaces[$id]);
				}
			}
		}
		
		return $interfaces;
	}
	
	
    /**
	 * Изпълнява се след извличане на запис чрез ->fetch()
	 */
	public static function on_AfterRead($mvc, $rec)
	{
        try {
            if(cls::load($rec->{$mvc->driverClassField}, TRUE)){

                $driverRec = $rec->driverRec;
                
                if(is_array($driverRec)) {
                    foreach($driverRec as $field => $value) {
                        $rec->{$field} = $value;
                    }
                }

                $driver = cls::get($rec->{$mvc->driverClassField}, array('Embedder' => $mvc));
                
                return $driver->invoke('AfterRead', array(&$rec));
            }
        } catch(core_exception_Expect $e) {}
	}
    

    /**
	 * Преди запис в модела, компактираме полетата
	 */
	public function save_(&$rec, $fields = NULL, $mode = NULL)
	{   
        $saveDriverRec = FALSE;
 
		if($driver = $this->getDriver($rec)){
            $driverRec = array();
			$addFields = self::getDriverFields($driver);
			 
			foreach($addFields as $name => $caption) {
				$driverRec[$name] = $rec->{$name};
                $saveDriverRec = TRUE;
			}
			
			$rec->driverRec = $driverRec;
		}

        if($fields && (is_array($fields) || $fields != '*')) {
            $fields = arr::make($fields, TRUE);
            foreach($fields as $f => $dummy) {
                if($addFields[$f] && !$this->getField($f, FALSE)) {
                    unset($fields[$f]);
                }
            }
        }

        if($saveDriverRec && is_array($fields)) {
            $fields['driverRec'] = 'driverRec';
        }

        return parent::save_($rec, $fields, $mode);
	}

	
	/**
	 * Изпълнява се след подготовка на единичните полета
	 */
	public function prepareSingleFields_($data)
	{
        parent::prepareSingleFields_($data);

        // Ако има драйвър, добавяме полетата от него към полетата за показване
        if($driver = $this->getDriver($data->rec)){
        	$driverFields = self::getDriverFields($driver, TRUE);
        	if(is_array($driverFields)){
        		$data->singleFields += $driverFields;
        	}
        }
	}


    /**
     * Добавяме полетата от драйвера, ако са указани
     */
    static function recToVerbal_($rec, &$fields = '*')
    {
        if($fields === '*'){
        	
        	// Ако извличаме всички полета се подсигуряваме че към тях са и полетата на драйвера
        	$data = (object)array('rec' => $rec);
        	static::prepareSingleFields($data);
        	$fields = $data->singleFields;
        }
    	
    	$row = parent::recToVerbal_($rec, $fields);
		$mvc = cls::get(get_called_class());
		
		if(is_array($fields)){
			if($driver = static::getDriver($rec)){
				
				$fieldset = cls::get('core_Fieldset');
				$driver->addFields($fieldset);
				
				foreach($fieldset->fields as $name => $field) {
					
					if(!isset($row->{$name}) && $fields[$name] && isset($rec->{$name})) {
						$row->{$name} = $field->type->toVerbal($rec->{$name});
					}
				}
			}
		}
		
        return $row;
    }


	/**
	 * Връща полетата добавени от драйвера
	 * 
	 * @param core_BaseClass $driver - драйвер
	 * @param boolean $onlySingleFields - дали да са само полетата за сингъл
	 * @param boolean $returnAsFieldSet - дали да се върнат като фийлд сетове
	 * @return array $res - добавените полета от драйвера
	 */
    public static function getDriverFields($driver, $onlySingleFields = FALSE, $returnAsFieldSet = FALSE)
    {
        $fieldset = cls::get('core_Fieldset');
        $driver->addFields($fieldset);
        
        $res = array(); 
        if(is_array($fieldset->fields)) {
            foreach($fieldset->fields as $name => $f) {
            	if($onlySingleFields === TRUE && $f->single == 'none') continue;
            	
            	$res[$name] = ($returnAsFieldSet === FALSE) ? $f->caption : $f;
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
                case 'aftersave':
                case 'afterrectoverbal': 
                    $driverClass = $args[1]->{$this->driverClassField};
                    break;
                
                case 'aftercreate':
                case 'afterupdate':
                    $driverClass = $args[0]->{$this->driverClassField};
                    break;

                case 'aftergetrequiredroles':
                    if(is_object($args[2])) {
                        $driverClass = $args[2]->{$this->driverClassField};
                    }
                    break;
                    
                case 'afterprepareeditform':
                    $driverClass = $args[0]->form->rec->{$this->driverClassField};
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
                    $driverClass = $args[1]->rec->{$this->driverClassField};
                    break;
                case 'afterinputeditform':
                    $driverClass = $args[0]->rec->{$this->driverClassField};
                    break;
                case 'afterread':
                    $driverClass = $args[0]->{$this->driverClassField};
                    break;
                case 'aftergetsearchkeywords';
                	$driverClass = $args[1]->{$this->driverClassField};
                	break;
                case 'beforesaveclonerec':
                	$driverClass = $args[1]->{$this->driverClassField};
                	break;
                case 'beforesave':
                	$driverClass = $args[1]->{$this->driverClassField};
                case 'aftercreate':
                	$driverClass = $args[1]->{$this->driverClassField};
                	break;
                case 'aftergetdetailstoclone':
                	$driverClass = $args[1]->{$this->driverClassField};
                	break;
                case 'aftergetfieldforletterhead':
                	$driverClass = $args[1]->{$this->driverClassField};
                	break;
            }

            // Ако има избран драйвер
            if($driverClass) {
            	$dRec = (object)array($this->driverClassField => $driverClass);
            	if($driver = $this->getDriver($dRec)){
            		
            		// Добавяме ембедъра към аргументите на ивента
            		array_unshift($args, $this);
            		 
            		// Генерираме същото събитие в драйвера за да може да го прихване при нужда
            		$status2 = $driver->invoke($event, $args);
            		
            		if($status2 === FALSE) {
            			$status = FALSE;
            		} elseif($status == -1 && $status2 === TRUE) {
            			$status = TRUE;
            		}
            	}
            }
        }
        
        return $status;
    }
    
    
    /**
     * Връща инстанция на драйвера на класа
     *
     * @param int $id
     * @return mixed - инстанция на драйвера или FALSE ако не може се инстанцира / има проблем с инсрабцирането
     */
    public static function getDriver($id)
    {
    	$rec = static::fetchRec($id);
    	$self = cls::get(get_called_class());
    	
    	// Ако има драйвер и той може да се зареди, инстанцираме го
    	if(isset($rec->{$self->driverClassField}) && cls::load($rec->{$self->driverClassField}, TRUE)){
    		
    		return cls::get($rec->{$self->driverClassField});
    	}
    	
    	return FALSE;
    }
}