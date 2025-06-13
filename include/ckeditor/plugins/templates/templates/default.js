/*
 * Copyright (c) 2003-2011, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.addTemplates('default', {
    imagesPath: CKEDITOR.getUrl(CKEDITOR.plugins.getPath('templates') + 'templates/images/'),
    templates: [
        {
            title: 'DIY-1单图文模板-左图',
            image: 'diy1-1.png',
            description: '单张图片带说明文字的简单模板',
            html: '<div class="diy-1">' +
                  '<img id="no-title" src="/uploads/allimg/250417/1-25041GU500929.jpg" />' +
                  '<p>使用效果：如当前条目所示；<br />1. 首先使用源代码模式，给图片和文本段落添加一个外层div，并设置div的class="diy-1"；2. 然后必须给img图片添加id="no-title"标签（后面有具体说明）。</p>' +
                  '</div>'
        },
        {
          title: 'DIY-1单图文模板-右图',
          image: 'diy1-2.png',
          description: '单张图片带说明文字的简单模板',
          html: '<div class="diy-1">' +
                '<p>使用效果：如当前条目所示；<br />1. 首先使用源代码模式，给图片和文本段落添加一个外层div，并设置div的class="diy-1"；2. 然后必须给img图片添加id="no-title"标签（后面有具体说明）。</p>' +
                '<img id="no-title" src="/uploads/allimg/250417/1-25041GU500929.jpg" />' +
                '</div>'
      },
        {
            title: 'DIY-2多图模板',
            image: 'diy2.png',
            description: '多张图片并排展示的简单模板',
            html: '<p>使用方法：1. 首先使用源代码模式，给多张图片添加一个外层div或p;，并设置div/p的class="diy-2"<br />2. 如果同一行的图片比例不一致，会导致宽度平均、高度不一，解决这个问题的方式是在后台图片设置中把每张图的高度设置为相同，这里推荐800px，这样最终效果就是高度统一，宽度按图片比例自适应；<br /> 3. 不需给图片添加id="no-title"标签。</p>' +
                  '<div class="diy-2">' +
                  '<img src="/uploads/allimg/250417/1-25041GU500929.jpg" />' +
                  '<img src="/uploads/allimg/250417/1-25041GU500929.jpg" />' +
                  '<img src="/uploads/allimg/250417/1-25041GU500929.jpg" />' +
                  '</div>'
        },
        {
            title: '左右分栏模板',
            image: 'diy4.png',
            description: '左右分栏布局模板，左侧占60%宽度，右侧占30%宽度',
            html: '<div class="container clearfix">' +
                  '<p class="left-content">这是左侧内容，占据60%宽度，字体较大且颜色较深。</p>' +
                  '<p class="right-content">这是右侧内容，占据30%宽度，字体较小且颜色较浅。</p>' +
                  '</div>'
        },
        {
            title: '主视觉模板',
            image: 'diy3.png',
            description: '主视觉及元素展示模板，替换页面文字及图片，虚化背景图在发布后自动替换为上传的图片，色值在发布后自动获取图片前6个主要配色。',
            html: '<div class="diy-container">' +
                  '<div class="diy-visual-background">' +
                  '<img src="/uploads/allimg/250509/1-2505091H316317.jpg" alt="Page Background" id="no-preview">' +
                  '</div>' +
                  '<header class="diy-header">' +
                  '<div class="diy-header-left">' +
                  '<div class="diy-title-main">' +
                  '<h1>主视觉设计</h1>' +
                  '<p>Main Visual Design</p>' +
                  '</div>' +
                  '</div>' +
                  '<div class="diy-credits">' +
                  '<p>Designer: Lntano</p>' +
                  '<p>Illustrator: Lntano</p>' +
                  '<p>Time: 2025.05</p>' +
                  '</div>' +
                  '</header>' +
                  '<section class="diy-main-visual">' +
                  '<img src="/uploads/allimg/250509/1-2505091H316317.jpg">' +
                  '</section>' +
                  '<section class="diy-project-intro">' +
                  '<h2>项目介绍</h2>' +
                  '<div class="diy-intro-content">' +
                  '<p>1.双击替换↑上面的图片来更改主画面，虚化背景在源代码模式修改；<br>' +
                  '2.修改页面的文字内容和字体logo等元素，不需要可删除；<br>' +
                  '3.下方↓的配色圆点为辅助示意，F12选取元素修改或者源代码模式修改；<br>' +
                  '4.编辑器中展示效果仅供参考，实际样式稍有差异以发布页面为准。</p>' +
                  '<div class="diy-color-palette">' +
                  '<span class="diy-color-dot" style="background-color: #FF3E9A;">&nbsp;</span>' +
                  '<span class="diy-color-dot" style="background-color: #4D6FFF;">&nbsp;</span>' +
                  '<span class="diy-color-dot" style="background-color: #4DFFF3;">&nbsp;</span>' +
                  '<span class="diy-color-dot" style="background-color: #6A4DFF;">&nbsp;</span>' +
                  '<span class="diy-color-dot" style="background-color: #3D2A99;">&nbsp;</span>' +
                  '<span class="diy-color-dot" style="background-color: #FFB84D;">&nbsp;</span>' +
                  '</div>' +
                  '</div>' +
                  '</section>' +
                  '<footer class="diy-footer-logo-area">' +
                  '<div class="diy-footer-logo-container">' +
                  '<img src="/uploads/allimg/250509/1-2505091H64b61.png">' +
                  '</div>' +
                  '<p class="diy-footer-tagline">-活动主视觉字体设计-</p>' +
                  '</footer>' +
                  '</div>'
        },
        {
          title: '附件下载模板',
          image: 'download.png',
          description: '文件附件下载模板，包含文件图标、文件名称和下载按钮。',
          html: '<a href="#" class="attachment-box">' +
                '<span class="file-icon">' +
                '<i class="iconfont">&#xe64d;</i>' +
                '</span>' +
                '<span class="file-name">file_name.zip</span>' +
                '<span class="download-btn">' +
                '<i class="iconfontb download-icon">&#xe600;</i>' +
                '下载' +
                '</span>' +
                '</a>'
      },
        {
            title: 'Image and Title',
            image: 'template1.gif',
            description: 'One main image with a title and text that surround the image.',
            html: '<h3>' +
                  '<img style="margin-right: 10px" height="100" width="100" align="left"/>' +
                  'Type the title here' +
                  '</h3>' +
                  '<p>Type the text here</p>'
        },
        {
            title: 'Strange Template',
            image: 'template2.gif',
            description: 'A template that defines two colums, each one with a title, and some text.',
            html: '<table cellspacing="0" cellpadding="0" style="width:100%" border="0">' +
                  '<tr>' +
                    '<td style="width:50%"><h3>Title 1</h3></td>' +
                    '<td></td>' +
                    '<td style="width:50%"><h3>Title 2</h3></td>' +
                  '</tr>' +
                  '<tr>' +
                    '<td>Text 1</td>' +
                    '<td></td>' +
                    '<td>Text 2</td>' +
                  '</tr>' +
                  '</table>' +
                  '<p>More text goes here.</p>'
        },
        {
            title: 'Text and Table',
            image: 'template3.gif',
            description: 'A title with some text and a table.',
            html: '<div style="width: 80%">' +
                  '<h3>Title goes here</h3>' +
                  '<table style="width:150px;float: right" cellspacing="0" cellpadding="0" border="1">' +
                    '<caption style="border:solid 1px black"><strong>Table title</strong></caption>' +
                    '<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>' +
                    '<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>' +
                    '<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>' +
                  '</table>' +
                  '<p>Type the text here</p>' +
                  '</div>'
        }
    ]
});
