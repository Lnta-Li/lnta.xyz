<p>/*访问  http://118.178.254.226/  预览<br>/*所有修改代码使用CURSOR、TRAE修改，因为我是设计师不是程序员，不会JS、PHP</p>

<p>使用了织梦（dede）开源网站模板，在其基础上进行修改，主要修改如下：</p>
<p>一、页面美化（主要是美化）</p>
<p>1.网站相关页面模板美化设计；<br>
2.手机端改为自适应，而非单独设计手机端页面模板（通过CSS媒体查询）；<br>
3.网站文章各种格式显示美化（后端编辑器同步）；<br>
4.修改和重做了文章点赞、评论功能，dede原版是论坛样式比较落后，进行了升级改造以及美化，添加了点赞动效、点赞/踩可以取消、互换.</p>

<p>二、功能添加</p>
<p>1.dede原版文档、图集采用的是不同的模板，但其实文档的功能图集中都有，但是一个栏目只能二选一，我希望一个栏目既能发文档又能发图集，所以现在统一改为了使用图集发布，如果后台不上传图集的图片，那么前端自动显示文档页面（缩略图+图文）；如果后台上传了图集图片，那么前端显示图集页面（类似小红书的轮播翻页）；<br>
2.全站伪静态；<br>
3.图片预览器：文章页点击任意图片进入图片预览器，预览器可以切换浏览整个网页的图片，支持缩放平移，手机端支持触摸操作；<br>
4.数据库升级utf8mb4_unicode_ci，支持emoji表情<br>
5.Tag页面增加了一个自动翻译Tag，在中文tag后面添加英文、英文tag后面添加中文，这是调用百度翻译api实现的，api密钥在后台核心设置里面填写（目前只在tag这里用到的，作用不大，后续其他地方有需要可以方便调用）</p>
6.网页昼夜模式切换并保存设置在浏览器Cookie中</p>

<p>三、其他想到了再不定时更新</p>

<p>四、更新日志：</p>

<p>✨待办：</p>
<p>  太好了，所有需求都被我们消灭了！😎😎😎</p>

<p>😃更新记录：</p>
<p>  修复了评论框弹出位置的bug（25.04.03）<br>
  更新了昼夜模式设置开关，现在可以在后台设置一个默认的模式（深色/浅色），页面加载时选择默认的模式，如果用户手动切换了模式，将模式储存在Cookie中，下次直接加载用户的模式（25.04.08）<br>
  发表评论后，背景缩放和虚化效果未消失的bug（25.04.06）<br>
  新增了tag列表瀑布流排列，当tag文章数量小于8使用原来的列表页，大于8篇使用瀑布流排列，最大4列最小2列自适应（25.04.05）<br>
  修复了评论框弹出位置的bug（25.04.03）<br>
  增加了漂亮优雅的手机导航菜单动画效果（25.04.03）<br>
  增加了移动端nav导航的背景毛玻璃效果(25.04.02)<br>
  更新了文章图片预览在手机端可以触摸缩放平移，并美化了手机端显示（25.04.01）<br>
  更新了点赞状态在页面加载前就预先从cookie中读取并加载（25.04.01）<br>
  增加了点赞特效，并将图标改为SVG方便修改效果（25.04.01）<br>
  页面设计美化；改了点样式；删除多余注释代码；CSS更新（25.04.01）<br>
  在所有前端页面添加一个loading动画，在页面顶端优先加载，当页面加载完成后隐藏（效果不好）<br>
  同一图集中不同比例的图片自动适配宽高（25.03.31）<br>
  增加了emoji表情的支持（25.03.31）<br>
  评论点赞Bug修复、体验优化，增加推荐阅读（25.03.31）<br>
  点赞和评论功能上线（25.03.30）<br>
  优化页面加载完成的动画，避免页面闪烁（25.03.29）<br>
  网站所有基本功能完成，Bug修复</p>
