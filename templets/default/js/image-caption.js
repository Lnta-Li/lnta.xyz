/**
 * 图片处理模块
 * 处理文章中的图片显示、长图展开收起、小图模式等功能
 */
document.addEventListener('DOMContentLoaded', () => {
    // 配置常量
    const CONFIG = {
        transition: { // 过渡时间配置
            base: 2000, // 过渡时间计算基数（2000px/1s）
        },
        
        timeout: { // 延时配置
            domComplete: 0.5, // DOM结构创建完成后的检查延时0.5秒
        },
        
        selector: { // 选择器配置
            contentImages: '.Content-Type img', // 内容区域图片选择器
            longImg: 'img[id="long-img"]', // 长图选择器
            excludeImgId: 'no-title', // 小图模式、长图模式 不处理的图片ID
            excludeContainer: '.diy-container', // 小图模式排除diy-container内的图片
        },
        
        longImg: { // 长图配置
            expandIcon: '&#xe615;', // 展开按钮图标编码
            expandText: '展开长图', // 展开按钮文本
            collapseText: '收起长图' // 收起按钮文本
        },
        
        smallImgUI: { // 小图模式UI配置
            keepIcon: '&#xe6d2;', // 保持预览按钮图标编码
            switchIcon: '&#xe628;', // 切换大图按钮图标编码
            floatKeepTitle: '保持预览模式', // 悬浮栏保持预览按钮标题
            floatSwitchTitle: '切换大图浏览', // 悬浮栏切换大图按钮标题
            floatBarId: 'img-mode-control' // 悬浮栏的ID
        },

        noticeUI: { // 通知栏配置
            noticeShow: 5, // 提示条显示延时5秒
            noticeAutoHide: 10, // 提示条自动消失倒计时10秒
            noticeRemove: 3, // 提示条消失后的延时3秒销毁（预留给退出动画的时间）
            noticeIcon: '&#xe651;', // 提示图标编码
            noticeText: "当前文章作者设置了缩略图预览模式，可以点击这里切换" // 提示文本
        }
    };
    
    // 工具函数模块
    const utils = {
        calculateTransitionDuration(height) { // 计算过渡时间
            return (height / CONFIG.transition.base).toFixed(2) + 's';
        },
        
        waitForImagesLoaded(imgs) { // 等待所有图片加载完成
            return Promise.all(imgs.map(img => {
                if (img.complete && img.naturalHeight !== 0) {
                    return Promise.resolve();
                } else {
                    return new Promise(resolve => {
                        img.onload = () => resolve();
                        img.onerror = () => resolve();
                    });
                }
            }));
        },
        
        createElement(tag, attrs = {}, children = []) { // 创建DOM元素并设置属性
            const element = document.createElement(tag);
            
            Object.entries(attrs).forEach(([key, value]) => { // 设置属性
                if (key === 'className') {
                    element.className = value;
                } else if (key === 'style') {
                    Object.entries(value).forEach(([prop, val]) => {
                        element.style[prop] = val;
                    });
                } else if (key === 'innerHTML') {
                    element.innerHTML = value;
                } else if (key === 'textContent') {
                    element.textContent = value;
                } else {
                    element.setAttribute(key, value);
                }
            });
            
            if (children) { // 添加子元素
                if (Array.isArray(children)) {
                    children.forEach(child => {
                        if (child) element.appendChild(child);
                    });
                } else {
                    element.appendChild(children);
                }
            }
            
            return element;
        }
    };
    
    // 小图模式检测模块
    const smallImgMode = {
        detect() { // 检测是否为小图模式
            const userAgent = navigator.userAgent.toLowerCase(); // 检查是否为iPhone设备
            if (userAgent.indexOf('iphone') > -1) {
                return true;
            }
            
            const smallImgMeta = document.querySelector('meta[name="small_img"]'); // 通过meta标签检查是否为小图模式
            return smallImgMeta ? smallImgMeta.getAttribute('content') === '1' : false;
        },
        
        applyToContainers(isSmallImg) { // 应用小图模式到图片容器
            if (!isSmallImg) return;
            
            setTimeout(() => { // 延迟执行，确保所有DOM结构都已创建完成
                document.querySelectorAll('.image-box').forEach((box) => {
                    if (!box.classList.contains('small-box')) {
                        box.classList.add('small-box');
                    }
                });
                
                // 检查是否为移动设备
                const isMobile = window.matchMedia('(max-width: 768px)').matches;
                
                // 当应用小图模式且不是移动设备时，显示通知
                if (!isMobile && typeof NoticeManager !== 'undefined') {
                    NoticeManager.show({
                        text: CONFIG.noticeUI.noticeText,
                        icon: CONFIG.noticeUI.noticeIcon,
                        targetId: CONFIG.smallImgUI.floatBarId,
                        positionMode: 'above-right', // 在目标元素上方右对齐
                        showDelay: CONFIG.noticeUI.noticeShow,
                        autoHideDelay: CONFIG.noticeUI.noticeAutoHide,
                        removeDelay: CONFIG.noticeUI.noticeRemove
                    });
                }
            }, CONFIG.timeout.domComplete * 1000);
        },
        
        toggleMode(toSmallMode) { // 切换图片显示模式
            document.querySelectorAll('.image-box').forEach(box => {
                if (toSmallMode) {
                    box.classList.add('small-box');
                } else {
                    box.classList.remove('small-box');
                }
            });
        }
    };
    
    // 普通图片处理模块
    const normalImageProcessor = {
        process(isSmallImg) { // 处理普通图片
            const images = Array.from(document.querySelectorAll(CONFIG.selector.contentImages)) // 获取文章内容区域中的所有图片，并排除特定ID的图片
                .filter(img => {
                    // 排除特定ID的图片
                    if (img.id === CONFIG.selector.excludeImgId || img.id === 'long-img') {
                        return false;
                    }
                    
                    // 排除diy-container内部的图片
                    if (CONFIG.selector.excludeContainer && img.closest(CONFIG.selector.excludeContainer)) {
                        return false;
                    }
                    
                    return true;
                });
                
            images.forEach(img => {
                const wrapperClass = isSmallImg ? // 创建外层包裹div
                    'image-box small-box' : 
                    'image-box';
                    
                const wrapper = utils.createElement('div', {
                    className: wrapperClass
                });
                
                img.parentNode.insertBefore(wrapper, img); // 将图片包裹在新div中
                wrapper.appendChild(img);
                
                const titleText = img.getAttribute('title'); // 获取图片的title属性内容并创建标题（如果存在）
                if (titleText) { // 获取图片的style.width设置
                    const imgWidth = img.style.width;
                    const captionAttrs = {
                        className: 'image-caption',
                        textContent: titleText
                    };
                    
                    if (imgWidth) { // 如果图片设置了width，则同步到caption的style中
                        captionAttrs.style = {
                            width: imgWidth
                        };
                    }
                    
                    const caption = utils.createElement('div', captionAttrs);
                    wrapper.appendChild(caption);
                }
            });
        }
    };
    
    // 长图处理模块
    const longImageProcessor = {
        // 预先创建DOM结构
        prepareLongImage(longImg, isSmallImg) {
            const longImgBox = utils.createElement('div', { // 创建长图容器
                className: 'long-img-box'
            });
            
            const titleBar = utils.createElement('div', { // 创建标题栏
                className: 'title-bar',
                style: {
                    cursor: 'pointer'
                }
            });
            
            const icon = utils.createElement('i', { // 创建展开按钮
                className: 'iconfontb',
                innerHTML: CONFIG.longImg.expandIcon
            });
            
            const expandButton = utils.createElement('div', {
                id: 'click-expand'
            }, [
                icon,
                document.createTextNode(CONFIG.longImg.expandText)
            ]);
            
            const wrapper = longImg.parentNode; // 重组DOM结构
            wrapper.insertBefore(longImgBox, longImg);
            wrapper.insertBefore(titleBar, longImg);
            longImgBox.appendChild(longImg);
            
            const existingCaption = wrapper.querySelector('.image-caption'); // 获取已存在的image-caption并移动到title-bar中
            if (existingCaption) {
                titleBar.appendChild(existingCaption);
            }
            
            titleBar.appendChild(expandButton);
            
            // 存储图片引用以便后续处理
            longImg.setAttribute('data-processed', 'false');
            
            titleBar.addEventListener('click', () => { // 添加点击展开功能
                if (longImg.getAttribute('data-processed') !== 'true') return;
                
                if (longImgBox.classList.contains('caption-img')) {
                    longImgBox.classList.remove('caption-img');
                    longImgBox.style.removeProperty('max-height');
                    icon.style.transform = 'rotate(0deg)';
                    expandButton.lastChild.textContent = CONFIG.longImg.expandText;
                } else {
                    const imgHeight = longImg.getAttribute('data-height');
                    longImgBox.style.maxHeight = imgHeight + 'px';
                    longImgBox.classList.add('caption-img');
                    icon.style.transform = 'rotate(180deg)';
                    expandButton.lastChild.textContent = CONFIG.longImg.collapseText;
                }
            });
            
            if (isSmallImg && wrapper.classList.contains('image-box')) { // 如果是小图模式，添加small-box类
                wrapper.classList.add('small-box');
            }
        },
        
        // 计算尺寸并设置过渡效果
        setDimensions(longImg) {
            const imgHeight = longImg.offsetHeight;
            longImg.setAttribute('data-height', imgHeight);
            longImg.setAttribute('data-processed', 'true');
            
            const transitionDuration = utils.calculateTransitionDuration(imgHeight);
            const longImgBox = longImg.closest('.long-img-box');
            
            if (longImgBox) {
                longImgBox.style.transition = `all ${transitionDuration} ease`;
                longImgBox.style.WebkitTransition = `all ${transitionDuration} ease`;
            }
        }
    };
    
    // 小图模式UI模块
    const smallImgUI = {
        create(isSmallImg) { // 创建小图模式UI
            // 检查是否为移动设备
            const isMobile = window.matchMedia('(max-width: 768px)').matches;
            
            // 如果不是小图模式或是移动设备，则不创建UI
            if (!isSmallImg || isMobile) return;
            
            this.createFloatBar();
        },
        
        createFloatBar() { // 创建悬浮栏
            if (document.querySelector('.img-float-bar')) return;
            
            const floatBar = utils.createElement('div', { // 创建悬浮栏
                className: 'img-float-bar',
                id: CONFIG.smallImgUI.floatBarId
            });
            
            const floatKeepIcon = utils.createElement('i', { // 创建保持预览按钮
                className: 'iconfontb',
                innerHTML: CONFIG.smallImgUI.keepIcon
            });
            
            const floatKeepBtn = utils.createElement('button', {
                className: 'float-btn keep-preview',
                title: CONFIG.smallImgUI.floatKeepTitle
            }, floatKeepIcon);
            
            const floatSwitchIcon = utils.createElement('i', { // 创建切换大图按钮
                className: 'iconfontb',
                innerHTML: CONFIG.smallImgUI.switchIcon
            });
            
            const floatSwitchBtn = utils.createElement('button', {
                className: 'float-btn switch-large',
                title: CONFIG.smallImgUI.floatSwitchTitle
            }, floatSwitchIcon);
            
            floatKeepBtn.addEventListener('click', () => smallImgMode.toggleMode(true)); // 绑定事件
            floatSwitchBtn.addEventListener('click', () => smallImgMode.toggleMode(false));
            
            floatBar.appendChild(floatKeepBtn); // 组装悬浮栏
            floatBar.appendChild(floatSwitchBtn);
            document.body.appendChild(floatBar);
        }
    };
    
    // 主程序初始化
    const init = () => {
        const isSmallImg = smallImgMode.detect(); // 检测是否为小图模式
        
        normalImageProcessor.process(isSmallImg); // 处理普通图片
        
        // 预先处理长图DOM结构
        const longImgs = Array.from(document.querySelectorAll(CONFIG.selector.longImg))
            .filter(img => {
                // 排除特定ID的图片
                if (img.id === CONFIG.selector.excludeImgId) {
                    return false;
                }
                
                // 排除diy-container内部的图片
                if (CONFIG.selector.excludeContainer && img.closest(CONFIG.selector.excludeContainer)) {
                    return false;
                }
                
                return true;
            });
            
        if (longImgs.length > 0) {
            longImgs.forEach(longImg => longImageProcessor.prepareLongImage(longImg, isSmallImg));
        }
        
        smallImgMode.applyToContainers(isSmallImg); // 应用小图模式到容器
        
        smallImgUI.create(isSmallImg); // 创建小图模式UI
    };
    
    init(); // 启动程序

    // 使用window.onload确保在所有图片和资源加载完成后再处理长图尺寸
    window.addEventListener('load', () => {
        const longImgs = Array.from(document.querySelectorAll(CONFIG.selector.longImg))
            .filter(img => {
                // 排除特定ID的图片
                if (img.id === CONFIG.selector.excludeImgId) {
                    return false;
                }
                
                // 排除diy-container内部的图片
                if (CONFIG.selector.excludeContainer && img.closest(CONFIG.selector.excludeContainer)) {
                    return false;
                }
                
                return true;
            });
            
        if (longImgs.length > 0) {
            longImgs.forEach(longImg => longImageProcessor.setDimensions(longImg));
        }
    });
});