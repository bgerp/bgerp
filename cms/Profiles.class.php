<?php


/**
 * Прокси на 'crm_Profiles' позволяващ на външен потребител с роля 'user'
 * да има достъп до профила си и да може да го редактира.
 *
 * @category  bgerp
 * @package   cms
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.12
 */
class cms_Profiles extends core_Master
{
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'crm_ProfileIntf';

	
    /**
     * Заглавие на мениджъра
     */
    public $title = "Профили";


    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Профил";

    
    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    public $loadList = 'Profile=crm_Profiles,cms_ExternalWrapper';


    /**
     * Кой  може да пише?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'partner';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има достъп до единичния изглед
     */
    public $canSingle = 'partner,powerUser';
    
    
	/**
     * Екшън по подразбиране е Single
     */
    function act_Default()
    {
        // Изискваме да е логнат потребител
        requireRole('partner,powerUser');
        
        // Редиректваме
        return new Redirect(array($this, 'Single'));
    }
    
    
    /**
     * Връща единичния изглед към профила на текущия потребител
     */
    function act_Single()
    {
        // Ако потребителя е powerUser, да се редиректне в профилите
        if (core_Users::isPowerUser()) {
            $id = Request::get('id', 'int');
            if ($id) {
                if (crm_Profiles::haveRightFor('single', $id)) {
                    
                    return new Redirect(array('crm_Profiles', 'single', $id));
                }
            } else {
                if (crm_Profiles::haveRightFor('list')) {
                    
                    return new Redirect(array('crm_Profiles', 'list'));
                }
            }
        }
          
        $this->requireRightFor('single');
        Mode::set('currentExternalTab', 'cms_Profiles');
        
    	// Създаваме обекта $data
        $data = new stdClass();
        $userId = core_Users::getCurrent();
        
        expect($data->rec = $this->Profile->fetch("#userId = {$userId}"));
       
        // Проверяваме дали потребителя може да вижда списък с тези записи
        $this->requireRightFor('single', $data->rec);
        
        // Подготвяме данните за единичния изглед
        $this->Profile->prepareSingle($data);
        
        // Промяна на някой данни, след подготовката на профила
        $this->modifyProfile($data);
        
        $data->Person->row->editLink = ht::createLink('', array($this, 'EditProfile', 'ret_url' => TRUE), FALSE, 'title=Редактиране на профила, ef_icon=img/32/edit.png');
        
        if(core_Users::haveRole('partner')){
        	unset($data->row->createdOn);
        	unset($data->row->createdBy);
        	unset($data->User->row->roles);
        	unset($data->User->row->modifiedOn);
        	unset($data->User->row->modifiedBy);
        }
        
        // Рендираме изгледа
        $tpl = $this->Profile->renderSingle($data);
        
        // Опаковаме изгледа
        $tpl = $this->renderWrapping($tpl, $data);
        
        // Записваме, че потребителя е разглеждал този списък
        $this->Profile->logRead('Виждане', $data->rec->id);
        
        // Връщане на шаблона
        return $tpl;
    }
    
    
    /**
     * Ф-я модифицираща данните за профила, така че да са подходящи за
     * външен достъп
     */
    private function modifyProfile(&$data)
    {
    	$data->toolbar->removeBtn('btnPrint');
        
    	// Подмяна на линка за смяна на паролата
        $data->User->row->password = substr($data->User->row->password, 0, 7);
        $changePassUrl = array($this, 'ChangePassword', 'ret_url' => TRUE);
        $data->User->row->password .= " " . ht::createLink('(' . tr('cмяна') . ')', $changePassUrl, FALSE, 'title=Смяна на парола');
    }
    
    
    /**
     * Екшън за смяна на парола, използва 'act_ChangePassword' на crm_Profiles
     * @return core_ET 
     */
    public function act_ChangePassword()
    {
    	requireRole('partner,powerUser');

        $form = $this->Profile->prepareChangePassword();
        $form->input();

        if ($form->isSubmitted()) {
            $this->Profile->validateChangePasswordForm($form);
	        if(!$form->gotErrors()){
				
	        	// Записваме данните
	         	if (core_Users::setPassword($form->rec->passNewHash))  {
		               // Правим запис в лога
		               $this->Profile->logWrite('Промяна на парола', $form->rec->id);
		               
		               // Редиректваме към предварително установения адрес
		               return new Redirect(getRetUrl(), "|Паролата е сменена успешно");
	            }
			}
        }
        
        $tpl = $form->renderHtml();
        $tpl = $this->renderWrapping($tpl);
        
        return $tpl;
    }
    
    
    /**
     * Екшън за редактиране на данните на колаборатора
     * 
     * @return core_ET
     */
    public function act_EditProfile()
    {
        requireRole('partner');
        
        $form = cls::get('core_Form');
        
        $fMap = array('names', 'email', 'avatar');
        
        $form->FNC('names', 'varchar', 'caption=Имена,mandatory, input, width=100%');
        $form->FNC('email', 'email(64, ci)', 'caption=Имейл,mandatory, input,width=100%');
        $form->FNC('avatar', 'fileman_FileType(bucket=Avatars)', 'caption=Аватар, input, width=100%');
        
        $uRec = core_Users::fetch(core_Users::getCurrent());
        
        foreach ($fMap as $f) {
            $form->setDefault($f, $uRec->{$f});
        }
        
        $form->input();
        
        $retUrl = getRetUrl();
        
        if (empty($retUrl)) {
            $retUrl = array('cms_Profiles', 'single');
        }
        
        if ($form->isSubmitted()) {
            
            foreach ($fMap as $f) {
                $uRec->{$f} = $form->rec->{$f};
            }
            
            core_Users::save($uRec, $fMap);
            
            try {
                $personRec = crm_Profiles::getProfile();
                
                crm_Profiles::syncPerson($personRec->id, $uRec);
            } catch (core_exception_Expect $e) {
                reportException($e);
            }
            
            $personRec = crm_Profiles::getProfile();
            
            $personRec->buzEmail = $form->rec->email;
            
            crm_Persons::save($personRec);
            
            // Редиректваме към предварително установения адрес
            return new Redirect($retUrl);
        }
        
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title=Запис на данните');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        $form->title = 'Редактиране на профила';
        
        $tpl = $form->renderHtml();
        $tpl = $this->renderWrapping($tpl);
        
        return $tpl;
    }
}
