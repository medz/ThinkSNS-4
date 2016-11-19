<p>{$body|t|replaceUrl}</p>
<div class="feed_img_lists" >
<ul class="small">

<php>$attachCount=count($attachInfo);</php>
<volist name='attachInfo' id='vo'>
	<li rel="{$vo.attach_id}" {$attachCount==1?'style="width:205px;height:auto"':''}>
		<a href="javascript:void(0);" onclick="core.weibo.showBigImage({$feedid}, {$i})" >
		   <img <php>if($attachCount==1):</php>onload="/*仅标签上有效，待改进*/;var li=$(this).parents('li');if(li.height()>300){li.css('height','300px');li.find('.pic-btm').show();}" <php>endif;</php>class="imgicon" src='{$attachCount==1?$vo['attach_medium']:$vo['attach_small']}' title='点击放大' >
		   <!--共有{$attachCount}张图片-->
           {$attachCount==1?'<span class="pic-btm hidden">点击查看完整图片</span>':''}
		</a>
	</li>
</volist>
</ul>
</div>
<div class="feed_img_lists" rel='big' style='display:none'>
<ul class="feed_img_list big" >
<span class='tools'>
	<a href="javascript:;" event-node='img_big'><i class="ico-pack-up"></i>收起</a>
	<a target="_blank" href="{$vo.attach_url}"><i class="ico-show-big"></i>查看大图</a>
	<a href="javascript:;" onclick="revolving('left', {$feedid})"><i class="ico-turn-l"></i>向左转</a>
	<a href="javascript:;" onclick="revolving('right', {$feedid})"><i class="ico-turn-r"></i>向右转</a>
</span>
<volist name='attachInfo' id='vo'>
<li title='{$vo.attach_url}'>
	<!-- <a onclick="core.weibo.showBigImage({$feedid});" target="_blank" class="ico-show-big" title="查看大图" ></a> -->
	<a href="javascript:void(0)" event-node='img_big'><img maxwidth="557" id="image_index_{$feedid}" class="imgsmall" src='{$vo.attach_middle}' title='点击缩小' ></a>
</li>
</volist>
</ul>
</div>