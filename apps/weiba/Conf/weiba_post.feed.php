<div class="contents clearfix">
		<p><a target="_blank" href="{$sourceInfo['source_url']}">帖子&nbsp;|&nbsp;{$sourceInfo['title']}</a>&nbsp;{:getShort($sourceInfo['content'], intval(110-get_str_length($sourceInfo['title'])), '...')}</p>
    <?php if (!empty($sourceInfo['pic_url'])): ?>
    <div class="feed_img_lists">
    	<ul class="small">
        	<li style="width: 205px; height: auto;">
            	<a target="_blank" href="{$sourceInfo['source_url']}"><img onload="/*仅标签上有效，待改进*/;var li=$(this).parents('li');if(li.height()>300){li.css('height','300px');li.find('.pic-btm').show();}" class="imgicon" src="{$sourceInfo['pic_url_medium']}" style="cursor:pointer" /></a>		
        	</li>
        </ul>
    </div>
	<?php endif; ?>
</div>