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
    var $loadList = 'plg_RowTools, sens2_Wrapper, plg_AlignDecimals';
    
    
    /**
     * Заглавие
     */
    var $title = 'Индикатори на входовете и изходите';
    
    
    /**
     * Права за писане
     */
    var $canWrite = 'ceo, sens, admin';
    
    
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
        $this->FLD('controllerId', 'key(mvc=sens2_Controllers, select=name)', 'caption=Контролер, mandatory');
        $this->FLD('port', 'varchar(32)', 'caption=Порт, mandatory');
        $this->FLD('uom', 'varchar(16)', 'caption=Мярка,column=none');
        $this->FLD('value', 'double(minDecimals=0, maxDecimals=4)', 'caption=Стойност');
        $this->FLD('lastValue', 'datetime', 'caption=Време,oldFieldName=time');
        $this->FLD('lastUpdate', 'datetime', 'caption=Последно обновяване,column=none');
        $this->FLD('error', 'varchar(64)', 'caption=Грешка');
        $this->FNC('title', 'varchar(64)', 'caption=Заглавие,column=none');

        $this->setDbUnique('controllerId,port,uom');
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
        $cRec = sens2_Controllers::fetch($rec->controllerId);

        $title = sens2_Controllers::getVerbal($cRec, 'name') . '::';
        
        $nameVar = $rec->port . '_name';

        if($cRec->config->{$nameVar}) {
            $title .= $cRec->config->{$nameVar};
        } else {
            $title .= $rec->port;
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
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        
        $data->query->orderBy('#controllerId,#port', 'DESC');
    }



    /**
     * Изпълнява се след подготовката на редовете на листовия изглед
     */
    static function on_AfterPrepareListRows($mvc, $data, $data)
    { 
    	if(is_array($data->rows)) {
            foreach($data->rows as $id => &$row) {
                $row->value .= "<span class='measure'>" . self::getVerbal($data->recs[$id], 'uom') . "</span>";
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
        if($rec->lastValue) {
            $color = dt::getColorByTime($rec->lastValue);
            $row->lastValue = ht::createElement('span', array('style' => "color:#{$color}"), $row->lastValue);
        }

        if($rec->error && $rec->lastUpdate) {
            $color = dt::getColorByTime($rec->lastUpdate);
            $row->error = ht::createElement('span', array('style' => "color:#{$color}"), $row->error);
         }
    }
    
}
