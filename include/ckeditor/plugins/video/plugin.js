/*
Copyright (c) 2003-2011, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

(function() {
    CKEDITOR.plugins.add('video', {
        requires: ['dialog', 'fakeobjects'],
        
        init: function(editor) {
            var lang = editor.lang.video || editor.lang.common;
            
            editor.addCommand('video', new CKEDITOR.dialogCommand('video'));
            
            editor.ui.addButton('Video', {
                label: '插入视频',
                command: 'video',
                icon: this.path + 'icons/video.png'
            });
            
            CKEDITOR.dialog.add('video', this.path + 'dialogs/video.js');
            
            // Add contentsCss
            var rootPath = CKEDITOR.getUrl(this.path);
            if (editor.addContentsCss) {
                editor.addContentsCss(rootPath + 'css/video.css');
            }
            
            // Register fake object
            editor.addCss(
                'img.cke_video' +
                '{' +
                    'background-image: url(' + CKEDITOR.getUrl(this.path + 'icons/placeholder.png') + ');' +
                    'background-position: center center;' +
                    'background-repeat: no-repeat;' +
                    'border: 1px solid #a9a9a9;' +
                    'width: 80px;' +
                    'height: 80px;' +
                '}'
            );
        }
    });
})(); 