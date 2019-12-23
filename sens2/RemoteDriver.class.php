<?php


/**
 * Драйвер за всички индикатори в отдалечена bgERP система
 *
 *
 * @category  vendors
 * @package   modbus
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sens2_RemoteDriver extends sens2_ProtoDriver
{
    /**
     * Масив за съхранение на състоянията на отдалечените системи
     */
    static $states = array();
    

    /**
     * Заглавие на драйвера
     */
    public $title = 'Отдалечен bgERP';
    

    /**
     * IP на устройството
     */
    public $authorizationId;
    

    /**
     * Състоянието на отдалечената система
     */
    public $state;
    

    /**
     * Без автоматични полета във формата
     */
    public $notExpandForm = true;
    
    
    /**
     * Описание на входовете
     *
     */
    public $inputs = array();
    
    
    /**
     * Подготвя форма с настройки на контролера, като добавя полета с $form->FLD(....)
     *
     * @see  sens2_ControllerIntf
     *
     * @param core_Form
     */
    public function prepareConfigForm($form)
    {
        $form->FNC('authorizationId', 'key(mvc=remote_Authorizations,select=url)', 'caption=IP,hint=Изберете отдалечената система, input, mandatory');

        $aQuery = remote_Authorizations::getQuery();
        
        $bgerpClassId = core_Classes::getId('remote_BgerpDriver');

        $cu = core_Users::getCurrent();

        $aQuery->where("#driverClass = {$bgerpClassId} && #userId = {$cu}");
        
        $opt = array();

        while($aRec = $aQuery->fetch()) {
            $opt[$aRec->id] = $aRec->url;
        }

        $form->setOptions('authorizationId', $opt);
    }
    
    
    /**
     * Връща снимка на контролера
     */
    public static function getPicture($config)
    {
        $path = 'bgerp/icon.png';
        
        return $path;
    }
    
    
    /**
     * Връща масив със стойностите на изразходваната активна мощност
     */
    public function readInputs($inputs, $config, &$persistentState)
    {
        $portsArr = $this->discovery();
        
          
        
        $res = array();
        
        foreach ($portsArr as $port) {
             
                $res[$port->name] = $port;
        }
        
        if (empty($res)) {
            
            return "Нищо не беше обновено от {$config->ip}:{$config->port}";
        }
        
        return $res;
    }
    
    
 
    
    
    /**
     * Връша информация за наличните портове
     *
     * @return array масив с обекти имащи следните полета:
     *               o name     - име на променливата
     *               о slot     - име на слота
     *               о uom      - стринг, който се изписва след променливата (%, V, W, ...)
     *               o prefix   - стринг, който се изписва преди променливата
     *               о options  - масив с възможни стоийнисти
     *               о min      - минимална стойност
     *               о max      - максимална стойност
     *               о readable - дали порта може да се чете
     *               о writable - дали порта може да се записва
     */
    public function discovery()
    {   
        $key = 'RD' . $this->driverRec->id;

        if((!$this->state) && !($this->state = core_Cache::get('RemoteDriver', $key))) {

            $this->loadState();

            core_Cache::set('RemoteDriver', $key, $this->state, 10);
        }
 
        return $this->state;
    }

    
    /**
     * Зарежда състоянието в драйвера
     */
    private function loadState()
    {   
        $authorizationId = $this->driverRec->config->authorizationId;

        if(!($this->state = self::$states[$authorizationId])) {
            $aRec = remote_Authorizations::fetch($authorizationId);
            $this->state = remote_BgerpDriver::sendQuestion($aRec, __CLASS__, 'getState', array('priority' => true));

            if(is_array($this->state)) {
                foreach($this->state as $i => $p) {
                    $this->state[$i] = (object) $p;
                }
            } else {
                $this->state = array();
            }

            self::$states[$authorizationId] = $this->state;
        }
    }


    /**
     * Връща информация за всички индикатори към отдалечена система 
     */
    public function remote_GetState($authId, $arg = null)
    {
        expect($authId > 0);
        
        $aRec = remote_Authorizations::fetch($authId);

        core_Users::sudo($aRec->userId);

        $iMvc = cls::get('sens2_Indicators');

        $iMvc->requireRightFor('list');
        
        $res = array();
        $iQuery = $iMvc->getQuery();
        while($iRec = $iQuery->fetch("#state = 'active'")) {
            $rec = new stdClass();
            $rec->port  = sens2_Controllers::fetchField($iRec->controllerId, 'name') . '.' . $iRec->port;
            $rec->name  = $iRec->name ? $iRec->name : $rec->port;
            $rec->uom   = $iRec->uom;
            $rec->value = $iRec->value;
            $rec->error = $iRec->error;
            $rec->lastUpdate = $iRec->lastUpdate;
            $rec->lastValue  = $iRec->lastValue;
            $rec->readable = true;
            $rec->logPeriod = 60;
            $rec->readPeriod = 60;
            
            $res[] = $rec;
        }
        
        core_Users::exitSudo($aRec->userId);

        return $res;

    }

}
