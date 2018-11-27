/**
 * 取消推荐操作
 * @param integer rowId 资源ID
 * @return void
 */
admin.cancelRecommended = function(rowId)
{
    // 检查参数
    if(typeof rowId == 'undefined') {
        rowId = admin.getChecked();
        rowId = rowId.join(',');
    }
    if(rowId == '') {
        ui.error('请选择取消推荐内容');
        return false;
    }
    // 回调函数
    var unRecommended = function()
    {
        $.post(U('channel/Admin/cancelRecommended'), {rowId:rowId}, function(msg) {
            if(msg.status == 1) {
                ui.success('取消推荐成功');
                location.href = location.href
                return false;
            } else {
                ui.error('取消推荐失败');
                return false;
            }
        }, 'json');
    };
    if(confirm('确定取消推荐？')) {
        unRecommended();
    }
    return false;
};
/**
 * 驳回操作
 * @param integer rowId 资源ID
 * @return void
 */
admin.rejectChannel = function(rowId)
{
    // 检查参数
    if(typeof rowId == 'undefined') {
        rowId = admin.getChecked();
        rowId = rowId.join(',');
    }
    if(rowId == '') {
        ui.error('请选择驳回内容');
        return false;
    }
    // 回调函数
    var unRecommended = function()
    {
        $.post(U('channel/Admin/cancelRecommended'), {rowId:rowId}, function(msg) {
            if(msg.status == 1) {
                ui.success('驳回成功');
                location.href = location.href;
                return false;
            } else {
                ui.error('驳回失败');
                return false;
            }
        }, 'json');
    };
    if(confirm('确定驳回？')) {
        unRecommended();
    }
    return false;
};
/**
 * 审核操作
 * @param integer rowId 资源ID
 * @return void
 */
admin.auditChannelList = function(rowId, channelId)
{
    var isBatch = false;
    // 检查参数
    if(typeof rowId == 'undefined') {
        rowId = admin.getChecked();
        rowId = rowId.join(',');
        isBatch = true;
    }
    if(rowId == '') {
        ui.error('请选择审核内容');
        return false;
    }
    // 查看是否提示编辑弹窗
    if(isBatch) {
        $.post(U('channel/Admin/auditChannelList'), {rowId:rowId}, function(msg) {
            if(msg.status == 1) {
                ui.success('审核成功');
                location.href = location.href;
                return false;
            } else {
                ui.error('审核失败');
                return false;
            }
        }, 'json');
    } else {
        // 编辑弹窗
        ui.box.load(U('channel/Admin/editAdminBox'+'&feed_id='+rowId+'&channel_id='+channelId), '编辑频道');
    }
};