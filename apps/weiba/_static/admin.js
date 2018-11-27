/**
 * 微吧后台JS操作对象 -
 * 
 * 微吧后台所有JS操作都集中在此
 */

var admin = {};

/**
 * 收缩展开某个DOM
 */
admin.fold = function(id){
  	$('#'+id).slideToggle('fast');
};

/**
 * 处理ajax返回数据之后的刷新操作
 */
admin.ajaxReload = function(obj,callback){
    if("undefined" == typeof(callback)){
        callback = "location.href = location.href";
    }else{
        callback = 'eval('+callback+')';
    }
    if(obj.status == 1){
        ui.success(obj.data);
        setTimeout(callback,1500);
     }else{
        ui.error(obj.data);
    }
};

admin.getChecked = function() {
    var ids = new Array();
    $.each($('#list input:checked'), function(i, n){
        if($(n).val() !='0' && $(n).val()!='' ){
            ids.push( $(n).val() );    
        }
    });
    return ids;
};

admin.checkon = function(o){
    if( o.checked == true ){
        $(o).parents('tr').addClass('bg_on');
    }else{
        $(o).parents('tr').removeClass('bg_on');
    }
};

admin.checkAll = function(o){
    if( o.checked == true ){
        $('#list input[name="checkbox"]').attr('checked','true');
        $('tr[overstyle="on"]').addClass("bg_on"); 
    }else{
        $('#list input[name="checkbox"]').removeAttr('checked');
        $('tr[overstyle="on"]').removeClass("bg_on");
    }
};
//绑定tr上的on属性
admin.bindTrOn = function(){
    $("tr[overstyle='on']").hover(
      function () {
        $(this).addClass("bg_hover");
      },
      function () {
        $(this).removeClass("bg_hover");
      }
    );
};

admin.upload = function(type,obj){
    if("undefined"  != typeof(core.uploadFile)){
        core.uploadFile.filehash = new Array();
    }
    core.plugInit('uploadFile',obj,function(data){
        $('.input-content').remove();
        $('#show_'+type).html('<img src="'+data.src+'" width="100" height="100">');
        $('#form_'+type).val(data.attach_id);    
    },'image');
};

admin.checkAddWeiba = function(form){
	if(getLength(form.weiba_name.value) < 1){
		ui.error('请输入微吧名称');
		return false;
	}
	if(getLength(form.logo.value) < 1){
		ui.error('请上传logo');
		return false;
	}
	if(getLength($('#form_intro').val()) < 1){
		ui.error('请输入微吧简介');
		return false;
	}
    return true;	
};

/**
 * 设置微吧推荐状态
 * @param integer weiba_id 微吧ID
 * @param integer type 当前微吧的推荐状态
 * @return void
 */
admin.recommend = function(weiba_id, type){
    $.post(U('weiba/Admin/setRecommend'),{weiba_id:weiba_id,type:type},function(msg){
        admin.ajaxReload(msg);
    },'json');
};

/**
 * 解散微吧
 * @param integer weiba_id 微吧ID
 * @return void
 */
admin.delWeiba = function(weiba_id){
    if("undefined" == typeof(weiba_id) || weiba_id=='') weiba_id = admin.getChecked();
    if(weiba_id==''){
        ui.error('请选择要解散的微吧');return false;
    }  
    if(confirm('解散微吧会删除该微吧下的所有帖子，确定要解散此微吧吗？')){
        $.post(U('weiba/Admin/delWeiba'),{weiba_id:weiba_id},function(msg){
            admin.ajaxReload(msg);
        },'json');
    }
};
/**
 * 删除微吧分类
 * @param integer cate_id 分类ID
 * @return void
 */
admin.delWeibaCate = function(cate_id){
    if("undefined" == typeof(cate_id) || cate_id=='') cate_id = admin.getChecked();
    if(cate_id==''){
        ui.error('请选择要删除的分类');return false;
    }  
    if(confirm('确定要删除分类吗？')){
        $.post(U('weiba/Admin/delWeibaCate'),{cate_id:cate_id},function(msg){
            admin.ajaxReload(msg);
        },'json');
    }
};
/**
 * 设置帖子状态
 * @param integer post_id 帖子ID
 * @param integer type 要设置的帖子类型 1:推荐，2:精华，3:置顶
 * @param integer curValue 当前状态值
 * @param integer topValue 置顶值，仅置顶用到
 * @return void
 */
admin.setPost = function(post_id, type, curValue, topValue){
    //alert(topValue);exit;
    $.post(U('weiba/Admin/setPost'),{post_id:post_id,type:type,curValue:curValue,topValue:topValue},function(msg){
        admin.ajaxReload(msg);
    },'json');
};

/**
 * 编辑帖子表单验证
 * @return void
 */
admin.checkEditPost = function(form){
    if(getLength(form.title.value) < 1){
        ui.error('帖子标题不能为空');
        return false;
    }
    if(getLength(form.content.value) < 1){
        ui.error('帖子内容不能为空');
        return false;
    }
    return true;
};

/**
 * 删除帖子至回收站
 * @param integer post_id 帖子ID
 * @return void
 */
admin.delPost = function(post_id){
    if("undefined" == typeof(post_id) || post_id=='') post_id = admin.getChecked();
    if(post_id==''){
        ui.error('请选择要删除的帖子');return false;
    }  
    $.post(U('weiba/Admin/delPost'),{post_id:post_id},function(msg){
        admin.ajaxReload(msg);
    },'json');
};

admin.removePost = function(post_id){
    if("undefined" == typeof(post_id) || post_id=='') post_id = admin.getChecked();
    if(post_id==''){
        ui.error('请选择要移除的帖子');return false;
    }  
    $.post(U('weiba/Admin/removePost'),{post_id:post_id},function(msg){
        admin.ajaxReload(msg);
    },'json');
};

/**
 * 调整帖子评论楼层
 * @param integer post_id 帖子ID
 * @return void
 */
admin.doStorey = function(post_id){
    if("undefined" == typeof(post_id) || post_id=='') post_id = admin.getChecked();
    if(post_id==''){
        ui.error('请选择要调整回复楼层的帖子');return false;
    }  
    $.post(U('weiba/Admin/doStorey'),{post_id:post_id},function(msg){
        if(msg==1){
            ui.success('操作成功');
        }
    });
};

/**
 * 还原已删除的帖子
 * @param mixed post_id 帖子ID
 * @return void
 */
admin.recoverPost = function(post_id){
    if("undefined" == typeof(post_id) || post_id=='') post_id = admin.getChecked();
    if(post_id==''){
        ui.error('请选择要还原的帖子');return false;
    }
    $.post(U('weiba/Admin/recoverPost'),{post_id:post_id},function(msg){
            admin.ajaxReload(msg);
        },'json');
};

/**
 * 删除帖子至回收站
 * @param integer post_id 帖子ID
 * @return void
 */
admin.deletePost = function(post_id){
    if("undefined" == typeof(post_id) || post_id=='') post_id = admin.getChecked();
    if(post_id==''){
        ui.error('请选择要删除的帖子');return false;
    }  
    if(confirm('删除后不可恢复，确定要删除帖子吗')){
        $.post(U('weiba/Admin/deletePost'),{post_id:post_id},function(msg){
            admin.ajaxReload(msg);
        },'json');
    }
};

/**
 * 圈主审核
 */
admin.doAudit = function(weiba_id, uid, value){
    $.post(U('weiba/Manage/verify'),{weiba_id:weiba_id,uid:uid,value:value},function(msg){
        admin.ajaxReload(msg);
    },'json');
};

admin.doWeibaAudit = function(weiba_id, value){
    if("undefined" == typeof(weiba_id) || weiba_id=='') weiba_id = admin.getChecked();
    if(weiba_id==''){
        ui.error('请选择微吧');return false;
    }
    $.post(U('weiba/Admin/doWeibaAudit'),{weiba_id:weiba_id,value:value},function(msg){
            admin.ajaxReload(msg);
        },'json');
}

/**
 * 添加首页帖子
 * @return void
 */
admin.checkNewImg = function(form){
    if(getLength(form.post_id.value) < 1){
        ui.error('请输入帖子id');
        return false;
    }
    if(getLength(form.index_img.value) < 1){
        ui.error('请上传摘要图');
        return false;
    }
    return true;    
};
/**
 * 审核帖子
 * @param integer post_id 帖子ID
 * @return void
 */
admin.auditPost = function(post_id){
    if("undefined" == typeof(post_id) || post_id=='') post_id = admin.getChecked();
    if(post_id==''){
        ui.error('请选择要通过审核的帖子');return false;
    }
    $.post(U('weiba/Admin/auditPost'),{post_id:post_id},function(msg){
        admin.ajaxReload(msg);
    },'json');
};
