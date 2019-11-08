
this symfony bundle allows you to manage DNS zones and records through a web interface.If you have already installed and configured Powerdns properly, following the steps below will be all that is required

```console
git clone https://github.com/shiningw/pdns-symfony

cd pdns-symfony && composer update

mkdir -p var/data
sudo chown www-data var/data
#by default, it uses sqlite. so please install php-sqlite
php bin/console doctrine:schema:update --force
php bin/console fos:user:create admin admin@xxxx.com YOURPASSWORD //this is the credentials to log in\
php bin/consle fos:user:promote admin ROLE_ADMIN

sudo chown -R www-data:www-data var/
sudo chown -R www-data:www-data web/
sudo -u www-data php bin/console assetic:dump
```
demo: http://pdns.gizfun.com


successfully tested on Ubuntu 16.04 with apache2.4.18 and php7.3

