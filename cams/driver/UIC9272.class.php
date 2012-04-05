<?php



/**
 * Драйвер за IP камера UIC - ALC-9272
 *
 *
 * @category  bgerp
 * @package   cams
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cams_driver_UIC9272 extends cams_driver_IpDevice {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'cams_DriverIntf';
    
	/**
     * Записва видео в указания файл с продължителност $duration
     */
    function captureVideo($savePath, $duration)
    {
        $url = $this->getDeviceUrl('rtsp') . "/cam{$this->id}/" . $this->codec;
        
        $cmd = dirname (__FILE__) . "/vlcschedule.sh {$url} " .
        "{$savePath} {$duration} " . $this->vlcCodec["$this->codec"] . " < /dev/null > /dev/null 2>&1 &";
        
        exec($cmd, $arrOutput);
        $res = implode(',', $arrOutput);
        
        return "Команда: " . $cmd . " Резултат: " . $res;
    }
    
    
    /**
     * Записва снимка от камерата в указания файл;
     */
    function getPicture()
    {
        if(!$this->isActive()) {
            $img = imagecreatefromjpeg(dirname(__FILE__) . '/setup.jpg');
        } else {
            $url = $this->getDeviceUrl('http') . "/image.cgi";
            $img = @core_Url::loadUrl($url);
            
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
     * Нулиране състоянието на камерата
     */
    function reset()
    {
        $a = 1;
    }
    
    
    /**
     * Подготвя формата за настройки на камерата
     */
    function prepareSettingsForm($form)
    {
        $form->FNC('ip', new type_Varchar(array('size' => 16, 'regexp' => '^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}(/[0-9]{1,2}){0,1}$')),
            'caption=IP,hint=Въведете IP адреса на камерата,input, mandatory');
        $form->FNC('codec', 'enum(h264=H.264)', 'caption=Кодек,hint=Кодек на RTSP стрийма,input');
        // ALC-9272 codec = h264; ALC-9453 = mpeg4
        $form->FNC('user', 'varchar(64)', 'caption=Потребител,hint=Въведете потребителското име за администратора на камерата,input');
        $form->FNC('password', 'password(64)', 'caption=Парола,hint=Въведете паролата за администратора на камерата,input');
        $form->FNC('ptzControl', 'enum(yes=Има,no=Няма)', 'caption=PTZ контрол,hint=Има ли камерата PTZ контрол?,input');
        $form->FNC('running', 'enum(yes=Активно,no=Спряно)', 'caption=Състояние,hint=Дали камерата да се наблюдава?,input');
        $form->FNC('rtspPort', 'int(min=1,max=65535)', 'caption=Порт->Rtsp,hint=Въведете порта за RTSP потока,input');
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
     * Инициализиране на обекта
     */
    function init($params = array())
    {
        parent::init($params);
        
        if(!isset($this->id)) {
            $this->id = 1;
        }
        setIfNot($this->width, 720);
        setIfNot($this->height, 600);
        
        setIfNot($this->user, 'root');
        setIfNot($this->password, 'root');
    }
    
    
    /**
     * Дали има отдалечено управление?
     */
    function havePtzControl()
    {
        return $this->ptzControl == 'yes';
    }
    
    
    /**
     * Проверява дали състоянието е активно
     */
    function isActive()
    {
        return $this->running == 'yes';
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
        $cmdArr = (array) $cmdArr;
        
        foreach($cmdArr as $key => $value) {
            if($value) {
                if($key == 'rpan' || $key == 'tilt') {
                    if($value != '0.0') {
                        $value .= '.0';
                    }
                }
                $params .= "&" . $key . "=" . $value;
            }
        }
        
        $url = $this->getDeviceUrl('http') . "/ptz.cgi?camera=1" . $params;
        
        $res['url'] = $url;
        
        $res['ans'] = file_get_contents($url);
        
        return $res;
    }
}