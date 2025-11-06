<?php


/**
 * Проверка за най-честите опити за hakwane
 *
 *
 * @category  bgerp
 * @package   dim
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 */
class core_HackDetector extends core_MVC
{

    /**
     * Проверява дали стринга съдържа хак с ниво по-голямо от $threashole. Ако да - спира изпълнението и дава грешка
     */
    public static function check($str, $threashole = null)
    {
        if(!is_string($str) || strlen($str) < 3) return;
        
        if(!defined('BGERP_HACK_TOLERANCE')) return;

        $score = max(
                self::sqlInjectionScore($str),
                self::xssLikelihoodScore($str),
            );

        if($score > $threashole ?? BGERP_HACK_TOLERANCE) {
            error('400 Bad Request');
        }
    }
    
    public static function sqlInjectionScore($input) 
    {
        $commentPattern = '/(--\s.*?$|#.*?$|\/\*[\s\S]*?\*\/)/m';
        $hasComments = (bool) preg_match($commentPattern, $input);
        $noComments = preg_replace($commentPattern, ' ', $input);

        // Normalize: up to 3 URL-decodes, HTML entities, strip simple escapes, lowercase, collapse ws
        $clean = $noComments;
        for ($i = 0; $i < 3; $i++) {
            $dec = rawurldecode($clean);
            if ($dec === $clean) break;
            $clean = $dec;
        }
        $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5);
        $clean = str_replace(["\\'", '\\"', '\\\\', '\\;'], ['', '', '', ''], $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);
        $clean = trim(mb_strtolower($clean, 'UTF-8'));

        if ($clean === '') {
            return 0;
        }

        $rawScore = 0.0; $flags = [];
        $mark = function($f) use (&$flags){ if(!in_array($f,$flags,true)) $flags[]=$f; };

        // 1) Tautologies (OR/AND … = …, 'a'='a', numeric)
        if (preg_match('/\b(or|and)\b\s+[^;]{0,40}=\s*[^;]{0,40}/', $clean) ||
            preg_match('/([\'"]?)(\d+|[a-z0-9]+)\1\s*=\s*\1\2\1/', $clean) ||
            preg_match('/\b\d+\s*[\*\+\/-]\s*\d+\s*=\s*\d+/', $clean)) {
            $rawScore += 1.4; $mark('tautology');
        }

        // 2) UNION/SELECT (incl. SELECT expr w/o FROM)
        if (preg_match('/\bunion\b\s+(all\s+)?\bselect\b/', $clean) ||
            preg_match('/\bselect\b\s+[^;]{1,200}\bfrom\b/', $clean)) {
            $rawScore += 1.5; $mark('union_select');
        } elseif (preg_match('/\bselect\b\s+(?:\d+|[\(\)\*\+\-\,\s]{3,}|case\s+when)/', $clean)) {
            $rawScore += 1.0; $mark('select_expression');
        }

        // 3) Metadata/schema
        if (preg_match('/\b(information_schema|pg_catalog|sqlite_master|all_tables|dba_|user_tables|dual)\b/', $clean)) {
            $rawScore += 1.3; $mark('metadata');
        }

        // 4) Time-based
        if (preg_match('/\b(pg_sleep\s*\(|sleep\s*\(|waitfor\s+delay|benchmark\s*\()/',$clean)) {
            $rawScore += 2.0; $mark('time_based');
        }

        // 5) File/exec
        if (preg_match('/\b(into\s+outfile|into\s+dumpfile|load_file\s*\(|xp_cmdshell|exec\s*\(|sp_executesql|system\s*\(|shell_exec\s*\()/',$clean)) {
            $rawScore += 1.8; $mark('file_exec');
        }

        // 6) Engine-specific (Oracle et al.)
        if (preg_match('/\b(dbms_pipe|dbms_output|utl_http|dbms_lob|xmltype|dbms_xml)\b/',$clean) ||
            preg_match('/\bchr\s*\(\s*\d{1,3}\s*\)/',$clean)) {
            $rawScore += 1.3; $mark('engine_funcs');
        }

        // 7) MySQL error-based & obfuscation: EXTRACTVALUE/UPDATEXML, ELT, CONCAT, 0xHEX, CAST/CONVERT
        if (preg_match('/\b(extractvalue|updatexml|elt\s*\(|concat\s*\()/',$clean) ||
            preg_match('/0x[0-9a-f]{4,}/',$clean) ||
            preg_match('/\bcast\s*\(|\bconvert\s*\(/',$clean)) {
            $rawScore += 1.3; $mark('mysql_error_based');
        }

        // 8) CASE WHEN inside SELECT (common in scanners)
        if (preg_match('/\bselect\b[^;]{0,200}\bcase\s+when\b/',$clean)) {
            $rawScore += 1.0; $mark('case_when');
        }

        // 9) ORDER BY column-index tests (ORDER BY 1,2,… often with comment)
        if (preg_match('/\border\s+by\s+\d+(?:\s*,\s*\d+)*/',$clean)) {
            $rawScore += 0.8; $mark('order_by_index');
        }

        // 10) Stacked queries via semicolon
        if (strpos($clean,';') !== false) {
            $rawScore += 0.7; $mark('stacked_queries');
        }

        // 11) Special chars density
        $specialCount = preg_match_all("/['\"#;\\-\\/\\*]/", $clean);
        if ($specialCount >= 3) { $rawScore += (min($specialCount,8)/8)*0.9; $mark('special_chars'); }

        // 12) Comment-obfuscated keywords (se/**/lect, uni/**/on)
        if (preg_match('/se\s*\/\*.*?\*\/\s*lect|\buni\s*\/\*.*?\*\/\s*on/',$clean)) {
            $rawScore += 0.9; $mark('comment_obfuscation');
        }

        // 13) Boolean keyword density
        $boolWords = preg_match_all('/\b(or|and|not)\b/',$clean);
        if ($boolWords >= 3) { $rawScore += 0.6; $mark('bool_density'); }
        elseif ($boolWords == 2) { $rawScore += 0.35; }

        // 14) Closing quote + OR pattern
        if (preg_match('/[\'"]\s*\)\s*or\b|or\s+[\'"]?\d+=[\'"]?\d+/', $clean)) {
            $rawScore += 1.1; $mark('closing_quote_or');
        }

        // Remember original comments as a signal
        if ($hasComments) { $rawScore += 0.9; $mark('original_comments'); }

        // Payload length heuristic
        $len = mb_strlen($clean,'UTF-8');
        if ($len > 200) { $rawScore += 0.8; $mark('long_payload'); }
        elseif ($len > 100) { $rawScore += 0.4; }

        // Map to 0..4
        if ($rawScore <= 0.5)      $score = 0;
        elseif ($rawScore <= 1.6)  $score = 1;
        elseif ($rawScore <= 2.6)  $score = 2;
        elseif ($rawScore <= 3.8)  $score = 3;
        else                       $score = 4;

        return $score;
    }


    /**
     * Heuristic XSS detector.
     * Returns 0..4 (higher = more likely XSS).
     * Uses multi-pass decode + HTML comment stripping + lowercase/whitespace normalize,
     * then applies weighted signatures for classic reflected/stored XSS vectors.
     *
     * NOTE: Heuristic only — use proper output encoding & CSP for real protection.
     */
    public static function xssLikelihoodScore($input) 
    {
        // 1) Strip HTML comments (common for obfuscation)
        $noComments = preg_replace('/<!--.*?-->/s', ' ', $input);

        // 2) Multi-pass URL decode (up to 3) + HTML entities
        $clean = $noComments;
        for ($i=0; $i<3; $i++) {
            $d = rawurldecode($clean);
            if ($d === $clean) break;
            $clean = $d;
        }
        $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5);

        // 3) Normalize: collapse whitespace, lowercase
        $clean = preg_replace('/\s+/', ' ', $clean);
        $clean = trim(mb_strtolower($clean, 'UTF-8'));

        if ($clean === '') return 0;

        $score = 0.0;

        // Helper checker
        $hit = function(bool $cond, float $w) use (&$score){ if ($cond) $score += $w; };

        // --- High-risk direct script execution ---
        // <script ...> or obfuscated split like <scri<script>pt>
        $hit(preg_match('/<\s*script\b/', $clean), 2.3);
        $hit(preg_match('/scri\s*<\s*script\s*>\s*pt|<\s*\/\s*script\s*>/', $clean), 0.8);

        // javascript: or vbscript: URLs (href/src/etc)
        $hit(preg_match('/\b(?:href|src|xlink:href|formaction|lowsrc|background)\s*=\s*["\']?\s*javascript:/', $clean), 2.0);
        $hit(preg_match('/\b(?:href|src|xlink:href)\s*=\s*["\']?\s*vbscript:/', $clean), 1.2);

        // data: URIs with script-capable mime (svg/xml/html)
        $hit(preg_match('/\b(?:src|href)\s*=\s*["\']?\s*data:\s*(?:text\/html|image\/svg\+xml|application\/xml)/', $clean), 1.6);

        // on* event handlers (onerror, onload, onclick, etc.)
        $hit(preg_match('/\bon[a-z0-9_-]{3,}\s*=\s*["\']?/', $clean), 1.6);

        // srcdoc (iframe HTML injection)
        $hit(preg_match('/\bsrcdoc\s*=\s*["\']/', $clean), 1.4);

        // document.* / window.* / alert( / prompt( / confirm(
        $hit(preg_match('/\b(?:alert|prompt|confirm)\s*\(|\bdocument\.(?:cookie|write|location)|\bwindow\./', $clean), 1.0);

        // --- Dangerous elements/contexts ---
        $hit(preg_match('/<\s*(?:iframe|object|embed|link|meta|svg|math|foreignobject|base)\b/', $clean), 1.2);
        // <img ... onerror= / svg onload=
        $hit(preg_match('/<\s*img\b[^>]*\bon[a-z0-9_-]{3,}\s*=/', $clean), 1.2);
        $hit(preg_match('/<\s*svg\b[^>]*\bon[a-z0-9_-]{3,}\s*=/', $clean), 1.2);

        // meta refresh to javascript/data
        $hit(preg_match('/<\s*meta\b[^>]*http-equiv\s*=\s*["\']?refresh[^>]*content\s*=\s*["\'][^"\']*(?:url\s*=\s*javascript:|data:)/', $clean), 1.4);

        // base href that could alter relative links target (less certain)
        $hit(preg_match('/<\s*base\b[^>]*href\s*=\s*["\']?javascript:/', $clean), 1.0);

        // style attribute / CSS with expression() or url(javascript:...)
        $hit(preg_match('/\bstyle\s*=\s*["\'][^"\']*(?:expression\s*\(|url\s*\(\s*javascript:)/', $clean), 1.3);

        // SVG/XLink javascript
        $hit(preg_match('/\bxlink:href\s*=\s*["\']\s*javascript:/', $clean), 1.5);

        // Template-esque curly payloads combined with tags (weak signal)
        $hit(preg_match('/{{[^}]+}}.*<[^>]+>/', $clean), 0.4);

        // Attribute breaking + JS start (e.g., '" onerror=' / "';alert(1);//)
        $hit(preg_match('/["\'][\s\/]*on[a-z0-9_-]{3,}\s*=/', $clean), 0.9);
        $hit(preg_match('/["\']\s*;\s*(?:alert|prompt|confirm)\s*\(/', $clean), 0.9);

        // Many angle brackets & quotes density (generic but useful)
        $specials = preg_match_all('/[<>"\'`]/', $clean);
        if ($specials >= 8)        $score += 0.8;
        elseif ($specials >= 5)    $score += 0.5;
        elseif ($specials >= 3)    $score += 0.3;

        // Long payload heuristic
        $len = mb_strlen($clean, 'UTF-8');
        if ($len > 200) $score += 0.4;
        elseif ($len > 120) $score += 0.2;

        // Map to discrete 0..4 (tuned conservatively)
        if ($score <= 0.4)       return 0;
        if ($score <= 1.2)       return 1;
        if ($score <= 2.2)       return 2;
        if ($score <= 3.2)       return 3;

        return 4;
    }


    /**
     * Heuristic spam detector for search queries.
     * Returns integer 0..4 (higher = more likely spam).
     *
     * Goal: normal short searches (1-3 words, product codes) -> score < 2
     *       spammy promotional lines with domains/visit/buy/etc -> score >=3
     */
    function spamLikelihoodScore($input)
    {
        // normalize
        $s = trim($input);
        if ($s === '') return 0;
        // remove the prefix like "Търсене в продуктите:" if present
        $s = preg_replace('/^[^\:]{1,60}:\s*/u', '', $s);
        // decode a few layers
        for ($i=0;$i<2;$i++) {
            $d = rawurldecode($s);
            if ($d === $s) break;
            $s = $d;
        }
        $s_norm = html_entity_decode($s, ENT_QUOTES | ENT_HTML5);
        $s_lower = mb_strtolower($s_norm, 'UTF-8');

        // quick tokenization
        $words = preg_split('/\s+/u', $s_lower, -1, PREG_SPLIT_NO_EMPTY);
        $wordCount = count($words);

        $raw = 0.0;

        // patterns / indicators
        // 1) domain / url / www / http presence (strong)
        $domainPattern = '/(?:https?:\/\/|www\.|[a-z0-9-]{2,}\.(?:com|net|org|cn|ru|cc|tk|info|biz|shop|site|online|club|io|store|top|pro|vip|me|xyz))/i';
        if (preg_match($domainPattern, $s_lower)) {
            $raw += 2.0;
        }

        // 2) promotional words (multilingual)
        $promoWords = [
            'buy','visit','visitez','comprar','comprar','purchase','buyfc','buyfc26','sale','discount','promo','promotions',
            'prix','gratuit','free','offre','off','code','coupon','offer','for sale','vente','acheter',
            'best bot','bestseo','coins','coins ','coins,','bot','bots','hack','hacker','hacken','phishing','instagram hack',
            'visit sig','visit kung','kunghac','kungx','buyfc26coins','coinsnight','sig8','kungx.cc'
        ];
        foreach ($promoWords as $pw) {
            if (mb_strpos($s_lower, $pw) !== false) {
                $raw += 0.9;
            }
        }

        // 3) explicit "Visit" style with bracketed site markers or "【" "】"
        if (preg_match('/(visit|visitez|visit\s+site|visit\s+www|【|】|\[Visit|\[HackerSite|\[Best BOT site|\{Visit)/i', $s_norm)) {
            $raw += 0.8;
        }

        // 4) giveaway / promo phrases like "30% off", "30% OFF", "code: FC2026"
        if (preg_match('/\b\d{1,3}\s*%\s*off\b|\bcode[:\s]/i', $s_lower)) {
            $raw += 0.9;
        }

        // 5) 'hack', 'phish', 'instagram', 'whatsapp', 'facebook' combined with promotional context
        if (preg_match('/\b(hack|phish|phishing|hacker|hacked|hacken|instagram|facebook|whatsapp|telegram|bot|cheat|crack)\b/i', $s_lower)) {
            $raw += 1.0;
        }

        // 6) many words (long text) -> suspicious (spammy descriptions)
        if ($wordCount >= 6) {
            $raw += 0.8;
        } elseif ($wordCount >= 4) {
            $raw += 0.3;
        }

        // 7) presence of multiple domain-like tokens or many dots with short fragments
        preg_match_all('/[a-z0-9-]{2,}\.(?:[a-z]{2,4})/i', $s_lower, $dm);
        $domainCount = count($dm[0] ?? []);
        if ($domainCount >= 1) $raw += min(1.5, 0.9 + 0.3*($domainCount-1));

        // 8) trailing random suffixes like ".k6Yy" or ".sjpcq8651.ryF4" -> typical spam markers
        if (preg_match('/\.[a-z0-9]{2,10}(?:\.[a-z0-9]{2,10})?$/i', trim($s_lower))) {
            $raw += 0.4;
        }

        // 9) presence of non-latin scripts combined with latin promo words (typical spam)
        if (preg_match('/[\p{Arabic}\p{Cyrillic}\p{Han}\p{Hangul}\p{Hebrew}]/u', $s_norm) && preg_match('/(buy|visit|coins|promo|site|visit)/i', $s_lower)) {
            $raw += 0.7;
        }

        // 10) "buy" or "visit" directly adjacent to domain or TLD -> high
        if (preg_match('/(buy|visit)[^a-z0-9]{0,6}(?:www\.|https?:\/\/|[a-z0-9-]{2,}\.(?:com|net|org|cn|ru|cc|info|shop))/i', $s_lower)) {
            $raw += 1.0;
        }

        // 11) spammy numeric tokens counts (many tiny tokens like cn67c2s etc)
        $shortAlphaNumTokens = 0;
        foreach ($words as $w) {
            if (preg_match('/^[a-z0-9]{4,9}$/i', $w) && preg_match('/[0-9]/', $w) && preg_match('/[a-z]/i', $w)) {
                $shortAlphaNumTokens++;
            }
        }
        if ($shortAlphaNumTokens >= 2) $raw += 0.5;

        // 12) presence of "best", "trusted", "guarantee", "support", "service" in promotional context
        if (preg_match('/\b(best|trusted|guarantee|support|service|client|customer|trusted)\b/i', $s_lower)) {
            $raw += 0.4;
        }

        // 13) phone numbers or explicit payment words (visa, mastercard, paypal)
        if (preg_match('/\b(?:visa|mastercard|paypal|payment|paiement|paiement|paiement)\b/i', $s_lower)) {
            $raw += 0.5;
        }
        if (preg_match('/\+?\d{6,}/', $s_lower)) {
            $raw += 0.4;
        }

        // --- final adjustments / caps to avoid false positives on normal short searches ---
        // map raw to discrete score (conservative thresholds)
        // typical raw range 0..~6
        if ($raw <= 0.5) $score = 0;
        elseif ($raw <= 1.4) $score = 1;
        elseif ($raw <= 2.4) $score = 2;
        elseif ($raw <= 3.6) $score = 3;
        else $score = 4;

        // if query is short (<=3 words) and no domain and no promo keywords, force low score <=1
        $hasPromoKeyword = (bool) preg_match('/\b(buy|visit|promo|discount|code|coupon|sale|free|offer|coins|bot|hack|phish|hacker)\b/i', $s_lower);
        if ($wordCount <= 3 && !$hasPromoKeyword && $domainCount === 0) {
            if ($score > 1) $score = 1;
        }

        return (int)$score;
    }


}
