/** 图片轮播组件 */
class ImageCarousel {
    constructor(container) {
        this.container = container;
        this.items = container.querySelectorAll('.carousel-item');
        this.currentIndex = 0;
        this.totalItems = this.items.length;

        // 立即初始化UI，不等待图片加载
        this.createNavigationButtons();
        this.createDots();
        this.updateCarousel();
        this.addEventListeners();
            
        // 存储实例引用到DOM元素上，以便外部访问
        container._carouselInstance = this;
            
        // 等待图片加载完成后再计算图片比例
        this.waitForImages().then(() => {
            this.calculateAspectRatios();
            // 重新更新一次轮播以应用计算后的样式
            this.updateCarousel();
        });
    }

    createNavigationButtons() {
        const prevBtn = document.createElement('button');
        prevBtn.className = 'carousel-nav carousel-prev';
        prevBtn.innerHTML = `<svg viewBox="0 0 24 24" width="24" height="24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z" fill="currentColor"/></svg>`;

        const nextBtn = document.createElement('button');
        nextBtn.className = 'carousel-nav carousel-next';
        nextBtn.innerHTML = `<svg viewBox="0 0 24 24" width="24" height="24"><path d="M8.59 16.59L10 18l6-6-6-6-1.41 1.41L13.17 12z" fill="currentColor"/></svg>`;

        this.container.appendChild(prevBtn);
        this.container.appendChild(nextBtn);

        this.prevBtn = prevBtn;
        this.nextBtn = nextBtn;
    }

    createDots() {
        const dotsContainer = document.createElement('div');
        dotsContainer.className = 'carousel-dots';

        for (let i = 0; i < this.totalItems; i++) {
            const dot = document.createElement('div');
            dot.className = 'carousel-dot';
            dot.dataset.index = i;
            // 从img标签的alt属性获取描述文本
            const img = this.items[i].querySelector('img');
            if (img && img.alt) {
                dot.textContent = img.alt;
            }
            dotsContainer.appendChild(dot);
        }

        this.container.appendChild(dotsContainer);
        this.dots = dotsContainer.querySelectorAll('.carousel-dot');
    }

    updateCarousel() {
        // 更新容器位置
        const container = this.container.querySelector('.carousel-container');
        // 添加过渡动画属性
        container.style.transition = 'transform 0.3s';
        container.style.transform = `translateX(-${this.currentIndex * 100}%)`;

        // 更新指示器圆点
        this.dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === this.currentIndex);
            // 根据alt文本长度设置宽度和样式
            if (index === this.currentIndex) {
                const img = this.items[index].querySelector('img');
                const altLength = img && img.alt ? img.alt.length : 0;
                if (altLength === 0) {
                    dot.style.cssText = 'padding: 0;background: #fff; border: none;';
                } else {
                    const width = Math.min(altLength * 14, 200);
                    dot.style.width = `${width}px`;
                }
            } else {
                dot.style.cssText = '';
            }
        });
    }

    addEventListeners() {
        this.prevBtn.addEventListener('click', () => this.prev());
        this.nextBtn.addEventListener('click', () => this.next());

        this.dots.forEach(dot => {
            dot.addEventListener('click', () => {
                const index = parseInt(dot.dataset.index);
                this.goToSlide(index);
            });
        });

        // 统一处理拖拽和触摸事件
        const container = this.container.querySelector('.carousel-container');
        container.setAttribute('draggable', 'false');
        container.style.userSelect = 'none';

        let isDragging = false;
        let startPos = 0;
        let currentTranslate = 0;

        // 通用事件处理函数
        const handleStart = (pos) => {
            isDragging = true;
            startPos = pos;
            currentTranslate = -this.currentIndex * 100;
            container.style.transition = 'none';
        };

        const handleMove = (pos) => {
            if (!isDragging) return;
            const delta = pos - startPos;
            const movePercent = (delta / container.offsetWidth) * 100;
            container.style.transform = `translateX(${currentTranslate + movePercent}%)`;
        };

        const handleEnd = (pos) => {
            if (!isDragging) return;
            isDragging = false;
            container.style.transition = 'transform 0.3s';

            const delta = pos - startPos;
            const movePercent = (delta / container.offsetWidth) * 100;

            if (Math.abs(movePercent) > 20) {
                if (movePercent > 0 && this.currentIndex > 0) {
                    this.prev();
                } else if (movePercent < 0 && this.currentIndex < this.totalItems - 1) {
                    this.next();
                } else {
                    this.updateCarousel();
                }
            } else {
                this.updateCarousel();
            }
        };

        // 鼠标事件
        container.addEventListener('mousedown', (e) => {
            e.preventDefault();
            handleStart(e.clientX);
        });

        document.addEventListener('mousemove', (e) => {
            handleMove(e.clientX);
        });

        document.addEventListener('mouseup', (e) => {
            handleEnd(e.clientX);
        });

        // 触摸事件
        let startY = 0;
        let isVerticalScroll = false;

        container.addEventListener('touchstart', (e) => {
            startY = e.touches[0].clientY;
            handleStart(e.touches[0].clientX);
        }, { passive: false });

        this.container.addEventListener('touchmove', (e) => {
            const currentY = e.touches[0].clientY;
            const deltaY = Math.abs(currentY - startY);
            const deltaX = Math.abs(e.touches[0].clientX - startPos);

            // 如果垂直滑动距离大于水平滑动距离，则认为是垂直滑动
            if (deltaY > deltaX) {
                isVerticalScroll = true;
                return; // 允许垂直滑动
            }

            isVerticalScroll = false;
            e.preventDefault();
            handleMove(e.touches[0].clientX);
        }, { passive: false });

        container.addEventListener('touchend', (e) => {
            if (!isVerticalScroll) {
                e.preventDefault();
                handleEnd(e.changedTouches[0].clientX);
            }
        });

        // 键盘事件
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') {
                this.prev();
            } else if (e.key === 'ArrowRight') {
                this.next();
            }
        });

        // 鼠标滚轮事件
        this.lastWheelTime = 0;
        container.addEventListener('wheel', (e) => {
            e.preventDefault();
            const now = Date.now();
            if (now - this.lastWheelTime > 500) {
                this.lastWheelTime = now;
                e.deltaY < 0 ? this.prev() : this.next();
            }
        });
    }

    prev() {
        if (this.currentIndex > 0) {
            this.currentIndex--;
            this.updateCarousel();
        }
    }

    next() {
        if (this.currentIndex < this.totalItems - 1) {
            this.currentIndex++;
            this.updateCarousel();
        }
    }

    goToSlide(index) {
        this.currentIndex = index;
        this.updateCarousel();
    }

    // 等待所有图片加载完成，增加超时处理
    async waitForImages() {
        const images = Array.from(this.items).map(item => item.querySelector('img'));
        const promises = images.map(img => {
            if (img.complete) return Promise.resolve();
            return new Promise(resolve => {
                // 图片加载成功或失败都会触发resolve
                img.onload = resolve;
                img.onerror = resolve;
                
                // 添加超时处理，0.1秒后自动resolve
                setTimeout(resolve, 100);
            });
        });
        await Promise.all(promises);
    }

    // 计算所有图片的长宽比例并添加标签
    calculateAspectRatios() {
        const images = Array.from(this.items).map(item => item.querySelector('img'));
        const ratios = images.map(img => img.naturalWidth / img.naturalHeight);

        // 找出最大和最小比例
        const maxRatio = Math.max(...ratios);
        const minRatio = Math.min(...ratios);

        // 检查是否存在竖版图片（高度大于宽度）
        const hasPortraitImage = images.some(img => img.naturalHeight > img.naturalWidth);

        // 计算最大差异百分比
        const difference = Math.abs(maxRatio - minRatio) / minRatio * 100;

        // 根据差异和竖版图片判断添加标签
        const className = (difference > 30 || hasPortraitImage) ? 'no-fill' : 'fill';
        this.items.forEach(item => item.classList.add(className));
    }
}

// DOM加载完成后初始化轮播
document.addEventListener('DOMContentLoaded', () => {
    const carouselContainers = document.querySelectorAll('.image-carousel');
    carouselContainers.forEach(container => {
        new ImageCarousel(container);
    });
    
    // 监听外部更新事件
    document.addEventListener('carouselExternalUpdate', (e) => {
        const { carouselElement, targetIndex } = e.detail;
        if (carouselElement && carouselElement._carouselInstance) {
            // 如果能找到轮播图实例，直接调用goToSlide方法
            carouselElement._carouselInstance.currentIndex = targetIndex;
        }
    });
});