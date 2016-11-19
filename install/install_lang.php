<?php

if (!defined('THINKSNS_INSTALL')) {
    exit('Access Denied');
}

$i_message['install_lock'] = '您已安装过ThinkSNS '.$_TSVERSION.'，如果需要重新安装，请先删除data目录下的install.lock文件';
$i_message['install_title'] = 'ThinkSNS '.$_TSVERSION.' 安装向导';
$i_message['install_wizard'] = '安装向导';
$i_message['install_warning'] = '<strong>注意 </strong>这个安装程序仅仅用在你首次安装ThinkSNS。如果你已经在使用 ThinkSNS 或者要更新到一个新版本，请不要运行这个安装程序。';
$i_message['install_intro'] = '<h4>安装须知</h4><p>一、运行环境需求：PHP(5.3.12+)+MYSQL(5.5.12+)</p><p>二、安装步骤：<br /><br />1、使用ftp工具以二进制模式，将该软件包里的 thinksns 目录及其文件上传到您的空间，假设上传后目录仍旧为 thinksns。<br /><br />2、如果您使用的是Linux 或 Freebsd 服务器，先确认以下目录或文件属性为 (777) 可写模式。<br /><br />目录: data<br />目录: stroage/temp/<br />目录: install<br />目录: config<br />3、运行 http://yourwebsite/thinksns/install/install.php 安装程序，填入安装相关信息与资料，完成安装！<br />4、运行 http://yourwebsite/thinksns/cleancache.php 清除系统缓存文件！<br />5、运行 http://yourwebsite/thinksns/index.php 开始体验ThinkSNS！</p>';
$i_message['install_start'] = '开始安装ThinkSNS';
$i_message['install_license_title'] = '安装许可协议';
$i_message['install_license'] = '版权所有 (C) 2008-'.date('Y').'，ThinkSNS.com 保留所有权利。

ThinkSNS是由ThinkSNS项目组独立开发的SNS程序，基于PHP脚本和MySQL数据库。本程序源码开放的，任何人都可以从互联网上免费下载，并可以在不违反本协议规定的前提下进行使用而无需缴纳程序使用费。

官方网址： www.thinksns.com 交流社区： t.thinksns.com

为了使你正确并合法的使用本软件，请你在使用前务必阅读清楚下面的协议条款：

    智士软件（北京）有限公司为ThinkSNS产品的开发商，依法独立拥有ThinkSNS产品著作权（中华人民共和国国家版权局著作权登记号 2011SR069454）。智士软件（北京）有限公司网址为 http://www.zhishisoft.com，ThinkSNS官方网站网址为 http://www.thinksns.com。
    ThinkSNS著作权已在中华人民共和国国家版权局注册，著作权受到法律和国际公约保护。使用者：无论个人或组织、盈利与否、用途如何（包括以学习和研究为目的），均需仔细阅读本协议，在理解、同意、并遵守本协议的全部条款后，方可开始使用 ThinkSNS软件。
智士软件（北京）有限公司拥有对本授权协议的最终解释权。
1.0   协议许可的权利
1)    您可以在完全遵守本最终用户授权协议的基础上，将本软件应用于非商业用途，而不必支付软件版权授权费用；
2)    您可以在协议规定的约束和限制范围内修改 ThinkSNS 源代码或界面风格以适应您的网站要求；
3)    您拥有使用本软件构建的社区中全部会员资料、文章及相关信息的所有权，并独立承担与文章内容的相关法律义务；
4)    获得商业授权之后，您可以将本软件应用于商业用途，同时依据所购买的授权类型中确定的技术支持期限、技术支持方式和技术支持内容，自购买时刻起， 在技术支持期限内拥有通过指定的方式获得指定范围内的技术支持服务。商业授权用户享有反映和提出意见的权力，相关意见将被作为首要考虑，但没有一定被采纳的承诺或保证。
2.0   协议规定的约束和限制
1)    未获商业授权之前，不得将本软件用于商业用途（包括但不限于企业网站、经营性网站、以营利为目或实现盈利的网站）。购买商业授权请登录http://www.thinksns.com 参考相关说明，也可以致电8610- 82431402了解详情；
2)    不得对本软件或与之关联的商业授权进行出租、出售、抵押或发放子许可证；
3)    无论如何，即无论用途如何、是否经过修改或美化、修改程度如何，只要使用ThinkSNS的整体或任何部分，未经书面许可，页面页脚处的 Powered by ThinkSNS名称和官网网站的链接（http://www.thinksns.com ）都必须保留，而不能清除或修改；
4)    禁止ThinkSNS的整体或任何部分基础上以发展任何派生版本、修改版本或第三方版本用于重新分发；
5)    如果您未能遵守本协议的条款，您的授权将被终止，所被许可的权利将被收回，并承担相应法律责任。
3.0     有限担保和免责声明

1)    本软件及所附带的文件是作为不提供任何明确的或隐含的赔偿或担保的形式提供的；
2)    用户出于自愿而使用本软件，您必须了解使用本软件的风险，在尚未购买产品技术服务之前，我们不承诺提供任何形式的技术支持、使用担保，也不承担任何因使用本软件而产生问题的相关责任；
3)    智士软件（北京）有限公司不对使用本软件构建的社区中的文章或信息承担责任。
有关ThinkSNS最终用户授权协议、商业授权与技术服务的详细内容，均由ThinkSNS官方网站独家提供。智士软件（北京）有限公司拥有在不事先通知的情况下，修改授权协议和服务价目表的权力，修改后的协议或价目表对自改变之日起的新授权用户生效。
电子文本形式的授权协议如同双方书面签署的协议一样，具有完全的和等同的法律效力。您一旦开始安装 ThinkSNS，即被视为完全理解并接受本协议的各项条款，在享有上述条款授予的权力的同时，受到相关的约束和限制。协议许可范围以外的行为，将直接违反本授权协议并构成侵权，我们有权随时终止授权，责令停止损害，并保留追究相关责任的权力。

';
$i_message['install_agree'] = '我已看过并同意安装许可协议';
$i_message['install_disagree'] = '不同意';
$i_message['install_disagree_license'] = '您必须在同意授权协议的全部条件后，方可继续ThinkSNS的安装';
$i_message['install_prev'] = '上一步';
$i_message['install_next'] = '下一步';
$i_message['dirmod'] = '目录和文件的写权限';
$i_message['install_dirmod'] = '目录和文件是否可写，如果发生错误，请更改文件/目录属性 777';
$i_message['install_env'] = '服务器配置';
$i_message['php_os'] = '操作系统';
$i_message['php_version'] = 'PHP版本';
$i_message['php_memory'] = '内存限制';
$i_message['php_session'] = 'SESSION支持';
$i_message['php_session_error'] = 'SESSION目录不可写';
$i_message['file_upload'] = '附件上传';
$i_message['support'] = '支持';
$i_message['unsupport'] = '不支持';
$i_message['php_extention'] = 'PHP扩展';
$i_message['php_extention_unload_gd'] = '您的服务器没有安装这个PHP扩展：gd';
$i_message['php_extention_unload_mbstring'] = '您的服务器没有安装这个PHP扩展：mbstring';
$i_message['php_extention_unload_mysql'] = '您的服务器没有安装这个PHP扩展：mysql';
$i_message['php_extention_unload_curl'] = '您的服务器没有安装这个PHP扩展：curl';
$i_message['mysql'] = 'MySQL数据库';
$i_message['mysql_unsupport'] = '您的服务器不支持MYSQL数据库，无法安装ThinkSNS。';
$i_message['install_setting'] = '数据库资料与管理员帐号设置';
$i_message['install_mysql'] = '数据库配置';
$i_message['install_mysql_host'] = '数据库服务器';
$i_message['install_mysql_host_intro'] = '格式：地址(:端口)，一般为 localhost';
$i_message['install_mysql_username'] = '数据库用户名';
$i_message['install_mysql_password'] = '数据库密码';
$i_message['install_mysql_name'] = '数据库名';
$i_message['install_mysql_prefix'] = '表名前缀';
$i_message['install_mysql_prefix_intro'] = '同一数据库安装多个ThinkSNS时可改变默认值';
$i_message['site_url'] = ' 站点地址';
$i_message['site_url_intro'] = '';
$i_message['founder'] = '超级管理员资料';
$i_message['install_founder_name'] = '管理员帐号';
$i_message['install_founder_password'] = '密码';
$i_message['install_founder_rpassword'] = '重复密码';
$i_message['install_founder_email'] = '电子邮件';
$i_message['install_mysql_host_empty'] = '错误:数据库服务器不能为空';
$i_message['install_mysql_username_empty'] = '错误:数据库用户名不能为空';
$i_message['install_mysql_name_empty'] = '错误:数据库名不能为空';
$i_message['install_founder_name_empty'] = '错误:超级管理员用户名不能为空';
$i_message['install_founder_password_length'] = '错误:超级管理员密码必须大于6位';
$i_message['install_founder_rpassword_error'] = '错误:两次输入管理员密码不同';
$i_message['install_founder_email_empty'] = '错误:超级管理员Email不能为空';
$i_message['mysql_invalid_configure'] = '错误:数据库配置信息不完整';
$i_message['mysql_invalid_prefix'] = '错误:您指定的数据表前缀包含点字符(".")，请返回修改。';
$i_message['forbidden_character'] = '错误:用户名包含非法字符';
$i_message['founder_invalid_email'] = '错误:电子邮件格式不正确';
$i_message['founder_invalid_configure'] = '错误:超级管理员信息不完整';
$i_message['founder_invalid_password'] = '错误:密码长度必须大于6位';
$i_message['founder_invalid_rpassword'] = '错误:两次输入的密码不一致';
$i_message['founder_intro'] = '网站创始人，拥有最高权限';
$i_message['config_log_success'] = '数据库配置信息写入完成';
$i_message['define_log_success'] = '网站全局配置信息写入完成';
$i_message['config_read_failed'] = '错误:数据库配置文件写入错误，请检查config.inc.php文件是否存在或属性是否为777';
$i_message['define_read_failed'] = '错误:网站全局配置文件写入错误，请检查define.inc.php文件是否存在或属性是否为777';
$i_message['error'] = '错误';
$i_message['database_errno_2003'] = '错误:无法连接数据库，请检查数据库是否启动，数据库服务器地址是否正确';
$i_message['database_errno_1045'] = '错误:无法连接数据库，请检查数据库用户名或者密码是否正确';
$i_message['database_errno_1044'] = '错误:无法创建新的数据库，请检查数据库名称填写是否正确';
$i_message['database_errno_1064'] = '错误:SQL执行错误，请检查数据库名称填写是否正确';
$i_message['database_errno'] = '错误:程序在执行数据库操作时发生了一个错误，安装过程无法继续进行。';
$i_message['configure_read_failed'] = '数据库配置失败';
$i_message['mysql_version_402'] = '错误:您的 MYSQL 版本低于 5.0.0，安装无法继续进行！';
$i_message['thinksns_rebuild'] = '数据库中已经安装过 ThinkSNS，继续安装会清空原有数据！';
$i_message['mysql_import_data'] = '点击下一步开始导入数据';
$i_message['import_processing'] = '导入数据库';
$i_message['import_processing_error'] = '错误:导入数据库失败';
$i_message['create_table'] = '创建表';
$i_message['create_founder'] = '创建超级管理员帐户';
$i_message['create_founder_success'] = '超级管理员帐户创建成功';
$i_message['create_founder_error'] = '超级管理员帐户创建失败';
$i_message['create_founderpower_success'] = '超级管理员权限设置成功';
$i_message['create_founderpower_error'] = '超级管理员权限设置失败';
$i_message['create_cache'] = '创建缓存';
$i_message['create_cache_success'] = '创建缓存成功';
$i_message['auto_increment'] = '用户的起始ID';
$i_message['set_auto_increment_success'] = '用户起始ID设置成功';
$i_message['set_auto_increment_error'] = '用户起始ID设置失败';
$i_message['install_success'] = '安装成功';
$i_message['install_success_intro'] = '<p>安装程序执行完毕，请尽快删除整个 install 目录，以免被他人恶意利用。如要重新安装，请删除data目录的 install.lock 文件！</p><p><a href="../index.php">请点击这里开始体验ThinkSNS吧！</a></p>';
$i_message['install_dbFile_error'] = '数据库文件无法读取，请检查/install/ThinkSNS.sql是否存在或者拥有读取权限。';
