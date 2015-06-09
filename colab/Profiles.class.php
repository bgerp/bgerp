<?php


/**
 * Прокси на 'crm_Profiles' позволяващ на външен потребител с роля 'user'
 * да има достъп до профила си и да може да го редактира.
 *
 * @category  bgerp
 * @package   colab
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.12
 */
class colab_Profiles extends core_Master
{
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'crm_ProfileIntf';

	
    /**
     * Заглавие на мениджъра
     */
    var $title = "Профили";


    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Профил";

    
    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    var $loadList = 'colab_Wrapper,Profile=crm_Profiles';


    /**
     * Кой  може да пише?
     */
    var $canWrite = 'no_one';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'user';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'user';
    
    
    /**
     * Кой има право да листва всички профили?
     */
    //var $canList = 'contractor';
    
    
    /**
     * Кой има достъп до единичния изглед
     */
    var $canSingle = 'user';
    
    
	/**
     * Екшън по подразбиране е Single
     */
    function act_Default()
    {
        // Изискваме да е логнат потребител
        requireRole('user');
        
        // Редиректваме
        return Redirect(array($this, 'Single'));
    }
    
    
    /**
     * Връща единичния изглед към профила на текущия потребител
     */
    function act_Single()
    {        
        $this->requireRightFor('single');
        
    	// Създаваме обекта $data
        $data = new stdClass();
        $userId = core_Users::getCurrent();
        
        expect($data->rec = $this->Profile->fetch("#userId = {$userId}"));
       
        // Проверяваме дали потребителя може да вижда списък с тези записи
        $this->requireRightFor('single', $data->rec);
        
        unset($this->Profile->loadList);
        $this->Profile->load('colab_Wrapper');
        
        // Подготвяме данните за единичния изглед
        $this->Profile->prepareSingle($data);
        
        // Промяна на някой данни, след подготовката на профила
        $this->modifyProfile($data);
        
        // Рендираме изгледа
        $tpl = $this->Profile->renderSingle($data);
        
        // Опаковаме изгледа
        $tpl = $this->Profile->renderWrapping($tpl, $data);
       
        // Записваме, че потребителя е разглеждал този списък
        $this->log('Single: ' . ($data->log ? $data->log : tr($data->title)), $data->rec->id);
        
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
        $data->User->row->password .= " " . ht::createLink('(' . tr('cмяна') . ')', $changePassUrl, FALSE, 'title=' . tr('Смяна на парола'));
    }
    
    
    /**
     * Екшън за смяна на парола, използва 'act_ChangePassword' на crm_Profiles
     * @return core_ET 
     */
    public function act_ChangePassword()
    {
    	requireRole('user');

        $form = $this->Profile->prepareChangePassword();
        $form->input();

        if ($form->isSubmitted()) {
            $this->Profile->validateChangePasswordForm($form);
	        if(!$form->gotErrors()){
				
	        	// Записваме данните
	         	if (core_Users::setPassword($form->rec->passNewHash))  {
		               // Правим запис в лога
		               static::log('change_password');
		            
		               // Редиректваме към предварително установения адрес
		               return new Redirect(getRetUrl(), "Паролата е сменена успешно");
	            }
			}
        }
        
        $tpl = $form->renderHtml();
        
        unset($this->Profile->loadList);
        $this->Profile->load('colab_Wrapper');
        
        $tpl = $this->Profile->renderWrapping($tpl);
        
        return $tpl;
    }
}