#欢迎使用php githook超轻量级自动部署工具 
###如何使用？ 
**您只需要简单的设置即可使用本自动部署工具，甚至无需手动git clone** 

1. 下载本项目的githook.php至需要部署的目录根下，注意**该目录和目录下文件权限均设定为777**。 
> sudo chmod 777 -R /dir/ 
2. 修改githook.php第十行 
> `define('__DEPLOY_DIR__', __DIR__ . '/sync-deploy');` 
将其更改为**之前未创建**的部署文件夹（防止权限问题而造成无法正常运行的问题） 
3. 在任意项目的**git页面**的**Settings**面板中选择**Webhooks & Services**，点击**Add webhook**按钮，在**Payload URL**中填入能够访问到githook.php脚本的地址，例如  。 
> http://****.com/githook.php 
4. ENJOY ;-) 

    
