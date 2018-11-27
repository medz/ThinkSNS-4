{$body|t|replaceUrl}
<div class="mt10">
	<php>if(empty($attachInfo)):</php>
	<ul class="feed_file_list"><li>附件已被删除</li></ul>
	<php>else:</php>
	<ul class="feed_file_list">
		<volist name='attachInfo' id='vo'>
			<li><a href="{:U('widget/Upload/down',array('attach_id'=>$vo['attach_id']))}" class="current right" target="_blank" title="下载"><i class="ico-down"></i></a><i class="ico-{$vo.extension}-small"></i><a href="{:U('widget/Upload/down',array('attach_id'=>$vo['attach_id']))}">{$vo.attach_name}</a> <span class="tips">({$vo.size|byte_format})</span></li>
		</volist>			
		</ul>
	<php>endif;</php>
</div>