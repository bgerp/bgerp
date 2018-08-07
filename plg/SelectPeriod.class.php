<?php


/**
 * Клас 'plg_Select' - Добавя селектор на ред от таблица
 *
 *
 * @category  ef
 * @package   plg
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class plg_SelectPeriod extends core_Plugin
{
    /**
     * Префикс използван в recently пакета
     */
    const RECENTLY_KEY = 'UNIQ.PERIOD';
    
    
    public static function on_BeforePrepareListFilter($mvc, &$res, $data)
    {
        $fF = $mvc->filterDateFrom ? $mvc->filterDateFrom : 'from';
        $fT = $mvc->filterDateTo ? $mvc->filterDateFrom : 'to';
        
        $form = $data->listFilter;
        
        $selectPeriod = Request::get('selectPeriod');
        
        if ($selectPeriod != 'select') {
            list($from, $to) = self::getFromTo($selectPeriod);
            Request::push(array($fF => $from, $fT => $to));
        }
    }
    
    
    /**
     * @TODO описание
     *
     * След потготовка на формата за добавяне / редактиране.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $fF = $mvc->filterDateFrom ? $mvc->filterDateFrom : 'from';
        $fT = $mvc->filterDateTo ? $mvc->filterDateTo : 'to';
        
        $form = $data->listFilter;
        
        $form->FLD('selectPeriod', 'varchar', 'caption=Период,input,before=from,silent,printListFilter=none', array('attr' => array('onchange' => 'spr(this);')));
        if (strpos($form->showFields, $fF) !== false) {
            $form->showFields = trim(str_replace(",{$fF},", ",selectPeriod,{$fF},", ',' . $form->showFields . ','), ',');
        } else {
            $form->showFields .= ($form->showFields ? ',' : '') . 'selectPeriod';
        }
        
        
        $form->input($data->listFilter->showFields, 'silent');
        $rec = $form->rec;
        
        $keySel = null;
        if ($rec->selectPeriod && $rec->selectPeriod != 'select') {
            list($rec->{$fF}, $rec->{$fT}) = self::getFromTo($rec->selectPeriod);
            Request::push(array($fF => $rec->{$fF}, $fT => $rec->{$fT}));
        }
        $form->setOptions('selectPeriod', self::getOptions($keySel, $rec->{$fF}, $rec->{$fT}));
        
        if ($keySel) {
            $form->setDefault('selectPeriod', $keySel);
            $rec->selectPeriod = $keySel;
            Request::push(array('selectPeriod' => $keySel));
        }
    }
    
    
    /**
     * Подготвяме скриваме полетата
     */
    public static function on_BeforePrepareListSummary($mvc, &$res, $data)
    {
        $form = $data->listFilter;
        if (empty($form)) return;
        $fF = $mvc->filterDateFrom ? $mvc->filterDateFrom : 'from';
        $fT = $mvc->filterDateTo ? $mvc->filterDateFrom : 'to';
        
        $form->setField($fF, array('rowStyle' => 'display:none'));
        $form->setField($fT, array('rowStyle' => 'display:none'));
        
        $form->defOrder = true;
    }
    
    
    /**
     * Изчислява $from и $to
     */
    public static function getFromTo($sel)
    {
        if (date('N') == 7) {
            $now = dt::mysql2timestamp(dt::addDays(-1));
        } else {
            $now = dt::mysql2timestamp(dt::addDays(0));
        }
        
        switch ($sel) {
            
            // Ден
            case 'today':
                $from = $to = dt::today();
                break;
            case 'yesterday':
                $from = $to = dt::addDays(-1, null, false);
                break;
            case 'dby':
                $from = $to = dt::addDays(-2, null, false);
                break;
            
            // Седмица
            case 'cur_week':
                $from = date('Y-m-d', strtotime('monday this week', $now));
                $to = date('Y-m-d', strtotime('sunday this week', $now));
                break;
            
            case 'last_week':
                $from = date('Y-m-d', strtotime('monday last week', $now));
                $to = date('Y-m-d', strtotime('sunday last week', $now));
                break;
            
            // Месец
            case 'cur_month':
                $from = date('Y-m-d', strtotime('first day of this month'));
                $to = date('Y-m-d', strtotime('last day of this month'));
                break;
            case 'last_month':
                $from = date('Y-m-d', strtotime('first day of last month'));
                $to = date('Y-m-d', strtotime('last day of last month'));
                break;
            
            // Година
            case 'cur_year':
                $from = date('Y-01-01');
                $to = date('Y-12-t', strtotime($from));
                break;
            case 'last_year':
                $from = date('Y-01-01', strtotime('-1 year'));
                $to = date('Y-12-t', strtotime($from));
                break;
            
            // Последните
            case 'last7':
                $from = dt::addDays(-6, null, false);
                $to = dt::addDays(0, null, false);
                break;
            case 'last14':
                $from = dt::addDays(-13, null, false);
                $to = dt::addDays(0, null, false);
                break;
            case 'last30':
                $from = dt::addDays(-29, null, false);
                $to = dt::addDays(0, null, false);
                break;
            case 'last360':
                $from = dt::addDays(-359, null, false);
                $to = dt::addDays(0, null, false);
                break;
            
            // За всички да е празен стринг вместо NULL
            case 'gr0':
                $from = '';
                $to = '';
                break;
            default:
                if (preg_match('/^\\d{4}-\\d{2}-\\d{2}\\|\\d{4}-\\d{2}-\\d{2}$/', $sel)) {
                    list($from, $to) = explode('|', $sel);
                }
        
        }
        
        return array($from,  $to);
    }
    
    
    /**
     * Подготва опциите за избир на период
     */
    private static function getOptions(&$keySel = null, $fromSel = null, $toSel = null)
    {
        $opt = array();
        
        // Всички
        $opt['gr0'] = (object) array('title' => tr('Всички'));
        
        // Ден
        $opt['gr1'] = (object) array('title' => tr('Ден'), 'group' => true);
        $opt['today'] = tr('Днес');
        $opt['yesterday'] = tr('Вчера');
        $opt['dby'] = tr('Завчера');
        
        // Седмица
        $opt['gr2'] = (object) array('title' => tr('Седмица'), 'group' => true);
        $opt['cur_week'] = tr('Тази седмица');
        $opt['last_week'] = tr('Миналата седмица');
        
        // Месец
        $opt['gr3'] = (object) array('title' => tr('Месец'), 'group' => true);
        $opt['cur_month'] = tr('Този месец');
        $opt['last_month'] = tr('Миналият месец');
        
        // Година
        $opt['gr4'] = (object) array('title' => tr('Година'), 'group' => true);
        $opt['cur_year'] = tr('Тази година');
        $opt['last_year'] = tr('Миналата година');
        
        // Последни дни
        $opt['gr5'] = (object) array('title' => tr('Последните'), 'group' => true);
        $opt['last7'] = '7 ' .tr('дни');
        $opt['last14'] = '14 ' .tr('дни');
        $opt['last30'] = '30 ' .tr('дни');
        $opt['last360'] = '360 ' .tr('дни');
        
        // Друг период
        $opt['gr6'] = (object) array('title' => tr('Друг период'), 'group' => true);
        
        $f = count($opt);
        
        // Вкарваме периодите от recently
        $values = recently_Values::fetchSuggestions(self::RECENTLY_KEY, 5);
        if (is_array($values)) {
            foreach ($values as $val) {
                if (!$val) {
                    continue;
                }
                list($key, $title) = explode('=>', $val);
                if (!$opt[$key]) {
                    $opt[$key] = $title;
                }
            }
        }
        
        // Добяваме вербално определение и търсим евентуално ключа отговарящ на избрания период
        foreach ($opt as $key => $val) {
            if (is_scalar($val)) {
                @list($from, $to) = self::getFromTo($key);
                if (!$from || !$to) {
                    continue;
                }
                
                if (!strpos($key, '|')) {
                    $opt[$key] .= ' (' . self::getPeriod($from, $to) . ')';
                }
                
                if ($fromSel && $toSel) {
                    if ($fromSel == $from && $toSel == $to) {
                        $keySel = $key;
                    }
                }
            }
        }
        
        // Ако имаме входящ период, и той не е стандартен, добавяме го
        if ($fromSel && $toSel && !$keySel) {
            $keySel = $fromSel . '|' . $toSel;
            $title = self::getPeriod($fromSel, $toSel);
            if (!$opt[$keySel]) {
                $opt[$keySel] = $title;
            }
            $val = $fromSel . '|' . $toSel . '=>' . $title;
            recently_Values::add(self::RECENTLY_KEY, $val);
        }
        
        $first = array_slice($opt, 0, $f);
        $second = array_slice($opt, $f);
        
        uksort($second, function ($a, $b) {
            if (strpos($a, '|') && strpos($b, '|')) {
                $res = $a > $b ? 1 : -1;
            } else {
                $res = null;
            }
            
            return $res;
        });
        
        $opt = $first + $second;
        
        // Добавяме избор на производлен период
        $opt['select'] = (object) array('title' => tr('Избор'), 'attr' => array('class' => 'out-btn multipleFiles'));
        
        return $opt;
    }
    
    
    private static function getPeriod($from, $to)
    {
        list($y1, $m1, $d1) = explode('-', $from);
        list($y2, $m2, $d2) = explode('-', $to);
        
        if ($y1 == $y2) {
            $y1 = '';
            if ($m1 == $m2) {
                $m1 = '';
            }
        }
        
        $ldm = date('t', strtotime($to));
        if ($d1 == '01' && $d2 == $ldm) {
            $d1 = $d2 = '';
            if ($m1 == '01' && $m2 == '12') {
                $m1 = $m2 = '';
            }
        }
        
        if ($d1 && ($d1 == $d2) && !$m1 && !$y1) {
            $d1 = '';
        }
        
        if ($m2 && !$y1 && $y2 == date('Y')) {
            $y2 = '';
        }
        
        $v = '.';
        
        if (strlen("{$d1}{$m1}{$y1}{$d2}{$m3}{$y2}") < 10) {
            if ($m1) {
                $m1 = dt::getMonth($m1, 'FM');
            }
            if ($m2) {
                $m2 = dt::getMonth($m2, 'FM');
            }
            $v = ' ';
        }
        
        $period = trim(trim("{$d1}{$v}{$m1}{$v}{$y1}", $v) . '-' . trim("{$d2}{$v}{$m2}{$v}{$y2}", $v), '-');
        
        return $period;
    }
}
