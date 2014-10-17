<?php 


/**
 * Циркулярни SMS-и
 *
 *
 * @category  bgerp
 * @package   blast
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class blast_Sms extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Циркулярни SMS-и";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo, blast';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo, blast';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo, blast';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo, blast';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo, blast';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo, blast';
    
    
    /**
     * Кой може да праша информационните съобщения?
     */
    var $canBlast = 'ceo, blast';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'blast_Wrapper';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    
    }
    
    
    /**
     * Екшън по подразбиране.
     * Извежда картинка, че страницата е в процес на разработка
     */
    function act_Default()
    {
        requireRole('blast, ceo');
        
        $text = tr('В процес на разработка');
        $underConstructionImg = "<h2>$text</h2><img src=" . sbf('img/under_construction.png') . ">";
        
        return $this->renderWrapping($underConstructionImg);
    }
}
