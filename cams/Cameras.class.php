<?php

/**
 * Мениджър на камери за видеонаблюдение
 *
 * @category   Experta Framework
 * @package    cams
 * @copyright  2006-2010 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id$
 * @since      v 0.1
 */
class cams_Cameras extends core_Master
{
    /**
     *  Зареждане на използваните мениджъри
     */
    var $loadList = 'plg_Created, cams_plg_RecordState, plg_RowTools, cams_Wrapper, plg_State2';
    
    
    /**
     *  Титла
     */
    var $title = 'Камери за видеонаблюдение';
    
    
    /**
     *  Полетата, които ще се ползват
     */
    var $listFields   = 'id, thumb=Изглед, caption=Камера, state';
    var $singleFields = 'id, liveImg, title';
    
    /**
     * Права за писане
     */
    var $canWrite = 'cams, admin';
    
    
    /**
     * Права за четене
     */
    var $canRead = 'cams, admin';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('title', 'varchar(255)', 'caption=Име, mandatory');
        $this->FLD('params', 'text', 'caption=Параметри,input=none');
        $this->FLD('location', 'key(mvc=common_Locations,select=title)', 'caption=Локация');
        $this->FLD('driver', 'class(interface=cams_DriverIntf)', 'caption=Драйвер,mandatory');
    }
    
    
    /**
     * Показва текуща снимка от камера
     *
     * Параметри от Request:
     * id - номер на камерата
     * maxWidth - максимална широчина на снимката
     * maxHeight - максинална височина на снимката
     */
    function act_ShowImage()
    {
        $id = Request::get('id', 'int');
        
        expect($rec = $this->fetch($id));
        
        $driver = cls::getInterface('cams_DriverIntf', $rec->driver, $rec->params);
        
        $img = $driver->getPicture();
        
        if(!$img) {
            $img = imagecreatefromjpeg(dirname(__FILE__) . '/img/novideo.jpg');
        }
        
        if(Request::get('thumb')) {
            $img = thumbnail_Thumbnail::resample($img, array(64));
        }
        
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        
        // Долния ред предизвиква memory-leaks на Google Chrome
        //header("Cache-Control: no-store, no-cache, must-revalidate");
        
        header("Cache-Control: post-check=0, pre-check=0", FALSE);
        header("Pragma: no-cache");
        // Set the content type header - in this case image/jpeg
        header('Content-type: image/jpeg');
        
        // Output the image
        imagejpeg($img);
        
        die;
    }
    
    
    /**
     * Изпълнява се след преобразуването към вербален ред
     */
    function on_AfterRecToVerbal($mvc, $row, $rec, $fields)
    {
        $row->location = $mvc->getVerbal($rec, 'location');
        $row->driver = $mvc->getVerbal($rec, 'driver');
        $row->title = $mvc->getVerbal($rec, 'title');
  
        $driver = cls::getInterface('cams_DriverIntf', $rec->driver, $rec->params);

        if(isset($fields['thumb'])) {
            if($driver->isActive()) {
                $attr['src'] = toUrl(array($this, 'ShowImage', $rec->id, 'thumb' => 'yes'));
                $attr['class'] = 'camera-tumb';
                $row->thumb = ht::createLink(ht::createElement('img', $attr), array($this, 'Single', $rec->id)); ;
            } else {
                $attr['src'] = sbf('cams/img/novideo.jpg', '');
                $row->thumb = ht::createElement('img', $attr);
            }
        }


        $attr = array();
        $url = toUrl(array($this, 'ShowImage', $rec->id));
        $attr['src'] = $url;
        $attr['width']  = $driver->getWidth();
        $attr['height'] = $driver->getHeight();
        $attr['id'] = 'monitor';
        
        $row->liveImg = ht::createElement('img', $attr);
        
        $row->liveImg->appendOnce("
                  flagLoad = 0;
                  function reloadImage(url){
                      img = document.getElementById('monitor');
                      if(img && (flagLoad-- <= 0) ) {
                          img.src = url + '?rnd=' + Math.floor(Math.random()*10000000001);
                          img.onload = setFlagLoad;
                          flagLoad = 11;
                      }
                      setTimeout('reloadImage(\"' +url + '\")',500);
                  }

                  function setFlagLoad()
                  { 
                    flagLoad = 0;
                  }
                  ", 'SCRIPTS');
        
        $row->liveImg->appendOnce("setTimeout('reloadImage(\'{$url}\')', 2000);", 'ON_LOAD');
        
        $row->title = "<b>{$row->title} - {$row->location}</b>";
        $row->caption = new ET($row->title . "<br>");
        $row->caption->append("<small style='font-size:0.8em'><i>{$row->driver}</i></small>&nbsp;");
        $row->caption->append( ht::createLink("<img width=16 height=16 src=" . sbf('img/16/testing.png') . ">", array($mvc, 'Settings', $rec->id) ));
        
        if($driver->havePtzControl()) {
            $form = cls::get('core_form');
            $form->setAction(array($this, 'applayPtzCmd'));
            $form->setHidden('id', $rec->id);
            $driver->preparePtzForm($form);
            
            $row->remoteControl = $form->renderHtml();
            
            $row->remoteControl->append("<IFRAME NAME='rcFrame' WIDTH='0' onload=\"if (!this.src){this.height='0'; this.width='0';}\" HIEGHT='0' FRAMEBORDER='0' style='visibility: hidden'></IFRAME>");
        }
    }
    
    
    /**
     * Изпълнява се преди подготовката на титлата за единичния изглед
     */
    function on_AfterPrepareSingleTitle($mvc, $data)
    {
        Mode::setPermanent('monLastUsedCameraId', $data->rec->id);
    }
    
    
    /**
     *
     */
    function on_AfterPrepareSingleToolbar($mvc, $res, $data)
    {
        if($mvc->haveRightFor('edit', $data->rec)) {
            $data->toolbar->addBtn('Настройки', array(
                $mvc, 'Settings',
                $data->rec->id,
                'ret_url' => TRUE),
            'settings', array('class' => 'btn-settings'));
        }
    }
    
    
    /**
     * Този екшън изпълнява командата, зададена чрез RemoteControl формата
     */
    function act_ApplayPtzCmd()
    {
        if( !($id = Request::get('id'))) return new Redirect(array($this));
        
        if( !($rec = $this->fetch($id))) return new Redirect(array($this));
        
        $driver = cls::get($rec->driver, $rec->params);
        
        expect($driver->havePtzControl());
        
        $form = cls::get('core_form');
        $driver->preparePtzForm($form);
        
        $cmdArr = $form->input();
        
        $res = $driver->applayPtzCommands($cmdArr);
        
        die;
    }
    
    
    /**
     * Подготвя титлата в единичния изглед
     */
    function prepareSingleTitle_($data)
    {
        $Locations = cls::get('common_Locations');
        
        $data->title = ht::createLink($data->row->title, array($this, 'Single', $data->rec->id));
        
        return $data;
    }
    
    
    /**
     *  Промяна параметрите на камера
     */
    function act_Settings()
    {
        requireRole('admin');
        
        $form = cls::get('core_Form');
        
        expect($id = Request::get('id', 'int'));
        
        expect($rec = $this->fetch($id));
        
        if(strpos($rec->params, '}') ) {
            $params = json_decode($rec->params);
        } else {
            $params = arr::make($rec->params, TRUE);
        }
        
        $driver = cls::getInterface('cams_DriverIntf', $rec->driver);
        
        $retUrl = getRetUrl()?getRetUrl():array($this);
        
        $driver->prepareSettingsForm($form);
        
        $form->toolbar->addSbBtn('Запис', 'save', array('class' => 'btn-save'));
        $form->toolbar->addBtn('Отказ', $retUrl, array('class' => 'btn-cancel'));
        
        $form->input();
        
        if($form->isSubmitted()) {
            
            $driver->validateSettingsForm($form);
            
            if(!$form->gotErrors()) {
                $rec->params = json_encode((array) $form->rec);
                $this->save($rec, 'params');
                
                return new Redirect($retUrl);
            }
        }
        
        $form->title = tr("Настройка на камера") . " \"" . $this->getVerbal($rec, 'title') .
        " - " . $this->getVerbal($rec, 'location') . "\"";
        $form->setDefaults($params);
        
        $tpl = $form->renderHtml();
        
        return $this->renderWrapping($tpl);
    }
    
    
    /**
     * Синхронизира часовниците
     */
    function cron_SetTime()
    {
    }
    
    
    /**
     * Подготвя шаблона за единичния изглед
     */
    function renderSingleLayout_($data)
    {
        
        return new ET("[#SingleToolbar#]<h2>[#SingleTitle#]</h2>[#remoteControl#]<div class='clearfix'></div>[#liveImg#]");
    }
}