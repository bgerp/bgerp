<?php


/** Досегашният PHPSass остава компилаторът по подразбиране. */
defIfNot('SASS_USE_COMPOSER', 'no');

/** Досегашната Composer версия остава default за избралите scssphp. */
defIfNot('SASS_VERSION', '1.0.4');


/**
 * Конвертиране на SASS файлове към CSS
 *
 * @category  vendors
 * @package   sass
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sass_Setup extends core_ProtoSetup
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
    public $info = 'Конвертиране на SASS файлове към CSS';


    /** Изборът се пази за конкретната инсталация. */
    public $configDescription = array(
        'SASS_USE_COMPOSER' => array('enum(no=PHPSass,yes=scssphp чрез Composer)', 'mandatory, caption=Компилатор->Избор, removeAndRefreshForm=SASS_VERSION'),
        'SASS_VERSION' => array('enum(1.0.4,1.13.0,2.1.0=2.1.0 (PHP 8.1+))', 'mandatory, caption=scssphp->Версия'),
    );


    /** Показва версията само за Composer компилатора, без да губи избора. */
    public function manageConfigDescriptionForm(&$configForm)
    {
        $configForm->input('SASS_USE_COMPOSER', 'silent');
        if (($configForm->rec->SASS_USE_COMPOSER ?? null) !== 'yes') {
            $configForm->setField('SASS_VERSION', 'input=hidden');
        }
    }


    /** Показва липсващата избрана библиотека по стандартния начин. */
    public function checkConfig()
    {
        if (self::get('USE_COMPOSER') === 'yes' && ($error = self::getPhpVersionError(self::get('VERSION')))) {
            return $error;
        }
        if (self::get('USE_COMPOSER') === 'yes'
            && !core_Composer::isInstalled('scssphp/scssphp', self::get('VERSION'), true)) {
            return 'Липсва избраната версия на scssphp. Инициализирайте пакета sass.';
        }
    }


    /** Проверява средата преди зареждане или инсталиране на PHP 8 библиотеката. */
    public static function getPhpVersionError($version)
    {
        if ($version === '2.1.0' && PHP_VERSION_ID < 80100) {
            return 'scssphp 2.1.0 изисква PHP 8.1 или по-нова версия. За PHP 7.4 изберете 1.13.0.';
        }
    }


    /** Връща стандартна грешка към полето, преди core_Packs да запише избора. */
    public function inputConfigDescriptionForm(&$form)
    {
        if ($form->isSubmitted() && ($form->rec->SASS_USE_COMPOSER ?? null) === 'yes'
            && ($error = self::getPhpVersionError($form->rec->SASS_VERSION ?? null))) {
            $form->setError('SASS_VERSION', $error);
        }
    }
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        if (self::get('USE_COMPOSER') === 'yes' && ($error = self::getPhpVersionError(self::get('VERSION')))) {
            return "<li class='red'>" . $error . '</li>';
        }
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина
        $html .= $Plugins->forcePlugin('SASS файлове към CSS', 'sass_Plugin', 'core_Sbf', 'private');
        
        // Composer е нужен само ако е избран този компилатор.
        if (self::get('USE_COMPOSER') === 'yes') {
            $html .= core_Composer::install('scssphp/scssphp', self::get('VERSION'), true);
        }

        return $html;
    }
}
