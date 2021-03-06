# Streaming Server Installation

## Prerequisites
- Linux Server with Ubuntu 16.04 or 18.04
- sudo rights to install and compile additional tools
- existing linux user and group www-data

## Create needed folders on the Ubuntu server
**Create folder for video recordings:**  
```
sudo mkdir /video_recordings/mbrdi
```

**Make user www-data and group www-data owner of the folder:**  
```
sudo chown www-data:www-data /video_recordings/mbrdi
```

**Create folder for php and html files:**  
```
sudo mkdir /usr/local/nginx/html
```

**Make user www-data and group www-data owner of the folder:**  
```
sudo chown www-data:www-data /usr/local/nginx/html
```

## Install ffmpeg
```
sudo apt-get update  
sudo apt-get install ffmpeg
```

Create a softlink to ffmpeg in /home/rarents/bin
Create an additional softlink to ffmpeg in /home/rarents/bin and name it avconv

## Install PHP 
```
sudo apt install php7.1-fpm php7.1-mcrypt php7.1-cli php7.1-xml php7.1-mysql php7.1-gd php7.1-imagick php7.1-recode php7.1-tidy php7.1-xmlrpc
```

## Configure PHP
**Add the following line to /etc/php/7.1/fpm/php-fpm.conf to execute PHP Code within HTML files:**  
`sudo nano /etc/php/7.1/fpm/php-fpm.conf`  

`security.limit_extensions = .php .html .js` 

**Set cgi.fix_pathinfo to 0 in /etc/php/7.1/fpm/php.ini**  
```
sudo nano /etc/php/7.1/fpm/php.ini
cgi.fix_pathinfo=0
```

**Then restart php-fpm service:**  
`sudo service php7.1-fpm restart`  

## Compile nginx with rtmp 
**Install the tools required to compile Nginx and Nginx-RTMP from source:**  
```
sudo apt-get update  
sudo apt-get install build-essential libpcre3 libpcre3-dev libssl-dev zlib1g-dev unzip  
```

**Create a working directory and switch to it:**  
```
mkdir ~/working  
cd ~/working  
```

**Download the Nginx and Nginx-RTMP source:**  
```
wget http://nginx.org/download/nginx-1.9.15.tar.gz  
wget https://github.com/arut/nginx-rtmp-module/archive/master.zip  
```

**Extract the Nginx and Nginx-RTMP source:**  
```
tar -zxvf nginx-1.9.15.tar.gz  
unzip master.zip  
```

**Switch to the Nginx directory:**  
```
cd nginx-1.9.15  
```

**Add modules that Nginx will be compiled with. Nginx-RTMP is included:**  
```
./configure --with-http_ssl_module --with-http_stub_status_module --add-module=../nginx-rtmp-module-master  
```

**Compile and install Nginx with Nginx-RTMP:**  
```
make
sudo make install
```

**Install the Nginx init scripts:**  
```
sudo wget https://raw.github.com/JasonGiedymin/nginx-init-ubuntu/master/nginx -O /etc/init.d/nginx
sudo chmod +x /etc/init.d/nginx
sudo update-rc.d nginx defaults
```

**Start and stop Nginx to generate configuration files:**  
```
sudo service nginx start
sudo service nginx stop
```

## Configuration of nginx
**Edit the nginx configuration file:**  
```
sudo nano /usr/local/nginx/conf/nginx.conf
```

**Ensure that nginx is running under user www-data by adding/replacing the first line of the configuration file:** 
```
user  www-data;
```

**In the RTMP section add the marked configurations:**  

```
rtmp {
 server {
  listen 1935;
  allow play all;

  # ----- Start of configuration to add -----
  # Live Videostream Configuration for the location "mbrdi"
  application mbrdi {
   allow play all;
   live on;
   record all;
   record_suffix -%A-%d-%m-%y-%H-%M.flv;
   record_max_size 3000M;
   record_path /video_recordings/mbrdi;
   record_unique on;
   hls on;
   hls_nested on;
   hls_path /HLS/mbrdi;
   hls_fragment 10s;
   exec_options on;
   #After finishing the flv-recording convert the video to mp4 format and remove the first 200 seconds.
   exec_record_done /usr/bin/ffmpeg -y -i $path  -ss 200 -c copy -copyts - $dirname/aufzeichnung.mp4;
  #After finishing the flv-recording convert the video to mp4 format, which is available on the homepage as recorded video link. Remove the first 200 seconds.
   exec_record_done /usr/bin/ffmpeg -y -i $path  -ss 200 -c copy -copyts -y $dirname/$basename.mp4;
}
# ----- End of configuration to add -----
```

**In the Server section add the following configuration:**  

```
    server {
        listen       80;
        server_name  localhost;

        #charset koi8-r;

        #access_log  logs/host.access.log  main;

        location / {
            root   html;
            index  index.html index.htm index.php;
        }


        # ------- Start of configuration to add ------- 
        # ----------- Start mbrdi--------------
        location /mbrdistreams {
            alias /video_recordings/mbrdi;
        }

        #creates the http-location for our full-resolution (desktop) HLS stream - "http://serverlocation/mbrdi/test
        location /mbrdi {
            types {
            application/vnd.apple.mpegurl m3u8;
        }
        alias /HLS/lmbrdi;
        add_header Cache-Control no-cache;
        }

        location /mbrdiadmin/.*$ {
         auth_basic "MBRDI Admin";
         auth_basic_user_file /usr/local/nginx/.htpasswd;
         fastcgi_pass unix:/var/run/php/php7.1-fpm.sock;
         fastcgi_index index.html;
         fastcgi_param    SCRIPT_FILENAME $document_root$fastcgi_script_name;
         include fastcgi_params;
        }

        location /mbrdirecordings {
        alias /video_recordings/mbrdi;
        }
# ------- End of configuration to add ------- 
```

**Add the following PHP configuration:**  

```
# ----- Start of configuration to add -----
       ## Begin - PHP Konfiguration
       location ~ \.(php|html|htm)$ {
        root html;
        # Choose either a socket or TCP/IP address
        fastcgi_pass unix:/var/run/php/php7.1-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/$fastcgi_script_name;
      }
# ----- End of configuration to add -----
```

**Restart nginx and php:**  
```
sudo service nginx restart
sudo service php7.1-fpm restart
```

## Extract PHP and HTML pages:
**Switch to directory /usr/local/nginx/html:**  
`cd /usr/local/nginx/html`

**Create directory mbrdi:**  
`sudo mkdir /usr/local/nginx/html/mbrdi`

# Streaming Client installation  
In this example, the streaming client ist a linub box (eg Intel NUC), with Ubuntu OS 16.04 or 18.04, 
but it can also be a Windows-  or Raspberry Pi client, as well as a smartphone app.  

