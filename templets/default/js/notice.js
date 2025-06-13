/**
 * 通用通知管理模块
 * 统一管理网页通知，提供简单的接口调用
 */

const NoticeManager = (function() {
    // 默认配置
    const DEFAULT_CONFIG = {
        position: 'bottom-right', // 默认位置：右下角
        positionMode: 'default', // 默认定位模式
        timeout: {
            show: 0,           // 显示延时（秒）
            autoHide: 5,       // 自动隐藏时间（秒）
            remove: 0.5        // 隐藏后销毁延时（秒）
        },
        ui: {
            noticeIcon: '&#xe651;', // 默认图标编码
            noticeText: '通知消息'   // 默认文本
        },
    };

    // 创建DOM元素的工具函数
    function createElement(tag, attrs = {}, children = []) {
        const element = document.createElement(tag);
        
        // 设置属性
        Object.entries(attrs).forEach(([key, value]) => {
            if (key === 'style' && typeof value === 'object') {
                Object.entries(value).forEach(([prop, val]) => {
                    element.style[prop] = val;
                });
            } else if (key === 'className') {
                element.className = value;
            } else if (key === 'innerHTML') {
                element.innerHTML = value;
            } else {
                element.setAttribute(key, value);
            }
        });
        
        // 添加子元素
        if (Array.isArray(children)) {
            children.forEach(child => {
                if (child) element.appendChild(child);
            });
        } else if (children) {
            element.appendChild(children);
        }
        
        return element;
    }

    // 获取元素位置
    function getElementPosition(elementId) {
        if (!elementId) return null;
        
        const element = document.getElementById(elementId);
        if (!element) return null;
        
        const rect = element.getBoundingClientRect();
        return {
            bottom: rect.bottom + window.pageYOffset,
            left: rect.left + window.pageXOffset,
            width: rect.width
        };
    }

    // 定位通知元素
    function positionNotice(noticeElement, position, targetId, positionMode) {
        // 设置为固定定位，不随页面滚动
        noticeElement.style.position = 'fixed';
        
        // 检查是否为移动设备（屏幕宽度 ≤ 768px）
        const isMobile = window.matchMedia('(max-width: 768px)').matches;
        
        // 如果是移动设备，忽略positionMode和position参数，直接设置在屏幕下方居中
        if (isMobile) {
            noticeElement.style.bottom = '20px';
            return;
        }
        
        // 如果指定了目标元素ID，则计算相对于视口的位置
        if (targetId) {
            const targetElement = document.getElementById(targetId);
            if (targetElement) {
                const rect = targetElement.getBoundingClientRect();
                
                // 根据定位模式设置位置
                switch (positionMode) {
                    case 'above-right':
                        // 放置在目标元素上方，右对齐
                        noticeElement.style.top = `${rect.top - 70}px`;
                        noticeElement.style.left = `${rect.right - noticeElement.offsetWidth}px`;
                        break;
                    case 'below-left':
                        // 放置在目标元素下方，左对齐
                        noticeElement.style.top = `${rect.bottom + 50}px`;
                        noticeElement.style.left = `${rect.left}px`;
                        break;
                    case 'above-left':
                        // 放置在目标元素上方，左对齐
                        noticeElement.style.top = `${rect.top - 70}px`;
                        noticeElement.style.left = `${rect.left}px`;
                        break;
                    case 'below-right':
                        // 放置在目标元素下方，右对齐
                        noticeElement.style.top = `${rect.bottom + 50}px`;
                        noticeElement.style.left = `${rect.right - noticeElement.offsetWidth}px`;
                        break;
                    case 'above-center':
                        // 放置在目标元素上方，水平居中
                        noticeElement.style.top = `${rect.top - 70}px`;
                        noticeElement.style.left = `${rect.left + (rect.width / 2) - (noticeElement.offsetWidth / 2)}px`;
                        break;
                    case 'below-center':
                        // 放置在目标元素下方，水平居中
                        noticeElement.style.top = `${rect.bottom + 50}px`;
                        noticeElement.style.left = `${rect.left + (rect.width / 2) - (noticeElement.offsetWidth / 2)}px`;
                        break;
                    case 'center':
                        // 放置在目标元素中央，水平居中
                        noticeElement.style.top = `${rect.top + (rect.height / 2) - (noticeElement.offsetHeight / 2)}px`;
                        noticeElement.style.left = `${rect.left + (rect.width / 2) - (noticeElement.offsetWidth / 2)}px`;
                        break;
                    default:
                        // 默认放置在目标元素下方，左对齐
                        noticeElement.style.top = `${rect.bottom + 50}px`;
                        noticeElement.style.left = `${rect.left}px`;
                        break;
                }
                return;
            }
        }
        
        // 根据position参数设置位置
        switch (position) {
            case 'top-right':
                noticeElement.style.top = '32px';
                noticeElement.style.right = '32px';
                break;
            case 'top-left':
                noticeElement.style.top = '32px';
                noticeElement.style.left = '32px';
                break;
            case 'bottom-left':
                noticeElement.style.bottom = '32px';
                noticeElement.style.left = '32px';
                break;
            case 'bottom-right':
            default:
                noticeElement.style.bottom = '32px';
                noticeElement.style.right = '32px';
                break;
        }
    }

    // 创建通知
    function createNotice(options = {}) {
        // 合并配置项
        const config = {
            position: options.position || DEFAULT_CONFIG.position,
            positionMode: options.positionMode || DEFAULT_CONFIG.positionMode,
            timeout: {
                show: options.showDelay !== undefined ? options.showDelay : DEFAULT_CONFIG.timeout.show,
                autoHide: options.autoHideDelay !== undefined ? options.autoHideDelay : DEFAULT_CONFIG.timeout.autoHide,
                remove: options.removeDelay !== undefined ? options.removeDelay : DEFAULT_CONFIG.timeout.remove
            },
            transition: DEFAULT_CONFIG.transition,
            ui: {
                noticeIcon: options.icon || DEFAULT_CONFIG.ui.noticeIcon,
                noticeText: options.text || DEFAULT_CONFIG.ui.noticeText
            },
            targetId: options.targetId || null
        };

        // 创建通知容器
        const noticeElement = createElement('div', {
            className: 'notice-bubble',
        });
        
        // 创建图标
        const iconElement = createElement('i', {
            className: 'iconfontb',
            innerHTML: config.ui.noticeIcon
        });
        
        // 创建文本
        const textElement = createElement('span', {
            className: 'notice-text-content'
        });
        textElement.textContent = config.ui.noticeText;
        
        // 包装在notice-text div中
        const noticeTextDiv = createElement('div', {
            className: 'notice-text'
        });
        noticeTextDiv.appendChild(iconElement);
        noticeTextDiv.appendChild(textElement);
        
        // 组装通知
        noticeElement.appendChild(noticeTextDiv);
        
        // 添加到页面
        document.body.appendChild(noticeElement);
        
        // 设置位置
        positionNotice(noticeElement, config.position, config.targetId, config.positionMode);
        
        // 使用Promise包装通知的显示和隐藏过程
        return new Promise((resolve) => {
            // 延迟显示
            setTimeout(() => {
                // 添加active类来显示通知
                noticeElement.classList.add('active');
                
                // 如果设置了自动隐藏时间
                if (config.timeout.autoHide > 0) {
                    setTimeout(() => {
                        hideNotice(noticeElement, config.timeout.remove);
                        resolve();
                    }, config.timeout.autoHide * 1000);
                }
            }, config.timeout.show * 1000);
        });
    }

    // 隐藏通知
    function hideNotice(noticeElement, removeDelay = 0.5) {
        // 移除active类来隐藏通知
        noticeElement.classList.remove('active');
        
        // 延迟销毁DOM
        setTimeout(() => {
            noticeElement.remove();
        }, removeDelay * 1000);
    }

    // 公开API
    return {
        /**
         * 显示通知
         * @param {Object} options - 通知配置选项
         * @param {string} [options.text] - 通知文本
         * @param {string} [options.icon] - 通知图标HTML编码
         * @param {string} [options.position] - 通知位置 (top-right, top-left, bottom-right, bottom-left)
         * @param {string} [options.targetId] - 目标元素ID，如果设置，通知将显示在该元素下方
         * @param {number} [options.showDelay] - 显示延时（秒）
         * @param {number} [options.autoHideDelay] - 自动隐藏时间（秒）
         * @param {number} [options.removeDelay] - 隐藏后销毁延时（秒）
         * @returns {Promise} - 返回Promise，通知隐藏后resolve
         */
        show: function(options = {}) {
            return createNotice(options);
        },
        
        /**
         * 快速显示通知
         * @param {string} text - 通知文本
         * @param {string} [icon] - 通知图标HTML编码
         * @returns {Promise} - 返回Promise，通知隐藏后resolve
         */
        quickShow: function(text, icon) {
            return createNotice({
                text: text,
                icon: icon
            });
        }
    };
})();

// 如果支持模块导出，则导出NoticeManager
if (typeof module !== 'undefined' && typeof module.exports !== 'undefined') {
    module.exports = NoticeManager;
} 