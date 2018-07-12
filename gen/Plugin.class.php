<?php


/**
 * Клас 'gen_Plugin' -
 *
 * Добавя родословно дърво към хората от визитника
 *
 *
 * @category  vendors
 * @package   gen
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class gen_Plugin extends core_Plugin
{
    /**
     * Извиква се след описанието на модела
     */
    public function on_AfterDescription(&$mvc)
    {
        if (!$mvc->fields['mother']) {
            $mvc->FLD('mother', 'key(mvc=crm_Persons, allowEmpty, select=name)', 'caption=Генеалогически данни->Майка');
        }
        
        if (!$mvc->fields['father']) {
            $mvc->FLD('father', 'key(mvc=crm_Persons, allowEmpty, select=name)', 'caption=Генеалогически данни->Баща');
        }
        
        if (!$mvc->fields['deathOn']) {
            $mvc->FLD('deathOn', 'date', 'caption=Генеалогически данни->Починал на');
        }
    }
    
    
    /**
     * Извиква се преди извличането на вербална стойност за поле от запис
     */
    public function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->nameList = new ET('[#1#]', $row->nameList);
        
        if ($rec->mother) {
            $gen = "<div style='margin-top:5px;'>Майка: " .
                    ht::createLink($mvc->getVerbal($rec, 'mother'), array('crm_Persons', 'single', $rec->mother)) .
                    '</div>';
        }
        
        if ($rec->father) {
            $gen .= "<div style='margin-top:5px;'>Баща: " .
                    ht::createLink($mvc->getVerbal($rec, 'father'), array('crm_Persons', 'single',  $rec->father)) .
                    '</div>';
        }
        
        if ($rec->deathOn) {
            $gen .= "<div style='margin-top:5px;'>" . (($rec->salutation == 'mr' || $rec->salutation === '') ? 'Починал на: ' : 'Починала на: ') .
                    $mvc->getVerbal($rec, 'deathOn') .
                    '</div>';
        }
        
        $row->contacts .= $gen;
    }
    
    
    /**
     * Рутинни действия, които трябва да се изпълнят в момента преди терминиране на скрипта
     */
    public static function on_Shutdown($mvc)
    {
        if (count($mvc->updatedRecs)) {
            // Обновяване на информацията за рожденните дни, за променените лица
            foreach ($mvc->updatedRecs as $id => $rec) {
                self::updateDeathToCalendar($id);
            }
        }
    }
    
    
    /**
     * Обновява информацията за датата на смърта
     * човек за текущата и следващите три години
     */
    public static function updateDeathToCalendar($id)
    {
        if (($rec = crm_Persons::fetch($id)) && ($rec->state != 'rejected')) {
            if (!$rec->deathOn) {
                
                return;
            }
            
            list($y, $m, $d) = explode('-', $rec->deathOn);
        }
        
        $events = array();
        
        // Годината на датата от преди 30 дни е начална
        $cYear = date('Y', time() - 30 * 24 * 60 * 60);
        
        // Начална дата
        $fromDate = "{$cYear}-01-01";
        
        // Крайна дата
        $toDate = ($cYear + 2) . '-12-31';
        
        // Масив с години, за които ще се вземат рожденните дни
        $years = array($cYear, $cYear + 1, $cYear + 2);
        
        // Префикс на клучовете за рожденните дни на това лице
        $prefix = "DD-{$id}";
        
        if ($d > 0 && $m > 0) {
            foreach ($years as $year) {
                
                // Родените в бъдещето, да си празнуват рождения ден там
                if (($y > 0) && ($y > $year)) {
                    continue;
                }
                
                $calRec = new stdClass();
                
                // Ключ на събитието
                $calRec->key = $prefix . '-' . $year;
                
                // TODO да се проверява за високосна година
                $calRec->time = date('Y-m-d 00:00:00', mktime(0, 0, 0, $m, $d, $year));
                
                $calRec->type = 'gen/img/16/death.png';
                
                $calRec->allDay = 'yes';
                
                $calRec->title = ($year - $y) . ' год. от смърта на ' . $rec->name;
                
                $calRec->users = '';
                
                $calRec->url = array('crm_Persons', 'Single', $id);
                
                $calRec->priority = 90;
                
                $events[] = $calRec;
            }
        }
        
        return cal_Calendar::updateEvents($events, $fromDate, $toDate, $prefix);
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    public function on_AfterPrepareEditForm($mvc, $data)
    {
        $mothers = crm_Persons::makeArray4Select('name', "#salutation != 'mr' AND #salutation != 'miss'");
        $fathers = crm_Persons::makeArray4Select('name', "#salutation != 'mrs' AND #salutation != 'miss'");
        
        if ($data->form->rec->id) {
            unset($mothers[$data->form->rec->id]);
            unset($fathers[$data->form->rec->id]);
        }
        $data->form->setOptions('mother', $mothers);
        $data->form->setOptions('father', $fathers);
        
        if (!count($mothers)) {
            $data->form->setField('mother', 'input=none');
        }
        
        if (!count($fathers)) {
            $data->form->setField('father', 'input=none');
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function insertAfter($sourceArr, $afterField, $key, $value)
    {
        foreach ($sourceArr as $k => $v) {
            $destArr[$k] = $v;
            
            if ($k == $afterField) {
                $destArr[$key] = $value;
            }
        }
        
        return $destArr;
    }
}
