/**
 * 后台JS操作对象 -
 * 
 * 后台所有JS操作都集中在此
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
/*
 * 上移对象
 */

admin.moveUp = function(obj,topList)
{
    var current=$(obj).parent().parent();
    var prev=current.prev();
    if(!topList || topList=='undefined'){
    	topList = 1;
    }
    if(current.index()>1)
    {
        current.insertBefore(prev);
        return true;
    }else{
    	ui.error(L('PUBLIC_NOMOVE_UP'));
        return false;
    }
}

/*
 * 下移对象
 */
admin.moveDown = function(obj)
{
    var current=$(obj).parent().parent();
    var next=current.next();
    if(next)
    {
        current.insertAfter(next);
        return true;
    }else{
    	ui.error(L('PUBLIC_NOMOVE_DOWN'));
        return false;
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

/*知识相关*/

//选择某个知识类型
admin.selectLog = function(value,def){
    if(!def){
        def ='';
    }
    if(value!='0'){
        $.post(U('admin/Home/_getLogGroup'),{app_name:value,def:def},function(msg){
            if($('#selectAfter').length > 0){
                $('#selectAfter').html(msg);
            }else{
                $('#form_app_name').after("<span id='selectAfter'>"+msg+"</span>");    
            }
            
        }) ;
    }else{
        $('#selectAfter').html('');
    }
};
//清理知识
admin.cleanLogs = function(m){
    if(m!=6 && m!=12){
    	ui.error(L('PUBLIC_TIME_ISNOT'));       
    }else{
        $.post(U('admin/Home/_cleanLogs'),{m:m},function(msg){
            admin.ajaxReload(msg);
        },'json');
    }
};
//知识归档
admin.logsArchive = function(){
    $.post(U('admin/Home/_logsArchive'),{},function(msg){
            admin.ajaxReload(msg);
        },'json') ;
};
//删除一条知识
admin.dellog = function(id,table){
	if(confirm(L('PUBLIC_DELETE_NOTE_TIPS'))){
		$.post(U('admin/Home/_delLogs'),{id:id,table:table},function(msg){
            admin.ajaxReload(msg,"$('#tr"+id+"').remove()");
		},'json');
	}
};
admin.delselectLog = function(table){
	var id = admin.getChecked();
	if(id==''){
		ui.error(L('PUBLIC_SELECT_DELETE_TIPS'));
        return false;
	}else{
		if(confirm(L('PUBLIC_DELETE_NOTE_SELECT_TIPS'))){	
			$.post(U('admin/Home/_delLogs'),{id:id,table:table},function(msg){
                admin.ajaxReload(msg);
			},'json');
		}
	}
};
/* 积分相关 */
//添加积分类型
admin.addCreditType = function(){
    location.href = U('admin/Config/addCreditType');
};
//验证积分类型
admin.checkCreditType = function(form){
    if(form.CreditType.value =='' || getLength(form.CreditType.value) < 1){
        ui.error( L('PUBLIC_TYPENOT_ISNULL') );
        return false;
    }
    if(form.CreditName.value =='' || getLength(form.CreditName.value) < 1){
        ui.error( L('PUBLIC_TYPENAME_ISNULL') );
        return false;
    }
    return true;
};
admin.checkCreditSet = function(form){
    var uid_chose = $("input[name='uid_chose']:checked").val();
    if(uid_chose == '0'){
        if(form.uids.value==''){
            ui.error( '用户ID不能为空' );
        }
    }
	//if(form.nums.value=='' || form.nums.value <1){
    if(form.nums.value===''){
		ui.error( L('PUBLIC_NUMBER_ISNULL') );
		return false;
	}
    var todo = $("input[name='todo']:checked").val();
    if(todo==1 && form.nums.value=='0'){
        ui.error( '数量不能为0' );
        return false;
    }
	return true;
};
admin.delCreditType = function(type){
   if(!type){
        var type = admin.getChecked(); 
   }
   if(type==''){
        ui.error( L('PUBLIC_PLEASE_SELECTDATA') );
   }else{
        $.post(U('admin/Config/_delCreditType'),{type:type},function(msg){
            admin.ajaxReload(msg);
        },'json');
   }
};
//部门子集管理
admin.selectDepart = function(pid,obj,sid){    
    obj.nextAll().remove();
    if("undefined"==typeof(sid))
    	sid ='';
    if( pid > 0 ){
        //obj.after('<span>加载中……</span>');    //TODO 修改成图片
        $.post(U('admin/Public/selectDepartment'),{pid:pid,sid:sid},function(msg){
            obj.nextAll().remove();
            if(msg.status=="1"){
                obj.after(msg.data);
            }
        },'json');
    }
};

//默认生成部门 --已知道子部门集合
admin.departDefault = function(ids,domid){
	if("undefined" == typeof(ids) || ids==''){
		return false;
	}
	var id = ids.split(',');
	var obj = $('#'+domid);
	var objVal = obj.val();
	if(objVal =='' || objVal=='0'){
		return fasle;
	}
	var _defaultDepart = function(pid,_obj,i){
		var sid = id[i];
		$.post(U('admin/Public/selectDepartment'),{pid:pid,sid:sid},function(msg){
	        if(msg.status=="1"){
	            _obj.after(msg.data);
	            if("undefined" != id[i+1] && id[i+1] !='' &&  id[i+1] !='0'){
		        	var objVal = sid;
		        	var obj = $('#_parent_dept_'+pid);
		        	_defaultDepart(objVal,obj,i+1);
		        }
	        }
	    },'json');
	}
	_defaultDepart(objVal,obj,0);
};
// 添加用户验证信息
admin.addUserSubmitCheck = function(form) {
    if(getLength(form.password.value) < 1) {
        ui.error(L('PUBLIC_PASSWORD_EMPTY'));
        return false;
    }
    
    if(!admin.checkUser(form)) {
        return false;
    }

    if($('input:[name="user_group[]"]:checked').length <1){
        ui.error('请选择用户组');
        return false;
    }
       
    return true;
};
// 检验用户基本信息
admin.checkUser = function(form){
    if(getLength(form.email.value) < 1){
        ui.error(L('PUBLIC_EMAIL_EMPTY'));
        return false;
    }
    if(getLength(form.uname.value) < 1){
        ui.error(L('PUBLIC_USER_EMPTY'));
        return false;
    }
/*    if(form.department_id.value < 1 && $('#department_show').html()==''){
        ui.error(L('PUBLIC_SELECT_DEPARTMENT'));
        return false;
    }*/
    if($(form).find('input[name="user_category[]"]').length != 0 && $(form).find('input[name="user_category[]"]:checked').length < 1) {
        ui.error("请选择用户职业信息");
        return false;
    }
    
    return true;
};

admin.selectUserDepart = function(){
	var oldDepartMent = $('#form_department_id').val();
	var oldDepartMentName = $('#form_show_user_department').val(); 
	ui.box.load(U('widget/SelectDepartment/render')+'&departDomId=form_department_id&departId='+oldDepartMent+'+&departName='+oldDepartMentName+'&tpl=public&callback=admin.doSelectUserDepart','部门选择');
};

//做完选择
admin.doSelectUserDepart = function(){
	$('#form_department_id').val($('#tboxDepartMentId').val());
	$('#form_show_user_department').next().html($('#tboxDepartMentId').prev().html());
};
//验证部门
admin.checkDepartment = function(form){
    if(form.title.value=='' || getLength(form.title.value)<1){
        ui.error( L('PUBLIC_DEPARENT_ISNULL') );
        return false;
    }
    return true;
};
admin.bindCatetree = function(){
    $("td[catetd='yes']").click(function(){
        var relId = $(this).attr('rel');
        $('#table'+relId).slideToggle();
    });
    $("span[rel='del']").click(function(){
    	var id = $(this).attr('cateid');
    	var callfunc = 'admin.'+$(this).attr('func')+'del('+id+')';
    	eval(callfunc);
    });
    $("span[rel='edit']").click(function(){
    	var id = $(this).attr('cateid');
    	var callfunc = 'admin.'+$(this).attr('func')+'edit('+id+')';
    	eval(callfunc);
    });
    $("span[rel='move']").click(function(){
    	var id = $(this).attr('cateid');
    	var callfunc = 'admin.'+$(this).attr('func')+'move('+id+')';
    	eval(callfunc);
    });
};
//分类相关的修改、删除操作列表
//删除部门
admin.departmentdel = function(id){
	var url = U('admin/Department/delDepartment')+'&id='+id;
	//ui.tbox("load('"+url+"','删除部门')");
	ui.box.load(url,L('PUBLIC_DELETE_DEPARENT'));
};
admin.dodeldepart = function(id){

	var topid = $('#DepartmentSelect').attr('departmentid');
	 
	if("undefined" == typeof(topid) || topid == 0 ){
        ui.error( L('PUBLIC_SELECT_NEWDEPARENT') );
        return false;
    }
	$.post(U('admin/Department/dodelDepartment'),{id:id,topid:topid},function(msg){
		ui.box.close();
		admin.ajaxReload(msg);
	},'json');
};
//修改部门名称
admin.departmentedit = function(id){
	var url = U('admin/Department/editDepartment')+'&id='+id;
	//ui.tbox("load('"+url+"','修改名称')");
	ui.box.load(url,L('PUBLIC_EDIT_NAME'));
};
admin.doeditdepart = function(){
	var id = $('#editid').val();
	var title = $('#edittitle').val();	
	var display_order = $('#display_order').val();
    if(getLength(title) < 1){
          ui.error(L('PUBLIC_DEPARENT_ISNULL'));
          return false;
    }
	$.post(U('admin/Department/doeditDepartment'),{id:id,title:title,display_order:display_order},function(msg){
		ui.box.close();
		admin.ajaxReload(msg);
	},'json');
	
};
//移动部门
admin.departmentmove = function(id){
	var url = U('admin/Department/moveDepartment')+'&id='+id;
	//ui.tbox("load('"+url+"','移动名称')");
	ui.box.load(url,L('PUBLIC_MOVE_NAME'));
};
admin.domovedepart = function(id,oldid){

	var topid = $('#DepartmentSelect').attr('departmentid');
	if(oldid == topid){
        ui.error(L('PUBLIC_EDIT_NO'));
        return false;
    }
	$.post(U('admin/Department/domoveDepartment'),{id:id,topid:topid},function(msg){
		ui.box.close();
		admin.ajaxReload(msg);
	},'json');	
};



admin.addUserGroup = function(){
     location.href = U('admin/UserGroup/addUsergroup');
};
//删除用户组
admin.delUserGroup = function(obj,gid){
    if("undefined" == typeof(gid) || gid ==''){
        gid = admin.getChecked();
        if(gid.length == 0){
            ui.error(L('PUBLIC_SELECT_EDIT_GROUP'),3);
            return false;
        }
    }
    if("string" == typeof(gid)){
    	if( gid <= 3){
    		ui.error( L('PUBLIC_ADMIN_GROUP_IS') );
    		return false;
    	}
    }else{
    	for(var i in gid){
    		if(gid[i] <=3 ){
    			ui.error( L('PUBLIC_ADMIN_GROUP_IS') );
    	    	return false;
    		}
    	}
    }
    if(confirm( L('PUBLIC_DELETE_GROUP_TIPES') )){
    	$.post(U('admin/UserGroup/delgroup'),{gid:gid},function(msg){
			admin.ajaxReload(msg);
    	},'json');
    }
};

admin.checkUserGroup = function(form){
    var user_group_name = form.user_group_name.value;
    if( getLength(user_group_name) < 1 ){
        form.user_group_name.value = '';
        ui.error( L('PUBLIC_PLEASE_SUERGROUPNAME') );
        return false;
    }
    return true;
};
//绑定权限配置页面checkbox时间
admin.bindperm = function(){
    $('.hAll').click(function(){
      var checked = $(this).attr("checked");
      if(!checked){
        var rel = $(this).attr("rel");
        var name = $(this).attr('name');
        $('.'+name).removeAttr("checked");
        $('.vAll').removeAttr("checked");
      }else{
        var rel = $(this).attr("rel");
        var name = $(this).attr('name');
        $('.'+name).attr("checked","checked");
      }
      
      });
    $('.vAll').click(function(){
      var checked = $(this).attr("checked");
      if(!checked){
        var rel = $(this).attr("rel");
        var name = $(this).attr('name');
        $('.'+name+"_"+rel).removeAttr("checked");
        $('.hAll').removeAttr("checked");
      }else{
        var rel = $(this).attr("rel");
        var name = $(this).attr('name');
        $('.'+name+"_"+rel).attr("checked","checked");
      }
      });
};
//删除计划任务
admin.delschedule = function(){
   var id = admin.getChecked();
   if(id==''){
	   ui.error( L('PUBLIC_SELECT_TASK_TIPES') );
       return false;
   }
   if(confirm( L('PUBLIC_DELETE_TASK') )){
	   $.post(U('admin/Home/doDeleteSchedule'),{id:id},function(msg){
			admin.ajaxReload(msg);
   	},'json');
   }
};
//内容管理用到的JS
admin.ContentEdit = function(_id,action,title,type){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
    if(id==''){
        ui.error(L('PUBLIC_SELECT_TITLE_TYPE',{'title':title,'type':type}));
        return false;
	}
   if(confirm(L('PUBLIC_CONFIRM_DO',{'title':title,'type':type}))){
	   $.post(U('admin/Content/'+action),{id:id},function(msg){
			admin.ajaxReload(msg);
  	 },'json');
   }
};

admin.delArticle = function(_id,type){
	 var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
     var title = type==1 ?L('PUBLIC_ACCONTMENT'):L('PUBLIC_FOOTER_NOTE');
	 if(id==''){
    	ui.error( L('PUBLIC_PLEASE_DELETE_TITLE',{'title':title}) );
        return false;
	 }
    if(confirm( L('PUBLIC_ANSWER_DELETE_TITLE',{'title':title}) )){
	   $.post(U('admin/Config/delArticle'),{id:id,type:type},function(msg){
			admin.ajaxReload(msg);
   	 },'json');
    }
};

admin.delFeedback = function(_id){
   var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
   if(confirm( L('PUBLIC_ADD_NOTE_TIPES') )){
	   $.post(U('admin/Home/delFeedback'),{id:id},function(msg){
		 admin.ajaxReload('1',callback);
  	 });
   }
};

admin.delFeedbackType = function(_id){
	   //var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
	   if(confirm( L('PUBLIC_ANSWER_DELETE_CATEGORY') )){
		   $.post(U('admin/Home/delFeedbackType'),{id:id},function(msg){
			 admin.ajaxReload(msg,callback);
	  	 });
	   }
	};

admin.delsystemdata = function(id){
    if("undefined" == typeof(id) || id==''){
        ui.error( L('PUBLIC_PLEASE_DELTER_TIPES') );
     }
    if(confirm( L('PUBLIC_ANSWER_PLEASE_DELETE_TIPES') )){
       $.post(U('admin/Home/deladdsystemdata'),{key:id},function(msg){
            admin.ajaxReload(msg);
     },'json');
    }
};
//删除导航配置
admin.delnav = function(id){
    if(confirm( L('PUBLIC_ANSWER_DELETE') )){
    	
        $.post(U('admin/Config/delNav'),{id:id},function(msg){
             admin.ajaxReload(msg);
      },'json');
     }
	
}
//验证应用信息
admin.checkAppInfo = function(form){
	if(form.app_name.value=='' || getLength(form.app_name.value) < 1 ){
		ui.error( L('PUBLIC_APPNAME_ISNULL') );
		return false;
	}
	if(form.app_alias.value=='' || getLength(form.app_alias.value) < 1){
		ui.error( L('PUBLIC_APPNAME_ISNULL') );
		return false;
	}
	if(form.app_entry.value=='' || getLength(form.app_alias.value) < 1){
		ui.error( L('PUBLIC_APPCENT_ISNULL') );
		return false;
	}
	 return true;
};
// 表单信息验证
admin.checkNavInfo = function(form) {
	if(form.navi_name.value.replace(/^ +| +$/g,'')==''){
		ui.error( L('PUBLIC_LEADNAME_ISNULL') );
		return false;
	}
	if(form.app_name.value.replace(/^ +| +$/g,'')==''){
		ui.error('英文名称不能为空');
		return false;
	}
	if(form.url.value.replace(/^ +| +$/g,'')==''){
		ui.error( L('PUBLIC_HREF_ISNULL') );
		return false;
	}
	if(form.position.value.replace(/^ +| +$/g,'')==''){
		ui.error( L('PUBLIC_LEAD_ISNULL') );
		return false;
	}
	if(form.order_sort.value.replace(/^ +| +$/g,'')==''){
		ui.error( L('PUBLIC_APP_UPDATE_ISNULL') );
		return false;
	}
	return true;
};

admin.setAppStatus = function(app_id,status){
	if(app_id ==''){
	  var app_id = admin.getChecked();
	}
    if(app_id == ''){
        ui.error( L('PUBLIC_PLEASE_APP') );
        return false;
    }
	$.post(U('admin/Apps/setAppStatus'),{app_id:app_id,status:status},function(msg){
        admin.ajaxReload(msg);
    },'json');
};

admin.moveAppUp = function(obj,app_id){
	alert('up');
};

admin.moveAppDown = function(obj,app_id){
	alert('down');
};
//站点配置页面JS
admin.siteConfigDefault = function(value){

	var html ='<input type="submit" value="'+L('PUBLIC_QUEDING')+'" onclick="return confirm(\''+L('PUBLIC_CLOSE_LOCALHOST_TIPES')+'\')"' 
		  +' id ="form_submit_2" class="btn_b">';
	$(html).insertAfter($('#form_submit')).hide();
	admin.siteConfig(value);
};
admin.siteConfig = function(value){
   
	$('.form2 dl').each(function(){
		var _id = $(this).attr('id');
		if(_id != "dl_site_closed"){
			if(value == "1"){
				if(_id != "dl_site_closed_reason"){
					$(this).show();
				}else{
					$(this).hide();
				}
			}else{
				if(_id == "dl_site_closed_reason"){
					$(this).show();
				}else{
					$(this).hide();
				}
			}
		}
	});
	if(value==1){
		$('#form_submit').show();$('#form_submit_2').hide();
	}else{
		$('#form_submit_2').show();$('#form_submit').hide();
	}
};

admin.registerConfig = function(value){
	$('.form2 dl').each(function(){
		var _id = $(this).attr('id');
		if( _id !='dl_register_type'){
			switch(value){
				case 'closed':
					if(_id != 'dl_register_close'){
						$(this).hide(); 
					}else{
						$(this).show();
					}	
					break;
				case 'open':
					if(_id == 'dl_email_suffix' || _id == 'dl_register_close'){
						$(this).hide();
					}else{
						$(this).show();
					}
					break;
				case 'appoint':
					if( _id == 'dl_register_close' ){
						$(this).hide();
					}else{
						$(this).show();
					}
					break;
			}
		}	
	});
};
admin.addmedal = function(value){
	if ( value == 0 ){
		$('#dl_attach_id').show();
		$('#dl_attach_small').show();
		$('#dl_medal_name').show();
		$('#dl_medal_desc').show();
	} else {
		$('#dl_attach_id').hide();
		$('#dl_attach_small').hide();
		$('#dl_medal_name').hide();
		$('#dl_medal_desc').hide();
	}
};
// 禁用用户
admin.delUser = function(id){
    if("undefined" == typeof(id) || id=='')
        id = admin.getChecked();
    if(id==''){
        ui.error( L('PUBLIC_PLEASE_SELECT_NUMBER') );return false;
    }  
    if(confirm( L('PUBLIC_ANSWER_BUMBER_NO') )){
        $.post(U('admin/User/doDeleteUser'),{id:id},function(msg){
            admin.ajaxReload(msg);
        },'json');
   }
};
// 彻底删除用户
admin.trueDelUser = function(id){
    if("undefined" == typeof(id) || id=='')
        id = admin.getChecked();
    if(id==''){
        ui.error( L('PUBLIC_PLEASE_SELECT_NUMBER') );return false;
    }  
    if(confirm( '确定要彻底删除选中帐号？' )){
        $.post(U('admin/User/doTrueDeleteUser'),{id:id},function(msg){
            admin.ajaxReload(msg);
        },'json');
   }
};
// 恢复用户
admin.rebackUser = function(id){
     if("undefined" == typeof(id) || id=='')
        id = admin.getChecked();
    if(id==''){
        ui.error( L('PUBLIC_PLEASE_SELECT_NUMBER') );return false;
    }  
    //alert(id);exit;
    if(confirm( L('PUBLIC_ANSWER_NUMBER') )){
        $.post(U('admin/User/doRebackUser'),{id:id},function(msg){
            admin.ajaxReload(msg);
        },'json');
   }
};

admin.disableUser = function(id, type) {
    if (typeof id === 'undefined' || id == '') {
        id = admin.getChecked();
    }
    if (id == '') {
        ui.error(L('PUBLIC_PLEASE_SELECT_NUMBER'));
        return false;
    }
    ui.box.load(U('admin/User/disableUserBox', ['uid='+id, 't='+type]), '禁用用户');
    return false;
};

admin.enableUser = function(id) {
    if (typeof id === 'undefined' || id == '') {
        return false;
    }
    if (confirm('确定恢复该用户')) {
        $.post(U('admin/User/setEnableUser'), {id:id}, function(res) {
            if (res.status == 1) {
                ui.success(res.info);
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                ui.error(res.info);
            }
        }, 'json');
    }    
    return false;
}

//激活/取消激活 用户
admin.activeUser = function(id,type){
    if("undefined" == typeof(id) || id=='')
        id = admin.getChecked();
    if(id==''){
        ui.error( L('PUBLIC_PLEASE_SELECT_USER') );return false;
    }  
    $.post(U('admin/User/doActiveUser'),{id:id,type:type},function(msg){
            admin.ajaxReload(msg);
    },'json');
};

//激活/取消激活 用户
admin.auditUser = function(id,type){
    if("undefined" == typeof(id) || id=='')
        id = admin.getChecked();
    if(id==''){
        ui.error( L('PUBLIC_PLEASE_SELECT_USER') );return false;
    }  
    $.post(U('admin/User/doAuditUser'),{id:id,type:type},function(msg){
            admin.ajaxReload(msg);
    },'json');
};

//转移部门
admin.changeUserDepartment = function(){
	var id = admin.getChecked();
	if(id ==''){
		ui.error( L('PUBLIC_PLEASE_SELECT_USER') );return false;
	}
	var url = U('admin/User/moveDepartment')+'&uid='+id;
	//ui.tbox("load('"+url+"','转移部门')");
	ui.box.load(url, L('PUBLIC_MOVE_DEPARTMENT') );
};



admin.domoveUserdepart = function(){
	var uid = $('#uid').val();
	var topid = $('#DepartmentSelect').attr('departmentid');
     
    if("undefined" == typeof(topid) || topid == 0 || topid == null){
        ui.error( L('PUBLIC_SELECT_NEWDEPARENT') );
        return false;
    }
	$.post(U('admin/User/domoveDepart'),{uid:uid,topid:topid},function(msg){
		admin.ajaxReload(msg);
	},'json');
};

admin.domoveUsergroup = function(){
	var ids = new Array();
    $.each($('#movegroup input:checked'), function(i, n){
        if($(n).val() !='0' && $(n).val()!='' ){
            ids.push( $(n).val() );    
        }
    });
    if(ids.length<1){
    	ui.error( L('PUBLIC_PLEASE_SELECT_USERGROUP') );return false;
    }	
    ids = ids.join(',');
    var uid = $('#uid').val();
    $.post(U('admin/User/domoveUsergroup'),{uid:uid,user_group_id:ids},function(msg){
    	admin.ajaxReload(msg);
    },'json');
};

//转移用户组
admin.changeUserGroup = function(){
	var id = admin.getChecked();
	if(id ==''){
		ui.error( L('PUBLIC_PLEASE_SELECT_USER') );return false;
	}
	var url = U('admin/User/moveGroup')+'&uid='+id;
	//ui.tbox("load('"+url+"','转移用户组')");
	ui.box.load(url,L('PUBLIC_MOVE_USERGROUP'));
};

//添加用户

//删除资料字段
admin.delProfileField = function(id,t){
   if("undefined" == typeof(id)){
	   var id = admin.getChecked();
   }
   if(t==1){
    var msg  = L('PUBLIC_PLEASE_DELETE_FIELD');
    var conf = L('PUBLIC_ANSWER_DELETE_FIELD');
   }else{
    var msg  = L('PUBLIC_SELECT_DELETE_CATEGORY');
    var conf = L('PUBLIC_ANSER_DELETE_CATEGORY');
   }
   if(id==''){
       ui.error(msg);
       return false;
   }
   if(confirm(conf)){
       $.post(U('admin/User/doDeleteProfileField'),{id:id},function(msg){
            admin.ajaxReload(msg);
    },'json');
   }
};


admin.upload = function(type,obj){
    if("undefined"  != typeof(core.uploadFile)){
        core.uploadFile.filehash = new Array();
    }
	core.plugInit('uploadFile',obj,function(data){
        $('.input-content').remove();
        $('#show_'+type).html('<img class="pic-size" src="'+data.src+'">');
        $('#form_'+type).val(data.attach_id);    
    },'image');
};


admin.setCredit = function(v){
	if("undefined"==typeof(v)){
		v = 0;
	}
	if(v==0){
		$('#dl_uids').show();
		$('#dl_userGroup').hide();
	}else{
		$('#dl_uids').hide();
		$('#dl_userGroup').show();
	}
};

admin.delCreditNode = function(id){
    if("undefined" == typeof(id)){
       var id = admin.getChecked();
   }
   if(id==''){
       ui.error( L('PUBLIC_PLEASE_SELECT_INTEG0RL') );
       return false;
   }
   if(confirm( L('PUBLIC_ANSWER_INTEG0RL') )){
       $.post(U('admin/Apps/delCreditNode'),{id:id},function(msg){
            admin.ajaxReload(msg);
    },'json');
   }
};

admin.delPermNode = function(id){
 if("undefined" == typeof(id)){
       var id = admin.getChecked();
   }
   if(id==''){
       ui.error( L('PUBLIC_PLEASE_DELETEOPINT') );
       return false;
   }
   if(confirm( L('PUBLIC_ANSWER_SELECT_OPINT') )){
       $.post(U('admin/Apps/delPermNode'),{id:id},function(msg){
            admin.ajaxReload(msg);
    },'json');
   }
};

admin.checkCreditNode = function(obj){
    var reg = /\W+/g;
    var appname = $('#form_appname').val();
    var action = $('#form_action').val();
    if(reg.test(appname)){
        ui.error( L('PUBLIC_ADMIN_APP_TIPES') ); return false;
    }else{
        if(reg.test(action)){
            ui.error( L('PUBLIC_GONGFU_ISNULL') );return false;
        }
    }
    return true;
};

admin.checkFeedNode = function(obj){
    if(obj.appname.value == '' || obj.nodetype.value==''){
        ui.error( L('PUBLIC_APP_WEIBO_ISNULL') );
        return false;
    }
    return true;
};
admin.checkPermNode = function(obj){
    var reg = /\W+/g;
    var appname = $('#form_appname').val();
    var rule = $('#form_rule').val();
    if(reg.test(appname)){
        ui.error( L('PUBLIC_ADMIN_APP_TIPES') ); return false;
    }else{
        if(reg.test(rule)){
            ui.error( L('PUBLIC_ADMIN_OPINT_TIPES') );return false;
        }
    }
    return true;
};

admin.testEmail = function(){
    var email_sendtype = $('#form_email_sendtype').val();
    var email_host = $('#form_email_host').val();
    var email_port = $('#form_email_port').val();
    var email_ssl = $('input:radio[name="email_ssl"]:checked').val();
    var email_account = $('#form_email_account').val();
    var email_password = $('#form_email_password').val();
    var email_sender_name = $('#form_email_sender_name').val();
    var email_sender_email = $('#form_email_sender_email').val();
    var sendto_email = $('#form_email_test').val();
    if ( sendto_email == ''){
    	ui.error('测试邮件地址未填');
    	return;
    }
    var eMailReg = /^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+((.[a-zA-Z0-9_-]{2,3}){1,2})$/;
        if(!eMailReg.test(sendto_email)) {
        ui.error("邮箱格式不正确");
        return false;
    }
    $.post(U('admin/Public/test_email'),
         {email_sendtype:email_sendtype,email_host:email_host,email_port:email_port,email_ssl:email_ssl,email_account:email_account,
          email_password:email_password,email_sender_name:email_sender_name,sendto_email:sendto_email,email_sender_email:email_sender_email},
        function(msg){
        if(msg == 1 ){
            ui.success( L('PUBLIC_TEST_MAIL_SUCCESS') );    
        }else{
            alert(msg);
        }
    });
};


admin.checkProfile = function(form){
    if(form.field_key.value=='' || getLength(form.field_key.value) < 1){
        ui.error( L('PUBLIC_KEY_ISNULL') );return false;
    }
    if(form.field_name.value==''　|| getLength(form.field_name.value) < 1 ){
        ui.error( L('PUBLIC_NAME_ISNULL') );return false;
    }
    if(form.field_type.value==''　|| getLength(form.field_type.value) < 1 ){
        ui.error( '字段类型不能为空' );return false;
    }
    return true;
};

/*** 添加双语内容 ***/
// 跳转到添加语言页面
admin.updateLangContent = function(sid) {
    location.href = U('admin/Config/updateLangContent') + '&sid=' + sid;
};

// 对比语言文件
admin.compareLangFile = function() {
    location.href = U('admin/Config/compareLangFile');
};

// 更新语言文件
admin.updateLang = function(id) {
    id = (id != '') ? id : admin.getChecked().join(',');
    if('undefined' === typeof(id) || id == '') {
        ui.error( '请选择要同步的语言项' );
        return false;
    }
    if(confirm( '确定要同步？' )) {
        $.post(U('admin/Config/updateLang'), {lang_id:id}, function(msg) {
            admin.ajaxReload(msg);
        }, 'json');
    }
};

// 更新后台菜单配置项
admin.updateAdminTab = function(id) {
    id = (id != '') ? id : admin.getChecked().join(',');
    if('undefined' === typeof(id) || id == '') {
        ui.error( '请选择要同步的语言项' );
        return false;
    }

    if(confirm( '确定要同步？' )) {
        $.post(U('admin/Config/doUpdateAdminTab'), {tab_id:id}, function(msg) {
            admin.ajaxReload(msg);
        }, 'json');
    }
};

// 删除语言配置内容
admin.deleteLangContent = function(id) {
    id = (id != '') ? id : admin.getChecked().join(',');
    if('undefined' === typeof(id) || id == '') {
        ui.error( L('PUBLIC_PLEASE_DELTER_TIPES') );
        return false;
    }

    if(confirm( L('PUBLIC_ANSWER_PLEASE_DELETE_TIPES') )) {
        $.post(U('admin/Config/deleteLangContent'), {lang_id:id}, function(msg) {
            admin.ajaxReload(msg);
        }, 'json');
    }
};

//更新wigdet
admin.updateWidget = function(){
    $.post(U('widget/Diy/updateWidget'),{},function(data){
        ui.success(data);
    });
};

admin.configWidget = function(id){
    ui.box.load(U('widget/Diy/config')+'&id='+id,L('PUBLIC_SET_WIDGET'));
};

admin.checkAddArticle = function(form){
    if(form.title.value == '' ||　getLength(form.title.value) < 1 ){
        ui.error( L('PUBLIC_TITLE_ISNULL') );return false;
    }

    if(!admin.checkEditor(Editor_content,form.content)){
        return false;
    }
    return true;
};

admin.checkMessage = function(form){

    if(!admin.checkEditor(Editor_content,form.content)){
        return false;
    }
    return true;
};

admin.checkEditor = function(editor,content){
    
    var html = editor.html();
    content.value =  html;

    var t =$('<div></div>');
    t.html(html);
    
    var imgnums = t.find('img').size();

    html = html.replace(/&nbsp;/g,"").replace(/\s+/g,"").replace(/<.*?>/g,"");

    if(getLength(html)<1 && imgnums <1 ){

        ui.error( L('PUBLIC_INNULL') );
        return false;
    }
    
    return true;
};

admin.delTag = function(obj,tag_id,table,row_id){
    if(confirm(L('PUBLIC_DELETE_TAG_CONFIRM'))){
        $.post(U('admin/Home/deltag'),{tag_id:tag_id,table:table,row_id:row_id},function(msg){
            if(msg.status == 1){
                 ui.success(msg.data);
                $(obj).parent().parent().remove();    
            }else{
                ui.error(msg.data);
            }
            
        },'json');
    }
};

admin.appnav = function(obj,name,url){
    if($(obj).attr('add') == 0){
        window.parent.addTonav(name,url);
        $(obj).html(L('PUBLIC_REMOVE_NAV'));
        $(obj).attr('add',1);
    }else{
        var appname = url.split('/');
        window.parent.removeFromNav(appname[0]);
        $(obj).html(L('PUBLIC_ADD_NAV'));
        $(obj).attr('add',0);
    } 
};


admin.sendEmailList = function(){
    $('#email_msg').html('发送中……');
    $.post(U('admin/Config/dosendEmail'),{},function(msg){
        $('#email_msg').html(msg);
    });
};

/**
 * 认证通过、驳回
 * @param  integer id  认证ID
 * @param  integer status 认证状态
 * @param  string info 认证资料
 * @return void
 */
admin.verify = function(id,status,isgroup,info){
  //  alert(info);exit;
    if("undefined" == typeof(id) || id=='')
        id = admin.getChecked();
    if(id == ''){
        if(status == 1){
            if(isgroup == 6){
                ui.error('请选择要通过认证的企业');
                return false;
            }else{
                ui.error('请选择要通过认证的用户');
                return false;
            }
        }else{
            if(isgroup == 6){
                ui.error('请选择要驳回认证的企业');
                return false;
            }else{
                ui.error('请选择要驳回认证的用户');
                return false;
            }
        }
    }
    if(status == 1){
        ui.box.load(U('admin/User/editVerifyInfo')+'&id='+id+'&status='+status,'编辑认证资料');
    }else{
        $.post(U('admin/User/doVerify'),{id:id,status:status},function(msg){
            admin.ajaxReload(msg);
        },'json');
    }
};

/**
 * 添加认证提交验证
 * @param {[type]} form [description]
 * @return bool
 */
admin.addVerifySubmitCheck = function(form){
    if(!admin.checkAddVerify(form)){
        return false;
    } 
    return true;
};

/**
 * 添加认证验证表单
 * @param  {[type]} form [description]
 * @return bool
 */
admin.checkAddVerify = function(form){
    var Regx1 = /^[0-9]*$/;
    var Regx2 = /^[A-Za-z0-9]*$/;
    var Regx3 = /^[\u4E00-\u9FA5]+$/;

    if(getLength(form.uname.value) < 1){
        ui.error('请选择用户');
        return false;
    }
    if($(":radio:checked").val() == 6){
        if(getLength(form.company.value) < 1){
            ui.error('请输入企业名称');
            return false;
        }
        if(getLength(form.realname.value) < 1){
            ui.error(L('请输入法人姓名'));
            return false;
        }
        if(getLength(form.idcard.value) < 1){
            ui.error(L('请输入营业证号'));
            return false;
        }
        if(getLength(form.phone.value) < 1){
            ui.error(L('请输入联系方式'));
            return false;
        }
        if(getLength(form.info.value) < 1){
            ui.error(L('请输入认证资料'));
            return false;
        }
        if(!Regx3.test($.trim(form.realname.value)) || getLength($.trim(form.realname.value))>10){
            ui.error('请输入正确的法人姓名');
            return false;
        }   
        if(!Regx2.test(form.idcard.value)){
            ui.error('请输入正确的营业证号格式');
            return false;
        }
        // if(getLength(form.info.value) > 70){
        //     ui.error(L('认证资料不能超过140个字符'));
        //     return false;
        // }
    }else{     
        if(getLength(form.realname.value) < 1){
            ui.error(L('请输入真实姓名'));
            return false;
        }
        if(getLength(form.idcard.value) < 1){
            ui.error(L('请输入身份证号'));
            return false;
        }
        if(getLength(form.phone.value) < 1){
            ui.error(L('请输入手机号码'));
            return false;
        }
        if(getLength(form.info.value) < 1){
            ui.error(L('请输入认证资料'));
            return false;
        }
        if(!Regx3.test($.trim(form.realname.value)) || getLength($.trim(form.realname.value))>10){
            ui.error('请输入正确的真实姓名');
            return false;
        }   
        if($.trim(form.idcard.value).length !== 18 || !Regx1.test($.trim(form.idcard.value).substr(0,17)) || !Regx2.test($.trim(form.idcard.value).substr(-1,1))){
            ui.error('请输入正确的身份证号码格式');
            return false;
        }
        if($.trim(form.phone.value).length !== 11 || !Regx1.test($.trim(form.phone.value))){
            ui.error('请输入正确的手机号码格式');
            return false;
        }
        // if(getLength(form.info.value) > 70){
        //     ui.error(L('认证资料不能超过140个字符'));
        //     return false;
        // }
    }  
    return true;
};

/**
 * 后台添加认证单选按钮切换
 * @param  integer value 认证类型
 * @return void
 */
admin.addVerifyConfig = function(value){
    if(value == 6){
        $('#dl_company').show();
        $('#dl_realname dt').html("<font color='red'> * </font>法人姓名：");
        $('#dl_idcard dt').html("<font color='red'> * </font>营业执照号：");
        $('#dl_phone dt').html("<font color='red'> * </font>联系方式：");
    }else{
        $('#dl_company').hide();
        $('#dl_realname dt').html("<font color='red'> * </font>真实姓名：");
        $('#dl_idcard dt').html("<font color='red'> * </font>身份证号码：");
        $('#dl_phone dt').html("<font color='red'> * </font>手机号码：");
    }
    $.post(U('admin/User/getVerifyCategory'),{value:value},function(data){
        if(data){
            $('#dl_user_verified_category_id').css('display','block');
            $('#form_user_verified_category_id').html(data);
        }else{
            $('#dl_user_verified_category_id').css('display','none');
        }
    });
};

/**
 * 后台添加认证单选按钮切换
 * @param  integer value 认证类型
 * @return void
 */
admin.addVerifyConfig = function(value){
    if(value == 6){
        $('#dl_company').show();
        $('#dl_realname dt').html("<font color='red'> * </font>法人姓名：");
        $('#dl_idcard dt').html("<font color='red'> * </font>营业执照号：");
        $('#dl_phone dt').html("<font color='red'> * </font>联系方式：");
    }else{
        $('#dl_company').hide();
        $('#dl_realname dt').html("<font color='red'> * </font>真实姓名：");
        $('#dl_idcard dt').html("<font color='red'> * </font>身份证号码：");
        $('#dl_phone dt').html("<font color='red'> * </font>手机号码：");
    }
    $.post(U('admin/User/getVerifyCategory'),{value:value},function(data){
        if(data){
            $('#dl_user_verified_category_id').css('display','block');
            $('#form_user_verified_category_id').html(data);
        }else{
            $('#form_user_verified_category_id').html('');
            $('#dl_user_verified_category_id').css('display','none');
        }
    });
};

admin.editVerifyConfig = function(value){
    $.post(U('admin/User/getVerifyCategory'),{value:value},function(data){
        if(data){
            $('#dl_user_verified_category_id').css('display','block');
        }else{
            $('#dl_user_verified_category_id').css('display','none');
        }
    });
}

/**
 * 后台编辑认证信息提交验证
 * @param  {[type]} form [description]
 * @return bool
 */
admin.editVerifySubmitCheck = function(form){
    if(!admin.checkEditVerify(form)){
        return false;
    } 
    return true;
};

/**
 * 编辑认证验证表单
 * @param  {[type]} form [description]
 * @return bool
 */
admin.checkEditVerify = function(form){
    var Regx1 = /^[0-9]*$/;
    var Regx2 = /^[A-Za-z0-9]*$/;
    var Regx3 = /^[A-Za-z\u4E00-\u9FA5]+$/;
    
    if($(":radio:checked").val() == 6){
        if(getLength(form.company.value) < 1){
            ui.error('请输入企业名称');
            return false;
        }
        if(getLength(form.realname.value) < 1){
            ui.error(L('请输入法人姓名'));
            return false;
        }
        if(getLength(form.idcard.value) < 1){
            ui.error(L('请输入营业执照号'));
            return false;
        }
        if(getLength(form.phone.value) < 1){
            ui.error(L('请输入联系方式'));
            return false;
        }
        if(getLength(form.reason.value) < 1){
            ui.error(L('请输入认证补充'));
            return false;
        }
        if(getLength(form.info.value) < 1){
            ui.error(L('请输入认证资料'));
            return false;
        }
        if(!Regx3.test($.trim(form.realname.value)) || getLength($.trim(form.realname.value))>10){
            ui.error('请输入正确的法人姓名');
            return false;
        }   
        if(!Regx2.test(form.idcard.value)){
            ui.error('请输入正确的营业证号格式');
            return false;
        }
        // if(getLength(form.info.value) > 70){
        //     ui.error(L('认证资料不能超过140个字符'));
        //     return false;
        // }
    }else{     
        if(getLength(form.realname.value) < 1){
            ui.error(L('请输入真实姓名'));
            return false;
        }
        if(getLength(form.idcard.value) < 1){
            ui.error(L('请输入身份证号'));
            return false;
        }
        if(getLength(form.phone.value) < 1){
            ui.error(L('请输入手机号码'));
            return false;
        }
        if(getLength(form.reason.value) < 1){
            ui.error(L('请输入认证补充'));
            return false;
        }
        if(getLength(form.info.value) < 1){
            ui.error(L('请输入认证资料'));
            return false;
        }
        if(!Regx3.test($.trim(form.realname.value)) || getLength($.trim(form.realname.value))>10){
            ui.error('请输入正确的真实姓名');
            return false;
        }   
        if($.trim(form.idcard.value).length !== 18 || !Regx1.test($.trim(form.idcard.value).substr(0,17)) || !Regx2.test($.trim(form.idcard.value).substr(-1,1))){
            ui.error('请输入正确的身份证号码格式');
            return false;
        }
        if($.trim(form.phone.value).length !== 11 || !Regx1.test($.trim(form.phone.value))){
            ui.error('请输入正确的手机号码格式');
            return false;
        }
        // if(getLength(form.info.value) > 70){
        //     ui.error(L('认证资料不能超过140个字符'));
        //     return false;
        // }
    }  
    return true;
};
// 验证CheckBox选中的个数
admin.checkBoxNums = function(obj, nums) {
    var name = $(obj).attr('name');
    var len = $('#dl_' + name.replace(/\[\]/, '')).find('input:checked').length;
    len > nums && $(obj).attr('checked', false);
    return false;
};

/**
 * 设置话题类型
 * @param integer type 要设置的话题类型 1:推荐  2:精华   3:锁定
 * @param integer topic_id  话题ID 
 * @param integer value 话题现有的类型值，改为相反的。0变为1，1变为0
 */
admin.setTopic = function(type,topic_id,value){
    if(!topic_id){
       var topic_id = admin.getChecked();
    }
    if(topic_id==''){
        ui.error('请选择话题');return false;
    }
    $.post(U('admin/Content/setTopic'),{topic_id:topic_id,type:type,value:value},function(msg){
            admin.ajaxReload(msg);
    },'json');
};

/**
 * 添加/编辑话题验证
 * 
 */
admin.topicCheck = function(form){
    if(getLength(form.topic_name.value) < 1){
        ui.error('请输入话题名称');
        return false;
    }
    if(getLength(form.note.value) < 1){
        ui.error('请输入话题注释');
        return false;
    }
    if(getLength(form.domain.value) > 0){
        var Regx2 = /^[A-Za-z0-9]*$/;
        if(!Regx2.test(form.domain.value)){
            ui.error('请输入正确的话题域名格式');
            return false;
        }
    }
    return true;
};

/**
 * 移除官方用户
 * @param integer id 官方用户列表主键ID
 * @return void
 */
admin.removeOfficialUser = function(id)
{
    // 获取用户ID
    if(typeof id === "undefined") {
        id = admin.getChecked();
        id = id.join(',');
    }
    // 提交操作
    $.post(U('admin/User/doRemoveOfficialUser'), {id:id}, function(msg) {
        if(msg.status == 1) {
            ui.success(msg.data);
            location.href = location.href;
            return false;
        } else {
            ui.error(msg.data);
            return false;
        }
    }, 'json');
    return false;
};
//删除任务
admin.delcustomtask = function (id){
    // 获取任务ID
    if(typeof id === "undefined") {
        id = admin.getChecked();
        id = id.join(',');
    }
    if ( id == '' ){
    	ui.error('请选择删除项');
    	return;
    }
    if ( confirm('删除无法恢复请确认是否删除！') ){
	    // 提交操作
	    $.post(U('admin/Task/doDeleteCustomTask'), {id:id}, function(msg) {
	        if(msg.status == 1) {
	            ui.success(msg.data);
	            location.href = location.href;
	        } else {
	            ui.error(msg.data);
	        }
	    }, 'json');
    }
}
//删除勋章
admin.deletemedal = function (id){
    // 获取勋章ID
    if(typeof id === "undefined") {
        id = admin.getChecked();
        id = id.join(',');
    }
    if ( id == '' ){
    	ui.error('请选择删除项');
    	return;
    }
    if ( confirm('删除无法恢复请确认是否删除！') ){
	    // 提交操作
	    $.post(U('admin/Medal/doDeleteMedal'), {id:id}, function(msg) {
	        if(msg.status == 1) {
	            ui.success(msg.data);
	            location.href = location.href+"&tabHash=customIndex";
	        } else {
	            ui.error(msg.data);
	        }
	    }, 'json');
    }
}
//删除用户勋章
admin.deleteusermedal = function (id){
    // 获取用户勋章ID
    if(typeof id === "undefined") {
        id = admin.getChecked();
        id = id.join(',');
    }
    if ( id == '' ){
    	ui.error('请选择删除项');
    	return;
    }
    if ( confirm('删除无法恢复请确认是否删除！') ){
    	 // 提交操作
        $.post(U('admin/Medal/doDeleteUserMedal'), {id:id}, function(msg) {
            if(msg.status == 1) {
                ui.success(msg.data);
                location.href = location.href;
            } else {
                ui.error(msg.data);
            }
        }, 'json');
    }
}

/**
 * 添加认证分类
 */
admin.addVerifyCategory = function(){
    ui.box.load(U('admin/User/addVerifyCategory'), "添加认证分类");
}

/**
 * 添加认证分类
 */
admin.editVerifyCategory = function(user_verified_category_id){
    ui.box.load(U('admin/User/editVerifyCategory')+'&user_verified_category_id='+user_verified_category_id, "编辑认证分类");
}

/**
 * 删除认证分类
 */
admin.delVerifyCategory = function(user_verified_category_id){
    if(confirm('确定删除此分类吗？')){
        $.post(U('admin/User/delVerifyCategory'), {user_verified_category_id:user_verified_category_id}, function(msg){
              admin.ajaxReload(msg);
        },'json');
    }
}

/**
 * 注册配置验证
 */
admin.checkRegisterConfig = function(){
    if($('#dl_tag_open input:checked').val() == 1){
        var tag_num = $('#form_tag_num').val();
        if(getLength(tag_num) < 1){
            ui.error('请输入允许设置标签数量');
            return false;
        }
        if(isNaN(tag_num)){
            ui.error('允许设置标签数量必须为数字');
            return false;
        }
        if(tag_num < 0){
            ui.error('允许设置标签数量不能小于0');
            return false;
        }
    }
    if(!$('#dl_default_user_group input:checked').val()){
        ui.error('请选择默认用户组');
        return false;
    }
    return true;

}

/**
 * 删除模板操作
 * @param integer id 模板ID
 * @return void
 */
admin.delTemplate = function(id)
{
    // 获取模板ID
    if(typeof id === 'undefined') {
        id = admin.getChecked();
        id = id.join(',');
    }
    // 异步提交，删除操作
    if(confirm('是否删除该模板？')) {
        $.post(U('admin/Content/doDelTemplate'), {id:id}, function(res) {
            if(res.status == 1) {
                ui.success(res.data);
                location.href = location.href;
            } else {
                ui.error(res.data);
            }
        }, 'json');
    }
    return false;
};
/**
 * 认证驳回弹窗
 * @param integer id 驳回ID
 * @return void
 */
admin.getVerifyBox = function (id) {
    if (typeof id === 'undefined') {
        return false;
    }
    ui.box.load(U('admin/User/getVerifyBox') + '&id=' + id, '驳回理由');
    return false;
};

admin.personalRequired = function(obj) {
    if (typeof obj === 'undefined') {
        return false;
    }
    if (parseInt($(obj).val()) === 1) {
        $('#dl_personal_required').show();
        $('#dl_tag_num').show();
    } else {
        $('#dl_personal_required').hide();
        $('#dl_tag_num').hide();
    }
};

admin.interesterRequired = function(obj) {
    if (typeof obj === 'undefined') {
        return false;
    }
    if (parseInt($(obj).val()) === 1) {
        $('#dl_interester_rule').show();
        $('#dl_interester_recommend').show();
    } else {
        $('#dl_interester_rule').hide();
        $('#dl_interester_recommend').hide();
    }
};

admin.registerConfigDefault = function(pValue, iValue) {
    if (pValue == 1) {
        $('#dl_personal_required').show();
        $('#dl_tag_num').show();
    } else if (pValue == 0) {
        $('#dl_personal_required').hide();
        $('#dl_tag_num').hide();
    }

    if (iValue == 1) {
        $('#dl_interester_rule').show();
        $('#dl_interester_recommend').show();
    } else if (iValue == 0) {
        $('#dl_interester_rule').hide();
        $('#dl_interester_recommend').hide();
    }
};

admin.setSensitiveBox = function(id) {
    if (typeof id === 'undefined' || id == '') {
        ui.box.load(U('admin/Config/setSensitiveBox'), '新增敏感词');
    } else {
        ui.box.load(U('admin/Config/setSensitiveBox', ['id='+id]), '编辑敏感词');
    }
    return false;
};

admin.rmSensitive = function(id) {
    if (typeof id === 'undefined' || id == '') {
        return false;
    }
    if (confirm('确定删除该信息')) {
        $.post(U('admin/Config/doRmSensitive'), {id:id}, function(res) {
            if (res.status == 1) {
                ui.success(res.info);
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                ui.error(res.info);
            }
        }, 'json');
    }
    return false;
};
function getChecked() {
    var ids = new Array();
    $.each($('table input:checked'), function(i, n){
        ids.push( $(n).val() );
    });
    return ids;
}
admin.del = function(id){
    var id = id ? id : getChecked();
    id = id.toString();
    if(id=='' || id==0){
        ui.error("请选择要删除的礼物");
        return false;
    }
    if( confirm("是否删除礼物？") ){
      $.post(U('gift/Admin/delete'),{id:id},function(text ){
          if( text == 1 ){
              ui.success( "删除多份礼物成功" );
              var id_list = id.split( ',' );   
              for (var j=0 ; j< id_list.length ; j++   ){
                  $('#tr'+id_list[j]).remove(); 
              }
          }else if( text == 2 ){
              ui.success( "删除成功" );
              $('#tr'+id).remove();
          }else{
              ui.error( "删除失败" );
          }
      });
    }
};
admin.forbid = function(id){
    var id = id ? id : getChecked();
    id = id.toString();
    if(id=='' || id==0){
        ui.error("请选择要禁用的礼物");
        return false;
    }
    if( confirm( "是否禁用礼物" ) ){
      $.post(U('gift/Admin/forbid'),{id:id},function( text ){
         if( text == 1 ){
             ui.success( "禁用多份礼物成功" );
              var id_list = id.split( ',' );   
              for (var j=0 ; j< id_list.length ; j++   ){
                  $('#status'+id_list[j]).html('<img src="'+SITE_URL+'/apps/'+APPNAME+'/_static/images/locked.gif" width="20" height="20" border="0" alt="正常">');
                  $('#button'+id_list[j]).html('<a href="javascript:void(0);" onclick="admin.resume('+id_list[j]+')">启用</a> ');
              }
         }else if( text == 2 ){
             ui.success( "禁用成功" );
             $('#status'+id).html('<img src="'+SITE_URL+'/apps/'+APPNAME+'/_static/images/locked.gif" width="20" height="20" border="0" alt="正常">');
             $('#button'+id).html('<a href="javascript:void(0);" onclick="admin.resume('+id+')">启用</a> ');
         }else{
             ui.error( "禁用失败或已经禁用" );
         }
      });
    }
};
admin.resume = function(id){
    var id = id ? id : getChecked();
    id = id.toString();
    if(id=='' || id==0){
        ui.error("请选择要启用的礼物");
        return false;
    }
    if( confirm( "是否启用礼物" ) ){
      $.post(U('gift/Admin/resume'),{id:id},function( text ){
         if( text == 1 ){
             ui.success( "启用多份礼物成功" );
              var id_list = id.split( ',' );   
              for (var j=0 ; j< id_list.length ; j++   ){
                  $('#status'+id_list[j]).html('<img src="'+SITE_URL+'/apps/'+APPNAME+'/_static/images/ok.gif" width="20" height="20" border="0" alt="正常">');
                  $('#button'+id_list[j]).html('<a href="javascript:void(0);" onclick="admin.forbid('+id_list[j]+')">禁用</a> ');
              }
         }else if( text == 2 ){
             ui.success( "启用成功" );
             $('#status'+id).html('<img src="'+SITE_URL+'/apps/'+APPNAME+'/_static/images/ok.gif" width="20" height="20" border="0" alt="正常">');
             $('#button'+id).html('<a href="javascript:void(0);" onclick="admin.forbid('+id+')">禁用</a> ');
         }else{
             ui.error( "启用失败或已非禁用" );
         }
      });
    }
};
admin.edit_tab = function(action,id){
    var title = action+"礼物";
    ui.box.load(U('gift/Admin/edit_gift_tab')+ '&id='+id,title+'信息');
};
