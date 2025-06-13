# list_drawing 页面视差效果说明

本文档旨在解释 `list_image_drawing.htm` 页面中实现的图片背景视差效果，包括其在桌面端和移动端的不同呈现方式以及具体的实现原理。

## 效果展示

### 1. 桌面端 (PC)

- **触发方式**: 鼠标在页面可视区域内移动。
- **效果**: 背景中的多个图层会根据鼠标的移动方向和速度，以不同的速率进行反向位移。距离观察者"较远"（z-index较低，或移动速度较慢）的图层移动幅度小，而"较近"（z-index较高，或移动速度较快，或有缩放）的图层移动幅度大，从而产生立体的深度感和视差效果。例如，`layer1` 设置了 `scale: 1.2`，使其看起来更近，移动更明显。

### 2. 移动端 (手机)

- **触发方式**: 在指定的图片区域（class为 `design` 的容器）内进行触摸拖拽。
- **效果**:
    - 用户手指在图片区域内拖拽时，背景图层会跟随手指的拖拽方向进行位移。
    - 不同图层根据预设的速度系数，产生不同幅度的位移，形成视差。
    - 拖拽位移有一定的范围限制（JavaScript中限制在 -100px 到 100px 之间），防止图层移出过多。
    - 当用户手指抬起（停止拖拽）后，所有图层会自动平滑地返回到其初始位置（`translate(0px, 0px)`）。

## 实现原理

视差效果主要通过 HTML 结构、CSS 样式和 JavaScript 脚本三者结合实现。

### 1. HTML 结构

```html
<!-- 简化结构 -->
<div class="design">
    <div class="parallax-layer" id="layer1"><img src="path/to/image1.png" alt="图层1"></div>
    <div class="parallax-layer" id="layer2"><img src="path/to/image2.png" alt="图层2"></div>
    <div class="parallax-layer" id="layer3"><img src="path/to/image3.png" alt="图层3"></div>
    <div class="parallax-layer" id="layer4"><img src="path/to/image4.png" alt="图层4"></div>
    <div class="parallax-layer" id="layer5"><img src="path/to/image5.png" alt="图层5"></div>
</div>
```
- 一个主容器 `div.design`。
- 内部包含多个 `div.parallax-layer` 子元素，每个代表一个视差图层。
- 每个图层内有一个 `img` 标签用于显示图片。

### 2. CSS 样式

关键 CSS 属性：

- **`.parallax-layer`**:
    - `position: absolute;`: 使所有图层可以层叠在同一位置。
    - `top: 0; left: 0; width: 100%; height: 100%;`: 使图层铺满其父容器（`.design`）。
    - `pointer-events: none;`: 允许鼠标事件穿透图层（主要对PC端效果，移动端交互在 `.design` 容器上）。
    - `transition: transform 0.1s ease-out;`: 为图层位移动画提供平滑过渡效果。
    - `z-index`: 通过 `id`选择器（如 `#layer1`, `#layer2`）为不同图层设置不同的 `z-index`，控制它们的堆叠顺序和视觉上的远近。
    - `#layer1 { scale: 1.2; }`: 对特定图层进行缩放，增强近大远小的感觉。
    - `img` 标签通过 `width: 100%; height: 100%; object-fit: cover;` 确保图片填满图层。
- **`.design:hover`**:
    - `cursor: url('/uploads/250317/1-250323225050496.png'), pointer;`: 鼠标悬停在 `.design` 区域时显示自定义光标。
- **移动端特定样式 (`@media screen and (max-width: 768px)`)**:
    - `.parallax-layer { will-change: transform; }`: 提前告知浏览器该元素的 `transform` 属性会变化，有助于性能优化。
    - **注意**: 根据最新的文件更改，原先为 `.design` 容器在移动端设置的 `position: relative; overflow: hidden; height: 300px; touch-action: none;` 等样式已被移除。`touch-action: none;` 的移除可能会影响在某些设备上拖拽的流畅性或导致意外的页面滚动。`overflow: hidden;` 的移除意味着如果图层移动超出 `.design` 容器原始边界，它们将不会被裁剪。

### 3. JavaScript 逻辑

JavaScript 负责动态改变图层的 `transform: translate(Xpx, Ypx)` 样式，以响应用户的输入（鼠标移动或触摸拖拽）。

- **初始化**:
    - DOM加载完成后执行。
    - 获取 `.design` 容器和所有 `.parallax-layer` 元素。
    - 为每个图层定义一个 `speed` 属性，该属性决定了图层相对于用户输入的移动幅度。

- **桌面端 (PC - `mousemove` 事件)**:
    - 监听整个文档的 `mousemove` 事件。
    - 计算鼠标指针相对于窗口中心的位置比例 (`x = e.clientX / window.innerWidth - 0.5`, `y = e.clientY / window.innerHeight - 0.5`)。
    - 根据此比例和每个图层的 `speed`，计算各图层应有的 `translateX` (`-x * layer.speed`) 和 `translateY` (`-y * layer.speed`) 值。移动方向与鼠标方向相反，产生"窥视"效果。
    - 更新图层的 `transform` 样式。

- **移动端 (手机 - `touchstart`, `touchmove`, `touchend` 事件)**:
    - 事件监听器绑定在 `.design` 容器上。
    - `touchstart`:
        - 调用 `event.preventDefault()` 来尝试阻止默认的浏览器触摸行为（如页面滚动）。
        - 记录触摸起始点坐标 (`touchStartX`, `touchStartY`) 和上一次触摸点坐标 (`lastPosX`, `lastPosY`)。
        - 设置 `isMoving` 标记为 `true`。
    - `touchmove`:
        - 如果 `isMoving` 为 `false`，则不执行。
        - 调用 `event.preventDefault()`。
        - 获取当前触摸点坐标，计算手指在屏幕上滑动的距离 (`deltaX`, `deltaY`)。
        - 遍历每个图层：
            - 获取图层当前的 `transform` 值，解析出当前的 `translateX` 和 `translateY`。
            - 计算新的位移：`moveX = currentX + deltaX * layer.speed * 10`，`moveY = currentY + deltaY * layer.speed * 10`。图层跟随手指方向移动，`10` 是一个放大系数。
            - 对计算出的新位移值进行范围限制（例如，水平和垂直方向均在 -100px 到 100px 之间）。
            - 更新图层的 `transform` 样式。
        - 更新 `lastPosX`, `lastPosY` 为当前触摸点坐标。
    - `touchend`:
        - 设置 `isMoving` 标记为 `false`。
        - 触发一个动画，使所有图层平滑地返回到它们的初始位置 (`transform: translate(0px, 0px);`)。此动画有特定的过渡时间（例如 `0.8s`），之后过渡效果恢复为默认的 `0.1s`。

## 总结

该视差效果通过分层图像、CSS绝对定位和变换，以及JavaScript对用户输入的动态响应来实现。桌面端利用鼠标位置提供流畅的背景深度感，移动端则通过触摸拖拽交互和回弹效果，提供了动态且有趣的视觉体验。开发者应注意在移动端 `.design` 容器的CSS样式（特别是其在媒体查询中被移除的 `touch-action` 和 `overflow` 属性）对最终效果和用户体验的潜在影响。 