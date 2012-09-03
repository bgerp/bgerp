<?php
/**
 * Мениджър на потребителски профили
 *
 * @category  bgerp
 * @package   crm
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.12
 */
class crm_Profiles extends core_Master
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = array();


    /**
     * Заглавие на мениджъра
     */
    var $title = "Профили";


    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Профил";


    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/vcard.png';


    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    var $loadList = 'plg_Created';


    /**
     * Кой  може да пише?
     */
    var $canWrite = 'admin';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin';


    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('userId', 'key(mvc=core_Users, select=nick)', 'caption=Потребител');
        $this->FLD('personId', 'key(mvc=crm_Persons)', 'input=hidden,silent');
        
        $this->setDbUnique('userId,personId');
    }
    
    
    /**
     * Екшън за смяна на парола
     * 
     * return core_ET
     */
    public static function act_ChangePassword()
    {
        $data = new stdClass();
        
        $data->form = static::getForm();
        
        /* @var $form core_Form */
        $form = &$data->form;

        foreach($form->fields as &$field) {
            $field->input = 'none';
        } 
        
        $form->FNC('old', 'password', 'caption=Стара парола,input,mandatory');
        $form->FNC('new', 'password', 'caption=Нова Парола,input,mandatory');
        $form->FNC('newAgain', 'password', 'caption=Нова Парола (пак),input,mandatory');
        
        $retUrl = getRetUrl();
        
        // Ако формата е успешно изпратена - запис, лог, редирект
        if (static::validatePasswordForm($form)) {
            // Записваме данните
            if (core_Users::setPassword($form->rec->new))  {
                // Правим запис в лога
                static::log('change_password');
            
                // Редиректваме към предварително установения адрес
                return new Redirect($retUrl, 'Паролата е сменена успешно');
            }
        }
        
        // Подготвяме лентата с инструменти на формата
        $form->toolbar->addSbBtn('Смяна', 'change_password', array('class' => 'btn-save'));
        $form->toolbar->addBtn('Отказ', $retUrl, array('class' => 'btn-cancel'));
        
        // Потготвяме заглавието на формата
        $form->title = 'Смяна на паролата';
        
        // Получаваме изгледа на формата
        $tpl = $form->renderHtml();
        
        // Опаковаме изгледа
        $tpl = static::renderWrapping($tpl, $data);
        
        return $tpl;
    }
    
    
    /**
     * Зарежда от заявката и валидира полетата на формата за смяна на парола
     *  
     * @param core_Form $form
     * @return boolean
     */
    protected static function validatePasswordForm(core_Form $form)
    { 
        $form->input();
        
        if ($form->isSubmitted()) {
            if (core_Users::getCurrent('ps5Enc') != core_Users::encodePwd($form->rec->old)) {
                $form->setError('old', 'Грешна стара парола');
            }
            
            if ($form->rec->new != $form->rec->newAgain) {
                $form->setError('new', 'Двете пароли не съвпадат');
                $form->setError('newAgain');
            }
        }
    
        return $form->isSubmitted();
    }

    
    /*
     * Методи за подготовка и показване на потребителски профил като детайл на визитка
     * 
     *  o prepareProfile()
     *  o renderProfile()
     *  
     */
    
    /**
     * Подготвя данните необходими за рендиране на профил на визитка
     */
    function prepareProfile($data)
    {
        expect($data->masterId);

        $data->profile = static::fetch("#personId = {$data->masterId}");
        
        if ($data->profile) {
            $data->profile->userRec = core_Users::fetch($data->profile->userId);
        }
    }
    
    
    /**
     * Рендира потребителски профил
     */
    function renderProfile($data)
    {
        $tpl = new ET(getFileContent('crm/tpl/ContragentDetail.shtml'));
        
        $tpl->append(tr('Потребителски профил'), 'title');
        
        if ($data->profile) {
            $profileTpl = new ET(getFileContent('crm/tpl/Profile.shtml'));
            $userRow = core_Users::recToVerbal($data->profile->userRec);
            $changePasswordBtn = ht::createBtn(
                tr('Смяна'), array($this, 'changePassword', 'ret_url'=>TRUE), FALSE, FALSE,
                'class=btn-edit,title=' . tr('Смяна на парола')
            );
            $profileTpl->append($changePasswordBtn, 'password');
            $profileTpl->placeObject($userRow);
            $tpl->append($profileTpl, 'content');
        } else {
            $tpl->append('<p>' . tr("Няма профил") . '</p>', 'content');
            if(!Mode::is('printing')) {
                if($this->haveRightFor('write')) {
                    // Добавяне на бутон за създаване на профил
                    $url = array($this, 'edit', 'personId'=>$data->masterId, 'ret_url' => TRUE);
                    $tpl->append(
                        ht::createBtn(
                            tr('Създаване'), $url, FALSE, FALSE, 
                            'class=btn-add button,title=' . tr('Създаване на профил')
                        ), 
                        'content'
                    );
                }
                
            }
        }
        
        return $tpl;
    }

    
    /**
     *
     * @param crm_Profiles $mvc
     * @param stdClass $res
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        /*
         * @TODO:
         * 
         * 1. Създаване на група "Потребителски профили" в crm_Groups. Създателя да е system user!
         * 2. Добавяне на екстендер "crm_Profiles" към групата "Потребителски профили".
         */
    }
    
}