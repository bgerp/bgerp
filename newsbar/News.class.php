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
    public $listFields = 'news=Новина,position=Позиция,moving,startTime,endTime,domainId,color,transparency,state';
    
    
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
        
        $this->FLD('domainId', 'key(mvc=cms_Domains, select=titleExt)', 'caption=Показване в->Домейн,notNull,defValue=bg,mandatory,autoFilter');
        $this->FLD('position', 'enum(bottomHeader=Над менюто, topPage=В началото, bottomMenu=Под менюто, topConten=Преди съдържанието, bottomContent=След съдържанието, topNav=Над навигацията, bottomNav=Под навигацията, beforeFooter=Преди футър, footer=Футър, afterFooter=След футър)', 'caption=Показване в->Позиция, notNull, mandatory');
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
        
        // Показваме статиите до преди 2 год и текущата, която се редактира
        $aQuery = cms_Articles::getQuery();
        $before = dt::addDays(-2 * 365);
        $aQuery->where(array("#modifiedOn >= '[#1#]'", $before));
        if ($data->form->rec->articles) {
            $aQuery->orWhere(array("#id = '[#1#]'", $data->form->rec->articles));
        }
        $aQuery->orderBy('modifiedOn', 'DESC');
        
        $aArr = array();
        while ($aRec = $aQuery->fetch()) {
            $aArr[$aRec->id] = $aRec->title;
        }
        
        $data->form->setSuggestions('articles', $aArr);
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
        
        $html = new ET("<div class=\"defaultNewsbar [#class#]\" style=\"<!--ET_BEGIN padding-->padding: [#padding#]px;<!--ET_END padding-->background-color: rgb([#r#], [#g#], [#b#]);
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
}
