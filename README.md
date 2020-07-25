# rabc-annotaion
composer require caoshen1/annotation-rbac
# 指定路径
RbacService::setPath('路径','对应的命名空间');
# 构建权限
RbacService::build();
# 获取权限列表
RbacService::getAuthList();
# 根据权限ID生成权限字符串
RbacService::createStr([1,2,3,4,5]);
# 校验权限
RbacService::checkAuth('权限字符串','控制器全类名','方法名');