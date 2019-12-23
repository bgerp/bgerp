<?php


/**
 * Драйвер за IP камера Edimax IC-9000
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
class cams_driver_EdimaxIC9000 extends cams_driver_IpDevice
{
    /**
     * Инициализиране на обекта
     */
    public function init($params = array())
    {
        parent::init($params);
        
        setIfNot($this->width, 640);
        setIfNot($this->height, 480);
        
        setIfNot($this->user, 'admin');
        setIfNot($this->password, '1234');
        
        setIfNot($this->rtspPort, 554);
        
        setIfNot($this->httpPort, 80);
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
        $form->FNC('codec', 'enum(mpeg4=MPEG-4)', 'caption=Кодек,hint=Кодек на RTSP стрийма,input');
        $form->FNC('width', 'int(min=320,max=1280)', 'caption=Ширина,hint=Хоризонтална резолюция,input');
        $form->FNC('height', 'int(min=240,max=1024)', 'caption=Височина,hint=Вертикална резолюция,input');
        $form->FNC('FPS', 'int(min=1,max=30)', 'caption=Скорост,hint=Скорост на записа (fps),input');
        $form->FNC('user', 'varchar(64)', 'caption=Потребител,hint=Въведете потребителското име за администратора на камерата,input');
        $form->FNC('password', 'password(64,autocomplete=off)', 'caption=Парола,hint=Въведете паролата за администратора на камерата,input');
        $form->FNC('cameraId', 'varchar(64)', 'caption=ID на камерата,hint=уникалния и номер от WEB панела,input');
        $form->FNC('videopass', 'password(show)', 'caption=Парола за видеото,hint=парола за видеото от WEB панела,input');
        $form->FNC('running', 'enum(yes=Активно,no=Спряно)', 'caption=Състояние,hint=Дали камерата да се наблюдава?,input');
        $form->FNC('rtspPort', 'int(min=1,max=65535)', 'caption=Порт->Rtsp,hint=Въведете порта за Mpeg4 потока,input');
        $form->FNC('httpPort', 'int(min=1,max=65535)', 'caption=Порт->Http,hint=Въведете порта за CGI заявките,input');
    }
    
    
    /**
     * Записва снимка от камерата в указания файл - предефинира ф-та от родителя щото тая камера е особена
     */
    public function getPicture()
    {
        if (!$this->isActive()) {
            $img = imagecreatefromjpeg(dirname(__FILE__) . '/setup.jpg');
        } else {
            $url = $this->getPictureUrl();
            
            // Преди да вземем картинката трябва да фечнем линка само, че с cgi отзад вместо с jpg
            $urlCgi = str_replace('.jpg', '.cgi', $url);
            core_Url::loadUrl($urlCgi);
            
            $img = core_Url::loadUrl($url);
            
            if (!empty($img)) {
                $img = imagecreatefromstring($img);
            }
            
            if (!$img) {
                $img = imagecreatefromjpeg(dirname(__FILE__) . '/nocamera.jpg');
            }
        }
        
        return $img;
    }
    
    
    /**
     * Подготвя формата за PTZ контрола
     */
    public function preparePtzForm($form)
    {
    }
    
    
    /**
     * Изпълнява отдалечените команди
     */
    public function applyPtzCommands($cmdArr)
    {
    }
    
    public function normalizeCameraId()
    {
        $res = str_replace('-', '', $this->cameraId);
        
        return $res;
    }
}
