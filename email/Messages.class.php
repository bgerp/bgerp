<?php 


/**
 * Максимално време за еднократно фетчване на писма
 */
defIfNot('IMAP_MAX_FETCHING_TIME',  20);

/**
 * Максималната разрешена памет за използване
 */
defIfNot('MAX_ALLOWED_MEMORY', '800M');


/**
 * Входящи писма
 */
class email_Messages extends core_Master
{
	

    /**
     *  Заглавие на таблицата
     */
    var $title = "Входящи писма";
    
    
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
	var $loadList = 'email_Wrapper, plg_Created, doc_DocumentPlg, plg_RowTools, plg_Rejected, plg_State, plg_Printing';
    
	
	/**
	 * Нов темплейт за показване
	 */
	var $singleLayoutFile = 'email/tpl/SingleLayoutMessages.html';
	
	
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/email.png';
       
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('accId', 'key(mvc=email_Accounts,select=eMail)', 'caption=Акаунт');
		$this->FLD("messageId", "varchar", "caption=Съобщение ID");
		$this->FLD("subject", "varchar", "caption=Тема");
		$this->FLD("fromEml", "email", 'caption=От->Е-мейл');
		$this->FLD("fromName", "varchar", 'caption=От->Име');
		$this->FLD("toEml", "email", 'caption=До->Е-мейл');
        $this->FLD("toBox", "email", 'caption=До->Кутия');
		$this->FLD("headers", "text", 'caption=Хедъри');
		$this->FLD("textPart", "richtext", 'caption=Текстова част');
		$this->FLD("spam", "int", 'caption=Спам');
		$this->FLD("lg", "varchar", 'caption=Език');
   		$this->FLD("date", "datetime", 'caption=Дата');
		$this->FLD('hash', 'varchar(32)', 'caption=Keш');
		$this->FLD('country', 'key(mvc=drdata_countries,select=commonName)', 'caption=Държава');
		$this->FLD('fromIp', 'ip', 'caption=IP');
		$this->FLD('files', 'keylist(mvc=fileman_Files,select=name,maxColumns=1)', 'caption=Файлове');		
		$this->FLD('emlFile', 'key(mvc=fileman_Files,select=name)', 'caption=eml файл');
		$this->FLD('htmlFile', 'key(mvc=fileman_Files,select=name)', 'caption=html файл');
		$this->FLD('boxIndex', 'int', 'caption=Индекс');
	
		$this->setDbUnique('hash');
		
	}
	
		
	/**
	 * Взема записите от пощенската кутия и ги вкарва в модела
	 *
	 * @param number $oneMailId - Потребителя, за когото ще се проверяват записите.
	 * Ако е празен, тогава ще се проверяват за всички.
	 * 
	 * @return boolean TRUE
	 */
	function getMailInfo($oneMailId = FALSE)
	{  
		ini_set('memory_limit', MAX_ALLOWED_MEMORY);
		        
		$accQuery = email_Accounts::getQuery();


        while ($accRec = $accQuery->fetch("#state = 'active'")) {
			$imapConn = cls::get('email_Imap', array('host' => $accRec->server,
                                                     'port' => $accRec->port,
                                                     'user' => $accRec->user,
                                                     'pass' => $accRec->password,
                                                     'subHost' => $accRec->subHost,
                                                     'folder' => "INBOX",
                                                     'ssl' => $accRec->ssl));
			
 			// Логването и генериране на съобщение при грешка е винаги в контролерната част
			if ($imapConn->connect() === FALSE) {
                
                $this->log("Не може да се установи връзка с пощенската кутия на <b>\"{$accRec->user} ({$accRec->server})\"</b>. " .
                           "Грешка: " . $imapConn->getLastError());

				$htmlRes .= "\n<li style='color:red'> Възникна грешка при опит да се свържем с пощенската кутия: <b>{$arr['user']}</b></li>";
				
				continue;
			}

 			$htmlRes .= "\n<li> Връзка с пощенската кутия на: <b>\"{$accRec->user} ({$accRec->server})\"</b></li>";
            
            // Получаваме броя на писмата в INBOX папката
			$numMsg = $imapConn->getStatistic('messages');

			// До коя секунда в бъдещето максимално да се теглят писма?
            $maxTime = time() + IMAP_MAX_FETCHING_TIME;

            // даваме достатъчно време за изпълнението на PHP скрипта
			set_time_limit(IMAP_MAX_FETCHING_TIME + 10);
            
            // Правим цикъл по всички съобщения в пощенската кутия
            // Цикълът може да прекъсне, ако надвишим максималното време за сваляне на писма
            for ($i = 1; ($i <= $numMsg) && ($maxTime > time()); $i++) {
                
                if(is_array($testMsgs) && !in_array($i, $testMsgs)) continue;
     
                $mail = new email_Mime();

                $mail->parseAll($imapConn->getEml($i));
                
                $hash = $mail->getHash();
            	
            	if ($this->fetch("#hash = '{$hash}'", 'id')) {
            		$htmlRes .= "\n<li> Skip: $hash</li>";
				
                    continue;
            	} else {
               		$htmlRes .= "\n<li style='color:green'> Get: $hash</li>";
                }

               	$rec = $mail->getEmail();

                // Само за дебъг. Todo - да се махне
                $rec->boxIndex = $i;

               	$rec->accId = $accRec->id;

                $saved = email_Messages::save($rec);
                
                // Добавя грешки, ако са възникнали при парсирането
                if(count($mail->errors)) {
                    foreach($mail->errors as $err) {
                        $this->log($err . " ({$i})", $rec->id);
                    }
                }

               	//TODO Да се премахне коментара
				//$imapConn->delete();
            }
            
            //TODO Да се премахне коментара
			//$imapConn->expunge();
		
			$imapConn->close();
			
		}
		
		return $htmlRes;
	}


    /**
	 * TODO ?
	 * Преобразува containerId в машинен вид
	 */
	function on_AfterRecToVerbal($mvc, &$row, $rec)
	{ 
		$row->containerId = $rec->containerId;
		
        if(!$rec->subject) {
		    $row->subject = '[' . tr('Липсва заглавие') . ']';
        }

		if ($rec->files) {
			$vals = type_Keylist::toArray($rec->files);
			if (count($vals)) {
				$row->files = '';
				foreach ($vals as $keyD) {
					$row->files .= fileman_Files::getSingleLink($keyD);
				}
			}
		}

        if(!$rec->toBox) {
            $row->toBox = $row->toEml;
        }
        
        if($rec->fromIp && $rec->country) {
            $row->fromIp .= " ($row->country)";
        }
        
        if($rec->fromName && (strtolower(trim($rec->fromName)) != strtolower(trim($rec->fromEml)))) {
            $row->fromEml = $row->fromEml . ' (' . trim($row->fromName) . ')';
        }
		
		$row->emlFile = fileman_Files::getSingleLink($rec->emlFile);
		$row->htmlFile = fileman_Files::getSingleLink($rec->htmlFile);
		
		$row->htmlFile = fileman_Files::getSingleLink($rec->htmlFile);
		
		$pattern = '/\s*[0-9a-f_A-F]+.eml\s*/';
		$row->emlFile = preg_replace($pattern, 'EMAIL.eml', $row->emlFile);
		
		$pattern = '/\s*[0-9a-f_A-F]+.html\s*/';
		//$row->htmlFile = preg_replace($pattern, 'EMAIL.html', $row->htmlFile);
		
		$row->files .= $row->emlFile . $row->htmlFile;
		
		
	}
	
	
	/**
     * Да сваля имейлите
     */
    function act_DownloadEmails()
    {		
		$mailInfo = $this->getMailInfo();
		
		return $mailInfo;
    }
    
    
	/**
     * Да сваля имейлите по - крон
     */
    function cron_DownloadEmails()
    {		
		$mailInfo = $this->getMailInfo();
		
		return $mailInfo;
    }
    
	
	/**
     * Изпълнява се след създаването на модела
     */
	function on_AfterSetupMVC($mvc, $res)
    {
    	$res .= "<p><i>Нагласяне на Cron</i></p>";
        
        $rec->systemId = 'DownloadEmails';
        $rec->description = 'Сваля и-мейлите в модела';
        $rec->controller = $this->className;
        $rec->action = 'DownloadEmails';
        $rec->period = 1;
        $rec->offset = 0;
        $rec->delay = 0;
     // $rec->timeLimit = 200;
        
        $Cron = cls::get('core_Cron');
        
        if ($Cron->addOnce($rec)) {
            $res .= "<li><font color='green'>Задаване на крон да сваля имейлите в модела.</font></li>";
        } else {
            $res .= "<li>Отпреди Cron е бил нагласен да сваля имейлите.</li>";
        }
        
        return $res;
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentIntf
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $subject = $this->getVerbal($rec, 'subject');

        if(!trim($subject)) {
            $subject = '[' . tr('Липсва заглавие') . ']';
        }

        $row->title = $subject . " ({$rec->boxIndex})";
        
        if(trim($rec->fromName)) {
            $row->author =  $this->getVerbal($rec, 'fromName');
        } else {
            $row->author = "<small>{$rec->fromEml}</small>";
        }
 
        $row->authorEmail = $rec->fromEml;

        $row->state  = $rec->state;

        return $row;
    }

}