<?php


//cls::load('php_Token');


/**
 * Обща директория на bgerp, vendors, ef. Използва се за едновременно форматиране на трите пакета.
 */
//defIfNot('EF_ALL_PATH', EF_ROOT_PATH . '/all');


/**
 * Клас 'php_Formater' - Форматер за приложения на EF
 *
 * Форматира кода на файлове, включени във ЕП, приложението, vendors, private и др.
 *
 *
 * @category  vendors
 * @package   php
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class php_Test extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Тестване на файлове от EF/bgERP/vendors";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools,plg_Sorting,plg_Sorting,php_Wrapper';
    
    
    /**
     * Масив с всички използвани функции
     * @var array
     */
    var $arrF;
    
    var $functions;
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('functionName', 'varchar', 'caption=Име->Функция');
        $this->FLD('modulName', 'varchar', 'caption=Име->Модул');
        //$this->FLD('modul', 'varchar', 'caption=Модул');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Tester()
    {
        requireRole('admin');
        expect(isDebug());
        
        $form = cls::get('core_Form');
        
        if(defined('EF_PRIVATE_PATH')) {
            $form->FNC('src', 'enum(' . EF_APP_PATH . ',' . EF_EF_PATH . ',' . EF_VENDORS_PATH . ',' . EF_PRIVATE_PATH . ')', 'caption=Директории->Източник,input,mandatory');
        } else {
            $form->FNC('src', 'enum(' . EF_APP_PATH . ',' . EF_EF_PATH . ',' . EF_VENDORS_PATH . ', ' . EF_ALL_PATH . ')', 'caption=Директории->Оригинален код,input');
        }
        
        $form->FNC('dst', 'varchar', 'caption=Директории->За тестване на  кода,recently,input,mandatory,width=100%');
        
        $form->title = "Тестване на системата";
        
        $form->toolbar->addSbBtn("Тествай...");
        
        $form->input();
        
        if($form->isSubmitted()) {
            
            $src = $form->rec->src . '/';
            $dst = rtrim($form->rec->dst, '/') . '/';
            
            if(!is_dir($dst)) {
                $form->setWarning('dst', "Директорията <b>{$dst}</b> не съществува. Да бъде ли създадена?");
            }
            
            if(!$form->gotErrors()) {
                
                $files = (object) php_Formater::readAllFiles($src);
                
                unset($files['files'][29]);     //изключване на store/_PalletDetails
                unset($files['files'][89]);     //изключваме предефиниране на bank_PaymentMethodDetails(bank, common)
                unset($files['files'][86]);     //изключваме предефиниране на bank_PaymentMethodDetails(bank, common)
                unset($files['files'][103]);    //изключване на tests/config/core/Exception/Expect.class.php
                
                set_time_limit(120);
                
                foreach($files->files as $f) {
                    
                    // bp($files->files);
                    
                    $destination = str_replace("\\", "/", $dst . $f);
                    $dsPos = strrpos($destination, "/");
                    $dir = substr($destination, 0, $dsPos);
                    
                    $cl = str_replace("/", "_", $f);
                    
                    $class = strtok($cl, ".");
                    
                    if(!is_dir($dir)) mkdir($dir, 0777, TRUE);
                    
                    // Пропускаме класовете които ни правят проблем
                    
                    if($class == 'store_Products' ||
                        $class == 'store_Stores' ||
                        $class == 'acc_Items' ||
                        $class == 'cat_Products_Packagings' || //core_Details
                        $class == 'catering_RequestDetails' || //core_Details
                        $class == 'acc_BalanceDetails' ||  //core_Details
                        $class == 'acc_ArticleDetails' ||  //core_Details
                        $class == 'email_Incomings' ||
                        $class == 'email_Sent' ||
                        $class == 'email_Pop3' ||
                        $class == 'email_Accounts' ||
                        $class == 'email_Router' ||
                        $class == 'email_Inboxes' ||
                        $class == 'cash_Cases' ||
                        $class == 'cash_Documents' ||
                        $class == 'common_LocationTypes' ||
                        $class == 'common_DistrictCourts' ||
                        $class == 'common_DocumentTypes' ||
                        $class == 'common_Mvr' ||
                        $class == 'common_Locations' ||
                        $class == 'common_Units' ||
                        $class == 'common_DeliveryTerms' ||
                        $class == 'bank_OwnAccounts' ||
                        $class == 'bank_AccountTypes' ||
                        $class == 'bank_PaymentMethods' ||
                        $class == 'bank_Documents' ||
                        $class == 'bank_Accounts' ||
                        $class == 'bank_PaymentMethodDetails' || //core_Details
                        $class == 'catering_EmployeesList' ||
                        $class == 'catering_Menu' ||
                        $class == 'catering_Requests' ||
                        $class == 'catering_MenuDetails' ||
                        $class == 'catering_Orders' ||
                        $class == 'catering_Companies' ||
                        $class == 'accda_Documents' ||
                        $class == 'accda_Da' ||
                        $class == 'accda_Groups' ||
                        $class == 'catpr_Pricelists_Details' || //core_Details
                        $class == 'catpr_Discounts_Details' ||  //core_Details
                        $class == 'catpr_Costs' ||
                        $class == 'catpr_Pricelists' ||
                        $class == 'catpr_Pricegroups' ||
                        $class == 'catpr_Discounts' ||
                        $class == 'trans_DeliveryTerms' ||
                        $class == 'sales_Deals' ||
                        $class == 'sales_Invoices' ||
                        $class == 'sales_InvoiceDetails' || //core_Details
                        $class == 'store_RackDetails' ||    //core_Details
                        $class == 'store_Pallets' ||
                        $class == 'store_DocumentDetails' ||  //core_Details
                        $class == 'store_Zones' ||
                        $class == 'store_Movements' ||
                        $class == 'store_Documents' ||
                        $class == 'store_Racks' ||
                        $class == 'crm_Persons' ||
                        $class == 'crm_Locations' ||
                        $class == 'cal_Agenda' ||
                        $class == 'crm_Companies' ||
                        $class == 'crm_Groups' ||
                        $class == 'cat_Products_Params' ||
                        $class == 'cat_Products_Files' ||
                        $class == 'cat_Params' ||
                        $class == 'cat_Packagings' ||
                        $class == 'cat_Categories' ||
                        $class == 'cat_UoM' ||
                        $class == 'cat_Products' ||
                        $class == 'cat_Groups' ||
                        $class == 'acc_SaleDetails'){
                        continue;
                    }
                    
                    // Ако класа е със суфикс от приетите от фреймуърка, той се зарежда
                    
                    $loader[$class] = cls::get($class);
                    
                    if(($loader[$class] instanceof core_Manager) || ($loader[$class] instanceof core_Master) && (($loader[$class] instanceof core_Detail) == FALSE)){
                        
                        $URL = array('Ctr'=>$loader[$class], 'Act'=>'list');
                        
                        $result = request::forward($URL);
                    }
                }
                
                return new Redirect(array($this));
            }
        }
        
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * 
     * Функция за извеждане на всички активни php модули и функциите, които използваме
     */
    function act_phpInfo()
    {
    
     // Намираме всички активни модули	
     $modules = get_loaded_extensions();
     
     foreach ($modules as $mod){
     	
     	// За всеки модул търсим, кои функции спадат към него
     	$functions = get_extension_funcs($mod);
    
     	if (is_array($functions)) {
	     	foreach ($functions as $fun){
	     		
	     		// Масив с ключ името на функцията и стойност модула му
	     		$info[$fun] = $mod;
	     	}	
     	}
      }

       $arr = $this->generateModules();
       $new = $this->modules();
     	//bp($new);
     	// Сравняваме ключовете на двата масива, ако има съответствие взимаме името на функцията 
     	// и записваме като стойност модула й
     	foreach ($arr as $f=>$v){
     		foreach ($info as $func=>$mode){
     			if(trim($f) === trim($func)){
     				$ourFun[$f] = $mode;
     				     				
     				// Записваме в БД
     				$rec = new stdClass();
     				$rec->functionName = $f;
                    $rec->modulName = $mode;
                    //$rec->modul = $m;
                    
     			}
     		}
     	}
     	 //bp($mods);
     	
     	 php_Test::save($rec, NULL, 'IGNORE');
     	 
     	 return new Redirect(array($this));
     	//return $mods;
    }

    function generateModules(){
   	
 
    // Зареждаме клас  php_Formater
    $formater = cls::load(php_Formater);
          
    set_time_limit(540);
    
    // Прочитаме всички файлове
    $erp = (object) php_Formater::readAllFiles('/var/www/ef_root/');
 
   

     	foreach($erp->files as $file){
     		$file = '/var/www/ef_root/'. $file;
     		
     		// Ако файловете имат път различен от посочения ги пропускаме
     		if(!strpos($file, 'ef_root/ef/') && 
     		   !strpos($file, 'ef_root/bgerp/') && 
     		   !strpos($file, 'ef_root/vendors/')) continue;
     		
     		// Разглеждаме само тези файлове, които имат определен суфикс 
     		if(strpos($file, '.class.php') || strpos($file, 'boot.inc.php')) {
     			$src = $file;
     			
     			// Вземаме съдържанието на файла
       			$str = file_get_contents($src);
     			
     			$this->tokenArr = array();
     			
     			// Парсираме файла
     			php_BeautifierM::parse($str);
     			
     			// Правим масив с токените
     			$ta = $this->tokenArr;
     			
				if (is_array($ta)) {
					foreach($ta as $i => $c) {
						
						// Махаме всички интервали
						if($c->type != T_WHITESPACE) {
							$e[] = $i;
						}
					}
				}
			        
				if (is_array($e)) {
				     foreach($e as $id => $i) {

				     	// Върсим всички функции, които използваме
				      	 if (($ta[$e[$id]]->type == T_STRING)   &&
				             ($ta[$e[$id+1]]->type == '(')) {

				            // Масив с ключ името на функцията и стойност колко пъти се среща
				            $this->arrF[$ta[$e[$id]]->str]++;
				         }
				     }
				}

				   // $r++;
		     		//if($r > 500)  bp($this->arrF, $info);
     		}	

     	}
     
    	return $this->arrF;
    }
    
    function modules(){
    	
     // Намираме всички активни модули	
     $modules = get_loaded_extensions();
     
     foreach ($modules as $mod){
     	
     	// За всеки модул търсим, кои функции спадат към него
     	$functions = get_extension_funcs($mod);
    
     	if (is_array($functions)) {
	     	foreach ($functions as $fun){
	     		
	     		// Масив с ключ името на функцията и стойност модула му
	     		$info[$fun] = $mod;
	     	}	
     	}
      }
    	
        // Сравняваме ключовете на двата масива, ако има съответствие взимаме името на функцията 
     	// и записваме като стойност модула й
       	$arrMod = $this->generateModules();
     	foreach ($arrMod as $f=>$v){
     		foreach ($info as $func=>$mode){
     			if(trim($f) === trim($func)){
     				$ourFun[$f] = $mode;
     				$mods[] = $mode; 
     				$mods = array_unique($mods);
     			                    
     			}
     		}
     	}
   bp($mods);
     	return $mods;
    }
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        
        $data->toolbar->addBtn('Тест', array($mvc, 'Tester'));
        $data->toolbar->addBtn('Инфо модули', array($mvc, 'phpInfo'));
    }
    
}

