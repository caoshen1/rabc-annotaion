<?php


namespace Cs\RBAC;

/**
 * 伪二进制十六进制操作
 * Class HexBinStr
 * @package mytools\lib
 */
class HexBinStr
{
    const HEX_ARR = [
        0 => '0000',
        1 => '0001',
        2 => '0010',
        3 => '0011',
        4 => '0100',
        5 => '0101',
        6 => '0110',
        7 => '0111',
        8 => '1000',
        9 => '1001',
        'a' => '1010',
        'b' => '1011',
        'c' => '1100',
        'd' => '1101',
        'e' => '1110',
        'f' => '1111',
    ];

    const BIN_ARR = [
        '0000' => '0',
        '0001' => '1',
        '0010' => '2',
        '0011' => '3',
        '0100' => '4',
        '0101' => '5',
        '0110' => '6',
        '0111' => '7',
        '1000' => '8',
        '1001' => '9',
        '1010' => 'a',
        '1011' => 'b',
        '1100' => 'c',
        '1101' => 'd',
        '1110' => 'e',
        '1111' => 'f',
    ];


    // 操作的二进制字符串
    private $bin_str = '';

    private $hex_str = '';

    private $cur_mode = null;


    /**
     * 16->2
     * @param string $hex
     * @return string
     */
    private function hex2bin(string $hex)
    {
        $len = mb_strlen($hex);
        $bin_str = '';
        for ($i = 0; $i < $len; $i++) {
            $bin_str .= self::HEX_ARR[strtolower($hex[$i])];
        }
        return $bin_str;
    }

    /**
     * 2->16
     * @param string $bin
     * @return string
     */
    private function bin2hex(string $bin)
    {
        $bins = str_split($bin,4);
        $hex_str = '';
        foreach ($bins as $v) {
            $hex_str .= self::BIN_ARR[$v];
        }
        return $hex_str;
    }


    /**
     * 生成一个二进制字符串
     * @param int $len
     * @return $this
     */
    public function createBinStr(int $len)
    {
        $this->bin_str = str_repeat('0',$len * 4);
        return $this;
    }


    /**
     * 将二进制字符串的某位设置为0或1
     * @param int $index
     * @param bool $value
     * @return $this
     * @throws
     */
    public function setBit(int $index, bool $value = true)
    {
        if(!$this->bin_str) {
            throw new \Exception('没有二进制字符串');
        }
        $this->bin_str[$index] = (string)$value;
        return $this;
    }

    /**
     * 将二进制字符串的某位设置为1
     * @param array $index_arr
     * @return $this
     * @throws \Exception
     */
    public function setMBit(array $index_arr)
    {
        foreach ($index_arr as $index) {
            $this->setBit($index, true);
        }
        return $this;
    }

    /**
     * 将二进制字符串的某位设置为0
     * @param array $index_arr
     * @return $this
     * @throws \Exception
     */
    public function delMBit(array $index_arr)
    {
        foreach ($index_arr as $index) {
            $this->setBit($index, false);
        }
        return $this;
    }

    /**
     * 获取得到的二进制或者16进制字符串
     * @param string $type
     * @return string
     */
    public function getStr($type = 'bin')
    {
        return $type == 'bin'
            ? $this->bin2hex($this->bin_str)
            : $this->hex2bin($this->hex_str);
    }

    /**
     * 解析16进制字符串
     * @param string $hex
     * @return $this
     */
    public function decodeHex(string $hex)
    {
        $this->hex_str = $hex;
        $this->bin_str = $this->hex2bin($hex);
        return $this;
    }

    /**
     * 判断二进制字符串中的某位是否是1
     * @param int $index
     * @return bool
     */
    public function isTrue(int $index)
    {
        return $this->bin_str[$index] == 1 ? true : false;
    }

    /**
     * 获取当前二进制字符串中所有为1的下标
     * @return array
     */
    public function getIndex()
    {
        $index = [];
        $len = mb_strlen($this->bin_str);
        for ($i = 0; $i < $len; $i ++) {
            if($this->bin_str[$i] == '1') {
                $index[] = $i;
            }
        }
        return $index;
    }
    
    /**
     * 将相邻的一样的字符串处理为 .值,数量. 形式的值
     * @param string $str
     * @return string
     */
    public static function reduceStr(string $str)
    {
        $cur_value = ''; // 当前值
        $cur_len = 1;// 当前值的连续数量
        $zip = ''; // 优化后的字符串
        $len = mb_strlen($str);
        for ($i = 0;$i< $len;$i++) {
            if($cur_value == $str[$i]) {
                $cur_len++;
            }else{
                // 如果之前的长度大于5，就改成.n,n.的格式
                if($cur_len >= 5) {
                    $zip .= ".{$cur_value},{$cur_len}.";
                }else{
                    $zip .= str_repeat($cur_value, $cur_len);
                }
                $cur_value = $str[$i];
                $cur_len = 1;
            }
        }
        if($cur_len >= 5) {
            $zip .= ".{$cur_value},{$cur_len}.";
        }else{
            $zip .= str_repeat($cur_value, $cur_len);
        }
        return $zip;
    }
    
    // 解压缩字符串
    public static function deduceStr(string $str)
    {
        $old = '';
        $str_arr = explode('.',$str);
        foreach ($str_arr as $v) {
            if(!empty($v)) {
                $sub_arr = explode(',',$v);
                if(count($sub_arr) == 1) {
                    $old .= $sub_arr[0];
                }else{
                    $old .= str_repeat($sub_arr[0], (int)$sub_arr[1]);
                }
            }
        }
        return $old;
    }
}