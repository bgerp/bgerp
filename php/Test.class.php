<?php


cls::load('php_Token');


/**
 * Обща директория на bgerp, vendors, ef. Използва се за едновременно форматиране на трите пакета.
 */
defIfNot('EF_ALL_PATH', EF_ROOT_PATH . '/all');


/**
 * Клас 'php_Formater' - Форматер за приложения на EF
 *
 * Форматира кода на файлове, включени във ЕП, приложението, vendors, private и др.
 *
 *
 * @category  all
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
    var $loadList = 'plg_RowTools,plg_Sorting,plg_Sorting';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('fileName', 'varchar', 'caption=Файл');
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
                
                $files = (object) $this->readAllFiles($src);
                
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
                        $class == 'crm_Calendar' ||
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
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    function on_AfterPrepareListToolbar($mvc, $res, $data)
    {
        
        $data->toolbar->addBtn('Тест', array($mvc, 'Tester'));
    }
    
    
    /**
     * Връща масив със всички поддиректории и файлове от посочената начална директория
     *
     * array(
     * 'files' => [],
     * 'dirs'  => [],
     * )
     * @param string $root
     * @result array
     */
    function readAllFiles($root = '.')
    {
        $files = array('files'=>array(), 'dirs'=>array());
        $directories = array();
        $last_letter = $root[strlen($root)-1];
        $root = ($last_letter == '\\' || $last_letter == '/') ? $root : $root . DIRECTORY_SEPARATOR;
        
        $directories[] = $root;
        
        while (sizeof($directories)) {
            
            $dir = array_pop($directories);
            
            if ($handle = opendir($dir)) {
                while (FALSE !== ($file = readdir($handle))) {
                    if ($file == '.' || $file == '..' || $file == '.git') {
                        continue;
                    }
                    
                    $file = $dir . $file;
                    
                    if (is_dir($file)) {
                        $directory_path = $file . DIRECTORY_SEPARATOR;
                        array_push($directories, $directory_path);
                        $files['dirs'][] = $directory_path;
                    } elseif (is_file($file) && strpos($file, '.class.php')) {
                        
                        $files['files'][] = str_replace($root, "", $file);
                        
                        unset($files['files'][29]);    //изключване на store/_PalletDetails
                        unset($files['files'][89]);    //изключваме предефиниране на bank_PaymentMethodDetails(bank, common)
                        unset($files['files'][86]);    //изключваме предефиниране на bank_PaymentMethodDetails(bank, common)
                        unset($files['files'][103]);   //изключване на tests/config/core/Exception/Expect.class.php
                    }
                }
                closedir($handle);
            }
        }
        
        return $files;
    }
}

