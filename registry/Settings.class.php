<?php

/**
 *
 * Централизиран регистър, съхраняващ конфигурационни данни на различните мениджъри
 *
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 *
 */
class registry_Settings extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'registry_Wrapper, plg_RowTools, plg_Created';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('manager', 'class(interface=intf_Settings,select=info,allowEmpty)',
        'caption=Мениджър,mandatory');
        $this->FLD('section', 'varchar(64)', 'caption=Раздел');
        $this->FLD('data', 'blob', 'caption=Данни,input=none');
        $this->FNC('settings', 'text', 'column=none');
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function on_CalcSettings($mvc, $rec)
    {
        if (!empty($rec->data)) {
            $rec->settings = unserialize($rec->data);
        }
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function act_ManageSettings()
    {
        expect($id = Request::get('id', 'int'));
        expect($settingsRec = $this->fetch($id));
        
        $Classes = &cls::get('core_Classes');
        $mgrName = $Classes->fetchField($settingsRec->manager, 'name');
        $manager = &cls::get($mgrName);
        
        // Създаване и подготвяне на формата
        
        $formTitle = $this->getVerbal($settingsRec, 'manager') . ' [' . $settingsRec->section . ']';
        $form = &cls::get('core_Form', array('title' => $formTitle));
        
        $data = (object)array(
            'settingsRec' => $settingsRec,
            'form' => $form,
        );
        
        $form->rec = $settingsRec->settings;
        
        $manager->invoke('AfterPrepareSettingsForm', array($data));
        
        // Подготвяме адреса за връщане, ако потребителя не е логнат.
        // Ресурса, който ще се зареди след логване обикновено е страницата, 
        // от която се извиква екшъна act_Manage
        $retUrl = getRetUrl();
        
        // Определяме, какво действие се опитваме да направим
        $data->cmd = isset($data->form->rec->id)?'Edit':'Add';
        
        // Очакваме до този момент във формата да няма грешки
        expect(!$data->form->gotErrors(), 'Има грешки в silent полетата на формата');
        
        // Дали имаме права за това действие към този запис?
        $this->requireRightFor($data->cmd, $data->form->rec, NULL, $retUrl);
        
        // Зареждаме формата
        $rec = $data->form->input();
        
        // Проверка дали входните данни са уникални
        if($rec) {
            if(!$this->isUnique($rec, $fields)) {
                $data->form->setError($fields, "Вече съществува запис със същите данни");
            }
        }
        
        // Генерираме събитие в mvc, след въвеждането на формата, ако е именована
        $manager->invoke('AfterInputSettingsForm', array($data));
        
        // Дали имаме права за това действие към този запис?
        $this->requireRightFor($data->cmd, $rec, NULL, $retUrl);
        
        // Подготвяме адреса, към който трябва да редиректнем,  
        // при успешно записване на данните от формата
        $this->prepareRetUrl($data);
        
        // Ако формата е успешно изпратена - запис, лог, редирект
        if ($data->form->isSubmitted()) {
            $settingsRec->settings = $rec;
            // Записваме данните
            $id = $this->save($settingsRec);
            
            // Правим запис в лога
            $this->log($data->cmd, $id);
            
            // Редиректваме към предваритлено установения адрес
            return new Redirect($data->retUrl);
        }
        
        // Подготвяме тулбара на формата
        $this->prepareEditToolbar($data);
        // Получаваме изгледа на формата
        $tpl = $data->form->renderHtml();
        
        // Опаковаме изгледа
        $tpl = $this->renderWrapping($tpl);
        
        return $tpl;
    }
    
    
    /**
     *  Извиква се преди вкарване на запис в таблицата на модела
     */
    function on_BeforeSave($mvc, &$id, $rec)
    {
        $rec->data = serialize($rec->settings);
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function on_AfterPrepareListRecs(registry_Settings $mvc, $data)
    {
        $recs = $data->recs;
        $rows = $data->rows;
        
        foreach ($rows as $i=>$row) {
            $rows[$i]->data = ht::createBtn('Настройки ...', array($mvc, 'ManageSettings', $recs[$i]->id));
        }
    }
}