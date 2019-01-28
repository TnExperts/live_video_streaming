# Prerequisites
Linux Server with Ubuntu 16.04 or 18.04
sudo rights to install and compile additional tools

# Create needed folders on the Ubuntu server
Folder for video recordings:
Sudo mkdir /videorecordings/mbrdi

Folder for php and html files:
Sudo mkdir /usr/local/nginx/html

# Install ffmpeg
sudo apt-get install ffmpeg

Create a softlink to ffmpeg in /home/rarents/bin
Create an additional softlink to ffmpeg in /home/rarents/bin and name it avconv

# Install PHP 
sudo apt install php7.1-fpm php7.1-mcrypt php7.1-cli php7.1-xml php7.1-mysql php7.1-gd php7.1-imagick php7.1-recode php7.1-tidy php7.1-xmlrpc

# Configure PHP
Add the following line to /etc/php/7.1/fpm/php-fpm.conf to execute PHP Code within HTML files.

sudo nano /etc/php/7.1/fpm/php-fpm.conf 

security.limit_extensions = .php .html .js

Then restart php-fpm service:
sudo service php7.1-fpm restart

# Compile nginx with rtmp
Install the tools required to compile Nginx and Nginx-RTMP from source:
sudo apt-get install build-essential libpcre3 libpcre3-dev libssl-dev zlib1g-dev unzip

Create a working directory and switch to it: 
mkdir ~/working
cd ~/working

Download the Nginx and Nginx-RTMP source:
wget http://nginx.org/download/nginx-1.9.15.tar.gz
wget https://github.com/arut/nginx-rtmp-module/archive/master.zip

Extract the Nginx and Nginx-RTMP source: 
tar -zxvf nginx-1.9.15.tar.gz
unzip master.zip

Switch to the Nginx directory:
cd nginx-1.9.15

Add modules that Nginx will be compiled with. Nginx-RTMP is included.
./configure --with-http_ssl_module --with-http_stub_status_module --add-module=../nginx-rtmp-module-master

Compile and install Nginx with Nginx-RTMP:
make
sudo make install

Install the Nginx init scripts:
sudo wget https://raw.github.com/JasonGiedymin/nginx-init-ubuntu/master/nginx -O /etc/init.d/nginx

sudo chmod +x /etc/init.d/nginx
sudo update-rc.d nginx defaults

Start and stop Nginx to generate configuration files:
sudo service nginx start
sudo service nginx stop

sudo nano /etc/php/7.1/fpm/php.ini
cgi.fix_pathinfo=0

sudo service php7.1-fpm restart

Configuration of nginx
Edit the nginx configuration file:
sudo nano /usr/local/nginx/conf/nginx.conf

In the RTMP section add the yellow marked configurations:

rtmp {
 server {
  listen 1935;
  allow play all;

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
   #Nach Fertigstellung flv-Aufzeichnung umwandeln in mp4 das auf Homepage unter dem Link Letzte Aufzeichnung erreichbar ist. Die ersten 120 Sekunden werden weggeschnitten.
   exec_record_done /usr/bin/ffmpeg -y -i $path  -ss 200 -c copy -copyts - $dirname/aufzeichnung.mp4;
  #Nach Fertigstellung flv-Aufzeichnung umwandeln in mp4 das auf Homepage unter dem entsprechenden Datums-Link erreichbar ist. Die ersten 120 Sekunden werden weggeschnitten.
   exec_record_done /usr/bin/ffmpeg -y -i $path  -ss 200 -c copy -copyts -y $dirname/$basename.mp4;
}

In the Server section add the following configuration:

    server {
        listen       80;
        server_name  localhost;

        #charset koi8-r;

        #access_log  logs/host.access.log  main;

        location / {
            root   html;
            index  index.html index.htm index.php;
        }

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

Add the following PHP configuration:

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

Restart nginx and php:
Sudo service nginx restart
sudo service php7.1-fpm restart

Extract PHP and HTML pages:
