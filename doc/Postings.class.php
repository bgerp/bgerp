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
    var $title = "Постинги";
    
    /**
     *  Заглавие на таблицата
     */
    var $singleTitle = "Коментар";
    
    
    /**
     * Права
     */
    var $canRead = 'admin, email';
    
    
    /**
     *  
     */
    var $canEdit = 'admin, email';
    
    
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
	var $loadList = 'doc_Wrapper, doc_DocumentPlg, plg_RowTools, 
		plg_Printing, email_plg_Document, doc_ActivatePlg';
    
	
	/**
	 * Нов темплейт за показване
	 */
	var $singleLayoutFile = 'doc/tpl/SingleLayoutPostings.html';
	
	
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/doc_text_image.png';
       
	var $currentTab = 'doc_Containers';

	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('subject', 'varchar', 'caption=Относно,mandatory,width=100%');
		$this->FLD('body', 'richtext(rows=10,bucket=Postings)', 'caption=Съобщение,mandatory');
		$this->FLD('recipient', 'varchar', 'caption=Адресант->Фирма');
		$this->FLD('attn', 'varchar', 'caption=Адресант->Лице,oldFieldName=attentionOf');
		$this->FLD('email', 'email', 'caption=Адресант->Имейл');
		$this->FLD('phone', 'varchar', 'caption=Адресант->Тел.');
		$this->FLD('fax', 'varchar', 'caption=Адресант->Факс');
        $this->FLD('country', 'varchar', 'caption=Адресант->Държава');
		$this->FLD('pcode', 'varchar', 'caption=Адресант->П. код');
		$this->FLD('place', 'varchar', 'caption=Адресант->Град/с');
		$this->FLD('address', 'varchar', 'caption=Адресант->Адрес');
        $this->FLD('sharedUsers', 'keylist(mvc=core_Users,select=nick)', 'caption=Споделяне->Потребители');
	}

    
    /**
     *
     */
    function on_AfterPrepareEditForm($mvc, &$data)
    {
        $rec = $data->form->rec;
        
        $emailTo = Request::get('emailto');
        
        //Проверяваме дали е валиден имейл
        if (type_Email::isValidEmail($emailTo)) {
            //Вземаме данните от визитката
            $company = crm_Companies::fetch("#email LIKE '%{$emailTo}%'");
    
            //Ако има запис тогава попълваме данните
            if ($company) {
                $rec->recipient = $company->name;
//                $rec->attn = $company->;
                $rec->email = $company->email;
                $rec->phone = $company->tel;
                $rec->fax = $company->fax;
                $rec->country = crm_Companies::getVerbal($company, 'country');
                $rec->pcode = $company->pCode;
                $rec->place = $company->place;
                $rec->address = $company->address;
            }
        }
        
        if($rec->originId) {
            $oDoc = doc_Containers::getDocument($rec->originId);
            $oRow = $oDoc->getDocumentRow();
            $rec->subject = 'RE: ' . $oRow->title;
        }
    }
    
    
    /**
     * 
     */
    function on_AfterPrepareSingle($mvc, $data)
	{
		if (Mode::is('text', 'plain')) {
			// Форматиране на данните в $data->row за показване в plain text режим
			
			$width = 80;
			$leftLabelWidth = 19;
			$rightLabelWidth = 11;
			$columnWidth = $width / 2;
			
			$row = $data->row;
			
			// Лява колона на антетката
			foreach (array('modifiedOn', 'subject', 'recipient', 'attentionOf', 'refNo') as $f) {
				$row->{$f} = strip_tags($row->{$f});
				$row->{$f} = type_Text::formatTextBlock($row->{$f}, $columnWidth - $leftLabelWidth, $leftLabelWidth);
				
			}
			
			// Дясна колона на антетката
			foreach (array('email', 'phone', 'fax', 'address') as $f) {
				$row->{$f} = strip_tags($row->{$f});
				$row->{$f} = type_Text::formatTextBlock($row->{$f}, $columnWidth - $rightLabelWidth, $columnWidth + $rightLabelWidth);
			}
			
			$row->body = type_Text::formatTextBlock($row->body, $width, 0);
			$row->hr   = str_repeat('-', $width);
		}

        $data->row->iconStyle = 'background-image:url(' . sbf($mvc->singleIcon) . ');';

        if($data->rec->recipient || $data->rec->attn || $data->rec->email) {
            $data->row->headerType = tr('Писмо');
        } elseif($data->rec->originId) {
            $data->row->headerType = tr('Отговор');
        } else {
            $threadRec = doc_Threads::fetch($data->rec->threadId);
            if($threadRec->firstContainerId  == $data->rec->containerId) {
                $data->row->headerType = tr('Съобщение');
            } else {
                $data->row->headerType = tr('Съобщение');
            }
        }
	}
	
	
	/**
	 * 
	 */
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
     *
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->handle = $mvc->getHandle($rec->id);
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

	public function getHandle($id)
    {
		return 'T' . $id; 
	}


    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $subject = $this->getVerbal($rec, 'subject');
 
        $row->title = $subject;
        
        $row->author =  $this->getVerbal($rec, 'createdBy');
 
        $row->authorId = $rec->createdBy;

        $row->state  = $rec->state;
        
        return $row;
    }
    
	
    /**
     * Изпълнява се след създаването на модела
     */
	function on_AfterSetupMVC($mvc, $res)
    {
        //инсталиране на кофата
    	$Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('Postings', 'Прикачени файлове в постингите', NULL, '300 MB', 'user', 'user');
    }
    
    
    /**
     * Преди вкарване на записите в модела
     */
    function on_BeforeSave($mvc, $id, &$rec)
    {
        // Към тялото на писмото добавяме и footer' а
        // $rec->body .= $this->getFooter();
    }
    
    
    /**
     * Добавя футър към постинга
     */
    function getFooter()
    {
        //Зареждаме текущия език
        $lg = core_Lg::getCurrent();
        
        //Зареждаме класа, за да имаме достъп до променливите
        cls::load('crm_Companies');
        
        $companyId = BGERP_OWN_COMPANY_ID;
        
        //Вземаме данните за нашата фирма
        $myCompany = crm_Companies::fetch("#id = '{$companyId}'");

        //Добавяме един празен ред в началото на footer
        $footer = "\r\n\n\r";
        
        //В зависимост от езика генерираме footer' а
        switch ($lg) {
            case 'bg':
                $footer .= "Сърдечни поздрави, \r\n";
                $footer .= "Име: {...}\r\n";
                $footer .= "Фирма: {$myCompany->name}\r\n";
                $footer .= "Тел: {$myCompany->tel}\r\n";
                $footer .= "Факс: {$myCompany->fax}\r\n";
                $footer .= "Имейл: {$myCompany->email}\r\n";
                $footer .= "Web адрес: {$myCompany->website}";
            break;

            default:
                $footer .= "Sincerely, \r\n";
                $footer .= "Name: {...}\r\n";
                $footer .= "Business: {$myCompany->name}\r\n";
                $footer .= "Tel: {$myCompany->tel}\r\n";
                $footer .= "Fax: {$myCompany->fax}\r\n";
                $footer .= "E-mail: {$myCompany->email}\r\n";
                $footer .= "Web site: {$myCompany->website}";
            break;
        }
        
        return $footer;
    }
}
