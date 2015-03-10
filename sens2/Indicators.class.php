<?php

/**
 * Регистър - описание на всички входове и изходи на контролерите
 * Съдържа и текущите им стойности, време за измерване/установяване и евентуално грешки
 *
 * @category  bgerp
 * @package   sens2
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sens2_Indicators extends core_Manager
{
    
    
    /**
     * Необходими мениджъри
     */
    var $loadList = 'plg_RowTools, sens2_Wrapper, plg_AlignDecimals, plg_RefreshRows';
    
    
    /**
     * Заглавие
     */
    var $title = 'Индикатори на входовете и изходите';
    
    
    /**
     * На колко време ще се актуализира листа
     */
    var $refreshRowsTime = 25000;
 
    
    /**
     * Права за писане
     */
    var $canWrite = 'debug';
    
    
    /**
     * Права за запис
     */
    var $canRead = 'ceo, sens, admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo, admin, sens';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo, admin, sens';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('controllerId', 'key(mvc=sens2_Controllers, select=name, allowEmpty)', 'caption=Контролер, mandatory, silent,refreshForm');
        $this->FLD('port', 'varchar(32)', 'caption=Порт, mandatory');
        $this->FLD('uom', 'varchar(16)', 'caption=Мярка,column=none');
        $this->FLD('value', 'double(minDecimals=0, maxDecimals=4)', 'caption=Стойност,input=none');
        $this->FLD('lastValue', 'datetime', 'caption=Към момент,oldFieldName=time,input=none');
        $this->FLD('lastUpdate', 'datetime', 'caption=Последно време на Обновяване,column=none,input=none');
        $this->FLD('error', 'varchar(64)', 'caption=Съобщения за грешка,input=none');
        $this->FNC('title', 'varchar(64)', 'caption=Заглавие,column=none');

        $this->setDbUnique('controllerId,port,uom');
    }
    

    /**
     * Изпълнява се при подготовката на формата
     */
    static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $form = $data->form;
        $rec  = $form->rec;
        if($rec->id) {
            $form->setReadOnly('controllerId');

        }  
        if($rec->controllerId) {
            $form->setOptions('port', sens2_Controllers::getActivePorts($rec->controllerId));
        }
    }

    /**
     * Изчислява стойността на функционалното поле 'title'
     */
    function on_CalcTitle($mvc, $rec)
    {
        $rec->title = $mvc->getRecTitle($rec);
    }


    /**
     * Записва текущи данни за вход или изход
     * Ако липсва запис за входа/изхода - създава го
     */
    static function setValue($controllerId, $port, $uom, $value, $time)
    {
        $rec = self::fetch(array("#controllerId = {$controllerId} AND #port = '[#1#]' AND #uom = '[#2#]'", $port, $uom));

        if(!$rec) {
            $rec = new stdClass();
        } else {
            // Ако имаме повторение на последните данни - не правим запис
            if($rec->value == $value && $rec->lastValue == $time) {
                return;
            }
        }

        $rec->controllerId = $controllerId;
        $rec->port         = $port;
        $rec->uom          = $uom;
        $rec->value        = $value;
        $rec->lastValue    = $time;
        $rec->lastUpdate   = $time;

        // Ако имаме грешка, поставяме я в правилното място
        $value = trim($value);
        if($value === '') {
            $value = "Празна стойност";
        }
        if(!is_numeric($value)) {
            unset($rec->value, $rec->lastValue);
            $rec->error = $value;
        } else {
            $rec->error = '';
        }

        self::save($rec);

        return $rec->id;
    }


    /**
     * Връща заглавието на дадения запис (името на порта)
     */
    static function getRecTitle($rec, $escape = TRUE)
    {
        static $drivers = array();

        $cRec = sens2_Controllers::fetch($rec->controllerId);

        $title = sens2_Controllers::getVerbal($cRec, 'name') . '::';
        
        $nameVar = $rec->port . '_name';

        if(!isset($portsOndriver[$cRec->driver])) {
            $drv = cls::get($cRec->driver);
            $portsOndriver[$cRec->driver] = $drv->getInputPorts() + $drv->getOutputPorts();
        }

        if($cRec->config->{$nameVar}) {
            $title .= $rec->port . " (" . $cRec->config->{$nameVar} . ")";
        } else {
            $title .= $rec->port. " (" . $portsOndriver[$cRec->driver][$rec->port]->caption . ")";
        }

        if($escape) {
            $title = type_Varchar::escape($title);
        }

        return $title;
    }


    /**
     * Връща мярката за дадения индикатор
     */
    static function getUom($id)
    {
        $rec = self::fetch($id);

        return $rec->uom;
    }
    
    

    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->EXT('ctrState', 'sens2_Controllers', 'externalName=state,externalKey=controllerId');
        $data->query->where("#ctrState = 'active'");
        $data->query->orderBy('#controllerId,#port', 'DESC');

    }



    /**
     * Изпълнява се след подготовката на редовете на листовия изглед
     */
    static function on_AfterPrepareListRows($mvc, $data, $data)
    { 
    	if(is_array($data->rows)) {
            foreach($data->rows as $id => &$row) {
                if(strlen($data->recs[$id]->value)) {
                    $row->value .= "<span class='measure'>" . self::getVerbal($data->recs[$id], 'uom') . "</span>";
                }
            }
    	}
    }


    /**
     * Добавяме означението за съответната мерна величина
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {   
        static $configs = array();
        static $params  = array();

        if($rec->lastValue) {
            $color = dt::getColorByTime($rec->lastValue);
            $row->lastValue = ht::createElement('span', array('style' => "color:#{$color}"), $row->lastValue);
        }

        if($rec->error && $rec->lastUpdate) {
            $color = dt::getColorByTime($rec->lastUpdate);
            $row->error = ht::createElement('span', array('style' => "color:#{$color}"), $row->error);
        }
        
        // Определяне на вербалното име на порта
        
        if(!$configs[$rec->controllerId]) {
            $configs[$rec->controllerId] = sens2_Controllers::fetchField($rec->controllerId, 'config');
        }
        
        if(!$params[$rec->controllerId]) {
            $driver = cls::get(sens2_Controllers::fetchField($rec->controllerId, 'driver'));
            $params[$rec->controllerId] = arr::combine($driver->getInputPorts(), $driver->getOutputPorts());
        }

        
        $var = $rec->port . '_name'; 
        if($configs[$rec->controllerId]->{$var}) {
            $row->port = type_Varchar::escape($rec->port . " (" . $configs[$rec->controllerId]->{$var} . ")");
        } else {
            $row->port = $rec->port . " (" . type_Varchar::escape($params[$rec->controllerId][$rec->port]->caption . ")");
        }

        $row->controllerId = sens2_Controllers::getLinkToSingle($rec->controllerId, 'name');

    }
    
}
