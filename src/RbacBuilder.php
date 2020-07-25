<?php


namespace Cs\RBAC;


use Cs\RBAC\Exception\RbacException;

class RbacBuilder extends DocumentAnnotation
{
    // 缓存名称
    const INFO_CACHE = 'admin_auth_info_cache';
    const INDEX_CACHE = 'admin_auth_index_cache';

    protected $annotation_tags = [
        'authName' => 'authName',
        'isCheck' => 'isCheck',
        'menuID' => 'menuID',
        'authIndex' => 'authIndex'
    ];

    /**
     * 最终权限信息
     * [
     *      [
     *          'name' => 权限分组
     *          'menuID' => 一级菜单编号
     *          'item' => [
     *              [
     *                  'authName' => 子权限
     *                  'isCheck'
     *                  'menuID',
     *                  'authIndex'
     *              ]
     *          ]
     *      ]
     * ]
     * @var array
     */
    private $auth_info = [];

    /**
     * 权限索引，通过Index快速定位到权限信息
     * [
     *      'index' => [0,item(忽略),方法名]
     * ]
     * @var array
     */
    private $auth_index = [];

    /**
     * 当前最大的权限下标
     * @var int
     */
    private $cur_max_index = 0;

    /**
     * 子类重写的方法，执行子类的主逻辑
     */
    protected function doJob()
    {
        // 扫描指定的控制器，获取 权限名，是否校验，权限菜单ID，权限index信息
        foreach ($this->annotation_info as $ci => $fileClass) {
            // 获取一级权限名
            $class_title = $fileClass->getClassTitle();
            if(empty($class_title)) {
                throw new \Exception($fileClass->getClassName() . '没有类标题');
            }
            // 获取一级菜单编号
            $top_menu_id = $fileClass->getClassDoc()['menuId'] ?? 0;

            $action_docs = $fileClass->getActionDoc();
            // 处理方法备注，提取信息
            $action_doc_handle = [];
            foreach ($action_docs as $k => $ad) {
                // 去除空项
                if(empty($ad['authName'])) {
                    continue;
                }
                $action_doc_handle[$k]['authName'] = trim($ad['authName'],'()');
                $action_doc_handle[$k]['isCheck'] = trim($ad['isCheck'],'()') == 'true' ? true : false;
                $action_doc_handle[$k]['menuID'] = trim($ad['menuID'],'()');
                $action_doc_handle[$k]['authIndex'] = (int)trim($ad['authIndex'],'()');
                // 维护索引
                if(isset($this->auth_index[$action_doc_handle[$k]['authIndex']])) {
                    $index = $action_doc_handle[$k]['authIndex'];

                    if(isset($this->auth_info[$this->auth_index[$index][0]])) {
                        $pre_class = $this->auth_info[$this->auth_index[$index][0]]['name'];
                    }else{
                        $pre_class = $class_title;
                    }
                    $pre_action = $this->auth_index[$index][1];

                    $str = "权限【{$action_doc_handle[$k]['authName']}】的权限序号【{$action_doc_handle[$k]['authIndex']}】重复,上次出现在【{$pre_class}】【{$pre_action}】";
                    throw new RbacException($str);
                }
                // 维护最大下标
                if($action_doc_handle[$k]['authIndex'] > $this->cur_max_index) {
                    $this->cur_max_index = $action_doc_handle[$k]['authIndex'];
                }
                $this->auth_index[$action_doc_handle[$k]['authIndex']] = [$ci,$k];
            }

            // 去除空类
            if(!empty($action_doc_handle)) {
                $this->auth_info[] = [
                    'authName' => $class_title,
                    'menuID' => $top_menu_id,
                    'item' => $action_doc_handle,
                    'authIndex' => ToolBag::hash2int($class_title),
                    'isCheck' => false,
                    'disabled' => true
                ];
            }
        }

        return ['auth_info' => $this->auth_info, 'auth_index' => $this->auth_index, 'cur_max_index' => $this->cur_max_index];

    }
}