#Win10子系统 Ubuntu 18.04配置nginx+laravelS
####一. 配置环境
1. php 7.4.13
2. swoole 4.5.9
3. nginx 1.14.0
4. laravel 7.30.1
5. laravelS 3.7.10
6. composer 1.6.3
####二. 配置步骤
1. 使用apt安装nginx、php、composer
2. 安装php-pear、php-dev扩展
3. 安装swoole：`pecl install swoole`，在php.ini中添加`extension=swoole.so`
4. composer安装laravel installer：`composer global require laravel/installer`
5. 创建项目文件夹：`mkdir laravelS_test`
6. 在项目文件夹内，安装laravel：`composer create-project -vvv --prefer-dist laravel/laravel laravelS_test`
7. 安装laravelS：`composer -vvv require hhxsv5/laravel-s`
8. 在`config/app.php`中的`providers`添加提供者：`Hhxsv5\LaravelS\Illuminate\LaravelSServiceProvider::class`。如果已经存在则不用添加
9. 发布配置文件：`php artisan laravels publish`
10. 安装php-inotify扩展：
    1. 到 [inotify页面](https://pecl.php.net/package/inotify) 下载对应版本的包，如**inotify-3.0.0.tgz**
    2. 解压文件：`tar zxvf inotify-3.0.0.tgz`
    3. `cd inotify-3.0.0`
    4. `phpize`
    5. 查看php-config路径：`whereis php-config`，一般直接使用第一个
    6. `./configure --with-php-config=/usr/bin/php-config --enable-inotify`此处 **--with-php-config** 填入第5步获得的路径
    7. `make && make install`
    8. 在php.ini中添加`extension=inotify.so`
11. 添加.env中的laravels选项
    + `LARAVELS_LISTEN_IP=127.0.0.9`  这里的ip跟后面nginx配置要一致
    + `LARAVELS_LISTEN_PORT=5200`     laravelS监听端口，默认5200
    + `LARAVELS_DAEMONIZE=true`       laravelS以守护进程启动
    + `LARAVELS_INOTIFY_RELOAD=true`  开启修改文件自动重载
    + `LARAVELS_HANDLE_STATIC=false`  是否使用laravelS处理静态文件，一般使用nginx处理，所以选false
12. .env中需要配置APP_KEY才能正常打开项目，以`base64:`开头，后面跟任意base64字符串
13. 第一次开启laravelS：
    1. 进入项目根目录
    2. `php bin/laravels start`
    3. 最后可以看到 `Swoole is running in daemon mode, see "ps -ef|grep laravels".`，即为启动成功。
    4. win10浏览器，打开`127.0.0.9:5200`，即可看见laravel默认页。
14. 配置nginx
    1. `cd /etc/nginx/sites-enabled`
    2. `rm default`
    3. `vim laravelS`
    4. 粘贴以下内容，并保存退出：
    ```
    upstream laravels {
        #ip与端口要与项目中的.env文件laravels配置相同
        server 127.0.0.9:5200 weight=5 max_fails=3 fail_timeout=30s;
        keepalive 16;
    }
    server {
        listen 80;
        
        server_name laravels.com;
        root /var/www/html/laravelS_test/public;
        index index.php index.html index.htm;
        
        # Nginx 处理静态资源，LaravelS 处理动态资源
        location / {
            try_files $uri @laravels;
        }
        
        location @laravels {
            proxy_http_version 1.1;
            proxy_set_header Connection "";
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Real-PORT $remote_port;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
            proxy_set_header Host $http_host;
            proxy_set_header Scheme $scheme;
            proxy_set_header Server-Protocol $server_protocol;
            proxy_set_header Server-Name $server_name;
            proxy_set_header Server-Addr $server_addr;
            proxy_set_header Server-Port $server_port;
            proxy_pass http://laravels;
        }
    }
    ```
    5. `cd .. && vim nginx.conf`
    6. 修改nginx启动用户： `user root;`（之前因为这里没有修改，导致静态文件一直获取不了，一直以为是server配置问题，最后看了nginx错误日志才知道没有权限）
    7. 启动gzip，根据需要调整配置项
    ```
    gzip on;
    gzip_vary on;
    # gzip_proxied any;
    gzip_comp_level 2;
    # gzip_buffers 16 8k;
    # gzip_http_version 1.1;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
    ```
    8. 保存退出后，启动nginx：`service nginx start`
    9. win10绑定hosts：`127.0.0.9 laravels.com`，浏览器打开`laravels.com`，能看见laravel默认首页
