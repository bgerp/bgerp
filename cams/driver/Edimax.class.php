<?php

/**
 * Драйвер за IP камера Edimax
 */
class cams_driver_Edimax extends cams_driver_IpDevice {
    
    /**
     * Интерфайси, поддържани от този мениджър
     */
    var $interfaces = 'cams_DriverIntf';

    /**
     * Записва видео в указания файл с продължителност $duration
     */
    function captureVideo($savePath, $duration)
    {
        $url = $this->getDeviceUrl('rtsp') . "/ipcam.sdp";
        
        debug::log("url = {$url}");
        
        $cmd = dirname (__FILE__) . "/vlcschedule.sh {$url} " .
        "{$savePath} {$duration}  < /dev/null > /dev/null 2>&1 &";
        
        debug::log("cmd = {$cmd}");
        
        $res = exec($cmd, $arrOutput);
        
        return $res;
    }
    
    
    /**
     * Записва снимка от камерата в указания файл;
     */
    function getPicture()
    {
        if(!$this->isActive()) {
            $img = imagecreatefromjpeg(dirname(__FILE__) . '/setup.jpg');
        } else {
            $url = $this->getDeviceUrl('http') . "/snapshot.jpg";
            
            $context = stream_context_create(array('http' => array('timeout' => 3)));
            
            $img = @file_get_Contents($url, 0, $context);
            
            if(!empty($img)) {
                $img = imagecreatefromstring($img);
            }
            
            if(!$img) {
                
                $img = imagecreatefromjpeg(dirname(__FILE__) . '/nocamera.jpg');
            }
        }
        
        return $img;
    }
    
    
    /**
     * Вземане на картинка от MotionJped
     */
    function getImageFromMjpeg($url)
    {
        $f = fopen($url, "r");
        
        if(!$f) return FALSE;
        
        while ( (substr_count(strtolower($r),"content-type") != 2 ) && strlen($r) < 200000 ) $r .= fread($f,512);
        
        if(!$r || (strlen($r) >= 200000)) return FALSE;
        
        $boundary="\r\n--";
        
        $soi = chr(0xff).chr(0xd8);
        $soi = strpos($r, $soi );
        
        if(!$soi) return FALSE;
        
        $end = strpos($r, $boundary, $soi) ;
        
        if(!$end) return FALSE;
        
        $frame = substr("$r", $soi, $end - $soi);
        
        $eoi = chr(0xff).chr(0xd9);
        $eoi = strrpos($frame, $eoi);
        
        if(!$eoi) return FALSE;
        
        $frame = substr($frame, 0, $eoi+2);
        
        fclose($f);
        
        return $frame;
    }
    
    
    /**
     * Ресетва състоянието на камерата
     */
    function reset()
    {
        $a=1;
    }
    
    
    /**
     * Подготвя формата за настройки на камерата
     */
    function prepareSettingsForm($form)
    {
        $form->FNC('ip', new type_Varchar(array( 'size' => 16, 'regexp' => '^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}(/[0-9]{1,2}){0,1}$')),
        'caption=IP,hint=Въведете IP адреса на камерата,input, mandatory');
        $form->FNC('user', 'varchar(64)', 'caption=Потребител,hint=Въведете потребителското име за администратора на камерата,input');
        $form->FNC('password', 'password(64)', 'caption=Парола,hint=Въведете паролата за администратора на камерата,input');
        $form->FNC('ptzControl', 'enum(yes=Има,no=Няма)', 'caption=PTZ контрол,hint=Има ли камерата PTZ контрол?,input');
        $form->FNC('running', 'enum(yes=Активно,no=Спряно)', 'caption=Състояние,hint=Дали камерата да се наблюдава?,input');
        $form->FNC('rtspPort', 'int(min=1,max=65535)', 'caption=Порт->Rtsp,hint=Въведете порта за Mpeg4 потока,input');
        $form->FNC('httpPort', 'int(min=1,max=65535)', 'caption=Порт->Http,hint=Въведете порта за CGI заявките,input');
    }
    
    
    /**
     * Проверява дали данните във формата са въведени правилно
     */
    function validateSettingsForm($form)
    {
        return;
    }
    
    
    /**
     * Проверява дали състоянието е активно
     */
    function isActive()
    {
        return $this->running == 'yes';
    }
    
    
    /**
     * Дали има отдалечено управление?
     */
    function havePtzControl()
    {
        return $this->ptzControl == 'yes';
    }
    
    
    /**
     *  Инициализиране на обекта
     */
    function init($params)
    {
        
        parent::init($params);
        
        setIfNot($this->width, 640);
        setIfNot($this->height, 480);
        
        setIfNot($this->user, 'admin');
        
        if(!$this->password) unset($this->password);
        
        setIfNot($this->password, '1234');
        
        setIfNot($this->mpeg4Port, 554);
        
        setIfNot($this->mjpegPort, 80);
    }
    
    
    /**
     * Подготвя формата за PTZ контрола
     */
    function preparePtzForm($form)
    {
        $form->FNC('rpan', 'enum(0,-45,-30,-15,-10,-5,-1,0.0,1,5,10,15,30,45)', 'caption=Pan');
        $form->FNC('tilt', 'enum(0,3,9,12,15,18,21,24,27,30,35,40,45,50,55,60,65,70,75,80,85,90)', 'caption=Tilt');
        $form->FNC('rzoom', 'enum(0,-5,-4,-3,-1,1,2,3,4,5)', 'caption=Zoom');
        
        $form->showFields = 'rpan,tilt,rzoom';
        $form->view = 'horizontal';
        $form->toolbar->addSbBtn('Изпълни', 'default', 'target=rcFrame');
    }
    
    
    /**
     * Изпълнява отдалечените команди
     */
    function applayPtzCommands($cmdArr)
    {
    
    }
}