class ParticleSystem {
    constructor(canvas) {
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
        
        // 动画参数配置
        this.config = {
            // 粒子水平偏移量：粒子生成时向左偏移的像素距离，建议范围：30-100
            particleOffsetX: 0,

            // 粒子数量：点赞时产生的粒子数，建议范围：5-30
            particleCount: 20,
            
            // 粒子大小范围：最小值3，最大值会在此基础上增加该范围值，建议范围：3-10
            particleSizeRange: 6,
            
            // 粒子水平速度范围：实际速度为 (-range/2 ~ range/2)，建议范围：2-8
            particleSpeedX: 3,
            
            // 粒子初始垂直速度范围：实际速度为 (-range/2 ~ range/2) - 2，建议范围：2-8
            particleSpeedY: 3,
            
            // 重力效果：每帧垂直速度增加值，建议范围：0.02-0.1
            gravity: 0.08,
            
            // 旋转速度范围：实际速度为 (-range/2 ~ range/2)，建议范围：2-8
            rotationSpeed: 4,
            
            // 透明度衰减速度：每帧透明度减少值，建议范围：0.01-0.05
            alphaDecay: 0.02
        };

        this.particles = [];
        this.isAnimating = false;
        
        // 粒子颜色配置：可以根据需要调整颜色
        this.colors = [
            '#ff7676', '#f87171', '#fb7185',  // 红色系
            '#fcd34d', '#fbbf24', '#f59e0b',  // 黄色系
            '#4ade80', '#34d399', '#10b981',  // 绿色系
            '#ff234c', '#ff3023', '#ff0000',  // 蓝色系
            '#e879f9', '#d946ef', '#c026d3'   // 紫色系
        ];
        
        // 粒子形状配置：可以根据需要增减形状
        this.shapes = ['circle', 'triangle', 'rectangle', 'heart'];
    }

    createParticle(x, y) {
        return {
            x: x - this.config.particleOffsetX,
            y,
            size: Math.random() * this.config.particleSizeRange + 3,
            speedX: (Math.random() - 0.5) * this.config.particleSpeedX,
            speedY: (Math.random() - 0.5) * this.config.particleSpeedY - 2,
            color: this.colors[Math.floor(Math.random() * this.colors.length)],
            alpha: 1,
            rotation: Math.random() * 360,
            rotationSpeed: (Math.random() - 0.5) * this.config.rotationSpeed,
            shape: this.shapes[Math.floor(Math.random() * this.shapes.length)]
        };
    }

    emit(x, y) {
        // 清除之前的动画
        if (this.isAnimating) {
            this.particles = [];
            cancelAnimationFrame(this.animationFrame);
        }

        // 创建新的粒子
        for (let i = 0; i < this.config.particleCount; i++) {
            this.particles.push(this.createParticle(x, y));
        }

        this.isAnimating = true;
        this.animate();
    }

    drawParticle(p) {
        this.ctx.save();
        this.ctx.translate(p.x, p.y);
        this.ctx.rotate(p.rotation * Math.PI / 180);
        this.ctx.globalAlpha = p.alpha;
        this.ctx.fillStyle = p.color;

        switch (p.shape) {
            case 'circle':
                this.ctx.beginPath();
                this.ctx.arc(0, 0, p.size/2, 0, Math.PI * 2);
                break;
            case 'triangle':
                this.ctx.beginPath();
                this.ctx.moveTo(0, -p.size/2);
                this.ctx.lineTo(p.size/2, p.size/2);
                this.ctx.lineTo(-p.size/2, p.size/2);
                break;
            case 'rectangle':
                this.ctx.fillRect(-p.size/2, -p.size/2, p.size, p.size);
                break;
            case 'heart':
                this.ctx.beginPath();
                this.ctx.moveTo(0, p.size/4);
                this.ctx.bezierCurveTo(p.size/4, 0, p.size/4, -p.size/2, 0, -p.size/2);
                this.ctx.bezierCurveTo(-p.size/4, -p.size/2, -p.size/4, 0, 0, p.size/4);
                break;
        }

        if (p.shape !== 'rectangle') {
            this.ctx.closePath();
        }
        this.ctx.fill();
        this.ctx.restore();
    }

    animate() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        for (let i = this.particles.length - 1; i >= 0; i--) {
            const p = this.particles[i];

            // 更新位置
            p.x += p.speedX;
            p.y += p.speedY;
            p.speedY += this.config.gravity;
            p.rotation += p.rotationSpeed;
            p.alpha -= this.config.alphaDecay;

            // 绘制粒子
            this.drawParticle(p);

            // 移除消失的粒子
            if (p.alpha <= 0) {
                this.particles.splice(i, 1);
            }
        }

        if (this.particles.length > 0) {
            this.animationFrame = requestAnimationFrame(() => this.animate());
        } else {
            this.isAnimating = false;
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        }
    }
}

// 初始化粒子系统
document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.createElement('canvas');
    canvas.style.cssText = 'position:fixed;top:0;left:0;pointer-events:none;z-index:9999;';
    document.body.appendChild(canvas);

    const resizeCanvas = () => {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    };
    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();

    const particleSystem = new ParticleSystem(canvas);

    // 监听点赞按钮点击
    document.addEventListener('click', (e) => {
        const target = e.target.closest('.stat-item[onclick*="postDigg"]');
        if (target && target.getAttribute('onclick').includes('good')) {
            const rect = target.getBoundingClientRect();
            particleSystem.emit(rect.left + rect.width / 2, rect.top + rect.height / 2);
        }
    });
});