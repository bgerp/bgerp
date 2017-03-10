<?php



/**
 * Клас 'core_Url' ['url'] - Функции за за работа със URL
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Url
{
    
	 // От http://data.iana.org/TLD/tlds-alpha-by-domain.txt
     static $valideTld = array('aaa', 'aarp', 'abarth', 'abb', 'abbott', 'abbvie', 'abc', 'able', 'abogado', 
                               'abudhabi', 'ac', 'academy', 'accenture', 'accountant', 'accountants', 'aco', 
                               'active', 'actor', 'ad', 'adac', 'ads', 'adult', 'ae', 'aeg', 'aero', 'aetna', 'af', 'afamilycompany', 
                               'afl', 'africa', 'ag', 'agakhan', 'agency', 'ai', 'aig', 'aigo', 'airbus', 'airforce', 'airtel', 'akdn', 
                               'al', 'alfaromeo', 'alibaba', 'alipay', 'allfinanz', 'allstate', 'ally', 'alsace', 'alstom', 'am', 'americanexpress', 
                               'americanfamily', 'amex', 'amfam', 'amica', 'amsterdam', 'analytics', 'android', 'anquan', 'anz', 'ao', 'aol', 
                               'apartments', 'app', 'apple', 'aq', 'aquarelle', 'ar', 'aramco', 'archi', 'army', 'arpa', 'art', 'arte', 
                               'as', 'asda', 'asia', 'associates', 'at', 'athleta', 'attorney', 'au', 'auction', 'audi', 'audible', 
                               'audio', 'auspost', 'author', 'auto', 'autos', 'avianca', 'aw', 'aws', 'ax', 'axa', 'az', 'azure', 
                               'ba', 'baby', 'baidu', 'banamex', 'bananarepublic', 'band', 'bank', 'bar', 'barcelona', 'barclaycard', 'barclays', 
                               'barefoot', 'bargains', 'baseball', 'basketball', 'bauhaus', 'bayern', 'bb', 'bbc', 'bbt', 'bbva', 'bcg', 'bcn', 
                               'bd', 'be', 'beats', 'beauty', 'beer', 'bentley', 'berlin', 'best', 'bestbuy', 'bet', 'bf', 'bg', 'bh', 'bharti', 
                               'bi', 'bible', 'bid', 'bike', 'bing', 'bingo', 'bio', 'biz', 'bj', 'black', 'blackfriday', 'blanco', 'blockbuster', 
                               'blog', 'bloomberg', 'blue', 'bm', 'bms', 'bmw', 'bn', 'bnl', 'bnpparibas', 'bo', 'boats', 'boehringer', 'bofa', 
                               'bom', 'bond', 'boo', 'book', 'booking', 'boots', 'bosch', 'bostik', 'boston', 'bot', 'boutique', 'box', 'br', 
                               'bradesco', 'bridgestone', 'broadway', 'broker', 'brother', 'brussels', 'bs', 'bt', 'budapest', 'bugatti', 
                               'build', 'builders', 'business', 'buy', 'buzz', 'bv', 'bw', 'by', 'bz', 'bzh', 'ca', 'cab', 'cafe', 'cal', 'call', 
                               'calvinklein', 'cam', 'camera', 'camp', 'cancerresearch', 'canon', 'capetown', 'capital', 'capitalone', 'car', 
                               'caravan', 'cards', 'care', 'career', 'careers', 'cars', 'cartier', 'casa', 'case', 'caseih', 'cash', 'casino', 
                               'cat', 'catering', 'catholic', 'cba', 'cbn', 'cbre', 'cbs', 'cc', 'cd', 'ceb', 'center', 'ceo', 'cern', 'cf', 
                               'cfa', 'cfd', 'cg', 'ch', 'chanel', 'channel', 'chase', 'chat', 'cheap', 'chintai', 'chloe', 'christmas', 
                               'chrome', 'chrysler', 'church', 'ci', 'cipriani', 'circle', 'cisco', 'citadel', 'citi', 'citic', 'city', 
                               'cityeats', 'ck', 'cl', 'claims', 'cleaning', 'click', 'clinic', 'clinique', 'clothing', 'cloud', 'club', 
                               'clubmed', 'cm', 'cn', 'co', 'coach', 'codes', 'coffee', 'college', 'cologne', 'com', 'comcast', 'commbank', 
                               'community', 'company', 'compare', 'computer', 'comsec', 'condos', 'construction', 'consulting', 'contact', 
                               'contractors', 'cooking', 'cookingchannel', 'cool', 'coop', 'corsica', 'country', 'coupon', 'coupons', 
                               'courses', 'cr', 'credit', 'creditcard', 'creditunion', 'cricket', 'crown', 'crs', 'cruise', 'cruises', 
                               'csc', 'cu', 'cuisinella', 'cv', 'cw', 'cx', 'cy', 'cymru', 'cyou', 'cz', 'dabur', 'dad', 'dance', 'data', 
                               'date', 'dating', 'datsun', 'day', 'dclk', 'dds', 'de', 'deal', 'dealer', 'deals', 'degree', 'delivery', 
                               'dell', 'deloitte', 'delta', 'democrat', 'dental', 'dentist', 'desi', 'design', 'dev', 'dhl', 'diamonds', 
                               'diet', 'digital', 'direct', 'directory', 'discount', 'discover', 'dish', 'diy', 'dj', 'dk', 'dm', 'dnp', 
                               'do', 'docs', 'doctor', 'dodge', 'dog', 'doha', 'domains', 'dot', 'download', 'drive', 'dtv', 'dubai', 
                               'duck', 'dunlop', 'duns', 'dupont', 'durban', 'dvag', 'dvr', 'dz', 'earth', 'eat', 'ec', 'eco', 'edeka', 
                               'edu', 'education', 'ee', 'eg', 'email', 'emerck', 'energy', 'engineer', 'engineering', 'enterprises', 
                               'epost', 'epson', 'equipment', 'er', 'ericsson', 'erni', 'es', 'esq', 'estate', 'esurance', 'et', 'eu', 
                               'eurovision', 'eus', 'events', 'everbank', 'exchange', 'expert', 'exposed', 'express', 'extraspace', 
                               'fage', 'fail', 'fairwinds', 'faith', 'family', 'fan', 'fans', 'farm', 'farmers', 'fashion', 'fast', 
                               'fedex', 'feedback', 'ferrari', 'ferrero', 'fi', 'fiat', 'fidelity', 'fido', 'film', 'final', 'finance', 
                               'financial', 'fire', 'firestone', 'firmdale', 'fish', 'fishing', 'fit', 'fitness', 'fj', 'fk', 'flickr', 
                               'flights', 'flir', 'florist', 'flowers', 'fly', 'fm', 'fo', 'foo', 'food', 'foodnetwork', 'football', 
                               'ford', 'forex', 'forsale', 'forum', 'foundation', 'fox', 'fr', 'free', 'fresenius', 'frl', 'frogans', 
                               'frontdoor', 'frontier', 'ftr', 'fujitsu', 'fujixerox', 'fun', 'fund', 'furniture', 'futbol', 'fyi', 
                               'ga', 'gal', 'gallery', 'gallo', 'gallup', 'game', 'games', 'gap', 'garden', 'gb', 'gbiz', 'gd', 'gdn', 
                               'ge', 'gea', 'gent', 'genting', 'george', 'gf', 'gg', 'ggee', 'gh', 'gi', 'gift', 'gifts', 'gives', 
                               'giving', 'gl', 'glade', 'glass', 'gle', 'global', 'globo', 'gm', 'gmail', 'gmbh', 'gmo', 'gmx', 
                               'gn', 'godaddy', 'gold', 'goldpoint', 'golf', 'goo', 'goodhands', 'goodyear', 'goog', 'google', 
                               'gop', 'got', 'gov', 'gp', 'gq', 'gr', 'grainger', 'graphics', 'gratis', 'green', 'gripe', 'group', 
                               'gs', 'gt', 'gu', 'guardian', 'gucci', 'guge', 'guide', 'guitars', 'guru', 'gw', 'gy', 'hair', 
                               'hamburg', 'hangout', 'haus', 'hbo', 'hdfc', 'hdfcbank', 'health', 'healthcare', 'help', 'helsinki', 
                               'here', 'hermes', 'hgtv', 'hiphop', 'hisamitsu', 'hitachi', 'hiv', 'hk', 'hkt', 'hm', 'hn', 'hockey', 
                               'holdings', 'holiday', 'homedepot', 'homegoods', 'homes', 'homesense', 'honda', 'honeywell', 
                               'horse', 'hospital', 'host', 'hosting', 'hot', 'hoteles', 'hotmail', 'house', 'how', 'hr', 
                               'hsbc', 'ht', 'htc', 'hu', 'hughes', 'hyatt', 'hyundai', 'ibm', 'icbc', 'ice', 'icu', 'id', 
                               'ie', 'ieee', 'ifm', 'ikano', 'il', 'im', 'imamat', 'imdb', 'immo', 'immobilien', 'in', 
                               'industries', 'infiniti', 'info', 'ing', 'ink', 'institute', 'insurance', 'insure', 'int', 'intel', 
                               'international', 'intuit', 'investments', 'io', 'ipiranga', 'iq', 'ir', 'irish', 'is', 'iselect', 
                               'ismaili', 'ist', 'istanbul', 'it', 'itau', 'itv', 'iveco', 'iwc', 'jaguar', 'java', 'jcb', 'jcp', 
                               'je', 'jeep', 'jetzt', 'jewelry', 'jio', 'jlc', 'jll', 'jm', 'jmp', 'jnj', 'jo', 'jobs', 'joburg', 
                               'jot', 'joy', 'jp', 'jpmorgan', 'jprs', 'juegos', 'juniper', 'kaufen', 'kddi', 'ke', 'kerryhotels', 
                               'kerrylogistics', 'kerryproperties', 'kfh', 'kg', 'kh', 'ki', 'kia', 'kim', 'kinder', 'kindle', 
                               'kitchen', 'kiwi', 'km', 'kn', 'koeln', 'komatsu', 'kosher', 'kp', 'kpmg', 'kpn', 'kr', 'krd', 'kred', 
                               'kuokgroup', 'kw', 'ky', 'kyoto', 'kz', 'la', 'lacaixa', 'ladbrokes', 'lamborghini', 'lamer', 
                               'lancaster', 'lancia', 'lancome', 'land', 'landrover', 'lanxess', 'lasalle', 'lat', 'latino', 'latrobe', 
                               'law', 'lawyer', 'lb', 'lc', 'lds', 'lease', 'leclerc', 'lefrak', 'legal', 'lego', 'lexus', 'lgbt', 'li', 
                               'liaison', 'lidl', 'life', 'lifeinsurance', 'lifestyle', 'lighting', 'like', 'lilly', 'limited', 'limo', 
                               'lincoln', 'linde', 'link', 'lipsy', 'live', 'living', 'lixil', 'lk', 'loan', 'loans', 'locker', 'locus', 
                               'loft', 'lol', 'london', 'lotte', 'lotto', 'love', 'lpl', 'lplfinancial', 'lr', 'ls', 'lt', 'ltd', 'ltda', 
                               'lu', 'lundbeck', 'lupin', 'luxe', 'luxury', 'lv', 'ly', 'ma', 'macys', 'madrid', 'maif', 'maison', 'makeup', 
                               'man', 'management', 'mango', 'market', 'marketing', 'markets', 'marriott', 'marshalls', 'maserati', 'mattel', 
                               'mba', 'mc', 'mcd', 'mcdonalds', 'mckinsey', 'md', 'me', 'med', 'media', 'meet', 'melbourne', 'meme', 
                               'memorial', 'men', 'menu', 'meo', 'metlife', 'mg', 'mh', 'miami', 'microsoft', 'mil', 'mini', 'mint', 
                               'mit', 'mitsubishi', 'mk', 'ml', 'mlb', 'mls', 'mm', 'mma', 'mn', 'mo', 'mobi', 'mobile', 'mobily', 
                               'moda', 'moe', 'moi', 'mom', 'monash', 'money', 'monster', 'montblanc', 'mopar', 'mormon', 'mortgage', 
                               'moscow', 'moto', 'motorcycles', 'mov', 'movie', 'movistar', 'mp', 'mq', 'mr', 'ms', 'msd', 'mt', 'mtn', 
                               'mtpc', 'mtr', 'mu', 'museum', 'mutual', 'mv', 'mw', 'mx', 'my', 'mz', 'na', 'nab', 'nadex', 'nagoya', 
                               'name', 'nationwide', 'natura', 'navy', 'nba', 'nc', 'ne', 'nec', 'net', 'netbank', 'netflix', 'network', 
                               'neustar', 'new', 'newholland', 'news', 'next', 'nextdirect', 'nexus', 'nf', 'nfl', 'ng', 'ngo', 'nhk', 
                               'ni', 'nico', 'nike', 'nikon', 'ninja', 'nissan', 'nissay', 'nl', 'no', 'nokia', 'northwesternmutual', 
                               'norton', 'now', 'nowruz', 'nowtv', 'np', 'nr', 'nra', 'nrw', 'ntt', 'nu', 'nyc', 'nz', 'obi', 'observer', 
                               'off', 'office', 'okinawa', 'olayan', 'olayangroup', 'oldnavy', 'ollo', 'om', 'omega', 'one', 'ong', 
                               'onl', 'online', 'onyourside', 'ooo', 'open', 'oracle', 'orange', 'org', 'organic', 'orientexpress', 
                               'origins', 'osaka', 'otsuka', 'ott', 'ovh', 'pa', 'page', 'pamperedchef', 'panasonic', 'panerai', 
                               'paris', 'pars', 'partners', 'parts', 'party', 'passagens', 'pay', 'pccw', 'pe', 'pet', 'pf', 'pfizer', 
                               'pg', 'ph', 'pharmacy', 'philips', 'phone', 'photo', 'photography', 'photos', 'physio', 'piaget', 
                               'pics', 'pictet', 'pictures', 'pid', 'pin', 'ping', 'pink', 'pioneer', 'pizza', 'pk', 'pl', 'place', 
                               'play', 'playstation', 'plumbing', 'plus', 'pm', 'pn', 'pnc', 'pohl', 'poker', 'politie', 'porn', 
                               'post', 'pr', 'pramerica', 'praxi', 'press', 'prime', 'pro', 'prod', 'productions', 'prof', 
                               'progressive', 'promo', 'properties', 'property', 'protection', 'pru', 'prudential', 'ps', 'pt', 
                               'pub', 'pw', 'pwc', 'py', 'qa', 'qpon', 'quebec', 'quest', 'qvc', 'racing', 'radio', 'raid', 're', 
                               'read', 'realestate', 'realtor', 'realty', 'recipes', 'red', 'redstone', 'redumbrella', 'rehab', 
                               'reise', 'reisen', 'reit', 'reliance', 'ren', 'rent', 'rentals', 'repair', 'report', 'republican', 
                               'rest', 'restaurant', 'review', 'reviews', 'rexroth', 'rich', 'richardli', 'ricoh', 'rightathome', 
                               'ril', 'rio', 'rip', 'rmit', 'ro', 'rocher', 'rocks', 'rodeo', 'rogers', 'room', 'rs', 'rsvp', 
                               'ru', 'ruhr', 'run', 'rw', 'rwe', 'ryukyu', 'sa', 'saarland', 'safe', 'safety', 'sakura', 'sale', 
                               'salon', 'samsclub', 'samsung', 'sandvik', 'sandvikcoromant', 'sanofi', 'sap', 'sapo', 'sarl', 
                               'sas', 'save', 'saxo', 'sb', 'sbi', 'sbs', 'sc', 'sca', 'scb', 'schaeffler', 'schmidt', 'scholarships', 
                               'school', 'schule', 'schwarz', 'science', 'scjohnson', 'scor', 'scot', 'sd', 'se', 'seat', 'secure', 
                               'security', 'seek', 'select', 'sener', 'services', 'ses', 'seven', 'sew', 'sex', 'sexy', 'sfr', 'sg', 
                               'sh', 'shangrila', 'sharp', 'shaw', 'shell', 'shia', 'shiksha', 'shoes', 'shop', 'shopping', 'shouji', 
                               'show', 'showtime', 'shriram', 'si', 'silk', 'sina', 'singles', 'site', 'sj', 'sk', 'ski', 'skin', 'sky', 
                               'skype', 'sl', 'sling', 'sm', 'smart', 'smile', 'sn', 'sncf', 'so', 'soccer', 'social', 'softbank', 
                               'software', 'sohu', 'solar', 'solutions', 'song', 'sony', 'soy', 'space', 'spiegel', 'spot', 'spreadbetting', 
                               'sr', 'srl', 'srt', 'st', 'stada', 'staples', 'star', 'starhub', 'statebank', 'statefarm', 'statoil', 
                               'stc', 'stcgroup', 'stockholm', 'storage', 'store', 'stream', 'studio', 'study', 'style', 'su', 'sucks', 
                               'supplies', 'supply', 'support', 'surf', 'surgery', 'suzuki', 'sv', 'swatch', 'swiftcover', 'swiss', 
                               'sx', 'sy', 'sydney', 'symantec', 'systems', 'sz', 'tab', 'taipei', 'talk', 'taobao', 'target', 
                               'tatamotors', 'tatar', 'tattoo', 'tax', 'taxi', 'tc', 'tci', 'td', 'tdk', 'team', 'tech', 
                               'technology', 'tel', 'telecity', 'telefonica', 'temasek', 'tennis', 'teva', 'tf', 'tg', 'th', 
                               'thd', 'theater', 'theatre', 'tiaa', 'tickets', 'tienda', 'tiffany', 'tips', 'tires', 'tirol', 
                               'tj', 'tjmaxx', 'tjx', 'tk', 'tkmaxx', 'tl', 'tm', 'tmall', 'tn', 'to', 'today', 'tokyo', 'tools', 
                               'top', 'toray', 'toshiba', 'total', 'tours', 'town', 'toyota', 'toys', 'tr', 'trade', 'trading', 
                               'training', 'travel', 'travelchannel', 'travelers', 'travelersinsurance', 'trust', 'trv', 'tt', 
                               'tube', 'tui', 'tunes', 'tushu', 'tv', 'tvs', 'tw', 'tz', 'ua', 'ubank', 'ubs', 'uconnect', 'ug', 
                               'uk', 'unicom', 'university', 'uno', 'uol', 'ups', 'us', 'uy', 'uz', 'va', 'vacations', 'vana', 
                               'vanguard', 'vc', 've', 'vegas', 'ventures', 'verisign', 'versicherung', 'vet', 'vg', 'vi', 'viajes', 
                               'video', 'vig', 'viking', 'villas', 'vin', 'vip', 'virgin', 'visa', 'vision', 'vista', 'vistaprint', 
                               'viva', 'vivo', 'vlaanderen', 'vn', 'vodka', 'volkswagen', 'volvo', 'vote', 'voting', 'voto', 'voyage', 
                               'vu', 'vuelos', 'wales', 'walmart', 'walter', 'wang', 'wanggou', 'warman', 'watch', 'watches', 
                               'weather', 'weatherchannel', 'webcam', 'weber', 'website', 'wed', 'wedding', 'weibo', 'weir', 
                               'wf', 'whoswho', 'wien', 'wiki', 'williamhill', 'win', 'windows', 'wine', 'winners', 'wme', 
                               'wolterskluwer', 'woodside', 'work', 'works', 'world', 'wow', 'ws', 'wtc', 'wtf', 'xbox', 
                               'xerox', 'xfinity', 'xihuan', 'xin', 'xn--11b4c3d', 'xn--1ck2e1b', 'xn--1qqw23a', 'xn--30rr7y', 
                               'xn--3bst00m', 'xn--3ds443g', 'xn--3e0b707e', 'xn--3oq18vl8pn36a', 'xn--3pxu8k', 'xn--42c2d9a', 
                               'xn--45brj9c', 'xn--45q11c', 'xn--4gbrim', 'xn--54b7fta0cc', 'xn--55qw42g', 'xn--55qx5d', 
                               'xn--5su34j936bgsg', 'xn--5tzm5g', 'xn--6frz82g', 'xn--6qq986b3xl', 'xn--80adxhks', 'xn--80ao21a', 
                               'xn--80aqecdr1a', 'xn--80asehdb', 'xn--80aswg', 'xn--8y0a063a', 'xn--90a3ac', 'xn--90ae', 
                               'xn--90ais', 'xn--9dbq2a', 'xn--9et52u', 'xn--9krt00a', 'xn--b4w605ferd', 'xn--bck1b9a5dre4c', 
                               'xn--c1avg', 'xn--c2br7g', 'xn--cck2b3b', 'xn--cg4bki', 'xn--clchc0ea0b2g2a9gcd', 'xn--czr694b', 
                               'xn--czrs0t', 'xn--czru2d', 'xn--d1acj3b', 'xn--d1alf', 'xn--e1a4c', 'xn--eckvdtc9d', 'xn--efvy88h', 
                               'xn--estv75g', 'xn--fct429k', 'xn--fhbei', 'xn--fiq228c5hs', 'xn--fiq64b', 'xn--fiqs8s', 
                               'xn--fiqz9s', 'xn--fjq720a', 'xn--flw351e', 'xn--fpcrj9c3d', 'xn--fzc2c9e2c', 'xn--fzys8d69uvgm', 
                               'xn--g2xx48c', 'xn--gckr3f0f', 'xn--gecrj9c', 'xn--gk3at1e', 'xn--h2brj9c', 'xn--hxt814e', 
                               'xn--i1b6b1a6a2e', 'xn--imr513n', 'xn--io0a7i', 'xn--j1aef', 'xn--j1amh', 'xn--j6w193g', 
                               'xn--jlq61u9w7b', 'xn--jvr189m', 'xn--kcrx77d1x4a', 'xn--kprw13d', 'xn--kpry57d', 'xn--kpu716f', 
                               'xn--kput3i', 'xn--l1acc', 'xn--lgbbat1ad8j', 'xn--mgb9awbf', 'xn--mgba3a3ejt', 
                               'xn--mgba3a4f16a', 'xn--mgba7c0bbn0a', 'xn--mgbaam7a8h', 'xn--mgbab2bd', 'xn--mgbai9azgqp6j', 
                               'xn--mgbayh7gpa', 'xn--mgbb9fbpob', 'xn--mgbbh1a71e', 'xn--mgbc0a9azcg', 'xn--mgbca7dzdo', 
                               'xn--mgberp4a5d4ar', 'xn--mgbi4ecexp', 'xn--mgbpl2fh', 'xn--mgbt3dhd', 'xn--mgbtx2b', 
                               'xn--mgbx4cd0ab', 'xn--mix891f', 'xn--mk1bu44c', 'xn--mxtq1m', 'xn--ngbc5azd', 'xn--ngbe9e0a', 
                               'xn--node', 'xn--nqv7f', 'xn--nqv7fs00ema', 'xn--nyqy26a', 'xn--o3cw4h', 'xn--ogbpf8fl',
                               'xn--p1acf', 'xn--p1ai', 'xn--pbt977c', 'xn--pgbs0dh', 'xn--pssy2u', 'xn--q9jyb4c', 'xn--qcka1pmc', 
                               'xn--qxam', 'xn--rhqv96g', 'xn--rovu88b', 'xn--s9brj9c', 'xn--ses554g', 'xn--t60b56a', 
                               'xn--tckwe', 'xn--tiq49xqyj', 'xn--unup4y', 'xn--vermgensberater-ctb', 'xn--vermgensberatung-pwb', 
                               'xn--vhquv', 'xn--vuq861b', 'xn--w4r85el8fhu5dnra', 'xn--w4rs40l', 'xn--wgbh1c', 'xn--wgbl6a', 
                               'xn--xhq521b', 'xn--xkc2al3hye2a', 'xn--xkc2dl3a5ee0h', 'xn--y9a3aq', 'xn--yfro4i67o', 'xn--ygbi2ammx', 
                               'xn--zfr164b', 'xperia', 'xxx', 'xyz', 'yachts', 'yahoo', 'yamaxun', 'yandex', 'ye', 'yodobashi', 
                               'yoga', 'yokohama', 'you', 'youtube', 'yt', 'yun', 'za', 'zappos', 'zara', 'zero', 'zip', 'zippo', 
                               'zm', 'zone', 'zuerich', 'zw');
    
    /**
     * @todo Чака за документация...
     */
    static function parseUrl(&$url)
    {
        if (strlen($url) <= 300) {
            $r = "(?:([a-z0-9+-._]+)://)?";
            $r .= "(?:";
            $r .= "(?:((?:[a-z0-9-._~!$&'()*+,;=:]|%[0-9a-f]{2})*)@)?";
            $r .= "(?:\[((?:[a-z0-9:])*)\])?";
            $r .= "((?:[a-z0-9-._~!$&'()*+,;=]|%[0-9a-f]{2})*)";
            $r .= "(?::(\d*))?";
            $r .= "(/(?:[a-z0-9-._~!$&'()*+,;=:@/]|%[0-9a-f]{2})*)?";
            $r .= "|";
            $r .= "(/?";
            $r .= "(?:[a-z0-9-._~!$&'()*+,;=:@]|%[0-9a-f]{2})+";
            $r .= "(?:[a-z0-9-._~!$&'()*+,;=:@\/]|%[0-9a-f]{2})*";
            $r .= ")?";
            $r .= ")";
            $r .= "(?:\?((?:[a-z0-9-._~!$&'()*+,;=:\/?@]|%[0-9a-f]{2})*))?";
            $r .= "(?:#((?:[a-z0-9-._~!$&'()*+,;=:\/?@]|%[0-9a-f]{2})*))?";
            
            preg_match("`$r`i", $url, $match);
            
            $parts = array(
                "scheme" => '',
                "userinfo" => '',
                "authority" => '',
                "host" => '',
                "port" => '',
                "path" => '',
                "query" => '',
                "fragment" => ''
            );
            
            switch (count($match)) {
                case 10 :
                    $parts['fragment'] = $match[9];
                case 9 :
                    $parts['query'] = $match[8];
                case 8 :
                    $parts['path'] = $match[7];
                case 7 :
                    $parts['path'] = $match[6] . $parts['path'];
                case 6 :
                    $parts['port'] = $match[5];
                case 5 :
                    $parts['host'] = $match[3] ? "[" . $match[3] . "]" : $match[4];
                case 4 :
                    $parts['userinfo'] = $match[2];
                case 3 :
                    $parts['scheme'] = $match[1];
            }
            $parts['authority'] = ($parts['userinfo'] ? $parts['userinfo'] . "@" : "") . $parts['host'] . ($parts['port'] ? ":" . $parts['port'] : "");
        } else {
            $parts = parse_url($url);
        }
        
        if ($parts['query']) {
            $parts['query_params'] = array();
            $aPairs = explode('&', $parts['query']);
            
            foreach ($aPairs as $sPair) {
                if (trim($sPair) == '') {
                    continue;
                }
                list($sKey, $sValue) = explode('=', $sPair);
                $parts['query_params'][$sKey] = decodeUrl($sValue);
            }
        }
        
        if (empty($parts['scheme'])) {
            if (strpos($parts['host'], 'ftp') === 0) {
                $parts['scheme'] = 'ftp';
            } else {
                $parts['scheme'] = 'http';
            }
            
            $url = $parts['scheme'] . '://' . $url;
        }
        
        $parts['scheme'] = strtolower($parts['scheme']);
        
        if ($parts['host']) {
            $parts['host'] = strtolower($parts['host']);
            $domainPttr = "/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.(?P<tld>[a-z\.]{2,6}))$/i";
            
            if (preg_match($domainPttr, $parts['host'], $match)) {
                $parts['domain'] = $match['domain'];
                $parts['tld'] = strtolower($match['tld']);
            }
        }
        
        if ($parts['path']) {
            setIfNot($parts, pathInfo(decodeUrl($parts['path'])));
        }
        
        if (!core_URL::isValidUrl($url)) {
            $parts['error'] = "Невалидно URL";
        } elseif ($parts['tld'] && !in_array($parts['tld'], self::$valideTld)) {
            $parts['error'] = "Невалидно разширение на домейн|*: <b>" . $parts['tld'] . "</b>";
        }
        
        return $parts;
    }
    
    
    /**
     * Дали посоченото URL е частно (запазено за частна употреба от организации)?
     * 
     * @param string $url
     * 
     * @return boolean
     */
    public static function isPrivate($url)
    {
        $url = preg_replace('/^https?:\/\//', '', $url);
        
        $url = preg_replace('/^www\./', '', $url);
        
        if ($url == 'localhost') return TRUE;
        
        if (strpos($url, 'localhost:') === 0) return TRUE;
        
        if (strpos($url, 'localhost/') === 0) return TRUE;
            
        return type_IP::isPrivate($url);
    }
    

    /**
     * Проверява дали е валиден даден топ-левъл домейн
     * Ако в домейна има точка, се взема последното след точката
     */
    static function isValidTld($tld)
    {
        if(FALSE !== ($dotPos = strrpos($tld, '.'))) {
            $tld = substr($tld, $dotPos + 1);
        }

        $tld = strtolower($tld);

        if (in_array($tld, self::$valideTld)) {
        	   
        	return TRUE;
        }
       
        return FALSE;
    }
    
    /**
     * Проверява дали дадено URL е валидно
     */
    static function isValidUrl2($url)
    {
        // схема 
        $urlregex = "^([a-z0-9+\-\.\_]+)\:\/\/";
        
        // USER и PASS (опционално)
        $urlregex .= "([a-z0-9+\!\*\(\)\,\;\?\&=\$\_\.\-]+(\:[a-z0-9+\!\*\(\)\,\;\?\&\=\$\_\.\-]+)?\@)?";
        
        // HOSTNAME или IP
        $urlregex .= "[a-z0-9+\$\_\-]+(\.[a-z0-9+\$\_\-]+)*";     // http://x = allowed (ex. http://localhost, http://routerlogin)
        //$urlregex .= "[a-z0-9+\$_-]+(\.[a-z0-9+\$_-]+)+";  // http://x.x = minimum
        //$urlregex .= "([a-z0-9+\$_-]+\.)*[a-z0-9+\$_-]{2,3}";  // http://x.xx(x) = minimum
        //use only one of the above
        
        // PORT (опционално)
        $urlregex .= "(\:[0-9]{2,5})?";
        
        // PATH  (optional)
        $urlregex .= "(\/([a-z0-9+\%\$_\-]\.?)+)*\/?";
        
        // GET Query (optional)
        $urlregex .= "(\?[a-z+&\$_\.\-][a-z0-9\;\:\@\/\&\%\=+\$\_\.\-]*)?";
        
        // ANCHOR (optional)
        $urlregex .= "(#[a-z\_\.\-][a-z0-9+\$\_\.\-]*)?\$";
        
        $urlregex = '/' . $urlregex . '/iu';
        
        // check
        $res = preg_match($urlregex, $url) ? TRUE : FALSE;
        
        return $res;
    }
    
    
    /**
     * This function should only be used on actual URLs. It should not be used for
     * Drupal menu paths, which can contain arbitrary characters.
     * Valid values per RFC 3986.
     *
     * @param $url
     * The URL to verify.
     * TRUE if the URL is in a valid format.
     */
    static function isValidUrl($url, $absolute = TRUE)
    {
        if ($absolute) {
            $res = (bool) preg_match("/^" . # Start at the beginning of the text
                "(?:[a-z0-9+-._]+?):\/\/" . # Look for ftp, http, or https schemes
                "(?:" . # Userinfo (optional) which is typically
                "(?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*" . # a username or a username and password
                "(?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@" . # combination
                ")?" . "(?:" . "(?:[a-z0-9\-\.]|%[0-9a-f]{2})+" . # A domain name or a IPv4 address
                "|(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\])" . # or a well formed IPv6 address
                ")" . "(?::[0-9]+)?" . # Server port number (optional)
                "(?:[\/|\?]" . "(?:[\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})" . # The path and query (optional)
                "*)?" . "$/xi", $url, $m);
        } else {
            $res = preg_match("/^(?:[\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})+$/i", $url);
        }
        
        return $res;
    }
    
    
    /**
     * Link: http://www.bin-co.com/php/scripts/load/
     * Version : 3.00.A
     */
    static function loadURL($url, $options = array())
    {
    	// Фечва УРЛ с cUrl наподобяваща функционалност на file_get_contents()
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); 
        $ans = curl_exec($ch);
        curl_close($ch);
    	
        return ($ans);
        
        $default_options = array(
            'method' => 'get',
            'post_data' => FALSE,
            'return_info' => FALSE,
            'return_body' => TRUE,
            'cache' => FALSE,
            'referer' => '',
            'headers' => array(),
            'session' => FALSE,
            'session_close' => FALSE
        );
        
        // Sets the default options.
        foreach ($default_options as $opt => $value) {
            if (!isset($options[$opt]))
            $options[$opt] = $value;
        }
        
        $url_parts = parse_url($url);
        $ch = FALSE;
        $info = array( //Currently only supported by curl.
            'http_code' => 200
        );
        $response = '';
        
        $send_header = array(
            'Accept' => 'text/*',
            'User-Agent' => 'BinGet/1.00.A (http://www.bin-co.com/php/scripts/load/)'
        ) + $options['headers'];     // Add custom headers provided by the user.
        if ($options['cache']) {
            $cache_folder = joinPath(sys_get_temp_dir(), 'php-load-function');
            
            if (isset($options['cache_folder']))
            $cache_folder = $options['cache_folder'];
            
            if (!file_exists($cache_folder)) {
                $old_umask = umask(0);     // Or the folder will not get write permission for everybody.
                mkdir($cache_folder, 0777);
                umask($old_umask);
            }
            
            $cache_file_name = md5($url) . '.cache';
            $cache_file = joinPath($cache_folder, $cache_file_name);     //Don't change the variable name - used at the end of the function.
            if (file_exists($cache_file)) { // Cached file exists - return that.
                $response = file_get_contents($cache_file);
                
                //Seperate header and content
                $separator_position = strpos($response, "\r\n\r\n");
                $header_text = substr($response, 0, $separator_position);
                $body = substr($response, $separator_position + 4);
                
                foreach (explode("\n", $header_text) as $line) {
                    $parts = explode(": ", $line);
                    
                    if (count($parts) == 2)
                    $headers[$parts[0]] = chop($parts[1]);
                }
                $headers['cached'] = TRUE;
                
                if (!$options['return_info'])
                return $body;
                else
                return array(
                    'headers' => $headers,
                    'body' => $body,
                    'info' => array(
                        'cached' => TRUE
                    )
                );
            }
        }
        
        if (isset($options['post_data'])) { //There is an option to specify some data to be posted.
            $options['method'] = 'post';
            
            if (is_array($options['post_data'])) { //The data is in array format.
                $post_data = array();
                
                foreach ($options['post_data'] as $key => $value) {
                    $post_data[] = "$key=" . urlencode($value);
                }
                $url_parts['query'] = implode('&', $post_data);
            } else { //Its a string
                $url_parts['query'] = $options['post_data'];
            }
        } elseif (isset($options['multipart_data'])) { //There is an option to specify some data to be posted.
            $options['method'] = 'post';
            $url_parts['query'] = $options['multipart_data'];
            
            /*
            This array consists of a name-indexed set of options.
            For example,
            'name' => array('option' => value)
            Available options are:
            filename: the name to report when uploading a file.
            type: the mime type of the file being uploaded (not used with curl).
            binary: a flag to tell the other end that the file is being uploaded in binary mode (not used with curl).
            contents: the file contents. More efficient for fsockopen if you already have the file contents.
            fromfile: the file to upload. More efficient for curl if you don't have the file contents.
            
            Note the name of the file specified with fromfile overrides filename when using curl.
            */
        }
        
        ///////////////////////////// Curl /////////////////////////////////////
        //If curl is available, use curl to get the data.
        if (function_exists("curl_init") and (!(isset($options['use']) and $options['use'] == 'fsocketopen'))) { //Don't use curl if it is specifically stated to use fsocketopen in the options
            
            if (isset($options['post_data'])) { //There is an option to specify some data to be posted.
                $page = $url;
                $options['method'] = 'post';
                
                if (is_array($options['post_data'])) { //The data is in array format.
                    $post_data = array();
                    
                    foreach ($options['post_data'] as $key => $value) {
                        $post_data[] = "$key=" . urlencode($value);
                    }
                    $url_parts['query'] = implode('&', $post_data);
                } else { //Its a string
                    $url_parts['query'] = $options['post_data'];
                }
            } else {
                if (isset($options['method']) and $options['method'] == 'post') {
                    $page = $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'];
                } else {
                    $page = $url;
                }
            }
            
            if ($options['session'] and isset($GLOBALS['_binget_curl_session']))
            $ch = $GLOBALS['_binget_curl_session'];     //Session is stored in a global variable
            else
            $ch = curl_init($url_parts['host']);
            
            curl_setopt($ch, CURLOPT_URL, $page) or die("Invalid cURL Handle Resouce");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);     //Just return the data - not print the whole thing.
            curl_setopt($ch, CURLOPT_HEADER, TRUE);     //We need the headers
            curl_setopt($ch, CURLOPT_NOBODY, !($options['return_body']));     //The content - if TRUE, will not download the contents. There is a ! operation - don't remove it.
            $tmpdir = NULL;     //This acts as a flag for us to clean up temp files
            if (isset($options['method']) and $options['method'] == 'post' and isset($url_parts['query'])) {
                curl_setopt($ch, CURLOPT_POST, TRUE);
                
                if (is_array($url_parts['query'])) {
                    //multipart form data (eg. file upload)
                    $postdata = array();
                    
                    foreach ($url_parts['query'] as $name => $data) {
                        if (isset($data['contents']) && isset($data['filename'])) {
                            if (!isset($tmpdir)) { //If the temporary folder is not specifed - and we want to upload a file, create a temp folder.
                                //  :TODO:
                                $dir = sys_get_temp_dir();
                                $prefix = 'load';
                                
                                if (substr($dir, -1) != '/')
                                $dir .= '/';
                                
                                do {
                                    $path = $dir . $prefix . mt_rand(0, 9999999);
                                } while (!mkdir($path, $mode));
                                
                                $tmpdir = $path;
                            }
                            $tmpfile = $tmpdir . '/' . $data['filename'];
                            file_put_contents($tmpfile, $data['contents']);
                            $data['fromfile'] = $tmpfile;
                        }
                        
                        if (isset($data['fromfile'])) {
                            // Not sure how to pass mime type and/or the 'use binary' flag
                            $postdata[$name] = '@' . $data['fromfile'];
                        } elseif (isset($data['contents'])) {
                            $postdata[$name] = $data['contents'];
                        } else {
                            $postdata[$name] = '';
                        }
                    }
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $url_parts['query']);
                }
            }
            
            //Set the headers our spiders sends
            curl_setopt($ch, CURLOPT_USERAGENT, $send_header['User-Agent']);     //The Name of the UserAgent we will be using ;)
            $custom_headers = array(
                "Accept: " . $send_header['Accept']
            );
            
            if (isset($options['modified_since']))
            array_push($custom_headers, "If-Modified-Since: " . gmdate('D, d M Y H:i:s \G\M\T', strtotime($options['modified_since'])));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $custom_headers);
            
            if ($options['referer'])
            curl_setopt($ch, CURLOPT_REFERER, $options['referer']);
            
            curl_setopt($ch, CURLOPT_COOKIEJAR, "/tmp/binget-cookie.txt");     //If ever needed...
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            
            $custom_headers = array();
            unset($send_header['User-Agent']);     // Already done (above)
            foreach ($send_header as $name => $value) {
                if (is_array($value)) {
                    foreach ($value as $item) {
                        $custom_headers[] = "$name: $item";
                    }
                } else {
                    $custom_headers[] = "$name: $value";
                }
            }
            
            if (isset($url_parts['user']) and isset($url_parts['pass'])) {
                $custom_headers[] = "Authorization: Basic " . base64_encode($url_parts['user'] . ':' . $url_parts['pass']);
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $custom_headers);
            
            $response = curl_exec($ch);
            
            if (isset($tmpdir)) {
                //rmdirr($tmpdir); //Cleanup any temporary files :TODO:
            }
            
            $info = curl_getinfo($ch);     //Some information on the fetch
            if ($options['session'] and !$options['session_close'])
            $GLOBALS['_binget_curl_session'] = $ch;     //Dont close the curl session. We may need it later - save it to a global variable
            else
            curl_close($ch);     //If the session option is not set, close the session.
            //////////////////////////////////////////// FSockOpen //////////////////////////////
        } else { //If there is no curl, use fsocketopen - but keep in mind that most advanced features will be lost with this approch.
            
            if (!isset($url_parts['query']) || (isset($options['method']) and $options['method'] == 'post'))
            $page = $url_parts['path'];
            else
            $page = $url_parts['path'] . '?' . $url_parts['query'];
            
            if (!isset($url_parts['port']))
            $url_parts['port'] = ($url_parts['scheme'] == 'https' ? 443 : 80);
            $host = ($url_parts['scheme'] == 'https' ? 'ssl://' : '') . $url_parts['host'];
            $fp = fsockopen($host, $url_parts['port'], $errno, $errstr, 30);
            
            if ($fp) {
                $out = '';
                
                if (isset($options['method']) and $options['method'] == 'post' and isset($url_parts['query'])) {
                    $out .= "POST $page HTTP/1.1\r\n";
                } else {
                    $out .= "GET $page HTTP/1.0\r\n";     //HTTP/1.0 is much easier to handle than HTTP/1.1
                }
                $out .= "Host: $url_parts[host]\r\n";
                
                foreach ($send_header as $name => $value) {
                    if (is_array($value)) {
                        foreach ($value as $item) {
                            $out .= "$name: $item\r\n";
                        }
                    } else {
                        $out .= "$name: $value\r\n";
                    }
                }
                $out .= "Connection: Close\r\n";
                
                //HTTP Basic Authorization support
                if (isset($url_parts['user']) and isset($url_parts['pass'])) {
                    $out .= "Authorization: Basic " . base64_encode($url_parts['user'] . ':' . $url_parts['pass']) . "\r\n";
                }
                
                //If the request is post - pass the data in a special way.
                if (isset($options['method']) and $options['method'] == 'post') {
                    if (is_array($url_parts['query'])) {
                        //multipart form data (eg. file upload)
                        
                        // Make a random (hopefully unique) identifier for the boundary
                        srand((double) microtime() * 1000000);
                        $boundary = "---------------------------" . substr(md5(rand(0, 32000)), 0, 10);
                        
                        $postdata = array();
                        $postdata[] = '--' . $boundary;
                        
                        foreach ($url_parts['query'] as $name => $data) {
                            $disposition = 'Content-Disposition: form-data; name="' . $name . '"';
                            
                            if (isset($data['filename'])) {
                                $disposition .= '; filename="' . $data['filename'] . '"';
                            }
                            $postdata[] = $disposition;
                            
                            if (isset($data['type'])) {
                                $postdata[] = 'Content-Type: ' . $data['type'];
                            }
                            
                            if (isset($data['binary']) && $data['binary']) {
                                $postdata[] = 'Content-Transfer-Encoding: binary';
                            } else {
                                $postdata[] = '';
                            }
                            
                            if (isset($data['fromfile'])) {
                                $data['contents'] = file_get_contents($data['fromfile']);
                            }
                            
                            if (isset($data['contents'])) {
                                $postdata[] = $data['contents'];
                            } else {
                                $postdata[] = '';
                            }
                            $postdata[] = '--' . $boundary;
                        }
                        $postdata = implode("\r\n", $postdata) . "\r\n";
                        $length = strlen($postdata);
                        $postdata = 'Content-Type: multipart/form-data; boundary=' . $boundary . "\r\n" . 'Content-Length: ' . $length . "\r\n" . "\r\n" . $postdata;
                        
                        $out .= $postdata;
                    } else {
                        $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
                        $out .= 'Content-Length: ' . strlen($url_parts['query']) . "\r\n";
                        $out .= "\r\n" . $url_parts['query'];
                    }
                }
                $out .= "\r\n";
                
                fwrite($fp, $out);
                
                while (!feof($fp)) {
                    $response .= fgets($fp, 128);
                }
                fclose($fp);
            }
        }
        
        //Get the headers in an associative array
        $headers = array();
        
        if ($info['http_code'] == 404) {
            $body = "";
            $headers['Status'] = 404;
        } else {
            //Seperate header and content
            $header_text = substr($response, 0, $info['header_size']);
            $body = substr($response, $info['header_size']);
            
            foreach (explode("\n", $header_text) as $line) {
                $parts = explode(": ", $line);
                
                if (count($parts) == 2) {
                    if (isset($headers[$parts[0]])) {
                        if (is_array($headers[$parts[0]]))
                        $headers[$parts[0]][] = chop($parts[1]);
                        else
                        $headers[$parts[0]] = array(
                            $headers[$parts[0]],
                            chop($parts[1])
                        );
                    } else {
                        $headers[$parts[0]] = chop($parts[1]);
                    }
                }
            }
        }
        
        if (isset($cache_file)) { //Should we cache the URL?
            file_put_contents($cache_file, $response);
        }
        
        if ($options['return_info'])
        return array(
            'headers' => $headers,
            'body' => $body,
            'info' => $info,
            'curl_handle' => $ch
        );
        
        return $body;
    }
    
    
    /**
     * Добавя параметър в стринг представящ URL
     */
    static function change($url, $queryParams = array(), $domain = '')
    {
        $purl = parse_url($url);
        
        if (!$purl) return FALSE;
        
        // Добавяме новите параметри в част `Query`
        if(count($queryParams)) {
            $params = array();
            if (!empty($purl["query"])) {
                parse_str($purl["query"], $params);
            }
            foreach ($queryParams as $key => $value) {
                $params[$key] = $value;
            }
            $purl["query"] = http_build_query($params);
        }

        // Промяна на домейн
        if($domain) {
            $purl["host"] = $domain;
        }
        
        $res = "";
        
        if (isset($purl["scheme"])) {
            $res .= $purl["scheme"] . "://";
        }
        
        if (isset($purl["user"])) {
            $res .= $purl["user"];
            $res .= $purl["pass"];
            $res .= "@";
        }
        $res .= $purl["host"];
        
        if ($purl["port"]) {
            $res .= ":" . $purl["port"];
        }
        
        $res .= $purl["path"];
        
        if (isset($purl["query"])) {
            $res .= "?" . $purl["query"];
        }
        
        if (isset($purl["fragment"])) {
            $res .= "#" . $purl["fragment"];
        }
        
        return $res;
    }



    /**
     * Премахва опасни символи от URL адреси
     */
    static function escape($url)
    {
        $url = str_replace(array('&amp;', '<', ' ', '"'), array('&', '&lt', '+', '&quot;'), $url);
        
        $parts = explode(':', $url, 2);

        $scheme = strtolower($parts[0]);

        if(!in_array($scheme, array('http', 'https', 'ftp', 'ftps'))) {
            $scheme = preg_replace('/[^a-z0-9]+/', '', $scheme);
            $url = "javascript:alert('" . tr('Непозволенa URL схема') . ":&quot;{$scheme}&quot;');";
        }
        
       // $url = htmlentities($url, ENT_QUOTES, 'UTF-8');

        return $url;
    }

    
    /**
     * Дали посоченото URL е локално?
     */
    static function isLocal(&$url1, &$rest = NULL)
    {
        $url = $url1;
		
        $httpBoot = getBoot(TRUE);
		
		if (EF_APP_NAME_FIXED !== TRUE) {
            $app = Request::get('App');
            $httpBoot .= '/' . ($app ? $app : EF_APP_NAME);
        }

        $httpBootS = $httpBoot;

        $starts = array("https://", "http://", '//', 'www.');

		$httpBoot = str::removeFromBegin($httpBoot, $starts);

		$url      = str::removeFromBegin($url, $starts);

        if (stripos($url, $httpBoot) === 0) {
            $result = TRUE;
            $rest   = substr($url, strlen($httpBoot));
            $url1 = $httpBootS . $rest;
        } else {
            $result = FALSE;
        }

        return $result;
    }
        
    
    /**
     * Аналогична фунция на urldecode()
     * Прави опити за конвертиране в UTF-8. Ако не успее връща оригиналното URL.
     * 
     * @param URL $url
     * 
     * @return URL
     */
    static function decodeUrl($url)
    {
        // Декодираме URL' то
        $decodedUrl = urldecode($url);
        
        // Проверяваме дали е валиден UTF-8
        if (mb_check_encoding($decodedUrl, 'UTF-8')) {
            
            // Ако е валиден връщаме резултата
            return $decodedUrl;
        }
        
        try {
            
            // Използваме наша функция за конвертиране
            $decodedUrl = i18n_Charset::convertToUtf8($decodedUrl);
        } catch (core_exception_Expect $e) { }
        
        // Проверяваме дали е валиден UTF-8
        if (mb_check_encoding($decodedUrl, 'UTF-8')) {
            
            // Ако е валиден връщаме резултата
            return $decodedUrl;
        }
        
        // Ако все още не е валидно URL, връщаме оригиналното
        return $url;
    }
    

    /**
     * Функция, която връща масив с намерените уеб-адреси
     * 
     * Допускат са само прости уеб-адреси
     */
    static function extractWebAddress($line)
    {
        preg_match_all("/(((http(s?)):\/\/)|(www\.))([\%\_\-\.a-zA-Z0-9]+)/i", $line, $matches);
        
        if(count($matches[0])) {
            foreach($matches[0] as $id => &$w) {
                if(!self::isValidTld($w)) {
                    unset($matches[0][$id]);
                    continue;
                }

                if(strpos($w, 'http://www.') === 0) {
                    $w = substr($w, strlen('http://'));
                } elseif(strpos($w, 'https://www.') === 0) {
                    $w = substr($w, strlen('https://'));
                }
            }
        }

        return $matches[0];
    }


    /**
     * извлича домейна от подаденото URL
     */
    public static function getDomain($url)
    {
        $domain = FALSE;
        $arr = @parse_url(strtolower($url));
        if(is_array($arr) && $h = $arr['host']) {
            $hArr = explode('.', $h);
            if(($c = count($hArr)) >= 2) {
                $domain = $hArr[$c-2] . '.' . $hArr[$c-1];
            }
        }

        return $domain;
    }

    
    /**
     * Подготвяме масив с валидни TLD от файл
     */
    static public function prepareValideTldArray()
    {
    	$url = 'http://data.iana.org/TLD/tlds-alpha-by-domain.txt';
	    $pageSource = file_get_contents($url);
	    
		if (!$pageSource) {
		    echo "ERROR: Не може да се вземе съдържанието<br />\n";
		} else {
			$text = "UTC";
			
			$source = stristr($pageSource, "UTC");
			$source = substr($source, strpos($source, $text)+3);
			$source = mb_strtolower($source);
			
			$source = str_replace("\n", "', ", trim($source));
			$source = str_replace("', ", "', '",trim($source));
			$source = "'" . $source  . "'";
			
			$valideTld = explode(",", $source);
			
			return $valideTld;
		}
    }


    /**
     * Проверява дали е валидно домейн името
     */
    public static function isValidDomainName($domain)
    {
        return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain) //valid chars check
            && preg_match("/^.{1,253}$/", $domain) //overall length check
            && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain)   ); //length of each label
    }
}