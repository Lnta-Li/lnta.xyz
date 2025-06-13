# notice.js 说明文档

## 1. 功能概述

notice.js 是一个通用的网页通知管理模块，用于统一管理网页中的通知消息展示。该模块通过简洁的接口，提供了灵活的通知显示功能，支持多种位置定位、自动隐藏、图标自定义等特性，适用于各类网页应用中的消息提示需求。

### 1.1 主要特性

- **统一管理**：集中处理所有网页通知，避免重复代码
- **灵活定位**：支持多种预设位置和相对元素定位
- **响应式设计**：自动适配移动设备
- **自定义配置**：可定制图标、文本、显示时间等
- **简洁接口**：提供简单易用的API
- **Promise支持**：通知显示过程可通过Promise链式操作

## 2. 使用方法

### 2.1 基本用法

在HTML页面中引入notice.js文件：

```html
<script src="/templets/default/js/notice.js"></script>
```

#### 2.1.1 快速显示通知

最简单的使用方式是调用`quickShow`方法：

```javascript
// 显示一条简单通知
NoticeManager.quickShow("操作成功");

// 显示带自定义图标的通知
NoticeManager.quickShow("操作成功", "&#xe652;");
```

#### 2.1.2 自定义通知

使用`show`方法可以自定义更多参数：

```javascript
// 显示一条自定义通知
NoticeManager.show({
    text: "文件上传成功",
    icon: "&#xe652;",
    position: "top-right",
    autoHideDelay: 3
});
```

### 2.2 高级用法

#### 2.2.1 相对元素定位

可以将通知显示在特定DOM元素附近：

```javascript
// 在ID为"submitButton"的元素下方显示通知
NoticeManager.show({
    text: "提交成功",
    targetId: "submitButton",
    positionMode: "below-center"
});
```

#### 2.2.2 使用Promise

通知方法返回Promise，可用于链式操作：

```javascript
// 显示通知后执行其他操作
NoticeManager.show({
    text: "正在处理...",
    autoHideDelay: 2
}).then(() => {
    console.log("通知已关闭，继续执行后续操作");
    // 执行后续操作
});
```

## 3. API参数说明

### 3.1 NoticeManager.show(options)

显示一条自定义通知，返回Promise对象。

| 参数名 | 类型 | 默认值 | 说明 |
| --- | --- | --- | --- |
| options.text | string | "通知消息" | 通知文本内容 |
| options.icon | string | "&#xe651;" | 通知图标的HTML编码 |
| options.position | string | "bottom-right" | 通知位置，可选值：top-right, top-left, bottom-right, bottom-left |
| options.targetId | string | null | 目标元素ID，设置后通知将相对于该元素定位 |
| options.positionMode | string | "default" | 相对定位模式，可选值：above-right, below-left, above-left, below-right, above-center, below-center, center |
| options.showDelay | number | 0 | 显示延时（秒） |
| options.autoHideDelay | number | 5 | 自动隐藏时间（秒） |
| options.removeDelay | number | 0.5 | 隐藏后销毁延时（秒） |

### 3.2 NoticeManager.quickShow(text, icon)

快速显示一条通知，返回Promise对象。

| 参数名 | 类型 | 默认值 | 说明 |
| --- | --- | --- | --- |
| text | string | "通知消息" | 通知文本内容 |
| icon | string | "&#xe651;" | 通知图标的HTML编码 |

## 4. 实现原理

### 4.1 模块化结构

notice.js采用立即执行函数表达式(IIFE)封装，避免全局变量污染：

```javascript
const NoticeManager = (function() {
    // 模块内部代码
    return {
        // 公开API
        show: function() { /*...*/ },
        quickShow: function() { /*...*/ }
    };
})();
```

### 4.2 核心实现流程

1. **配置管理**：合并默认配置与用户自定义配置
2. **DOM构建**：动态创建通知元素及其子元素
3. **位置计算**：根据配置计算通知显示位置
4. **显示控制**：通过CSS类控制通知的显示和隐藏
5. **生命周期管理**：处理通知的延时显示、自动隐藏和销毁

### 4.3 响应式设计

通过媒体查询检测移动设备，并自动调整通知位置：

```javascript
const isMobile = window.matchMedia('(max-width: 768px)').matches;
if (isMobile) {
    noticeElement.style.bottom = '20px';
    return;
}
```

### 4.4 位置定位策略

- **固定位置**：通过position、top、right等CSS属性实现
- **相对元素定位**：通过getBoundingClientRect()获取目标元素位置，计算通知元素的相对位置

## 5. 注意事项

### 5.1 使用注意

- **图标编码**：图标使用HTML编码，需确保页面已引入对应的图标字体
- **样式依赖**：需要配套的CSS样式支持，确保.notice-bubble等类已定义
- **移动设备**：在移动设备上，通知将始终显示在屏幕底部中央，忽略position和positionMode设置

### 5.2 二次开发建议

- **扩展通知类型**：可添加success、error、warning等预设类型
- **增加交互功能**：可添加关闭按钮、点击回调等
- **动画效果增强**：可自定义进入和退出动画
- **队列管理**：可实现通知队列，避免多条通知重叠

### 5.3 兼容性考虑

- 代码使用ES6特性，如箭头函数、Promise等，需确保目标浏览器支持
- 使用了getBoundingClientRect()方法，在较旧浏览器中可能需要polyfill

## 6. 代码编写规范

### 6.1 模块化编写规则

- **使用立即执行函数表达式(IIFE)**：避免全局变量污染
- **功能内聚**：每个函数职责单一，只处理特定功能
- **配置集中**：默认配置统一管理，便于维护

### 6.2 命名规范

- **变量命名**：使用驼峰命名法，如noticeElement
- **常量命名**：使用全大写，如DEFAULT_CONFIG
- **函数命名**：使用动词前缀，如createNotice、hideNotice

### 6.3 代码风格规范

- **紧凑布局**：仅在不同功能模块间使用换行分隔
- **内聚摆放**：相关代码放在一起，保持代码块的内聚性
- **精简代码**：移除多余的空行和缩进，保持可读性
- **直观清晰**：代码结构直观反映逻辑关系
- **统一风格**：整个文件保持一致的缩进和格式风格
- **精确注释**：使用精准的注释说明主要逻辑

## 7. 更新与维护

- 遵循语义化版本控制
- 代码更新前必须理解现有结构
- 保持接口一致性，避免破坏性变更
- 添加新功能时，确保与现有架构风格一致