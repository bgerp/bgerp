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
    var $loadList = 'plg_RowTools, sens2_Wrapper';
    
    
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
        $this->FLD('uom', 'varchar(16)', 'caption=Мярка');
        $this->FLD('value', 'double', 'caption=Стойност');
        $this->FLD('time', 'datetime', 'caption=Време');
        $this->FLD('error', 'varchar(64)', 'caption=Грешка');

        $this->setDbUnique('controllerId,port,uom');
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
            if( ($rec->value == $value || $rec->error == $value) && $rec->time == $time) {
                return;
            }
        }

        $rec->controllerId = $controllerId;
        $rec->port         = $port;
        $rec->uom          = $uom;
        $rec->value        = $value;
        $rec->time         = $time;
       
        // Ако имаме грешка, поставяме я в правилното място
        $value = trim($value);
        if(!is_numeric($value)) {
            unset($rec->value);
            $rec->error = $value;
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

        $title = sens2_Controllers::getVerbal($cRec, 'name') . '->';
        
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
     * Добавяме означението за съответната мерна величина
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
         
    }
    
}