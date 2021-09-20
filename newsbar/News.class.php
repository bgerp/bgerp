<?php 

/**
 * Лента с топ новини
 *
 *
 * @category  bgerp
 * @package   newsbar
 *
 * @author    Gabriela Petrova <gpetrova@experta.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class newsbar_News extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Новини';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Новина';
    
    
    /**
     * Разглеждане на листов изглед
     */
    public $canSingle = 'cms, newsbar, admin, ceo';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'newsbar_Wrapper, plg_Created, plg_State2, plg_RowTools2, newsbar_Plugin';
    
    
    /**
     * Полета за листовия изглед
     */
    public $listFields = 'news=Новина,position=Позиция,moving,newStartTime,newEndTime,domainId,color,transparency,state';
    
    
    /**
     * Поле за инструментите на реда
     */
    public $rowToolsField = '✍';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'cms, newsbar, admin, ceo';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'cms, newsbar, admin, ceo';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('news', 'richtext(rows=2,bucket=Notes)', 'caption=Новина->Текст');
        $this->FLD('newsHtml', 'html(rows=2,tinyEditor=no)', 'caption=Новина->HTML');
        $this->FLD('moving', 'enum(yes=Да, no=Не)', 'caption=Новина->Движение,notNull,mandatory');
        $this->FLD('startTime', 'datetime(format=smartTime)', 'caption=Показване на новината->Начало, mandatory');
        $this->FLD('endTime', 'datetime(defaultTime=23:59:59,format=smartTime)', 'caption=Показване на новината->Край,mandatory');
        $this->FLD('repeat', 'enum(no=Няма,weekly=Ежеседмично,monthly=Ежемесечно,monthlyEnd=Ежемесечно спрямо края на месеца,yearly=Ежегодно,yearlyEaster=Ежегодно спрямо Великден)', 'caption=Показване на новината->Повторение');
        $this->FLD('newStartTime', 'datetime(format=smartTime)', 'caption=Показване на новината->Начало, input=hidden,silent,column=none');
        $this->FLD('newEndTime', 'datetime(defaultTime=23:59:59,format=smartTime)', 'caption=Показване на новината->Край,input=hidden,silent,column=none');
        
        $this->FLD('domainId', 'key(mvc=cms_Domains, select=titleExt)', 'caption=Показване в->Домейн,notNull,defValue=bg,mandatory,autoFilter, removeAndRefreshForm=menu|eshopProducts, silent');
        $this->FLD('position', 'enum(topPage=В началото,bottomHeader=Над менюто,topContent=Преди съдържанието, bottomContent=След съдържанието, topNav=Над навигацията, bottomNav=Под навигацията, beforeFooter=Преди футър, afterFooter=След футър)', 'caption=Показване в->Позиция, notNull, mandatory');
        $this->FLD('menu', 'keylist(mvc=cms_Content,select=menu)', 'caption=Филтриране при показване->Меню');
        $this->FLD('articles', 'keylist(mvc=cms_Articles,select=title)', 'caption=Филтриране при показване->Статии');
        $this->FLD('eshopGroups', 'keylist(mvc=eshop_Groups,select=name)', 'caption=Филтриране при показване->Продуктови групи');
        $this->FLD('eshopProducts', 'keylist(mvc=eshop_Products,select=name)', 'caption=Филтриране при показване->Продукти');
        
        $this->FLD('color', 'color_Type', 'caption=Оформление->Фон,unit=rgb');
        $this->FLD('transparency', 'percent(min=0,max=1,decimals=0)', 'caption=Оформление->Непрозрачност');
        $this->FLD('border', 'color_Type', 'caption=Оформление->Бордер,unit=rgb');
        $this->FLD('padding', 'int', 'caption=Оформление->Падинг');
    }
    
    
    /**
     * Изпълнява се след подготовката на формата за филтриране
     *
     * @param newsbar_News $mvc
     * @param stdClass     $data
     */
    public function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->addAttr('domainId', array('refreshForm' => 'refreshForm'));
        
        $data->listFilter->fields['domainId']->type->params['allowEmpty'] = 'allowEmpty';
        
        $data->listFilter->fields['domainId']->caption = 'Домейн';
        
        $data->listFilter->title = 'Търсене';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->showFields = 'domainId';
        
        $data->listFilter->input($data->listFilter->showFields);
        
        $rec = $data->listFilter->rec;
        
        if ($rec->domainId) {
            $data->query->where(array("#domainId = '[#1#]'", $rec->domainId));
        }
        
        $data->query->orderBy('#createdOn', 'DESC');
    }
    
    
    /**
     * Създаване на лентата за новини
     */
    public static function getTopNews()
    {
        // Правим заявка към базата
        $query = static::getQuery();
        
        $nowTime = dt::now();
        
        $query->groupBy('RAND()');
        $query->limit(1);
        
        $domainId = cms_Domains::getPublicDomain('id');
        $query->where("#state = 'active'");
        $query->where("#startTime <= '{$nowTime}' AND  #endTime >= '{$nowTime}'");
        $query->where("#domainId = '{$domainId}'");
        
        $news = $query->fetch();
        
        // Връщаме стринг от всички новини
        return $news;
    }
    
    
    /**
     * Създаване на лентата за новини
     *
     * @return array
     */
    public static function getAllNews()
    {
        static $resArr = null;
        
        if (!isset($resArr)) {
            // Правим заявка към базата
            $query = static::getQuery();
            
            $nowTime = dt::now();
            
            $domainId = cms_Domains::getPublicDomain('id');
            $query->where("#state = 'active'");
            $query->where(array("#startTime <= '[#1#]' AND  #endTime >= '[#1#]'", $nowTime));
            $query->where(array("#domainId = '[#1#]'", $domainId));
            
            $query->XPR('order', 'double', 'RAND()');
            $query->orderBy('order');
            
            $resArr = $query->fetchAll();
        }
        
        return $resArr;
    }
    
    
    /**
     * Превръщане на цвят от 16-тичен към RGB
     */
    public static function hex2rgb($hex)
    {
        $hex = str_replace('#', '', $hex);
        
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1).substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1).substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1).substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        $rgb = array($r, $g, $b);
        
        return $rgb;
    }
    
    
    /**
     * Пренасочва URL за връщане след запис към лист изгледа
     */
    public function on_AfterPrepareRetUrl($mvc, $res, $data)
    {
        // Ако е субмитната формата
        if ($data->form && $data->form->isSubmitted()) {
            
            // Променяма да сочи към list'a
            $data->retUrl = toUrl(array($mvc, 'list'));
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            if (empty($form->rec->startTime)) {
                
                // Сетваме грешката
                $form->setError('startTime', 'Непопълнено начално време за видимост на новината');
            }
            
            if (empty($form->rec->endTime)) {
                
                // Сетваме грешката
                $form->setError('endTime', 'Непопълнено крайно време за видимост на новината');
            }
        }
        
        if ($form->isSubmitted()) {
            if (!$form->rec->news && !$form->rec->newsHtml) {
                $form->setError('news, newsHtml', 'Трябва да има попълнен текст за новина');
            }
        }
        
        if ($form->isSubmitted()) {
            if (!$form->rec->news && !$form->rec->newsHtml) {
                $form->setError('news, newsHtml', 'Трябва да има попълнен текст за новина');
            }
            
            if ($form->rec->repeat) {
                
                // изчисляваме повторенията на новината
                switch ($form->rec->repeat) {
                    
                    case 'no':
                        $form->rec->newStartTime = $form->rec->startTime;
                        $form->rec->newEndTime = $form->rec->endTime;
                        break;
                        
                    case 'weekly':
                        self::calcRepeatWeekly($form->rec);
                        break;
                        
                    case 'monthly':
                        self::calcRepeatМonthly($form->rec);
                        break;
                        
                    case 'monthlyEnd':
                        self::calcRepeatМonthlyEnd($form->rec);
                        break;
                        
                    case 'yearly':
                        self::calcRepeatYearly($form->rec);
                        break;
                        
                    case 'yearlyEaster':
                        self::calcRepeatByEaster($form->rec);
                        break;
                }
            }
        }
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        
        if (!$form->rec->id) {
            $form->setDefault('domainId', cms_Domains::getCurrent());
        }
        
        if (!$form->rec->padding) {
            $form->setDefault('padding', 10);
        }
        
        $progressArr = array('' => '');
        
        for ($i = 0; $i <= 100; $i += 10) {
            if ($rec->transparency > ($i / 100)) {
                continue;
            }
            $p = $i . ' %';
            $progressArr[$p] = $p;
        }
        $form->setSuggestions('transparency', $progressArr);
        
        if (!$rec->color) {
            $form->setDefault('color', '#000000');
        }
        
        if (!$rec->transparency) {
            $form->setDefault('transparency', 0.5);
        }

        $cArr = array();

        // Спрямо избрания домейн, показваме менютата
        $cQuery = cms_Content::getQuery();
        $cQuery->where("#state = 'active'");
        if ($data->form->rec->domainId) {
            $cQuery->where(array("#domainId = '[#1#]'", $data->form->rec->domainId));
            $cQuery->likeKeylist('sharedDomains', $data->form->rec->domainId, true);
        }
        $cQuery->show('id, menu');

        while ($cRec = $cQuery->fetch()) {
            $cArr[$cRec->id] = $cRec->menu;
        }
        $data->form->setSuggestions('menu', $cArr);

        // Спрямо избрания домейн, показваме продуктите
        $pQuery = eshop_Products::getQuery();
        $pQuery->where("#state = 'active'");
        if ($data->form->rec->domainId) {
            $pQuery->where(array("#domainId = '[#1#]'", $data->form->rec->domainId));
        }
        $pQuery->show('id, name');
        $pArr = array();
        while ($pRec = $pQuery->fetch()) {
            $pArr[$pRec->id] = $pRec->name;
        }
        $data->form->setSuggestions('eshopProducts', $pArr);

        // Показваме статиите до преди 2 год и текущата, която се редактира
        $aQuery = cms_Articles::getQuery();
        $aQuery->where("#state = 'active'");
        if ($data->form->rec->articles) {
            $aQuery->orWhere(array("#id = '[#1#]'", $data->form->rec->articles));
        }

        $before = dt::addDays(-2 * 365);
        $aQuery->where(array("#modifiedOn >= '[#1#]'", $before));
        if ($data->form->rec->articles) {
            $aQuery->orWhere(array("#id = '[#1#]'", $data->form->rec->articles));
        }

        if ($cArr) {
            $aQuery->in('menuId', array_keys($cArr));
        }
        if ($data->form->rec->articles) {
            $aQuery->orWhere(array("#id = '[#1#]'", $data->form->rec->articles));
        }
        $aQuery->orderBy('modifiedOn', 'DESC');
        $aArr = array();
        while ($aRec = $aQuery->fetch()) {
            $aArr[$aRec->id] = $aRec->title;
        }
        $data->form->setSuggestions('articles', $aArr);

        if (!empty($cArr)) {
            // Спрямо менютата от избрания домейн, показваме продуктовите групи
            $gQuery = eshop_Groups::getQuery();
            $gQuery->where("#state = 'active'");
            $aQuery->in('menuId', array_keys($cArr));
            $cQuery->show('id, name');

            $gArr = array();
            while ($cRec = $cQuery->fetch()) {
                $gArr[$cRec->id] = $cRec->name;
            }
            $data->form->setSuggestions('eshopGroups', $gArr);
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->news = self::generateHTML($rec)->getContent();
    }
    
    
    /**
     * Генериране на HTML код за лентата на нюзбара
     * ще я използваме както вътре, така и вънка
     *
     * @param stdClass $rec
     *
     * @return ET
     */
    public static function generateHTML($rec)
    {
        $rgb = static::hex2rgb($rec->color);
        $hexTransparency = dechex($rec->transparency * 255);
        $forIE = '#'. $hexTransparency. str_replace('#', '', $rec->color);
        
        $text = '';
        
        if ($rec->news) {
            $rt = cls::get('type_Richtext');
            $text = $rt->toHtml('[color=white]' . $rec->news . '[/color]');
        }
        
        if ($rec->newsHtml) {
            $text .= $rec->newsHtml;
        }
        
        $html = new ET("<div class=\"[#class#]\" style=\"<!--ET_BEGIN padding-->padding: [#padding#]px;<!--ET_END padding-->background-color: rgb([#r#], [#g#], [#b#]);
            										   background-color: rgba([#r#], [#g#], [#b#], [#transparency#]);
                                                       <!--ET_BEGIN borderColor--> border: 1px solid [#borderColor#];
                                                       border-style: solid;<!--ET_END borderColor-->
                          filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=[#ie#], endColorstr=[#ie#]);
                          -ms-filter: 'progid:DXImageTransform.Microsoft.gradient(startColorstr=[#ie#], endColorstr=[#ie#])';
                          zoom: 1;\">
            [#marquee#][#1#][#marquee2#]
            </div><div class='clearfix21'></div>", $text);
        
        $html->replace($rgb[0], 'r');
        $html->replace($rgb[1], 'g');
        $html->replace($rgb[2], 'b');
        $html->replace($rec->transparency, 'transparency');
        $html->replace($rec->border, 'borderColor');
        $html->replace($rec->padding, 'padding');
        $html->replace($forIE, 'ie');
        
        return $html;
    }
    
    
    /**
     * Изчисляване на повторението на новината ежемесечно
     *
     * @param data $rec
     */
    public static function calcRepeatWeekly($rec){
        
        // Намираме понеделнка на седмицата, в която е започнала новината
        $mondayStart = date('Y-m-d 00:00', strtotime('monday this week',  dt::mysql2timestamp($rec->startTime)));
        
        // Намираме текущия понеделник
        $mondayNow = date('Y-m-d 00:00', strtotime('monday this week',  dt::mysql2timestamp(dt::now())));
        
        // Намираме разликата между деня на започване на новината и понеделника в същата седмица
        $deffSec = dt::secsBetween($rec->startTime, $mondayStart);
        
        // Намираме продължителността на новината
        $durationSec = dt::secsBetween($rec->endTime, $rec->startTime);
        
        // Новото начало е към текущия понеделник добавяме изчислената разлика
        // от началото на новината до понеделника  неговата седмица
        $newStart = dt::addSecs($deffSec, $mondayNow);
        
        // Новият край е новото начало с добавена продължителност на новината
        $newEnd = dt::addSecs($durationSec, $newStart);
        
        $rec->newStartTime = $newStart;
        $rec->newEndTime = $newEnd;
        
        self::save($rec, 'newStartTime,newEndTime');
    }
    
    
    /**
     * Изчисляване на повторението на новината ежемесечно
     *
     * @param data $rec
     */
    public static function calcRepeatМonthly($rec){
        
        // Намираме първият ден от месеца, в който е започнала новината
        $firstDayOfMonthStart = date('Y-m-d 00:00', strtotime('first day of this month',  dt::mysql2timestamp($rec->startTime)));
        
        // Намираме текущия първи ден на месеца
        $firstDayOfMonthNow = date('Y-m-d 00:00', strtotime('first day of this month',  dt::mysql2timestamp(dt::now())));
        
        // Намираме разликата между деня на започване на новината и първия ден от месеца
        $deffSec = dt::secsBetween($rec->startTime, $firstDayOfMonthStart);
        
        // Намираме продължителността на новината
        $durationSec = dt::secsBetween($rec->endTime, $rec->startTime);
        
        // Новото начало е към текущия първи ден да добавяме изчислената разлика
        $newStart = dt::addSecs($deffSec, $firstDayOfMonthNow);
        
        // Новият край е новото начало с добавена продължителност на новината
        $newEnd = dt::addSecs($durationSec, $newStart);
        
        $rec->newStartTime = $newStart;
        $rec->newEndTime = $newEnd;
        
        self::save($rec, 'newStartTime,newEndTime');
    }
    
    
    /**
     * Изчисляване на повторението на новината ежемесечно спрямо края на месеца
     *
     * @param data $rec
     */
    public static function calcRepeatМonthlyEnd($rec){
        
        // Намираме последни ден от месеца, в който е започнала новината
        $lastDayOfMonthStart = date('Y-m-d 00:00', strtotime('last day of this month',  dt::mysql2timestamp($rec->startTime)));
        
        // Намираме текущия последен ден на месеца
        $lastDayOfMonthNow = date('Y-m-d 00:00', strtotime('last day of this month',  dt::mysql2timestamp(dt::now())));
        
        // Намираме разликата между деня на започване на новината и последния ден от месеца
        $deffSec = dt::secsBetween($lastDayOfMonthStart, $rec->startTime);
        
        // Намираме продължителността на новината
        $durationSec = dt::secsBetween($rec->endTime, $rec->startTime);
        
        // Новото начало е към текущия последен ден да добавяме изчислената разлика
        $newStart = dt::addSecs(-($deffSec), $lastDayOfMonthNow);
        
        // Новият край е новото начало с добавена продължителност на новината
        $newEnd = dt::addSecs($durationSec, $newStart);
        
        $rec->newStartTime = $newStart;
        $rec->newEndTime = $newEnd;
        
        self::save($rec, 'newStartTime,newEndTime');
    }
    
    
    /**
     * Изчисляване на повторението на новината ежегодно спрямо Великден
     *
     * @param data $rec
     */
    public static function calcRepeatByEaster($rec){
        
        // Вземаме от конфигурационната константа, кой великден ще търсим
        $Easter = newsbar_Setup::get('EASTER');
        
        // През коя година е започнала новината
        $Year = date('Y', dt::mysql2timestamp($rec->startTime));
        
        // Коя година сме сега
        $YearNow = date('Y', dt::mysql2timestamp(dt::now()));
        
        
        // изчисляваме Великден
        switch ($Easter) {
            case 'Orthodox':
                // изчисляваме кога е започнал Великден спрямо константата
                $EasterStartYear = date('Y-m-d 00:00', dt::getOrthodoxEasterTms($Year));
                
                // изчисляваме кога е започнал Великден спрямо константата текущата година
                $EasterThisYear = date('Y-m-d 00:00', dt::getOrthodoxEasterTms($YearNow));
                break;
            case 'Easter':
                // изчисляваме кога е започнал Великден спрямо константата
                $EasterStartYear = date('Y-m-d 00:00', dt::getEasterTms($Year));
                
                // изчисляваме кога е започнал Великден спрямо константата текущата година
                $EasterThisYear = date('Y-m-d 00:00', dt::getEasterTms($YearNow));
                break;
        }
        
        // Намираме разликата между деня на започване на новината и стартовия Великден
        $deffSec = dt::secsBetween($rec->startTime, $EasterStartYear);
        
        // Намираме продължителността на новината
        $durationSec = dt::secsBetween($rec->endTime, $rec->startTime);
        
        // Новото начало е към текущия Великден да добавяме изчислената разлика
        $newStart = dt::addSecs($deffSec, $EasterThisYear);
        
        // Новият край е новото начало с добавена продължителност на новината
        $newEnd = dt::addSecs($durationSec, $newStart);
        
        $rec->newStartTime = $newStart;
        $rec->newEndTime = $newEnd;
        
        self::save($rec, 'newStartTime,newEndTime');
    }
}
