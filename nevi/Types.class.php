<?php


/**
 *
**/
class nevi_Types extends core_Master
{    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {

        $this->FLD('varchar', 'varchar(255)', 'caption=varchar');

                $this->FLD('proxyEgn', 'bglocal_EgnType', 'caption=ЕГН');
                $this->FLD('key', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=key,remember,class=contactData,mandatory,export=Csv');
                $this->FLD('emails', 'emails', 'caption=Имейли,class=contactData,export=Csv');
                $this->FLD('email', 'email', 'caption=Имейл');
                $this->FLD('nick', 'nick(64)', 'caption=Ник');
                $this->FLD('pass', 'password', 'caption=Pass');
                $this->FLD('userId', 'user', 'caption=Потребител');
                $this->FLD('userOrRole', 'userOrRole(rolesType=team)', 'caption=Потребител/и');
                $this->FLD('sharedUsers', 'userList(roles=powerUser)', 'caption=Потребители,mandatory');
                $this->FLD('author', 'users(rolesForAll=labelMaster|ceo|admin, rolesForTeams=label|ceo|admin)', 'caption=От');
               // $this->FLD('captcha', 'captcha_Type', 'caption=Разпознаване');
                $this->FLD('level', 'order(11)', 'caption=№');


                $this->FLD('group', 'group(base=crm_Companies,keylist=groupList)', 'caption=Групи,w100');
                $this->FLD('lists', 'keylist(mvc=acc_Lists,select=name)', 'caption=Номенклатури,w100');

                $this->FLD('identifier', 'identifier(64,utf8)', 'caption=Наименование');
                $this->FLD('combodate', 'combodate(minYear=1850,maxYear=' . date('Y') . ')', 'caption=Рожден ден,export=Csv');
                $this->FLD('row', 'complexType(left=К-во,right=Цена)', 'caption=complexType');
                $this->FLD('customKey', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Валута');
                $this->FLD('bigint', 'bigint(21)', 'caption=Номер, export=Csv,hint=Номера с който идва фактурата');
                $this->FLD('int', 'int', 'caption=int');
                $this->FLD('vatRate', 'percent', 'caption=ДДС');
                $this->FLD('double', 'double(decimals=2,max=2000000000,min=0)', 'caption=Сума,mandatory,summary=amount');
                $this->FLD('float', 'float', 'caption=Сума1');
                $this->FLD('fromIp', 'ip', 'caption=IP');


        $this->FLD('iban', 'iban_Type(64)', 'caption=IBAN / №, removeAndRefreshForm=bic|bank|discount');
        $this->FLD('bic', 'varchar(12)', 'caption=BIC');
        $this->FLD('bank', 'varchar(64)', 'caption=Банка');
        $this->FLD('discount', 'percent(Min=0,max=1)', 'caption=Отстъпка,smartCenter,input=none,suggestion=1|2|3');

        $this->FLD('accountId', 'acc_type_Account(allowEmpty)', 'caption=Сметка,silent,removeAndRefreshForm=action');
        $this->FLD('accounts', 'acc_type_Accounts', 'caption=Сметки');
        $this->FLD('gpsCoords', 'location_Type', 'caption=Координати');
        $this->FLD('class', 'class', 'caption=Клас,mandatory,silent');
        $this->FLD('regInterfaceId', 'interface(suffix=AccRegIntf, allowEmpty, select=name)', 'caption=Интерфейс,export');
        $this->FLD('drdata_PhoneType', 'drdata_PhoneType(type=tel)', 'caption=Телефони,class=contactData,silent,export=Csv');
        $this->FLD('url', 'url', 'caption=url');
        $this->FLD('drdata_VatType', 'drdata_VatType', 'caption=drdata_VatType,remember=info,class=contactData,export=Csv');
        $this->FLD('richtext', 'richtext', 'caption=richtext,height=150px');
        $this->FLD('titleLink', 'html', 'caption=Хтмл');
        $this->FLD('data', 'blob(serialize, compress)', 'caption=Данни');
        $this->FLD('state', 'enum(pending=Чакащо, sended=Изпратено, active=Активно)', 'caption=Състояние');
        $this->FLD('state2', 'enum(pending=Чакащо, sended=Изпратено)', 'maxRadio=4,caption=Състояние2');
        $this->FLD('sentOn', 'datetime(format=smartTime)', 'caption=Изпратено');
        $this->FLD('period', 'minutes', 'caption=Период (мин)');
        $this->FLD('to', 'date', 'caption=До,silent');
        $this->FLD('limitDuration', 'time(suggestions=1 седмица|2 седмици|1 месец|3 месеца|6 месеца|1 година)', 'caption=Продължителност');
        $this->FLD('stroke', 'color_Type', 'caption=Цвят,value=#333333');
        $this->FLD('logo', 'fileman_FileType(bucket=pictures)', 'caption=Лого');
        $this->FLD('groupList', 'keylist(mvc=crm_Groups,select=name,makeLinks,where=#allow !\\= \\\'persons\\\')', 'caption=Групи,remember,silent');
        $this->FLD('sendingDay', 'set(1=Пон, 2=Вто)', 'caption=Ден, columns=7');
        $this->FLD('weight', 'cat_type_Weight', 'caption=Тегло');
        $this->FLD('volume', 'cat_type_Volume', 'caption=Обем');
        $this->FLD('Density', 'cat_type_Density', 'caption=Density');
        $this->FLD('Uom', 'cat_type_Uom', 'caption=Uom');
        $this->FLD('sizeDepth', 'cat_type_Size', 'caption=Дълбочина');
        $this->FLD('eanCode', 'gs1_TypeEan', 'caption=EAN');
        $this->FLD('Temperature', 'physics_TemperatureType', 'caption=TemperatureType');
        $this->FLD('PressureType', 'physics_PressureType', 'caption=PressureType');
        $this->FLD('HumidityType', 'physics_HumidityType', 'caption=HumidityType');
        $this->FLD('fileSize', "fileman_FileSize", 'caption=Размер');
    }

    protected static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $rec = $data->form->rec;

        if($iban = Request::get('iban')) {
            $data->form->setDefault('iban', $iban);
        }

        if($data->form->rec->iban)
            $data->form->setField('discount', 'input');


        // Ако има въведен iban
        if(isset($rec->iban)){

            // и той е валиден
            if(!$data->form->gotErrors()){

                // по дефолт извличаме името на банката и bic-а ако можем
                $data->form->setDefault('bank', bglocal_Banks::getBankName($rec->iban));
                $data->form->setDefault('bic', bglocal_Banks::getBankBic($rec->iban));
            }
        }
    }
}
