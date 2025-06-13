// 移动端菜单控制脚本
document.addEventListener('DOMContentLoaded', function() {
    // 获取菜单切换按钮和导航菜单
    var menuToggle = document.querySelector('.mobile-menu-toggle');
    var mobileNav = document.querySelector('.mobile-nav');
    var pageBody = document.getElementById('pagebody');
    
    // 初始化transform-origin
    updateTransformOrigin();
    
    // 监听页面滚动，实时更新transform-origin
    // 防抖函数
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }
    
    // 更新transform-origin的函数
    function updateTransformOrigin() {
        if (pageBody) {
            // Y轴根据滚动位置和视口中心计算
            var y = window.scrollY + window.innerHeight / 2;
            // X轴始终为右侧，Y轴动态更新
            pageBody.style.transformOrigin = 'center ' + y + 'px';
        }
    }
    
    // 使用防抖函数包装updateTransformOrigin，设置200ms的延迟
    window.addEventListener('scroll', debounce(updateTransformOrigin, 200));
    
    // 创建一个遮罩层，用于在菜单打开时屏蔽背景
    var overlay = document.createElement('div');
    overlay.className = 'mobile-menu-overlay';
    document.body.appendChild(overlay);
    
    // 切换菜单状态的函数
    function toggleMenu(show) {
        if (show === undefined) {
            // 如果未指定，则切换当前状态
            mobileNav.classList.toggle('active');
            if (pageBody) {
                pageBody.classList.toggle('nav');
            }
            overlay.classList.toggle('active');
        } else if (show) {
            // 显示菜单
            mobileNav.classList.add('active');
            if (pageBody) {
                pageBody.classList.add('nav');
            }
            overlay.classList.add('active');
            // 禁止背景滚动
            document.body.style.overflow = 'hidden';
        } else {
            // 隐藏菜单
            mobileNav.classList.remove('active');
            if (pageBody) {
                pageBody.classList.remove('nav');
            }
            overlay.classList.remove('active');
            // 恢复背景滚动
            document.body.style.overflow = '';
        }
        
        // 更新菜单按钮样式
        var spans = menuToggle.querySelectorAll('span');
        if (mobileNav.classList.contains('active')) {
            spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
            spans[1].style.width = '0';
            spans[1].style.opacity = '0';
            spans[2].style.transform = 'rotate(-45deg) translate(7px, -7px)';
        } else {
            spans[0].style.transform = 'none';
            spans[1].style.width = '100%';
            spans[1].style.opacity = '1';
            spans[2].style.transform = 'none';
        }
    }
    
    if (menuToggle && mobileNav) {
        // 点击菜单按钮时切换导航菜单的显示状态
        menuToggle.addEventListener('click', function(event) {
            event.stopPropagation(); // 阻止事件冒泡
            toggleMenu();
        });
        
        // 点击遮罩层时关闭菜单
        overlay.addEventListener('click', function() {
            toggleMenu(false);
        });
        
        // 点击菜单内部不关闭菜单
        mobileNav.addEventListener('click', function(event) {
            event.stopPropagation(); // 阻止事件冒泡
        });
        
        // 添加触摸事件监听
        overlay.addEventListener('touchstart', function(event) {
            event.preventDefault(); // 阻止默认行为
            toggleMenu(false);
        });
        
        // ESC键关闭菜单
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && mobileNav.classList.contains('active')) {
                toggleMenu(false);
            }
        });
    }
});