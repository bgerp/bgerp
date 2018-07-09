<?php 

/**
 * Циркулярни етикети
 *
 *
 * @category  bgerp
 * @package   blast
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class blast_Labels extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Етикети';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, blast';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, blast';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, blast';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo, blast';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, blast';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, blast';
    
    
    /**
     * Кой може да праща информационните съобщения?
     */
    public $canBlast = 'ceo, blast';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'blast_Wrapper';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
    }
    
    
    /**
     * Екшън по подразбиране.
     * Извежда картинка, че страницата е в процес на разработка
     */
    public function act_Default()
    {
        requireRole('blast, ceo');
        
        $text = tr('В процес на разработка');
        $underConstructionImg = "<h2>${text}</h2><img src=" . sbf('img/under_construction.png') . '>';
        
        return $this->renderWrapping($underConstructionImg);
    }
}
