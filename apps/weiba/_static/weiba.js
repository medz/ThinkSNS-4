/**
 * 异步提交表单
 * @param object form 表单DOM对象
 * @return void
 */
var ajaxSubmit = function(form) {
	var args = M.getModelArgs(form);
	M.getJS(THEME_URL + '/js/jquery.form.js', function() {
        var options = {
        	dataType: "json",
            success: function(txt) {
        		if(1 == txt.status) {
        			if("function" ===  typeof form.callback) {
        				form.callback(txt);
        			} else {
        				if("string" == typeof(args.callback)) {
        					eval(args.callback+'()');
        				} else {
        					ui.success(txt.info);
        				}
        			}
        		} else {
        			ui.error(txt.info);
        		}
            }
        };
        $(form).ajaxSubmit(options);
	});
};

/**
 * 处理ajax返回数据之后的刷新操作
 */
var ajaxReload = function(obj,callback){
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

var getChecked = function() {
    var ids = new Array();
    $.each($('#list input:checked'), function(i, n){
        if($(n).val() !='0' && $(n).val()!='' ){
            ids.push( $(n).val() );    
        }
    });
    return ids;
};

var checkAll = function(o){
    if( o.checked == true ){
        $('#list input[name="checkbox"]').attr('checked','true');
    }else{
        $('#list input[name="checkbox"]').removeAttr('checked');
    }
};

M.addModelFns({
	weiba_post:{  //发布帖子
		callback:function(txt){
			if(txt.data.is_audit){
                ui.success('发布内容中包含审核关键词，需审核后展示！');
                setTimeout(function() {
                    location.href = U('weiba/Index/index');
                }, 1500);
			}else{
                ui.success('发布成功');
                setTimeout(function() {
                    location.href = U('weiba/Index/postDetail')+'&post_id='+txt.data.id;
                }, 1500);
			}
		}
	},
	weiba_post_edit:{  //编辑帖子
		callback:function(txt){
            if(txt.data.is_audit){
                ui.success('编辑内容中包含审核关键词，需审核后展示！');
                setTimeout(function() {
                    location.href = U('weiba/Index/index');
                }, 1500);
            }else{
                ui.success('编辑成功');
                setTimeout(function() {
                    location.href = U('weiba/Index/postDetail')+'&post_id='+txt.data.post_id;
                }, 1500);
            }
		}
	},
	drop_weiba_search:{
		load:function(){
			var _this = this;
			search.init();
			$(this.childEvents['searchKey'][0]).click(function(){
				search.searchInit(this);
			});	
		}
	},
	weiba_reply_edit:{   //编辑帖子回复
		callback:function(txt){
			ui.success('编辑成功');
			setTimeout(function() {
				location.href = U('weiba/Index/postDetail')+'&post_id='+txt.data;
			}, 1500);
		}
	},
	weiba_admin_apply:{   //申请圈主
		callback:function(txt){
			ui.success('申请成功，请等待管理员审核');
			setTimeout(function() {
				location.href = U('weiba/Index/detail')+'&weiba_id='+txt.data;
			}, 1500);
		}
	}
});

M.addEventFns({
	doFollowWeiba: {
		click: function() {		
			followWeiba.doFollow( this );
			return false;
		},
		load: function() {
			followWeiba.createBtn( this );
		}
	},
	unFollowWeiba: {
		click: function() {
			followWeiba.unFollow( this );
			return false;
		},
		load: function() {
			followWeiba.createBtn( this );
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
	submit_btn: {
		click: function(){
			// 判断是否有提示信息
			$(this).attr('event-node','');
			var args  = M.getEventArgs(this);
			if ( args.info && ! confirm(args.info)) {
				$(this).attr('event-node','submit_btn');
				return false;
			}
			try {
				(function( node ) {
					var parent = node.parentNode;
					// 判断node 类型，防止意外循环
					if ("FORM" === parent.nodeName) {
						if ("false" === args.ajax) {
							( ("function" !== typeof parent.onsubmit) || (false !== parent.onsubmit())) && parent.submit();
						} else {
							if(args.editor == true){
								var content = EditorList['weiba'].getContentTxt();
								var strlen = getLength(content);
								var leftnums = initNums - strlen;
								if (leftnums < 0) {
									$(this).attr('event-node','submit_btn');
									ui.error('不能超过'+initNums+'个字');
									return false;
								}
							}
							ajaxSubmit(parent);
						}
					} else if ( 1 === parent.nodeType ) {
						arguments.callee( parent );
					}
				})(this);
			}catch(e){
				$(this).attr('event-node','submit_btn');
				return true;
			}
			$(this).attr('event-node','submit_btn');
			return false;
		}
	},
	do_weiba_reply:{	//回复帖子操作
		click:function(){
			if ( this.noreply == 1 ){
				return;
			}else{
				this.noreply = 1;
			}
			var _this = this;
			setTimeout(function (){
				_this.noreply = 0;
			},5000);
			var attrs = M.getEventArgs(this);
			attrs.weiba_id = $(this).attr('weiba_id');
			attrs.post_id = $(this).attr('post_id');
			attrs.post_uid = $(this).attr('post_uid');
			attrs.to_reply_id = $(this).attr('to_reply_id');
			attrs.to_uid = $(this).attr('to_uid');
			attrs.feed_id = $(this).attr('feed_id');
			attrs.addtoend = $(this).attr('addtoend');
			attrs.list_count = $(this).attr('list_count');
			var comment_list = this.parentModel.parentModel;

			var commentListObj = comment_list;
			this.comment_textarea = commentListObj.childModels['comment_textarea'][0];
		    var mini_editor = this.comment_textarea.childModels['mini_editor'][0];
			var _textarea = $(mini_editor).find('textarea').get(0);
			var content = _textarea.value;
			var strlen = core.getLength(content);
			var leftnums = initNums - strlen;
			if(leftnums < 0 || leftnums == initNums) {
				flashTextarea(_textarea);
				ui.error("您输入的内容不符合字数限制！");
				return false;
			}
			$(_this).html('<span>回复中...</span>');
			// if(getLength(content) == '') {
			// 	flashTextarea(_textarea);
			// 	return false;
			// }
			if("undefined" != typeof(this.addComment) && (this.addComment == true)) {
				return false;	//不要重复评论
			}
			// 如果转发到自己的分享
			var ischecked = $(this.comment_textarea).find("input[name='shareFeed']").get(0).checked;
			if(ischecked == true) {
				var ifShareFeed = 1;
			} else {
				var ifShareFeed = 0;
			}
            var attach_id = $('#attach_ids').val();
            if (typeof attach_id != 'undefined') {
                attach_id = attach_id.split('|');
                var tmp = [];
                for (var i in attach_id) {
                    if (attach_id[i] != '') {
                        tmp.push(attach_id[i]);
                    }
                }
                attach_id = tmp.join(',');
            } else {
                attach_id = 0;
            }
     		$.post(U('widget/WeibaReply/addReply'),{widget_appname:'weiba',weiba_id:attrs.weiba_id,post_id:attrs.post_id,post_uid:attrs.post_uid,to_reply_id:attrs.to_reply_id,to_uid:attrs.to_uid,feed_id:attrs.feed_id,content:content,ifShareFeed:ifShareFeed, attach_id:attach_id, list_count:attrs.list_count},function(msg){

				if(msg.status == "0"){
					ui.error(msg.data);
				}else{
                    if(msg.status == 1000){
                        ui.success(msg.data);
                        //重置
                        _textarea.value = '';
                        this.to_reply_id = 0;
                        this.to_uid = 0;
                    }else{
                        if("undefined" != typeof(commentListObj.childModels['comment_list']) ){
                            ui.success('评论成功');
                            if(attrs.addtoend == 1){
                                $('#commentlist_'+attrs.post_id).append(msg.data);
//							$(commentListObj).find('.comment_lists').eq(0).append(msg.data);
                            }else{
                                $('#commentlist_'+attrs.post_id).prepend(msg.data);
//							$(msg.data).insertBefore($(commentListObj.childModels['comment_list'][0]));
                            }
                        }else{
                            ui.success('评论成功');
                            $('#commentlist_'+attrs.post_id).html(msg.data);
//						$(commentListObj).find('.comment_lists').eq(0).html(msg.data);
                        }
                        $('#reply_count').html(parseInt($('#reply_count').html()) + 1);
                        M(commentListObj);
                        //重置
                        _textarea.value = '';
                        this.to_reply_id = 0;
                        this.to_uid = 0;
                        // if("function" == typeof(afterComment)){
                        // 	afterComment();
                        // }
                    }
				}
				$(_this).html('<span>回复</span>');
				addComment = false;
                if("undefined" != typeof(core.uploadFile)) {
                    core.uploadFile.clean();
                    core.uploadFile.removeParentDiv();
                }
				//setTimeout("location.reload()",1000);
			},'json');
		}
	},
	reply_del:{
		click:function(){
			var attrs = M.getEventArgs(this);
/*			$(this.parentModel).fadeOut('normal', function () {
				var $commentList = $(this).parent();
				if ($commentList.length > 0) {
					// 获取分享ID
					var wid = parseInt($commentList.attr('id').split('_')[1]);
					var $commentListVisible = $commentList.find('dl:visible');
					var len = $commentListVisible.length;
					$commentListVisible.each(function (i, n) {
						$(this).find('span.floor').html((len - i)+'楼');
					});
				}
			});*/
			$.post(U('widget/WeibaReply/delReply'),{widget_appname:'weiba',reply_id:attrs.reply_id},function(msg){
			//什么也不做吧
				$('#reply_count').html(parseInt($('#reply_count').html()) - 1);
				$('#item_'+attrs.reply_id).fadeOut();
		});
		}
	},
	reply_reply:{	//点某条回复
		click:function(){ 
			if(MID == 0){
				ui.quicklogin();
				return;
			}
			var attrs = M.getEventArgs(this);
			ui.box.load(U('widget/WeibaReply/reply_reply')+'&widget_appname=weiba'+'&weiba_id='+attrs.weiba_id+'&post_id='+attrs.post_id+'&post_uid='+attrs.post_uid+'&to_reply_id='+attrs.to_reply_id+'&to_uid='+attrs.to_uid+'&to_comment_uname='+attrs.to_comment_uname+'&feed_id='+attrs.feed_id+'&addtoend='+attrs.addtoend+'&comment_id='+attrs.comment_id,L('PUBLIC_RESAVE'),function(){
				$('#at-view').hide();
			});
		}
	},
	post_del:{
		click:function(){
			var attrs = M.getEventArgs(this);
			var _this = this;
			var post_del = function(){
				$.post(U('weiba/Index/postDel'),{post_id:attrs.post_id,weiba_id:attrs.weiba_id,log:attrs.log},function(res){
				if(res == 1){
					ui.success('删除成功');
					location.href=U('weiba/Index/detail') + '&weiba_id='+ attrs.weiba_id;
				}else{
					ui.error('删除失败');
				}
				});
			}		
			ui.confirm(this,L('PUBLIC_DELETE_THISNEWS'),post_del);
		}
	},
	post_set:{
		click:function(){
			var attrs = M.getEventArgs(this);
			$.post(U('weiba/Index/postSet'),{post_id:attrs.post_id,type:attrs.type,currentValue:attrs.currentValue,targetValue:attrs.targetValue},function(res){
				if(res == 1){
					ui.success('设置成功');
					setTimeout("location.reload()",1000);
				}else{
					ui.error('设置失败');
				}
			});
		}
	},
	post_favorite:{
		click:function(){
			if(MID == 0){
				ui.quicklogin();
				return;
			}
			var _this = this;
			var attrs = M.getEventArgs(this);
			$.post(U('weiba/Index/favorite'),{post_id:attrs.post_id,weiba_id:attrs.weiba_id,post_uid:attrs.post_uid},function(res){
				if(res == 1){
					_this.setAttribute("event-node", "post_unfavorite");
					M(_this);
					$(_this).attr("title","取消收藏");
					$(_this).attr("class","big-post-btn-h");
					var html = "<i class=\"i-h-store\" title=\"取消收藏\"></i>取消收藏"
					$(_this).html(html);
					ui.success('收藏成功');
				}else{
					ui.error('收藏失败');
				}
			});
		}	
	},
	post_unfavorite:{
		click:function(){
			if(MID == 0){
				ui.quicklogin();
				return;
			}
			var _this = this;
			var attrs = M.getEventArgs(this);
			$.post(U('weiba/Index/unfavorite'),{post_id:attrs.post_id,weiba_id:attrs.weiba_id,post_uid:attrs.post_uid},function(res){
				if(res == 1){
					_this.setAttribute("event-node", "post_favorite");
					M(_this);
					$(_this).attr("title","收藏");
					$(_this).attr("class","big-post-btn");
					var html = "<i class=\"i-store\" title=\"收藏\"></i>收藏"
					$(_this).html(html);
					ui.success('取消收藏成功');
				}else{
					ui.error('取消收藏失败');
				}
			});
		}	
	},
	post_love:{
			click:function(){
				if(MID == 0){
					ui.quicklogin();
					return;
				}
				var _this = this;
				var attrs = M.getEventArgs(this);
				$.post(U('weiba/Index/addPostDigg'),{row_id:attrs.blog_id},function(res){
					if(res == 1){
						_this.setAttribute("event-node", "post_unlove");
						M(_this);
						$(_this).attr("title","取消点赞");
						$(_this).attr("class","big-post-btn-h");
						var num = parseInt($(_this).find("span").text()) + 1;
						var html = "<i class=\"i-h-praise\" title=\"取消点赞\"></i>已赞&nbsp;<span>"+num+"</span>"
						$(_this).html(html);
						ui.success('点赞成功');
					}else{
						ui.error('点赞失败');
					}
				});
			}	
		},
	post_unlove:{
			click:function(){
				if(MID == 0){
					ui.quicklogin();
					return;
				}
				var _this = this;
				var attrs = M.getEventArgs(_this);
				$.post(U('weiba/Index/delPostDigg'),{row_id:attrs.blog_id},function(res){
					if(res == 1){
						_this.setAttribute("event-node", "post_love");
						M(_this);
						$(_this).attr("title","点赞");
						$(_this).attr("class","big-post-btn");
						var num = parseInt($(_this).find("span").text()) - 1;
						var html = "<i class=\"i-praise\" title=\"点赞\"></i>赞&nbsp;<span>"+num+"</span>"
						$(_this).html(html);
						ui.success('取消点赞成功');
					}else{
						ui.error('取消点赞失败');
					}
				});
			}	
		}
});
	/**
 * 关注操作Js对象
 */
var followWeiba = {
	// 按钮样式
	btnClass: {
		doFollow: "C-btn-active",
		unFollow: "C-btn-active",
		haveFollow: "C-btn-positive"
	},
	// 按钮图标
	flagClass: {
		doFollow: "ico-add",
		unFollow: "ico-minus",
		haveFollow: ""
	},
	// 按钮文字
	btnText: {
		doFollow: '加入',
		unFollow: '退出',
		haveFollow: '已加入'
	},
	/**
	 * 创建关注按钮
	 * @param object node 按钮节点对象
	 * @param string btnType 按钮类型，4种
	 * @return void
	 */
	createBtn: function(node, btnType) {
		var args = M.getEventArgs(node);
		//alert(args.following);
		var btnType = (0 == args.following) ? "doFollow" : "haveFollow";
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
				M.addListener(node, {
					mouseenter: function() {
						var b = this.getElementsByTagName( "b" )[0];
						var text = b.nextSibling;
						this.className = followWeiba.btnClass.unFollow;
						b.className = followWeiba.flagClass.unFollow;
						text.nodeValue = followWeiba.btnText.unFollow;
					},
					mouseleave: function() {
						var b = this.getElementsByTagName( "b" )[0];
						var text = b.nextSibling;
						this.className = btnClass;
						b.className = flagClass;
						text.nodeValue = btnText;
					}
				});
				break;
			default:
				M.addListener(node, {
					mouseleave: function() {
					}
				});
			break;
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
		var url = node.getAttribute("href") || [U('weiba/Index/doFollowWeiba'), '&weiba_id=', args.weiba_id].join("");
		$.post(url, {}, function(txt) {
			if(1 == txt.status ) {
				ui.success('加入成功');
				if(args.isrefresh==1){
					setTimeout("location.reload()",1000);
				}else{
					node.setAttribute("event-node", "unFollowWeiba");
					node.setAttribute("href", [U('weiba/Index/unFollowWeiba'), '&weiba_id=', args.weiba_id].join(""));
					M.setEventArgs(node, ["weiba_id=", args.weiba_id, "&following=1"].join(""));
					M.removeListener(node);
					M(node);
				}
			} else {
				ui.error(txt.info);
			}
		}, 'json');
	},
	
	/**
	 * 取消关注操作
	 * @param object node 关注按钮的DOM对象
	 * @return void
	 */
	unFollow: function(node) {
		var _this = this;
		var args = M.getEventArgs(node);
		var url = node.getAttribute("href") || [U('weiba/Index/unFollowWeiba'), '&weiba_id=', args.weiba_id].join("");
		$.post(url, {}, function(txt) {
			if(1 == txt.status ) {
				ui.success('取消成功');
				if(args.isrefresh==1){
					setTimeout("location.reload()",1000);
				}else{
					node.setAttribute("event-node", "doFollowWeiba");
					node.setAttribute("href", [U('weiba/Index/doFollowWeiba'), '&weiba_id=', args.weiba_id].join(""));
					M.setEventArgs(node, ["weiba_id=", args.weiba_id, "&following=0"].join(""));
					M.removeListener(node);
					M(node);
				}
			} else {
				ui.error(txt.info);
			}
		}, 'json');
	}
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
		doFollow: "ico-add-black",
		unFollow: "ico-minus-gray",
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
		var _this = this;
		var args = M.getEventArgs(node);
		var url = node.getAttribute("href") || U('public/Follow/doFollow');
		$.post(url, {fid:args.uid}, function(txt) {
			if(1 == txt.status ) {
				if("undefined" != typeof(core.facecard)){
					core.facecard.deleteUser(args.uid);
				}
				node.setAttribute("event-node", "unFollow");
				node.setAttribute("href", [U('public/Follow/unFollow'), '&fid=', args.uid].join(""));
				M.setEventArgs(node, ["uid=", args.uid, "&uname=", args.uname, "&following=", txt.data.following, "&follower=", txt.data.follower].join(""));
				M.removeListener(node);
				M(node);
				_this.updateFollowCount(1);
				updateUserData('follower_count', 1, args.uid);
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
				} else {
					followGroupSelectorBox(args.uid, args.isrefresh);
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
			txt = eval( "(" + txt + ")" );
			if ( 1 == txt.status ) {
				ui.success( txt.info );
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
				if(args.isrefresh==1) location.reload();
			} else {
				ui.error( txt.info );
			}
		});
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
var search = {
	init: function(){
		this.searchKey = '';
		return true;
	},

	searchInit: function(obj){
		$(obj).keyup(function(){
			search.displayList(obj);
		});
	},

	displayList: function(obj){
		this.searchKey = obj.value.replace(/(^\s*)|(\s*$)/g,"");
		if(getLength(this.searchKey)>0){
			var html = '<div class="search-box" id="search-box"><dd id="s_2" class="current" onclick="search.dosearch(2);" onmouseover="$(\'#s_2\').addClass(\'current\');$(\'#s_1\').removeClass(\'current\');">搜“<span>'+this.searchKey+'</span>”相关帖子&raquo;</dd>'
						+'<dd id="s_1" onclick="search.dosearch(1);" onmouseover="$(\'#s_1\').addClass(\'current\');$(\'#s_2\').removeClass(\'current\');">搜“<span>'+this.searchKey+'</span>”相关微吧&raquo;</dd></div>';
					//+'<dd class="more"><a href="#"" onclick="core.search.dosearch();">点击查看更多结果&raquo;</a></dd>';
		}else{
			var html = '';
		}
		$(obj).parent().nextAll().remove();
		$(html).insertAfter($(obj).parent());
	},

	//查找数据
	dosearch:function(type){
		 var url = U('weiba/Index/search')+'&k='+this.searchKey;
		 if("undefined" != typeof(type)){
		 	url+='&type='+type;	
		 }
		 location.href = url;
	}
};


var	upload = function(type,obj){
	    if("undefined"  != typeof(core.uploadFile)){
	        core.uploadFile.filehash = new Array();
	    }
		core.plugInit('uploadFile',obj,function(data){
			//alert(data.src);
	        //$('.input-content').remove();
	        $('#show_'+type).html('<img src="'+data.src+'" width="150" height="150">');
	        $('#form_'+type).val(data.attach_id);    
	    },'image');
	};

/**
 * 修改吧内成员等级
 * @param integer weiba_id 微吧ID
 * @param integer follower_uid 当前成员UID
 * @param integer targetLevel 目标等级
 * @return void
 */
var editLevel = function(weiba_id,follower_uid,targetLevel){
	$.post(U('weiba/Manage/editLevel'), {weiba_id:weiba_id,follower_uid:follower_uid,targetLevel:targetLevel}, function(msg) {
		ajaxReload(msg);
	},'json');
};

/**
 * 将用户移出微吧
 * @param integer weiba_id 微吧ID
 * @param integer follower_uid 微吧成员UID
 * @return void
 */
var moveOut = function(weiba_id,follower_uid){
	if("undefined" == typeof(follower_uid) || follower_uid=='') follower_uid = getChecked();
    if(follower_uid==''){
        ui.error('请选择要移出的用户');return false;
    }
	$.post(U('weiba/Manage/moveOut'), {weiba_id:weiba_id,follower_uid:follower_uid}, function(msg) {
		ajaxReload(msg);
	},'json');
};

/**
 * 将用户加入黑名单
 * @param integer weiba_id 微吧ID
 * @param integer follower_uid 微吧成员UID
 * @return void
 */
var moveTo = function(weiba_id,follower_uid){
	if("undefined" == typeof(follower_uid) || follower_uid=='') follower_uid = getChecked();
    if(follower_uid==''){
        ui.error('请选择被加入黑名单的用户');return false;
    }
	$.post(U('weiba/Manage/moveTo'), {weiba_id:weiba_id,follower_uid:follower_uid}, function(msg) {
		ajaxReload(msg);
	},'json');
};

/**
 * 将用户加入黑名单
 * @param integer weiba_id 微吧ID
 * @param integer follower_uid 微吧成员UID
 * @return void
 */
var moveOutTo = function(weiba_id,follower_uid){
	if("undefined" == typeof(follower_uid) || follower_uid=='') follower_uid = getChecked();
    if(follower_uid==''){
        ui.error('请选择被加入黑名单的用户');return false;
    }
	$.post(U('weiba/Manage/moveOutTo'), {weiba_id:weiba_id,follower_uid:follower_uid}, function(msg) {
		ajaxReload(msg);
	},'json');
};


var addUser = function(){
  var weiba_id = $('#weiba_id').val();
  var follower_uid = $('#search_uids').val();
  if(follower_uid==''||follower_uid==' '){
	  alert('请添加成员！');return false;
  }
  $.post(U('weiba/Manage/moveTo'),{weiba_id:weiba_id,follower_uid:follower_uid},function(msg){
      if(msg.status == 0){
          ui.error(msg.data);
      }else{
          ui.success(msg.data);
          $('#search_uids').val('');
          setTimeout("location.reload()",1000);
      }
  },'json');
}; 

/**
 * 解散微吧
 * @param integer weiba_id 微吧ID
 * @return void
 */
var delWeiba = function(weiba_id){
	if(confirm('确定要解散此微吧吗？')){
        $.post(U('weiba/Manage/delWeiba'),{weiba_id:weiba_id},function(msg){
            if(msg == 1) {
            	ui.success('解散成功');
            	location.href = U('weiba/Index/index');
            }else if(msg == -1){
            	ui.error('微吧ID不能为空');
            }else{
            	ui.error('解散失败');
            }
        });
    }
};

/**
 * 检查是否有发帖权限
 * @param integer weiba_id 微吧ID
 * @param boolean who_can_post 发帖权限 0：所有人  1：关注本吧的人
 */
var check_post = function(weiba_id, who_can_post){
//	if(who_can_post){
//		$.post(U('weiba/Index/checkPost'),{weiba_id:weiba_id},function(txt){
//			if(txt==1){
//				location.href = U('weiba/Index/post')+'&weiba_id='+weiba_id;
//			}else{
//				ui.box.load(U('weiba/Index/joinWeiba')+'&weiba_id='+weiba_id,'您没有发帖权限');
//			}
//		});
//	}else{
		location.href = U('weiba/Index/post')+'&weiba_id='+weiba_id;
//	}
};

var weiba_admin_apply = function(weiba_id,type){
	$.post(U('weiba/Index/can_apply_weiba_admin'),{weiba_id:weiba_id,type:type},function(txt){
		if(txt==1){
			location.href = U('weiba/Index/apply_weiba_admin')+'&weiba_id='+weiba_id+'&type='+type;
		}else if(txt==2){
			ui.error('该圈已经设置了圈主');
			setTimeout("location.reload()",2000);
		}else if(txt==-1){
			ui.error('您已经提交了申请，请等待审核');
		}else if(txt==-2){
			ui.error('您已经是圈主，不能重复申请');
		}else if(txt==-3){
			ui.error('对不起，您没有权限执行该操作！');
		}else{
			ui.box.load(U('weiba/Index/apply_weiba_admin_box')+'&weiba_id='+weiba_id,'申请管理员');
		}
	});
};

/**
 * 处理申请圈主或小主申请
 */
var verify = function(weiba_id, uid, value){
	$.post(U('weiba/Manage/verify'),{weiba_id:weiba_id,uid:uid,value:value},function(msg){
		ajaxReload(msg);
	},'json');
};

var saveWeibaInfo = function(){
	var weiba_id = $('#weiba_id').val();
	var weiba_name = $('#weiba_name').val();
	var cid = $('#cid').val();
	var intro = $('#intro').val();
	var logo = $('#form_logo').val();
	var who_can_post = $('input:checked[name="who_can_post"]').val();
	$.post(U('weiba/Manage/doWeibaEdit'),{weiba_id:weiba_id,cid:cid,weiba_name:weiba_name,intro:intro,logo:logo,who_can_post:who_can_post},function(msg){
		if(msg=='1'){
			ui.success('保存成功');
		}else{
			ui.error(msg);
		}
	});
}

//申请微吧
var apply_weiba = function(){
	//未登录
	if( MID == 0 ){
		ui.quicklogin();
		return;
	}
	$.post(U('weiba/Index/can_apply_weiba'),{},function(txt){
		if(txt == 1){
			location.href = U('weiba/Index/apply_weiba');
		}else{
			ui.error("您的账号还没有达到申请微吧的要求！");
		}
	});
}

var do_apply_weiba = function(){
	var weiba_name = $('#weiba_name').val();
	var cid = $('#cid').val();
	var logo = $('#form_logo').val();
	var intro = $('#intro').val();
	$.post(U('weiba/Index/do_apply_weiba'),{weiba_name:weiba_name,cid:cid,logo:logo,intro:intro},function(msg){
		if(msg=='1'){
			ui.success('申请成功');
			setTimeout("location.href=U('weiba/Index/weibaList')",'2000');
		}else{
			ui.error(msg);
		}
	});
}

var weiba = {};
/**
 * 赞核心Js
 * @type {Object}
 */
weiba.digg = {
	// 给工厂调用的接口
	_init: function (attrs) {
		weiba.digg.init();
	},
	init: function () {
		weiba.digg.digglock = 0;
		weiba.digg.appname = 'weiba';
	},
	addDigg: function (row_id) {
		if(MID == 0){
				ui.quicklogin();
				return;
		}
		if (weiba.digg.digglock == 1) {
			return false;
		}
		weiba.digg.digglock = 1;
		$.post(U('widget/ReplyDigg/addDigg', ['widget_appname=weiba']), {row_id:row_id}, function (res) {
			if (res.status == 1) {
				$digg = {};
				if (typeof $('#digg'+row_id)[0] === 'undefined') {
					$digg = $('#digg_'+row_id);
				} else {
					$digg = $('#digg'+row_id);
				}
				var num = $digg.attr('rel');
				num++;
				$digg.attr('rel', num);
				$('#digg'+row_id).html('<a href="javascript:;" class="like-h digg-like-yes" onclick="weiba.digg.delDigg('+row_id+')"><i class="digg-like"></i>('+num+')</a>');
				$('#digg_'+row_id).html('<a href="javascript:;" class="like-h digg-like-yes" onclick="weiba.digg.delDigg('+row_id+')"><i class="digg-like"></i>('+num+')</a>');
			} else {
				ui.error(res.info);
			}
			weiba.digg.digglock = 0;
		}, 'json');
	},
	delDigg: function (row_id) {
		if (weiba.digg.digglock == 1) {
			return false;
		}
		weiba.digg.digglock = 1;
		$.post(U('widget/ReplyDigg/delDigg', ['widget_appname=weiba']), {row_id:row_id}, function(res) {
			if (res.status == 1) {
				$digg = {};
				if (typeof $('#digg'+row_id)[0] === 'undefined') {
					$digg = $('#digg_'+row_id);
				} else {
					$digg = $('#digg'+row_id);
				}
				var num = $digg.attr('rel');
				num--;
				$digg.attr('rel', num);
				var content;
				if (num == 0) {
					content = '<a href="javascript:;" onclick="weiba.digg.addDigg('+row_id+')"><i class="digg-like"></i></a>';
				} else {
					content = '<a href="javascript:;" onclick="weiba.digg.addDigg('+row_id+')"><i class="digg-like"></i>('+num+')</a>';
				}
				$('#digg'+row_id).html(content);
				$('#digg_'+row_id).html(content);
			} else {
				ui.error(res.info);
			}
			weiba.digg.digglock = 0;
		}, 'json');
	}
};

weiba.order = {
	ajaxList: function (weiba_id, post_id, post_uid, feed_id, type) {
		location.href = U('weiba/Index/postDetail', ['post_id='+post_id, 'type='+type]);
		return false;
		var args = {};
		args.tpl = 'detail';
		args.weiba_id = weiba_id;
		args.post_id = post_id;
		args.post_uid = post_uid;
		args.feed_id = feed_id;
		args.limit = 20;
		args.type = type;
		args.addtoend = 0;
		$.post(U('widget/WeibaReply/render', ['widget_appname=weiba']), args, function(res) {
			$('#ajax_reply_list').html(res);
			M(document.getElementById('ajax_reply_list'));
		});
	}
}

