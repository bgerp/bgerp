<?php

/**
 * Мениджър за сензори
 */
class sens_Sensors extends core_Manager
{
    /**
     *  Необходими мениджъри
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_State,
                     Params=sens_Params, sens_Wrapper';
    
    
    /**
     *  Титла
     */
    var $title = 'Сензори';
    
    
    /**
     * Права за писане
     */
    var $canWrite = 'sens, admin';
    
    
    /**
     *  Права за запис
     */
    var $canRead = 'sens, admin';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('title', 'varchar(255)', 'caption=Заглавие, mandatory');
//        $this->FLD('params', 'text', 'caption=Инициализация');
//        $this->FLD('checkPeriod', 'int', 'caption=период (m)');
//        $this->FLD('monitored', 'keylist(mvc=sens_Params,select=param)', 'caption=Параметри');
        $this->FLD('location', 'key(mvc=common_Locations,select=title)', 'caption=Локация');
        $this->FLD('driver', 'class(interface=sens_DriverIntf)', 'caption=Драйвер,mandatory');
        $this->FLD('state', 'enum(active=Активен, closed=Спрян)', 'caption=Статус');
        $this->FNC('settings', 'varchar(255)', 'caption=Настройки');
        $this->FNC('results', 'varchar(255)', 'caption=Показания');
    }
    
    
   /**
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {

    }
   
   
   /**
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_AfterPrepareEditForm($mvc, $rec, $data)
    {
		if (isset($rec->form->rec->id)) {
		}
    }
    
    /**
     * 
     * Enter description here ...
     */
	function act_Settings()
	{
        requireRole('admin');
        
        $form = cls::get('core_Form'); 
        
        expect($id = Request::get('id', 'int'));
        
        expect($rec = $this->fetch($id));
        
        $retUrl = getRetUrl()?getRetUrl():array($this);
        
        $driver = cls::get($rec->driver, array('id'=>$id));
        
        permanent_Settings::init($driver);
        
        $driver->prepareSettingsForm($form);
        
        $form->toolbar->addSbBtn('Запис', 'save', array('class' => 'btn-save'));
        $form->toolbar->addBtn('Отказ', $retUrl, array('class' => 'btn-cancel'));
        
        $form->input();
        
        if($form->isSubmitted()) {
        	$settings['fromForm'] = $form->rec;
        	$settings['values'] = $driver->getData();
			permanent_Data::write($driver->getSettingsKey(), $settings);
                
            return new Redirect($retUrl);
        }
        
        $form->title = tr("Настройка на сензор") . " \"" . $this->getVerbal($rec, 'title') .
        " - " . $this->getVerbal($rec, 'location') . "\"";
        $form->setDefaults($driver->settings['fromForm']);
        
        $tpl = $form->renderHtml();
        
        return $this->renderWrapping($tpl);
        
		
	}
	
    
    
    /**
     * Показваме актуални данни за всеки от параметрите
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {   

    	/**
         * @todo: Да се махне долния пасаж, когато се направи де-иснталиране
         */
        if(!cls::getClassName($rec->driver, FALSE)) {
            return;
        }

        $driver = cls::get($rec->driver, array('id'=>$rec->id));
       
        $sensorData = array();

        // Изваждаме данните за този сензор
        permanent_Settings::init($driver);

        $settingsArr = (array)$driver->settings['fromForm'];
        
        foreach ($settingsArr as $name =>$value) {
        	$row->settings .= $name . " = " . $value. "<br>" ;
        }

        $row->settings .= "<br>" . permanent_Settings::getLink($driver) ;
        
        //bp($driver->settings['values']);
        foreach ($driver->params as $param => $properties) {
        	$row->results .= "{$param} = {$driver->settings['values'][$param]} {$properties['details']}<br>";	
        }
         
        return;
    }
}