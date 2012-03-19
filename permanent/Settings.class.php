<?php



/**
 * Клас за перманентни данни
 *
 *
 * @category  all
 * @package   permanent
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Перманентни данни
 */
class permanent_Settings extends core_Manager
{
    
    
    /**
     * Извлича перманентните сетинги и ги сетва на обекта.
     * @param object $object
     */
    function setObject($object)
    {
        $data = permanent_Data::read($object->getSettingsKey());
        $object->setSettings($data);
        
        return $object;
    }
    
    
    /**
     * Изтрива перманентните сетинги за обекта.
     * Извиква се при изтриване на обект ползващ permanent_Data
     * @param object $object
     */
    function purge($object)
    {
        $key = $object->getSettingsKey();
        permanent_Data::remove($key);
    }
    
    
    /**
     * Връща URL - входна точка за настройка на данните за този обект.
     * Ключа в URL-то да бъде декориран с кодировка така,
     * че да е валиден само за текущата сесия на потребителя.
     * @param object $object
     */
    function getUrl($object)
    {
        return $object->getUrl();
    }
    
    
    /**
     * Връща линк с подходяща картинка към входната точка за настройка на данните за този обект
     * @param object $object
     */
    function getLink($object)
    {
        return $object->getLink();
    }
    
    
    /**
     * Екшън за настройка на произволен обект с перманентни данни
     * Обекта се определя от входните параметри $objCls и $objId
     * Екшън-а използва 'опаковката' която е подадена като параметър 'wrapping'
     * Изброените по-горе параметри са защитени срещу чуждо вмешателство.
     */
    function act_Ajust()
    {
        
        expect($objCls   = Request::get('objCls'));
        expect($objId    = Request::get('objId'));
        expect($wrapper  = Request::get('wrapper'));
        
        $form = cls::get('core_Form');
        
        $retUrl = getRetUrl();
        
        $obj = cls::get($objCls,  array('id' => $objId));
        
        $obj->prepareSettingsForm($form);
        $form->setHidden(array('objCls' => $objCls,  'objId' => $objId, 'wrapper' => $wrapper));
        Request::setProtected('objCls,objId,wrapper');
        
        $form->toolbar->addSbBtn('Запис', 'save', array('class' => 'btn-save'));
        $form->toolbar->addBtn('Отказ', $retUrl, array('class' => 'btn-cancel'));
        
        $form->input();
        
        if($form->isSubmitted()) {
            permanent_Data::write($obj->getSettingsKey(), $form->rec);
            
            return new Redirect($retUrl);
        }
        
        $form->title = "Настройка на|* \"" . $obj->getTitle() . "\"";
        $form->setDefaults($obj->getSettings());
        
        $tpl = $form->renderHtml();
        
        $wrapper = cls::get($wrapper);
        
        return $wrapper->renderWrapping($tpl);
    }
}