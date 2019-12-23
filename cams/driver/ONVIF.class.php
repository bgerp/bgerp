<?php


/**
 * Драйвер за IP камера поддържаща ONVIF
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
class cams_driver_ONVIF
{
    /**
     * Инициализиране на обекта
     */
    public function init($params = array())
    {
        parent::init($params);
        
        setIfNot($this->user, 'user');
        setIfNot($this->password, 'Admin555');
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
        $form->FNC('user', 'varchar(64)', 'caption=Потребител,hint=Въведете ONVIF потребител на камерата,input');
        $form->FNC('password', 'password(show)', 'caption=Парола,hint=Въведете паролата ,input');
        $form->FNC('running', 'enum(yes=Активно,no=Спряно)', 'caption=Състояние,hint=Дали камерата да се наблюдава?,input');
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
    }
}
