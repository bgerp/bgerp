<?php


/**
 * Абстрактен родителски клас за вход/изход/регистър на контролер
 *
 *
 * @category  bgerp
 * @package   sens2
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class sens2_ioport_Abstract extends core_BaseClass
{
    /**
     * Типът слотове за сензорите от този вид
     */
    const SLOT_TYPES = '';
    
    
    /**
     * Подръжани интерфейси
     */
    public $interfaces = 'sens2_ioport_Intf';
    
    
    /**
     * Инстанция на класа имплементиращ интерфейса
     */
    public $class;
    
    
    /**
     * Колко максимално порта могат да се вържат на един слот
     */
    public $maxUnitPerSlot = 1;
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $ports = $this->discovery();
        $pOpt = '1 min|2 min|3 min|4 min|5 min|10 min|30 min|60 min';
        
        if (count($ports) > 1) {
            $opt = '';
            foreach ($ports as $p) {
                $opt .= '|' . $p->name . ' [' . $p->uom . ']';
            }
            $fieldset->FLD('periods', "table(columns=param|readPeriod|logPeriod,captions=Параметър|Четене|Логване,param_opt={$opt},
                      readPeriod_opt=|{$pOpt},logPeriod_opt=|{$pOpt})", 'caption=Периоди на отчитане->Параметри,mandatory=param');
        } else {
            if (is_null($ports->uom)) {
                $p = array_pop($ports);
                $fieldset->FLD('uom', 'varchar(16)', 'caption=Единица');
                if ($p->uomDef) {
                    $fieldset->setField('uom', "placeholder={$p->uomDef}");
                }
            }
            $fieldset->FLD('readPeriod', "time(uom=minutes,suggestions={$pOpt})", 'caption=Периоди на отчитане->Четене');
            $fieldset->FLD('logPeriod', "time(uom=minutes,suggestions={$pOpt})", 'caption=Периоди на отчитане->Логване');
        }
    }
    
    
    /**
     * Може ли вградения обект да се избере
     */
    public function canSelectDriver($rec, $userId = null)
    {
        if ($rec->controllerId && static::SLOT_TYPES) {
            $Plc = sens2_Controllers::getDriver($rec->controllerId);
            $slotTypesArr = arr::make(static::SLOT_TYPES, true);
            $slotsCnt = $Plc->getSlotOpt($slotTypesArr, true);
            
            if (count($slotsCnt)) {
                
                return true;
            }
        }
    }
    
    
    /**
     * Конвертира извлечената стойност в масив от Име => Стойност
     */
    public function convert($value, $name, $pRec)
    {
        return $value;
    }
    
    
    /**
     * Връша информация за портовете, които това устройство показва
     *
     * @return array масив с обекти имащи следните полета:
     *               o subname  - подчинено на променливата, може да е ''
     *               о suffix   - стринг, който се изписва след променливата (%, V, W, ...)
     *               o prefix   - стринг, който се изписва преди променливата
     *               о options  - масив с възможни стоийнисти
     *               о min      - минимална стойност
     *               о max      - максимална стойност
     *               о readable - дали порта може да се чете
     *               о writable - дали порта може да се записва
     */
    public function discovery()
    {
        $res = array();
        if (is_array($this->description)) {
            foreach ($this->description as $key => $portInfo) {
                if (is_array($this->driverRec->{$key})) {
                    foreach ($this->driverRec->{$key} as $prop => $val) {
                        $portInfo[$prop] = $val;
                    }
                }
                $res[] = (object) $portInfo;
            }
        }
        
        return $res;
    }
    
    
    /**
     * Връща допълнителен идентификатор за порта, който е базиран на данните в драйвера
     */
    public function getPortIdent($rec)
    {
        return '';
    }
    
    
    /**
     * Добавя стойностите от записа на детайла посочения порт
     */
    public function addTimeValues($p, $pRec)
    {
        $p->portIdent = $pRec->portIdent;
        
        if ($pRec->periods) {
            $periods = json_decode($pRec->periods);
            
            if (is_array($periods->param)) {
                foreach ($periods->param as $i => $param) {
                    list($name, ) = explode(' ', $param);
                    if ($p->lName == $name) {
                        if ($periods->readPeriod[$i]) {
                            $p->readPeriod = floor($periods->readPeriod[$i]) * 60;
                        }
                        if ($periods->logPeriod[$i]) {
                            $p->logPeriod = floor($periods->logPeriod[$i]) * 60;
                        }
                    }
                }
            }
        } else {
            if ($pRec->readPeriod) {
                $p->readPeriod = $pRec->readPeriod;
            }
            if ($pRec->logPeriod) {
                $p->logPeriod = $pRec->logPeriod;
            }
            if ($pRec->uom) {
                $p->uom = $pRec->uom;
            }
        }
    }
}
