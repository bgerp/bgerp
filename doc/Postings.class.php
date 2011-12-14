<?php 
/**
 * Ръчен постинг в документната система
 * 
 * @category   bgerp
 * @package    doc
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @since      v 0.1
 *
 *
 */
class doc_Postings extends core_Master
{
    /**
     * Поддържани интерфейси
     */
	var $interfaces = 'doc_DocumentIntf, email_DocumentIntf';
	

    /**
     *  Заглавие на таблицата
     */
    var $title = "Постинг";
    
    
    /**
     * Права
     */
    var $canRead = 'admin, email';
    
    
    /**
     *  
     */
    var $canEdit = 'no_one';
    
    
    /**
     *  
     */
    var $canAdd = 'admin, email';
    
    
    /**
     *  
     */
    var $canView = 'admin, email';
    
    
    /**
     *  
     */
    var $canList = 'admin, email';
    
    
    /**
     *  
     */
    var $canDelete = 'no_one';
    
	
	/**
	 * 
	 */
	var $canEmail = 'admin, email';
	
    
    /**
     * 
     */
	var $loadList = 'email_Wrapper, plg_Created, plg_Modified, doc_DocumentPlg, plg_RowTools, 
		plg_Rejected, plg_State, plg_Printing, email_plg_Document';
    
	
	/**
	 * Нов темплейт за показване
	 */
	var $singleLayoutFile = 'doc/tpl/SingleLayoutPostings.html';
	
	
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/email.png';
       
	

	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('subject', 'varchar', 'caption=Относно');
		$this->FLD('recipient', 'varchar', 'caption=До');
		$this->FLD('attentionOf', 'varchar', 'caption=На вниманието на');
		$this->FLD('refNo', 'varchar', 'caption=Реф. №');
		$this->FLD('email', 'email', 'caption=Емайл');
		$this->FLD('phone', 'varchar', 'caption=Тел.');
		$this->FLD('fax', 'varchar', 'caption=Факс');
		$this->FLD('address', 'text', 'caption=Адрес');
		$this->FLD('body', 'richtext', 'caption=Съобщение');
	}
	
	
	function on_AfterInputEditForm($mvc, $form)
	{
		if ($form->isSubmitted()) {
			$form->rec->folderId = doc_Threads::fetchField($form->rec->threadId, 'folderId');
		}
	}
	
	
	function on_AfterRenderSingleLayout($mvc, $tpl)
	{
		if (Mode::is('text', 'plain')) {
			$tpl = new ET(file_get_contents(getFullPath('doc/tpl/SingleLayoutPostings.txt')));
		} else {
			$tpl = new ET(file_get_contents(getFullPath('doc/tpl/SingleLayoutPostings.html')));
		}
		
		$tpl->replace(static::getBodyTpl(), 'DOC_BODY');
	}
	
	
	/**
	 * Шаблон за тялото на съобщение в документната система.
	 * 
	 * Използва се в този клас, както и в blast_Emails
	 *
	 * @return ET
	 */
	static function getBodyTpl()
	{
		if (Mode::is('text', 'plain')) {
			$tpl = new ET(file_get_contents(getFullPath('doc/tpl/SingleLayoutPostingsBody.txt')));
		} else {
			$tpl = new ET(file_get_contents(getFullPath('doc/tpl/SingleLayoutPostingsBody.html')));
		}
		
		return $tpl;
	}
	
		
    /******************************************************************************************
     *
     * ИМПЛЕМЕНТАЦИЯ НА email_DocumentIntf
     * 
     ******************************************************************************************/

    /**
	 * Текстов вид (plain text) на документ при изпращането му по имейл 
	 *
	 * @param int $id ид на документ
	 * @param string $emailTo
	 * @param string $boxFrom
	 * @return string plain text
	 */
	public function getEmailText($id, $emailTo = NULL, $boxFrom = NULL)
	{
		return static::fetchField($id, 'body');
	}
	
	
	/**
	 * Прикачените към документ файлове
	 *
	 * @param int $id ид на документ
	 * @return array 
	 */
	public function getEmailAttachments($id)
	{
		/**
		 * @TODO
		 */
		return array();
	}
	
	/**
	 * Какъв да е събджекта на писмото по подразбиране
	 *
	 * @param int $id ид на документ
	 * @param string $emailTo
	 * @param string $boxFrom
	 * @return string
	 * 
	 * @TODO това ще е полето subject на doc_Posting, когато то бъде добавено.
	 */
	public function getDefaultSubject($id, $emailTo = NULL, $boxFrom = NULL)
	{
		return static::fetchField($id, 'subject');
	}
	
	
	/**
	 * До кой е-мейл или списък с е-мейли трябва да се изпрати писмото
	 *
	 * @param int $id ид на документ
	 */
	public function getDefaultEmailTo($id)
	{
		return static::fetchField($id, 'email');
	}
	
	
	/**
	 * Адреса на изпращач по подразбиране за документите от този тип.
	 *
	 * @param int $id ид на документ
	 * @return int key(mvc=email_Inboxes) пощенска кутия от нашата система
	 */
	public function getDefaultBoxFrom($id)
	{
		// Няма смислена стойност по подразбиране
		return NULL;
	}
	
	
	/**
	 * Писмото (ако има такова), в отговор на което е направен този постинг
	 *
	 * @param int $id ид на документ
	 * @return int key(email_Messages) NULL ако документа не е изпратен като отговор 
	 */
	public function getInReplayTo($id)
	{
		/**
		 * @TODO
		 */
		return NULL;
	}
	
	
	/**
	 ******************************************************************************************
     *
     * ИМПЛЕМЕНТАЦИЯ НА @link doc_DocumentIntf
     * 
     ******************************************************************************************
     */

	public function getHandle($id) {
		return sprintf('PST%010d', $id); 
	}


    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
//        $subject = $this->getVerbal($rec, 'subject');
//
//        if(!trim($subject)) {
//            $subject = '[' . tr('Липсва заглавие') . ']';
//        }

//        $row->title = $subject;
        
        $row->author =  $this->getVerbal($rec, 'createdBy');
 
//        $row->authorEmail = $rec->fromEml;

        $row->state  = $rec->state;
        
        return $row;
    }
    
}
