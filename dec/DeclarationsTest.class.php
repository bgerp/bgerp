<?php

/**
 * Р”РµРєР»Р°СЂР°С†РёРё Р·Р° СЃСЉРѕС‚РІРµС‚СЃС‚РІРёСЏ
 *
 *
 * @category  bgerp
 * @package   dec
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class dec_Declarations extends core_Master {
	
	/**
	 * Р�РЅС‚РµСЂС„РµР№СЃРё, РїРѕРґРґСЉСЂР¶Р°РЅРё РѕС‚ С‚РѕР·Рё РјРµРЅРёРґР¶СЉСЂ
	 */
	var $interfaces = 'doc_DocumentIntf, email_DocumentIntf';
	
	/**
	 * Р¤Р»Р°Рі, РєРѕР№С‚Рѕ СѓРєР°Р·РІР°, С‡Рµ РґРѕРєСѓРјРµРЅС‚Р° Рµ РїР°СЂС‚РЅСЊРѕСЂСЃРєРё
	 */
	public $visibleForPartners = TRUE;
	
	/**
	 * Р—Р°РіР»Р°РІРёРµ
	 */
	var $title = "Р”РµРєР»Р°СЂР°С†РёРё Р·Р° СЃСЉРѕС‚РІРµС‚СЃС‚РІРёРµ";
	
	/**
	 * Р—Р°РіР»Р°РІРёРµ РІ РµРґРёРЅСЃС‚РІРµРЅРѕ С‡РёСЃР»Рѕ
	 */
	var $singleTitle = "Р”РµРєР»Р°СЂР°С†РёСЏ Р·Р° СЃСЉРѕС‚РІРµС‚СЃС‚РІРёРµ";
	
	/**
	 * Р—Р°РіР»Р°РІРёРµ РЅР° РјРµРЅСЋС‚Рѕ
	 */
	var $pageMenu = "Р”РµРєР»Р°СЂР°С†РёРё";
	
	/**
	 * РџР»СЉРіРёРЅРё Р·Р° Р·Р°СЂРµР¶РґР°РЅРµ
	 */
	var $loadList = 'sales_Wrapper, bgerp_plg_Blank, recently_Plugin, doc_ActivatePlg, plg_Printing, cond_plg_DefaultValues, 
    				 plg_RowTools2, doc_DocumentIntf, doc_DocumentPlg, doc_EmailCreatePlg ,doc_plg_TplManager';
	
	/**
	 * РљРѕР№ РёРјР° РїСЂР°РІРѕ РґР° С‡РµС‚Рµ?
	 */
	var $canRead = 'ceo,dec';
	
	/**
	 * РљРѕР№ РјРѕР¶Рµ РґР° РіРѕ СЂР°Р·РіР»РµР¶РґР°?
	 */
	var $canList = 'ceo,dec';
	
	/**
	 * РљРѕР№ РјРѕР¶Рµ РґР° СЂР°Р·РіР»РµР¶РґР° СЃРёРЅРіСЉР»Р° РЅР° РґРѕРєСѓРјРµРЅС‚РёС‚Рµ?
	 */
	var $canSingle = 'ceo,dec';
	
	/**
	 * РљРѕР№ РјРѕР¶Рµ РґР° РїРёС€Рµ?
	 */
	var $canWrite = 'ceo,dec';
	
	/**
	 * РљРѕРё РїРѕР»РµС‚Р° С‰Рµ РІРёР¶РґР°РјРµ РІ Р»РёСЃС‚РѕРІРёСЏ РёР·РіР»РµРґ
	 */
	var $listFields = 'id, doc, createdOn, createdBy';
	
	/**
	 * РљРѕР№ Рµ С‚РµС‚СѓС‰РёСЏС‚ С‚Р°Р± РѕС‚ РјРµРЅСЋС‚Рѕ
	 * test
	 */
	var $currentTab = 'Р”РµРєР»Р°СЂР°С†РёРё';
	
	/**
	 * РЁР°Р±Р»РѕРЅ Р·Р° РµРґРёРЅРёС‡РЅРёСЏ РёР·РіР»РµРґ
	 */
	var $singleLayoutFile = 'dec/tpl/SingleLayoutDeclarations.shtml';
	
	/**
	 * Р’ РєРѕР№ РїР»РµР№СЃС…РѕР»РґРµСЂ РґР° СЃРµ СЃР»РѕР¶Рё С€Р°Р±Р»РѕРЅР°
	 */
	var $templateFld = 'content';
	
	/**
	 * РђР±СЂРµРІРёР°С‚СѓСЂР°
	 */
	var $abbr = "Dec";
	
	/**
	 * РџРѕР»РµС‚Р° РѕС‚ РєРѕРёС‚Рѕ СЃРµ РіРµРЅРµСЂРёСЂР°С‚ РєР»СЋС‡РѕРІРё РґСѓРјРё Р·Р° С‚СЉСЂСЃРµРЅРµ (@see plg_Search)
	 */
	var $searchFields = 'doc, declaratorName, id';
	
	/**
	 * Р”Р°Р»Рё РІ Р»РёСЃС‚РѕРІРёСЏ РёР·РіР»РµРґ РґР° СЃРµ РїРѕРєР°Р·РІР° Р±СѓС‚РѕРЅР° Р·Р° РґРѕР±Р°РІСЏРЅРµ
	 */
	public $listAddBtn = FALSE;
	
	/**
	 * РЎС‚СЂР°С‚РµРіРёРё Р·Р° РґРµС„РѕР»С‚ СЃС‚РѕР№РЅРѕСЃС‚С‚Рё
	 */
	public static $defaultStrategies = array (
			'statements' => 'lastDocUser|lastDoc|LastDocSameCuntry',
			'materials' => 'lastDocUser|lastDoc|LastDocSameCuntry' 
	);
	
	/**
	 * РћРїРёСЃР°РЅРёРµ РЅР° РјРѕРґРµР»Р°
	 */
	function description() {
		// РЅРѕРјРµСЂР° РЅР° РґРѕРєСѓРјРµРЅС‚Р°
		$this->FLD ( 'doc', 'key(mvc=doc_Containers)', 'caption=РљСЉРј РґРѕРєСѓРјРµРЅС‚, input=none' );
		
		// РґР°С‚Р° РЅР° РґРµРєР»Р°СЂР°С†РёСЏС‚Р°
		$this->FLD ( 'date', 'date', 'caption=Р”Р°С‚Р°' );
		
		// РґРµРєР»Р°СЂР°С‚РѕСЂ
		$this->FLD ( 'declaratorName', 'varchar', 'caption=РџСЂРµРґСЃС‚Р°РІР»СЏРІР°РЅР° РѕС‚->Р�РјРµ, recently, mandatory,remember' );
		
		// РїРѕР·РёС†РёСЏС‚Р° РЅР° РґРµРєР»Р°СЂР°С‚РѕСЂР°
		$this->FLD ( 'declaratorPosition', 'varchar', 'caption=РџСЂРµРґСЃС‚Р°РІР»СЏРІР°РЅР° РѕС‚->РџРѕР·РёС†РёСЏ, recently, mandatory,remember' );
		
		// РґРѕРїСЉР»РЅРёС‚РµР»РЅРё РїРѕСЏСЃРЅРµРЅРёСЏ
		$this->FLD ( 'explanation', 'varchar', 'caption=РџСЂРµРґСЃС‚Р°РІР»СЏРІР°РЅР° РѕС‚->Р”РѕРїСЉР»РЅРёС‚РµР»РЅРѕ, recently, remember' );
		
		// РїСЂРѕРґСѓРєС‚Рё, РёРґРІР°С‚ РѕС‚ С„Р°РєС‚СѓСЂР°С‚Р°
		$this->FLD ( 'productId', 'set', 'caption=РџСЂРѕРґСѓРєС‚Рё->РџСЂРѕРґСѓРєС‚Рё, maxColumns=2' );
		
		$this->FLD ( 'inv', 'int', 'caption=Р¤Р°РєС‚СѓСЂР°, input=none' );
		
		// РЅР° РєР°РєРІРё С‚РІСЉСЂРґРµРЅРёСЏ РѕС‚РіРѕРІР°СЂСЏС‚
		$this->FLD ( 'statements', 'keylist(mvc=dec_Statements,select=title)', 'caption=РўРІСЉСЂРґРµРЅРёСЏ->РћС‚РіРѕРІР°СЂСЏС‚ РЅР°, mandatory,remember' );
		
		// РѕС‚ РєР°РєРІРё РјР°С‚РµСЂРёР°Р»Рё Рµ
		$this->FLD ( 'materials', 'keylist(mvc=dec_Materials,select=title)', 'caption=РњР°С‚РµСЂРёР°Р»Рё->Р�Р·СЂР°Р±РѕС‚РµРЅРё РѕС‚, mandatory,remember' );
		
		// РґРѕРїСЉР»РЅРёС‚РµР»РµРЅ С‚РµРєСЃС‚
		$this->FLD ( 'note', 'richtext(bucket=Notes,rows=6)', 'caption=Р‘РµР»РµР¶РєРё->Р”РѕРїСЉР»РЅРµРЅРёСЏ' );
	}
	
	/**
	 * РЎР»РµРґ РїРѕС‚РіРѕС‚РѕРІРєР° РЅР° С„РѕСЂРјР°С‚Р° Р·Р° РґРѕР±Р°РІСЏРЅРµ / СЂРµРґР°РєС‚РёСЂР°РЅРµ.
	 *
	 * @param core_Mvc $mvc        	
	 * @param stdClass $data        	
	 */
	static function on_AfterPrepareEditForm($mvc, $data) {
		
		// Р—Р°РїРёСЃРІР°РјРµ РѕСЂРёРіРёРЅР°Р»РЅРѕС‚Рѕ РёРґ, Р°РєРѕ РёРјР°РјРµ С‚Р°РєРѕРІР°
		if ($data->form->rec->originId) {
			$data->form->setDefault ( 'doc', $data->form->rec->originId );
			
			// Рё Рµ РїРѕ РґРѕРєСѓРјРµРЅС‚ С„Р°РєС‚СѓСЂР° РЅР°РјРёСЂР°РјРµ РєРѕР№ Рµ С‚РѕР№
			$doc = doc_Containers::getDocument ( $data->form->rec->originId );
			$class = $doc->className;
			$dId = $doc->that;
			$rec = $class::fetch ( $dId );
			
			// РІР·РёРјР°РјРµ РїСЂРѕРґСѓРєС‚РёС‚Рµ РѕС‚ РґРµС‚Р°РёР№Р»Р° РЅР° С„Р°РєС‚СѓСЂР°С‚Р°
			$dQuery = sales_InvoiceDetails::getQuery ();
			$dQuery->where ( "#invoiceId = {$rec->id}" );
			
			while ( $dRec = $dQuery->fetch () ) {
				$productName [$dRec->productId] = cat_Products::getTitleById ( $dRec->productId );
			}
			
			$data->form->setSuggestions ( 'productId', $productName );
			$data->form->setDefault ( 'inv', $rec->id );
		}
		
		// СЃР»Р°РґР°РјРµ РЈРїСЂР°РІРёС‚РµР»Рё
		$hr = cls::get ( 'hr_EmployeeContracts' );
		
		$managers = $mvc->getManagers ();
		
		if (count ( $managers ) > 0) {
			
			$data->form->setSuggestions ( 'declaratorName', $managers );
		}
		
		// Р°РєРѕ РЅРµ Рµ СѓРєР°Р·Р°РЅР° РґР°С‚Р° РІР·РёРјР°РјРµ РґРЅРµС€РЅР°С‚Р°
		if (! $data->form->rec->date) {
			
			$data->form->setDefault ( 'date', dt::now ( FALSE ) );
		}
	}
	
	/**
	 * Р�Р·РІРёРєРІР° СЃРµ СЃР»РµРґ РєРѕРЅРІРµСЂС‚РёСЂР°РЅРµС‚Рѕ РЅР° СЂРµРґР° ($rec) РєСЉРј РІРµСЂР±Р°Р»РЅРё СЃС‚РѕР№РЅРѕСЃС‚Рё ($row)
	 */
	function on_AfterRecToVerbal($mvc, $row, $rec) {
		try {
			$row->doc = doc_Containers::getLinkForSingle ( $rec->doc );
		} catch ( core_exception_Expect $e ) {
			$row->doc = tr ( "РџСЂРѕР±Р»РµРј РїСЂРё РїРѕРєР°Р·РІР°РЅРµС‚Рѕ" );
		}
		
		$rec->tplLang = $mvc->pushTemplateLg ( $rec->template );
		$ownCompanyData = crm_Companies::fetchOwnCompany ();
		
		// Р—Р°СЂРµР¶РґР°РјРµ РґР°РЅРЅРёС‚Рµ Р·Р° СЃРѕР±СЃС‚РІРµРЅР°С‚Р° С„РёСЂРјР°
		$ownCompanyData = crm_Companies::fetchOwnCompany ();
		
		// РђРґСЂРµСЃР° РЅР° С„РёСЂРјР°С‚Р°
		$address = trim ( $ownCompanyData->place . ' ' . $ownCompanyData->pCode );
		if ($address && ! empty ( $ownCompanyData->address )) {
			$address .= ', ' . $ownCompanyData->address;
		}
		
		$Varchar = cls::get ( 'type_Varchar' );
		// РёРјРµС‚Рѕ РЅР° С„РёСЂРјР°С‚Р°
		$row->MyCompany = crm_Companies::getTitleById ( $ownCompanyData->companyId );
		$row->MyCompany = transliterate ( tr ( $row->MyCompany ) );
		
		// РґСЉСЂР¶Р°РІР°С‚Р°
		$fld = ($rec->tplLang == 'bg') ? 'commonNameBg' : 'commonName';
		$row->MyCountry = drdata_Countries::getVerbal ( $ownCompanyData->countryId, $fld );
		
		// Р°РґСЂРµСЃР°
		$row->MyAddress = $Varchar->toVerbal ( $address );
		$row->MyAddress = transliterate ( tr ( $row->MyAddress ) );
		
		// Р’Р°С‚ РЅРѕРјРµСЂР° Р№
		$uic = drdata_Vats::getUicByVatNo ( $ownCompanyData->vatNo );
		if ($uic != $ownCompanyData->vatNo) {
			$row->MyCompanyVatNo = ' ' . $ownCompanyData->vatNo;
		}
		
		if ($uic) {
			$row->uicId = ' ' . $uic;
		}
		
		// РёРЅС„РѕСЂРјР°С†РёСЏ Р·Р° СѓРїСЂР°РІРёС‚РµР»СЏ/РґРµРєР»Р°СЂР°С‚РѕСЂР°
		if ($rec->declaratorName) {
			$row->manager = $rec->declaratorName;
			
			if (is_numeric ( $rec->declaratorName )) {
				if ($declaratorData = crm_Persons::fetch ( $rec->declaratorName )) {
					$row->manager = $declaratorData->name;
					$row->{'managerР•GN'} = $declaratorData->egn;
				}
			}
			
			$row->manager = transliterate ( tr ( $row->manager ) );
			
			$row->declaratorName = transliterate ( tr ( $rec->declaratorName ) );
			
			$row->declaratorPosition = transliterate ( tr ( $rec->declaratorPosition ) );
		}
		
		if ($rec->date == NULL) {
			$row->date = $rec->createdOn;
		} else {
			if (core_Lg::getCurrent () == 'bg') {
				$row->date = dt::mysql2verbal ( $rec->date, "d.m.Y" ) . tr ( "|Рі.|*" );
			} else {
				$row->date = dt::mysql2verbal ( $rec->date, "d.m.Y" );
			}
		}
		
		// РІР·РµРјР°РјРµ РёР·Р±СЂР°РЅРёС‚Рµ РїСЂРѕРґСѓРєС‚Рё
		if ($rec->productId) {
			
			$products = arr::make ( $rec->productId );
			
			$batches = array ();
			$classProduct = array ();
			
			if ($rec->inv) {
				$dQuery = sales_InvoiceDetails::getQuery ();
				$dQuery->where ( "#invoiceId = {$rec->inv}" );
				
				while ( $dRec = $dQuery->fetch () ) {
					$batches [$dRec->productId] = $dRec->batches;
				}
			}
			
			foreach ( $products as $product ) {
				$classProduct [$product] = explode ( "|", $product );
			}
			
			$row->products = "<ol>";
			foreach ( $classProduct as $iProduct => $name ) {
				$pId = (isset ( $name [1] )) ? $name [1] : $name [0];
				$productName = cat_Products::getTitleById ( $pId );
				if (($batches [$pId])) {
					$row->products .= "<li>" . $productName . " - " . $batches [$pId] . "</li>";
				} else {
					$row->products .= "<li>" . $productName . "</li>";
				}
			}
			$row->products .= "</ol>";
		}
		
		// Р°РєРѕ РґРµРєР»Р°СЂР°С†РёСЏС‚Р° Рµ РєСЉРј РґРѕРєСѓРјРµРЅС‚
		if ($rec->originId) {
			// Рё Рµ РїРѕ РґРѕРєСѓРјРµРЅС‚ С„Р°РєС‚СѓСЂР° РЅР°РјРёСЂР°РјРµ РєРѕР№ Рµ С‚РѕР№
			$doc = doc_Containers::getDocument ( $rec->originId );
			
			$class = $doc->className;
			$dId = $doc->that;
			$recOrigin = $class::fetch ( $dId );
			
			// РџРѕРїСЉР»РІР°РјРµ РґР°РЅРЅРёС‚Рµ РѕС‚ РєРѕРЅС‚СЂР°РіРµРЅС‚Р°. Р�РґРІР°С‚ РѕС‚ С„Р°РєС‚СѓСЂР°С‚Р°
			$addressContragent = trim ( $recOrigin->contragentPlace . ' ' . $recOrigin->contragentPCode );
			if ($addressContragent && ! empty ( $recOrigin->contragentAddress )) {
				$addressContragent .= ', ' . $recOrigin->contragentAddress;
			}
			$row->contragentCompany = cls::get ( $recOrigin->contragentClassId )->getTitleById ( $recOrigin->contragentId );
			$row->contragentCompany = transliterate ( tr ( $row->contragentCompany ) );
			
			$fld = ($rec->tplLang == 'bg') ? 'commonNameBg' : 'commonName';
			$row->contragentCountry = drdata_Countries::getVerbal ( $recOrigin->contragentCountryId, $fld );
			
			$row->contragentAddress = $Varchar->toVerbal ( $addressContragent );
			$row->contragentAddress = transliterate ( tr ( $row->contragentAddress ) );
			
			$uicContragent = drdata_Vats::getUicByVatNo ( $rec->contragentVatNo );
			if ($uic != $recOrigin->contragentVatNo) {
				$row->contragentCompanyVatNo = $Varchar->toVerbal ( $rec->contragentVatNo );
			}
			$row->contragentUicId = $uicContragent;
			
			$invoiceNo = str_pad ( $recOrigin->number, '10', '0', STR_PAD_LEFT ) . " / " . dt::mysql2verbal ( $recOrigin->date, "d.m.Y" );
			$row->invoiceNo = $invoiceNo;
		}
		
		// РІР·РµРјР°РјРµ РјР°С‚РµСЂРёР°Р»РёС‚Рµ
		if ($rec->materials) {
			$materials = type_Keylist::toArray ( $rec->materials );
			
			$row->material = "";
			foreach ( $materials as $material ) {
				$m = dec_Materials::fetch ( $material );
				
				$text = $m->text;
				$row->material .= "<li>" . $text . "</li>";
			}
		}
		
		// РІР·РµРјР°РјРµ С‚РІСЉСЂРґРµРЅРёСЏС‚Р°
		if ($rec->statements) {
			
			$statements = type_Keylist::toArray ( $rec->statements );
			
			$row->statements = "";
			foreach ( $statements as $statement ) {
				$s = dec_Statements::fetch ( $statement );
				$text = $s->text;
				$row->statements .= "<li>" . $text . "</li>";
			}
		}
		
		// Р°РєРѕ РёРјР° РґРѕРїСЉР»РЅРёС‚РµР»РЅРё Р±РµР»РµР¶РєРё
		if ($rec->note) {
			$Richtext = cls::get ( 'type_Richtext' );
			$row->note = $Richtext->toVerbal ( $rec->note );
		}
		
		core_Lg::pop ( $rec->tplLang );
	}
	
	/**
	 * РЎР»РµРґ РїСЂРѕРІРµСЂРєР° РЅР° СЂРѕР»РёС‚Рµ
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL) {
		switch ($action) {
			
			case 'activate' :
				if (empty ( $rec->id )) {
					// РЅРµ СЃРµ РґРѕРїСѓСЃРєР° Р°РєС‚РёРІРёСЂР°РЅРµ РЅР° РЅРµР·Р°РїРёСЃР°РЅРё РґРµРєР»Р°СЂР°С†РёРё
					$requiredRoles = 'no_one';
				}
				break;
			case 'add' :
				if (empty ( $rec->originId )) {
					$requiredRoles = 'no_one';
				} else {
					$origin = doc_Containers::getDocument ( $rec->originId );
					
					if (! $origin->isInstanceOf ( 'sales_Invoices' )) {
						$requiredRoles = 'no_one';
					} else {
						$originRec = $origin->rec ();
						if ($originRec->state != 'active' || $originRec->type != 'invoice') {
							$requiredRoles = 'no_one';
						}
					}
				}
		}
	}
	
	/**
	 * Р”РѕР±Р°РІСЏ СЃР»РµРґ С‚Р°Р±Р»РёС†Р°С‚Р°
	 *
	 * @param core_Mvc $mvc        	
	 * @param StdClass $res        	
	 * @param StdClass $data        	
	 */
	static function on_AfterRenderListTable($mvc, &$tpl, $data) {
		$mvc->currentTab = "Р”РµРєР»Р°СЂР°С†РёРё->РЎРїРёСЃСЉРє";
		$mvc->menuPage = "РўСЉСЂРіРѕРІРёСЏ:РџСЂРѕРґР°Р¶Р±Рё";
	}
	
	/**
	 * **************************************************************************************
	 * *
	 * Р�РњРџР›Р•РњР•РќРўРђР¦Р�РЇ РќРђ @link doc_DocumentIntf *
	 * *
	 * **************************************************************************************
	 */
	
	/**
	 * Р�РЅС‚РµСЂС„РµР№СЃРµРЅ РјРµС‚РѕРґ РЅР° doc_DocumentInterface
	 */
	function getDocumentRow($id) {
		$rec = $this->fetch ( $id );
		$row = new stdClass ();
		
		$row->title = $this->singleTitle . " в„–{$id}";
		$row->authorId = $rec->createdBy;
		$row->author = $this->getVerbal ( $rec, 'createdBy' );
		$row->recTitle = $row->title;
		$row->state = $rec->state;
		
		return $row;
	}
	
	/**
	 * РџСЂРѕРІРµСЂРєР° РґР°Р»Рё РЅРѕРІ РґРѕРєСѓРјРµРЅС‚ РјРѕР¶Рµ РґР° Р±СЉРґРµ РґРѕР±Р°РІРµРЅ РІ
	 * РїРѕСЃРѕС‡РµРЅР°С‚Р° РїР°РїРєР° РєР°С‚Рѕ РЅР°С‡Р°Р»Рѕ РЅР° РЅРёС€РєР°
	 *
	 * @param $folderId int
	 *        	РёРґ РЅР° РїР°РїРєР°С‚Р°
	 */
	public static function canAddToFolder($folderId) {
		return FALSE;
	}
	
	/**
	 * Р’СЂСЉС‰Р° С‚СЏР»РѕС‚Рѕ РЅР° РёРјРµР№Р»Р° РіРµРЅРµСЂРёСЂР°РЅ РѕС‚ РґРѕРєСѓРјРµРЅС‚Р°
	 *
	 * @see email_DocumentIntf
	 * @param int $id
	 *        	- РёРґ РЅР° РґРѕРєСѓРјРµРЅС‚Р°
	 * @param boolean $forward        	
	 * @return string - С‚СЏР»РѕС‚Рѕ РЅР° РёРјРµР№Р»Р°
	 */
	public function getDefaultEmailBody($id, $forward = FALSE) {
		$handle = $this->getHandle ( $id );
		$tpl = new ET ( tr ( "РњРѕР»СЏ Р·Р°РїРѕР·РЅР°Р№С‚Рµ СЃРµ СЃ РЅР°С€Р°С‚Р° РґРµРєР»Р°СЂР°С†РёСЏ Р·Р° СЃСЉРѕС‚РІРµС‚СЃС‚РІРёРµ" ) . ': #[#handle#]' );
		$tpl->append ( $handle, 'handle' );
		
		return $tpl->getContent ();
	}
	
	/**
	 * Р—Р°СЂРµР¶РґР° С€Р°Р±Р»РѕРЅРёС‚Рµ РЅР° РїСЂРѕРґР°Р¶Р±Р°С‚Р° РІ doc_TplManager
	 */
	function loadSetupData() {
		$tplArr = array ();
		$tplArr [] = array (
				'name' => 'Р”РµРєР»Р°СЂР°С†РёСЏ Р·Р° СЃСЉРѕС‚РІРµС‚СЃС‚РІРёРµ',
				'content' => 'dec/tpl/AgreementDeclaration.shtml',
				'lang' => 'bg' 
		);
		$tplArr [] = array (
				'name' => 'РџСЂРёР»РѕР¶РµРЅРёРµ в„–1',
				'content' => 'dec/tpl/Application1.shtml',
				'lang' => 'bg' 
		);
		$tplArr [] = array (
				'name' => 'РџСЂРёР»РѕР¶РµРЅРёРµ в„–5',
				'content' => 'dec/tpl/Application5.shtml',
				'lang' => 'bg' 
		);
		$tplArr [] = array (
				'name' => 'Declaration of compliance',
				'content' => 'dec/tpl/DeclarationOfCompliance.shtml',
				'lang' => 'en' 
		);
		
		$res .= doc_TplManager::addOnce ( $this, $tplArr );
	}
	
	/**
	 * РњРµС‚РѕРґ РїРѕ РїРѕРґСЂР°Р·Р±РёСЂР°РЅРµ Р·Р° РЅР°РјРёСЂР°РЅРµ РЅР° РґРµС„РѕР»С‚ С€Р°Р±Р»РѕРЅР°
	 */
	public function getDefaultTemplate_($rec) {
		$cData = doc_Folders::getContragentData ( $rec->folderId );
		$bgId = drdata_Countries::fetchField ( "#commonName = 'Bulgaria'", 'id' );
		
		$conf = core_Packs::getConfig ( 'dec' );
		$def = (empty ( $cData->countryId ) || $bgId === $cData->countryId) ? $conf->DEC_DEF_TPL_BG : $conf->DEC_DEF_TPL_EN;
		
		return $def;
	}
	
	/**
	 * Р’СЂСЉС‰Р° РІСЃРёС‡РєРё Р’СЃРёС‡РєРё Р»РёС†Р°, РєРѕРёС‚Рѕ РјРѕРіР°С‚ РґР° Р±СЉРґР°С‚ С‚РёС‚СѓР»СЏСЂРё РЅР° СЃРјРµС‚РєР°
	 * С‚РµР·Рё РІРєР»СЋС‡РµРЅРё РІ РіСЂСѓРїР° "РЈРїСЂР°РІРёС‚РµР»Рё"
	 */
	public function getManagers() {
		$options = array ();
		$groupId = crm_Groups::fetchField ( "#sysId = 'managers'", 'id' );
		$personQuery = crm_Persons::getQuery ();
		$personQuery->where ( "#groupList LIKE '%|{$groupId}|%'" );
		
		while ( $personRec = $personQuery->fetch () ) {
			// $options[$personRec->id] = crm_Persons::getVerbal($personRec, 'name');
			$options [crm_Persons::getVerbal ( $personRec, 'name' )] = crm_Persons::getVerbal ( $personRec, 'name' );
		}
		
		return $options;
	}
}
