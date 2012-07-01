<?php



/**
 * Мениджър за сензори
 *
 *
 * @category  bgerp
 * @package   sens
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Сензори
 */
class sens_Sensors extends core_Master
{
    
    
    /**
     * Необходими мениджъри
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools, plg_State,
                     Params=sens_Params, sens_Wrapper';
    
    
    /**
     * Заглавие
     */
    var $title = 'Сензори';
    
    
    /**
     * Права за писане
     */
    var $canWrite = 'sens, admin';
    
    
    /**
     * Права за запис
     */
    var $canRead = 'sens, admin';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('title', 'varchar(255)', 'caption=Заглавие, mandatory');
        $this->FLD('driver', 'class(interface=sens_DriverIntf)', 'caption=Драйвер,mandatory');
        $this->FLD('state', 'enum(active=Активен, closed=Спрян)', 'caption=Статус');
        $this->FNC('settings', 'varchar(255)', 'caption=Настройки,column=none');
        $this->FNC('indications', 'varchar(255)', 'caption=Показания');
    }
    
    
    /**
     * Преди изтриване на запис
     */
    static function on_BeforeDelete($mvc, &$res, &$query, $cond)
    {
        // Изтриваме перманентните данни за драйвера
        $rec = $query->fetch($cond);
        $driver = cls::get($rec->driver, array('id'=>$rec->id));
        permanent_Settings::purge($driver);
    }
    
    
    /**
     * Подменя URL-то да сочи направо към настройките на обекта
     * @param object $mvc
     * @param object $data
     */
    static function on_AfterPrepareRetUrl($mvc, $data)
    {
        if ($data->form->isSubmitted()) {
            $url = array('permanent_Settings', 'Ajust',
                'objCls' => $data->form->rec->driver,
                'objId' => $data->form->rec->id,
                'wrapper' => 'sens_Sensors',
                'ret_url' => toUrl($data->retUrl)
            );
            $data->retUrl = $url;
            Request::setProtected('objCls, objId, wrapper');
        }
    }
    
    
    /**
     * Добавя бутон за настройки в единичен изглед на драйвер-а
     * @param stdClass $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        $driver = cls::get($data->rec->driver, (array) $data->rec);
        
        $url = array('permanent_Settings', 'Ajust',
            'objCls' => $data->rec->driver,
            'objId' => $data->rec->id,
            'wrapper' => 'sens_Sensors',
            'ret_url' => TRUE
        );
        
        Request::setProtected('objCls, objId, wrapper');
        
        $data->toolbar->addBtn('Настройки', $url, 'class=btn-settings');
    }
    
    
    /**
     * Показваме актуални данни за всеки от параметрите
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        
        if(!cls::load($rec->driver, TRUE)) {
            
            $className = cls::getClassName($rec->driver) ? : $rec->driver;
            $row->driver = "<font color='red'>{$className}</font>";
            
            return;
        }
        
        // Инициализираме драйвера
        $driver = cls::get($rec->driver, array('id'=>$rec->id));
        
        $settingsArr = (array)$driver->settings;
        
        foreach ($settingsArr as $name =>$value) {
            $row->settings .= $name . " = " . $value . "<br>";
        }
        
        $row->indications = $driver->renderHtml();
    }
    
    
    /**
     * Стартира функцията за крона през ВЕБ
     */
    function act_Cron()
    {
        return $this->cron_Process();
    }
    
    
    /**
     * Стартира се на всяка минута от cron-a
     * Извиква по http sens_Sensors->act_Process
     * за всеки 1 драйвер като предава id и key - ключ,
     * базиран на id на драйвера и сол
     */
    function cron_Process()
    {
        $querySensors = sens_Sensors::getQuery();
        $querySensors->where("#state='active'");
        $querySensors->show("id");
        
        while ($sensorRec = $querySensors->fetch($where)) {
            $url = toUrl(array($this->className, 'Process', str::addHash($sensorRec->id)), 'absolute');
            
            //return file_get_contents($url,FALSE,NULL,0,2);
            @file_get_contents($url, FALSE, NULL, 0, 2);
        }
    }
    
    
    /**
     * Приема id и key - базиран на драйвера и сол
     * Затваря връзката с извикващия преждевременно.
     * Инициализира обект $driver
     * и извиква $driver->process().
     */
    function act_Process()
    {
        // Затваряме връзката с извикване
        
        // Следващият ред генерира notice,
        // но без него file_get_contents забива, ако трябва да връща повече от 0 байта
        @ob_end_clean();
        
        header("Connection: close\r\n");
        header("Content-Encoding: none\r\n");
        ob_start();
        echo "OK";
        $size = ob_get_length();
        header("Content-Length: $size");
        ob_end_flush();
        flush();
        ob_end_clean();
        
        $id = str::checkHash(Request::get('id', 'varchar'));
        
        //        $id = 6;
        if (FALSE === $id) {
            
            /**
             * @todo Логва се съобщение за неоторизирано извикване
             */
            exit(1);
        }
        $rec = $this->fetch("#id = $id");
        $driver = cls::get($rec->driver, (array) $rec);
        $driver->process();
    }
}
