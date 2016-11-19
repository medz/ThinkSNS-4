<eq name='body' value=''> 分享分享 </eq> 
{$body|t|replaceUrl}
<dl class="comment">
	<php>if($sourceInfo['is_del'] == 0 && $sourceInfo['source_user_info'] != false):</php>
	<dd class="com-info clearfix">
		<php>if(!empty($sourceInfo['attach'])):</php>

		{* 附件分享 *}
		<eq name='sourceInfo.feedType' value='postfile'>
		<ul class="feed_file_list">
			<volist name='sourceInfo.attach' id='vo'>
			<li>
				<a href="{:U('widget/Upload/down',array('attach_id'=>$vo['attach_id']))}" class="current right" target="_blank"><i class="ico-down"></i></a>
				<i class="ico-{$vo.extension}-small"></i>
				<a href="{:U('widget/Upload/down',array('attach_id'=>$vo['attach_id']))}">{$vo.attach_name}</a>
				<span class="tips">({$vo.size|byte_format})</span>
			</li>
			</volist>			
		</ul>		
		</eq>

		{* 图片分享 *}
		<eq name='sourceInfo.feedType' value='postimage'>
		<div class="feed_img" rel='small' >
			<ul class="small">
				<?php 
                $attachCount = count($sourceInfo['attach']);
                $sourceInfo['attach'] = array($sourceInfo['attach'][0]);
                ?>
                <volist name='sourceInfo.attach' id='vo'>
                <li class="m0">
                    <a href="javascript:void(0)" onclick="core.weibo.showBigImage({$sourceInfo['feed_id']}, {$i});">
                        <img class="imgicon" src='{$vo.attach_small}' title='点击放大' width="100" height="100">
                        <php>if($attachCount>1){</php><span class="pic-more">{$attachCount} photos</span><php>}</php>
                    </a>
                </li>
                </volist>
			</ul>
		</div>
		<div class="feed_txt">
	       {* 转发原文 *}
           <span class="source_info">{$sourceInfo['source_user_info']['space_link']}<em>&nbsp;&nbsp;{$sourceInfo['publish_time']|friendlyDate}<!--&nbsp;{:getFromClient($sourceInfo['from'])}--></em></span>
		   <p class="txt-mt" onclick="core.weibo.clickRepost(this);" href="javascript:core.weibo.showBigImage({$sourceInfo['feed_id']}, {$i});">{:msubstr(t($sourceInfo['source_content']),0,100)}</p>
		</div>
		</eq>

		<php>else:</php>

			{* 视频分享 *}
			<eq name='sourceInfo.feedType' value='postvideo'>
				<div class="feed_img" id="video_mini_show_{$feedid}_{$sourceInfo['feed_id']}">
					  <a href="javascript:void(0);" <php>if(!$sourceInfo['transfering']){</php>onclick="switchVideo({$sourceInfo['feed_id']},{$feedid},'open','{$sourceInfo.host}','{$sourceInfo.flashvar}','{:strpos($sourceInfo['flashimg'], '://')?$sourceInfo['flashimg']:getImageUrl($sourceInfo['flashimg'], 150, 100)}')"<php>}</php> >
					    <img src="{:strpos($sourceInfo['flashimg'], '://')?$sourceInfo['flashimg']:getImageUrl($sourceInfo['flashimg'], 120, 120, true)}" style="width:100px;height:100px;overflow:hidden;" data-medz-name="user-outside-video"  onerror="javascript:var default_img = THEME_URL + '/image/video_bk.png';$(this).attr('src',default_img);">
					  </a>
					  <div class="video_play" ><a href="javascript:void(0);" <php>if(!$sourceInfo['transfering']){</php>onclick="switchVideo({$sourceInfo['feed_id']},{$feedid},'open','{$sourceInfo.host}','{$sourceInfo.flashvar}','{$sourceInfo.flashimg}')"<php>}</php> ></a>
					  </div>
				</div>
                <div class="feed_txt feed_txt_video">
                   {* 转发原文 *}
                   <span class="source_info">{$sourceInfo['source_user_info']['space_link']}<em>&nbsp;&nbsp;{$sourceInfo['publish_time']|friendlyDate}<!--&nbsp;{:getFromClient($sourceInfo['from'])}--></em></span>
                   <p class="txt-mt" onclick="core.weibo.clickRepost(this);" href="{:U('public/Profile/feed',array('uid'=>$sourceInfo['uid'],'feed_id'=>$sourceInfo['feed_id']))}">{:msubstr(t($sourceInfo['source_content']),0,100)}</p>
                </div>
				<div class="feed_quote" style="display:none;" id="video_show_{$feedid}_{$sourceInfo['feed_id']}">
				  <div class="q_tit">
				    <img class="q_tit_l" onclick="switchVideo({$sourceInfo['feed_id']},{$feedid},'open','{$sourceInfo.host}','{$sourceInfo.flashvar}','{$sourceInfo.flashimg}')" src="__THEME__/image/zw_img.gif" />
				  </div>
				  <div class="q_con"> 
				    <p style="margin:0;margin-bottom:5px" class="cGray2 f12">
				    <a href="javascript:void(0)" onclick="switchVideo({$sourceInfo['feed_id']},{$feedid},'close')"><i class="ico-pack-up"></i>收起</a>
				    
				    </p>
				    <div id="video_content_{$feedid}_{$sourceInfo['feed_id']}"></div>
				  </div>
				  <!--<div class="q_btm"><img class="q_btm_l" src="__THEME__/image/zw_img.gif" /></div>-->
				</div>
			</eq>

			<eq name='sourceInfo.feedType' value='post'>
			<div class="feed_txt feed_txt_default">
	        {* 转发原文 *}
            <span class="source_info">{$sourceInfo['source_user_info']['space_link']}<em>&nbsp;&nbsp;{$sourceInfo['publish_time']|friendlyDate}<!--&nbsp;{:getFromClient($sourceInfo['from'])}--></em></span>
		    <p class="txt-mt" onclick="core.weibo.clickRepost(this);" href="{:U('public/Profile/feed',array('uid'=>$sourceInfo['uid'],'feed_id'=>$sourceInfo['feed_id']))}">{:msubstr(t($sourceInfo['source_content']),0,128)}</p>
			</div>
			</eq>

		<php>endif;</php>
	</dd>
	<php>else:</php>
	<dd class="name">内容已被删除</dd>
	<php>endif;</php>
</dl>