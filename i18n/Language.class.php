<?php



/**
 * Клас 'i18n_Language' - Откриване на езика на текст
 *
 * Библиотека с функции за откриване на езика на стринг
 *
 *
 * @category  vendors
 * @package   i18n
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class i18n_Language
{
    
    /**
     * Масив с най-често срещаните 3-буквени последователности в различните езици
     */
    public static $lgAnalyzer;
    

    /**
     * Определя езика в началото на даден текст
     */
    public static function detect($text, $prefLangArr = array())
    {
        $res = self::getLgRates($text);

        if (!count($res)) {
            
            return ;
        }
        
        foreach ((array) $prefLangArr as $lg => $ratio) {
            $lg = strtolower($lg);
            
            if (!$res[$lg]) {
                continue;
            }
            
            $res[$lg] *= $ratio;
        }
        
        $maxs = array_keys($res, max($res));
        
        return $maxs[0];
    }


    /**
     * Връща рейтингите на различните езици спрямо дадения текст
     */
    public static function getLgRates($text)
    {
        self::prepareLgAnalyzer();
        
        $rate = array();

        // Премахваме .com
        $text = str_replace(array('.com', '.COM'), array(), $text);
        
        // Намираме масива от текста
        $arr = self::makeLgArray(mb_substr($text, 0, 1000));
        
        foreach (self::$lgAnalyzer as $lg => $dict) {
            foreach ($dict as $w => $f) {
                if ($arr[$w]) {
                    $rate[$lg] += sqrt($f * $arr[$w]);
                }
            }
        }
        
        if (is_array($rate)) {
            arsort($rate);
        }
        
        return $rate;
    }
    
    
    /**
     * Подготвя масив с 2-3-4 буквени под думи, които се срещат в текста
     */
    public static function makeLgArray($text)
    {
        $pattern = '/[^\p{L}]+/u';
        
        $text = preg_replace($pattern, ' ', $text);
        
        $text = mb_strtolower($text);
        
        $textArr = explode(' ', $text);
        
        foreach ($textArr as $word) {
            $wordLen = mb_strlen($word);
            
            if ($wordLen >= 5) {
                $count[mb_substr($word, 0, 3) . '*']++;
                $count['*' . mb_substr($word, $wordLen - 3)]++;
            } elseif ($wordLen > 1) {
                $count[$word]++;
            }
        }
        
        return $count;
    }


    /**
     * Подготвя анализатора за езици
     */
    private static function prepareLgAnalyzer()
    {
        if (!self::$lgAnalyzer) {
            self::$lgAnalyzer = unserialize(gzuncompress(base64_decode('
                eJx9vdl2FEfWNnwvPswjawTcV5NVlarKqhzKOZRa+te7FhLG4AFjd7sb29jtVhs3pt0MEgiEhMRBSacg
                XQMI8F38MewdsZ8Q/S3pZEfGuGN6ak8RfzT34Uf/X/3R7EcfdPof/Cn+aGbB0Oc++iA6uX6yfvzggz/1
                Pppb+FP90fxHH5xcOd4Geu14x9Bzf7JFjh+4Ih+qpPMffXC8a9IOdNrsImfbUUnbmPTgeD9M2tJFTdK8
                rUxXpFNNmm5gkdKe6pSZ87ZXqiLT3Mw5og9sLUyffGYbMrTu4vOTi8fbx09MGjd+ss4jmVng/hzwQHzS
                dpikx3u8G5mkedvD492Tz4+fuRSd6YnK9Nxn0p18dnIJsmypQk8imRSdfKoYdx2SDCdtB2gONCNP1iOZ
                FB3vOZ7McXN25nyWHT3dkGWfxi8qPn5oK57lHl3n6eAkNf6D4x3IpfpznVkyS1NA0z8zw80/VDOw55IW
                DY8OaLAzrtCOo+1qeKDZLSvSnWbOzvihXaRctCjVut1T7T1yabR2r1KvOEnl2xUlTa794x1abrzCVUUq
                bT+sjOeFkk4+UZn2IpekOfwQs6hJUdMUYRcuujn/0K0MtVxNrgtyGW6LFM2Gk4uYZ5crukCtP7JD4yI7
                iuPPZZHjxyptCyvZ0eMPUrZ8PfNmmTzBnmzTqnFF9B6G3m7pLSzzqBEeHD+KRLVqKmSRyGzZR1DtU2a5
                r2SbKjnvWLdGi5NTdl3T52nd6dNgR2bRjNmFQk80fyHl4GSNNh2nqLml6T7vFt0DNcMHIpM+ZT4/uQgp
                V3irnvfseggpx5vqaPBVa57v+R7ziqTdS2VU41u0tHiceqc8hYb2eYW4Qm6lSXY9hBS90taioMNb0Li9
                SXBQagXYLcP1PNeL2NRzjjq4o7j1gBPojtiyA3Up2yrPNqfwmfAEMuk+n1yPRIoa1+cnn7mUeXsgyyIH
                6szGzhzwreGaVjw/fg7tnFyiaRF5du24Oc9zPd8RNKW6vB8F/SWui4Hv4cD1coOa1bo5oM3K9ey4NckX
                nVp9alNvQyY19C1s7DGfDMD3BzjUi7QBPN8PAr5Hx5u6S5DyhCtapMt7ja/GRTF4y0QHA/SJuwspu3ox
                y1J6EV6RpQxbdyHlods1rp5tvuP9rS87qNm67ka6SEzcVMXoovBVy0yMXWBg+qB8YFe8aGwfh/FMb0no
                9I6ai22ZYjZymLJ3cglS9J2xhv3Z5fXCNT9SjN6EtvSk7kRBHqxH7dI9HKlKCdqy51wUjitkvb40A/7s
                4WSYOaSU/zMQNSkERJ376INmkJib8ZyGQ4qOi545UggwNIO40bSBq+pzbbPPnrPkIKkcqXIvp83A0ITI
                oiYxtc1q/DOrsxvK1Z2YARkYqtGz7ckFqjqtDXnefiyXHGWKiq+K7patvfMXaRRVIsmibBypci9VZW5o
                wuB1nJpuMpCMyrZxtCreEaT6HHdKjw11T+Nakitl60g7yhVDE26Lxlki6e4g9rhOcwFyL6dZZmjiWVRV
                K5IexJMEvreZHcqH/L3yYEjV3pSOMjzO5ceykkWjJA3oupa0mgOJLqIsWxHk8kCvNP+1P2jEbRsVdmEw
                mTYrTKpulUUiP7Y1kBUUXaZeuJomiagpttyjj8mfx5H4uGTHe55nUVY7TkqXVXFmWfYgbgbQZNsfCLId
                9+S1qFZDxaRqpRsX4mOWxDJv1ItXmFSN1qX8VsSZIOtBCSX7SSXIOMOyjV1y53jioGyV1FBVXPUkOUpq
                SabZiiDzspI1x30Yz1KcuxNLt5vmiSDjbld+XUorIEsgO4ldTrShlwel+BjFfVlxv4pl0WU6wphMqkTU
                lDY1U7N68mS1E8tUIksko7Toi3qGpexClCe1HOpSE4m8RbKMzdTiI509izw5MJaoLgs5Fjr2mElVKckB
                lh3GhSSruCPJepxIMpkAGXVg7GnRQw4ncnhVK/sYtRVwJrYHmOOiXdYL3E7aMDmrJ0d+i6takCtJXMmv
                dBUR2ZTLkSCzdCkRZEk7mM//pJJkL60l2SnLkezTkvxWpVhRFyoqE9njLk0P9zizBxmRy1hVlo4SGF46
                lmQPykalZSqRw7YGxlVANgMYbSeBdtMChqDWvKw5KvpyRFGSya8rtBz5a5ol0FADvBpDN+JiJYYR1Qn0
                yl5crmZ7jC4QqOklCGpaC2LOcfbWbtUFupKT1O6Eebf2LQvmCHpM7Cab46No+syiHLopeqmFMQwH2q4H
                PfrOac0KYcyz2jpKL4nEsmh2luoiAEWXv72PGSCphiJJ1ym1NOPGYXtC17miNckIKrfbZ+YC99Mua5bh
                ldQ201FSNY7WJ2MNoEkdaQXQaWKRiVsLVSHptmhAOqfgBKCo5bTyoMtwIpfksmUyo6g6tVPKdJF2I0n3
                2sqjKDMJtSPt6VAAXVlOMq1wAhRXm0J+HsYDLw8z+zwFKVQRU3UOKhUehWnQDUCqbloo3YstsKJpW21z
                psyuqRzK0heU/KYQWcLkwkcfTJ/RKU2f+8myBGhx1ZHkhC5YD6sKQdYlIKd+UsjMHbpZGIMlHoPp3whp
                AkX7WBTy0t3MyIoGwHnTDLvYk9iuZ2G3A341IJF2ySGRM1CqbRLxLc7lt6SQ1XTaArBRkcjMy7ToGJM1
                iSSTCnDVMAFcyDcqkavElnN+KvHetwuMyaqBO7U7cJhBL95UYo9OMoKamhZQTLOK4CiTmUdxIVFXD0AN
                Y06Pq6CdAVz7Sdp3X9X4RtPHAEb6Fd4cSeZuLH2Q8WlvPy4lklSnHBSlrUvkAK/2nNbxAh/0ACGSoSu6
                oFkzvSXvzaW4kFV1aD4dmQXdgD5XHp3oww2gS1z0oahCi4JcbQGNBNemWpFw1Q8aeW0OCON5jCRv70kK
                I4hquHLVHJjM88w5u0D5NM/sDM0zb7ryqzo9JJlUAbkEVRWFrGp1GRsaWUbO20nJp4/72A//WSNru7j5
                Y5x1BJnmuSxalHZPETmhn0F8PdEP53me3lVotoWyancGncoF2aNFx90oYPijKkXWwfiWXa8s0qnaQMN4
                vH/yxcllk8XpoZy0e55VOloybGWmc1K9cvKlTpp14v991gPOsnZO5VmzAu1Zrl6r0KzciZNOPtGaJw9V
                WKtG1bOW8YpTYnpN0MkXpC+6IJOspk8IzFkbx4L3R8d7pNHhTI+0aDCSSUbAa7sw40W1+yefRDLJykat
                UmxR9uEpJj1w6j6Su2qdTKC33HfyY04ygrw9j4POWS1lkKRVNSQA9FrJPWa8111+cVbl+EALYF3SvJG7
                7zt6kZWpWMgpKXzSru4plNtUzVltImsqxYhd0o5WH8gkrUzgscw6JvDqcwrFky/d8GZkt77CpF2nmCaF
                pdEJPSI974xYfs+isLIHWL+iP2Gt6ozjOyteXNKaFsIHfV1nIbzXTR44ZbPfT6xgcFrGHa9G5qQDxdeL
                ERZEdajtBAuEXcFNtWq8No+Y8xgVemYBglZQNUdrzSn9jN5W5nmPLlSrPb6UeR44ubav+RHttwuSAVKN
                uURXIhfZBHWeFfHz4eX3LPPaK7VY9XReLoFAO7Ye6suMrlZWLZTHIs+BXb1C44iaOKNXfQKljGIVuqhG
                QWrfwHyCm76o9R3Y9I7ecjrFn0Mnl+zy58PKa2TOyQ6jOmibFfNOrXWdTwmX8mWgQhOaTH8wPkOFmdaG
                n1yGPIZb2J99HqlXxUmFmcnzMNCFqftjG9V1ZgM/wlIPTr6yB5vQqe0Ho3AL14+Lp++c5zwpW7mHT1TV
                n0GKWmE0fULtJ1LmTYrpjNCrXARNi9Dq8v1wwHp+TnBaca/h4nvGa3mYEYuu4ctYJFSBWc0/pOgj+CKn
                eB2PUBypCw5SdvVBLpoNNF16f9GVISp5BilqEh+Ivp8DHf//1H0JgxyXssZWKkKv9SxIOVBd3oMUvVsk
                q8xJsil5GWXpn2VntFnFQXSGD8DLk4uMUKDDX52Zk72AOXvQYbW1eV+IIexZJjN8eOTOxwW5QLZlni3d
                a5nH2CA8iTAPM5lTDtzxzbjkkl6NkHLFXdILnu10oHM9WtN9AKXWVLmdoD98/7N0TsuJHGZVG+HF7ovN
                F4bL5whsvPzkxfOXFsTS3aFTDM2XsEqgQvMfukJPXppdOUdimhf7LzY1bQAt5Xix71J0jmcvrIEcAckX
                my8vvrQolawUXjx88ZRq4c5d5M7NEqB5sa3SngLeVdWsvbBHhkO3Ly9xBzlJDUF1EXK9vPTy8gsCrnyT
                6vZsv33S9ounti6Gt6oL2y/XPOLVTLtkR+cQsBrK7ov/nk2655IMl15+Rq35qje56nNuPjYdrbNc1myx
                WXhFv7jPjGL4++KRZiYgYjM4Ggn90nzx3E6sz6L6aGfFJz3R3IS6N30P5n0PNl96OGyS9nndcJLq1JMX
                BBc5aVuV3I+woGKTqOs8r1JK46Wg1vLLTwDuvthS/d/ySXYhPhXldMqXOs2lWA4/kdXo0b3Y89WcN2vF
                rWi28dOdEinEhW1qzSXtvvwqzLXp5vRDP+TnL6+5pEXDq+0XYJancji280a8xLt1Rqxz5orYDdt2xLwX
                d2zzF3zrd223xaK/D3nMZkFLON3jF9IKSu/El19KsKU3PfXG7QK1dWzN50U9LwFwaPZAimLi5Rdo6KPy
                bPs8zIsnMsuLPVU1VKyWJHGCd87Lz3h1L/pl9QIMSXSRF4+ClL0XaBLy4j5v5UXBMDAkMVsSjF8ixQo6
                8uS2fQ53gZosNRkyRfX4PpSK/EFCKS+vuHOEa37mVo/Y2Z9g65tqk30SYc3MQ19KsWgbUjaZhy7lMfN5
                wU879lCtuCtBf1Rbd6Etw43/yjx2wcO49I1w36Wcp5qxebMD8IJ/cZfvLHeEPVN/MkVVvPfiSSRSVC27
                xCBX6qE6UNYxzwGdvC7l5ac08a7UPq9eX2ofazaD3w1KMRNdD9WO+8rl4YPqKVStN/M+3enilrkIjT1R
                Q4Uumqo/g6q/VGmfYdVm+2xDiuO9S1kLxqpXC20NkrWdqfaSPsc4ZdEcJE9xttSS/wp4YdqxG2VenAib
                spazc77HW4kZsavvRGCNOsPo5OOUh+7O8r3hK4vzbOkbC0qpDfnyimSovcEsSuCkTX2y+CS7etawfQNJ
                sNcP9RzD9F1y28uVeiRXD6+V58BEledyMDRz/28HTGPMKBrbDrt4P5jTy3xKiBXmNorFq/UEtckkJ75A
                +rm4MeLqC6TfjSfmLCXx29L0sZVAO5sXq0ebJ/m1/m4bowJRVRi99xzrdHsTiw+9vUTP0ar16S2rMCZj
                s8R2ZpZUiSPSV7HOtyVDDWc3Rxo1VuIWpARkQ7laGMZpcmT6NsOdr6a/Fi5BVz8eR5KOkiqW9IAk32wa
                N/21cnjGaJBioJfIcMepWcsc6EFcgAVaPRKKVNW/bHqriGSGtLDqELZ3q0lBTvmjUT39VeYfxL1E0mV3
                FMnyo7iQn0dk0OEs4iRtlBW/Fg6sGA2LVdYQmZBVEH+lxmmZjW3XiEqsBQNn7cXSuC4nVdR52+z08SSR
                lm05aHajfgIq1wI0u6wI58xJI5WsUg1MDUWioR7UPIlRwRxDVVHhNb9exXeOe+x0o7qZW5NEWs11MrSa
                Q9u3qLAbytuvybJsVkZkj2xSuKVfhQp3VmvIZE0DOzzqIy0d2jgDsMsaVaC9VcsuFmTUq4CkXUaq0bqZ
                PnaFtc1sLJrJS6mtVTyU6tlJDJrdKh0B2QPlbZ5DH9C+axQ3sv+Kh5Vsx1rLOgs1sAWbpKBBVn2S7fTo
                +PISmb4k01p2eRBolIu+5FMx/bUvP6cFaJTrZgWHEBfwFSzloiyRfC2CXtY1mCZVsdSiBmZNEzQea1DH
                mhb+q94wdg0567EV91ENcMCqTqfqRYO3dCJ7NUDtdI3a6Z46yXAIUBUZVlCvBv6jtjiR+uXRCmqfM6iH
                7TX4a2Nn13EmlZr5iAxjvUVbLVq195Dj2pKsV20CSY7QcC4WLNZnPN/IbBsXF1LLzbawzMUSVLU1am6r
                BPTYbBHFumdrwMYK4yyZCDJfAQW5YlwhyEGSQVnaNNyLUQllJ6nUl0d0KbrMoE4exNDltkF1ci8WUEez
                vW/vLzJHo4OV1oUCD2ZXfIhH6Tzb0Rizt3meTzqVZ91NYtcY45p0NPJWdNp+NjFNz3pY5H0F/B3GuIYv
                MUeTfYWj6QacoUM09qZs4hpfwHvYoZjCmhM7ukrAcm3YZj7/nIEtkL1pAtO1PoAgxhxzcGnOzMkrx3/0
                AMljAiYn1giU4VOPjgDnCzCg7+xvweCPv9Nxa2i1VSbTf1fgTNCjA5hpNvt1eGyCHgPqIssc7S9195nO
                QkeP0YqOrxnnnRBLaza2zGIyzyWZxR5pzWo7ECa8nb8DS32ZsxwLou2JYgMwppuk3g5vQW8UmsbzfCdK
                c7lIQC4Pe9mqjU4GIrNyJGFSKSCV3mQZkBEayEUZukZkiFBKsGwrwLKNr15n1z+RX5MR+gQIEOVhNH8k
                /nJRQpDOFSFzyifz+2cHL/WkkbCEjytnRNYCaOnFEi4UxFc2dMtSWbaHpmx8lDurOEA4ORlZsbVasYpf
                fVXGvj2XnSp6EjsMSrSga6HZKIPM0hxt3nsYuCt9Iq8q/rXDZJpLkn/p8dcG7rWlMsOvQ7hQySXCNQQX
                6hKxmcg+9rkeBdbkCBByME9bIlzMV3UMmceiZrNUfoXC7Nbh4ASCDfqdvODPMvm5KBFLTdCGEMFT1IOG
                1TxI0LaUjrAmMMDre5t3jT6m/+4lUDNAIrWeoV2ywHZVY9nYm9yZG7HHlMBEbI+XrUhSlURY40GBBgEj
                8U2BNllvVFmXGCbJRtBXJEEOGyNyD0uoCY3i2DWQreCyVvawH3tTPcXGzvTXXiLofjb9d4DEKtEw2Yoz
                yNGdciDHsHlkTpU5NoyO7eafY+xR2HuA1YrMLlYiRo39PkvOYdbg1AEdcldjn8hoYh3LZnnDxPa8dz6S
                ZLjMyCchiz+W8NSJ/Ux3/qhNHanvdLrNWAAUsdW+EzmQayPdcMNYfq3bFsRFUWw542z8yQ7X2/z3wK+y
                zFL43NaAs/IUa2ti8Bgo6W5zHgQxFXd7eDv1yMnUnzVAj2pAWskgyF+TiwIzq0Ur/9L+Bjakae+W+K4P
                pBBQ0Q5kekILh+lRWaL8Kk1BXqUY0JF0UgduBORS5uRLZeXQieF+BgioBuv/ofrRKsVJFfksuKm1v54Z
                zijeCjJLQFA1SoEcx5kkJ0kiySYBD8yMfgB7sU8sySYBoETCGiKrBLDQqARHy7HwOjA28gIbKbqZ3kK/
                zF4rBU5Jlcrc6jd+4DjaSCiVxTCKPPZNzxk/R/Fx2EK7oxh8BpoYpVXka8w+A0kPpFW9RHp5Ngl4FOTx
                EDInviqNZCUoy7HkIIU+qUMEfDzTGgRdSS6byemQcF8bwJHpqBXkBAcfpX1Zs9pw8LVKa+BUhmWBNXzf
                k6huzMudCweeGslEFi6zDEkY0jAYYQPdUlyXX5PE90P/6rdCDUaZJciV4gxEVOyq4NBfKl0kqhTw66gF
                3854go6gncAZdeSAsj3JBjL3BH098xLIQew9RMyhDr4l/TSGmqBihWek+yzKFIW7r3H52MbxCaBsD/tK
                0mp3Q3b1ewV9FwCjKnDRYZJujo78XKXOy9T8/pdVpQVWVZbgudHU0ilkPN0G11H1Y78VZNKAaKop0eOz
                gsxZMIYafTqTPkgdE3DirFHMGGVDWXOUtzRCQkA1innoWpwnXkfp9KEdM/3yzkoPkJwIes6dqwLQaNWE
                9RNhgJJZ5rKPYlSXQOfTDYjcoO7ExtFG4mH7Rts86qq+uQS9Dz4myEJ0l1VM1Js4t1cs+14QnxwkSLs2
                AgL19uMWgjFETWU/OxFJDOEX2FfefScBqKNZAUZ09wyC6PkADPNC40V0v0SNUlSUHkHMaubKrxzPwOXO
                U6idVS6OJpc5R9OPFUen2Lu46gKdkG+wz186x0XTWcssxgUVxIfIp3cgM53uTHYT91VLweBbGsuS3TIv
                JRkD7FHbRIp3MpmVJfcOEmWO1Eutmt6VzTYpwJx+Ce6TDf1CIbKXZFB1VUL0i8pODEcrSVuAHgV6MlZF
                CWQqv46pGx5Pya9x28ivST2OoGwVtCuFSQVpWxnypOC3GcWA1JZaVBt2oV0WpxCZ5mPscwWQsFfKIXRj
                r5Kz8+nghj5dIBrFhPztXSSLsSTZrZ9PjhplWpVXORpd7Z0/I/poHGLQAuRMdGIMAq+4gA6ziocPsIrW
                PZ9XbQAHq1TU3AWRXSeGAajLVnZpIJEVB9Nw1RbS47VJiuBrDWyLcQBLksyF36pxrY8lJxCFdMDnM49B
                BdcrURRW5xDDo1uCqC+p8CsE/Oi2KESsQPNZtx7e6BmY0AwwhEEJnSqcidx53Ezvys+98v8R+aIXdKQL
                oUbicSw7Mizr6R3Z1HQXpI7doGMNtJzUXVlZnP55uiVzF0vAsBzZmaHrLimkPS7BYAsgWVRXpoNLWh7R
                Sqoby7AUgb9pja65geSwqcAbtSnRGzUpIOJD4+OEaHS4m0HVlVN3/p8LHCIBT8/KKVy0AHbWddeyDS8x
                RyCkax0mOEYr7yZnk1MSTXCpRxIg9zOfoiy5e6bxUhfdOQspqK3C3hQO8KTN9J8uYV6feH0APP1UACAT
                TMZW58XeQEcUTMnFuqK+M/0xnUdMx/RLacZJ4kgH5MR4WaDtIsQ05757ek6L6iCSQi9GEYsabgl01oP8
                fA4wXSSBDovrd/GxGoyHVRQI6Pp9hDylPX+dxVCOkGqcTp9C/rpBiETr0EMihEzqVIPvOUl8Xf4KIRsr
                VZ1JURvUTyEffH9qQDgFKMnixoMhf1q7ZVml4mMPwJv6uSWxUB/lQ4yq2SKukmZNfHM6vV2FYK4rBU8x
                QUp3+QMGiccIHDKAUSwPw18OJJTNpDSnQplUPy2hlRolVmkFSrV+0CjgID6ziay7EDYMg5xVlZRBcMAl
                d+v6a9bhVodF4DbvpQAL8jIQueQoJALZTpV6nODjo1GjOcALgD8gLmob2R013dB+iX2voO9dtL8KEJs6
                TFLInINSEgFcN0BHq6kcdwiIWrBD4kBN7qqFeBpLGEAsUB2y4Q2RQbQxtT8lmZUexxh8J62QegmIh4oW
                jZL6BQY5K6SkKcmgHalm1bwZgPCkm0L8sQRtstIJNJv44eld1UIYjxZg2bjtYNFKWvj0yrG7wg1wkJhj
                3Fr48n7zpW4K2rvAAIgDrTj4UkuoEBdFil8lWbegNswSlNCkE0AdIiSGnr02hU51sRepBFET1LjGE99l
                vZ/QEioDMgZw1sOYYiozfbVIJ5ugcqsiaTxjlw4xdm6eLzTL2Vm3uEkdxRrLKj166BIMS6zeztEkPZsl
                XVxUjKgAX9nDGFRU/CvGwaEhSSGYLmsPl0yFfVshZ8johziLjOJ+Aogoqjq2At6+pRAJiR3qIBDXxxE6
                E1QbVSQs9xhFBJMyEK04vC8zsLmnK9CJIfpUnyQh3u4Zo1M1cfA9LryeSas/j35vAPXEDaIgthBjeswj
                nOEejw4vywwsIHQ0q8K4AgLyznSoxAZ7hzcwLCmJzF2DncMbNfYwxR4d3sghw2RlFGZoANn1Y6yhTo9+
                D5gQBzX83rGRWB2WQmylsCJiPzplnH0SC+/YXLwdEhe5gjZ3dkbnTJePDg5vcIoukXVsCY6IIHCT7lA6
                ApJE/F7s1QLZlLJqtQi/l3Q18k0tmHvfLlLXFdrXbpfRDDE2BGOrvISe1a2Hf6rs4f1JIun88AaqD+N0
                LJtqc9+04esEMGu2IkVnvbh1demTshJEWsEIUqg2inMJNEdVoN8D3BY3gPk4LJ+TjIFIiuPwOcN2rHkM
                maNyiNFrcwcfdafHje20Q7gQLK2JPeBdVLmPfq8OL4vi+iRIRf4sHWG/QZKYIFAsYlBzjemniLPxap1Q
                yOtInPLNQy8j4wd9YQb6snGMgc5ImcA/X1EoF/UbiWqKFiLGRnksv7L8mkQg6dEGCkw6pfysgPDhZfF5
                VcifFsxFCp/rtgefi9pO1HuQj247OdpAUOh7qiZOnW6HN2R1h/frBqpXuw+GRvEuWUa1AnKlAs3TWI+0
                yFPVE9TYi6C0vupwDWqK2z52e9gKchxDrLhJChhzEoPsqx/Y6vXAVq/BstEQ5uZoAwuPkSySiWRWu3r0
                KQBy1IWOY+ROmksMV7v4aQSYAmsgHsYs79AsBEAkSmOaVdyzfM/wSeqcsOg3P9N8onl5UB4CoATpIdK0
                tpzKjHbFjBNZHtGLJ2xMRae3U4Lxbe3Mpej29zZfQE8Cmc+IhTZkX9XB4nyWOuka2/nO4dwb2q79IQh1
                3M3NeIhONQZcRwdJDgnRiE5RV0OKermJpN+HR6JJnEKGrEM1zrjDBTGXum4CyFSDZGkcGAPxbnH5V4Yg
                mWKg7iBWK4yt9dl/eC3FLqnNKlCcTsjlqOS2MgnuwvRdHALA6SQIcI4O2glkYBjgpF10W3lpV1vj98zn
                10b9mL2uUmjPYzwnHcPIqHwM+vZyVHDmwsBK/nhhiDDKkF7peNBkGDKRuCWLMaI9/ZDxAEzaW6nZAPhR
                tX1ZeJyCWrJuQV1YCQOrRct47Ggj0Jk5YxrEOkUKEEpoSM08TkB1Gepp6feSO47ATkr9vpMIo0aNImNk
                OiyPfh+BdKw3QWhUDuXXwFIsHqPcrQ/QJup7WKXvgKMD6EggC+TfF2yDlfrMc2Yzy6IFDLimRe9UqKj3
                RCgYJbnslUJ3Hp/N+5+WnJukxQ6DWkTPhRnAOs5DU8kSCAj50vsfMsEETZh6rfyaxyl8HQK4qwUuXDDX
                k+2Ww3MjhI0gshth1Xzt+7osf7xwspafM1rm5/xykjhzhHLHMSBJdQ06SGHySjw1CuBH1YBpUt/r/Oa9
                H6/Dan1QXNYYQbeHxlQcRdUDuVTUFSUNwEj+zcktCyMnfV76vPQT74bIXAWRfOl3k4M9LTTcSbHhOIeG
                6Scaf21HIGvM2KzLYqa8CR6NYasYOnf7h7cJkZEXkxVoLTqjG7KRp69LprTzeF8miRT/9iSXeWdQTUJq
                fnNmyerPnPwpPXrm5U36iRMz004ZN7AqdaY7afJnScejxoM1r5xzF8TQQzfddvuxIZ0dJeESdi5k2EEC
                w48TH+Kc2ARAbMiSJmYURd1mWOO0UURnKYplItLGM52nNl6z9/gKhCqqBy0kpIdfoP6sMwQTI7YFZbqK
                A5OjQIYSlQXQGVoS5wlcg1Gnght2bB/GuMDcQZerw9ugSeoto39YJ8Pb2s6zqwt87Zdox79HFWZ2+DJY
                75CJK63HNofg53XsDXL04fFFGtivjOWJtgQ/lf9c9uG0PLxdZoI+/ELYbUjdMJ+AxAGn6smklYqaWllW
                Lz64IQZADlvw6eqB7W2OP+l7KShihiO4e2o04FFrVBoIqylvRSd78B5OVIGBc3v0TBbN0xqPZfhFfbhR
                9uRZ2sDtkKddUbTEx07UuoPboezJo5IhD7XTcWcd7bFRLJvN4WZpR6DSCR61qRMweVXTP4B+ZBmICJp4
                Gcb3Z1F2mB5uyF/yUX8ZyqYTSQ6SXLqwBR7UI1RdLCVDaRBbCG8o3emhOn/FZzWFUpnB/m+k90idhatx
                +V1malEvqsMNu7qdyUcVi9xkJ/q/7F8HUmeS0RWy4BgLfuojGr53u0KFkW9Xr6vbMRimLIlQ9Qv6Uj7c
                gJ6QsYc3zJVkuQSKrCb13NE/A54ly4cb4ns8mcjsQ3QXS0eJfDunLcCpbzmuoCMdYMEwHUfATjCn6ZQY
                c16Q+pdpfLgB3YySforsRktmYW1k9IAfQ79SCAOAurAhXWDeCBost0e+1xoBwtM8ozbLoGL0rSsbSaoT
                fwTzwhVb/FNkiH8m5NjMNjgUGWSRUAK9VbVAN8RqajeQAzTkfGhUcM4FmRVyWWv1fV7fRrkJI0xKaxbG
                BtWsaGRA0yeNJhsnDdKh6StdZh16FISFSX2OWeOMM+k3O1uF0mnOkKcgSyuGPD3qjrMXSjogK4qJVYxw
                YkJADhoOR47Wzgf2rSX3/B2bw/BzOWQszoqytkXzawpkwXQpHda18WIK/lttiiKe5aTyDvPG+Jwsgrl8
                UXdk/on099Ire9KH3gyscRx/Jl9Y7/2F1tTlUuAOn1QjoFPhXW8GL6y19QFZSiucMhABxLH82ktWwZVs
                KUXPMop45a5HgEPDEb7vApbATTqUJEvB+Md17DObl0qG4mNEa8X91M7AfCYIY5T1ZC/iCqUDiX8GUEf0
                kr+rV+kVJS6Zwe/9XosSjBG4hUUJjD14NKeHrl5FjPAsTUaCXA78whJ4do9dwR0eg9dtehWKBkhczqYv
                gWEMeas5Q+cNcLPqJAMJUBtwI8sCdy4c0QB1OT3x23/OPZPlrJQAcvHDaQ7bDBIgQRvCjm4MmxosW3Vl
                1KDAs38pDk2FJSwcjmVAgcCUJyoT+I1MJv7uB3WFfv9ovJMkIGHAMAB5HIRqCqICBCNA2NsAI8sCn79J
                IIRAItzxpYsD5+4BqOonADfGwYNtJRiiZPhuHL9B5KAKwA1+bGqBRwi9zPGJm0x0wwWh8VhNNpNjeKVB
                goZB9AwXM6MC8MWmcM7wFayeu2gnNMn8aM0DPjU0i8/s1DmEU2gwbsMYwhhwqC0eUAxfixSiCwRRnaIC
                XuyJi8DcGs24+2CfFPUBPEYJPzpkEc84Qy3ZuKKIGC7SX98KjJwCxgrWZ/3Pb3O0z9Lvk1EztdEjOSE6
                umkNQR3O6dofDs4vnt5Uc2ZFK0TzdVnFq5KOCgQuy/TD2wGb0j7iykBmTC9EOiui1dT7ttv6YkmzAphl
                Oz2yg+atNZo+XHYJpvxKDhnSxP5Qclq3egiW1ONy1SMnLQy9SS8hcUKlWohkidUiBjotllGcNMJH9BSL
                ge6tCsOnOfP7A7LXq945bsGIGw6vQELDY+aE6cMKinQOv0ctGPsNMc2vPXlDomWQaA3J7dF9X7brymnJ
                yqOb1CnWq60ELndFiiKv1RUswE87MUwbxiP5mePKMexaXg0stFk36ZzucpCArZLEjCWKJZDL9aokWdTM
                htUd96Tygn4S8fB7yIwOavzAnEN8Xfkec7SK4ZNWEwm9pFrH7BVwQWMzT1bF1Efrko6O1ts+0xrnIm6r
                h6BPGnsfNGdyTBWNS1HxvA9iw4hpBRQRgXdUloCgrQD0Ea2MwA+9A/gpSrpgz5LHjSC7SeakUIt2SaNO
                I4bASi15hJ/j6UdpWL3q3k/WI46PPsEh5pC7iw7/hfe11wfEs5VlyL2Sg6l3CZ75ajND5tVkFVqy68Gb
                S69Ar5sWxuQnQv9+lcC1CKx/lmEeVnHOFDMiMaDo8HJX5u6XIGccV6UU4q3moDtpVqRIaxCDsU/OW4cN
                TAL1UNJdFSS78HFmuikYnqUo/+ObkMbQHdqbkJU2q4nsSNmfPoSWs0Ii3QxtsVd7AF5ZycdGNDfxvedx
                iX5vaXy0Lj6PqhX4PKTDm3vSXZU9WY0RPazU0sF8eRW/xuisVRTyWeDVOHgV2cOUBW3GT5ve5QYEtJwi
                pIuHEF+poEGwfK0usS4Me7UMX+WjkxrywBDKEqVHyzQigkcNPmkSTW9Pf7FHJumRrKpygTGslTeymia2
                WgB2NyMH2Dk+T6fbCXwnr1Bnol1TW7NUu41HxpEUW3umzlJ0p9hm5duQTOhmP3QMvA0hgRJWObEBWTy9
                axP8bwjbug+PGNcBbYUyDCMaYf4zL3zoCdh1Q+toMhh0xs4tRoGO6hi+j2P0uQ9tgdj1g+mC++99/NGw
                p7IvwjrIUlZQXx1ERexWCbbXEz77894j/X+5kIHXvT4iquntSCZEKffYOZ0FkqL2jGQIlHZxY/1OXYVx
                Nd3ALjRgEVKDvQi7CDrZUSvJwB2fHdKdO76HOxrQBAEDPGkiXUl757QAFR2rQtkgZnobq+p6Uu8+WVMP
                TXE4oonzJgCn+LINBEmQOZpg+KK+3djs9p54ZzCjgp/ekZ9DkEURENhHLQFPsqjK5dcGHeOWEnSFSuHS
                ZW98tslAn6toAtYf7M7kMmNAosJ7vuuaCzQVaWhlOiAFLmTYTgFeY0UJN10e+lihgCTwBicnS3dzxWgI
                ga7PKZTN0OeK/S9dQ31JdlswSy07Qyg7gYYCX/Au4X4nLuqBSIscjZxpBxqRTECLmKPaMB7Dtd8VWkS9
                xdBrLIWa4gby5qn82MM5UDMUcFlOSRsK4ZIcuoj4qoPzR6aTrl1YCj1U80ZNKttlZ03+uooLpQEr7WK6
                m8vxTv8phX89WDZ14pVrPjAMS4MwkA8HpXB4p4fu9F1Jsh2X04aBXi6IatmJQWCn7popaNqqUgqe4n4Y
                Xwj6geFEGTX4zCClQjczdRbXMEIWeNEzxCUqxKb3rNSYOHv0s9U2O5BL72x7sbmNb80H/6F5SnU+8Oud
                59MttkKFORJTkPnzHNn7dM1ZP8e++DY2LcuhOKborDsa7WQ4qVPWeqmTBnZoeV21PhrRnHsv3PnqH/1i
                f+s5mU8laBfGhuU3iklBoGnSwng77AQKRHFjOeOhVAv2QbwOnfwlXqUCTkFWBBmyQ/92rpnYFOx/Oq3w
                Tzf4sHvo37mdM8aCjhQHl8uvWIIGRONKWs3mh5fABYpD6zpE0IIXeopkNwUTnyAidNxIskdiHXKlOfrl
                8BJc9AzbGAYIz20ah9T0sGMKiS268hsDQlcxaJDY5IlK+pA7BsX8q+hJuqAJ52u+BN1aEJkleMti3KKk
                odfKmztKwNJGep/r8cC1DfY6GBg6ShMMFQgO2+r4lWWTJbANzUrvkr2oQ6n9q7CjdUIHLyswsLcLn7sJ
                jq+BKDxR1kDhWE2gGFMhjWO6CThL5+gYVKii8orpt+gf3Ye3InoleCzFNQglYoylR4FpHem9zvVvyEKv
                UfF9nNSyX2pJJil0GxyW1OkCPu1FinY6MR3K/+NWQu/lsgPKHQbf/JP98BLGXsarhC0YKHcvpnnk3/9B
                EOhARZU2kFttctntuAM3XqN6Jsgc9SNNCXdrVCRoi4McaCuQLKjjwjVs3DEu0YryMg/w9w6C+SV1F7hf
                +VGZCFJSrdNFMUUnLYEjNfIvSwvoaEy7aJ4rs+ceO+e0GPq5HcNXilbAz0NUOWYu4KUJ+i3AJP2Oostq
                3NK24boKe7NygGq6xNnyNYZ+sEyAVSFVWoiq9WEsn8uI8hY6Qj/K+PUM8WjHgt1WsiOTFPjDAWCYIUUf
                +FNAWGpG0+6lsBGGGbLvxCzy21zTXZOZNX0kzTZPgekbaaOZWtOxOVZNqJ/+5of6LIuHKxK+sOjn8BqF
                feDo0HS7OiVZU5qA3KwUm6Rx19Hmwtm3WjSnFRvZAjN8A5X10a+QMIkpgftUHl6LXIJZFlSFk/Hm+D0R
                7vlmZ11LIKp0zHGgWUba4Rb41xFJsh3MSgagiqstoz1sGlICO5y1MsGiHgj7WMTCzlqbzhKTWLk5KVFC
                lVr5JtNlR3hdGS5vUAUuvtRkxdH6hh1C9u6I5DMsUUoDiRKrIvl7ItyszCSSUsz5VV1rWkw4oIfdfA4M
                BZkHOE4tTnTFArWW2ZMbwrlrwfxcnJpH/Ah/DUoP9IzycsObfuubcSSlRp3Wv33mNAoX+OS9Vlj+UNHD
                a7LoagztRNOHY6lTUnPRrDBtcEIstVMFPp22Ot2YSHpytAEosSNA5by3hWX7nxIcmiqMIT1ZBVOowJMq
                50XL/eyV8pWQbgJ1sfSEBxkXNmgdd0S4s3s1ondKkubsUiE377WITiVzzWu39GQMjzZsU05Z1oKUp7eK
                qjSvWdPsjWlnO1GU1zppbieHB/C5KKW+S00OGJonFVhQ9UpwTCp7HTkQtThBZJanIxjndD/Hjlv2c9Oq
                OPSshHEVvGzcETiA3BW+xBLngLALZMJ038toTAyAg9SewCyFwqiC0fRuVzr+6H14R2RXv6alSCQvjw5k
                8dXpXUSZSeHjH+qfsFJSE+ijot5oRZI1kJMVFIB1UzBQT0sgSejuBrXRyM9SPLiobdgeHR1A8cYHLTK3
                2AQiLap1dnRV0vUIeCQtyhbMaWT3oh+Y5MIwgWCUbOjhZHeg0WOzFXeVrTpsZzD/dNdOLl3w010hD6LZ
                HQg6LWghuu8PxysBLdHh4bUUAit10Ho9alLfvOl8ORTda6b7tFq4v+nhNbs82PQJxVdR124brr6b+txz
                5iK3FMGnwMgomdCLYiQ+sTp7jlykrnWryuKzuOz5qIy6NtCJddCYiNYH003bg+8Z/bZkI+tV8vHxz2oM
                S6BpRTCMGuUYkqiukG5H4p1VU98IX84YpQP8nnnjI6eb5Hc1CsIPnHlIhfkHVxXEA4ga++IU0xkdT+77
                YAQaPfeuqgP+aDfk8BD583WCCJBFAK76iJ6aCXj/K97Hkla3wDVHG4ibSjLGgJJ8fXmf+JKKs7qPrkpn
                tB1GkMwKkICNA3Uf/87zZtlDUMaxWaR7xCPwkJtkGOJbLSX0mGP47OyFMEJkTYbcHngdxFCgqcDxnD1O
                GA+pCxukZAT4ieyXII7joPPsph5766RZ3TVNOOwDyKlVJw1AEgUcwPeZ8KMje5Jsa9C69TCqYqg4I7mf
                q2riqtJbBTsJsGlVaNHsrylw5cvD+Nig3St7PjaPw6jOa6J0WbX8eyMRzRwdBKq9FCMElbV8NndcAitG
                BJRJAj0qpWnSpAS9H9sDsn3QRgMmP+UoePsW7MDTVYAw0cQeKg6zTP6Xu7kFbgGui6Fw0pPYaiBw25wx
                rJR5C9+uucnVupL3J70R5GRiB6DuYhtKp84by8KrgUpOPLyr2dMBcKOOJbjlR6DgCqIxStXZgrYJEiDL
                LENQQ9XojE6PfzpQBRqtYYzSR/qBv8iThgEYG1T+9VOJPeIJ2JVPAsv4egQeg8UQH/PIQGUXmBrxrwQn
                QqX14BiPkYMyUGCGrw0LDaa268IQ4xWMP0oAJvKPO/c1b2Uv1U9WyY8ixcXSTWTVeQKKxk4QnDuB+Wfc
                5OEoxq3CaZkwaWHQoEUpkrVrnuEA0Ry93P9cMCDKwaKOFezza2X8viVDm0HZX3H0gn6H5fGqxzbWnGij
                7xLMUo8byJBOH0LAog4xzj0jtloCHdXkxeWsFTKPhiy670MEo3qVJGEMadhYkek+XUTOGJsiNDDkSbKj
                b3yCXRiNpHkbOUhD/n8sMyqmj0UGy6bMgxw9iLIP9DAR383vtg1M6Nb0kDeXGLArHYuNKmGopCd6d+hI
                M9ExPM/and4JkMtKApbKUdIB97Q4D8VIaFgVDUq70nieG36bxUGVAjzWlpKhgwP6lJs+tg060ctG3Ui6
                nt6BwNAUVYIFREkWQek7tQQq8WoZYWUbTdBYCp3ZgNACIWYYQ1SbeAQP2eu2wUh5NQ4/yyu6wpcyBgJk
                6J9wjzGeNFucOPGLnTXa3QJj2FHB2xgj4WFl1tgdDBigONyXNAu/mM6nGyNBqxUELlp5Am/A9uBJ2DgD
                NJFUtMWc/g0sg6IM6sqEV5kdVyHtntXugoHpRzJk5QO6x11b4Do3EiFoTGXP4GmwqCee3NCj3sAnaRnx
                u7ME4wJW04dgMlNBtOTgffuokxSycNxMIepKPxnJz0OaD/48SFbF56Q/vSMrb9BGqsEIherAgQs8j4fy
                8stp87qelLLlJAcDHRmYwHIMoxWquZc8kfZJ9uB46CrX1q5yUGrzgKNcYMQ8mt4BT/TgSdxA/TcWCj67
                bMAjfoQObPwUMdfVC19ulSa+RQz2P0kfxSp3UDuoTh8wtWlKqHrVt2S3ZVwIuuHVz99X6QZmm6AVFKr0
                V0DxOELH/qSRXzvotBd1OlC28R0zMZMkB/hpLqfRxBALjddZLhg3B7vSeQzqbMJ30+Dx4lxw1z1A5mYN
                PfVX8UXdskNPuVm0tJQGr7mS17AzNqqFFs6eU/b5Mv9oqBX9uB8nwhvfnKnN9JZLmPcuebMskJ3emt4C
                B36LlH3AbDJBYnkGhXV3OroUIxTxe5E+3HXtadOh2naIMzQlmf/SJbEyaDw40strhYGHOynoOcAZN6TU
                04tmA9+iNjgHvwzLXB2THz8Psk4AX01K4aum+5gEIqamBNOmXPURgkKOprdWAC2NOBYgR+mOEY+5x1Op
                AgX4aAhsHDXoAdyK6jQI2IhwKqZHIV3wgOB5eg5t5uhYBEs0AUpQrpMTxxhdqVnwaMquown12BXBcIr8
                OqoTJaUYn7Dxj4wZmE4MIDrjWXfwK5OynXIwhK9JIuFVzmvIxS9ssS584yMF+KR+7kvEEwdBiifo5j8C
                MnjBrG7hqY5hOZLwqJzEEpep0w3AUwtilyBsc51iTLwWXK34ad/3q5qa0psiLRjOWs4zellBUUqKQY4G
                qSQHwaunRS37MRIuUOYsSKGlvAWXuGGJJlIjaClKkwLGhAzI4f2M4LnV4fQWdESBWOhIFoQBbEvkQCHr
                jobw9NoweMqVw9cSWWH8QcUCqGsCT261GcTIG5ZgpVyOvQBoUdsNqFMPcE5OJztjvxReO82DF8ySQiLD
                URCkeQgdawLZU4OmuqMR2OYmEDUoyuBJ0kEAkcYpQpGRJIsEvM8DeMWPRHssAS9p1GipVUEwnjgDU6w0
                C13CZNAfdT8w5Z4qXuDry57TdFOs5GAZxHCR75HWHpFMjspSkOyr6+IWp5LMaSEFUY7JbYpgg5MPlGBx
                VKEBUgFfB7Ta2WgoByuqpIcjKFpnVLWgzwo6aLnPdHFy7nEsu9UIay4NPGjwhIyqABmRDfscwZSeNZRm
                oxkrdp3jh65KS8+SkQU9GMfKtnFm+c6oqUdPCLKlkr587sgMUVthhukdqtE95k60g0VkykTSkB59JpLe
                J/De/lbgyFGOPm59iGzzSiHEAohyW9kMWLg6+yN6Ksi5vFX0M4wzjGMbadOBobj9sywwvUPGw85HrqFf
                oA4upY2ntRKLKghsD2doXjM0C6e3UTmkErvEBxZ+DISmdxoMp63O/UrS/KqWf24EwyIlFKTMPxaS+6hK
                8leXV7EJ5CR/zXsXuwKBDcn8GRupCoWSTP8+aCRVgsl5XaJUKor7YIgUTX+rMOw0O+y5qNYFNNYF/Vjl
                H0kzPSvAyT4pfMRorWpVUy8/s9D4PeEu54wRj2wpaWWESn70hSRBNdf8flNwftrXIR98vZ74S3XFOS1o
                ZwPlH411jwo7lzZQrknX/3kfDcxpspZcRXNmZuTHMgVb90KdAPJz0cPAygU43Q8AcCX0vBXt34/ByjxH
                u58OPNeeYWDkwIRePtY67z2RHBZrpBxJTTZ87lYAcuICYiXFk6TL5JyJ0yMbwgiaFcZUVnAa3OARfAbe
                fQmafVeotqhRfZTHINeZ/qeBF1ujEmRGPfQvj1J4vSwquq6stpmE6EjjKv2zIAu0kudgfO7HZYO9SGVV
                PXTlV4fLEtRVNdBw2cJX8dYZ3UIwBvEGqblkpACkWwUm3bF0ga9XUDwygddM1ZkNEY5KqGpQeqxkhwR2
                68MSMNtSHLhmARmElIwKiKfYCd5VE2GM7FVhb20GiyUIj6oEuhkN6Fbz3e5iP2GQ8VIKAqEKoiJFBfJL
                uBDYMxxz9z07jacwlE0acE7LR4iDjreOD+x9RxfeyZpNMN75+uGm453jgxMTK8G46JukByfrxw90klG0
                qaTjZ8cPjnfNCI2uTec6uaQqt7noqjt+aOs2uMfWrSraxqQHunaTpEudV6UeHW8f79qaDKShkt/ZJJb8
                HB8cPzL0eW5/nTs+Q5Wruh8dbwF8OfnRtm9o29gDNRibtuC6vSNp1zKzSPFsVyTpanY1P4i1XM1+WOyi
                6yCz8UClPfSIRSdtH2+dXPRJi6byg5Pv7KxRpp2TtZN1D1woE3XbTdu+a88l7Z5NenDyA43fMftAzeQO
                Jm1r9sqk40014Q89eNB92FSLZ8+laB48sTwg82Vdh08w9W6pAe+LlOPniiVbEeR5cLxne3jBcvvkik7A
                Yg9VsQdY7IC7zHn2VaGHkEezETp0sq7q2ZZ51GSrPRNhPWphubvfrYgDkaJY8YCmkfOoPhPvOc+OYs+W
                zHP8XK8ImaI6yP3hPGrdH++5lPPMdsuh825Xb0MtqtQm9mZHM0zWoied1pnL9Cjosl5TO8e7QXeoy7zr
                tlRnHshrWS9EkUEvuYt2fZ3z68K2fM4za/fkkqxVdeV4H9rRjIAUfQRs2wV4zk9M2Pg2r3efoiragVLf
                qXNCpugjgKbqnJ8qYinnMasSxv3Icvicm4VtOjTP0UJe8yeiz7R7fAAtqT10/Nxd5DrPJ7rLMiXyO2vR
                ndAHx48xz6dq7nZkHjVzmEcv7ZM1SNFc3oTW1473Ti6FpbCH9sqQbellTBx0/VnjDcr1qE0sal40W+QZ
                Dkuz/Rk0vnO8j11W08fLlhu/pI8V7OBWkEfviE+hrciMaw+7fKaUPj6hh0/UQLGH6n45+Sv2UHHj5CKn
                nOcz3ld0nmfQLg43YXp9Y01XVNEdbG2HtoVsHzh0ckkV20Z+7OitjdN8hmcHvG/d4nD30IKbjWe0+Rd8
                nuN9mRKZe3lH5tlyp9yCm2c+Pzll/0yKXgu72JY6C7dcit1h+n4/kJkeuvNpQSyh9bCLW9BFw7KdsPmT
                K1jqEYMZl7LN6MKlbPGJ5Ft/QjvMIYVLjEE4jz7iH0XYn4Bl5vSD1s1qXQvzPLALj2t+orsM49KLbD8c
                V9hnvTzCPHQl+4lfx8kwd/u2hW3O7j0Q1KlNYK96gRkpaUGsVToV5ubENWl5yEnmqjoxwenmZnzBfYE/
                zUVpb5lZsZ+/oySJo47t62MekvCu90m6nxY0CWxL/Zw5B9vcZ9NduCngp0PJOzJJ9fshg8IFMbxjH0TT
                4bYHmPSAr3kGmBarbEOS7vvJJZkkkIjP5c50n7TN+ElU75AiT8537roSkPYBZtJzw93iXxQG08Lbdnof
                YBZ3aDts/MhdzIxxBaibmYGJ2JbZdHu0YlhYF4JetTNdqQ/Pntwz7/m5wnDZ4ySfS4/vWVjw0xCNb58F
                6Gr52fm64Fj8IETN7kB3yNqde5yypZkOKbqXa5DirxNO2XWnimhrD2C0QBYeRsseCnTEGS466CGA/z7g
                WPHL67zPswcp6kDbp2G6PA7geXgMRcwUhkCcLnHx83ILS+ljAeG7vw59Hh6Uh+JbCOjF6epSDvjHo6iH
                zltGhf53ojtudvlEAKz7AFL2A6QtfoS8H7+7486h1ACbnBMDA+CqjwaEyGZRAqw3oNk35n/NSMwuIJXr
                tbt/RArxntE3CQjOian4FIpoXPSXsH97CPz9NStLrUOKPszwp8lWWMpcUNDhk6sh0Le4CH4xrLtftbKe
                beDXvr8hxTB2w59cvHH8vD8LZ9khE4H0j73xvmXHGgLQ90HJA75zGMU/YK4C9A9Q8wP8eWA2JeBPAx4e
                Qh6PCX3jDNxY4rMGENpeWZBiTp4rslr/y5Kx+Ka/lF1F7g7z2CEYp4CI8tfK+tmfSttBKTqWZalPglIa
                cMEw4JeA/dm9I8eg+KIW3TZO8BYtAvGbh37gw0/LZ2d4vB325uQSzsx2MAZ9ZMDsmcv0xIXzcEtwB1Ie
                8R3gofD2sXfEFEImRpk7Vhgky+hDDiDt+36VaFGgBLBmvT2hPBatNhXG+qIHe+a8wNn6H7C2MrOa2jmn
                +1tJgKaQQe41HAqe5hTFxeGmtZ8jWXAnPvo5kgnN9Bm9GEiRNCJV4nDTpZhGyMiPPRRbRxkr3Yqyk8pw
                ZfqYwoSRSnDilcu6NnqlzFnkxbUtz3LZPvsC8JgqMt2nY3klFkFVTXDqwpHz2k4BvUNjjutO4m6Kh+a0
                xV2q3cnOqTo2EiBHeKY73DsyOeglhz97ZGqU94c2NpBz8GQNqWMY8dc/4bxJpmlUomQvR6I7FHPTW/Oh
                kjqd3qZOu/D+GXiBRlkKLwHFpARzKuURv8FES/lw8/Bn6iSpoWteJ96VcwXiaEQNmuj12S7XPZAci0Ab
                xg6a1iZnGAXOnp00iMyxkoEqmhWMTK8kEJq0E48kyU/nedW0jIbWroDfZ6fFKKe5tTwnKXQ8vX246b4b
                m7XDzRHkj51bgmFVDYZ2GcSuKIPXdoT5n96w7DrDn1fKSpB1C16WKzGQTQK+FVEdZ6Lq3vQZBOBYCWKC
                rYClXZkFj+KmztNQK7+eiYjoi9pv4/BnsEPjg4jyr/A5xNVR3GGu7nEB3padFt0rMzCQ4/eKzvGGg5bZ
                gJJbqsD9oRcPoOHbMZjq6eNRZu+2oHtle0OnxQX3hkGMb8zG6BRIL9OzNrU53ATnvAxCo0UpRBQfYTDV
                Hj5+WMd0KDnNbC0Vt+rARS+NhI6sRX+eyOJp1pfZO9NnPvi5PlFhWHXsP+qFoLYKcKEcgU45cJwcBaFr
                E2QZ2WC6YbWrQMIzOSvo7TC9DV4FpbCwWzR7gRYkK1wLcHcYYVSxQewjTxhTx649LRd4OlDX2x7+3Eq6
                R1O9wNsUH+YrYqnr7QsrQ3MQP16ByuMetB24MKzEoFlvEgg+d/Rz0HTj1cym7hVft515CDcfZ4eb2PMW
                tNTjeCJZ2gse61mJgGc9cK7ImAkk5vs4eDV5+ru9tulaLyx9jsy6evYJrAU2ZJj+bhjOwTCsPnyOEcfQ
                9mSO7lOd2yZwGIKhrZ1hFlunzrLfmXX0dyisJfs6+vwxlo7ohcNZt8gJIXHILTU2b65nbAVotA5D5WRk
                xrfA4GN7E/IZl9T+eR59qv1eD3wGE+TLco929IgHzCApp+/EzaL17wrO+8cPPOTh+ghfcFRChz/4tT4X
                tSL1eMNUQNPnwrzT/Lmrf5iAzRs/supfRg4irU5/n+AjfAPpPFkP0Ih+kEMI0oG/kI2fGXWOL9UhRLHq
                tfiK3QCMx7op3N79IT6IV6XSkKkSL9FZE5HfIdRRAbkVTxqZu6RFSp+DgKb8lCp/bcG+ahJc/bm3dTdn
                uFpA8gYoetPf4fppwbSbjz++EQbopBYTP+nzan8oP+vV7w55vRQ7qaBqG62FijYrYHFVYESlDr71FqU5
                GEbRM9VMNqVstUllO1Gdwl3cS51TlTaAhXNrmEqHq7z1r6bZjeaPY22tz7wIfv+5QxAqi0fojtak8PJu
                00KE6gG4Ak4CO/cWrH56gXta5buxaHrdQDflo8Y0ZbIncc9ff9rAUBofsZEgX25DuBPyDlYcU7t8Mo7o
                ZGTHJ7VUIqYFihQHaSXppPLV6VGXYMPeSyC6p4ITMmBnlXhTelNXDXVFg7YS5ETk1r6BwxLyggF8lvag
                5loM0qyJnguq6aIXsYE7hdNlMq1lqzHkVYspl820OQxgertXAjPAc4DfXGTr/wRIdo73LRWiG2QIyO+s
                deJIDMdGOXMhQScyrukkhQkZD7GVgQ31xmQ9kMb9nQQcEuj044pLqJhfi2DOqFUCoVrbGPwV6hIW1Qou
                KnVWQO4SQrVG9PK2e7HXBvJhUcHAjtB5V1I0Co5tEag6BxXZo9KxzU9JMa7hJTvnflraFyXmKIC5C9nD
                yIQjBzFdx9ZgeZZgQ2rDRTngUvbRWbMobewLZjNXT4IjG+XHBbYoSCjhbLaH1jQ9COXjQ6Pa9cugRgff
                w0cGKawO070hxr0oexnQNfncuPJl5V8hNKfcEALSc3BM74dQwJuDJV0/ztWgU+LbPWkXBEMKudsBc/St
                Fn0s+2Xgc5kUEOurGAYhKUYlPJnM0Sd9SIsCJTaTMsfyqQ+0quUDLYc+46eW0kAERNGxGIX1g+hc/IAU
                0xxgx0fjwvydtAGvzxEHKmGBj/Q1MBM28e37MKj8eRijfKit8BXnwg6fSYorwxBx4iNm6DCpVwBOJuD9
                mebwpk9e9qWkqYhBllRn3vnTRTl2G9RjyTlTr/xYYKQr8glzX23QHP6FUvrMLoCf82+YuGBiC3pZH20A
                KB1ipK4OhPVSFUtw7MLjOQ/CAiJ/ZvCcHwcgcJgUXsvhKEm0vyfJ4ZWgalkXy7acmX8BcUDx5Z3A6l/G
                xTIbty+/Fgk6ECQYgzKFQFdq0zmkqF8PA4FEWUuI2U7gxRtVUFZUIVrlp7/5LIvBPVIdVTJUUxGD20LU
                DuXXegKRmoYxoPOyFwTsx2hTariyrqMNDORV4kM8zQgDiGFQLIrBQ8dnYE2vDhPpEVBOMNxE6qUaRuBC
                kcwY3Q7BuZIF5ly4hhf1iiGY1pd1QPpuzZkgxbLdw2sjh2bDZ+7aBgJmcJjM98tlONAeA/ASXz1OCskM
                tcjkz4IiHSbw1f8OMHEowPuVIpHRAPLk8BtoqCxiGDz+wijA/ZVD4rk5yeRXjnnnSBBbHR2k8DnHR6wj
                HFId5/gGIq8NC4RWBwIIzXz40Qevd+6eXvvq9S7ZoujDSSe//e9/X+9cfPs9afsuUPLp5d9Ob+5z7oV5
                Tv7xwZu/Pz392upJjYpNJ0ent789/ea7189+0MkG+1z46IN3V/7zZmvv9Euj6TRyGZv34It3tz85vWfy
                znCDb368+Mfv33GDBsCY3KrONz/+/MeNR5B8+tXzt7u3X+/9GuQ+vbb2evfbNz9tnEk+vfz7+5LV2N+b
                vPvl+yu5+97kmzffX/el9yd/FiYrVp/ee/z6+U8ebJncb3/45PTJr6/3f8RkXcnTn7iSRT+TapDqm0dY
                Nreax5290527mKzqfnPj8enF7yHZLIdLZyrRybtfnq37zbfP3/y4e3r5ISSr9WEXxHsq+fZ9dSsGnkmm
                1fPVc0x+8+CbNzeuq4/R2eH8dOn06p0zdV++e6Zuze/PN/74/l9BT24+enfw99Od9bP93vnsPYz9y5eq
                vbc3Pz+T++lPZ3Lr7Xfr3umnNz1e9TN/ev2bIPnH3+wOxGQ9ytdPP1N9N8m8/dSu0Wx5+LcIkiO1cd7e
                3Dn9+kvMffDT28u3z+R+e+tA7TW1UoJKTr/5XKzBGdjaT/6Bed998evp0y3VHXPEcMXfboqxuAPms/t2
                VbrURZX19jend/8tsxkmf7P/eu8WpOrhfvv8dPdHmapZ7M+sC7andr28frqB5X+5eHr/5z/+9hxS9Rq6
                /vXp1yae73k3qOdXXu/cOt38XKa+vf3d66f/VhU4pHdBrB/Iafh6evNfEdSqt/anD+kUlKmq/39c/AxS
                7by83tuTqX/869PT+88Ue2QPTu9tvLu3QePitM/uv7m49/brT13pWbNF/7j0m/63eUkV9ebvV06//Dsn
                2Maf33tz95c3G1chVXVUrQE1tTJVHaanV6/wTLu8f1z8QfFQlQhrOP3q59Or30OqPazUGg/zvr35zduf
                fsG8f/nSbwaZ983fvn+3vsWpF8RsQc539w5e737xeucHlzrLR4M9vrC1q4/f7l1/s/2VTHVXHI743aOf
                3z36RZWAVJXTH5a+3us3Xh9sv/38Pua9+rVaCGEf7Jy/3vlCpqo5f/Pzr7wQJHfUHleXbcid13u/vNv4
                7QzPPv9V7fQzM3T9m9PNv2HP7Nb5/l9ha+/Lqzbfj9feM0N//Hjx3b/XGL641u79483f9yFVtaP/7cyR
                rNXeoTLb6ef/Of385usd76jgqny69eYx5P3jb1fsLsO8eo89++HdvXuQ+u6Xy6riP65cl6lvvn16ev27
                N1f/DjXQHX7w03tS1Q5wqRfE1RYMavdLaPz06o13awqHXJKpb/+99ubuv9RZcKb7dtFhlepiDdv4Fsvt
                /f1sG7TOaMe61Dd/e2C3Mqaq09XcPzKVbqRfLr6HGeqIPTNNfqX60X/z3du9T9Q+EIxzl4ZMM1tVrBCd
                trOuD2KLPN20/fPpm2v3XtON49v5+svTe/9RHcCJN1MU8kWfehtfqnUG9f70jRqr6saZUSlA9+bzW8it
                i7++/fzxm4uw9k/vP33z438VoI3EZPkz23dqQ6PS18goxdQ3P372+tlaFMz0zsUz8+ROVl/l+pY/Azn1
                3fN/vN27G64xdWXYyxRSVa7X+39TM4V5Ny//L57s7Z1evoYz8LO6Bv5yehUGe/rsojpN3h7cgho0BxTk
                JOzCv3W0kAB+60Sv1j97tfafV2v2ymEM9mrtr6/Wv3y1bu2RF2dd8vev1p6+Wvu3/VHjsPqrtd9frV99
                tf45JOtK1u69+XQLc9sVrL7Y5AVfyW+v1q69WrsfJKt+uA7Ozfvct3Xu9S9cslrNir9qP71aN7Bibtax
                4tN3dx8whp370Nf8hemiOeFnz/uaP1EjpH7MLkL3vqcGZ3n7fHr5j7//wnnJneT1/qev1m64FHvfb4gu
                cEb1S+706k8mZcY3s/5q7ZbqACYr+PNqfY1a9z8FiZX0Kjn+Qnz3yxoma8ig1hr1doYHrIHEsx94PmYE
                H655xvvcZsRvn1z3ufUuuqqGbC4ED/rV6lGL8NXaNiSrX6Kvdz53rfmfCFdvnd77jvvmkhXXTv+6cbbu
                tW/MungQJt+ys2eSZwmoOOZz7bOyi1v/5C66ZHVK+Kn2yae3br+78ousRK+AW3fe/nAlrPn0+rqYb5f8
                5sav6vg6+2Pl9OuregtRJTPUb5VbcUsxXH3zH3Ttl/97+sX3ii/wOyF689sd9SuLarnwJ7lR1mkwLvWP
                H370Q3S/NL7+5NVF1dpP+Pvjyq66R9RwMJXW0j3sg/oFcrZePblvPrsY9oznNugvLRuDSt0JYyE05RXn
                zrpfBueIba/Wr5uz67MzufV+/z1MVYf6H/+8fLbmz/1KOkdTTRsFcp5+ds0UvxfJVMVDlcQ8Ez34i1lb
                N2SqAqlvH/3Au9XXoFpX/f0PpPJ5g62RrOnyf7G10593cQw2dfMffobEnferbm4d7zxV/I/vv6ZzXSBN
                scsWHG/80SLK/81MBbRleaP6HAV5/2qO2KAGda244xCvihtBqvpN+WpNLaffsV51/6hj8my9irc/qyMV
                7sxf1uwxhDe02m2XN5nnAg+q3yg/h/W+29p4++0vZ/nw++n9A5r3gA+fPw1Q1vUb5nT7Iqjhhj+MRaoe
                Ah0PEjuq0+HW7ZC/999bw286dT0ESgbkfxOdTeU17TDd3V9O9//K61TUe0/9qKMf7e7s37369t4NniFx
                av/+eu874o6/EP75yB6LQermH5f/S/wlJ4O3nz19t/1vkfBm5+bp1W9kOQUGdY/++Qhapg1NKIcK61Pe
                12aWxd0HbzbVVrqB3TbXFW+keZjUP9Z2sKGr+qQ8/flJMPD1v5ze2qTrR0Iv3dXNkEmfWIAU1KuOr3f/
                QobS4UMHCtyZbgHM02Gp7q7Te7dOL++8vf8J1Pz0ofqhKllja/7CnBJfYM1vvr2vLolgAvWJ8Nsd3jai
                Fzc8uvK8/OZzDdwwla/WoIa3j++++XItaM0d+lHIS30/vI8/qnNhz+75IwFx5jodgb6/ZoMpNID1btmD
                KWhNTdyvqhtykflfcUKIqzh2drDf/nZ2kUX29g/Y9fb6gRaGXv88XON6MWxtyOYVr9/+dk8mPN8gIbmr
                zQjj1M+QoD9Pvz3bn7e3vzu9/6nKHiyLr66+3vlK9FLfE08fWR5JZly8fXr1V7kFnRhTcufJ5pmTQ/94
                /u2Oa8H+xunW4jeOMa6idxPZIte93OccnypraTxLWvURvc40S3rp8dH3ZxKsmo+Nat3TkCbBhJDeoFZn
                SOnfHlnfbaqiOLw58hDa6ty9xYp/oNx97mXePmXRdIFeIGaLk4LeAHF2uKVqQ2bgp1zdc78T7iJpxpZS
                YXMypx+V9NEuzSDvdiEh5xcqOcG9Iso1NOQPxUYtHeF7ZMxMJ9MNl8Avyt6VWVanGyMsM7078K/amVfo
                IHh4TXZezq6koieKiR5IdyUb/fAuWq7E3VRmyA9v1lgiocegnYFwO6ROsy1JY/2BXEDxw5sF5hjWCTz7
                WzdH30cyQyH9kYyZv6zB8GGj682atVnGiiy/2qDDlBoVGkHH4iFi014O+fN4CPQgA7MUNgdis5RyIsle
                5TNTVwcyN/lmkS4jOtpo7Eql7/yYKVm1jEtZFz8gyoXVioHCZWdFtp2lPZ9/wbwbHI9kfYl4wtjss1La
                2/TwFb6ReHZPN57oXShLJ7kz7Zkz78uJplblQzjaXjel3eIcv2gkLlaof9ZuTrNcxvDsxWALPq7g2WG1
                7+yozrumIWZpFUPM0h4+mzNZqWRler1viM/RqFfC5wI/1w18jof+BWQ96iSwEKL3y4hkswASLEQZv2rs
                DPlaCCyfj1oYyFjW3U3gVcGan3XnrvGb684cCZ6+GWeUneiCLxJn2tORL+/xU+Jk3khPOzurIG8GdOax
                ZmMmWtg3urn00YGsmowj2G8M3wdkcw+qqi7oYjjnp29f0L2j+xhLtRnJnkalt30yzhsboiy81azNQ3qJ
                bKmhveQ4kpWSn+oalVFb1ZFm1/u5PwmLzv9hN9UppUXWJAHHuvAR6LF4cFojAtkL/SL3Hcg7kjNzeG26
                AbZTo+AxvsK/G6QtelbguZ0uvl7MNquLnj93Ba0mzjLAmUd1pYlXge/xqFM0A/uoxL9fbK5a+QrisAb/
                hEZEeV2wL1FvyKroRYzFP/3f//3/MCC8xA==')));

            foreach (array('bg', 'ru', 'mk', 'sr') as $lg) {
                foreach (self::$lgAnalyzer[$lg] as $word => $rate) {
                    $word = str::utf2ascii($word);
                    if (strlen($word) > 4) {
                        if ($word{0} == '*') {
                            $word = '*' . substr($word, strlen($word) - 3, 3);
                        } else {
                            $word = substr($word, 0, 3) . '*';
                        }
                    }
                    self::$lgAnalyzer[$lg][$word] = $rate / 2;
                }
            }
        }
    }
}
