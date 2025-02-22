<?php


/**
 * Клас 'plg_SelectPeriod' - Добавя избор на период
 *
 *
 * @category  bgerp
 * @package   plg
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2021 Experta OOD
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
    
    
    /**
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $fF = $mvc->filterDateFrom ? $mvc->filterDateFrom : 'from';
        $fT = $mvc->filterDateTo ? $mvc->filterDateTo : 'to';
        $showFuturePeriods = $mvc->filterFutureOptions ? $mvc->filterFutureOptions : false;

        $form = $data->form;
        $rec = $form->rec;
        
        if (!$form->fields[$fF] || !$form->fields[$fT] || !$mvc->useFilterDateOnEdit) {
            
            return ;
        }
        
        if (($form->fields[$fF]->input == 'none') || ($form->fields[$fF]->input == 'hidden')) {
            
            return ;
        }
        
        if (($form->fields[$fT]->input == 'none') || ($form->fields[$fT]->input == 'hidden')) {
            
            return ;
        }
        
        if (!($form->fields[$fF]->type instanceof type_Date) || !($form->fields[$fT]->type instanceof type_Date)) {
            
            return ;
        }
        
        $refresh = '';
        if ($form->fields[$fF]->removeAndRefreshForm) {
            $refresh = $form->fields[$fF]->removeAndRefreshForm;
        }
        
        if ($form->fields[$fT]->removeAndRefreshForm) {
            $refresh = trim($refresh, '|');
            $refresh .= $refresh ? '|' : '';
            $refresh .= $form->fields[$fT]->removeAndRefreshForm;
        }
        
        if ($refresh) {
            $refresh = ',removeAndRefreshForm=' . $refresh;
        } else {
            if (isset($form->fields[$fF]->removeAndRefreshForm) || isset($form->fields[$fT]->removeAndRefreshForm) || isset($form->fields[$fF]->refreshForm) || isset($form->fields[$fT]->refreshForm)) {
                $refresh = ',removeAndRefreshForm';
            }
        }
        
        $fFEsc = json_encode($fF);
        $fTEsc = json_encode($fT);

        $mandatory = ($form->fields[$fF]->mandatory || $form->fields[$fT]->mandatory) ? ',mandatory' : '';
        $form->FLD('selectPeriod', 'varchar', "caption=Период,input,before=from,silent,printListFilter=none,before={$fF}{$mandatory}{$refresh},mustExist", array('attr' => array('onchange' => "spr(this,false, {$fFEsc}, {$fTEsc});")));
        
        $keySel = null;
        $form->setOptions('selectPeriod', self::getOptions($keySel, $rec->{$fF}, $rec->{$fT}, $showFuturePeriods));

        if (!$form->isSubmitted() && ($form->cmd != 'refresh')) {
            if (!$keySel) {
                if ($data->form->rec->id && $fF && $fT) {
                    if ((!$rec->{$fF} || !$rec->{$fT}) && ($rec->{$fF} || $rec->{$fT})) {
                        $keySel = 'select';
                    }
                }
            }
        }

        if ($rec->selectPeriod && $rec->selectPeriod != 'select') {
            list($rec->{$fF}, $rec->{$fT}) = self::getFromTo($rec->selectPeriod);
            Request::push(array($fF => $rec->{$fF}, $fT => $rec->{$fT}));
        }
        
        if ($keySel && !$form->isSubmitted() && ($form->cmd != 'refresh')) {
            $form->setDefault('selectPeriod', $keySel);
            $rec->selectPeriod = $keySel;
            Request::push(array('selectPeriod' => $keySel));
        }
        
        $form->input('selectPeriod');
        
        if (($rec->selectPeriod) && ($rec->selectPeriod != 'select')) {
            $selPerArr = self::getFromTo($rec->selectPeriod);
            if ($mandatory && (!$selPerArr || ((!$selPerArr[0]) && (!$selPerArr[1])))) {
                $form->setError('selectPeriod', 'Трябва да изберете период');
            } else {
                list($rec->{$fF}, $rec->{$fT}) = self::getFromTo($rec->selectPeriod);
                Request::push(array($fF => $rec->{$fF}, $fT => $rec->{$fT}));
            }
        }
        
        if ($rec->selectPeriod != 'select') {
            $form->setField($fF, array('rowStyle' => 'display:none'));
            $form->setField($fT, array('rowStyle' => 'display:none'));
        }
    }
    
    
    /**
     * 
     * @param core_Mvc $mvc
     * @param null|stdClass $res
     * @param stdClass $data
     */
    public static function on_BeforePrepareListFilter($mvc, &$res, $data)
    {
        if ($mvc->useFilterDateOnFilter === false) {
            
            return ;
        }
        
        $fF = $mvc->filterDateFrom ? $mvc->filterDateFrom : 'from';
        $fT = $mvc->filterDateTo ? $mvc->filterDateFrom : 'to';
        
        $form = $data->listFilter;
        
        $selectPeriod = Request::get('selectPeriod');
        
        if(empty($selectPeriod) && isset($mvc->defaultSelectPeriod)) {
            $selectPeriod = $mvc->defaultSelectPeriod;
        }

        if (!empty($selectPeriod) && $selectPeriod != 'select') {
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
        if ($mvc->useFilterDateOnFilter === false) {
            
            return ;
        }
        
        $fF = $mvc->filterDateFrom ? $mvc->filterDateFrom : 'from';
        $fT = $mvc->filterDateTo ? $mvc->filterDateTo : 'to';
        $showFuturePeriods = $mvc->filterFutureOptions ? $mvc->filterFutureOptions : false;

        $form = $data->listFilter;
        
        $fFEsc = json_encode($fF);
        $fTEsc = json_encode($fT);
        
        $form->FLD('selectPeriod', 'varchar', 'caption=Период,input,before=from,silent,printListFilter=none', array('attr' => array('onchange' => "spr(this, true, {$fFEsc}, {$fTEsc});")));
        if (strpos($form->showFields, $fF) !== false) {
            $form->showFields = trim(str_replace(",{$fF},", ",selectPeriod,{$fF},", ',' . $form->showFields . ','), ',');
        } else {
            $form->showFields .= ($form->showFields ? ',' : '') . 'selectPeriod';
        }
        
        $form->input($data->listFilter->showFields, 'silent');
        $rec = $form->rec;
        
        if ($rec->selectPeriod == 'select') {
            $form->showFields .= ",{$fF}, {$fT}";
        }
        
        $keySel = null;
        if ($rec->selectPeriod && $rec->selectPeriod != 'select') {
            list($rec->{$fF}, $rec->{$fT}) = self::getFromTo($rec->selectPeriod);
            Request::push(array($fF => $rec->{$fF}, $fT => $rec->{$fT}));
        }
        if ($mvc->showSelectPeriod === false) {
            $showSelect = false;
        } else {
            $showSelect = true;
        }
        $form->setOptions('selectPeriod', self::getOptions($keySel, $rec->{$fF}, $rec->{$fT}, $showFuturePeriods, $showSelect));
        
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
        if ($mvc->useFilterDateOnFilter === false) {
            
            return ;
        }
        
        $form = $data->listFilter;
        if (empty($form)) return;
        $fF = $mvc->filterDateFrom ? $mvc->filterDateFrom : 'from';
        $fT = $mvc->filterDateTo ? $mvc->filterDateFrom : 'to';
        
        if ($form->fields[$fF] && ($form->rec->selectPeriod != 'select')) {
            $form->setField($fF, array('rowStyle' => 'display:none'));
        }
            
        if ($form->fields[$fF] && ($form->rec->selectPeriod != 'select')) {
            $form->setField($fT, array('rowStyle' => 'display:none'));
        }

        setIfNot($form->defOrder, $data->defOrder, true);
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
            case 'tomorrow':
                $from = $to = dt::addDays(1, null, false);
                break;
            case 'dayafter':
                $from = $to = dt::addDays(2, null, false);
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
            case 'next_week':
                $from = date('Y-m-d', strtotime('monday next week', $now));
                $to = date('Y-m-d', strtotime('sunday next week', $now));
                break;

            case 'one_week_next_before':
                $from = date('Y-m-d', strtotime('-1 week', $now));
                $to = date('Y-m-d', strtotime('+1 week', $now));
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
            case 'next_month':
                $from = date('Y-m-d', strtotime('first day of next month'));
                $to = date('Y-m-d', strtotime('last day of next month'));
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
            case 'next_year':
                $from = date('Y-01-01', strtotime('1 year'));
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
            case 'next7':
                $from = dt::addDays(1, null, false);
                $to = dt::addDays(8, null, false);
                break;
            case 'next14':
                $from = dt::addDays(1, null, false);
                $to = dt::addDays(15, null, false);
                break;
            case 'next30':
                $from = dt::addDays(1, null, false);
                $to = dt::addDays(31, null, false);
                break;
            // За всички да е празен стринг вместо NULL
            case 'gr0':
                $oldestAvailableDate = self::getOldestAvailableDate();

                $from = !empty($oldestAvailableDate) ? $oldestAvailableDate : '';
                $to = self::getNewestAvailableDate();

                break;
            default:
                if (preg_match('/^\\d{4}-\\d{2}-\\d{2}\\|\\d{4}-\\d{2}-\\d{2}$/', $sel)) {
                    list($from, $to) = explode('|', $sel);
                }
        }

        return array($from,  $to);
    }


    /**
     * Коя е най-старата възжможна за избор дата
     *
     * @return date|null
     */
    public static function getOldestAvailableDate()
    {
        $oldestHorizon = doc_Setup::get('SELECT_ALL_PERIOD_IN_LIST_MIN_HORIZON');
        if(!empty($oldestHorizon)){
            $oldestDateAvailable = dt::addSecs(-1 * $oldestHorizon, null, false);
            $oldestDateAvailable = dt::mysql2verbal($oldestDateAvailable, 'Y-01-01');

            return $oldestDateAvailable;
        }

        return null;
    }


    /**
     * Коя е най-новата възжможна дата за избор
     *
     * @return date|string
     */
    public static function getNewestAvailableDate()
    {
        $res = '';

        $res = Mode::get('periodDefaultNewestDate');

        if (empty($res)) {
            $res = '';
        }

        return $res;
    }


    /**
     * Подготва опциите за избир на период
     */
    public static function getOptions(&$keySel = null, $fromSel = null, $toSel = null, $showFutureOptions = false, $showSelect = true)
    {
        $opt = array();

        $oldestHorizon = doc_Setup::get('SELECT_ALL_PERIOD_IN_LIST_MIN_HORIZON');
        $captionAll = tr('Всички');
        if(!empty($oldestHorizon)){
            $oldestDateAvailable = dt::addSecs(-1 * $oldestHorizon, null, false);
            $oldestDateAvailable = dt::mysql2verbal($oldestDateAvailable, 'Y-01-01');
            $oldestDateAvailableVerbal = dt::mysql2verbal($oldestDateAvailable, 'd.m.Y');
            $captionAll = tr("Всички|* (|от|* {$oldestDateAvailableVerbal})");
        }

        // Всички
        $opt['gr0'] = (object) array('title' => $captionAll);
        
        // Ден
        $opt['gr1'] = (object) array('title' => tr('Ден'), 'group' => true);

        $opt['today'] = tr('Днес');
        $opt['yesterday'] = tr('Вчера');
        $opt['dby'] = tr('Завчера');

        if ($showFutureOptions){
            $opt['tomorrow'] = tr('Утре');
            $opt['dayafter'] = tr('Вдругиден');
        }

        // Седмица
        $opt['gr2'] = (object) array('title' => tr('Седмица'), 'group' => true);
        $opt['cur_week'] = tr('Тази седмица');
        $opt['last_week'] = tr('Миналата седмица');

        if($showFutureOptions){
            $opt['one_week_next_before'] = tr('±1 седмица');
        }

        if($showFutureOptions){
            $opt['next_week'] = tr('Следващата седмица');
        }

        // Месец
        $opt['gr3'] = (object) array('title' => tr('Месец'), 'group' => true);
        $opt['cur_month'] = tr('Този месец');
        $opt['last_month'] = tr('Миналият месец');

        if($showFutureOptions){
            $opt['next_month'] = tr('Следващият месец');
        }

        // Година
        $opt['gr4'] = (object) array('title' => tr('Година'), 'group' => true);
        $opt['cur_year'] = tr('Тази година');
        $opt['last_year'] = tr('Миналата година');

        if($showFutureOptions){
            $opt['next_year'] = tr('Следващата година');
        }

        // Последни дни
        $opt['gr5'] = (object) array('title' => tr('Последните'), 'group' => true);
        $opt['last7'] = '7 ' .tr('дни');
        $opt['last14'] = '14 ' .tr('дни');
        $opt['last30'] = '30 ' .tr('дни');
        $opt['last360'] = '360 ' .tr('дни');

        if($showFutureOptions){
            $opt['gr7'] = (object) array('title' => tr('Следващите'), 'group' => true);
            $opt['next7'] = '7 ' .tr('дни');
            $opt['next14'] = '14 ' .tr('дни');
            $opt['next30'] = '30 ' .tr('дни');
        }

        // Друг период
        $opt['gr6'] = (object) array('title' => tr('Друг период'), 'group' => true);
        
        $f = countR($opt);
        
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

        if ($showSelect) {
            // Добавяме избор на производлен период
            $opt['select'] = (object) array('title' => tr('Избор'), 'attr' => array('class' => 'out-btn multipleFiles'));
        }

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
        
        if (strlen("{$d1}{$m1}{$y1}{$d2}{$m2}{$y2}") < 10) {
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
