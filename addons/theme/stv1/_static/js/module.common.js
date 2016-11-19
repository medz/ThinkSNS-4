/**
 * 全站供用的Js监听
 * @author jason <yangjs17@yeah.net> 
 * @version TS3.0
 */
M.addModelFns({
	invite_colleague_form: {
		callback: function( txt ) {
			ui.success( txt.info );
			ui.box.close();
		}
	},
	drop_menu_list: {
		load: function() {
			var first = true;
			var parentModel = this.parentNode,
				list = this;

			$('body').bind('click',function(event){
				event = event || window.event;
				var obj = "undefined" != typeof(event.srcElement) ? event.srcElement : event.target;
				var wrap = ['search', 'account', 'application'];
				for (var i in wrap) {
					var dom = $(obj).parents('li[model-node="' + wrap[i] + '"]').get(0);
					var hidden = $('li[model-node="' + wrap[i] + '"]').find('div[model-node="drop_menu_list"]').is(':hidden');
					if (dom == undefined && !hidden) {
						var btn = $('li[model-node="' + wrap[i] + '"]')[0];
						if (typeof btn === 'undefined') {
							return;
						}
						var className = btn.className;

						btn.className = className.replace(/(\s+drop)+(\s+|$)/g, "");

						if (wrap[i] == 'search') {
							$('li[model-node="' + wrap[i] + '"]').find('div[model-node="drop_menu_list"]').stop().animate({width:0}, 'normal', function() {
								$(this)[0].style.display = 'none';
							});
						}
					}
				}
			});

			if ($(parentModel).attr('model-node') === 'search') {
				M.addListener(parentModel, {
					click: function(event) {
						event = event || window.event;
						var obj = "undefined" != typeof(event.srcElement) ? event.srcElement : event.target;
						if ($(obj).attr('rel') !== 'search-btn') {
							return false;
						}
						var style = list.style.display;
						var className = this.className;
						if (style === '' || style === 'none') {
							this.className = [className, " drop"].join("");
							list.style.display = 'block';
							$(list).stop().animate({width:178}, 'normal');
							$('#search_input').focus();
						} else {
							this.className = className.replace(/(\s+drop)+(\s+|$)/g, "");
							$(list).stop().animate({width:0}, 'normal', function() {
								list.style.display = 'none';	
							});
						}
					}
				});
			} else if ($(parentModel).attr('model-node') === 'account') {
				M.addListener(parentModel, {
					click: function(event) {
						event = event || window.event;
						var obj = "undefined" != typeof(event.srcElement) ? event.srcElement : event.target;
						if ($(obj).attr('rel') !== 'account-btn') {
							return true;
						}
						var style = list.style.display;
						var className = this.className;
						if (style === '' || style === 'none') {
							this.className = [className, " drop"].join("");
							$(list).show();
							$(list).stop().animate({opacity:1},'fast');
						} else {
							if ($(obj).attr('rel') != 'all') {
								this.className = className.replace(/(\s+drop)+(\s+|$)/g, "");
								$(list).stop().animate({opacity:0},'fast',function(){
									$(list).hide();
								});
							}
						}
					},
					mouseover: function(event) {
						event = event || window.event;
						var obj = "undefined" != typeof(event.srcElement) ? event.srcElement : event.target;
						if ($(obj).attr('rel') !== 'account-btn') {
							return true;
						}
						var style = list.style.display;
						var className = this.className;
						className = className.replace(/(\s+drop)+(\s+|$)/g, "");
						this.className = [className, " drop"].join("");
						$(list).show();
						$(list).stop().animate({opacity:1},'fast');
					},
					mouseleave: function(event){
						var className = this.className;
						this.className = className.replace(/(\s+drop)+(\s+|$)/g, "");
						$(list).stop().animate({opacity:0},'fast',function(){
							$(list).hide();
						});
					}
				});
			} else if ($(parentModel).attr('model-node') === 'application') {
				M.addListener(parentModel, {
					click: function(event) {
						event = event || window.event;
						var obj = "undefined" != typeof(event.srcElement) ? event.srcElement : event.target;
						if ($(obj).attr('rel') !== 'application-btn') {
							return true;
						}
						var style = list.style.display;
						var className = this.className;
						if (style === '' || style === 'none') {
							this.className = [className, " drop"].join("");
							$(list).show();
							$(list).stop().animate({opacity:1},'fast');
						} else {
							if ($(obj).attr('rel') != 'all') {
								this.className = className.replace(/(\s+drop)+(\s+|$)/g, "");
								$(list).stop().animate({opacity:0},'fast',function(){
									$(list).hide();
								});
							}
						}
					},
					mouseover: function(event) {
						event = event || window.event;
						var obj = "undefined" != typeof(event.srcElement) ? event.srcElement : event.target;
						if ($(obj).attr('rel') !== 'application-btn') {
							return true;
						}
						var style = list.style.display;
						var className = this.className;
						className = className.replace(/(\s+drop)+(\s+|$)/g, "");
						this.className = [className, " drop"].join("");
						$(list).show();
						$(list).stop().animate({opacity:1},'fast');
					},
					mouseleave: function(event){
						var className = this.className;
						this.className = className.replace(/(\s+drop)+(\s+|$)/g, "");
						$(list).stop().animate({opacity:0},'fast',function(){
							$(list).hide();
						});
					}
				});
			} else {
				// 鼠标进入父Model，显示Menu；反之，则隐藏Menu。
				M.addListener( parentModel, {
					mouseenter: function() {
						var className = this.className;
						this.className = [ className, " drop" ].join( "" );
						list.style.display = "block";
					},
					mouseleave: function() {
						var className = this.className;
						this.className = className.replace(/(\s+drop)+(\s+|$)/g, "");
						list.style.display = "none";
					}
				});
			}
		}
	},
	search_icon:{
		mouseleave:function(){
			// $('.search_footer').attr('ison','no');
			// setTimeout(function(){
			// 	if($('.search_footer').attr('ison')=='no'){
			// 		$('.search_footer').hide();
			// 	}
			// },150);
		},
		click:function(){
			if($('.search_footer').attr('ison') == 'yes'){
				$('.search_footer').attr('ison','no');
				$('.search_footer').hide();
				return true;
			}
			$('.search_footer').attr('ison','yes');
			$('.search_footer').show();
		}
	},
	search_menu_footer:{
	  	click:function(){
	  		var offset = $(this).offset();
	  		$('#search_menu').css({'left':offset.left+'px','top':offset.top-35+'px','width':'81px'}).show();
	  		$('#search_menu').attr('ison','yes');
		   },
	  	mouseleave:function(){
	  		//setTimeout(core.search.hideMenu,300);
	  	},
	  	blur:function(){
  			core.search.dohide();
  		}
  	},
  	search_menu:{
	  	click:function(){
	  		var offset = $(this).offset();
	  		$('#search_menu').css({'left':offset.left+'px','top':offset.top+$(this).height()+12+'px','width':'81px'}).show();
	  		$('#search_menu').attr('ison','yes');
		   },
	  	mouseleave:function(){
	  		setTimeout(core.search.hideMenu,300);
	  	},
	  	blur:function(){
	  		core.search.dohide();
	  	}
	  },
	search_menu_ul:{
	  	mouseleave:function(){
	  		$('.search_footer').attr('ison','yes');
	  		core.search.dohide();
	  	},
	  	mouseenter:function(){
	  		core.search.doshow(); 
	  	}
	 },
	search_footer:{
		mouseleave:function(){
			// $(this).attr('ison','no');
			// var _this = this;
			// setTimeout(function(){
			// 	$(_this).hide();
			// },'250');
		},
		mouseenter:function(){
			$(this).attr('ison','yes');
		}
	},
	drop_search:{
		load:function(){
			var _this = this;
			core.plugInit('search');
			// $(this.childEvents['searchKey'][0]).click(function(){
			// 	core.search.searchInit(this);
			// });	
			$(this.childEvents['searchKey'][0]).focus(function(){
				core.search.searchInit(this);
			});	
		}
	},
	wigdet_setform:{
		callback:function(data){
			core.widget.afterSet(data);
		}
	},
	diy_widget:{
		load:function(data){
			var child = this.childModels['widget_box'];
			var _this = this;
			var args  = M.getModelArgs(this);
			core.plugFunc('widget',function(){
				//拖动处理
				core.loadFile(THEME_URL+'/js/ui.sortable.js',function(){
					$(_this).sortable({
						items: '.ui-state-disabled',
						placeholder: 'ui-selected',
						revert: 0.01,
						helper: 'clone', 
						update:function(){
							core.widget.dosort(args,_this);
						}
					});
					$(child).disableSelection();	
				});
			});
		}
	}
});
M.addEventFns({
	widget_toggle:{
		click:function(){
			$(this.parentModel.childModels['widget_child'][0]).toggle('500');
		}
	},
	widget_setup:{
		click:function(){
			$(this.parentModel.childModels['wigdet_setbox'][0]).toggle('500');	
		}
	},
	widget_cancel_set:{
		click:function(){
			$(this.parentModel).hide('500');
		}
	},
	widget_close:{
		click:function(){
			var args = M.getModelArgs(this.parentModel);
			core.widget.removeWidget(this,args,this.parentModel);
		}
	},
	widget_add:{
		click:function(){
			var args = M.getEventArgs(this);
			core.widget.addWidget(args);
		}
	},
	invite_colleague: {
		click: function() {
			ui.box.load( this.href, L('PUBLIC_INVITE_COLLEAGUE') );
			return false;
		}
	},	
	invite_addemail:{
		click: function() {
			var input1 = document.getElementById("email_input").value,
				$email_input = $("#email_input"),
				dInput = this.parentModel.childEvents["email"][0],
				dInputClone = dInput.cloneNode( true );

			dInputClone.value = "";
			$email_input.append( dInputClone );
			M( dInputClone );
			return false;
		}
	},
	face_card:{
		load:function(){
			//载入小名片js
			core.plugInit('facecard'); //只初始化那个框体
		},
		mouseenter:function(){
			var uid = $(this).attr('uid');
			if(MID<1 || (MID == uid) || (UID ==uid)){
				return false;
			}
			var obj = $(this);
			//setTimeout(function(){
			if("undefined" == typeof(core.facecard)){
				core.plugFunc('facecard',function(){
					core.facecard.show(obj,uid);
				})
			}else{
				core.facecard.show(obj,uid);
			}
			//},'250');
			
		},
		mouseleave:function(){
			if(MID<1){
				return false;
			}
			core.facecard.hide();
		},
		blur:function(){
			core.facecard.hide();	
		}
	},
	//个人信息
	more_person_info: {
		click: function() {
			var li;

			li = this.parentNode;
			li.style.display = "block";
			
			if($(this).attr('rel')=='hide'){
				var _display = 'block';
				$(this).attr('rel','show');
				$('.mod-person .person-info a').text(L('PUBLIC_PUT')+"↑")
			}else{
				var _display = 'none';
				$(this).attr('rel','hide');
				$('.mod-person .person-info a').text(L('PUBLIC_OPEN_MORE')+"↓")
			}
			
			while ( li = li.nextSibling ) {
				( "LI" === li.tagName ) && ( li.style.display = _display );

			}
			
			return false;
		}
	},
	ico_wallet:{//积分
		mouseenter:function(){
			this._model.style.display = 'block';
		},
		mouseleave:function(){
			this._model.style.display = 'none';
		},
		load:function(){
			var _model = M.getModels('layer_wallet');
			this._model = _model[0];
		}
	},
	ico_level:{//等级
		mouseenter:function(){
			this._model.style.display = 'block';
		},
		mouseleave:function(){
			this._model.style.display = 'none';
		},
		load:function(){
			var _model = M.getModels('layer_level');
			this._model = _model[0];
		}
	},
	open_share:{
		click : function(){
			if(MID == 0){
				ui.quicklogin();
				return;
			}
			var _this = this;
			core.plugFunc('bdshare', function(){
				core.bdshare.feedlist(_this);
			});
		}
	},
	share:{//分享操作
		click : function(){
			var attrs = M.getEventArgs(this);
			//alert(typeof(attrs));exit;
			// if(attrs.appname == 'weiba' && attrs.feedtype == 'weiba_post'){
			// 	var sid = attrs.curid;
			// }else{
			var sid = attrs.sid;
			//}
			var stable = attrs.stable;
			var initHTML = attrs.initHTML;
			var curtable =attrs.curtable;
			var curid = attrs.curid;
			var appname = attrs.appname;
			var cancomment = attrs.cancomment;
			var is_repost = attrs.is_repost;
			share(sid,stable,initHTML,curid,curtable,appname,cancomment,is_repost);
			return false;
		}
	},
	share_to_feed:{//分享操作
		load: function () {
			var attrs = M.getEventArgs(this);
			if (attrs.isLoad == 1) {
				var initHTML = attrs.initHTML;
				var attachId = attrs.attachId;
				var from = attrs.from;
				var appname = attrs.appname;
				var source_url = attrs.url;
				var url = U('public/Share/shareToFeed')+'&initHTML='+initHTML+'&attachId='+attachId+'&from='+from+'&appname='+appname+'&source_url='+source_url;
				ui.box.load(url,'分享');
			}
			return false;
		},
		click : function(){
			var attrs =M.getEventArgs(this);
			var initHTML = attrs.initHTML;
			var attachId = attrs.attachId;
			var from = attrs.from;
			var appname = attrs.appname;
			var source_url = attrs.url;
			var url = U('public/Share/shareToFeed')+'&initHTML='+initHTML+'&attachId='+attachId+'&from='+from+'&appname='+appname+'&source_url='+source_url;
			ui.box.load(url,'分享');
			return false;
		}
	},
	setremark:{	//设置备注
		click:function(){
			var remark = $(this).attr('remark');
			var uid = $(this).attr('uid');
			ui.box.load(U('widget/Remark/edit')+'&remark='+remark+'&uid='+uid,L('PUBLIC_EDIT_FOLLWING'));
		}
	},
	/**
	 * 添加关注
	 * @type {Object}
	 */
	doFollowSpace: {
		click: function() {
			followSpace.doFollow(this);
			return false;
		},
		load: function() {
			followSpace.createBtn(this);
		}
	},
	unFollowSpace: {
		click: function() {
			followSpace.unFollow( this );
			return false;
		},
		load: function() {
			followSpace.createBtn( this );
		}
	},
	setFollowGroup:{
		click:function(){
			var args = M.getEventArgs(this);
			followSpace.setFollowGroup(this,args.fid);
		}
	},
	/**
	 * 添加关注
	 * @type {Object}
	 */
	doFollow: {
		click: function() {
			follow.doFollow(this);
			return false;
		},
		load: function() {
			follow.createBtn(this);
		}
	},
	unFollow: {
		click: function() {
			follow.unFollow( this );
			return false;
		},
		load: function() {
			follow.createBtn( this );
		}
	},
	setFollowGroup:{
		click:function(){
			var args = M.getEventArgs(this);
			follow.setFollowGroup(this,args.fid);
		}
	},
	follow_check: {
		click: function( e ) {
			var check = this.getElementsByTagName( "input" )[0];
			setTimeout( function() {
				check.checked = !check.checked;
				check = undefined;
			}, 1);
			return false;
		}
	},
	/**
	 * 添加关注
	 * @type {Object}
	 */
	newDoFollow: {
		click: function() {
			NewFollow.doFollow(this);
			return false;
		}
	},
	setFollowGroup:{
		click:function(){v
			var args = M.getEventArgs(this);
			NewFollow.setFollowGroup(this,args.fid);
		}
	},
	follow_check: {
		click: function( e ) {
			var check = this.getElementsByTagName( "input" )[0];
			setTimeout( function() {
				check.checked = !check.checked;
				check = undefined;
			}, 1);
			return false;
		}
	},
	
	/**
	 * 添加联盟
	 * @type {Object}
	 */
	doUnion: {
		click: function() {
			union.doUnion(this);
			return false;
		},
		load: function() {
			union.createBtn(this);
		}
	},
	unUnion: {
		click: function() {
			union.unUnion( this );
			return false;
		},
		load: function() {
			union.createBtn( this );
		}
	},	
	/**
	 * 微吧加入
	 * @type {Object}
	 */
	doFollowWeiba: {
		click: function() {
			followweiba.doFollow(this);
			return false;
		},
		load: function() {
			followweiba.createBtn(this);
		}
	},
	unFollowWeiba: {
		click: function() {
			followweiba.unFollow( this );
			return false;
		},
		load: function() {
			followweiba.createBtn( this );
		}
	},
	setFollowGroup:{
		click:function(){
			var args = M.getEventArgs(this);
			follow.setFollowGroup(this,args.fid);
		}
	},
	follow_check: {
		click: function( e ) {
			var check = this.getElementsByTagName( "input" )[0];
			setTimeout( function() {
				check.checked = !check.checked;
				check = undefined;
			}, 1);
			return false;
		}
	},
	comment:{	
		click:function(){	//点击评论的时候
			var attrs = M.getEventArgs(this);
			var comment_list = this.parentModel.childModels['comment_detail'][0];
			attrs.sourceObject = this;
//			if("undefined" == typeof(core.comment)){
//				core.plugInit('comment',attrs,comment_list);
//				core.setTimeout("core.comment.display()",150);
//			}else{
		
			core.comment.init(attrs,comment_list);
			core.comment.display();
//			}
			return false;
		}
	},
	reply_comment:{	//点某条回复
		click:function(){
			var attrs = M.getEventArgs(this);
			var comment_list = this.parentModel.parentModel;
			var docomment = comment_list.childModels['comment_textarea'][0].childEvents['do_comment'][0];
			$(docomment).attr('to_comment_id',attrs.to_comment_id);
			$(docomment).attr('to_uid',attrs.to_uid);
			$(docomment).attr('to_comment_uname',attrs.to_comment_uname);
			core.plugFunc('comment',function(){
				core.comment.init(attrs,comment_list);
				core.comment.initReply();
			});
			//core.plugInit('comment',attrs,comment_list);
			//core.setTimeout("core.comment.initReply()",150);
		}
	},
	comment_del:{
		click:function(){
			var attrs = M.getEventArgs(this);
			// 添加删除后的楼层统计数变化
			$(this.parentModel).fadeOut('normal', function () {
				var $commentList = $(this).parent();
				if ($commentList.length > 0) {
					// 获取分享ID
					var wid = parseInt($commentList.attr('id').split('_')[1]);
					var $commentListVisible = $commentList.find('dl:visible');
					var len = parseInt($commentListVisible.eq(0).find('span.floor').html());
					$commentListVisible.each(function (i, n) {
						$(this).find('span.floor').html((len - i)+'楼');
					});
				}
			});
			if("undefined"==typeof(core.comment)){
				core.plugFunc('comment',function(){
					core.comment.delComment(attrs.comment_id);
				});
			}else{
				core.comment.delComment(attrs.comment_id);	
			}
		}
	},
	do_comment:{	//回复操作
		click:function(){
			if ( this.noreply == 1 ){
				return;
			}
			var attrs = M.getEventArgs(this);
			attrs.to_comment_id = $(this).attr('to_comment_id');
			attrs.to_uid = $(this).attr('to_uid');
			attrs.to_comment_uname = $(this).attr('to_comment_uname');
			attrs.addToEnd = $(this).attr('addtoend');
			
			var comment_list = this.parentModel.parentModel;
			core.comment.init(attrs,comment_list);

			var _this = this;
			var after = function(){
				$(_this).attr('to_uid','0');
				$(_this).attr('to_comment_id','0');
				$(_this).attr('to_comment_uname','');
				if(attrs.closeBox == 1){
					ui.box.close();
					ui.success( L('PUBLIC_CENTSUCCESS') );
				}
			}
			core.comment.addComment(after,this);
			this.noreply = 1;
			setTimeout(function (){
				_this.noreply = 0;
			},5000);
		},
		load:function(){
			var attrs = M.getEventArgs(this);
			attrs.to_comment_id = $(this).attr('to_comment_id');
			attrs.to_uid = $(this).attr('to_uid');
			attrs.to_comment_uname = $(this).attr('to_comment_uname');
			attrs.addToEnd = $(this).attr('addtoend');
			attrs.talkbox = $(this).attr('talkbox');
			var comment_list = this.parentModel.parentModel;
			core.plugInit('comment',attrs,comment_list);
		}
	},
	comment_insert_face:{
		click:function(){
			var target = this.parentModel.childModels["mini_editor"][0];		
			var _faceDiv = this.parentModel.childModels['faceDiv'][0];
			if($(target).find('textarea').size() == 0){
				core.plugInit('face',this,$(target).find('input:eq(0)'),_faceDiv);
			}else{
				core.plugInit('face',this,$(target).find('textarea'),_faceDiv);
			}
		}
	},
	//消息弹出层评论回复
	messasge_reply_comment:{
		click:function(){		
			var attrs = M.getEventArgs(this);
			var comment_list = this.parentModel.childModels['comment_detail'][0];
			attrs.sourceObject = this;
			core.comment.init(attrs,comment_list);
			core.comment.display(function(type){
				if(type == 'show'){
					core.comment.initReply();
				}
			});
		}
	},
	showCategory:{
		click:function(){
			var attrs = M.getEventArgs(this);
			//显示分类
			var obj = this;
			core.plugFunc('category',function(){
				core.category.loadSelect(obj,attrs.model_name,attrs.app_name,attrs.method,attrs.id,attrs.inputname,attrs.callback);
			});
		}
	},
	show_url_detail: {	//链接地址详情显示
		mouseover: function(){
			$(this).parent().find('.url-detail').show();
		},
		mouseout: function(){
			$(this).parent().find('.url-detail').hide();
		}
	}
});

//分享
var share=function(sid,stable,initHTML,curid,curtable,appname,cancomment,is_repost){
	if(MID == 0){
				ui.quicklogin();
				return;
	}
	if("undefined" == typeof(cancomment)){
		cancomment = 0;
	}
	var url = U('public/Share/index')+'&sid='+sid+'&stable='+stable+'&curid='+curid+'&curtable='+curtable+'&appname='+appname+'&initHTML='+initHTML+'&cancomment='+cancomment+'&is_repost='+is_repost;
	ui.box.load(url,L('PUBLIC_SHARE'),function(){
		$('#at-view').hide();
		var share_id="feed"+curid;
		window.location.hash=share_id;
	});
	return false;
};

/**
 * 关注操作Js类
 * @type {Object}
 */
var follow = {
	// 按钮样式
	btnClass: {
		doFollow: "btns-red",
		unFollow: "btns-red",		
		haveFollow: "btns-gray",
		eachFollow: "btns-gray"
	},
	// 按钮图标
	flagClass: {
		doFollow: "ico-add",
		unFollow: "ico-minus",
		haveFollow: "ico-already",
		eachFollow: "ico-connect"
	},
	// 按钮文字
	btnText: {
		doFollow: '关注',
		unFollow: '取消关注',
		haveFollow: '已关注',
		eachFollow: '相互关注'
	},
	/**
	 * 创建关注按钮
	 * @param object node 按钮节点对象
	 * @param string btnType 按钮类型，4种
	 * @return void
	 */
	createBtn: function(node, btnType) {
		var args = M.getEventArgs(node);
		var btnType = (0 == args.following) ? "doFollow" : ((0 == args.follower) ? "haveFollow" : "eachFollow");
		var btnClass = this.btnClass[btnType];
		var flagClass = this.flagClass[btnType];
		var btnText = this.btnText[btnType];
		var btnHTML = ['<span><b class="', flagClass, '"></b>', btnText, '</span>'].join( "" );
		// 按钮节点添加HTML与样式
		node.innerHTML = btnHTML;
		node.className = btnClass;
		// 选择按钮类型
		switch(btnType) {
			case "haveFollow":
			case "eachFollow":
				$(node).bind({
					mouseover: function() {
						var b = this.getElementsByTagName( "b" )[0];
						var text = b.nextSibling;
						this.className = follow.btnClass.unFollow;
						b.className = follow.flagClass.unFollow;
						text.nodeValue = follow.btnText.unFollow;
					},
					mouseout: function() {
						var b = this.getElementsByTagName( "b" )[0];
						var text = b.nextSibling;
						this.className = btnClass;
						b.className = flagClass;
						text.nodeValue = btnText;
					}
				});
				break;
			default:
				$(node).unbind('mouseover');
				$(node).unbind('mouseout');
		}
	},
	/**
	 * 添加关注操作
	 * @param object node 关注按钮的DOM对象
	 * @return void
	 */
	doFollow: function(node) {
		if(MID == 0){
			ui.quicklogin();
			return;
		}
		var _this = this;
		var args = M.getEventArgs(node);
		var url = node.getAttribute("href") || U('public/Follow/doFollow');

		$.post(url, {fid:args.uid}, function(txt) {
			if(1 == txt.status ) {
				if("undefined" != typeof(core.facecard)){
					core.facecard.deleteUser(args.uid);
				}
				_this.updateFollowCount(1);
				updateUserData('follower_count', 1, args.uid);
				if("following_right" == args.refer) {
					var item = node.parentModel;
					// item.parentNode.removeChild(item);
					$(item).fadeOut('normal', function() {
						$(this).remove();
					});
					$.post(U('widget/RelatedUser/changeRelate'), {uid:args.uid, limit:1}, function(msg) {
						var _model = M.getModels("related_ul_user");
						$(_model[0]).append(msg);
						M(_model[0]);
					}, 'json');
					ui.success("关注成功");
				} else {
					node.setAttribute("event-node", "unFollow");
					node.setAttribute("href", [U('public/Follow/unFollow'), '&fid=', args.uid].join(""));
					M.setEventArgs(node, ["uid=", args.uid, "&uname=", args.uname, "&following=", txt.data.following, "&follower=", txt.data.follower].join(""));
					M.removeListener(node);
					M(node);
					ui.success("关注成功");
					//followGroupSelectorBox(args.uid, args.isrefresh);
				}
			} else {
				ui.error(txt.info);
			}
		}, 'json');
	},
	/**
	 * 选择关注分组下拉窗
	 * @param object node 关注按钮的DOM对象
	 * @param integer fid 关注人ID
	 * @return void
	 */
	setFollowGroup: function(node, fid) {
		var url = U('public/FollowGroup/selectorBox')+'&fid='+fid;
		ui.box.load(url, L('PUBLIC_SET_GROUP'));	
	},
	/**
	 * 取消关注操作
	 * @param object node 关注按钮的DOM对象
	 * @return void
	 */
	unFollow: function(node) {
		var _this = this;
		var args = M.getEventArgs(node);
		var url = node.getAttribute( "href" ) || U('public/Follow/unFollow');

		// 取消关注操作
		$.post(url, {fid:args.uid}, function(txt) {
			if ( 1 == txt.status ) {
				if("undefined" != typeof(core.facecard) ){
					core.facecard.deleteUser(args.uid);
				}
				if ( "following_list" == args.refer ) {
					var item = node.parentModel;
					// 移除
					item.parentNode.removeChild( item );
				} else {					
					node.setAttribute( "event-node", "doFollow" );
					node.setAttribute( "href", [U('public/Follow/doFollow'), '&fid=', args.uid].join( "" ) );
					M.setEventArgs( node, ["uid=", args.uid, "&uname=", args.uname, "&following=", txt.data.following, "&follower=", txt.data.follower].join( "" ) );
					M.removeListener( node );
					M( node );
				}
				_this.updateFollowCount( - 1 );
				updateUserData('follower_count', -1, args.uid);
				ui.success("取消成功");
				
			} else {
				ui.error( txt.info );
			}
		}, 'json');
	},
	/**
	 * 更新关注数目
	 * @param integer num 添加的数值
	 * @return void
	 */
	updateFollowCount: function(num) {
		var l;
		var following_count = M.getEvents("following_count");
		if(following_count) {
			l = following_count.length;
			while(l-- > 0) {
				following_count[l].innerHTML = parseInt(following_count[l].innerHTML) + num;
			}
		}
	}
};

/**
 * 关注操作Js类
 * @type {Object}
 */
var followSpace = {
	// 按钮样式
	btnClass: {
		//doFollow: "btns-red",
		doFollow: "pay-attention",
		unFollow: "pay-attention-hover",		
		haveFollow: "pay-attention",
		eachFollow: "pay-attention"
	},
	// 按钮图标
	flagClass: {
		//doFollow: "ico-add-black",
		doFollow: "i-pay-attention",
		unFollow: "ico-minus",
		haveFollow: "ico-already",
		eachFollow: "ico-connect"
	},
	// 按钮文字
	btnText: {
		doFollow: '关注',
		unFollow: '取消关注',
		haveFollow: '已关注',
		eachFollow: '相互关注'
	},
	/**
	 * 创建关注按钮
	 * @param object node 按钮节点对象
	 * @param string btnType 按钮类型，4种
	 * @return void
	 */
	createBtn: function(node, btnType) {
		var args = M.getEventArgs(node);
		var btnType = (0 == args.following) ? "doFollow" : ((0 == args.follower) ? "haveFollow" : "eachFollow");
		var btnClass = this.btnClass[btnType];
		var flagClass = this.flagClass[btnType];
		var btnText = this.btnText[btnType];
		var btnHTML = ['<span><b class="', flagClass, '"></b>', btnText, '</span>'].join( "" );
		// 按钮节点添加HTML与样式
		node.innerHTML = btnHTML;
		node.className = btnClass;
		// 选择按钮类型
		switch(btnType) {
			case "haveFollow":
			case "eachFollow":
				$(node).bind({
					mouseover: function() {
						var b = this.getElementsByTagName( "b" )[0];
						var text = b.nextSibling;
						this.className = followSpace.btnClass.unFollow;
						b.className = followSpace.flagClass.unFollow;
						text.nodeValue = followSpace.btnText.unFollow;
					},
					mouseout: function() {
						var b = this.getElementsByTagName( "b" )[0];
						var text = b.nextSibling;
						this.className = btnClass;
						b.className = flagClass;
						text.nodeValue = btnText;
					}
				});
				break;
			default:
				$(node).unbind('mouseover');
				$(node).unbind('mouseout');
		}
	},
	/**
	 * 添加关注操作
	 * @param object node 关注按钮的DOM对象
	 * @return void
	 */
	doFollow: function(node) {
		if(MID == 0){
			ui.quicklogin();
			return;
		}
		var _this = this;
		var args = M.getEventArgs(node);
		var url = node.getAttribute("href") || U('public/Follow/doFollow');
		$.post(url, {fid:args.uid}, function(txt) {
			if(1 == txt.status ) {
				if("undefined" != typeof(core.facecard)){
					core.facecard.deleteUser(args.uid);
				}
				node.setAttribute("event-node", "unFollowSpace");
				node.setAttribute("href", [U('public/Follow/unFollow'), '&fid=', args.uid].join(""));
				M.setEventArgs(node, ["uid=", args.uid, "&uname=", args.uname, "&following=", txt.data.following, "&follower=", txt.data.follower].join(""));
				M.removeListener(node);
				M(node);
				_this.updateFollowCount(1);
				updateUserData('follower_count', 1, args.uid);
				// if("following_right" == args.refer) {
				// 	var item = node.parentModel;
				// 	// item.parentNode.removeChild(item);
				// 	$(item).slideUp('normal', function() {
				// 		$(this).remove();
				// 	});
				// 	$.post(U('widget/RelatedUser/changeRelate'), {uid:args.uid, limit:1}, function(msg) {
				// 		var _model = M.getModels("related_u l");
				// 		$(_model[0]).append(msg);
				// 		M(_model[0]);
				// 	}, 'json');
				// 	ui.success("关注成功");
				// } else {
				// 	followGroupSelectorBox(args.uid, args.isrefresh);
				// }
				ui.success("关注成功");
			} else {
				ui.error(txt.info);
			}
		}, 'json');
	},
	/**
	 * 选择关注分组下拉窗
	 * @param object node 关注按钮的DOM对象
	 * @param integer fid 关注人ID
	 * @return void
	 */
	setFollowGroup: function(node, fid) {
		var url = U('public/FollowGroup/selectorBox')+'&fid='+fid;
		ui.box.load(url, L('PUBLIC_SET_GROUP'));	
	},
	/**
	 * 取消关注操作
	 * @param object node 关注按钮的DOM对象
	 * @return void
	 */
	unFollow: function(node) {
		var _this = this;
		var args = M.getEventArgs(node);
		var url = node.getAttribute( "href" ) || U('public/Follow/unFollow');
		// 取消关注操作
		$.post(url, {fid:args.uid}, function(txt) {
			// txt = eval( "(" + txt + ")" );
			if ( 1 == txt.status ) {
				if("undefined" != typeof(core.facecard) ){
					core.facecard.deleteUser(args.uid);
				}
				if ( "following_list" == args.refer ) {
					var item = node.parentModel;
					// 移除
					item.parentNode.removeChild( item );
				} 
				else {					
					node.setAttribute( "event-node", "doFollowSpace" );
					node.setAttribute( "href", [U('public/Follow/doFollow'), '&fid=', args.uid].join( "" ) );
					M.setEventArgs( node, ["uid=", args.uid, "&uname=", args.uname, "&following=", txt.data.following, "&follower=", txt.data.follower].join( "" ) );
					M.removeListener( node );
					M( node );
				}
				_this.updateFollowCount( - 1 );
				updateUserData('follower_count', -1, args.uid);
				ui.success("取消成功");
			} else {
				ui.error( txt.info );
			}
		}, 'json');
	},
	/**
	 * 更新关注数目
	 * @param integer num 添加的数值
	 * @return void
	 */
	updateFollowCount: function(num) {
		var l;
		var following_count = M.getEvents("following_count");
		if(following_count) {
			l = following_count.length;
			while(l-- > 0) {
				following_count[l].innerHTML = parseInt(following_count[l].innerHTML) + num;
			}
		}
	}
};




/**
 * 操作Js类
 * @type {Object}
 */
var NewFollow = {
		/**
		 * 添加关注操作
		 * @param object node 关注按钮的DOM对象
		 * @return void
		 */
		doFollow: function(node) {
			if(MID == 0){
				ui.quicklogin();
				return;
			}
			var _this = this;
			var args = M.getEventArgs(node);
			var url = node.getAttribute("href") || U('public/Follow/doFollow');
			$.post(url, {fid:args.uid}, function(txt) {
				if(1 == txt.status ) {
					if("undefined" != typeof(core.facecard)){
						core.facecard.deleteUser(args.uid);
					}
					//node.setAttribute("event-node", "unFollow");
					//node.setAttribute("href", [U('public/Follow/unFollow'), '&fid=', args.uid].join(""));
					//M.setEventArgs(node, ["uid=", args.uid, "&uname=", args.uname, "&following=", txt.data.following, "&follower=", txt.data.follower].join(""));
					//M.removeListener(node);
					//M(node);
					if("following_right" == args.refer) {
						var item = node.parentModel;
						// item.parentNode.removeChild(item);
						$(item).slideUp('normal', function() {
							$(this).remove();
						});
						$.post(U('widget/RelatedUser/changeRelate'), {uid:args.uid, limit:1}, function(msg) {
							var _model = M.getModels("related_ul");
							$(_model[0]).append(msg);
							M(_model[0]);
						}, 'json');
						ui.success("关注成功");
						setTimeout(window.location.reload(),300);
					} else {
						$(node).remove();
						ui.success("关注成功");
						//followGroupSelectorBox(args.uid, args.isrefresh);
					}
				} else {
					ui.error(txt.info);
				}
			}, 'json');
		},
		/**
		 * 选择关注分组下拉窗
		 * @param object node 关注按钮的DOM对象
		 * @param integer fid 关注人ID
		 * @return void
		 */
		setFollowGroup: function(node, fid) {
			var url = U('public/FollowGroup/selectorBox')+'&fid='+fid;
			ui.box.load(url, L('PUBLIC_SET_GROUP'));	
		}
};
/**
 * 联盟操作Js类
 * @type {Object}
 */
var union = {
	// 按钮样式
	btnClass: {
		doUnion: "post-union",
		unUnion: "btn-att-white",		
		haveUnion: "btn-att-white",
		eachUnion: "btn-att-white"
	},
	// 按钮图标
	flagClass: {
		doUnion: "i-post-union",
		unUnion: "ico-minus-gray",
		haveUnion: "ico-already",
		eachUnion: "ico-connect"
	},
	// 按钮文字
	btnText: {
		doUnion: '发起联盟',
		unUnion: '取消联盟',
		haveUnion: '申请中',
		eachUnion: '已联盟'
	},
	/**
	 * 创建联盟按钮
	 * @param object node 按钮节点对象
	 * @param string btnType 按钮类型，4种
	 * @return void
	 */
	createBtn: function(node, btnType) {
		var args = M.getEventArgs(node);
		var btnType = (0 == args.unioning) ? "doUnion" : ((0 == args.unioner) ? "haveUnion" : "eachUnion");
		var btnClass = this.btnClass[btnType];
		var flagClass = this.flagClass[btnType];
		var btnText = this.btnText[btnType];
		var btnHTML = ['<span><b class="', flagClass, '"></b>', btnText, '</span>'].join( "" );
		// 按钮节点添加HTML与样式
		node.innerHTML = btnHTML;
		node.className = btnClass;
		// 选择按钮类型
		console.log(btnType);
		switch(btnType) {
			case "haveUnion":
			case "eachUnion":
				$(node).bind({
					mouseover: function() {
						var b = this.getElementsByTagName( "b" )[0];
						var text = b.nextSibling;
						this.className = union.btnClass.unUnion;
						b.className = union.flagClass.unUnion;
						text.nodeValue = union.btnText.unUnion;
					},
					mouseout: function() {
						var b = this.getElementsByTagName( "b" )[0];
						var text = b.nextSibling;
						this.className = btnClass;
						b.className = flagClass;
						text.nodeValue = btnText;
					}
				});
				break;
			default:
				$(node).unbind('mouseover');
				$(node).unbind('mouseout');
		}
	},
	/**
	 * 添加联盟操作
	 * @param object node 联盟按钮的DOM对象
	 * @return void
	 */
	doUnion: function(node) {
		var _this = this;
		var args = M.getEventArgs(node);
		var url = node.getAttribute("href") || U('public/Union/doUnion');
		$.post(url, {fid:args.uid}, function(txt) {
			if(1 == txt.status ) {
				if("undefined" != typeof(core.facecard)){
					core.facecard.deleteUser(args.uid);
				}
				node.setAttribute("event-node", "unUnion");
				node.setAttribute("href", [U('public/Union/unUnion'), '&fid=', args.uid].join(""));
				M.setEventArgs(node, ["uid=", args.uid, "&uname=", args.uname, "&unioning=", txt.data.unioning, "&unioner=", txt.data.unioner].join(""));
				M.removeListener(node);
				M(node);
				_this.updateUnionCount(1);
				updateUserData('unioner_count', 1, args.uid);
				if("unioning_right" == args.refer) {
					var item = node.parentModel;
					// item.parentNode.removeChild(item);
					$(item).slideUp('normal', function() {
						$(this).remove();
					});
					$.post(U('widget/RelatedUser/changeRelate'), {uid:args.uid, limit:1}, function(msg) {
						var _model = M.getModels("related_ul");
						$(_model[0]).append(msg);
						M(_model[0]);
					}, 'json');
					ui.success("联盟成功");
				} else {
					//unionGroupSelectorBox(args.uid, args.isrefresh);
				}
			} else {
				ui.error(txt.info);
			}
		}, 'json');
	},
	/**
	 * 选择联盟分组下拉窗
	 * @param object node 联盟按钮的DOM对象
	 * @param integer fid 联盟人ID
	 * @return void
	 */
	setUnionGroup: function(node, fid) {
		var url = U('public/UnionGroup/selectorBox')+'&fid='+fid;
		ui.box.load(url, L('PUBLIC_SET_GROUP'));	
	},
	/**
	 * 取消联盟操作
	 * @param object node 联盟按钮的DOM对象
	 * @return void
	 */
	unUnion: function(node) {
		var _this = this;
		var args = M.getEventArgs(node);
		var url = node.getAttribute( "href" ) || U('public/Union/unUnion');
		// 取消联盟操作
		$.post(url, {fid:args.uid}, function(txt) {
			txt = eval( "(" + txt + ")" );
			if ( 1 == txt.status ) {
				ui.success( txt.info );
				if("undefined" != typeof(core.facecard) ){
					core.facecard.deleteUser(args.uid);
				}
				if ( "unioning_list" == args.refer ) {
					var item = node.parentModel;
					// 移除
					item.parentNode.removeChild( item );
				} else {					
					/*node.setAttribute( "event-node", "doUnion" );
					node.setAttribute( "href", [U('public/Union/doUnion'), '&fid=', args.uid].join( "" ) );
					M.setEventArgs( node, ["uid=", args.uid, "&uname=", args.uname, "&unioning=", txt.data.unioning, "&unioner=", txt.data.unioner].join( "" ) );
					M.removeListener( node );
					M( node );*/
				}
				_this.updateUnionCount( - 1 );
				updateUserData('unioner_count', -1, args.uid);
				if(args.isrefresh==1) location.reload();
			} else {
				ui.error( txt.info );
			}
		});
	},
	/**
	 * 更新联盟数目
	 * @param integer num 添加的数值
	 * @return void
	 */
	updateUnionCount: function(num) {
		var l;
		var unioning_count = M.getEvents("unioning_count");
		if(unioning_count) {
			l = unioning_count.length;
			while(l-- > 0) {
				unioning_count[l].innerHTML = parseInt(unioning_count[l].innerHTML) + num;
			}
		}
	}
};

/**
 * 好友分组选择，下拉框
 * @param object obj 点击按钮DOM对象
 * @param integer fid 关注人ID
 * @return void
 */
var followGroupSelectorList = function(obj, fid)
{
	var x = obj.offset();
	// 获取数据列表
	$.post(U('public/FollowGroup/selectorList'), {fid:fid}, function(res) {
		if($('#followGroupList').length > 0) {
			if($('#followGroupList').attr('rel') == fid) {
				$('#followGroupList').remove();
			} else {
				$('#followGroupList').attr('rel', fid);
				$('#followGroupList').html(res);
			}
		} else {
			$('body').append('<div id="followGroupList" rel="' + fid + '" class="layer-follow-list">'+res+'</div>');
		}
		// 下拉定位
		var shiftSpace = $(window).width()-x.left-obj.width()//确定偏移量
		$('#followGroupList').css({'right': shiftSpace + 'px', 'top':x.top + obj.height() + 8 +'px', 'display':'block'});
		$('#followGroupSelector').find('label').hover(function() {
			$(this).addClass('hover');	
		}, function() {
			$(this).removeClass('hover');
		});

		$('body').bind('click',function(event){
			var obj = "undefined" != typeof(event.srcElement) ? event.srcElement : event.target;
			var isBox = $('#followGroupList').find(obj).length;
			if (isBox === 0) {
				$('#followGroupList').remove();
			}
		});
	});
};
/**
 * 好友分组选择，弹出框
 * @param integer fid 关注人ID
 * @param integer isrefresh 确定后是否刷新页面
 * @return void
 */
var followGroupSelectorBox = function(fid, isrefresh)
{
	if(isrefresh==1){
		var r = 'location.reload();';
	}else{
		var r = '';
	}
	ui.box.load(U('public/FollowGroup/selectorBox')+'&fid='+fid+'&isrefresh='+isrefresh, L('PUBLIC_FOLLOWING_SUCCESS'), r);
};
/**
 * 好友设置分组，弹出框
 * @param integer fid 关注人ID
 * @param integer isrefresh 确定后是否刷新页面
 */
var setFollowGroup = function(fid, isrefresh)
{
	if(isrefresh==1){
		var r = 'location.reload();';
	}else{
		var r = '';
	}
	ui.box.load(U('public/FollowGroup/selectorBox')+'&fid='+fid+'&isrefresh='+isrefresh, '设置分组', r);
};
/**
 * 关闭好友分组选择
 * @param integer fid 关注人ID
 * @return void
 */
var followGroupSelectorClose = function(fid)
{
	$('.followGroupStatus'+fid).hide();
	$('.followGroupStatus'+fid).html('');
};
/**
 * 添加、编辑关注分组，弹出框
 * @return void
 */
var setFollowGroupTab = function(gid)
{
	var title = gid ? L('PUBLIC_EDIT_GROUP') : L('PUBLIC_CREATE_GROUP');
	gid = gid ? '&gid='+gid : '';
	ui.box.load(U('public/FollowGroup/setGroupTab') + gid, title);
};


var message = {};

message.init = function() {
	this.hashUrl = {
		msg: U('public/Message/notify'),
		at: U('public/Mention/index'),
		com: U('public/Comment/index'),
		pmsg: U('public/Message/index')
	};
};

message.changeTab = function(type) {
	$li = $('li[model-node="notice"]').find('li[rel="notice_' + type + '"]');
	$ul = $li.parent();
	$ul.find('li').removeClass('current');
	$li.addClass('current');
	message.getMessage(type);

	var $span = $li.find('a.num');
	var num = parseInt($span.html());
	if (!isNaN(num)) {
		$span.remove();
		var $total = $('li[model-node="notice"] > a.num');
		var numTotal = parseInt($total.html());
		var nowTotal = numTotal - num;
		if (nowTotal <= 0) {
			$total.remove();
		} else {
			$total.html(nowTotal);
		}
	}
};

message.getMessage = function(type) {
	
	message.init();

	$dl = $('ul[rel="message_content"]');
	$dl.html('<p class="loadding"><img src="' + THEME_URL + '/image/icon_waiting.gif" /></p>');
	$.get(U('public/Index/getMessage'), {type:type}, function(res) {
		if (res.status == 1) {
			var $tmp = $('<div></div>').html(res.data.html);
			$tmp.children().last().addClass('no-border');
			$dl.html($tmp.html());
			setTimeout(function() {
				if (type === 'at' || type === 'com' || type === 'pmsg') {
					$dl.find('li').bind('click', function() {
						var id = parseInt($(this).attr('source-id'));
                        if (id == 0) {
                            return false;
                        } else {
                            ui.box.load(U('public/Index/showTalkBox') + '&id=' + id + '&t=' + type, '对话');
                        }
					});
				}
			}, 500);
		} else {
			$dl.html(res.info);
		}
	}, 'json');
	$('p[rel="message_footer"] a').attr('href', message.hashUrl[type]);
	if (type == 'pmsg') {
		$('p[rel="message_footer"] a[event-node="postMsg"]').show();
		M($('p[rel="message_footer"]')[0]);
	} else {
		$('p[rel="message_footer"] a[event-node="postMsg"]').hide()
	}
};

message.whichChangeTab = function() {
	var result = '';
	var type = {msg:0, at:0, com:0, pmsg:0}
	for (var i in type) {
		var num = parseInt($('li[model-node="notice"]').find('li[rel="notice_' + i + '"]').find('a.num').text());
		isNaN(num) && (num = 0);
		type[i] = num;
		if (num != 0) {
			result = i;
			break;
		}
		if (i == 'pmsg' && num == 0) {
			result = 'at';
		}
	}

	return result;
};

//@我的列表
message.at = function(type,t,p){
	var url = U('public/Index/messageContent');
	if(typeof(p) != 'undefined'){
		url = U('public/Index/messageContent')+"&p="+p;
	}
	message.nav(type);
    $.post(url,{type:type,t:t},function(data){
	    if(data){
			$(".main-right.minH").html(data.data.html);
	    }
	},'json');
}

//我的评论
message.comment = function(type,t,stype,p){
	var url = U('public/Index/messageContent');
	if(typeof(p) != 'undefined'){
		url = U('public/Index/messageContent')+"&p="+p;
	}
	message.nav(type);
    $.post( url,{type:type,t:t,stype:stype},function(data){
	    if(data){
			$(".main-right.minH").html(data.data.html);
	    }
	},'json');
}

//我的点赞and系统消息
message.notify = function(type,t,p){
	var url = U('public/Index/messageContent');
	if(typeof(p) != 'undefined'){
		url = U('public/Index/messageContent')+"&p="+p;
	}
	t == 'digg' ? message.nav('digg') : message.nav('system');
    $.post( url,{type:type,t:t},function(data){
	    if(data){
			$(".main-right.minH").html(data.data.html);
	    }
	},'json');

}


//私信列表
message.messageList = function(type,p){
	var url = U('public/Index/messageContent');
	if(typeof(p) != 'undefined'){
		url = U('public/Index/messageContent')+"&p="+p;
	}
	message.nav(type);
    $.post( url,{type:type},function(data){
	    if(data){
			$(".main-right.minH").html(data.data.html);
	    }
	},'json');
}


//私信详情
message.messageDetail = function(type,stype,id){
	message.nav('message');
    $.post( U('public/Index/messageContent'),{type:type,stype:stype,id:id},function(data){
	    if(data){
			$(".main-right.minH").html(data.data.html);
	    }
	},'json');
}

//删除私信
message.delMessage = function(ids){
    ids = ids ? ids : getChecked();
    ids = ids.toString();
    if (ids == '') return false;
    
    $.post(U('public/Message/doDelete'), {ids:ids}, function(res){
        if (res == '1') {
            ui.success("删除成功");
            ids = ids.split(',');
			for(i = 0; i < ids.length; i++) {
				$('#message_'+ids[i]).remove();
			}
			var $message_list_count = $('#message_list_count');
            var message_list_count  = parseInt($message_list_count.html());
            $message_list_count.html(message_list_count - ids.length);
        }else {
            ui.error("删除失败");
        }
    });
    return false;
}

//导航
message.nav = function(type){
	$('dl .current').removeClass('current');
	$('dl').find("#"+type).addClass('current');
	var numTag = $('dl').find("#"+type+' a i.num');
	if(numTag.size()) {
		var num = parseInt(numTag.text());
		if(num > 0) {
			var re   = new RegExp(_CP+'unread_message=(\\d+)');
			var tnum = document.cookie.match(re);
			if(tnum && tnum[1] > 0) {
				document.cookie=_CP+'unread_message='+(tnum[1]-num);
			}
		}
	}
	numTag.remove();
}

//@我的，我的评论，私信分页
message.page = function(a){
	var page = $(a).text();
	if(page == "下一页") page = parseInt($(a).parent().find(".current").text())+1;
	if(page == "上一页") page = parseInt($(a).parent().find(".current").text())-1;
	var type = $("#type").val();
	if(type == "at"){
		var t = $("#t").val();
		message.at(type,t,page);
	}else if(type == "comment"){
		var t = $("#t").val();
		var stype = $("#stype").val();
		message.comment(type,t,stype,page)
	}else if(type == "message"){
		message.messageList(type,page);
	}else if(type == "notify"){
		var t = $("#t").val();
		message.notify(type,t,page)
	}
}

