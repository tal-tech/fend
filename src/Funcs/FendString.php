<?php
namespace Fend\Funcs;

/**
 * 字符串处理
 *
 **/
class FendString
{
    /**
     * 过滤掉标签,by duchaoqun
     * @param       string $text    源串
     * @param       string $encode  编码
     *
     * @return string
     */
    public static function filterContent($text, $encode = 'UTF-8')
    {
        return htmlentities($text, ENT_NOQUOTES, $encode);
    }


    /**
     * 裁剪替换
     *
     * @param string $str 源串
     * @param int $len
     * @param string $ext 需要替换的
     * @return string
     */
    public static function getShort($str, $len = 40, $ext = '...')
    {
        $str = preg_replace("/(\s+)/", ' ', $str);
        $str = preg_replace("/\[i\](.+?)\[\/i\]/is", "\\1", $str);
        $str = preg_replace("/\[u\](.+?)\[\/u\]/is", "\\1", $str);
        $str = preg_replace("/\[b\](.+?)\[\/b\]/is", "\\1", $str);
        $str = preg_replace('/(\[br\]\s*){1,}/is', '', $str);
        $str = preg_replace("/\[img\](.+?)\[\/img\]/is", "", $str);

        $source_str = html_entity_decode($str, ENT_COMPAT, 'UTF-8');
        if (strlen($source_str) <= $len) {
            return $str;
        }
        preg_match_all('/((?:https?|ftp):\/\/(?:www\.)?(?:[a-zA-Z0-9][a-zA-Z0-9\-]*\.)?[a-zA-Z0-9][a-zA-Z0-9\-]*(?:\.[-_A-Z0-9a-z\$\.\+\!\*\/,:;@&=\?\~\#\%]+)+(?:\:[-_A-Z0-9a-z\$\.\+\!\*\/,:;@&=\?\~\#\%]*)?(?:\/[^\x{4e00}-\x{9fa5}\s<\'\"“”‘’]*)?)/u', $str, $match_url);
        preg_match_all(';(@[\x{4e00}-\x{9fa5}0-9A-Za-z_\-]+);sium', $str, $match_uname);

        if (!empty($match_url)) {
            foreach ($match_url[0] as $key => $v) {
                $pos  = strpos($str, $v);
                $slen = strlen($v);
                if ($pos < $len && ($pos + $slen) > $len) {
                    return substr($str, 0, $pos + $slen) . $ext;
                    break;
                }
            }
        }
        if (!empty($match_uname)) {
            foreach ($match_uname[0] as $key => $v) {
                $pos  = strpos($str, $v);
                $slen = strlen($v);
                if ($pos < $len && ($pos + $slen) > $len) {
                    return substr($str, 0, $pos + $slen) . $ext;
                    break;
                }
            }
        }
        // 截取
        $i    = 0;
        $tlen = 0;
        $tstr = '';
        while ($tlen < $len) {
            $chr    = mb_substr($source_str, $i, 1, 'utf8');
            $chrLen = ord($chr) > 127 ? 3 : 1;

            if ($tlen + $chrLen > $len) {
                break;
            }

            $tstr .= $chr;
            $tlen += $chrLen;
            $i++;
        }

        if ($tstr != $source_str) {
            //表情补全处理
            $rs  = mb_substr($tstr, -6, 8, 'utf8');
            $pos = strpos($rs, "[em");
            if (!empty($pos)) {
                $cut_str = substr($source_str, $len);
                !empty($cut_str) && $end_pos = strpos($cut_str, ']');
                !empty($end_pos) && $end_pos <= 5 && $pos_str = substr($cut_str, 0, $end_pos);
                !empty($pos_str) && $tstr    .= $pos_str . ']';
            }
            $tstr .= $ext;
        }
        $tstr = FendString::filterContent($tstr);
        return $tstr;
    }

    /**
     * 追加URL参数
     * 检测是?&追加
     *
     * @param  string $str  url地址
     * @param  string $pars 需要追加的参数
     * @return string
     * */
    public static function subUrl($str, $pars)
    {
        if (false === strpos($str, '?')) {
            $str .= '?';
        } else {
            $str .= '&';
        }
        $str .= $pars;

        return $str;
    }

    /**
     * @param      $idStr id串  1,2,3,4
     * @param      $id        要加入的id
     * @param bool $reverse  加载的位置
     *
     * @return string
     */
    public static function addIdToIdStr($idStr, $id, $reverse = false)
    {
        if ($reverse) {
            return $idStr ? $idStr . ',' . $id : $id;
        }
        return $idStr ? $id . ',' . $idStr : $id;
    }

    /**
     * 移除串中ID
     * @param $idStr
     * @param $id
     *
     * @return string
     */
    public static function removeIdFromIdStr($idStr, $id)
    {
        $idStr = ',' . $idStr . ',';
        return trim(str_replace(",{$id},", ',', $idStr), ',');
    }

    /**
     * 判断ID是否在串中
     * @param $idStr
     * @param $id
     *
     * @return bool
     */
    public static function isExistInIdStr($idStr, $id)
    {
        $idStr = ',' . $idStr . ',';
        return (strpos($idStr, ",{$id},") !== false);
    }

    /**
     * 处理ID串为半角","分隔
     * @param $id_str
     *
     * @return string
     */
    public static function getIdStr($id_str)
    {
        if (empty($id_str)) {
            return '';
        }
        $id_str = trim(str_replace("，", ',', $id_str), ',');
        $id_ary = @explode(',', $id_str);
        if (!empty($id_ary)) {
            foreach ($id_ary as &$id) {
                $id = (int) $id;
            }
        }

        return !empty($id_ary) ? implode(',', $id_ary) : '';
    }

    /**
     * 文本入库前的过滤工作
     * @param      $textString
     * @param bool $htmlspecialchars
     *
     * @return string
     */
    public static function getSafeText($textString, $htmlspecialchars = true)
    {
        return $htmlspecialchars ? htmlspecialchars(trim(strip_tags(self::qj2bj($textString)))) : trim(strip_tags(self::qj2bj($textString)));
    }


    /**
     * XML转换
     * @param $string
     *
     * @return mixed|string
     */
    public static function getSafeXml($string)
    {
        return self::getSafeUtf8(self::getSafeText($string), true);
    }

    /**
     * UTF8转换
     * @param $content
     *
     * @return mixed|string
     */
    public static function getSafeUtf8($content)
    {
        $content = mb_convert_encoding($content, 'gbk', 'utf-8');
        $content = mb_convert_encoding($content, 'utf-8', 'gbk');
        $content = preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', '', $content);
        return $content;
    }

    /**
     * 国标转换
     * @param $content
     *
     * @return mixed|string
     */
    public static function getSafeGbk($content)
    {
        $content = mb_convert_encoding($content, 'utf-8', 'gbk');
        $content = preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', '', $content);
        return $content;
    }

    /**
     * 全角转半角
     * @param $string
     *
     * @return string
     */
    public static function qj2bj($string)
    {
        $convert_table = array(
            '０' => '0',
            '１' => '1',
            '２' => '2',
            '３' => '3',
            '４' => '4',
            '５' => '5',
            '６' => '6',
            '７' => '7',
            '８' => '8',
            '９' => '9',
            'Ａ' => 'A',
            'Ｂ' => 'B',
            'Ｃ' => 'C',
            'Ｄ' => 'D',
            'Ｅ' => 'E',
            'Ｆ' => 'F',
            'Ｇ' => 'G',
            'Ｈ' => 'H',
            'Ｉ' => 'I',
            'Ｊ' => 'J',
            'Ｋ' => 'K',
            'Ｌ' => 'L',
            'Ｍ' => 'M',
            'Ｎ' => 'N',
            'Ｏ' => 'O',
            'Ｐ' => 'P',
            'Ｑ' => 'Q',
            'Ｒ' => 'R',
            'Ｓ' => 'S',
            'Ｔ' => 'T',
            'Ｕ' => 'U',
            'Ｖ' => 'V',
            'Ｗ' => 'W',
            'Ｘ' => 'X',
            'Ｙ' => 'Y',
            'Ｚ' => 'Z',
            'ａ' => 'a',
            'ｂ' => 'b',
            'ｃ' => 'c',
            'ｄ' => 'd',
            'ｅ' => 'e',
            'ｆ' => 'f',
            'ｇ' => 'g',
            'ｈ' => 'h',
            'ｉ' => 'i',
            'ｊ' => 'j',
            'ｋ' => 'k',
            'ｌ' => 'l',
            'ｍ' => 'm',
            'ｎ' => 'n',
            'ｏ' => 'o',
            'ｐ' => 'p',
            'ｑ' => 'q',
            'ｒ' => 'r',
            'ｓ' => 's',
            'ｔ' => 't',
            'ｕ' => 'u',
            'ｖ' => 'v',
            'ｗ' => 'w',
            'ｘ' => 'x',
            'ｙ' => 'y',
            'ｚ' => 'z',
            '　' => ' ',
            '：' => ':',
            '。' => '.',
            '？' => '?',
            '，' => ',',
            '／' => '/',
            '；' => ';',
            '［' => '[',
            '］' => ']',
            '｜' => '|',
            '＃' => '#',
            '＋' => '+',
            '－' => '-',
            '＝' => '=',
            '＜' => '<',
            '＞' => '>',
            '！' => '!',
            '（' => '(',
            '）' => ')',
            '＇' => "'",
            '‘'  => "'",
            '’'  => "'",
            '．' => '.',
        );
        return strtr($string, $convert_table);
    }

    /**
     * 字符串截取
     * @param string $str
     * @param int $strlen
     * @param int $other
     * @return string
     */
    public static function doStrOut($str, $strlen = 10, $other = 0)
    {
        if (empty($str)) {
            return $str;
        }
        $str = @iconv('UTF-8', 'GBK', $str);
        $j   = 0;
        for ($i = 0; $i < $strlen; $i++) {
            if (ord(substr($str, $i, 1)) > 0xa0) {
                $j++;
            }
        }
        if ($j % 2 != 0) {
            $strlen++;
        }
        $rstr = @substr($str, 0, $strlen);
        $rstr = @iconv('GBK', 'UTF-8', $rstr);
        if (strlen($str) > $strlen && $other) {
            $rstr .= '...';
        }
        return $rstr;
    }

    /**
     * 字符串截取
     * Enter description here ...
     * @param string $Str  为截取字符串
     * @param int $Length  需要截取的长度
     * @param string $dot  后缀
     * @return  string
     */
    public static function doSubstr($str, $len, $dot = '...')
    {
        // 检查长度
        if (mb_strwidth($str, 'UTF-8') <= $len) {
            return $str;
        }
        // 截取
        $i    = 0;
        $tlen = 0;
        $tstr = '';
        while ($tlen < $len) {
            $chr    = mb_substr($str, $i, 1, 'utf8');
            $chrLen = ord($chr) > 127 ? 2 : 1;

            if ($tlen + $chrLen > $len) {
                break;
            }

            $tstr .= $chr;
            $tlen += $chrLen;
            $i ++;
        }
        if ($tstr != $str) {
            $tstr .= $dot;
        }

        return $tstr;
    }


    /**
     * 过滤特殊字符
     * @return mixed|string
     */
    public static function replaceHtmlAndJs($string)
    {
        if (get_magic_quotes_gpc()) {
            $string = stripslashes($string);
        }
        $string = mb_ereg_replace('^(　| )+', '', $string);
        $string = mb_ereg_replace('(　| )+$', '', $string);
        $string = mb_ereg_replace('　　', "\n　　", $string);
        //       $string    =   preg_replace('/select|insert|and|or|update|delete|\'|\/\*|\*|\.\.\/|\.\/|union|into|load_file/i','',$string);
        $string = htmlspecialchars($string, ENT_QUOTES);
        return $string;
    }

    /**
     * 检测是否在设定的两个数之间
     * 结果总是出现在边界
     * 例如:
     * domid(985,0,100)=100 无边界设置
     * domid(985,0,100,20,96)=96 大边界
     * domid(0,0,100,20,96)=20 小边界
     *
     * @param int $it     一个整数
     * @param int $min    边界,较小的数
     * @param int $max    边界,较大的数
     * @param int $min_de 小边界的默认数值
     * @param int $max_de 大边界的默认数值
     * @return int
     */
    public static function doMid($it, $min, $max, $min_de = null)
    {
        $it = (int) $it;
        if (null !== $min_de && $it === 0) {
            $it = $min_de;
        } else {
            $it = max($it, $min);
            $it = min($it, $max);
        }
        return $it;
    }

    /**
     * Db 设为 utf8 字符集情况下不支持 emoji，评论入库后为空。
     */
    public static function cleanEmoji($text)
    {
        // 删掉表情
        $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
        $clean_text     = preg_replace($regexEmoticons, '', $text);
        // 删掉其他符号类
        $regexSymbols   = '/[\x{1F300}-\x{1F5FF}]/u';
        $clean_text     = preg_replace($regexSymbols, '', $clean_text);
        // 删掉地图类符号
        $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
        $clean_text     = preg_replace($regexTransport, '', $clean_text);
        $clean_text     = str_replace('سمَـَّوُوُحخ ̷̴̐خ ̷̴̐خ ̷̴̐خ امارتيخ ̷̴̐خ', '', $clean_text); //处理阿拉伯文会让iOS/MacOS崩溃
        return $clean_text;
    }

    /**
     * isjson检测
     * @param string $json_str
     * @return bool
     */
    public static function isJson($json_str)
    {
        $json_str   = str_replace('＼＼', '', $json_str);
        json_decode($json_str, true);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * 验证数字是否合法
     * @param int $num 需要验证的数字
     * @param array $options => array( 数字验证区间
     *      'min' =>1,
     *      'max' => 20,
     *  )
     * @return boolean
     */
    public static function isInt($num, $options = array())
    {
        if (!preg_match('/^-?\d+$/', $num)) {
            return false;
        }
        if (empty($options)) {
            return true;
        }
        if (isset($options['min']) && is_int($options['min']) && $options['min'] > $num) {
            return false;
        }
        if (isset($options['max']) && is_int($options['max']) && $options['max'] < $num) {
            return false;
        }
        return true;
    }

    /**
     * 全角转半角
     * @param string $str
     * @return mixed
     */
    public static function SBC_DBC($str = '')
    {
        if (!$str) {
            return $str;
        }
        //全角字符
        $DBC = array(
            '０', '１', '２', '３', '４',
            '５', '６', '７', '８', '９',
            'Ａ', 'Ｂ', 'Ｃ', 'Ｄ', 'Ｅ',
            'Ｆ', 'Ｇ', 'Ｈ', 'Ｉ', 'Ｊ',
            'Ｋ', 'Ｌ', 'Ｍ', 'Ｎ', 'Ｏ',
            'Ｐ', 'Ｑ', 'Ｒ', 'Ｓ', 'Ｔ',
            'Ｕ', 'Ｖ', 'Ｗ', 'Ｘ', 'Ｙ',
            'Ｚ', 'ａ', 'ｂ', 'ｃ', 'ｄ',
            'ｅ', 'ｆ', 'ｇ', 'ｈ', 'ｉ',
            'ｊ', 'ｋ', 'ｌ', 'ｍ', 'ｎ',
            'ｏ', 'ｐ', 'ｑ', 'ｒ', 'ｓ',
            'ｔ', 'ｕ', 'ｖ', 'ｗ', 'ｘ',
            'ｙ', 'ｚ', '～', '！', '＠',
            '＃', '＄', '％', '＾', '＆',
            '＊', '（', '）', '－', '＿',
            '＋', '＝', '［', '｛', '｝',
            '］', '：', '；', '＇', '＂',
            '，', '＜', '＞', '．', '？',
            '／', '｜', '＼', '　'
        );
        //半角字符
        $SBC = array(
            '0', '1', '2', '3', '4',
            '5', '6', '7', '8', '9',
            'A', 'B', 'C', 'D', 'E',
            'F', 'G', 'H', 'I', 'J',
            'K', 'L', 'M', 'N', 'O',
            'P', 'Q', 'R', 'S', 'T',
            'U', 'V', 'W', 'X', 'Y',
            'Z', 'a', 'b', 'c', 'd',
            'e', 'f', 'g', 'h', 'i',
            'j', 'k', 'l', 'm', 'n',
            'o', 'p', 'q', 'r', 's',
            't', 'u', 'v', 'w', 'x',
            'y', 'z', '~', '!', '@',
            '#', '$', '%', '^', '&',
            '*', '(', ')', '-', '_',
            '+', '=', '[', '{', '}',
            ']', ':', ';', '\'', '"',
            ',', '<', '>', '.', '?',
            '/', '|', '\\', ' '
        );
        return str_replace($DBC, $SBC, $str);
    }

    /**
     * 返回正确的json中
     *
     * @param array $array
     * @return string
     */
    public static function getJson($array)
    {
        if (empty($array)) {
            return "";
        }
        if (is_array($array)) {
            self::arrayRecursive($array, 'urlencode', true);
        } else {
            $array = urlencode($array);
        }

        $json = json_encode($array, JSON_UNESCAPED_UNICODE);
        return urldecode($json);
    }
    /**
     * json串处理
     *
     * @param array $array
     * @param string $function
     * @param boolean $apply_to_keys_also
     */
    private static function arrayRecursive(&$array, $function, $apply_to_keys_also = false, $recursive_counter = 0)
    {
        if (++$recursive_counter > 1000) {
            echo('possible deep recursion attack');
            return;
        }
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                self::arrayRecursive($array[$key], $function, $apply_to_keys_also, $recursive_counter);
            } else {
                $array[$key] = $function($value);
            }
            if ($apply_to_keys_also && is_string($key)) {
                $new_key = $function($key);
                if ($new_key != $key) {
                    $array[$new_key] = $array[$key];
                    unset($array[$key]);
                }
            }
        }
    }

    /**
     * session_encode 实现
     * @param array $sessionData
     * @return string
     */
    public static function serialize_php(array $sessionData)
    {
        if (empty($sessionData)) {
            return '';
        }
        $encodedData = '';
        foreach ($sessionData as $key => $value) {
            $encodedData .= $key . '|' . serialize($value);
        }
        return $encodedData;
    }

    /**
     * 解析由session_encode序列化的字符串
     */
    public static function unserialize_php($session_data)
    {
        $return_data = array();
        $offset = 0;
        while ($offset < strlen($session_data)) {
            if (!strstr(substr($session_data, $offset), "|")) {
                return false;
            }
            $pos = strpos($session_data, "|", $offset);
            $num = $pos - $offset;
            $varname = substr($session_data, $offset, $num);
            $offset += $num + 1;
            $data = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }
        return $return_data;
    }
}
