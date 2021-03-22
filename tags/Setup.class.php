<?php
/**
 *
 *
 * @category  bgerp
 * @package   tags
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class tags_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';

    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'tags_Tags';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Тагове за документи';


    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(1.9899, 'Документи', 'Маркери', 'tags_Tags', 'default', 'ceo,admin'),
    );

    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'tags_Tags',
        'tags_Logs',
        'tags_LinkedTags',
    );
}
