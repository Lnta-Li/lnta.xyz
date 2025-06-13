/*
Copyright (c) 2003-2011, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

(function() {
    // 视频对话框定义
    CKEDITOR.dialog.add('video', function(editor) {
        var lang = editor.lang.video || editor.lang.common;
        
        function updatePreview(dialog) {
            //获取URL
            var url = dialog.getValueOf('info', 'src');
            var width = dialog.getValueOf('info', 'width');
            var height = dialog.getValueOf('info', 'height');
            
            // 获取控制选项
            var autoplay = dialog.getValueOf('advanced', 'autoplay');
            var loop = dialog.getValueOf('advanced', 'loop');
            var controls = dialog.getValueOf('advanced', 'controls');
            var muted = dialog.getValueOf('advanced', 'muted');
            
            // 验证URL
            if (!url) {
                dialog.getContentElement('info', 'preview').getElement().setHtml('');
                return;
            }
            
            // 根据URL类型生成不同的预览代码（支持主流视频网站和HTML5视频）
            var html = '';
            
            // 判断是否是视频文件（.mp4, .webm, .ogg）
            if (/\.(mp4|webm|ogg)$/i.test(url)) {
                html = '<video ' + 
                       (controls ? 'controls ' : '') + 
                       (autoplay ? 'autoplay ' : '') + 
                       (loop ? 'loop ' : '') + 
                       (muted ? 'muted ' : '') + 
                       'width="' + width + '" height="' + height + '">' +
                       '<source src="' + CKEDITOR.tools.htmlEncode(url) + '">' +
                       '您的浏览器不支持HTML5视频播放器。' +
                       '</video>';
            } 
            // 判断是否是优酷、土豆、腾讯视频等嵌入链接
            else if (url.indexOf('youku.com') > -1 || url.indexOf('tudou.com') > -1 || 
                     url.indexOf('qq.com') > -1 || url.indexOf('bilibili.com') > -1) {
                // 为嵌入视频添加自动播放、循环播放参数
                var separator = url.indexOf('?') > -1 ? '&' : '?';
                var embedUrl = url;
                if (autoplay) embedUrl += separator + 'autoplay=1';
                if (loop) embedUrl += '&loop=1';
                
                html = '<iframe src="' + CKEDITOR.tools.htmlEncode(embedUrl) + '" ' +
                       'width="' + width + '" height="' + height + '" ' +
                       'frameborder="0" allowfullscreen></iframe>';
            }
            // 默认尝试使用iframe嵌入
            else {
                // 为嵌入视频添加自动播放、循环播放参数
                var separator = url.indexOf('?') > -1 ? '&' : '?';
                var embedUrl = url;
                if (autoplay) embedUrl += separator + 'autoplay=1';
                if (loop) embedUrl += '&loop=1';
                
                html = '<iframe src="' + CKEDITOR.tools.htmlEncode(embedUrl) + '" ' +
                       'width="' + width + '" height="' + height + '" ' +
                       'frameborder="0" allowfullscreen></iframe>';
            }
            
            // 更新预览区域
            dialog.getContentElement('info', 'preview').getElement().setHtml(html);
        }
        
        // 视频元素提交函数
        function commitValue(element) {
            var dialog = this.getDialog();
            var value = this.getValue();
            
            if (value) {
                element.setAttribute(this.id, value);
            } else {
                element.removeAttribute(this.id);
            }
        }
        
        return {
            title: '视频属性',
            minWidth: '',
            minHeight: '',
            
            onShow: function() {
                // 清除当前选择的视频
                this.fakeImage = this.videoElement = null;
                
                // 检查编辑器是否已选择了视频元素
                var fakeImage = this.getSelectedElement();
                if (fakeImage && fakeImage.data('cke-real-element-type') && 
                    fakeImage.data('cke-real-element-type') == 'video') {
                    this.fakeImage = fakeImage;
                    
                    // 恢复真实的视频元素
                    var realElement = editor.restoreRealElement(fakeImage);
                    this.videoElement = realElement;
                    
                    // 设置对话框值
                    this.setupContent(realElement);
                }
            },
            
            onOk: function() {
                // 创建或更新视频元素
                var dialog = this;
                var videoElement = null;
                var url = dialog.getValueOf('info', 'src');
                var width = dialog.getValueOf('info', 'width');
                var height = dialog.getValueOf('info', 'height');
                
                // 获取控制选项
                var autoplay = dialog.getValueOf('advanced', 'autoplay');
                var loop = dialog.getValueOf('advanced', 'loop');
                var controls = dialog.getValueOf('advanced', 'controls');
                var muted = dialog.getValueOf('advanced', 'muted');
                
                // 如果未设置URL，则不进行任何操作
                if (!url) {
                    return;
                }
                
                // 创建新元素
                if (!this.videoElement) {
                    // 判断视频类型并创建相应的HTML元素
                    if (/\.(mp4|webm|ogg)$/i.test(url)) {
                        // HTML5视频
                        var html = '<video ' + 
                                (controls ? 'controls ' : '') + 
                                (autoplay ? 'autoplay ' : '') + 
                                (loop ? 'loop ' : '') + 
                                (muted ? 'muted ' : '') + 
                                'width="' + width + '" height="' + height + '">' +
                                '<source src="' + url + '">' +
                                '您的浏览器不支持HTML5视频播放器。' +
                                '</video>';
                        videoElement = CKEDITOR.dom.element.createFromHtml(html, editor.document);
                    } else {
                        // 为嵌入视频添加自动播放、循环播放参数
                        var separator = url.indexOf('?') > -1 ? '&' : '?';
                        var embedUrl = url;
                        if (autoplay) embedUrl += separator + 'autoplay=1';
                        if (loop) embedUrl += '&loop=1';
                        
                        // iframe嵌入视频
                        var html = '<iframe src="' + embedUrl + '" ' +
                                'width="' + width + '" height="' + height + '" ' +
                                'frameborder="0" allowfullscreen></iframe>';
                        videoElement = CKEDITOR.dom.element.createFromHtml(html, editor.document);
                    }
                } else {
                    // 更新现有元素
                    videoElement = this.videoElement;
                    
                    // 如果是HTML5视频元素，更新控制属性
                    if (videoElement.getName() === 'video') {
                        if (controls) {
                            videoElement.setAttribute('controls', 'controls');
                        } else {
                            videoElement.removeAttribute('controls');
                        }
                        
                        if (autoplay) {
                            videoElement.setAttribute('autoplay', 'autoplay');
                        } else {
                            videoElement.removeAttribute('autoplay');
                        }
                        
                        if (loop) {
                            videoElement.setAttribute('loop', 'loop');
                        } else {
                            videoElement.removeAttribute('loop');
                        }
                        
                        if (muted) {
                            videoElement.setAttribute('muted', 'muted');
                        } else {
                            videoElement.removeAttribute('muted');
                        }
                    }
                    // 如果是iframe元素，更新src属性以包含控制参数
                    else if (videoElement.getName() === 'iframe') {
                        var currentSrc = videoElement.getAttribute('src');
                        var newSrc = currentSrc;
                        
                        // 移除现有的自动播放和循环播放参数
                        newSrc = newSrc.replace(/[&?]autoplay=1/g, '').replace(/[&?]loop=1/g, '');
                        
                        // 如果最后一个&符号后面没有内容，移除它
                        newSrc = newSrc.replace(/[&?]$/g, '');
                        
                        // 添加新的参数
                        var separator = newSrc.indexOf('?') > -1 ? '&' : '?';
                        if (autoplay) newSrc += separator + 'autoplay=1';
                        if (loop) newSrc += (autoplay ? '&' : separator) + 'loop=1';
                        
                        videoElement.setAttribute('src', newSrc);
                    }
                }
                
                // 将内容提交到元素
                this.commitContent(videoElement);
                
                // 创建伪图像用于在编辑器中显示
                var newFakeImage = editor.createFakeElement(videoElement, 'cke_video', 'video', true);
                newFakeImage.setAttributes({
                    width: width,
                    height: height
                });
                
                // 保存控制选项信息在伪图像中，以便编辑时恢复
                if (controls) newFakeImage.setAttribute('data-cke-video-controls', 'true');
                if (autoplay) newFakeImage.setAttribute('data-cke-video-autoplay', 'true');
                if (loop) newFakeImage.setAttribute('data-cke-video-loop', 'true');
                if (muted) newFakeImage.setAttribute('data-cke-video-muted', 'true');
                
                // 替换选中的伪图像或插入新的伪图像
                if (this.fakeImage) {
                    newFakeImage.replace(this.fakeImage);
                    editor.getSelection().selectElement(newFakeImage);
                } else {
                    editor.insertElement(newFakeImage);
                }
            },
            
            contents: [
                {
                    id: 'info',
                    label: '视频信息',
                    elements: [
                        {
                            id: 'src',
                            type: 'text',
                            label: '视频地址',
                            required: true,
                            validate: CKEDITOR.dialog.validate.notEmpty('视频地址不能为空'),
                            setup: function(element) {
                                if (element) {
                                    // 处理不同类型的视频元素
                                    if (element.getName() === 'video') {
                                        var source = element.getChild(0);
                                        if (source && source.getName() === 'source') {
                                            this.setValue(source.getAttribute('src'));
                                        }
                                    } else if (element.getName() === 'iframe') {
                                        this.setValue(element.getAttribute('src'));
                                    }
                                }
                            },
                            commit: function(element) {
                                if (element) {
                                    if (element.getName() === 'video') {
                                        var source = element.getChild(0);
                                        if (source && source.getName() === 'source') {
                                            source.setAttribute('src', this.getValue());
                                        }
                                    } else if (element.getName() === 'iframe') {
                                        // 在commit中不修改iframe的src，因为已经在onOk中处理了
                                    }
                                }
                            },
                            onChange: function() {
                                updatePreview(this.getDialog());
                            }
                        },
                        {
                            id: 'browse',
                            type: 'button',
                            style: 'display:inline-block;margin-top:10px;',
                            hidden: false,
                            filebrowser: 
                            {
                                action: 'Browse',
                                target: 'info:src',
                                url: editor.config.filebrowserVideoBrowseUrl || editor.config.filebrowserBrowseUrl
                            },
                            label: '浏览服务器'
                        },
                        {
                            type: 'hbox',
                            widths: ['50%', '50%'],
                            children: [
                                {
                                    id: 'width',
                                    type: 'text',
                                    label: '宽度',
                                    default: '',
                                    setup: function(element) {
                                        if (element) {
                                            var width = element.getAttribute('width');
                                            this.setValue(width || '');
                                        }
                                    },
                                    commit: commitValue,
                                    onChange: function() {
                                        updatePreview(this.getDialog());
                                    }
                                },
                                {
                                    id: 'height',
                                    type: 'text',
                                    label: '高度',
                                    default: '',
                                    setup: function(element) {
                                        if (element) {
                                            var height = element.getAttribute('height');
                                            this.setValue(height || '');
                                        }
                                    },
                                    commit: commitValue,
                                    onChange: function() {
                                        updatePreview(this.getDialog());
                                    }
                                }
                            ]
                        },
                        {
                            id: 'preview',
                            type: 'html',
                            html: '<div style="width:100%;text-align:center;"><div id="videoPreview" style="border:1px solid #ddd;padding:10px;">预览区域</div></div>'
                        }
                    ]
                },
                {
                    id: 'advanced',
                    label: '高级设置',
                    elements: [
                        {
                            type: 'hbox',
                            widths: ['50%', '50%'],
                            children: [
                                {
                                    id: 'controls',
                                    type: 'checkbox',
                                    label: '显示控制栏',
                                    'default': true,
                                    setup: function(element) {
                                        if (element && element.getName() === 'video') {
                                            this.setValue(element.hasAttribute('controls'));
                                        } else if (this.getDialog().fakeImage) {
                                            this.setValue(this.getDialog().fakeImage.getAttribute('data-cke-video-controls') === 'true');
                                        }
                                    },
                                    commit: function(element) {
                                        // 在onOk里处理
                                    },
                                    onChange: function() {
                                        updatePreview(this.getDialog());
                                    }
                                },
                                {
                                    id: 'autoplay',
                                    type: 'checkbox',
                                    label: '自动播放',
                                    'default': false,
                                    setup: function(element) {
                                        if (element && element.getName() === 'video') {
                                            this.setValue(element.hasAttribute('autoplay'));
                                        } else if (element && element.getName() === 'iframe') {
                                            var src = element.getAttribute('src');
                                            this.setValue(src && src.indexOf('autoplay=1') > -1);
                                        } else if (this.getDialog().fakeImage) {
                                            this.setValue(this.getDialog().fakeImage.getAttribute('data-cke-video-autoplay') === 'true');
                                        }
                                    },
                                    commit: function(element) {
                                        // 在onOk里处理
                                    },
                                    onChange: function() {
                                        updatePreview(this.getDialog());
                                    }
                                }
                            ]
                        },
                        {
                            type: 'hbox',
                            widths: ['50%', '50%'],
                            children: [
                                {
                                    id: 'loop',
                                    type: 'checkbox',
                                    label: '循环播放',
                                    'default': false,
                                    setup: function(element) {
                                        if (element && element.getName() === 'video') {
                                            this.setValue(element.hasAttribute('loop'));
                                        } else if (element && element.getName() === 'iframe') {
                                            var src = element.getAttribute('src');
                                            this.setValue(src && src.indexOf('loop=1') > -1);
                                        } else if (this.getDialog().fakeImage) {
                                            this.setValue(this.getDialog().fakeImage.getAttribute('data-cke-video-loop') === 'true');
                                        }
                                    },
                                    commit: function(element) {
                                        // 在onOk里处理
                                    },
                                    onChange: function() {
                                        updatePreview(this.getDialog());
                                    }
                                },
                                {
                                    id: 'muted',
                                    type: 'checkbox',
                                    label: '默认静音',
                                    'default': false,
                                    setup: function(element) {
                                        if (element && element.getName() === 'video') {
                                            this.setValue(element.hasAttribute('muted'));
                                        } else if (this.getDialog().fakeImage) {
                                            this.setValue(this.getDialog().fakeImage.getAttribute('data-cke-video-muted') === 'true');
                                        }
                                    },
                                    commit: function(element) {
                                        // 在onOk里处理
                                    },
                                    onChange: function() {
                                        updatePreview(this.getDialog());
                                    }
                                }
                            ]
                        }
                    ]
                }
            ]
        };
    });
})(); 