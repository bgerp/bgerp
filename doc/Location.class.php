<?php



/**
 * Описва местоположението на документ в документната система
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_Location
{
    
    
    /**
     * Папка на документа - key(mvc=doc_Folders)
     *
     * @var int
     */
    public $folderId;
    
    
    /**
     * Тред на документа - key(mvc=doc_Threads)
     *
     * @var int
     */
    public $threadId;
    
    
    /**
     * Контекстно зависима стойност, даваща информация за причината документа да бъде рутиран
     * на точно това място
     *
     * @var string
     */
    public $routeRule;
}
