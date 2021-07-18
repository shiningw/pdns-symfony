#!/bin/bash
#the dns name for accessing this site
server_name="dns.example.com"
web_root="/var/www/html/pdns-symfony"
#powerdns api key
api_key="testapikey"
#default username for login
web_username=pdns
#default admin password for login
web_password="pdns12345678" 
#your nameservers
ns_servers="ns1.example.com ns2.example.com"
sqlite_db_path=/var/lib/powerdns/pdns.sqlite

sudo apt -y update
sudo apt -y install build-essential sqlite3

get_pkg() {

  local pkg_url=$1
  local pkg_file=${pkg_url##*/}
  local pkg_dir=$(echo $pkg_file | sed -n 's/\(\(\.tar\.bz2\)\|\.tar\.gz\|\.zip\)//p')

  [ -f "$pkg_file" ] || wget --no-check-certificate $pkg_url

  case ${pkg_file##*.} in

  bz2) tar -xjpf $pkg_file ;;
  gz | tgz) tar -xzpf $pkg_file ;;
  zip) unzip $pkg_file ;;

  esac

  echo $pkg_dir
}

compile_pdns() {
	
 sudo apt -y install libboost-all-dev liblua5.3-dev gearman-job-server libcurl4-openssl-dev libsqlite3-dev

  pkg_dir=$(get_pkg "https://downloads.powerdns.com/releases/pdns-4.4.1.tar.bz2")

  cd $pkg_dir

  ./configure --prefix=/usr/local --sysconfdir=/etc/powerdns --with-modules="gsqlite3 gmysql bind pipe remote"

  make && sudo make install

}
get_server_ip() {
	server_ip=$(ip route get 8.8.8.8 | awk '/8.8.8.8/ { print $NF }')
if [ $server_ip -eq 0 ]; then

  server_ip=$(ip route get 8.8.8.8 | awk '/8.8.8.8/ { print $(NF -2) }')

fi
}

install_pdns() {

 	sudo apt install -y pdns-server pdns-backend-sqlite3 pdns-backend-mysql pdns-backend-remote pdns-backend-pipe

}

pdns_config() {

 sudo cat >/etc/powerdns/pdns.conf <<EOF
config-dir=/etc/powerdns
daemon=yes
disable-axfr=yes
guardian=yes
local-address=0.0.0.0
#webserver-address=127.0.0.1
local-port=53
#module-dir=/usr/lib/powerdns
setgid=pdns
setuid=pdns
slave=no
master=yes
socket-dir=/var/run
version-string=powerdns
#include-dir=/etc/powerdns/pdns.d
socket-dir=/var/run
version-string=powerdns
#include-dir=/usr/local/etc/powerdns/pdns.d
api=yes
api-key=${api_key}
webserver=yes
#pipe-abi-version=1
webserver-allow-from=127.0.0.1,::1,${server_ip}
launch=gsqlite3
gsqlite3-pragma-foreign-keys=1
gsqlite3-database=${sqlite_db_path}
gsqlite3-pragma-foreign-keys=1
#default-soa-content=${server_name}. hostmaster.@ 0 10800 3600 604800 3600
#loglevel=99
EOF

  [ -d /etc/powerdns/pdns.d ] || sudo mkdir -p /etc/powerdns/pdns.d

  sudo chown -R pdns:pdns ${sqlite_db_path}
  sudo chown -R pdns:pdns /etc/powerdns
  sudo chmod 755 /etc/powerdns/*
}

sqlite_schema() {

  sudo sqlite3 ${sqlite_db_path} <<'EOF'
PRAGMA foreign_keys = 1;

CREATE TABLE domains (
  id                    INTEGER PRIMARY KEY,
  name                  VARCHAR(255) NOT NULL COLLATE NOCASE,
  master                VARCHAR(128) DEFAULT NULL,
  last_check            INTEGER DEFAULT NULL,
  type                  VARCHAR(6) NOT NULL,
  notified_serial       INTEGER DEFAULT NULL,
  account               VARCHAR(40) DEFAULT NULL
);

CREATE UNIQUE INDEX name_index ON domains(name);


CREATE TABLE records (
  id                    INTEGER PRIMARY KEY,
  domain_id             INTEGER DEFAULT NULL,
  name                  VARCHAR(255) DEFAULT NULL,
  type                  VARCHAR(10) DEFAULT NULL,
  content               VARCHAR(65535) DEFAULT NULL,
  ttl                   INTEGER DEFAULT NULL,
  prio                  INTEGER DEFAULT NULL,
  disabled              BOOLEAN DEFAULT 0,
  ordername             VARCHAR(255),
  auth                  BOOL DEFAULT 1,
  FOREIGN KEY(domain_id) REFERENCES domains(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX records_lookup_idx ON records(name, type);
CREATE INDEX records_lookup_id_idx ON records(domain_id, name, type);
CREATE INDEX records_order_idx ON records(domain_id, ordername);


CREATE TABLE supermasters (
  ip                    VARCHAR(64) NOT NULL,
  nameserver            VARCHAR(255) NOT NULL COLLATE NOCASE,
  account               VARCHAR(40) NOT NULL
);

CREATE UNIQUE INDEX ip_nameserver_pk ON supermasters(ip, nameserver);


CREATE TABLE comments (
  id                    INTEGER PRIMARY KEY,
  domain_id             INTEGER NOT NULL,
  name                  VARCHAR(255) NOT NULL,
  type                  VARCHAR(10) NOT NULL,
  modified_at           INT NOT NULL,
  account               VARCHAR(40) DEFAULT NULL,
  comment               VARCHAR(65535) NOT NULL,
  FOREIGN KEY(domain_id) REFERENCES domains(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX comments_idx ON comments(domain_id, name, type);
CREATE INDEX comments_order_idx ON comments (domain_id, modified_at);


CREATE TABLE domainmetadata (
 id                     INTEGER PRIMARY KEY,
 domain_id              INT NOT NULL,
 kind                   VARCHAR(32) COLLATE NOCASE,
 content                TEXT,
 FOREIGN KEY(domain_id) REFERENCES domains(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX domainmetaidindex ON domainmetadata(domain_id);


CREATE TABLE cryptokeys (
 id                     INTEGER PRIMARY KEY,
 domain_id              INT NOT NULL,
 flags                  INT NOT NULL,
 active                 BOOL,
 published              BOOL DEFAULT 1,
 content                TEXT,
 FOREIGN KEY(domain_id) REFERENCES domains(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX domainidindex ON cryptokeys(domain_id);


CREATE TABLE tsigkeys (
 id                     INTEGER PRIMARY KEY,
 name                   VARCHAR(255) COLLATE NOCASE,
 algorithm              VARCHAR(50) COLLATE NOCASE,
 secret                 VARCHAR(255)
);

CREATE UNIQUE INDEX namealgoindex ON tsigkeys(name, algorithm);
EOF

}

install_php() {
	sudo apt -y install php-mysql php-xml php-bcmath php-intl php-radius php-xml php-gd php-zip php-mbstring php-curl php-sqlite3 php-curl  # php-pcntl
}

install_apache2(){
	
    sudo apt-get -y install apache2 libapache2-mod-php
    sudo a2enmod rewrite
    sudo a2enmod ssl
}

install_pdns_symfony(){
	[ -f ${web_root} || mkdir -p ${web_root}
	sudo git clone https://github.com/shiningw/pdns-symfony ${web_root}
}

setup_pdns_symfony() {
	cd ${web_root} && sudo composer install

sudo mkdir -p var/data
sudo chown www-data var/data
sudo php bin/console doctrine:schema:update --force
sudo php bin/console fos:user:create ${web_username} admin@xxxx.com ${web_password}
sudo php bin/console fos:user:promote ${web_username} ROLE_ADMIN

sudo chown -R www-data:www-data var/
sudo chown -R www-data:www-data web/
sudo -u www-data php bin/console assetic:dump
sudo  php bin/console pdns:configure ${api_key} ${ns_servers}
}
disable_systemd_resolvd(){
	sudo systemctl disable systemd-resolved
	sudo systemctl stop systemd-resolved
	[ -f "/run/systemd/resolve/resolv.conf" ] && sudo rm -f /etc/resolv.conf && sudo ln -s /run/systemd/resolve/resolv.conf /etc/resolv.conf

}

install_composer(){
	  [ `which composer` ] || sudo wget https://getcomposer.org/download/latest-stable/composer.phar -O /usr/bin/composer
	  sudo chmod 755 /usr/bin/composer
}

httpd_conf(){
	sudo cat >/etc/apache2/sites-available/pdns.conf <<EOF
	<VirtualHost *:80>
        DocumentRoot ${web_root}/web
        Servername ${server_name}

        <Directory />
                Options FollowSymLinks
                AllowOverride All
        </Directory>
        <Directory ${web_root}>
                Options Indexes FollowSymLinks MultiViews
                AllowOverride All
                Order allow,deny
                allow from all

          <IfModule mod_rewrite.c>
              Options -MultiViews
              RewriteEngine On
              RewriteCond %{REQUEST_FILENAME} !-f
              RewriteRule ^(.*)$ app.php [QSA,L]
          </IfModule>
        </Directory>

        ScriptAlias /cgi-bin/ /usr/lib/cgi-bin/
        <Directory "/usr/lib/cgi-bin">
                AllowOverride None
                Options +ExecCGI -MultiViews +SymLinksIfOwnerMatch
                Order allow,deny
                Allow from all
        </Directory>

        ErrorLog \${APACHE_LOG_DIR}/error.log

        # Possible values include: debug, info, notice, warn, error, crit,
        # alert, emerg.
        LogLevel warn

        CustomLog \${APACHE_LOG_DIR}/access.log combined

    Alias /doc/ "/usr/share/doc/"
    <Directory "/usr/share/doc/">
        Options Indexes MultiViews FollowSymLinks
        AllowOverride None
        Order deny,allow
        Deny from all
        Allow from 127.0.0.0/255.0.0.0 ::1/128
    </Directory>

</VirtualHost>
EOF
sudo a2ensite pdns
sudo systemctl reload apache2
}
get_server_ip
install_apache2
httpd_conf
install_php
install_pdns
sqlite_schema
pdns_config

install_pdns_symfony
install_composer
setup_pdns_symfony

disable_systemd_resolvd
sudo systemctl start pdns
sudo systemctl restart apache2