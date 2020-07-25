<?php
/**
 * Created by PhpStorm.
 * User: bzg
 * Date: 2019/10/13
 * Time: 14:40
 */

namespace Cs\RBAC;


/**
 * 解析文档注释
 * Class DocumentAnnotation
 * @package mytools\annotation
 */
abstract class DocumentAnnotation
{
    // 定义方法访问修饰符级别
    const ALL = 0;
    const PUB = 1;
    const PRO = 2;
    const PRI = 3;
    const PUB_PRO = 4;
    const PUB_PRI = 5;
    const PRO_PRI = 6;

    /**
     * 扫描文件夹 子类可重写
     * @var string
     */
    protected $dir;

    /**
     * 获取的文件后缀
     * @var string
     */
    protected $suffix;

    /**
     * 命名空间前缀
     * @var string
     */
    protected $namespace_prefix;

    /**
     * 只获取指定访问修饰符方法的注释
     * @var int
     */
    protected $action_modifier;

    /**
     * 是否获取静态方法的注释
     * @var bool
     */
    protected $static_action;

    /**
     * 获取指定的注释标签
     * @var array
     */
    protected $annotation_tags;

    /**
     * 包含文件结构和注释信息的数据
     * @var array
     */
    protected $annotation_info = [];

    /**
     * 当前指定的类的方法
     * @var string
     */
    protected $cur_action;

    /**
     * 构造方法
     * DocumentAnnotation constructor.
     */
    public function __construct()
    {
        $this->dir              = '';
        $this->suffix           = '.php';
        $this->namespace_prefix = '';
        $this->action_modifier  = self::PUB;
        $this->static_action    = false;
        $this->annotation_tags  = [];
    }

    /**
     * 运行
     * @throws \Exception
     * @throws \ReflectionException
     */
    public function run()
    {
        // 获取文件列表
        $file_list = $this->fileList();

        // 遍历文件列表获取注释
        if(!empty($file_list)) {
            foreach ($file_list as &$v) {
                $this->getClassDoc($v);
            }
        }
        $this->annotation_info = $file_list;
        // 调用一下子类的操作方法
        return $this->doJob();
    }

    /**
     * 子类重写的方法，执行子类的主逻辑
     */
    abstract protected function doJob();

    /**
     * 遍历文件夹获取指定后缀的所有文件
     * @param string $path 当前路径
     * @param array $file_list 当前文件数组
     * @return array 所有带命名空间的类名数组
     * @throws \Exception
     */
    private function fileList(string $path = '', $file_list = [])
    {
        // 尝试以@分割传入的路径，0表示类，1表示方法
        $path_info = explode('@',$this->dir);
        if(count($path_info) > 1) {
            if(class_exists($path_info[0])) {
                $this->cur_action = $path_info[1];
                return [new ClassFile($path_info[0])];
            }else{
                throw new \Exception('没有找到类'.$path_info[0]);
            }
        }

        // 如果指定的路径是个类，则直接将这个文件的类名返回
        if(class_exists($this->dir)) {
            return [new ClassFile($this->dir)];
        }

        $path = $path ? $path : $this->pathSeparator($this->dir);
        $file = [];
        $temp = scandir($path);
        //遍历文件夹
        foreach($temp as $v){
            $a = $path . '/'. $v;
            if(is_dir($a)){//如果是文件夹则执行
                if($v == '..' || $v == '.') continue;
                // 按多维数组的方式表示文件夹
                if(!isset($file_list[$v])) {
                    $file_list[$v] = [];
                }
                $file_list[$v] = $this->fileList($a,$file_list[$v]);
            }else{
                // 只将指定后缀的文件遍历出来
                if(empty($this->suffix)) {
                    $file[] = $v;
                }else{
                    $suffix = $this->pregStrEncode($this->suffix);
                    if(preg_match('/^[\W\w]+' . $suffix . '$/', $v)) {
                        // 加上命名空间
                        $class_name = str_replace(rtrim($this->pathSeparator($this->dir),'/'), $this->namespace_prefix, rtrim($path,'/'));
                        $class_name = str_replace('/','\\',$class_name) . '\\' . $v;
                        $file[] = new ClassFile(rtrim($class_name,'.php'));
                    }
                }

            }
        }
        if(!empty($file)) {
            foreach ($file as $f){
                $file_list[] = $f;
            }
        }
        return $file_list;
    }

    /**
     * 转换文本中正则表达式的字符
     * @param string $str
     * @return mixed|string
     */
    private function pregStrEncode(string $str)
    {
        $search_list = ['.'];
        foreach ($search_list as $v) {
            $str = str_replace($v, '\\'.$v, $str);
        }
        return $str;
    }

    /**
     * 路径分隔符统一
     * @param string $path
     * @return mixed
     */
    private function pathSeparator(string $path)
    {
        return str_replace('\\','/',$path);
    }


    /**
     * 获取类中所有符合条件的方法的指定标签的注释
     * @param $class_info
     * @throws \Exception
     * @throws \ReflectionException
     */
    public function getClassDoc($class_info)
    {

        if(is_array($class_info)) {
            foreach ($class_info as $class_item) {
                $this->getClassDoc($class_item);
            }
        }

        if($class_info instanceof ClassFile) {
            $class = new \ReflectionClass($class_info->getClassName(true));

            // 获取类注释
            $class_doc = $class->getDocComment();
            if(!empty($class_doc)) {
                // 获取类的标题
                $no_sp_str = str_replace(' ', '', $class_doc);
                preg_match('/\\n\*[{4e00}-\x{9fa5}A-Za-z0-9_]+/u',$no_sp_str,$titles);
                if(!empty($titles)) {
                    $class_info->setClassTitle(trim($titles[0],"\n*"));
                }

                // 获取类的其他注释
                preg_match_all('/@\w+\s+.*\\n/',$class_doc,$cd);
                $cdr = [];
                if(!empty($cd[0])) {
                    foreach ($cd[0] as $str) {
                        $temp = explode(' ',trim($str,"@\n"));
                        $cdr[$temp[0]] = trim($temp[1]);
                    }
                    $class_info->setClassDoc($cdr);
                }
            }

            // 如果指定了方法直接获取指定类指定方法的注释
            if(!empty($this->cur_action)) {
                $method = $class->getMethod($this->cur_action);
                $class_info->setActionDoc([$method->getName() => $this->getMethodDoc($method)[$method->getName()]]);
                return;
            }

            $methods = $class->getMethods();
            if(empty($methods)) {
                return;
            }
            // 保存需要获取注释的方法
            $doc_methods = [];
            // 获取指定访问修饰符的方法
            foreach ($methods as $method) {
                switch ($this->action_modifier) {
                    case self::PUB:
                        if($method->isPublic()) {
                            $doc_methods[] = $method;
                        }
                        break;
                    case self::PRO:
                        if($method->isProtected()) {
                            $doc_methods[] = $method;
                        }
                        break;
                    case self::PRI:
                        if($method->isPrivate()) {
                            $doc_methods[] = $method;
                        }
                        break;
                    case self::PUB_PRO:
                        if($method->isPublic() || $method->isProtected()) {
                            $doc_methods[] = $method;
                        }
                        break;
                    case self::PUB_PRI:
                        if($method->isPublic() || $method->isPrivate()) {
                            $doc_methods[] = $method;
                        }
                        break;
                    case self::PRO_PRI:
                        if($method->isProtected() || $method->isPrivate()) {
                            $doc_methods[] = $method;
                        }
                        break;
                    default:
                        $doc_methods[] = $method;
                }
            }
            // 判断是否需要静态方法的注释
            if(!$this->static_action) {
                foreach ($doc_methods as $k => $m) {
                    if($m->isStatic()) {
                        unset($doc_methods[$k]);
                    }
                }
            }
            // 获取方法的注释
            $action_doc = [];
            foreach ($doc_methods as $method) {
                $action_doc[$method->getName()] = $this->getMethodDoc($method)[$method->getName()];
            }
            $class_info->setActionDoc($action_doc);
        }
    }


    /**
     * 获取单个方法的注释
     * @param \ReflectionFunctionAbstract $method
     * @return array
     */
    private function getMethodDoc(\ReflectionFunctionAbstract $method)
    {
        $method_name = $method->getName();
        $doc = $method->getDocComment();
        preg_match_all('/@[\s\S\{\}\[\]\"\'\:\,]*\)/U',$doc,$check);
        $doc_arr = [];
        if(!empty($check[0])) {
            foreach ($check[0] as $str) {
                preg_match('/@\w+/',$str,$keys);
                $temp[0] = trim($keys[0],'@');
                preg_match('/\([\s\S\{\}\[\]\"\'\:\,]*\)/',$str,$values);
                $temp[1] = $values[0];
                if(!empty($doc_arr[$temp[0]])) {
                    if(!is_array($doc_arr[$temp[0]])) {
                        $t = $doc_arr[$temp[0]];
                        $doc_arr[$temp[0]] = [];
                        $doc_arr[$temp[0]][] = $t;
                        $doc_arr[$temp[0]][] = trim($temp[1]);
                    }else{
                        $doc_arr[$temp[0]][] = trim($temp[1]);
                    }

                }else{
                    $doc_arr[$temp[0]] = trim($temp[1]);
                }
            }
            $action_doc = [];
            // 将指定的标签放入对应的classFile类
            if(!empty($this->annotation_tags)) {
                foreach ($this->annotation_tags as $tag) {
                    if(!empty($doc_arr[$tag])) {
                        $action_doc[$method_name][$tag] = $doc_arr[$tag];
                    }
                }
            }else{
                foreach ($doc_arr as $k => $v) {
                    $action_doc[$method_name][$k] = $v;
                }
            }
            return $action_doc;
        }
    }

    /**
     * @return string
     */
    public function getDir(): string
    {
        return $this->dir;
    }

    /**
     * @param string $dir
     * @return DocumentAnnotation
     */
    public function setDir(string $dir) : DocumentAnnotation
    {
        $this->dir = $dir;
        return $this;
    }

    /**
     * @return string
     */
    public function getSuffix(): string
    {
        return $this->suffix;
    }

    /**
     * @param string $suffix
     * @return DocumentAnnotation
     */
    public function setSuffix(string $suffix) : DocumentAnnotation
    {
        $this->suffix = $suffix;
        return $this;
    }

    /**
     * @return string
     */
    public function getNamespacePrefix(): string
    {
        return $this->namespace_prefix;
    }

    /**
     * @param string $namespace_prefix
     * @return DocumentAnnotation
     */
    public function setNamespacePrefix(string $namespace_prefix) : DocumentAnnotation
    {
        $this->namespace_prefix = $namespace_prefix;
        return $this;
    }

    /**
     * @return int
     */
    public function getActionModifier(): int
    {
        return $this->action_modifier;
    }

    /**
     * @param int $action_modifier
     * @return DocumentAnnotation
     */
    public function setActionModifier(int $action_modifier) : DocumentAnnotation
    {
        $this->action_modifier = $action_modifier;
        return $this;
    }

    /**
     * @return bool
     */
    public function isStaticAction(): bool
    {
        return $this->static_action;
    }

    /**
     * @param bool $static_action
     * @return DocumentAnnotation
     */
    public function setStaticAction(bool $static_action) : DocumentAnnotation
    {
        $this->static_action = $static_action;
        return $this;
    }

    /**
     * @return array
     */
    public function getAnnotationTags(): array
    {
        return $this->annotation_tags;
    }

    /**
     * @param array $annotation_tags
     * @return DocumentAnnotation
     */
    public function setAnnotationTags(array $annotation_tags) : DocumentAnnotation
    {
        $this->annotation_tags = $annotation_tags;
        return $this;
    }

    /**
     * @return array
     */
    public function getAnnotationInfo(): array
    {
        return $this->annotation_info;
    }

    /**
     * 获取对象
     * @return DocumentAnnotation
     */
    public static function getInstance()
    {
        return new static();
    }

}
