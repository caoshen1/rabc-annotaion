<?php


namespace Cs\RBAC;

/**
 * RBAC缓存
 * Class Cache
 * @package Cs\RBAC
 */
class Cache
{
    /**
     * 获取缓存数据
     * @param string $key
     * @return mixed|null
     */
    public static function get(string $key)
    {
        $cache_file = self::getCachePath() . self::getCacheFile($key);
        if(!file_exists($cache_file)) {
            return null;
        }
        return json_decode(file_get_contents($cache_file), true);
    }

    /**
     * 设置缓存
     * @param string $key
     * @param $value
     * @return false|int
     */
    public static function set(string $key, $value)
    {
        $cache_file = self::getCachePath() . self::getCacheFile($key);
        return file_put_contents($cache_file, json_encode($value));
    }

    public static function clear()
    {

    }

    /**
     * 获取缓存路径
     * @return string
     */
    public static function getCachePath() : string
    {
        return __DIR__ . '/Cache/';
    }

    /**
     * 获取缓存文件名
     * @param string $key
     * @return string
     */
    public static function getCacheFile(string $key) : string
    {
        return md5($key) . '.rbacc';
    }
}