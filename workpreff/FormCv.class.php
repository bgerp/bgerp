<?php


/**
 * Мениджър на форма за CV
 *
 *
 * @category  bgerp
 * @package   workpreff
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Форма за CV
 */
class workpreff_FormCv extends core_Master
{
    /**
     * Кой има право да чете?
     */
    public $canRead = 'hr, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'hr,ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'hr,ceo';
    
    
    /**
     * Кой има право да чете?
     */
    public $canSummarise = 'hr,ceo';
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf,cms_SourceIntf';
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Форма CV';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'CV';
    
    
    /**
     * Полета, които се показват в листови изглед
     */
    public $listFields = 'name,egn,place,mobile';
    
    
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'workpreff/tpl/SingleLayoutCV.shtml';
    
    
    /**
     * Полета за експорт
     */
    public $exportableCsvFields = 'name,egn,country,place,email,info,birthday,pCode,place,address,tel,mobile';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'CVf';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '5.7|Човешки ресурси';
    
    
    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    public $loadList = 'doc_DocumentPlg, plg_RowTools2,hr_Wrapper, plg_Printing, plg_State, plg_PrevAndNext,doc_ActivatePlg';
    
    
    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = 'doc_UnsortedFolders';
    
    
    /**
     * Кой има право да добавя?
     *
     * @var string|array
     */
    public $canNew = 'every_one';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('typeOfPosition', 'enum(,adm=Администрация,man=Производство, log=Логистика,sall=Продажби)', 'caption=Тип на позицията,mandatory,silent,refreshForm,allowEmpty');
        
        // Име на лицето
        // $this->FLD('salutation', 'enum(,mr=Г-н,mrs=Г-жа,miss=Г-ца)', 'caption=Обръщение,export=Csv');
        $this->FLD('name', 'varchar(255,ci)', 'caption=Информация->Имена,class=contactData,mandatory,remember=info,silent,export=Csv');
        
        // $this->FNC('nameList', 'varchar', 'sortingLike=name');
        
        // Единен Граждански Номер
        $this->FLD('egn', 'bglocal_EgnType', 'caption=ЕГН,export=Csv');
        
        //Снимка
        $this->FLD('photo', 'fileman_FileType(bucket=pictures)', 'caption=Фото,export=Csv');
        
        // Адресни данни
        $this->FLD(
            'country',
            'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,default=Bg)',
                    'caption=Адресни данни->Държава,remember,class=contactData,silent,export=Csv'
        );
        $this->FLD('pCode', 'varchar(16)', 'caption=П. код,recently,class=pCode,export=Csv');
        $this->FLD('place', 'varchar(64)', 'caption=Град,class=contactData,hint=Населено място: град или село и община,export=Csv');
        $this->FLD('address', 'varchar(255)', 'caption=Адрес,class=contactData,export=Csv');
        
        // Лични комуникации
        $this->FLD('email', 'emails', 'caption=Лични комуникации->Имейли,class=contactData,export=Csv');
        $this->FLD('tel', 'drdata_PhoneType(type=tel)', 'caption=Лични комуникации->Телефони,class=contactData,silent,export=Csv');
        $this->FLD('mobile', 'drdata_PhoneType(type=tel)', 'caption=Лични комуникации->Мобилен,class=contactData,silent,export=Csv');
        
        
        $period = '';
        $months = '';
        
        for ($i = 1989;$i <= 2017;$i++) {
            $period .= $i.'|';
        }
        
        $monthsArr = array('Ян', 'Фев', 'Мар', 'Апр', 'Май', 'Юни', 'Юли', 'Авг', 'Сеп', 'Окт', 'Ное', 'Дек');
        foreach ($monthsArr as $m) {
            $months .= $m.'|';
        }
        
        $this->FLD('workExperience', "table(columns=orgName|position|beginM|beginY|endM|endY,beginM_opt=${months},beginY_opt=${period},endM_opt=${months},endY_opt=${period},captions=Фирма/Организация|Длъжност|ОТ мес|год|ДО мес|год,widths=20em|15em|4em|4em|4em|4em)", 'caption=Трудов стаж||Extras->Месторабота||Additional,autohide,advanced,export=Csv');
        
        $this->FLD('education', 'table(columns=school|specility|begin|end,captions=Учебно заведение|Степен/Квалификация|Начало|Край,widths=20em|15em|5em|5em)', 'caption=Образование||Extras->Обучение||Additional,autohide,advanced,export=Csv');
        
        $this->FLD('workpreff', 'blob(compress,serialize)', 'caption = Предпочитания,input=none');
        
        
        $this->FLD('state', 'enum(draft=Чернова,active=Публикувана,rejected=Оттеглена)', 'caption=Състояние,input=none');
    }
    
    
    public function act_New()
    {
        $this->requireRightFor('new');
        
        // 		expect($id = Request::get('id'));
        // 		expect($rec = $this->fetch($id));
        // 		$this->requireRightFor('new', $rec);
        
        $form = $this->getForm();
        foreach (array('folderId', 'threadId', 'originId', 'id') as $fld) {
            $form->setField($fld, 'input=none');
        }
        
        $form->input(null, 'silent');
        $data = (object) array('form' => $form);
        self::expandEditForm($mvc, $data);
        
        
        $form->input();
        $this->invoke('AfterInputEditForm', array(&$form));
        
        if ($form->isSubmitted()) {
            $rec = $form->rec;
            
            $rec->state = 'active';
            $this->save($rec);
            
            return followRetUrl(null, '|Вашето CV е прието. Благодарим за проявения интерес. ', 'success');
        }
        
        
        $form->title = 'Изпращане на CV';
        $form->toolbar->addSbBtn('Изпрати', 'save', 'id=save, ef_icon = img/16/disk.png,title=Изпращане на CV');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'id=cancel, ef_icon = img/16/close-red.png,title=Отказ');
        $tpl = $form->renderHtml();
        
        // Поставяме шаблона за външен изглед
        Mode::set('wrapper', 'cms_page_External');
        
        return $tpl;
    }
    
    
    private static function expandEditForm($mvc, &$data)
    {
        $form = &$data->form;
        
        $rec = &$form->rec;
        
        $form->setDefault('country', drdata_Countries::getIdByName('bul'));
        
        if ($rec->id) {
            $exRec = $mvc->fetch($rec->id);
        } else {
            $exRec = $rec;
        }
        
        $exRec = $rec;
        
        $form->input('typeOfPosition');
        
        if ($exRec->typeOfPosition) {
            $options = workpreff_WorkPreff::getOptionsForChoice();
            
            if (is_array($options)) {
                foreach ($options as $v) {
                    if (in_array($exRec->typeOfPosition, $v->typeOfPosition)) {
                        if ($v->type == 'enum') {
                            foreach ($v->parts as $k => $venum) {
                                $parts .= $k.'='.$venum.',' ;
                            }
                            
                            $parts = trim($parts, ',') ;
                            
                            
                            $form->FLD("workpreff_{$v->id}", "enum(${parts})", "caption={$v->name},maxRadio={$v->count},columns=3,input");
                            
                            $form->setDefault("workpreff_{$v->id}", $exRec->workpreff[$v->id]->value);
                            
                            unset($parts);
                        }
                        
                        if ($v->type == 'set') {
                            foreach ($v->parts as $k => $vset) {
                                $parts .= $k.'='.$vset.',' ;
                            }
                            
                            
                            $parts = trim($parts, ',') ;
                            
                            $form->FLD("workpreff_{$v->id}", "set(${parts})", "caption ={$v->name},input");
                            
                            
                            $form->setDefault("workpreff_{$v->id}", $exRec->workpreff[$v->id]->value);
                            
                            unset($parts);
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        self::expandEditForm($mvc, $data);
    }
    
    
    /**
     * Добавям поле "ПРЕДПОЧИТАНИЯ" във формата
     *
     * @param $mvc
     * @param $form
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec;
        
        $preferencesForWork = array();
        
        if ($form->isSubmitted()) {
            $workpreff = new stdClass();
            
            
            foreach ($form->rec as $k => $v) {
                if (substr($k, 0, 10) == 'workpreff_') {
                    $preferencesForWork[substr($k, 10)] = (object) array(
                        
                        'value' => $v
                    
                    );
                }
            }
            
            
            $form->rec->workpreff = $preferencesForWork;
        }
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        $title = $this->recToverbal($rec, 'name')->name;
        $row = new stdClass();
        $row->title = $this->singleTitle . ' - ' . $title;
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->recTitle = $rec->title;
        
        return $row;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public static function getHandle($id)
    {
        $rec = static::fetch($id);
        $self = cls::get(get_called_class());
        
        return $self->abbr . $rec->id;
    }
    
    
    /**
     * Вербализиране на полето Предпочитания
     *
     * @param $mvc
     * @param $row
     * @param $rec
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if ($fields['-single']) {
            $row->singleTitle = 'CV - ' . $row->name;
            
            // Fancy ефект за картинката
            $Fancybox = cls::get('fancybox_Fancybox');
            
            $tArr = array(120, 120);
            $mArr = array(450, 450);
            
            if ($rec->photo) {
                $row->image = $Fancybox->getImage($rec->photo, $tArr, $mArr);
            }
            
            $row->egn = $rec->egn;
        }
        
        
        $prepare = '';
        
        if (is_array($rec->workpreff)) {
            foreach ($rec->workpreff as $k => $v) {
                $printChoice = '';
                
                $printChoice = workpreff_WorkPreff::fetch($k)->name;
                
                $printValues = explode(',', $v->value);
                
                $printValue = '';
                
                
                foreach ($printValues as $vp) {
                    if (!$vp) {
                        continue;
                    }
                    $printValue .= '<div>' . workpreff_WorkPreffDetails::fetch($vp)->name . '</div>';
                }
                if (!empty($printValue)) {
                    $prepare .= "<tr><td class='aright'>" . $printChoice . ': ' . "</td><td class='aleft' colspan='2'>" . $printValue . '</td></tr>';
                }
            }
        }
        
        $row->workpreff = "${prepare}";
        
        $prepare = '';
    }
    
    
    /**
     * Връща URL към себе си (блога)
     */
    public function getUrlByMenuId($cMenuId)
    {
        return array('workpreff_FormCv', 'new', 'ret_url' => true);
    }
    
    
    /**
     * Връща URL към вътрешната част (работилницата), отговарящо на посочената точка в менюто
     */
    public function getWorkshopUrl($menuId)
    {
        $url = array('workpreff_FormCv', 'list');
        
        return $url;
    }
}
