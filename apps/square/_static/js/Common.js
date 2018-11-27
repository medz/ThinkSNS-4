// 前台管理的添加分类
function addCategory(name) {
	if(name=="" || getLength(name.replace(/\s+/g,"")) == 0) {
		ui.error("请输入分类名");
		return false;
	}
	
    var a = $( "li[id]:first" ).clone();
    $.post( U('blog/Index/addCategory'),{name:name},function( txt ){
    	if(-1 == txt) {
    		ui.error( '添加失败' );
        }else if(-2 == txt) {
            ui.error( "分类名已存在" );
        }else if(-3 == txt) {
        	ui.error( '添加失败' );
        }else{
            txt = $.trim(txt);
            var html = "";
            html += '<li class="lineD_btm" style="margin:0px;" id ="cate'+txt+'">';
            html += '<div class="left" style="width: 40%;">';
            html += '<input name="name['+txt+']" style="width:200px;" type="text" class="text" onBlur="this.className=\'text\'" onFocus="this.className=\'text2\'" value="'+name+'"/>';
            html += '</div>';
            html += '<div id="c'+txt+'" class="left" style="width: 50%;">0</div>';
            html += '<div class="left" style="width: 9%;"><a href="javascript:deleteCategory('+txt+')">[移除]</a></div>';
            html += '</li>';
            
            $( "li[id]:last" ).after( html );
            $( '#insertCategory' ).val( "" );
        }
    });
}


function photo_size(name){
    $(name +" img").each(function(){
        var width = 500;
        var height = 500;
        var image = $(this);
        image.addClass('hand');
        image.bind('click',function(){
            window.open(image.attr('src'),"图片显示",'width='+image.width()+',height='+image.height());
        });
        if (image.width() > image.height()){
            if(image.width()>width){
                image.width(width);
                image.height(width/image.width()*image.height());
            }
        }else{
            if(image.height()>height){
                image.height(height);
                image.width(height/image.height()*image.width());
            }
        }
    });
}

$(function(){
    photo_size('#blog_con');
});
    
function deleteCategoryBlog( toCate,formCate ){
    $.post( U('blog/Index/deleteCategory'),{
        id:formCate,
        toCate:toCate
    },function( txt ){
        if( -1 != txt ){
            if( toCate != null ){
                var c1 = $( '#c'+toCate ).text();
                var c2 = $( '#c'+formCate ).text();
                $( '#c'+toCate ).html(parseInt(c1)+parseInt(c2) );
            }
            $( '#cate'+formCate ).remove();
        }else{
            ui.error( "删除分类失败" );
        }
        ui.box.close();
    })
}

       
function deleteCategory( id ){
    var count = $( '#c'+id ).text();
    if( count > 0 ){
    	 ui.box.load(U("blog/Index/deleteCateFrame",["id="+id,"count="+count] ),{title:'转移分类',closeable:true});
       return;
    }
    if( confirm("是否确定删除" ))
        {
                $.post(U('blog/Index/deleteCategory'),{id:id},function( txt ){
                    if( -1 != txt ){
                        $( '#cate'+id ).remove();
                    }else{
                        ui.error( "删除分类失败" );
                    }
                });
            }
      
        } 

function deleteBlog( url ){
	if(confirm("是否确定删除这条知识")) {
		location.href=url;
	}
	return ;
}
function deleteCommentCount( appid ){
    //计数
    $.post(U('blog/Index/deleteSuccess'),{
        id:appid
    },function(result){
        $('#commentCount').text(result);
    });
}
function commentSuccess(json){
    //计数
    $.post(U('blog/Index/commentSuccess'),{
        data:json
    },function(result){
        $('#commentCount').text(result);
    });
}

// 仿T3JS
M.addModelFns({
  event_edit:{  //编辑分类
    callback:function(txt){
      ui.success('编辑成功');
      setTimeout(function() {
        location.href = U('blog/Index/admin');
      }, 1500);
    }
  }

});
