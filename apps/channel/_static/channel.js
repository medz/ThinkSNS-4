/**
 * 频道核心Js对象
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
var channel = {};
// 用于存储频道的配置信息
channel.setting = {};
/**
 * 频道初始化
 * $param object option 频道配置相关数据
 * @return void
 */
channel.init = function(option)
{
	this.setting.container = '#'+option.container;				// 容器ID
	this.setting.loadcount = option.loadcount || 0;				// 加载数目
	this.setting.loadmax = option.loadmax || 4;					// 加载最大次数
	this.setting.loadId = option.loadId || 0;					// 加载起始ID
	this.setting.loadlimit = option.loadlimit || 10;			// 每次加载的数目，默认为10
	this.setting.cid = option.cid || 0;							// 频道分类ID
	this.setting.canload = option.canload || true;				// 是否能加载
	this.setting.page = 1;										// 分页页数
	this.setting.newload = 0;									// 是否是新加载
	this.setting.categoryJson = option.categoryJson || null;	// 分类JSON数据
	this.setting.order = option.order;

	channel.bindScroll();
	if($(channel.setting.container).length > 0 && this.setting.canload){
		$(channel.setting.container).append("<div class='loading' id='loadMore'>" + L('PUBLIC_LOADING') + "<img src='" + THEME_URL + "/image/ico-load.png' class='load'></div>");
		channel.loadMore();
	}
};

/**
 * 页面底部触发事件
 * @return void
 */
channel.bindScroll = function()
{
	// 底部触发事件绑定
	$(window).bind('scroll resize', function() {
		// 加载指定次数后，将不能自动加载频道信息
		if(channel.isLoading()) {
			var bodyTop = document.documentElement.scrollTop + document.body.scrollTop;
			var bodyHeight = $(document.body).height();
			if(bodyTop + $(window).height() > bodyHeight - 250) {
				if($(channel.setting.container).length > 0) {
					// 加载载入样式
					$(channel.setting.container).after("<div class='loading' id='loadMore'>" + L('PUBLIC_LOADING') + "<img src='" + THEME_URL + "/image/ico-load.png' class='load'></div>");
					// 加载数据
					channel.loadMore();
				}
			}
		}
	});
};
/**
 * 判断是否频道时候能自动加载
 * @return boolean 频道是否能自动加载
 */
channel.isLoading = function()
{
	var status = (this.setting.loadcount >= this.setting.loadmax || this.setting.canload == false) ? false : true;
	return status;
};
/**
 * 获取加载的数据信息
 * @return void
 */
channel.loadMore = function()
{
	// 将能加载参数关闭
	channel.setting.canload = false;
	channel.setting.loadcount++;
	// 异步提交，获取相关频道数据
	var postArgs = {};
	postArgs.widget_appname = 'channel';
	postArgs.loadId = channel.setting.loadId;
	postArgs.loadcount = channel.setting.loadcount;
	postArgs.loadlimit = channel.setting.loadlimit;
	postArgs.cid = channel.setting.cid;
	postArgs.p = channel.setting.page;
	postArgs.newload = channel.setting.newload;
	postArgs.order = channel.setting.order;
	$.get(U('widget/Content/loadMore'), postArgs, function(res) {
		if(res.status == 1) {
			channel.setting.newload = 0;
			// 开启加载参数
			channel.setting.canload = true;
			// 修改加载ID
			channel.setting.loadId = res.loadId;
			// 动态加载数据
			channel.dynamicLoading(res.html, false);
			// 分页操作
			if(channel.setting.loadcount >= channel.setting.loadmax) {
				$(channel.setting.container).after('<div id="page" class="page" style="display:none;">'+res.pageHtml+'</div>');
				if($('#page').find('a').size() > 2) {
					var href = false;
					$('#page').find('a').each(function() {
						href = $(this).attr('href');
					});
					// 重组分页结构
					$('#page').html(res.pageHtml).show();
					var now = parseInt($('#page').children('.current').html().replace('..',''));
					$('#page').find('a').each(function() {
						var href = $(this).attr('href');
						if(href) {
							$(this).attr('href', '#');
							$(this).click(function() {
								$('.boxy-modal-blackout-channel').remove();
								channel.setting.loadcount = 0;
								// $(channel.setting.container).replaceWith(channel.setting.cloneContainer);
								$(channel.setting.container).remove();
								$('#main-wrap').append('<div id="container" class="mb10 channel-list clearfix"></div>');
								// $(channel.setting.container) = $(channel.setting.container);		// 容器Jq对象
								if($(this).is('.pre')) {
									channel.setting.page = now - 1;
								} else if($(this).is('.next')) {
									channel.setting.page = now + 1;
								} else {
									channel.setting.page = parseInt($(this).html().replace('..',''));
								}
								channel.setting.newload = 1;
								channel.loadMore();
								$('.page').remove();
							});
						}
					});
				}
			}
		} else {
			$('#loadMore').remove();
			// channel.dynamicLoading('', false);
		}
	}, 'json');
	return false;
};
/**
 * 动态加载HTML频道数据
 * @param DOM html 新加载HTML数据
 * @param boolean page 是否分页
 * @return void
 */
channel.dynamicLoading = function(html, page)
{
	if(page) {
		//html = channel.getCategoryBox() + html;
		$(channel.setting.container).html(html).masonry('reload');
	} else {
		if(channel.setting.loadcount == 1) {
			//html = channel.getCategoryBox() + html;
			//载入瀑布流
			$(channel.setting.container).html(html);
			$(channel.setting.container).masonry({itemSelector: ".box",gutterWidth: 16});
		} else {
			var domDiv = $('<div></div>').append(html);
			var box = [];
			domDiv.find('div').filter('.box').each(function() {
				box.push(this);
			});
			$(channel.setting.container).append($(box)).masonry('appended', $(box));
		}
	}
	$('#loadMore').remove();
	M($(channel.setting.container)[0]);
};
/**
 * 设置分类下拉分类目录
 * @param string dropId 分类DIV的ID
 * @param string btnId 出发按钮的ID，暂时没有使用
 * @return void
 */
channel.setDropBox = function(dropId, btnId)
{
	$('#'+dropId).bind({
	    mouseover: function() {
	    	$(this).addClass('on');
	    },
	    mouseout: function () {
			$(this).removeClass('on');
	    }
	});
};
/**
 * 浮动Fix导航
 * @param string navId 导航ID字段
 * @return void
 */
channel.setNavigation = function(navId)
{
	var $nav = $('#'+navId);
	var $header = $('#header');
	var height = $header.height();
	$(window).bind('scroll resize', function() {
		var topNav = $nav.offset().top;
		var topHeader = $header.offset().top;
		if(topNav - topHeader < height) {
			$nav.addClass('fixed');
		} else {
			if(topHeader < height) {
				$nav.removeClass('fixed');
			}
		}
	});
};
/**
 * 更改关注频道分类状态
 * @param integer uid 关注用户ID
 * @param integer cid 频道分类ID
 * @param string type 更新类型，add or del
 * @param object obj 按钮DOM对象
 * @return void
 */
channel.upFollowStatus = function(uid, cid, type, obj)
{
	// 数据验证
	if(typeof uid == 'undefined' || typeof cid == 'undefined' || typeof type == 'undefined') {
		return false;
	}
	// 异步提交处理
	$.post(U('widget/TopMenu/upFollowStatus'), {uid:uid, cid:cid, type:type, widget_appname:'channel'}, function(res) {
		if(res.status == 1) {
			if(type === 'del') {
				ui.success('取消收听成功');
				$(obj).html('<span><i class="ico-add"></i>收听</span>');
				$(obj).attr('onclick', "channel.upFollowStatus('"+uid+"', '"+cid+"', 'add', this)");
				channel.upFigures(false);
			} else if(type === 'add') {
				ui.success('收听成功');
				$(obj).html('<span><i class="ico-minus"></i>取消收听</span>');
				$(obj).attr('onclick', "channel.upFollowStatus('"+uid+"', '"+cid+"', 'del', this)");
				channel.upFigures(true);
			}
		} else {
			ui.error('收听失败');
		}
	}, 'json');
	return false;
};
/**
 * 投稿弹窗
 * @param integer cid 频道分类ID
 * @return void
 */
channel.contributeBox = function(cid)
{
	ui.box.load(U('channel/Index/contributeBox')+'&cid='+cid, '我来投稿');
	return false;
};
/**
 * 获取频道分类数据瀑布流块
 * @return string 频道分类数据瀑布流块
 */
channel.getCategoryBox = function()
{
	var html = '<div class="box">\
				<div class="channel-tab-menu"><dl><dt>';
	var data = $.parseJSON(channel.setting.categoryJson);
	for(var i in data) {
		html += '<a class="btn-cancel '+((data[i]['channel_category_id'] == channel.setting.cid) ? 'current' : '')+'" href="'+U('channel/Index/index')+'&cid='+data[i]['channel_category_id']+'"><span>'+data[i]['title']+'</span></a>';
	}
	html += '</dt></dl></div></div>';

	return html;
};
/**
 * 更新频道关注数目
 * @param boolean inc 更新类型，增加还是减少
 * @return void
 */
channel.upFigures = function(inc)
{
	inc = (typeof inc === 'undefined') ? true : inc;
	var nums = parseInt($('#channel_follow_nums').html());
	if(inc) {
		nums++;
	} else {
		nums--;
	}
	nums = (nums < 0 ) ? 0 : nums;
	$('#channel_follow_nums').html(nums);
};
/**
 * 管理弹窗显示
 * @param integer feedId 分享ID
 * @param integer channelId 频道分类ID
 * @return void
 */
var getAdminBox = function(feedId, channelId)
{
	ui.box.load(U('channel/Manage/getAdminBox')+'&feed_id='+feedId+'&channel_id='+channelId, '推荐到频道');
};
