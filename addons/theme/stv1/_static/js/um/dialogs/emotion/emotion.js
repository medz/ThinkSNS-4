(function(){

    var editor = null;

    UM.registerWidget('emotion',{

        tpl: "<link type=\"text/css\" rel=\"stylesheet\" href=\"<%=emotion_url%>emotion.css\">" +
            "<div id=\"edui-emotion-tab-Jpanel\" class=\"edui-emotion-wrapper\">" +
            "<ul id=\"edui-emotion-Jtabnav\" class=\"edui-tab-nav\">" +
            "<li style=\"display:none;\" class=\"edui-tab-item\"><a href=\"#edui-emotion-Jtab0\" hideFocus=\"true\" class=\"edui-tab-text\"><%=lang_input_choice%></a></li>" +
            "<li class=\"edui-emotion-tabs\"></li>" +
            "</ul>" +
            "<div id=\"edui-emotion-JtabBodys\" class=\"edui-tab-content\">" +
            "<div id=\"edui-emotion-Jtab0\" class=\"edui-tab-pane\"></div>" +
            "</div>" +
            "<div id=\"edui-emotion-JtabIconReview\" class=\"edui-emotion-preview-box\">" +
            "<img id=\'edui-emotion-JfaceReview\' src=\"<%=cover_img%>\" class=\'edui-emotion-preview-img\'/>" +
            "</div>",

        sourceData: {
            emotion: {
                tabNum:1, //切换面板数量
                SmilmgName: {
                    'edui-emotion-Jtab0':['', 56]
                }, //图片前缀名
                imageFolders:{
                    'edui-emotion-Jtab0':'location/'
                }, //图片对应文件夹路径
                imageCss:{
                    'edui-emotion-Jtab0':''
                }, //图片css类名
                imageCssOffset:{
                    'edui-emotion-Jtab0':35
                }, //图片偏移
                SmileyInfor:{
                    'edui-emotion-Jtab0':['色', '弱', '衰', '睡觉', '偷笑', '调皮', '嗅大了', '亲亲', '难过', '咒骂', '撇嘴', '强', '敲打', '吐', '抠鼻', '再见', '晕', '折磨', '猪', '左哼哼', '右哼哼', '疑问', '微笑', '委屈', '吓', '嘘', '阴险', '大哭', '流汗', '发呆', '酷', '发怒', '奋斗', '尴尬', '大哭', '大兵', '鄙视', '白眼', '闭嘴', '擦汗', '呲牙', '鼓掌', '哈哈', '快哭了', '酷', '骷髅', '困', '冷汗', '可怜', '可爱', '哈欠', '害羞', '坏笑', '惊恐', '惊讶', '傲慢']
                }
            }
        },
        initContent:function( _editor, $widget ){

            var me = this,
                emotion = me.sourceData.emotion,
                lang = _editor.getLang( 'emotion' )['static'],
                emotionUrl = UMEDITOR_CONFIG.UMEDITOR_HOME_URL + 'dialogs/emotion/',
                options = $.extend( {}, lang, {
                    emotion_url: emotionUrl
                } );

            if( me.inited ) {
                me.preventDefault();
                this.switchToFirst();
                return;
            }

            me.inited = true;

            editor = _editor;
            this.widget = $widget;

            emotion.SmileyPath = _editor.options.emotionLocalization === true ? emotionUrl + 'images/' : "http://img.baidu.com/hi/";
            emotion.SmileyBox = me.createTabList( emotion.tabNum );
            emotion.tabExist = me.createArr( emotion.tabNum );

            options['cover_img'] = emotion.SmileyPath + (editor.options.emotionLocalization ? '0.gif' : 'default/0.gif');

            me.root().html( $.parseTmpl( me.tpl, options ) );

            me.tabs = $.eduitab({selector:"#edui-emotion-tab-Jpanel"});

            //缓存预览对象
            me.previewBox = $("#edui-emotion-JtabIconReview");
            me.previewImg = $("#edui-emotion-JfaceReview");

            me.initImgName();

        },
        initEvent:function(){

            var me = this;

            //防止点击过后关闭popup
            me.root().on('click', function(e){
                return false;
            });

            //移动预览
            me.root().delegate( 'td', 'mouseover mouseout', function( evt ){

                var $td = $( this),
                    url = $td.attr('data-surl') || null;

                if( url ) {
                    me[evt.type]( this, url , $td.attr('data-posflag') );
                }

                return false;

            } );

            //点击选中
            me.root().delegate( 'td', 'click', function( evt ){

                var $td = $( this),
                    realUrl = $td.attr('data-realurl') || null;

                if( realUrl ) {
                    me.insertSmiley( realUrl.replace( /'/g, "\\'" ), evt );
                }

                return false;

            } );

            //更新模板
            me.tabs.edui().on("beforeshow", function( evt ){

                var contentId = evt.target.href.replace( /^.*#(?=[^\s]*$)/, '' );

                evt.stopPropagation();

                me.updateTab( contentId );

            });

            this.switchToFirst();

        },
        initImgName: function() {

            var emotion = this.sourceData.emotion;

            for ( var pro in emotion.SmilmgName ) {
                var tempName = emotion.SmilmgName[pro],
                    tempBox = emotion.SmileyBox[pro],
                    tempStr = "";

                if ( tempBox.length ) return;

                for ( var i = 1; i <= tempName[1]; i++ ) {
                    tempStr = tempName[0];
                    if ( i < 10 ) tempStr = tempStr + '0';
                    tempStr = tempStr + i + '.gif';
                    tempBox.push( tempStr );
                }
            }

        },
        /**
         * 切换到第一个tab
         */
        switchToFirst: function(){
            $("#edui-emotion-Jtabnav .edui-tab-text:first").trigger('click');
        },
        updateTab: function( contentBoxId ) {

            var me = this,
                emotion = me.sourceData.emotion;

            me.autoHeight( contentBoxId );

            if ( !emotion.tabExist[ contentBoxId ] ) {

                emotion.tabExist[ contentBoxId ] = true;
                me.createTab( contentBoxId );

            }

        },
        autoHeight: function( contentBoxId ) {
            var panel = this.widget[0],
                index = +contentBoxId.replace( /[a-zA-Z-]+/, '' );
            switch ( index ) {
                case 0:
                    panel.style.height = "400px";
                    break;
                case 1:
                    panel.style.height = "248px";
                    break;
                case 2:
                    panel.style.height = "286px";
                    break;
                case 3:
                    panel.style.height = "324px";
                    break;
                case 4:
                    panel.style.height = "172px";
                    break;
                case 5:
                    panel.style.height = "236px";
                    break;
                case 6:
                    panel.style.height = "248px";
                    break;
                default:

            }
        },
        createTabList: function( tabNum ) {
            var obj = {};
            for ( var i = 0; i < tabNum; i++ ) {
                obj["edui-emotion-Jtab" + i] = [];
            }
            return obj;
        },
        mouseover: function( td, srcPath, posFlag ) {

            posFlag -= 0;

            $(td).css( 'backgroundColor', 'transparent' );

            this.previewImg.css( "backgroundImage", "url(" + srcPath + ")" );
            posFlag && this.previewBox.addClass('edui-emotion-preview-left');
            // this.previewBox.show();

        },
        mouseout: function( td ) {
            $(td).css( 'backgroundColor', 'transparent' );
            this.previewBox.removeClass('edui-emotion-preview-left').hide();
        },
        insertSmiley: function( url, evt ) {
            var obj = {
//                src:editor.options.emotionLocalization ? editor.options.UMEDITOR_HOME_URL + "dialogs/emotion/" + url : url
                src:url
            };
            obj._src = obj.src;
            obj._emo = "emot";
            editor.execCommand( 'insertimage', obj );
            if ( !evt.ctrlKey ) {
                //关闭预览
                this.previewBox.removeClass('edui-emotion-preview-left').hide();
                this.widget.edui().hide();
            }
        },
        createTab: function( contentBoxId ) {

            var faceVersion = "?v=1.1", //版本号
                me = this,
                $contentBox = $("#"+contentBoxId),
                emotion = me.sourceData.emotion,
                imagePath = emotion.SmileyPath + emotion.imageFolders[ contentBoxId ], //获取显示表情和预览表情的路径
                positionLine = 11 / 2, //中间数
                iWidth = iHeight = 24, //图片长宽
                iColWidth = 3, //表格剩余空间的显示比例
                tableCss = emotion.imageCss[ contentBoxId ],
                cssOffset = emotion.imageCssOffset[ contentBoxId ],
                textHTML = ['<table cellspacing="3" class="edui-emotion-smileytable">'],
                i = 0, imgNum = emotion.SmileyBox[ contentBoxId ].length, imgColNum = 11, faceImage,
                sUrl, realUrl, posflag, offset, infor;

            for ( ; i < imgNum; ) {
                textHTML.push( '<tr>' );
                for ( var j = 0; j < imgColNum; j++, i++ ) {
                    faceImage = emotion.SmileyBox[ contentBoxId ][i];
                    if ( faceImage ) {
                        sUrl = imagePath + faceImage + faceVersion;
                        realUrl = imagePath + faceImage;
                        posflag = j < positionLine ? 0 : 1;
                        offset = cssOffset * i * (-1) - 1;
                        infor = emotion.SmileyInfor[ contentBoxId ][i];

                        textHTML.push( '<td  class="edui-emotion-' + tableCss + '" data-surl="'+ sUrl +'" data-realurl="'+ realUrl +'" data-posflag="'+ posflag +'" align="center">' );
                        textHTML.push( '<span>' );
                        textHTML.push( '<img  style="background-position:left ' + offset + 'px;" title="' + infor + '" src="' + sUrl + '" width="' + iWidth + '" height="' + iHeight + '"></img>' );
                        textHTML.push( '</span>' );
                    } else {
                        textHTML.push( '<td>' );
                    }
                    textHTML.push( '</td>' );
                }
                textHTML.push( '</tr>' );
            }
            textHTML.push( '</table>' );
            textHTML = textHTML.join( "" );
            $contentBox.html( textHTML );
        },
        createArr: function( tabNum ) {
            var arr = [];
            for ( var i = 0; i < tabNum; i++ ) {
                arr[i] = 0;
            }
            return arr;
        },
        width:310
    });

})();

