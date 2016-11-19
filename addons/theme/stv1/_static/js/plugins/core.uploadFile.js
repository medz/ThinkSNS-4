/**
 * uploadFile js 
 * @author jason
 * @exapmle <form><input type="file" name="attach" onchange="core.uploadFile.upload(this,'','image')" urlquery='attach_type=feed_image'></form>
 *  如果自定义回调函数为myback　则<form><input type="file" name="attach" onchange="core.uploadFile.upload(this,myback,'image')" urlquery='attach_type=feed_image'></form>
 *
 */
core.uploadFile = {
		//给工厂调用的接口
		_init:function(attrs){
			if(attrs.length == 6){
				core.uploadFile.upload(attrs[1],attrs[2],attrs[3],attrs[4],attrs[5]);
			}else if(attrs.length == 5){
				core.uploadFile.upload(attrs[1],attrs[2],attrs[3],attrs[4]);
			}else if(attrs.length == 4){
				core.uploadFile.upload(attrs[1],attrs[2],attrs[3]);
			}else if(attrs.length == 3){
				core.uploadFile.upload(attrs[1],attrs[2]);
			}else{	
				//供其他方法使用
				return false;
			}
		},
		//init private
		init:function(obj,callback,type,flag,allowType){
			if("undefined" == typeof(obj)){
				return false;
			}
			this.obj = obj;
			this.callback = "function" != typeof(callback)  ?  '' : callback;
			this.type = "undefined"==typeof(type) || '' == type ? "all":type;
			this.flag = "undefined"==typeof(flag) || '' == flag ? "0":"1";	//0 只能上传图片或者附件，1，允许同时上传附件和图片
			this.allowFileType = "undefined"==typeof(allowType) || '' == allowType ? "":allowType;
			this.urlquery = $(obj).attr('urlquery');
			this.stop = false;
			var limit = $(obj).attr('limit');
			if (typeof limit === 'undefined' || isNaN(limit) || limit === '') {
				this.limit = 9;
			} else {
				this.limit = parseInt(limit);
			}
			// 是否添加form标签
			var addForm = $(obj).attr('addForm');
			if (typeof addForm === 'undefined') {
				this.addForm = false;
			} else {
				this.addForm = true;
			}
				
			if("undefined" == typeof(this.filehash)){
				this.filehash = new Array();
			}
	
			
			//结果展示 保留div
			var hasCreateDiv = false;
			var _this = this;
			
			this.parentModel = this.getParentDiv();
			$(this.parentModel).parent().find('div').each(function(){
			//$(this.parentModel).find('div').each(function(){
				if($(this).attr('uploadcontent') == _this.type){
					hasCreateDiv = true;
					_this.resultDiv = this;
				}
			});
			
			if(!hasCreateDiv){
				if($(this.parentModel).parent().find('.input-content').length > 0){
					this.resultDiv = $(this.parentModel).parent().find('.input-content')[0];
					return true;	
				}
				//未创建过DIV
				this.createResultDiv();
			}

		},
		createResultDiv:function(){
			
			this.resultDiv = document.createElement("div");
			$(this.resultDiv).attr('uploadcontent',this.type);
			$(this.resultDiv).addClass('input-content attach-file');
			if(this.type == 'image'){
				$(this.resultDiv).addClass('attach-file');
				$(this.resultDiv).html('<ul class="weiba-image-list" ></ul>');
			}else{
				$(this.resultDiv).html('<ul class="weibo-file-list"></ul>');
			}


			var hideId = $(this.obj).attr('inputname')+'_ids';

			$(this.resultDiv).append('<input type="hidden" class="attach_ids" value="|" feedtype="'+this.type+'" name="'+hideId+'" id="'+hideId+'">');

			$(this.parentModel).parent().append($(this.resultDiv));
			
		},
		//api for js public
		upload:function(obj,callback,type,flag,allowType){
			　var _this = this;
			 core.loadFile(THEME_URL+'/js/jquery.form.js',function(){
				core.uploadFile.init(obj,callback,type,flag,allowType);
				if(!core.uploadFile.checkFile()){
					if($(_this.resultDiv).find('li').size() <1 ){
						$(_this.resultDiv).remove();
					}
					ui.error( L('PUBLIC_UPLOAD_TIPES_ERROR') )
					return false;
				}

				//取消原来的
				$(_this.parentModel).parent().find('.input-content').each(function(){
					if( $(this).attr('uploadcontent').length>0 ){
						if( $(this).attr('uploadcontent') != _this.type){
							_this.filehash = new Array();
							$(this).remove();
							_this.createResultDiv();
						}
					}
				});

				//验证附件上传个数 不能大于4个
				if(_this.limit!=0 && $(_this.resultDiv).find('li').size() > (_this.limit - 1)){
					ui.error('最多只能上传'+_this.limit+'个附件');
					return false;
				}

				if($(_this.resultDiv).find('.loading').size() < 1 ){
					$(_this.resultDiv).find('ul').eq(0).append('<li class="loading"><div class="loads"><img src="'+THEME_URL+'/image/load.gif" style="width:auto;height:auto"></div><p class="tips upload_tips" style="padding:5px"><a href="javascript:core.uploadFile.stopupload()">'+L('PUBLIC_REMOVE_UPLOAD')+'</a></p></li>');	
				}

				var uploadTimes = core.uploadFile.updataUploadTimes();
				
				_this.parentForm  = _this.getParentFrom();
				/**把其他的上传组件删除 并保存到变量中**/
				if ( $(obj).attr('rel') ){
					var filelist = new Array();
					$(_this.parentForm).find('div[type="file"][id!="divup_'+$(obj).attr('rel')+'"]').each(function (i){
						filelist[$(this).attr('rel')] = $(this).html();
					});
					// $(_this.parentForm).find('input[type="file"][rel!="'+$(obj).attr('rel')+'"]').remove();
				}
				_this.parentForm.method = "post";
				_this.parentForm.action =  U('widget/Upload/save')+'&'+_this.urlquery;
				$(_this.parentForm).ajaxSubmit({ 
					dataType:'json',
			        success: function (data) {
								$(obj).val('');
								core.uploadFile.afterUpload(data.data,data.status,uploadTimes,callback,filelist);
			        }  
			    });
				
			}); 
		},
		clean:function(){
			this.filehash = new Array();
		},
		updataUploadTimes:function(){
			if("undefined" == typeof(this.uploadTimes)){
				this.uploadTimes = 1;
			}else{
				this.uploadTimes++;
			}
			return this.uploadTimes;
		},
		stopupload:function(){
			
			this.updataUploadTimes();

			if(this.stop == true){
				return false;
			}
			$(this.resultDiv).find('.loading').remove();
			if($(this.resultDiv).find('li').size() < 1){
				$(this.resultDiv).remove();
			}
			if("undefined" != typeof(this.oldAction)){
				this.parentForm.action = this.oldAction;
				this.parentForm.method = this.oldMethod;
				//$(this.parentForm).attr('id',this.oldId);
			}

			this.stop = true;
		},
		//afterUpload private 
		afterUpload:function(data,status,times,callback,filelist){
			if(times != this.uploadTimes){
				return false;
			}
			if(filelist != undefined){
				for(var f in filelist){
					$('#divup_'+f).html(filelist[f]);
				}
			}
			if("undefined" != typeof(this.oldAction)){
				this.parentForm.action = this.oldAction;
				this.parentForm.method = this.oldMethod;
				//$(this.parentForm).attr('id',this.oldId);
			}else{
				var html =  $(this.parentForm).html();
				$(this.parentForm).find('input').remove();
				$(this.parentForm).html(html);
			}
			
			$(this.resultDiv).find('.loading').remove();	

			//验证是否已经上传过了
/*			var hasUpload = false;
			for(var i in this.filehash){
				if(this.filehash[i] == data.hash){
					hasUpload = true;
				}
			}

			if(hasUpload == true){
				ui.error( L('PUBLIC_UPLOAD_ISNOT_TIPIES') );
				return false;
			}*/

			if(status !="1"){
				var type = this.type;
				$(this.parentModel).parent().find('.input-content').each(function(){
					if($(this).attr('uploadcontent') == type){
						  if($(this).find('.attach_ids').length <0 || $(this).find('.attach_ids').val() == "|"){
							  $(this).remove();  
						  }
					}
				});
        		ui.error(data);
        		return false;
        	}
			
			
			if(this.stop == true){
				this.stop = false;
				return false;
			}


			
			var func = "undefined" == typeof(callback) ?  '':callback;

			//hash 处理
			this.filehash[data.attach_id] = data.hash;
			
			if('' !=func){
				if('function'==typeof(func)){
					func(data);//执行回调函数
				}
			}else{
				if(this.type=='image'){
    				var html = '<li><a class="pic" href="javascript:void(0)"><img src="'+data.src+'" width="100" height="100"></a>'
    						  +'<a class="delete" href="javascript:void(0)" onclick="core.uploadFile.removeAttachId(this,\''+this.type+'\','+data.attach_id+')">'+L('PUBLIC_DELETE')+'</a></li>';
    			}else{
    				var html = '<li><i class="ico-'+data.extension+'-small"></i><a class="ico-close right" href="javascript:void(0)" onclick="core.uploadFile.removeAttachId(this,\''+this.type+'\','+data.attach_id+')"></a>'
    						  +'<a class="xxx" href="javascript:void(0)" title="'+data.name+'">'+subStr(data.name, 42)+'</a><span>('+data.size+')</span></li>';
    			}		
				var _this = this;
				$(this.parentModel).parent().find('.input-content').each(function(){
					if( $(this).attr('uploadcontent').length>0 ){
						if( $(this).attr('uploadcontent') == _this.type){
							$(this).find('ul').append(html);
						}else{
							$(this).remove();						
						}
					}
				});
    			core.uploadFile.addAttachId(data.attach_id);
			}
		},
		//public func for after post form
		removeParentDiv:function(){
			if("undeifned" != typeof(this.parentModel)){
				$(this.parentModel).parent().find('.input-content').each(function(){
					if($(this).attr('uploadcontent').length > 0){
						$(this).remove();
					}
				});
			}
		},
		//private
		addAttachId:function(id){
			var _this = this;
			$(this.parentModel).parent().find('.input-content').each(function(){
				//$(this.parentModel).find('.input-content').each(function(){
				if($(this).attr('uploadcontent') == _this.type){
					$(this).find('.attach_ids').val($(this).find('.attach_ids').val()+id+'|');
				}
			});
		},
		//public
		removeAttachId:function(obj,type,id){
			var _this = this;
			var parentObj = $(obj).parent().parent().parent();
			$(obj).parent().remove();
			
			//$(this.parentModel).parent().find('.input-content').each(function(){
				
			//$(this.parentModel).find('.input-content').each(function(){
				if(parentObj.attr('uploadcontent') == type){
					var ids = parentObj.find('.attach_ids').val();
					parentObj.find('.attach_ids').val(ids.replace('|'+id+'|','|'));
					if(parentObj.find('.attach_ids').val() == "|" && parentObj.find('.loading').size()<1){
						parentObj.remove();
					}
				}
			//});
			this.filehash[id] = '';
		},
		//private 默认为父DIV的上一层div
		getParentDiv:function(_parent){
			var parent = "undefined" == typeof(_parent) ? this.obj.parentNode : _parent.parentNode;
			if(parent.nodeName == 'SPAN' || parent.nodeName == 'DIV' || parent.nodeName =='UL'){
				return parent;				
			}else{
				return core.uploadFile.getParentDiv(parent);
			}
		},
		getParentFrom:function(_parent){
			if (this.addForm) {
				var offset = $(this.obj).offset();

				var form = toElement('<form method="post" enctype="multipart/form-data"></form>');
				form.action = U('widget/Upload/save')+'&'+this.urlquery;
				form.appendChild(this.obj);
				document.body.appendChild(form);
				$(form).css({'position':'absolute','left':offset.left+'px','top':offset.top+'px'});

				return form;
			} else {
				if(this.obj.parentNode.nodeName == 'FORM'){
					return this.obj.parentNode;
				}else{
					var parent = "undefined" == typeof(_parent) ? this.obj.parentNode : _parent.parentNode;
					if(parent.nodeName == 'FORM'){
						this.oldAction = parent.action;
						this.oldMethod = parent.method;
						//this.oldId = $(parent).attr('id');
						return parent;				
					}else{
						return core.uploadFile.getParentFrom(parent);
					}		
				}
			}
/*			if("object" == typeof(this.parentForm) && this.parentForm.nodeName == 'FORM'){
				$(this.parentForm).remove();
			}
			
			var offset = $(this.obj).offset();

			var form = toElement('<form method="post" enctype="multipart/form-data"></form>');
			form.action = U('widget/Upload/save')+'&'+this.urlquery;
			form.appendChild(this.obj);
			document.body.appendChild(form);
			$(form).css({'left':offset.left+'px','top':offset.top+'px'});
			return form;*/
		},
		//private
		checkFile:function(){
			var filename = $(this.obj).val();
			var pos = filename.lastIndexOf(".");  
		    var str = filename.substring(pos, filename.length)  
		    var str1 = str.toLowerCase();  
		    if(this.type == 'image'){
    		    if (!/\.(gif|jpg|jpeg|png|bmp)$/.test(str1)) {  
    		        return false;  
    		    } 
    		    return true;
		    }
		    //..else file type
		    return true; 
		}
};