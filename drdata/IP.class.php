<?php



/**
 * Клас 'drdata_IP' -
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
class drdata_IP
{
    
    
    /**
     * Add hyper link to RIPE.net on IP
     */
    public function ripeLink($ip)
    {
        return "<a class=out target=_blank href='http://www.ripe.net/perl/whois?searchtext=${ip}'>${ip}</a>";
    }
    
    
    /**
     * Намира последното не-частен IP адрес от текст в който се срещат IP адреси
     */
    public function getLastIp($str)
    {
        preg_match_all('/((?:\d{1,3}\.){3})\d{1,3}/', $str, $matches);
        
        for ($ipCount = count($matches[0]) - 1; $ipCount >= 0; $ipCount--) {
            $ip = $matches[0][$ipCount];
            
            if (!drdata_Ip::isPrivateIp($ip)) {
                break;
            }
        }
        
        if ($findIp) {
            
            return $ip;
        }
    }
    
    
    /**
     * Връща структура с информация за държавата, от където е ИП то
     */
    public function toCountry($ip)
    {
        global $db;
        $ips = explode('.', "${ip}");
        $ipn = ($ips[3] + $ips[2] * 256 + $ips[1] * 256 * 256 + $ips[0] * 256 * 256 * 256);
        $dbRes = $db->query("SELECT c FROM ip2country WHERE l<${ipn} AND h>${ipn}");
        
        if ($db->numRows($dbRes) > 0) {
            $r = $db->fetchObject($dbRes);
            $db->freeResult($dbRes);
            $c = $r->c;
        }
        
        if ($c) {
            $country = $this->countries[$c];
            
            if ($country[0] != 'Proxy Server') {
                
                return $country;
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function __construct()
    {
        $this->countries['AD'] = array('Andorra', 'AN', 'AD', 'AND', '20', 'Andorra la Vella', 'Europe', 'Euro', 'EUR', '67627');
        $this->countries['AE'] = array('United Arab Emirates', 'AE', 'AE', 'ARE', '784', 'Abu Dhabi', 'Middle East', 'UAE Dirham', 'AED', '2407460');
        $this->countries['AF'] = array('Afghanistan', 'AF', 'AF', 'AFG', '4', 'Kabul', 'Asia', 'Afghani', 'AFA', '26813057');
        $this->countries['AG'] = array('Antigua and Barbuda', 'AC', 'AG', 'ATG', '28', "Saint John's", 'Central America and the Caribbean', 'East Caribbean Dollar', 'XCD', '66970');
        $this->countries['AI'] = array('Anguilla', 'AV', 'AI', 'AIA', '660', 'The Valley', 'Central America and the Caribbean', 'East Caribbean Dollar', 'XCD', '12132');
        $this->countries['AL'] = array('Albania', 'AL', 'AL', 'ALB', '8', 'Tirana', 'Europe', 'Lek', 'ALL', '3510484');
        $this->countries['AM'] = array('Armenia', 'AM', 'AM', 'ARM', '51', 'Yerevan', 'Commonwealth of Independent States', 'Armenian Dram', 'AMD', '3336100');
        $this->countries['AN'] = array('Netherlands Antilles', 'NT', 'AN', 'ANT', '530', 'Willemstad', 'Central America and the Caribbean', 'Netherlands Antillean guilder', 'ANG', '212226');
        $this->countries['AO'] = array('Angola', 'AO', 'AO', 'AGO', '24', 'Luanda', 'Africa', 'Kwanza', 'AOA', '10366031');
        $this->countries['AQ'] = array('Antarctica', 'AY', 'AQ', 'ATA', '10', '--', 'Antarctic Region', '', '', '0');
        $this->countries['AR'] = array('Argentina', 'AR', 'AR', 'ARG', '32', 'Buenos Aires', 'South America', 'Argentine Peso', 'ARS', '37384816');
        $this->countries['AS'] = array('American Samoa', 'AQ', 'AS', 'ASM', '16', 'Pago Pago', 'Oceania', 'US Dollar', 'USD', '67084');
        $this->countries['AT'] = array('Austria', 'AU', 'AT', 'AUT', '40', 'Vienna', 'Europe', 'Euro', 'EUR', '8150835');
        $this->countries['AU'] = array('Australia', 'AS', 'AU', 'AUS', '36', 'Canberra', 'Oceania', 'Australian dollar', 'AUD', '19357594');
        $this->countries['AW'] = array('Aruba', 'AA', 'AW', 'ABW', '533', 'Oranjestad', 'Central America and the Caribbean', 'Aruban Guilder', 'AWG', '70007');
        $this->countries['AZ'] = array('Azerbaijan', 'AJ', 'AZ', 'AZE', '31', 'Baku (Baki)', 'Commonwealth of Independent States', 'Azerbaijani Manat', 'AZM', '7771092');
        $this->countries['BA'] = array('Bosnia and Herzegovina', 'BK', 'BA', 'BIH', '70', 'Sarajevo', 'Bosnia and Herzegovina, Europe', 'Convertible Marka', 'BAM', '3922205');
        $this->countries['BB'] = array('Barbados', 'BB', 'BB', 'BRB', '52', 'Bridgetown', 'Central America and the Caribbean', 'Barbados Dollar', 'BBD', '275330');
        $this->countries['BD'] = array('Bangladesh', 'BG', 'BD', 'BGD', '50', 'Dhaka', 'Asia', 'Taka', 'BDT', '131269860');
        $this->countries['BE'] = array('Belgium', 'BE', 'BE', 'BEL', '56', 'Brussels', 'Europe', 'Euro', 'EUR', '10258762');
        $this->countries['BF'] = array('Burkina Faso', 'UV', 'BF', 'BFA', '854', 'Ouagadougou', 'Africa', 'CFA Franc BCEAO', 'XOF', '12272289');
        $this->countries['BG'] = array('Bulgaria', 'BU', 'BG', 'BGR', '100', 'Sofia', 'Europe', 'Lev', 'BGL', '7707495');
        $this->countries['BH'] = array('Bahrain', 'BA', 'BH', 'BHR', '48', 'Manama', 'Middle East', 'Bahraini Dinar', 'BHD', '645361');
        $this->countries['BI'] = array('Burundi', 'BY', 'BI', 'BDI', '108', 'Bujumbura', 'Africa', 'Burundi Franc', 'BIF', '6223897');
        $this->countries['BJ'] = array('Benin', 'BN', 'BJ', 'BEN', '204', 'Porto-Novo', 'Africa', 'CFA Franc BCEAO', 'XOF', '6590782');
        $this->countries['BM'] = array('Bermuda', 'BD', 'BM', 'BMU', '60', 'Hamilton', 'North America', 'Bermudian Dollar', 'BMD', '63503');
        $this->countries['BN'] = array('Brunei Darussalam', 'BX', 'BN', 'BRN', '96', 'Bandar Seri Begawan', 'Southeast Asia', 'Brunei Dollar', 'BND', '0');
        $this->countries['BO'] = array('Bolivia', 'BL', 'BO', 'BOL', '68', 'La Paz /Sucre', 'South America', 'Boliviano', 'BOB', '8300463');
        $this->countries['BR'] = array('Brazil', 'BR', 'BR', 'BRA', '76', 'Brasilia', 'South America', 'Brazilian Real', 'BRL', '174468575');
        $this->countries['BS'] = array('The Bahamas', 'BF', 'BS', 'BHS', '44', 'Nassau', 'Central America and the Caribbean', 'Bahamian Dollar', 'BSD', '297852');
        $this->countries['BT'] = array('Bhutan', 'BT', 'BT', 'BTN', '64', 'Thimphu', 'Asia', 'Ngultrum', 'BTN', '2049412');
        $this->countries['BV'] = array('Bouvet Island', 'BV', 'BV', 'BVT', '74', '--', 'Antarctic Region', 'Norwegian Krone', 'NOK', '0');
        $this->countries['BW'] = array('Botswana', 'BC', 'BW', 'BWA', '72', 'Gaborone', 'Africa', 'Pula', 'BWP', '1586119');
        $this->countries['BY'] = array('Belarus', 'BO', 'BY', 'BLR', '112', 'Minsk', 'Commonwealth of Independent States', 'Belarussian Ruble', 'BYR', '10350194');
        $this->countries['BZ'] = array('Belize', 'BH', 'BZ', 'BLZ', '84', 'Belmopan', 'Central America and the Caribbean', 'Belize Dollar', 'BZD', '256062');
        $this->countries['CA'] = array('Canada', 'CA', 'CA', 'CAN', '124', 'Ottawa', 'North America', 'Canadian Dollar', 'CAD', '31592805');
        $this->countries['CC'] = array('Cocos (Keeling) Islands', 'CK', 'CC', 'CCK', '166', 'West Island', 'Southeast Asia', 'Australian Dollar', 'AUD', '633');
        $this->countries['CD'] = array('Congo, Democratic Republic of the', 'CG', 'CD', 'COD', '180', 'Kinshasa', 'Africa', 'Franc Congolais', 'CDF', '53624718');
        $this->countries['CF'] = array('Central African Republic', 'CT', 'CF', 'CAF', '140', 'Bangui', 'Africa', 'CFA Franc BEAC', 'XAF', '3576884');
        $this->countries['CG'] = array('Congo, Republic of the', 'CF', 'CG', 'COG', '178', 'Brazzaville', 'Africa', 'CFA Franc BEAC', 'XAF', '2894336');
        $this->countries['CH'] = array('Switzerland', 'SZ', 'CH', 'CHE', '756', 'Bern', 'Europe', 'Swiss Franc', 'CHF', '7283274');
        $this->countries['CI'] = array("Cote d'Ivoire", 'IV', 'CI', 'CIV', '384', 'Yamoussoukro', 'Africa', 'CFA Franc BCEAO', 'XOF', '16393221');
        $this->countries['CK'] = array('Cook Islands', 'CW', 'CK', 'COK', '184', 'Avarua', 'Oceania', 'New Zealand Dollar', 'NZD', '20611');
        $this->countries['CL'] = array('Chile', 'CI', 'CL', 'CHL', '152', 'Santiago', 'South America', 'Chilean Peso', 'CLP', '15328467');
        $this->countries['CM'] = array('Cameroon', 'CM', 'CM', 'CMR', '120', 'Yaounde', 'Africa', 'CFA Franc BEAC', 'XAF', '15803220');
        $this->countries['CN'] = array('China', 'CH', 'CN', 'CHN', '156', 'Beijing', 'Asia', 'Yuan Renminbi', 'CNY', '1273111290');
        $this->countries['CO'] = array('Colombia', 'CO', 'CO', 'COL', '170', 'Bogota', 'South America, Central America and the Caribbean', 'Colombian Peso', 'COP', '40349388');
        $this->countries['CR'] = array('Costa Rica', 'CS', 'CR', 'CRI', '188', 'San Jose', 'Central America and the Caribbean', 'Costa Rican Colon', 'CRC', '3773057');
        $this->countries['CU'] = array('Cuba', 'CU', 'CU', 'CUB', '192', 'Havana', 'Central America and the Caribbean', 'Cuban Peso', 'CUP', '11184023');
        $this->countries['CV'] = array('Cape Verde', 'CV', 'CV', 'CPV', '132', 'Praia', 'World', 'Cape Verdean Escudo', 'CVE', '405163');
        $this->countries['CX'] = array('Christmas Island', 'KT', 'CX', 'CXR', '162', 'The Settlement', 'Southeast Asia', 'Australian Dollar', 'AUD', '2771');
        $this->countries['CY'] = array('Cyprus', 'CY', 'CY', 'CYP', '196', 'Nicosia', 'Middle East', 'Cyprus Pound', 'CYP', '762887');
        $this->countries['CZ'] = array('Czech Republic', 'EZ', 'CZ', 'CZE', '203', 'Prague', 'Europe', 'Czech Koruna', 'CZK', '10264212');
        $this->countries['DE'] = array('Germany', 'GM', 'DE', 'DEU', '276', 'Berlin', 'Europe', 'Euro', 'EUR', '83029536');
        $this->countries['DJ'] = array('Djibouti', 'DJ', 'DJ', 'DJI', '262', 'Djibouti', 'Africa', 'Djibouti Franc', 'DJF', '460700');
        $this->countries['DK'] = array('Denmark', 'DA', 'DK', 'DNK', '208', 'Copenhagen', 'Europe', 'Danish Krone', 'DKK', '5352815');
        $this->countries['DM'] = array('Dominica', 'DO', 'DM', 'DMA', '212', 'Roseau', 'Central America and the Caribbean', 'East Caribbean Dollar', 'XCD', '70786');
        $this->countries['DO'] = array('Dominican Republic', 'DR', 'DO', 'DOM', '214', 'Santo Domingo', 'Central America and the Caribbean', 'Dominican Peso', 'DOP', '8581477');
        $this->countries['DZ'] = array('Algeria', 'AG', 'DZ', 'DZA', '12', 'Algiers', 'Africa', 'Algerian Dinar', 'DZD', '31736053');
        $this->countries['EC'] = array('Ecuador', 'EC', 'EC', 'ECU', '218', 'Quito', 'South America', 'US dollar', 'USD', '13183978');
        $this->countries['EE'] = array('Estonia', 'EN', 'EE', 'EST', '233', 'Tallinn', 'Europe', 'Kroon', 'EEK', '1423316');
        $this->countries['EG'] = array('Egypt', 'EG', 'EG', 'EGY', '818', 'Cairo', 'Africa', 'Egyptian Pound', 'EGP', '69536644');
        $this->countries['EH'] = array('Western Sahara', 'WI', 'EH', 'ESH', '732', '--', 'Africa', 'Moroccan Dirham', 'MAD', '250559');
        $this->countries['ER'] = array('Eritrea', 'ER', 'ER', 'ERI', '232', 'Asmara', 'Africa', 'Nakfa', 'ERN', '4298269');
        $this->countries['ES'] = array('Spain', 'SP', 'ES', 'ESP', '724', 'Madrid', 'Europe', 'Euro', 'EUR', '40037995');
        $this->countries['ET'] = array('Ethiopia', 'ET', 'ET', 'ETH', '231', 'Addis Ababa', 'Africa', 'Ethiopian Birr', 'ETB', '65891874');
        $this->countries['FI'] = array('Finland', 'FI', 'FI', 'FIN', '246', 'Helsinki', 'Europe', 'Euro', 'EUR', '5175783');
        $this->countries['FJ'] = array('Fiji', 'FJ', 'FJ', 'FJI', '242', 'Suva', 'Oceania', 'Fijian Dollar', 'FJD', '844330');
        $this->countries['FK'] = array('Falkland Islands (Islas Malvinas)', 'FK', 'FK', 'FLK', '238', 'Stanley', 'South America', 'Falkland Islands Pound', 'FKP', '2895');
        $this->countries['FM'] = array('Micronesia, Federated States of', 'FM', 'FM', 'FSM', '583', 'Palikir', 'Oceania', 'US dollar', 'USD', '134597');
        $this->countries['FO'] = array('Faroe Islands', 'FO', 'FO', 'FRO', '234', 'Torshavn', 'Europe', 'Danish Krone', 'DKK', '45661');
        $this->countries['FR'] = array('France', 'FR', 'FR', 'FRA', '250', 'Paris', 'Europe', 'Euro', 'EUR', '59551227');
        $this->countries['FX'] = array('France, Metropolitan', '--', '--', '--', '-1', '--', '', 'Euro', 'EUR', '0');
        $this->countries['GA'] = array('Gabon', 'GB', 'GA', 'GAB', '266', 'Libreville', 'Africa', 'CFA Franc BEAC', 'XAF', '1221175');
        $this->countries['GD'] = array('Grenada', 'GJ', 'GD', 'GRD', '308', "Saint George's", 'Central America and the Caribbean', 'East Caribbean Dollar', 'XCD', '89227');
        $this->countries['GE'] = array('Georgia', 'GG', 'GE', 'GEO', '268', "T'bilisi", 'Commonwealth of Independent States', 'Lari', 'GEL', '4989285');
        $this->countries['GF'] = array('French Guiana', 'FG', 'GF', 'GUF', '254', 'Cayenne', 'South America', 'Euro', 'EUR', '177562');
        $this->countries['GG'] = array('Guernsey', 'GK', '--', '--', '-1', 'Saint Peter Port', 'Europe', 'Pound Sterling', 'GBP', '64342');
        $this->countries['GH'] = array('Ghana', 'GH', 'GH', 'GHA', '288', 'Accra', 'Africa', 'Cedi', 'GHC', '19894014');
        $this->countries['GI'] = array('Gibraltar', 'GI', 'GI', 'GIB', '292', 'Gibraltar', 'Europe', 'Gibraltar Pound', 'GIP', '27649');
        $this->countries['GL'] = array('Greenland', 'GL', 'GL', 'GRL', '304', 'Nuuk', 'Arctic Region', 'Danish Krone', 'DKK', '56352');
        $this->countries['GM'] = array('The Gambia', 'GA', 'GM', 'GMB', '270', 'Banjul', 'Africa', 'Dalasi', 'GMD', '1411205');
        $this->countries['GN'] = array('Guinea', 'GV', 'GN', 'GIN', '324', 'Conakry', 'Africa', 'Guinean Franc', 'GNF', '7613870');
        $this->countries['GP'] = array('Guadeloupe', 'GP', 'GP', 'GLP', '312', 'Basse-Terre', 'Central America and the Caribbean', 'Euro', 'EUR', '431170');
        $this->countries['GQ'] = array('Equatorial Guinea', 'EK', 'GQ', 'GNQ', '226', 'Malabo', 'Africa', 'CFA Franc BEAC', 'XAF', '486060');
        $this->countries['GR'] = array('Greece', 'GR', 'GR', 'GRC', '300', 'Athens', 'Europe', 'Euro', 'EUR', '10623835');
        $this->countries['GS'] = array('South Georgia and the South Sandwich Islands', 'SX', 'GS', 'SGS', '239', '--', 'Antarctic Region', 'Pound Sterling', 'GBP', '0');
        $this->countries['GT'] = array('Guatemala', 'GT', 'GT', 'GTM', '320', 'Guatemala', 'Central America and the Caribbean', 'Quetzal', 'GTQ', '12974361');
        $this->countries['GU'] = array('Guam', 'GQ', 'GU', 'GUM', '316', 'Hagatna', 'Oceania', 'US Dollar', 'USD', '157557');
        $this->countries['GW'] = array('Guinea-Bissau', 'PU', 'GW', 'GNB', '624', 'Bissau', 'Africa', 'CFA Franc BCEAO', 'XOF', '1315822');
        $this->countries['GY'] = array('Guyana', 'GY', 'GY', 'GUY', '328', 'Georgetown', 'South America', 'Guyana Dollar', 'GYD', '697181');
        $this->countries['HK'] = array('Hong Kong (SAR)', 'HK', 'HK', 'HKG', '344', 'Hong Kong', 'Southeast Asia', 'Hong Kong Dollar', 'HKD', '0');
        $this->countries['HM'] = array('Heard Island and McDonald Islands', 'HM', 'HM', 'HMD', '334', '--', 'Antarctic Region', 'Australian Dollar', 'AUD', '0');
        $this->countries['HN'] = array('Honduras', 'HO', 'HN', 'HND', '340', 'Tegucigalpa', 'Central America and the Caribbean', 'Lempira', 'HNL', '6406052');
        $this->countries['HR'] = array('Croatia', 'HR', 'HR', 'HRV', '191', 'Zagreb', 'Europe', 'Kuna', 'HRK', '4334142');
        $this->countries['HT'] = array('Haiti', 'HA', 'HT', 'HTI', '332', 'Port-au-Prince', 'Central America and the Caribbean', 'Gourde', 'HTG', '6964549');
        $this->countries['HU'] = array('Hungary', 'HU', 'HU', 'HUN', '348', 'Budapest', 'Europe', 'Forint', 'HUF', '10106017');
        $this->countries['ID'] = array('Indonesia', 'ID', 'ID', 'IDN', '360', 'Jakarta', 'Southeast Asia', 'Rupiah', 'IDR', '228437870');
        $this->countries['IE'] = array('Ireland', 'EI', 'IE', 'IRL', '372', 'Dublin', 'Europe', 'Euro', 'EUR', '3840838');
        $this->countries['IL'] = array('Israel', 'IS', 'IL', 'ISR', '376', 'Jerusalem', 'Middle East', 'New Israeli Sheqel', 'ILS', '5938093');
        $this->countries['IM'] = array('Man, Isle of', 'IM', '--', '--', '-1', 'Douglas', 'Europe', 'Pound Sterling', 'GBP', '73489');
        $this->countries['IN'] = array('India', 'IN', 'IN', 'IND', '356', 'New Delhi', 'Asia', 'Indian Rupee', 'INR', '1029991145');
        $this->countries['IO'] = array('British Indian Ocean Territory', 'IO', 'IO', 'IOT', '86', '--', 'World', 'US Dollar', 'USD', '0');
        $this->countries['IQ'] = array('Iraq', 'IZ', 'IQ', 'IRQ', '368', 'Baghdad', 'Middle East', 'Iraqi Dinar', 'IQD', '23331985');
        $this->countries['IR'] = array('Iran', 'IR', 'IR', 'IRN', '364', 'Tehran', 'Middle East', 'Iranian Rial', 'IRR', '66128965');
        $this->countries['IS'] = array('Iceland', 'IC', 'IS', 'ISL', '352', 'Reykjavik', 'Arctic Region', 'Iceland Krona', 'ISK', '277906');
        $this->countries['IT'] = array('Italy', 'IT', 'IT', 'ITA', '380', 'Rome', 'Europe', 'Euro', 'EUR', '57679825');
        $this->countries['JE'] = array('Jersey', 'JE', '--', '--', '-1', 'Saint Helier', 'Europe', 'Pound Sterling', 'GBP', '89361');
        $this->countries['JM'] = array('Jamaica', 'JM', 'JM', 'JAM', '388', 'Kingston', 'Central America and the Caribbean', 'Jamaican dollar', 'JMD', '2665636');
        $this->countries['JO'] = array('Jordan', 'JO', 'JO', 'JOR', '400', 'Amman', 'Middle East', 'Jordanian Dinar', 'JOD', '5153378');
        $this->countries['JP'] = array('Japan', 'JA', 'JP', 'JPN', '392', 'Tokyo', 'Asia', 'Yen', 'JPY', '126771662');
        $this->countries['KE'] = array('Kenya', 'KE', 'KE', 'KEN', '404', 'Nairobi', 'Africa', 'Kenyan shilling', 'KES', '30765916');
        $this->countries['KG'] = array('Kyrgyzstan', 'KG', 'KG', 'KGZ', '417', 'Bishkek', 'Commonwealth of Independent States', 'Som', 'KGS', '4753003');
        $this->countries['KH'] = array('Cambodia', 'CB', 'KH', 'KHM', '116', 'Phnom Penh', 'Southeast Asia', 'Riel', 'KHR', '12491501');
        $this->countries['KI'] = array('Kiribati', 'KR', 'KI', 'KIR', '296', 'Tarawa', 'Oceania', 'Australian dollar', 'AUD', '94149');
        $this->countries['KM'] = array('Comoros', 'CN', 'KM', 'COM', '174', 'Moroni', 'Africa', 'Comoro Franc', 'KMF', '596202');
        $this->countries['KN'] = array('Saint Kitts and Nevis', 'SC', 'KN', 'KNA', '659', 'Basseterre', 'Central America and the Caribbean', 'East Caribbean Dollar', 'XCD', '38756');
        $this->countries['KP'] = array('Korea, North', 'KN', 'KP', 'PRK', '408', "P'yongyang", 'Asia', 'North Korean Won', 'KPW', '21968228');
        $this->countries['KR'] = array('Korea, South', 'KS', 'KR', 'KOR', '410', 'Seoul', 'Asia', 'Won', 'KRW', '47904370');
        $this->countries['KW'] = array('Kuwait', 'KU', 'KW', 'KWT', '414', 'Kuwait', 'Middle East', 'Kuwaiti Dinar', 'KWD', '2041961');
        $this->countries['KY'] = array('Cayman Islands', 'CJ', 'KY', 'CYM', '136', 'George Town', 'Central America and the Caribbean', 'Cayman Islands Dollar', 'KYD', '35527');
        $this->countries['KZ'] = array('Kazakhstan', 'KZ', 'KZ', 'KAZ', '398', 'Astana', 'Commonwealth of Independent States', 'Tenge', 'KZT', '16731303');
        $this->countries['LA'] = array('Laos', 'LA', 'LA', 'LAO', '418', 'Vientiane', 'Southeast Asia', 'Kip', 'LAK', '5635967');
        $this->countries['LB'] = array('Lebanon', 'LE', 'LB', 'LBN', '422', 'Beirut', 'Middle East', 'Lebanese Pound', 'LBP', '3627774');
        $this->countries['LC'] = array('Saint Lucia', 'ST', 'LC', 'LCA', '662', 'Castries', 'Central America and the Caribbean', 'East Caribbean Dollar', 'XCD', '158178');
        $this->countries['LI'] = array('Liechtenstein', 'LS', 'LI', 'LIE', '438', 'Vaduz', 'Europe', 'Swiss Franc', 'CHF', '32528');
        $this->countries['LK'] = array('Sri Lanka', 'CE', 'LK', 'LKA', '144', 'Colombo', 'Asia', 'Sri Lanka Rupee', 'LKR', '19408635');
        $this->countries['LR'] = array('Liberia', 'LI', 'LR', 'LBR', '430', 'Monrovia', 'Africa', 'Liberian Dollar', 'LRD', '3225837');
        $this->countries['LS'] = array('Lesotho', 'LT', 'LS', 'LSO', '426', 'Maseru', 'Africa', 'Loti', 'LSL', '2177062');
        $this->countries['LT'] = array('Lithuania', 'LH', 'LT', 'LTU', '440', 'Vilnius', 'Europe', 'Lithuanian Litas', 'LTL', '3610535');
        $this->countries['LU'] = array('Luxembourg', 'LU', 'LU', 'LUX', '442', 'Luxembourg', 'Europe', 'Euro', 'EUR', '442972');
        $this->countries['LV'] = array('Latvia', 'LG', 'LV', 'LVA', '428', 'Riga', 'Europe', 'Latvian Lats', 'LVL', '2385231');
        $this->countries['LY'] = array('Libya', 'LY', 'LY', 'LBY', '434', 'Tripoli', 'Africa', 'Libyan Dinar', 'LYD', '5240599');
        $this->countries['MA'] = array('Morocco', 'MO', 'MA', 'MAR', '504', 'Rabat', 'Africa', 'Moroccan Dirham', 'MAD', '30645305');
        $this->countries['MC'] = array('Monaco', 'MN', 'MC', 'MCO', '492', 'Monaco', 'Europe', 'Euro', 'EUR', '31842');
        $this->countries['MD'] = array('Moldova', 'MD', 'MD', 'MDA', '498', 'Chisinau', 'Commonwealth of Independent States', 'Moldovan Leu', 'MDL', '4431570');
        $this->countries['MG'] = array('Madagascar', 'MA', 'MG', 'MDG', '450', 'Antananarivo', 'Africa', 'Malagasy Franc', 'MGF', '15982563');
        $this->countries['MH'] = array('Marshall Islands', 'RM', 'MH', 'MHL', '584', 'Majuro', 'Oceania', 'US dollar', 'USD', '70822');
        $this->countries['MK'] = array('Macedonia', 'MK', 'MK', 'MKD', '807', 'Skopje', 'Europe', 'Denar', 'MKD', '2046209');
        $this->countries['ML'] = array('Mali', 'ML', 'ML', 'MLI', '466', 'Bamako', 'Africa', 'CFA Franc BCEAO', 'XOF', '11008518');
        $this->countries['MM'] = array('Burma', 'BM', 'MM', 'MMR', '104', 'Rangoon', 'Southeast Asia', 'kyat', 'MMK', '41994678');
        $this->countries['MN'] = array('Mongolia', 'MG', 'MN', 'MNG', '496', 'Ulaanbaatar', 'Asia', 'Tugrik', 'MNT', '2654999');
        $this->countries['MO'] = array('Macao', 'MC', 'MO', 'MAC', '446', 'Macao', 'Southeast Asia', 'Pataca', 'MOP', '453733');
        $this->countries['MP'] = array('Northern Mariana Islands', 'CQ', 'MP', 'MNP', '580', 'Saipan', 'Oceania', 'US Dollar', 'USD', '74612');
        $this->countries['MQ'] = array('Martinique', 'MB', 'MQ', 'MTQ', '474', 'Fort-de-France', 'Central America and the Caribbean', 'Euro', 'EUR', '418454');
        $this->countries['MR'] = array('Mauritania', 'MR', 'MR', 'MRT', '478', 'Nouakchott', 'Africa', 'Ouguiya', 'MRO', '2747312');
        $this->countries['MS'] = array('Montserrat', 'MH', 'MS', 'MSR', '500', 'Plymouth', 'Central America and the Caribbean', 'East Caribbean Dollar', 'XCD', '7574');
        $this->countries['MT'] = array('Malta', 'MT', 'MT', 'MLT', '470', 'Valletta', 'Europe', 'Maltese Lira', 'MTL', '394583');
        $this->countries['MU'] = array('Mauritius', 'MP', 'MU', 'MUS', '480', 'Port Louis', 'World', 'Mauritius Rupee', 'MUR', '1189825');
        $this->countries['MV'] = array('Maldives', 'MV', 'MV', 'MDV', '462', 'Male', 'Asia', 'Rufiyaa', 'MVR', '310764');
        $this->countries['MW'] = array('Malawi', 'MI', 'MW', 'MWI', '454', 'Lilongwe', 'Africa', 'Kwacha', 'MWK', '10548250');
        $this->countries['MX'] = array('Mexico', 'MX', 'MX', 'MEX', '484', 'Mexico', 'North America', 'Mexican Peso', 'MXN', '101879171');
        $this->countries['MY'] = array('Malaysia', 'MY', 'MY', 'MYS', '458', 'Kuala Lumpur', 'Southeast Asia', 'Malaysian Ringgit', 'MYR', '22229040');
        $this->countries['MZ'] = array('Mozambique', 'MZ', 'MZ', 'MOZ', '508', 'Maputo', 'Africa', 'Metical', 'MZM', '19371057');
        $this->countries['NA'] = array('Namibia', 'WA', 'NA', 'NAM', '516', 'Windhoek', 'Africa', 'Namibian Dollar', 'NAD', '1797677');
        $this->countries['NC'] = array('New Caledonia', 'NC', 'NC', 'NCL', '540', 'Noumea', 'Oceania', 'CFP Franc', 'XPF', '204863');
        $this->countries['NE'] = array('Niger', 'NG', 'NE', 'NER', '562', 'Niamey', 'Africa', 'CFA Franc BCEAO', 'XOF', '10355156');
        $this->countries['NF'] = array('Norfolk Island', 'NF', 'NF', 'NFK', '574', 'Kingston', 'Oceania', 'Australian Dollar', 'AUD', '1879');
        $this->countries['NG'] = array('Nigeria', 'NI', 'NG', 'NGA', '566', 'Abuja', 'Africa', 'Naira', 'NGN', '126635626');
        $this->countries['NI'] = array('Nicaragua', 'NU', 'NI', 'NIC', '558', 'Managua', 'Central America and the Caribbean', 'Cordoba Oro', 'NIO', '4918393');
        $this->countries['NL'] = array('Netherlands', 'NL', 'NL', 'NLD', '528', 'Amsterdam', 'Europe', 'Euro', 'EUR', '15981472');
        $this->countries['NO'] = array('Norway', 'NO', 'NO', 'NOR', '578', 'Oslo', 'Europe', 'Norwegian Krone', 'NOK', '4503440');
        $this->countries['NP'] = array('Nepal', 'NP', 'NP', 'NPL', '524', 'Kathmandu', 'Asia', 'Nepalese Rupee', 'NPR', '25284463');
        $this->countries['NR'] = array('Nauru', 'NR', 'NR', 'NRU', '520', '--', 'Oceania', 'Australian Dollar', 'AUD', '12088');
        $this->countries['NU'] = array('Niue', 'NE', 'NU', 'NIU', '570', 'Alofi', 'Oceania', 'New Zealand Dollar', 'NZD', '2124');
        $this->countries['NZ'] = array('New Zealand', 'NZ', 'NZ', 'NZL', '554', 'Wellington', 'Oceania', 'New Zealand Dollar', 'NZD', '3864129');
        $this->countries['OM'] = array('Oman', 'MU', 'OM', 'OMN', '512', 'Muscat', 'Middle East', 'Rial Omani', 'OMR', '2622198');
        $this->countries['PA'] = array('Panama', 'PM', 'PA', 'PAN', '591', 'Panama', 'Central America and the Caribbean', 'balboa', 'PAB', '2845647');
        $this->countries['PE'] = array('Peru', 'PE', 'PE', 'PER', '604', 'Lima', 'South America', 'Nuevo Sol', 'PEN', '27483864');
        $this->countries['PF'] = array('French Polynesia', 'FP', 'PF', 'PYF', '258', 'Papeete', 'Oceania', 'CFP Franc', 'XPF', '253506');
        $this->countries['PG'] = array('Papua New Guinea', 'PP', 'PG', 'PNG', '598', 'Port Moresby', 'Oceania', 'Kina', 'PGK', '5049055');
        $this->countries['PH'] = array('Philippines', 'RP', 'PH', 'PHL', '608', 'Manila', 'Southeast Asia', 'Philippine Peso', 'PHP', '82841518');
        $this->countries['PK'] = array('Pakistan', 'PK', 'PK', 'PAK', '586', 'Islamabad', 'Asia', 'Pakistan Rupee', 'PKR', '144616639');
        $this->countries['PL'] = array('Poland', 'PL', 'PL', 'POL', '616', 'Warsaw', 'Europe', 'Zloty', 'PLN', '38633912');
        $this->countries['PM'] = array('Saint Pierre and Miquelon', 'SB', 'PM', 'SPM', '666', 'Saint-Pierre', 'North America', 'Euro', 'EUR', '6928');
        $this->countries['PN'] = array('Pitcairn Islands', 'PC', 'PN', 'PCN', '612', 'Adamstown', 'Oceania', 'New Zealand Dollar', 'NZD', '47');
        $this->countries['PR'] = array('Puerto Rico', 'RQ', 'PR', 'PRI', '630', 'San Juan', 'Central America and the Caribbean', 'US dollar', 'USD', '3937316');
        $this->countries['PS'] = array('Palestinian Territory, Occupied', '--', 'PS', 'PSE', '275', '--', '', '', '', '0');
        $this->countries['PT'] = array('Portugal', 'PO', 'PT', 'PRT', '620', 'Lisbon', 'Europe', 'Euro', 'EUR', '10066253');
        $this->countries['PW'] = array('Palau', 'PS', 'PW', 'PLW', '585', 'Koror', 'Oceania', 'US dollar', 'USD', '19092');
        $this->countries['PY'] = array('Paraguay', 'PA', 'PY', 'PRY', '600', 'Asuncion', 'South America', 'Guarani', 'PYG', '5734139');
        $this->countries['QA'] = array('Qatar', 'QA', 'QA', 'QAT', '634', 'Doha', 'Middle East', 'Qatari Rial', 'QAR', '769152');
        $this->countries['RE'] = array('Rйunion', 'RE', 'RE', 'REU', '638', 'Saint-Denis', 'World', 'Euro', 'EUR', '732570');
        $this->countries['RO'] = array('Romania', 'RO', 'RO', 'ROU', '642', 'Bucharest', 'Europe', 'Leu', 'ROL', '22364022');
        $this->countries['RU'] = array('Russia', 'RS', 'RU', 'RUS', '643', 'Moscow', 'Asia', 'Russian Ruble', 'RUB', '145470197');
        $this->countries['RW'] = array('Rwanda', 'RW', 'RW', 'RWA', '646', 'Kigali', 'Africa', 'Rwanda Franc', 'RWF', '7312756');
        $this->countries['SA'] = array('Saudi Arabia', 'SA', 'SA', 'SAU', '682', 'Riyadh', 'Middle East', 'Saudi Riyal', 'SAR', '22757092');
        $this->countries['SB'] = array('Solomon Islands', 'BP', 'SB', 'SLB', '90', 'Honiara', 'Oceania', 'Solomon Islands Dollar', 'SBD', '480442');
        $this->countries['SC'] = array('Seychelles', 'SE', 'SC', 'SYC', '690', 'Victoria', 'Africa', 'Seychelles Rupee', 'SCR', '79715');
        $this->countries['SD'] = array('Sudan', 'SU', 'SD', 'SDN', '736', 'Khartoum', 'Africa', 'Sudanese Dinar', 'SDD', '36080373');
        $this->countries['SE'] = array('Sweden', 'SW', 'SE', 'SWE', '752', 'Stockholm', 'Europe', 'Swedish Krona', 'SEK', '8875053');
        $this->countries['SG'] = array('Singapore', 'SN', 'SG', 'SGP', '702', 'Singapore', 'Southeast Asia', 'Singapore Dollar', 'SGD', '4300419');
        $this->countries['SH'] = array('Saint Helena', 'SH', 'SH', 'SHN', '654', 'Jamestown', 'Africa', 'Saint Helenian Pound', 'SHP', '7266');
        $this->countries['SI'] = array('Slovenia', 'SI', 'SI', 'SVN', '705', 'Ljubljana', 'Europe', 'Tolar', 'SIT', '1930132');
        $this->countries['SJ'] = array('Svalbard', 'SV', 'SJ', 'SJM', '744', 'Longyearbyen', 'Arctic Region', 'Norwegian Krone', 'NOK', '2332');
        $this->countries['SK'] = array('Slovakia', 'LO', 'SK', 'SVK', '703', 'Bratislava', 'Europe', 'Slovak Koruna', 'SKK', '5414937');
        $this->countries['SL'] = array('Sierra Leone', 'SL', 'SL', 'SLE', '694', 'Freetown', 'Africa', 'Leone', 'SLL', '5426618');
        $this->countries['SM'] = array('San Marino', 'SM', 'SM', 'SMR', '674', 'San Marino', 'Europe', 'Euro', 'EUR', '27336');
        $this->countries['SN'] = array('Senegal', 'SG', 'SN', 'SEN', '686', 'Dakar', 'Africa', 'CFA Franc BCEAO', 'XOF', '10284929');
        $this->countries['SO'] = array('Somalia', 'SO', 'SO', 'SOM', '706', 'Mogadishu', 'Africa', 'Somali Shilling', 'SOS', '7488773');
        $this->countries['SR'] = array('Suriname', 'NS', 'SR', 'SUR', '740', 'Paramaribo', 'South America', 'Suriname Guilder', 'SRG', '433998');
        $this->countries['ST'] = array('Sгo Tomй and Prнncipe', 'TP', 'ST', 'STP', '678', 'Sao Tome', 'Africa', 'Dobra', 'STD', '165034');
        $this->countries['SV'] = array('El Salvador', 'ES', 'SV', 'SLV', '222', 'San Salvador', 'Central America and the Caribbean', 'El Salvador Colon', 'SVC', '6237662');
        $this->countries['SY'] = array('Syria', 'SY', 'SY', 'SYR', '760', 'Damascus', 'Middle East', 'Syrian Pound', 'SYP', '16728808');
        $this->countries['SZ'] = array('Swaziland', 'WZ', 'SZ', 'SWZ', '748', 'Mbabane', 'Africa', 'Lilangeni', 'SZL', '1104343');
        $this->countries['TC'] = array('Turks and Caicos Islands', 'TK', 'TC', 'TCA', '796', 'Cockburn Town', 'Central America and the Caribbean', 'US Dollar', 'USD', '18122');
        $this->countries['TD'] = array('Chad', 'CD', 'TD', 'TCD', '148', "N'Djamena", 'Africa', 'CFA Franc BEAC', 'XAF', '8707078');
        $this->countries['TF'] = array('French Southern and Antarctic Lands', 'FS', 'TF', 'ATF', '260', '--', 'Antarctic Region', 'Euro', 'EUR', '0');
        $this->countries['TG'] = array('Togo', 'TO', 'TG', 'TGO', '768', 'Lome', 'Africa', 'CFA Franc BCEAO', 'XOF', '5153088');
        $this->countries['TH'] = array('Thailand', 'TH', 'TH', 'THA', '764', 'Bangkok', 'Southeast Asia', 'Baht', 'THB', '61797751');
        $this->countries['TJ'] = array('Tajikistan', 'TI', 'TJ', 'TJK', '762', 'Dushanbe', 'Commonwealth of Independent States', 'Somoni', 'TJS', '6578681');
        $this->countries['TK'] = array('Tokelau', 'TL', 'TK', 'TKL', '772', '--', 'Oceania', 'New Zealand Dollar', 'NZD', '1445');
        $this->countries['TM'] = array('Turkmenistan', 'TX', 'TM', 'TKM', '795', 'Ashgabat', 'Commonwealth of Independent States', 'Manat', 'TMM', '4603244');
        $this->countries['TN'] = array('Tunisia', 'TS', 'TN', 'TUN', '788', 'Tunis', 'Africa', 'Tunisian Dinar', 'TND', '9705102');
        $this->countries['TO'] = array('Tonga', 'TN', 'TO', 'TON', '776', "Nuku'alofa", 'Oceania', "Pa'anga", 'TOP', '104227');
        $this->countries['TP'] = array('East Timor', 'TT', 'TL', 'TLS', '626', '--', '', 'Timor Escudo', 'TPE', '0');
        $this->countries['TR'] = array('Turkey', 'TU', 'TR', 'TUR', '792', 'Ankara', 'Middle East', 'Turkish Lira', 'TRL', '66493970');
        $this->countries['TT'] = array('Trinidad and Tobago', 'TD', 'TT', 'TTO', '780', 'Port-of-Spain', 'Central America and the Caribbean', 'Trinidad and Tobago Dollar', 'TTD', '1169682');
        $this->countries['TV'] = array('Tuvalu', 'TV', 'TV', 'TUV', '798', 'Funafuti', 'Oceania', 'Australian Dollar', 'AUD', '10991');
        $this->countries['TW'] = array('Taiwan', 'TW', 'TW', 'TWN', '158', 'Taipei', 'Southeast Asia', 'New Taiwan Dollar', 'TWD', '22370461');
        $this->countries['TZ'] = array('Tanzania', 'TZ', 'TZ', 'TZA', '834', 'Dar es Salaam', 'Africa', 'Tanzanian Shilling', 'TZS', '36232074');
        $this->countries['UA'] = array('Ukraine', 'UP', 'UA', 'UKR', '804', 'Kiev', 'Commonwealth of Independent States', 'Hryvnia', 'UAH', '48760474');
        $this->countries['UG'] = array('Uganda', 'UG', 'UG', 'UGA', '800', 'Kampala', 'Africa', 'Uganda Shilling', 'UGX', '23985712');
        $this->countries['UK'] = array('United Kingdom', 'UK', 'GB', 'GBR', '826', 'London', 'Europe', 'Pound Sterling', 'GBP', '59647790');
        $this->countries['UM'] = array('United States Minor Outlying Islands', '--', 'UM', 'UMI', '581', '--', '', 'US Dollar', 'USD', '0');
        $this->countries['US'] = array('United States', 'US', 'US', 'USA', '840', 'Washington, DC', 'North America', 'US Dollar', 'USD', '278058881');
        $this->countries['UY'] = array('Uruguay', 'UY', 'UY', 'URY', '858', 'Montevideo', 'South America', 'Peso Uruguayo', 'UYU', '3360105');
        $this->countries['UZ'] = array('Uzbekistan', 'UZ', 'UZ', 'UZB', '860', 'Tashkent', 'Commonwealth of Independent States', 'Uzbekistan Sum', 'UZS', '25155064');
        $this->countries['VA'] = array('Holy See (Vatican City)', 'VT', 'VA', 'VAT', '336', 'Vatican City', 'Europe', 'Euro', 'EUR', '890');
        $this->countries['VC'] = array('Saint Vincent and the Grenadines', 'VC', 'VC', 'VCT', '670', 'Kingstown', 'Central America and the Caribbean', 'East Caribbean Dollar', 'XCD', '115942');
        $this->countries['VE'] = array('Venezuela', 'VE', 'VE', 'VEN', '862', 'Caracas', 'South America, Central America and the Caribbean', 'Bolivar', 'VEB', '23916810');
        $this->countries['VG'] = array('British Virgin Islands', 'VI', 'VG', 'VGB', '92', 'Road Town', 'Central America and the Caribbean', 'US dollar', 'USD', '20812');
        $this->countries['VI'] = array('Virgin Islands', 'VQ', 'VI', 'VIR', '850', 'Charlotte Amalie', 'Central America and the Caribbean', 'US Dollar', 'USD', '122211');
        $this->countries['VN'] = array('Vietnam', 'VM', 'VN', 'VNM', '704', 'Hanoi', 'Southeast Asia', 'Dong', 'VND', '79939014');
        $this->countries['VU'] = array('Vanuatu', 'NH', 'VU', 'VUT', '548', 'Port-Vila', 'Oceania', 'Vatu', 'VUV', '192910');
        $this->countries['WF'] = array('Wallis and Futuna', 'WF', 'WF', 'WLF', '876', 'Mata-Utu', 'Oceania', 'CFP Franc', 'XPF', '15435');
        $this->countries['WS'] = array('Samoa', 'WS', 'WS', 'WSM', '882', 'Apia', 'Oceania', 'Tala', 'WST', '179058');
        $this->countries['YE'] = array('Yemen', 'YM', 'YE', 'YEM', '887', 'Sanaa', 'Middle East', 'Yemeni Rial', 'YER', '18078035');
        $this->countries['YT'] = array('Mayotte', 'MF', 'YT', 'MYT', '175', 'Mamoutzou', 'Africa', 'Euro', 'EUR', '163366');
        $this->countries['YU'] = array('Yugoslavia', 'YI', 'YU', 'YUG', '891', 'Belgrade', 'Europe', 'Yugoslavian Dinar', 'YUM', '10677290');
        $this->countries['ZA'] = array('South Africa', 'SF', 'ZA', 'ZAF', '710', 'Pretoria', 'Africa', 'Rand', 'ZAR', '43586097');
        $this->countries['ZM'] = array('Zambia', 'ZA', 'ZM', 'ZWB', '894', 'Lusaka', 'Africa', 'Kwacha', 'ZMK', '9770199');
        $this->countries['ZW'] = array('Zimbabwe', 'ZI', 'ZW', 'ZWE', '716', 'Harare', 'Africa', 'Zimbabwe Dollar', 'ZWD', '11365366');
        $this->countries['PX'] = array('Proxy Server', 'PX', 'PX', 'PX', '0', '', 'Internet', '', '', '0');
    }
}
