<?php



/**
 * Прототип на драйвер за IP устройство
 *
 *
 * @category  bgerp
 * @package   cams
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cams_driver_IpDevice extends core_BaseClass {
    
    
    /**
     * IP на устройството
     */
    var $ip;
    
    
    /**
     * id на устройството
     */
    var $id;
    
    
    /**
     * Потребителско име
     */
    var $user;
    
    
    /**
     * Парола за достъп
     */
    var $pass;

    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'cams_DriverIntf';

    
    /**
     * Съответствие между означенията на кодеците в камерите и в VLC плеъра
     */
    var $vlcCodec = array('h264' => 'h264', 'mpeg4' => 'mp4v');
    
    
    /**
     * Начално установяване на параметрите
     */
    public function init($params = array())
    {
        if(strpos($params, '}')) {
            $params = arr::make(json_decode($params));
        } else {
            $params = arr::make($params, TRUE);
        }
        
        parent::init($params);
    }
    
    
    /**
     * Записва снимка от камерата в указания файл;
     */
    public function getPicture()
    {
        if(!$this->isActive()) {
            $img = imagecreatefromjpeg(dirname(__FILE__) . '/setup.jpg');
        } else { 
            $url = $this->getPictureUrl();
            $img = core_Url::loadUrl($url);
            
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
     * Записва видео в указания файл с продължителност $duration
     */
    public function captureVideo($savePath, $duration)
    {

    	$url = $this->getStreamUrl();
        
//        $cmd = dirname (__FILE__) . "/vlcschedule.sh {$url} " .
//        "{$savePath} {$duration} " . $this->vlcCodec["$this->codec"] . " < /dev/null > /dev/null 2>&1 &";

        $cmd = dirname (__FILE__) . "/LIVE555.sh {$url} " .
        "{$savePath} {$duration} {$this->width} {$this->height} {$this->FPS} < /dev/null > /dev/null 2>&1 &";

        exec($cmd, $arrOutput);
        
        if (isDebug()) {
            $res = implode(',', $arrOutput);
        	log_System::add(get_called_class(), "Команда: {$cmd} Резултат: {$res}", NULL, 'debug');
        }
    }
    
    /**
     * Взимаме настройките на камерата за резолюцията и скоростта на записа
     */
    public function getParamsFromCam($params)
    {
    	$url = $this->getParamsUrl();
    	$res = url::loadURL($url);
		
    	$resArr = @parse_ini_string($res);
    	
    	if (!$resArr) {
    		
    		return $params;
    	}
    	
		$className = cls::getClassName($this);
    	
    	switch ($className) {
    		case "cams_driver_UIC":
    		case "cams_driver_UIC9272":
    			$fpsName = "Image.I0.Stream.FPS";
    			$resolutionName = "Image.I0.Appearance.Resolution";
    		break;
    		case "cams_driver_Edimax":
    			$fpsName = "Image.I1.Stream.FPS";
    			$resolutionName = "root.Image.I1.Appearance.Resolution";
    		break;
    	}
    	
    	list($params->width, $params->height) = preg_split("/[x,X]+/", $resArr["{$resolutionName}"]);
    	$params->FPS = $resArr["{$fpsName}"];
    	
    	return($params);
    }
    
    
    /**
     * 
     * Връща урл за взимане на параметри от камерата в зависимост от вида и
     */
	private function getParamsUrl()
	{
		$className = cls::getClassName($this);
    	
    	switch ($className) {
    		case "cams_driver_UIC9272":
    			$suffix = "/param.cgi?action=list&group=Image.I0.Stream,Image.I0.Appearance,Image.I*.Mpegl";
    		break;
    		case "cams_driver_UIC":
    			$suffix = "/param.cgi?action=list&group=Image.I0.Stream,Image.I0.Appearance";
    		break;
    		case "cams_driver_Edimax":
    			$suffix = "/camera-cgi/admin/param.cgi?action=list&group=Image.I1.Appearance,Image.I1.Stream";
    		break;
    	}
		
    	return $this->getDeviceUrl('http') . $suffix;
	}
    
    
    /**
     * 
     * Връща урл за взимане на снимка от камерата в зависимост от вида и
     */
	protected function getPictureUrl()
	{
		$className = cls::getClassName($this);
    	
    	switch ($className) {
    		case "cams_driver_UIC":
    		case "cams_driver_UIC9272":
    			$suffix = "/image.cgi";
    		break;
    		case "cams_driver_Edimax":
    			$suffix = "/snapshot.jpg";
    		break;
    		case "cams_driver_EdimaxIC9000":
    			 $suffix = "/snapshot.jpg";
    		break;
    		case "cams_driver_Hikvision":
    			// Шот по http
    			 $suffix = "/Streaming/channels/1/picture";
    			// Път до файла генериран от RTSP
    			//return EF_TEMP_PATH . "HikvisionShot.jpg";
    		break;
    	}
		
    	return $this->getDeviceUrl('http') . $suffix;
	}

	
    /**
     * 
     * Връща урл към видео стрийма на камерата в зависимост от вида и
     */
	protected function getStreamUrl()
	{
		$className = cls::getClassName($this);
    	
    	switch ($className) {
    		case "cams_driver_UIC":
    		case "cams_driver_UIC9272":
    			$suffix = "/cam{$this->id}/" . $this->codec;
    		break;
    		case "cams_driver_Edimax":
    			$suffix = "/ipcam.sdp"; // за H.264 ->"/ipcam_264.sdp"
    		break;
    		case "cams_driver_EdimaxIC9000":
    			$suffix = "/" . $this->normalizeCameraId() . ".{$this->videopass}";
    		break;
    		case "cams_driver_Hikvision":
    		break;
    	}

    	return $this->getDeviceUrl('rtsp') . $suffix;
	}
	
	
    /**
     * 
     * Връща урл за подаване на PTZ команди към камерата в зависимост от вида и
     */
	protected function getPtzUrl()
	{
		$className = cls::getClassName($this);
    	
    	switch ($className) {
    		case "cams_driver_UIC":
    		case "cams_driver_UIC9272":
    			$suffix = "/ptz.cgi?camera=1";
    		break;
    	}
		
    	return $this->getDeviceUrl('http') . $suffix;
	}

	
	/**
     * 
     * Връща базовото URL към устройството
     */
    private function getDeviceUrl($protocol, $portName = NULL)
    {
    	
        if($this->user) {
            $url = "{$this->user}:{$this->password}@{$this->ip}";
        } else {
            $url = "{$this->ip}";
        }
        
        if(!isset($portName)) {
            $portName = $protocol . "Port";
        }
        
        if($this->{$portName}) {
            $url .= ":" . $this->{$portName};
        }
        
        return $protocol . "://" . $url;
    }

    
    /**
     * Проверява дали данните във формата са въведени правилно
     */
    public function validateSettingsForm($form)
    {
        return;
    }
    
    
    /**
     * Нулиране състоянието на камерата
     */
    public function reset()
    {
        $a = 1;
    }


    /**
     * Дали има отдалечено управление?
     */
    public function havePtzControl()
    {
        return $this->ptzControl == 'yes';
    }
    
    
    /**
     * Проверява дали състоянието е активно
     */
    public function isActive()
    {
        return $this->running == 'yes';
    }
}