<?php



/**
 * Мениджър на камери за видео наблюдение
 *
 *
 * @category  bgerp
 * @package   cams
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cams_Cameras extends core_Master
{
    
    
    /**
     * Зареждане на използваните мениджъри
     */
    var $loadList = 'plg_Created, cams_plg_RecordState, plg_RowTools2, cams_Wrapper, plg_State2';
    
    
    /**
     * Заглавие
     */
    var $title = 'Камери за видео наблюдение';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'thumb=Изглед, caption=Камера, state';
    
    
    /**
     * @todo Чака за документация...
     */
    var $singleFields = 'id, liveImg, title';
    
    
    /**
     * Права за писане
     */
    var $canWrite = 'ceo,cams, admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,admin,cams';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,admin,cams';
    
    
    /**
     * Права за четене
     */
    var $canRead = 'ceo,cams, admin';
    

    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/web_camera.png';


    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('title', 'varchar(255)', 'caption=Име, mandatory');
        $this->FLD('params', 'text', 'caption=Параметри,input=none');
        $this->FLD('driver', 'class(interface=cams_DriverIntf)', 'caption=Драйвер,mandatory');
    }
    
    
    /**
     * Показва текуща снимка от камера
     *
     * Параметри от Request:
     * id - номер на камерата
     * maxWidth - максимална широчина на снимката
     * maxHeight - максимална височина на снимката
     */
    function act_ShowImage()
    {
//    	$this->haveRightFor('single');
        
    	$id = Request::get('id', 'int');
        
        expect($rec = $this->fetch($id));
        
//        $this->haveRightFor('single', $rec);
        
        $driver = cls::getInterface('cams_DriverIntf', $rec->driver, $rec->params);

        $img = $driver->getPicture();
        
        if(!$img) {
            $img = imagecreatefromjpeg(dirname(__FILE__) . '/img/novideo.jpg');
        }
        
        if(Request::get('thumb')) {
            $imgInst = new thumb_Img(array($img, 64, 64, 'gdRes', 'isAbsolute' => FALSE, 'mode' => 'small-no-change'));
            $img = $imgInst->getScaledGdRes();
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
    static function on_AfterRecToVerbal($mvc, &$row, &$rec, $fields)
    {
        $row->driver = $mvc->getVerbal($rec, 'driver');
        $row->title = $mvc->getVerbal($rec, 'title');
        
        $driver = cls::getInterface('cams_DriverIntf', $rec->driver, $rec->params);
        
        if(isset($fields['thumb'])) {
            $attr = array();
            if($driver->isActive()) {
                $attr['src'] = toUrl(array($this, 'ShowImage', $rec->id, 'thumb' => 'yes'));
                $attr['class'] = 'camera-tumb';
                $row->thumb = ht::createLink(ht::createElement('img', $attr), array($this, 'Single', $rec->id));
            } else {
                $attr['src'] = sbf('cams/img/novideo.jpg', '');
                $row->thumb = ht::createElement('img', $attr);
            }
        }
        
        $attr = array();
        $url = toUrl(array($this, 'ShowImage', $rec->id));
        $attr['src'] = $url;
        $attr['width'] = $driver->getWidth();
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
        
       	jquery_Jquery::run($row->liveImg, "setTimeout('reloadImage(\'{$url}\')', 2000);", TRUE);
        
        $row->title = "<b>{$row->title}</b>";
        $row->caption = new ET('[#1#]<br>', $row->title);
        $row->caption->append("<small><i>{$row->driver}</i></small>&nbsp;");

        if($mvc->haveRightFor('edit', $rec)) {
            core_RowToolbar::createIfNotExists($row->_rowTools);
            $row->_rowTools->addLink('Настройки', array($mvc, 'Settings', $rec->id, 'ret_url' => TRUE), 'ef_icon=img/16/testing.png');
        }

        if($driver->havePtzControl()) {
            $form = cls::get('core_form');
            $form->setAction(array(cls::get(get_called_class()), 'applyPtzCmd'));
            $form->setHidden('id', $rec->id);
            $driver->preparePtzForm($form);
            
            $row->remoteControl = $form->renderHtml();
            
            $row->remoteControl->append("<IFRAME NAME='rcFrame' WIDTH='0' onload=\"if (!this.src){this.height='0'; this.width='0';}\" HIEGHT='0' FRAMEBORDER='0' style='visibility: hidden'></IFRAME>");
        }
    }
    
    
    /**
     * Изпълнява се преди подготовката на титлата за единичния изглед
     */
    static function on_AfterPrepareSingleTitle($mvc, $data)
    {
        Mode::setPermanent('monLastUsedCameraId', $data->rec->id);
    }
    
    
    /**
     * Добавя бутоните в лентата с инструменти на единичния изглед
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
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
    function act_ApplyPtzCmd()
    {
        if(!($id = Request::get('id', 'int'))) return new Redirect(array($this));
        
        if(!($rec = $this->fetch($id))) return new Redirect(array($this));
        
        $driver = cls::get($rec->driver, $rec->params); 
        
        expect($driver->havePtzControl());
        
        $form = cls::get('core_form');
        $driver->preparePtzForm($form);
        
        $cmdArr = $form->input();
        
        $res = $driver->applyPtzCommands($cmdArr);
        
        die;
    }
    
    
    /**
     * Подготвя титлата в единичния изглед
     */
    function prepareSingleTitle_($data)
    {
        
        $data->title = ht::createLink($data->row->title, array($this, 'Single', $data->rec->id));
        
        return $data;
    }
    
    
    /**
     * Промяна параметрите на камера
     */
    function act_Settings()
    {
        requireRole('ceo,admin');
        
        $form = cls::get('core_Form');
        
        expect($id = Request::get('id', 'int'));
        
        expect($rec = $this->fetch($id));

        $driver = cls::getInterface('cams_DriverIntf', $rec->driver, $rec->params);        

        if(strpos($rec->params, '}')) {
            $params = json_decode($rec->params);
        } else {
            $params = arr::make($rec->params, TRUE);
        }
        $params = $driver->getParamsFromCam($params);
                
        $retUrl = getRetUrl() ? getRetUrl() : array($this);
        
        $driver->prepareSettingsForm($form);
        
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png');
        
        $form->input();
        
        if($form->isSubmitted()) {
            
            $driver->validateSettingsForm($form);
            
            if(!$form->gotErrors()) {
                $rec->params = json_encode((array) $form->rec);
                $this->save($rec, 'params');
                
                return new Redirect($retUrl);
            }
        }
        
        $form->title = "Настройка на камера|* \"" . $this->getVerbal($rec, 'title') . "\"";
        $form->setDefaults($params);
        
        $tpl = $form->renderHtml();
        
        return $this->renderWrapping($tpl);
    }


    /**
     * Подготвя шаблона за единичния изглед
     */
    function renderSingleLayout_(&$data)
    {
        
        return new ET("[#SingleToolbar#]<h2>[#SingleTitle#]</h2>[#remoteControl#]<div class='clearfix'></div>[#liveImg#]");
    }
}