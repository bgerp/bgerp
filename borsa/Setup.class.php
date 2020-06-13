<?php


/**
 * Информация, която да се добави във формата за добавяне на оферта
 */
defIfNot('BORSA_ADD_BID_INFO', 'Трябва да платите до 3 дена след офериране. В противен случай, офертата може да не бъде одобрена.');


/**
 * 
 */
defIfNot('BORSA_LOT_INFO', 'Форма за заявяване на продукти');


/**
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с продуктите
 *
 *
 * @category  bgerp
 * @package   borsa
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class borsa_Setup extends core_ProtoSetup
{
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'borsa_Lots';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Борса за запазване на стока';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
            array(3.1, 'Търговия', 'Борса', 'borsa_Lots', 'default', 'borsa, ceo')
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
            'BORSA_LOT_INFO' => array('text(rows=2)', 'caption=Текст във формата за заявяване на продукти->Информация, width=100%'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'borsa_Lots',
        'borsa_Periods',
        'borsa_Companies',
        'borsa_Bids',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array(
            array('borsa'),
    );
    
    
    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
            array(
                    'systemId' => 'Borsa Send Blast Emails',
                    'description' => 'Изпращане на циркулярни имейли в борсата',
                    'controller' => 'borsa_Periods',
                    'action' => 'sendBlast',
                    'period' => 1440,
                    'offset' => 570,
                    'timeLimit' => 300
            ),
            
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        $html .= core_Plugins::installPlugin('Добавяне на фирма към борса', 'borsa_Plugin', 'crm_Companies', 'private');
        
        return $html;
    }
}
