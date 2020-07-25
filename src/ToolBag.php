<?php
/**
 * Created by PhpStorm.
 * User: bzg
 * Date: 2019/10/25
 * Time: 11:02
 */

namespace Cs\RBAC;

/**
 * 工具类
 * Class ToolBag
 * @package mytools\lib
 */
class ToolBag
{
    /**
     * 将字符串哈希成整型
     * @param string $string
     * @return int
     */
    public static function hash2int($string)
    {
        $hash = 0;
        $len = strlen($string);
        for($i = 0; $i < $len; $i++) {
            $hash += ($hash << 5 ) + ord($string[$i]);
        }
        return $hash % 701819;
    }

    /**
     * 驼峰转下划线
     * @param $str
     * @return string
     */
    public static function tfToBot($str)
    {
        $dstr = preg_replace_callback('/([A-Z]+)/',function($matchs)
        {
            return '_'.strtolower($matchs[0]);
        },$str);
        return trim(preg_replace('/_{2,}/','_',$dstr),'_');
    }

    /**
     * 转大驼峰
     * @param $str
     * @return string
     */
    public static function bigTF($str)
    {
        $str2arr = explode(' ',$str);
        $s = '';
        foreach ($str2arr as $v) {
            $s .= ucfirst($v);
        }
        return $s;
    }

    /**
     * 转小驼峰
     * @param $str
     * @return string
     */
    public static function minTF($str)
    {
        $str2arr = explode(' ',$str);
        $s = '';
        foreach ($str2arr as $k => $v) {
            if($k == 0) {
                $s .= strtolower($v);
            }else{
                $s .= ucfirst($v);
            }
        }
        return $s;
    }

    /**
     * 转下划线
     * @param $str
     * @return string
     */
    public static function botLine($str)
    {
        $str2arr = explode(' ',$str);
        $s = '';
        foreach ($str2arr as $k => $v) {
            if($k == 0) {
                $s .= strtolower($v);
            }else{
                $s .= '_'.strtolower($v);
            }
        }
        return $s;
    }
}