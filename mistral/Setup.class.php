<?php


/**
 * Ключ за връзка с mitral.ai
 */
defIfNot('MISTRAL_API_KEY', '');


/**
 * Ключ за връзка с mitral.ai
 */
defIfNot('MISTRAL_API_URL', 'https://api.mistral.ai/v1/ocr');


/**
 * Моделът, който ще се използва за OCR
 */
defIfNot('MISTRAL_OCR_MODEL', 'mistral-ocr-latest');


/**
 * Дали прикачените файлове да се подават в base64 или не
 */
defIfNot('MISTRAL_OCR_USE_BASE_64_ON_ATTACHED_IMAGES', 'no');


/**
 *
 *
 * @category  vendors
 * @package   mistral
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class mistral_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = '';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Адаптер за mistal.ai - разпознаване на текст в сканирани документи';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'mistral_Converter'
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'MISTRAL_API_KEY' => array('password', 'caption=API ключ, mandatory'),
        'MISTRAL_API_URL' => array('Url', 'caption=API URL за OCR->URL'),
        'MISTRAL_OCR_MODEL' => array('varchar(64)', 'caption=OCR модел'),
        'MISTRAL_OCR_USE_BASE_64_ON_ATTACHED_IMAGES' => array('enum(no=Не,yes=Да)', 'caption=Дали прикачените файлове да се подават в Base64->Избор'),
    );

    
    /**
     * Проверява дали програмата е инсталирана в сървъра
     *
     * @return NULL|string
     */
    public function checkConfig()
    {
        if (!mistral_Setup::get('API_KEY')) {

            return "Не е зададен API ключ за връзка";
        }
    }
}
