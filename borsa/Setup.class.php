<?php


/**
 * Информация, която да се добави във формата за добавяне на оферта
 */
defIfNot('BORSA_ADD_BID_INFO', 'Трябва да платите до 3 дена след офериране. В противен случай, офертата може да не бъде одобрена.');


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
}
