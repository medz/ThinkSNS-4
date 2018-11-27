<div class="contents clearfix">
  {$body|t|replaceUrl}
  <dl class="comment">
    <?php if ($sourceInfo['source_user_info'] != false): ?>
    <dd class="com-info clearfix">
      <?php if (!empty($sourceInfo['pic_url_small'])): ?>
      <div class="feed_img">
        <a href="{$sourceInfo['source_url']}" target="_blank"><img src="{$sourceInfo['pic_url_small']}" width="100" height="100" /></a>
      </div>
      <?php endif; ?>
  		<div class="feed_txt<?php if (empty($sourceInfo['pic_url_small'])): echo ' feed_txt_default'; endif; ?>">
        	<span class="source_info"><a href="{$sourceInfo.source_user_info.space_url}" target="_blank" uid="{$sourceInfo.source_user_info.uid}" event-node="face_card">{$sourceInfo.source_user_info.uname}</a><em>&nbsp;&nbsp;{$sourceInfo['publish_time']|friendlyDate}&nbsp;&nbsp;发表在 <a href="{$sourceInfo['weiba_url']}" target="_blank" class="">{$sourceInfo['weiba_name']}</a><!--来自网站--></em></span>
  			<p class="txt-mt" onclick="core.weibo.clickRepost(this);" href="{$sourceInfo['source_url']}"><a target="_blank" href="{$sourceInfo['source_url']}">帖子&nbsp;|&nbsp;{$sourceInfo['title']}</a>&nbsp;{:getShort($sourceInfo['content'], intval(($sourceInfo['pic_url_small']?100:130)-get_str_length($sourceInfo['title'])), '...')}</p>
  		</div>
    </dd>
    <?php else: ?>
    <dd class="name">内容已被删除</dd>
    <?php endif; ?>
  </dl>
</div>