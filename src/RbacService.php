<?php

namespace Cs\RBAC;


use Cs\RBAC\Exception\RbacException;

/**
 * 权限管理层
 * Class AuthorityService
 * @package app\common\service
 */
class RbacService
{
    /**
     * 校验是否有访问权限
     * @param string $auth_str 原始的权限字符串
     * @param string $controller 控制器全类名
     * @param string $action 方法名
     * @throws RbacException
     * @throws \ReflectionException
     */
    public static function checkAuth(string $auth_str, string $controller, string $action)
    {
        // 获取类和方法的备注
        $auth_arr = (new RbacBuilder())->setDir($controller . '@' . $action)->run()['auth_info'][0]['item'][$action] ?? [];

        if(!empty($auth_arr) && $auth_arr['isCheck']) {
            // 未通过
            if(!(new HexBinStr())->decodeHex(HexBinStr::deduceStr($auth_str))->isTrue($auth_arr['authIndex'])){
                throw new RbacException('权限非法');
            }
        }
    }

    /**
     * 刷新权限
     * @param string $path
     * @return string
     * @throws RbacException
     */
    public static function refresh(string $path = '')
    {
        return self::build();
    }


    /**
     * 设置扫描目录
     * @param string $path
     * @param string $namespace
     * @return false|int
     */
    public static function setPath(string $path, string $namespace)
    {
        return Cache::set('scan_path',compact('path','namespace'));
    }


    /**
     * 扫描文件夹构建权限信息
     * @return string
     * @throws RbacException
     * @throws \ReflectionException
     */
    public static function build()
    {
        $path = Cache::get('scan_path');
        if(empty($path)) {
            throw new RbacException('请定义扫描目录');
        }
        $re = RbacBuilder::getInstance()
            ->setDir($path['path'])
            ->setNamespacePrefix($path['namespace'])
            ->run();
        Cache::set(RbacBuilder::INDEX_CACHE, $re['auth_index']);
        Cache::set(RbacBuilder::INFO_CACHE, $re['auth_info']);
        return '';
    }

    /**
     * 获取权限列表
     * @return mixed
     * @throws RbacException
     * @throws \ReflectionException
     */
    public static function getAuthList()
    {
        $list = Cache::get(RbacBuilder::INFO_CACHE);
        if(empty($list)) {
            self::refresh();
            $list = Cache::get(RbacBuilder::INFO_CACHE);
        }
        return $list;
    }

    /**
     * 获取权限索引数据
     * @return mixed
     */
    public static function getAuthIndex()
    {
        $list = Cache::get(RbacBuilder::INDEX_CACHE);
        if(empty($list)) {
            self::refresh();
            $list = Cache::get(RbacBuilder::INDEX_CACHE);
        }
        return $list;
    }


    /**
     * 根据原始权限字符串获取权限菜单ID
     * @param string $auth_str 原始权限字符串
     * @return array
     * @throws RbacException
     * @throws \ReflectionException
     */
    public static function getMenuList(string $auth_str) : array
    {
        $index = ((new HexBinStr())->decodeHex(HexBinStr::deduceStr($auth_str)))->getIndex();
        $auth_info = self::getAuthList();
        $auth_index = self::getAuthIndex();
        // 从权限index获取菜单信息
        $menu = [];
        foreach ($index as $i) {
            if(isset($auth_arr['auth_index'][$i])) {
                $menu[] = $auth_info[$auth_index[$i][0]]['menuId'];
                $menu[] = $auth_info[$auth_index[$i][0]]['item'][$auth_index[$i][1]]['menuID'];
            }
        }
        return array_unique($menu);
    }

    /**
     * 根据权限ID生成权限字符串
     * @param array $index 权限index数组
     * @return string
     * @throws \Exception
     */
    public static function createStr(array $index)
    {
        return HexBinStr::reduceStr((new HexBinStr())->createBinStr(100)->setMBit($index)->getStr());
    }
}
