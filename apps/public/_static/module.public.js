/**
 * public app下面用到的一些时间监听
 * 
 */

var updateAppUsed = function(app_id,flag){
	var countObj = M.nodes.events['app_used'];
	for(var i in countObj){
		var _wC = countObj[i] ;
		var args = M.getEventArgs(_wC);
		if(args.app_id == app_id){
			if(flag>0){
				_wC.innerHTML = parseInt(_wC.innerHTML,10)+1;
			}else{
				_wC.innerHTML = parseInt(_wC.innerHTML,10)-1;
			}
		}	
	}
	return false;
}

var uninstall_app = function(obj){
	var args = M.getEventArgs(obj);
	var _uninstall_app = function(){
		$.post(U('public/App/uninstall'),{app_id:args.app_id,rndtime : new Date().getTime()},function(msg){
			if(msg.status == 1){
				obj.className = 'btn-green-small right';
				obj.innerHTML = '<span>'+L('PUBLIC_APP_USE')+'</span>';
				$(obj).attr('event-node','install_app');
				M(obj);
				if($('#leftApp'+args.app_id).length>0){
					$('#leftApp'+args.app_id).hide();
				}
				updateAppUsed(args.app_id,-1);
				ui.success(msg.data);
			}else{
				ui.error(msg.data);
			}
		},'json');
	}
	ui.confirm(obj,L('PUBLIC_ANSWER_STOP_APP')+'"'+args.app_alias+'"?',_uninstall_app);
}

var install_app = function(obj){
	var args = M.getEventArgs(obj);
	$.post(U('public/App/install'),{app_id:args.app_id,rndtime : new Date().getTime()},function(msg){
		if(msg.status == 1){
			obj.className = 'btn-gray right';
			obj.innerHTML = '<span>'+L('PUBLIC_APP_STOP')+'</span>';
			$(obj).attr('event-node','uninstall_app');
			updateAppUsed(args.app_id,1);
			M(obj);
			ui.success(msg.data);
		}else{
			ui.error(msg.data);
		}
	},'json');
	
}
//监听控制器
M.addEventFns({
	uninstall_app:{// 卸载
		click:function(){
			uninstall_app(this);
		}
	},
	install_app:{	//安装
		click:function(){
			install_app(this);
		}
	},
	app_used:{}
});