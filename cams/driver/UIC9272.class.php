<?php


/**
 * Драйвер за IP камера UIC - ALC-9272
 *
 *
 * @category  bgerp
 * @package   cams
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cams_driver_UIC9272 extends cams_driver_IpDevice
{
    /**
     * Инициализиране на обекта
     */
    public function init($params = array())
    {
        parent::init($params);
        
        if (!isset($this->id)) {
            $this->id = 1;
        }
        setIfNot($this->width, 640);
        setIfNot($this->height, 480);
        
        setIfNot($this->user, 'root');
        setIfNot($this->password, 'root');
    }
    
    
    /**
     * Подготвя формата за настройки на камерата
     */
    public function prepareSettingsForm($form)
    {
        $form->FNC(
            'ip',
            'ip',
            'caption=IP,hint=Въведете IP адреса на камерата,input, mandatory'
        );
        $form->FNC('codec', 'enum(h264=H.264)', 'caption=Кодек,hint=Кодек на RTSP стрийма,input');
        $form->FNC('width', 'int(min=320,max=1600)', 'caption=Ширина,hint=Хоризонтална резолюция,input');
        $form->FNC('height', 'int(min=240,max=1200)', 'caption=Височина,hint=Вертикална резолюция,input');
        $form->FNC('FPS', 'int(min=1,max=30)', 'caption=Скорост,hint=Скорост на записа (fps),input');
        
        // ALC-9272 codec = h264; ALC-9453 = mpeg4
        $form->FNC('user', 'varchar(64)', 'caption=Потребител,hint=Въведете потребителското име за администратора на камерата,input');
        $form->FNC('password', 'password(show)', 'caption=Парола,hint=Въведете паролата за администратора на камерата,input');
        $form->FNC('ptzControl', 'enum(yes=Има,no=Няма)', 'caption=PTZ контрол,hint=Има ли камерата PTZ контрол?,input');
        $form->FNC('running', 'enum(yes=Активно,no=Спряно)', 'caption=Състояние,hint=Дали камерата да се наблюдава?,input');
        $form->FNC('rtspPort', 'int(min=1,max=65535)', 'caption=Порт->Rtsp,hint=Въведете порта за RTSP потока,input');
        $form->FNC('httpPort', 'int(min=1,max=65535)', 'caption=Порт->Http,hint=Въведете порта за CGI заявките,input');
    }
    
    
    /**
     * Подготвя формата за PTZ контрола
     */
    public function preparePtzForm($form)
    {
        /*	param.cgi
            action=update
            ImageSource.I0.Sensor.Brightness=50 - яркост
            ImageSource.I0.Sensor.Sharpness=50 - острота
            ImageSource.I0.Sensor.Contrast=50 - контраст
            ImageSource.I0.Sensor.Saturation=50 - насищане
        */
        //http://xxx.0.0.xx/param.cgi?action=update&ImageSource.I0.Sensor.Brightness=50&ImageSource.I0.Sensor.Sharpness=50&ImageSource.I0.Sensor.Contrast=50&ImageSource.I0.Sensor.Saturation=50
        
        $form->FNC('move', 'enum(up=Нагоре, upleft=Нагоре и ляво, left=Ляво, downleft=Надолу и ляво, down=Надолу, downright=Надолу и дясно, right=Дясно, upright=Нагоре и дясно, home=Начална позиция)', 'caption=Премести');
        $form->FNC('speed', 'enum(1=1,14=2,28=3,44=4,58=5,72=6,86=7,100=8)', 'caption=Скорост');
        
        $form->showFields = 'move, speed';
        $form->view = 'horizontal';
        $form->toolbar->addSbBtn('Изпълни', 'default', 'target=rcFrame');
    }
    
    
    /**
     * Изпълнява отдалечените команди
     */
    public function applyPtzCommands($cmdArr)
    {
        $cmdArr = (array) $cmdArr;
        
        foreach ($cmdArr as $key => $value) {
            if ($value) {
                if ($key == 'rpan' || $key == 'tilt') {
                    if ($value != '0.0') {
                        $value .= '.0';
                    }
                }
                $params .= '&' . $key . '=' . $value;
            }
        }
        
        $url = $this->getPtzUrl() . '/ptz.cgi?camera=1' . $params;
        
        $res['url'] = $url;
        
        $res['ans'] = core_Url::loadUrl($url);
        
        return $res;
    }
}
