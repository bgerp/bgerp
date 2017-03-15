<?php



/**
 * Клас 'drdata_Countries' -
 *
 *
 * @category  bgerp
 * @package   drdata
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class drdata_Countries extends core_Manager {
    
    
    /**
     * Заглавие
     */
    public $title = 'ISO информация за страните по света';
    
    
    /**
     * @todo Чака за документация...
     */
    public $recTitleTpl = '[#commonName#]';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'drdata_Wrapper,plg_Sorting';
    

    /**
     * Масив за съхранение на съответствието Държава => Езици
     */
    static $countryToLanguages = array();

	
    /**
     * Списък с кодовете на държавите от европейския съюз
     */
    static $euCountries = array('BE','BG','CY','CZ','DK','EE','GR','DE','PT','FR','FI','HU','LU','MT','SI','IE','IT','LV','LT','NL','PL','SK','RO','SE','ES','GB', 'AT', 'HR');
    

    /**
     * Списък с кодовете на държавите от европейския съюз
     */
    static $eurCountries = array('BE','BG','CY','CZ','DK','EE','GR','DE','PT','FR','FI','HU','LU','MT',
        'SI','IE','IT','LV','LT','NL','PL','SK','RO','SE','ES','GB', 'AT', 'HR',
        'IS', 'NO', 'CH', 'LI', 'ME', 'MK', 'AL', 'RS', 'TR', 'XK', 'BA');


    /**
     * Кой има право да променя?
     *
     * @var string|array
     */
    public $canEdit = "no_one";
    
    
    /**
     * Кой може да го изтрие?
     *
     * @var string|array
     */
    public $canDelete = "no_one";
    
    
    /**
     * Кой има право да добавя?
     *
     * @var string|array
     */
    public $canAdd = "no_one";
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('commonName', 'varchar', 'caption=Наименование');
        $this->FLD('commonNameBg', 'varchar', 'caption=НаименованиеBG');
        $this->FLD('formalName', 'varchar', 'caption=Формално име');
        $this->FLD('type', 'varchar', 'caption=Тип');
        $this->FLD('sovereignty', 'varchar', 'caption=Суверинитет');
        $this->FLD('capital', 'varchar', 'caption=Столица');
        $this->FLD('currencyCode', 'varchar(3)', 'caption=Валута->Код');
        $this->FLD('currencyName', 'varchar', 'caption=Валута->Име');
        $this->FLD('telCode', 'varchar(6)', 'caption=Tел. код');
        $this->FLD('letterCode2', 'varchar(3)', 'caption=ISO 3166-1->2,rem=ISO 3166-1 2 буквен код');
        $this->FLD('letterCode3', 'varchar(3)', 'caption=ISO 3166-1->3, rem=ISO 3166-1 3 буквен код');
        $this->FLD('isoNumber', 'int', 'caption=ISO 3166-1->N, rem=ISO Номер');
        $this->FLD('domain', 'varchar(4)', 'caption=TLD, rem=IANA Country Code TLD');
        $this->FNC('languages', 'varchar', 'caption=Езици');
        $this->load('plg_RowTools');
        
        $this->setDbUnique('commonName');
        $this->setDbIndex('letterCode2');
        $this->setDbIndex('letterCode3');
    }
    
	
    /**
     * Дали държавата е в EU
     * @param int $countryId - ид на държавата
     * @return boolean TRUE/FALSE
     */
    public static function isEu($countryId)
    {
    	expect($abbr = static::fetchField($countryId, 'letterCode2'));
    	
    	return in_array($abbr, static::$euCountries);
    }
    


    /**
     * Връща държавите, с които се търгува в EUR
     *
     * @param int $countryId - ид на държавата
     * @return boolean TRUE/FALSE
     */
    public static function isEUR($countryId)
    {
    	expect($abbr = static::fetchField($countryId, 'letterCode2'));
    	
    	return in_array($abbr, static::$eurCountries);
    }


    /**
     * Попълва езиците, които се говорят в дадена страна
     */
    function on_CalcLanguages($mvc, &$rec)
    {
        if(!self::$countryToLanguages) {
            self::$countryToLanguages = arr::make(  
               'af=fa|ps,ax=sv,al=sq,dz=ar,as=sm,ad=ca,ao=pt,ai=en,aq=en|fr|es|no|nn,ag=en,ar=es,am=hy,aw=nl,ac=en,au=en,at=de,az=az,bs=en,
               bh=ar,bd=bn,bb=en,by=be,be=de|fr|nl|wa,bz=en,bj=fr,bm=en,bt=dz,bo=es,ba=bs,bw=en,br=pt,io=en,bn=ms,bg=bg,bf=fr,bi=fr,kh=km,
               cm=en|fr,ca=en|fr,cv=pt,ky=en,cf=fr,td=fr|ar,cl=es,cn=zh,cx=en,cc=ms|en,co=es,km=ar|fr,cg=fr,cd=fr,ck=en|mi,cr=es,ci=fr,
               hr=hr,cu=es,cy=el|tr,cz=cs,dk=da|en,dj=fr|ar,dm=en,do=es,ec=es,eg=ar,sv=es,gq=es|fr,er=ti,ee=et,et=am|ti,fk=en,fo=fo,
               fj=en|fj,fi=fi|sv,fr=br|fr|oc,gf=fr,pf=fr,tf=fr,ga=fr,gm=en|wo|ff,ge=ka,de=de,gh=en,gi=en|es|it|pt,gr=el,gl=kl,gd=en,
               gp=fr,gu=en|ch,gt=es,gg=en|fr,gn=fr,gw=pt,gy=en,ht=fr,va=it|la,hn=es,hk=en|zh,hu=hu,is=is,in=ar|bn|en|hi|mr|ta|te,id=id,
               ir=fa,iq=ar,ie=en|ga,im=en|gv,il=he,it=it,jm=en,jp=ja,je=en|pt,jo=ar,kz=kk|ru,ke=en,ki=en,kp=ko,kr=ko,kw=ar,kg=ky|ru,la=lo,
               lv=lv,lb=ar,ls=st|en|zu|xh,lr=en,ly=ar,li=de,lt=lt,lu=de|fr,mo=zh,mk=mk,mg=fr|mg,mw=ny,my=ms,mv=dv,ml=fr|bm,mt=mt,mh=mh,
               mq=fr,mr=ar,mu=en|fr,yt=fr,mx=es,fm=en,md=mo|ru,mc=fr|it|en,mn=mn,me=sr,ms=en,ma=ar,mz=pt,mm=my,na=en|af|de,nr=na|en,
               np=ne|en,nl=nl,an=nl,nc=fr,nz=en|mi,ni=es,ne=fr,ng=en|ha|yo|ig|ff,nu=en,nf=en,mp=ch|en,no=nn|no|se,om=ar,pk=ur,pw=en,
               ps=ar,pa=es,pg=en,py=es,pe=es,ph=en|tl,pn=en,pl=pl,pt=pt,pr=es,qa=ar,re=fr,ro=ro,ru=ru|tt,rw=rw,bl=fr,sh=en,kn=en,lc=en,
               pm=fr,vc=en,ws=sm|en,sm=it,st=pt,sa=ar,sn=fr|wo,rs=sr,sc=en,sl=en,sg=en|sg|zh,sk=sk,si=sl,sb=en,so=so,
               za=en|af|zu|xh|tn|st|ss|nr,gs=en,es=ca|es|eu|gl,lk=si|ta,sd=ar,sr=nl,sj=no|ru,sz=en|ss,se=sv,ch=de|fr|it,sy=ar,tw=zh,
               tj=tg,tz=sw,th=th,tl=pt,tg=fr|ee,tk=en,to=to|en,tt=en,tn=ar,tr=tr,tm=tk,tc=en,tv=tv,ug=en,ua=uk,ae=ar,gb=cy|en|gd|gv|kw,
               us=en,um=en,uy=es,uz=uz,vu=bi|en,ve=es,vn=vi,vg=en,vi=en,wf=fr,eh=ar,ye=ar,yu=mk|sl|hr,zm=en,zw=en');
        }

        $rec->languages = str_replace('|', ',', self::$countryToLanguages[strtolower($rec->letterCode2)]);
    }

    
    /**
     * Връща id-то на държавата от която посоченото или текущото ip
     */
    function getByIp($ip = NULL)
    {
        $cCode2 = drdata_IpToCountry::get($ip);
                
        $id = self::fetchField("#letterCode2 = '{$cCode2}'", 'id');
        
        return $id;
    }


    /**
     * Връща името на държава на основния език
     * 
     * @param mixed mix id, 2 или 3 буквен
     */
    static function getCountryName($mix, $lg = NULL)
    {
        if(!$lg) {
            $lg = core_Lg::getDefaultLang();
        }
        if($lg == 'bg') {
            $field = 'commonNameBg';
        } else {
            $field = 'commonName';
        }

        if(is_numeric($mix)) {
            $country = drdata_Countries::fetchField($mix, $field);
        } elseif(strlen($mix) == 2) {
            $country = drdata_Countries::fetchField(array("#letterCode2 = '[#1#]'", $mix), $field);
        } else {
            expect(strlen($mix) == 3, $mix);
            $country = drdata_Countries::fetchField(array("#letterCode3 = '[#1#]'", $mix), $field);
        }

        return $country;
    }
    
    
    /**
     * Изпълнява се преди запис в модела
     * Премахва не-цифровите символи в кода
     */
    static function on_BeforeImportRec($mvc, $rec)
    {
        $rec->telCode = preg_replace('/[^0-9]+/', '', $rec->telCode);
    }


    public static function getIdByName($country)
    {
        static $commonNamesArr, $namesArr;
        
        if(is_numeric($country) && self::fetch($country)) {

            return $country;
        }

        if(!$commonNamesArr) {
            $query = self::getQuery();
            while($rec = $query->fetch()) {
                $commonNamesArr[strtolower(trim($rec->commonName))] = $rec->id;
                $commonNamesArr[strtolower(trim(str::utf2ascii($rec->commonNameBg)))] = $rec->id;
                $namesArr[strtolower(trim($rec->formalName))] = $rec->id;
                $namesArr[strtolower(trim($rec->letterCode2))] = $rec->id;
                $namesArr[strtolower(trim($rec->letterCode3))] = $rec->id;
            }

            $mis = array(
                'aequatorial guinea' => 'equatorial guinea',
                'algerie' => 'algeria',
                'b' => 'bulgaria',
                'balgaria' => 'bulgaria',
                'balgium' => 'belgium',
                'bealrus' => 'belarus',
                'belgique' => 'belgium',
                'belguim' => 'belgium',
                'bilgium' => 'belgium',
                'blagaria' => 'bulgaria',
                'bosna i hercigovina' => 'bosnia and herzegovina',
                'brasil' => 'brazil',
                'bugaria' => 'bulgaria',
                'bul' => 'bulgaria',
                'bulagia' => 'bulgaria',
                'bulagria' => 'bulgaria',
                'bularia' => 'bulgaria',
                'bulgairya' => 'bulgaria',
                'bulgari' => 'bulgaria',
                'bulgarien' => 'bulgaria',
                'bulgyaria' => 'bulgaria',
                'bylgariq' => 'bulgaria',
                'cameroun' => 'cameroon',
                'chech republic' => 'czech republic',
                'chez republic' => 'czech republic',
                'cote d ivoire' => 'cote d\'ivoire',
                'cote d\'ivoir' => 'cote d\'ivoire',
                'czech' => 'czech republic',
                'd' => 'germany',
                'danmark' => 'denmark',
                'demnark' => 'denmark',
                'deuschland' => 'germany',
                'deutschland' => 'germany',
                'dubai' => 'united arab emirates',
                'england' => 'united kingdom',
                'espana' => 'spain',
                'estona' => 'estonia',
                'f' => 'france',
                'finalnd' => 'finland',
                'finand' => 'finland',
                'gemany' => 'germany',
                'german' => 'germany',
                'gramany' => 'germany',
                'grecce' => 'greece',
                'grece' => 'greece',
                'greeece' => 'greece',
                'greek' => 'greece',
                'greese' => 'greece',
                'guinee' => 'guinea',
                'hellas' => 'greece',
                'hing kong' => 'hong kong',
                'holland' => 'netherlands',
                'hrvatska' => 'harvatia',
                'ile maurice' => 'mauritius',
                'iraland' => 'ireland',
                'israrel' => 'israel',
                'italien' => 'italy',
                'itally' => 'italy',
                'korea south' => 'republic of korea',
                'kosova' => 'kosovo',
                'latvija' => 'latvia',
                'lithunia' => 'lithuania',
                'luxemburg' => 'luxembourg',
                'makedonija' => 'macedonia',
                'makedoniq' => 'macedonia',
                'maroc' => 'morocco',
                'marocco' => 'morocco',
                'nederland' => 'netherlands',
                'nederlands' => 'netherlands',
                'netherland' => 'netherlands',
                'niederlande' => 'netherlands',
                'norawy' => 'norway',
                'noruega' => 'norway',
                'o.a.e.' => 'united arab emirates',
                'oesterreich' => 'austria',
                'osterreich' => 'austria',
                'palestinian territory' => 'palestinа',
                'polska' => 'poland',
                'protugal' => 'portugal',
                'rbulgaria' => 'bulgaria',
                'srbija' => 'serbia',
                'roamnia' => 'romania',
                'rossia' => 'russia',
                'rossii' => 'russia',
                'schweiz' => 'switzerland',
                'seoul, korea' => 'republic of korea',
                'sewden' => 'sweden',
                'slovania' => 'slovenia',
                'slovenija' => 'slovenia',
                'slovenska' => 'slovenia',
                'slowakische' => 'slovakia',
                'slowenia' => 'slovenia',
                'south korea' => 'republic of korea',
                'spainia' => 'spain',
                'suomi' => 'finland',
                'svizzera' => 'switzerland',
                'sw' => 'switzerland',
                'switerland' => 'switzerland',
                'swizerland' => 'switzerland',
                'polynesie francaise' => 'french polynesia',
                'tannzania' => 'tanzania',
                'trinidad & tobago' => 'trinidad and tobago',
                'trinidad & tobago ' => 'trinidad and tobago',
                'tukey' => 'turkey',
                'tunisie' => 'tunisia',
                'northern cyp' => 'northern cyprus',
                'turkiye' => 'turkey',
                'uae' => 'united arab emirates',
                'uk' => 'united kingdom',
                'ukraina' => 'ukraine',
                'unaited states' => 'united states',
                'unated states' => 'united states',
                'united kigdom' => 'united kingdom',
                'united kingom' => 'united kingdom',
                'united kongdom' => 'united kingdom',
                'untited kingdom' => 'united kingdom',
                'untited states' => 'united kingdom',
                'veliko tarnovo' => 'bulgaria',
                'viet nam' => 'vietnam',
                'việt nam' => 'vietnam',
                'yugoslavia' => 'serbia',
                'ελλαδα' => 'greece',
                'usa' => 'united states',
                'bahamas' => 'bahamas, the',
            );

            foreach($mis as $w => $c) {
                expect($id = $commonNamesArr[$c], $c, $commonNamesArr);
                expect(!$commonNamesArr[$w], $w, $commonNamesArr);
                $commonNamesArr[$w] = $id;
            }
        }

        $country = strtolower(trim(str::utf2ascii($country)));
        $country = preg_replace("/[^a-z0-9]+/u", " ", $country);

        // Добавка за този начин на изписване на формалното име на страната
        if($i = strpos($country, ', republic of')) {
            $country = 'republic of ' . trim(substr($country, 0, $i));
        }

        $country = trim(preg_replace('/[^a-zA-Z\'\d\p{L}]/u', " ", $country));
        
        if(!$country) return FALSE;
        

        if($id = $namesArr[$country]) {

            return $id;
        }
        
        if($id = $commonNamesArr[$country]) {

            return $id;
        }

        $country = str_replace(',' , ' , ', $country);

        $country = " {$country} ";

        foreach($commonNamesArr as $c => $id) {

            if(strpos($country, " {$c} ") !== FALSE) {
                
                if(strlen($c) > 3) {

                    return $id;
                }
            }
        }

        return FALSE;
    }
    
    
    /**
     * След като е готово вербалното представяне
     */
    public static function on_AfterGetVerbal($mvc, &$num, $rec, $part)
    {
        if (!Mode::is('forSearch')) return ;
        
        if ($part != 'commonName' && $part != 'commonNameBg') return ;
        
        if ($part == 'commonName') {
            $num .= ' ' . self::getCountryName($rec->id, 'bg');
        } else {
            $num .= ' ' . self::getCountryName($rec->id, 'en');
        }
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        // Подготвяме пътя до файла с данните
        $file = "drdata/data/countrylist.csv";

        // Кои колонки ще вкарваме
        $fields = array(
            1 => "commonName",
            2 => "commonNameBg",
            3 => "formalName",
            4 => "type",
            6 => "sovereignty",
            7 => "capital",
            8 => "currencyCode",
            9 => "currencyName",
            10 => "telCode",
            11 => "letterCode2",
            12 => "letterCode3",
            13 => "isoNumber",
            14 => "domain"
        );
        
        // Импортираме данните от CSV файла. 
        // Ако той не е променян - няма да се импортират повторно
        $cntObj = csv_Lib::largeImportOnceFromZero($mvc, $file, $fields);

        // Записваме в лога вербалното представяне на резултата от импортирането
        $res .= $cntObj->html;
    }
}
