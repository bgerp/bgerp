<?php



/**
 * Клас 'drdata_Countries' -
 *
 *
 * @category  vendors
 * @package   drdata
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class drdata_Countries extends core_Manager {
    
    
    /**
     * Заглавие
     */
    var $title = 'ISO информация за страните по света';
    
    
    /**
     * @todo Чака за документация...
     */
    var $recTitleTpl = '[#commonName#]';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'drdata_Wrapper,plg_Sorting';
    

    /**
     * Масив за съхранение на съответствието Държава => Езици
     */
    static $countryToLanguages = array();

	
    /**
     * Списък с кодовете на държавите от европейския съюз
     */
    static $euCountries = array('BE','BG','CY','CZ','DK','EE','EL','DE','PT','FR','FI','HU','LU','MT','SI','IE','IT','LV','LT','NL','PL','SK','RO','SE','ES','GB', 'AT');
    
    
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
        $this->FNC('languages', 'varchar(2)', 'caption=Езици');
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
        
        $me = cls::get(__CLASS__);
        
        $id = $me->fetchField("#letterCode2 = '{$cCode2}'", 'id');
        
        return $id;
    }
    
    
    /**
     * Изпълнява се преди запис в модела
     * Премахва не-цифровите символи в кода
     */
    static function on_BeforeSave($mvc, $id, $rec)
    {
        $rec->telCode = preg_replace('/[^0-9]+/', '', $rec->telCode);
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
        $cntObj = csv_Lib::importOnce($mvc, $file, $fields);

        // Записваме в лога вербалното представяне на резултата от импортирането
        $res .= $cntObj->html;
    }

    public static function getIdByName($country)
    {
        $canonicNames = array(
            'malta' => "malta",
            'austria' => "austria",
            'germany' => "germany",
            'france' => "france",
            'sweden' => "sweden",
            'switzerland' => "switzerland",
            'italy' => "italy",
            'netherlands' => "netherlands",
            'greece' => "greece",
            'uk and spain' => "united kingdom",
            'macedonia' => "macedonia",
            'cyprus' => "cyprus",
            'the netherlands' => "netherlands",
            'belgium' => "belgium",
            'nederlands' => "netherlands",
            'spain' => "spain",
            'german' => "germany",
            'latvia' => "latvia",
            'norway' => "norway",
            'denmark' => "denmark",
            'denmark' => "denmark",
            'skopje macedonia' => "macedonia",
            'united kingdom' => "united kingdom",
            'ireland' => "ireland",
            'romania' => "romania",
            'norway' => "norway",
            'malta-europe' => "malta",
            'reggio emilia italia' => "italy",
            'lithuania' => "lithuania",
            'portugal' => "portugal",
            'czech republic' => "czech republic",
            'england' => "united kingdom",
            'luxembourg' => "luxembourg",
            'hungary' => "hungary",
            'slovenia' => "slovenia",
            'bosnia and hercegowina' => "bosnia and hercegowina",
            'roamnia' => "romania",
            'croatia' => "croatia",
            'uk' => "united kingdom",
            'estonia' => "estonia",
            'untited kingdom' => "united kingdom",
            'bosnia and herzegovina' => "bosnia and herzegovina",
            'slovakia (slovak republic)' => "slovakia",
            'finland' => "finland",
            'sewden' => "sweden",
            'italia' => "italy",
            '08630 abrera-barcelona (spain)' => "spain",
            'yugoslavia' => "yugoslavia",
            'monaco' => "monaco",
            'slovakia' => "slovakia",
            'scotland' => "scotland",
            'ch 8575 bürglen' => "switzerland",
            'belgique' => "belgium",
            'uk' => "united kingdom",
            'kosovo' => "kosovo",
            'nederland' => "netherlands",
            'slowakische republik' => "slovakia",
            'switzerland/italy' => "switzerland",
            'italy' => "italy",
            'hellas' => "greece",
            'r. macedonia' => "macedonia",
            'iceland' => "iceland",
            'italia' => "italy",
            'switerland' => "switzerland",
            'mallorca - spain' => "spain",
            'greece ltd.' => 'greece',
            'castelnuovo della daunia (fg)it.' => 'italy',
            'be' => "belgium",
            'greeece' => "greece",
            'north ireland' => "north ireland",
            'serbia' => "serbia",
            'torino ( italia )' => "italy",
            'españa' => "spain",
            'bry sur marne, france' => "france",
            'malta' => "malta",
            'slovak republic' => "slovakia",
            'belgium' => "belgium",
            'germany' => "germany",
            'greece thesaloniki' => "greece",
            'athens greece' => "greece",
            'bonorva (ss) italy' => "italy",
            'torino' => "italy",
            'france' => "france",
            'united kongdom' => "united kingdom",
            'grèce' => "greece",
            'holland' => "netherlands",
            'andorra' => "andorra",
            'belgium' => "belgium",
            'italy' => "italy",
            'cz' => "czech republic",
            'san marino' => "san marino",
            'gemany' => "germany",
            'norvège' => "norway",
            'lithuania' => "lithuania",
            'ittaly' => "italy",
            'finand' => "finland",
            'spain' => "spain",
            'greese' => "greece",
            'slovenija' => "slovenia",
            'latvia' => "latvia",
            'ireland' => "ireland",
            'czech  republic' => "",
            'czech rep.' => "czech republic",
            'swiss' => "switzerland",
            'northern ireland' => "north ireland",
            '37200 tours, france' => "france",
            'netherlands' => "netherlands",
            'charleville- france' => "france",
            'estona' => "estonia",
            'creece' => "greece",
            'svitzerland' => "switzerland",
            'switzerland' => "switzerland",
            'latvijas republika' => "latvia",
            'brugherio (mi) italia' => "italy",
            'savona - italiy' => "italy",
            'холандия' => "netherlands",
            'македонија' => "macedonia",
            'grottaglie (ta) - italia' => "italy",
            'lituanie' => "lithuania",
            'румъния' => "romaina",
            'france' => "france",
            'francе' => "france",
            'yougoslavie' => "yugoslavia",
            'danmark' => "denmark",
            'stornara(fg)italia' => "italy",
            'united kingdom, england' => "united kingdom",
            'greece' => "greece",
            'iraland' => "ireland",
            'builgaria and uk' => "united kingdom",
            'portugal' => "portugal",
            'norawy' => "norway",
            'liechtenstein' => "liechtenstein",
            'roumanie' => "romania",
            'romania' => "romania",
            'slowenia' => "slovenia",
            'belgium.' => "belgium",
            'luxemburg' => "luxembourg",
            'slovenska republika' => "slovenia",
            'svizzera' => "switzerland",
            'slovaquie' => "slovakia",
            'marseille france' => "france",
            'marseillr france' => "france",
            'd' => "germany",
            '1264 københavn k - denmark' => "denmark",
            'chez republic' => "czech republic",
            'deutschland' => "germany",
            'greek' => "greece",
            'espana' => "spain",
            'lille france' => "france",
            '2791 dragoer denmark' => "denmark",
            'lithuania, central europe' => "lithuania",
            'macedonia' => "macedonia",
            'swizerland' => "switzerland",
            'makedonija' => "macedonia" 
        );

        $country = mb_strtolower($country);

        if($canonicalNames[$country]) {
            $country = $canonicalNames[$country];
        }

        $rec = self::fetch(array("LOWER(#formalName) LIKE '[#1#]'", $country));

        if(!$rec) {
            $rec = self::fetch(array("LOWER(#commonName) LIKE '[#1#]'", $country));
        }
        
        if(!$rec) {
            $rec = self::fetch(array("LOWER(#commonNameBg) LIKE '[#1#]'", $country));
        }
        
        if(!$rec) {
            $rec = self::fetch(array("LOWER(#commonName) LIKE '%[#1#]'", $country));
        }
        
        if(!$rec) {
            $rec = self::fetch(array("LOWER(#commonNameBg) LIKE '%[#1#]'", $country));
        }
        
        if(!$rec) {
            $rec = self::fetch(array("LOWER(#formalName) LIKE '%[#1#]'", $country));
        }
        if(!$rec) {
            $rec = self::fetch(array("LOWER(#commonName) LIKE '%[#1#]%'", $country));
        }
        
        if(!$rec) {
            $rec = self::fetch(array("LOWER(#commonNameBg) LIKE '%[#1#]%'", $country));
        }
        
        if(!$rec) {
            $rec = self::fetch(array("LOWER(#formalName) LIKE '%[#1#]%'", $country));
        }
        
        if(!$rec) {
            $rec = self::fetch(array("LOWER(#letterCode2) LIKE '[#1#]'", $country));
        }
        
        if(!$rec) {
            $rec = self::fetch(array("LOWER(#letterCode3) LIKE '[#1#]'", $country));
        }

        if($rec) {

            return $rec->id;
        }

    }
}
