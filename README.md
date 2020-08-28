# Welight Gateway for Magento 1.9

Plugin para magento 1.9 welight gateway de pagamento e seletor de ongs. Instruções de configuração. 

# Docker image for Magento 1.x

Then use `docker-compose up -d` to start MySQL and Magento server.

## Magento env config
A Magento installation script is also provided as `/usr/local/bin/install-magento`. This script can install Magento without using web UI. This script requires certain environment variables to run:

Environment variable      | Description | Default value (used by Docker Compose - `env` file)
--------------------      | ----------- | ---------------------------
MYSQL_HOST                | MySQL host  | mysql
MYSQL_DATABASE            | MySQL db name for Magento | magento
MYSQL_USER                | MySQL username | magento
MYSQL_PASSWORD            | MySQL password | magento
MAGENTO_LOCALE            | Magento locale | pt_BR
MAGENTO_TIMEZONE          | Magento timezone |America/Sao_paulo
MAGENTO_DEFAULT_CURRENCY  | Magento default currency | BRL
MAGENTO_URL               | Magento base url | http://local.magento
MAGENTO_ADMIN_FIRSTNAME   | Magento admin firstname | Admin
MAGENTO_ADMIN_LASTNAME    | Magento admin lastname | MyStore
MAGENTO_ADMIN_EMAIL       | Magento admin email | contato@welight.live
MAGENTO_ADMIN_USERNAME    | Magento admin username | admin
MAGENTO_ADMIN_PASSWORD    | Magento admin password | magentorocks1

If Docker Compose is used, you can just modify `env` file in the same directory of `docker-compose.yml` file to update those environment variables.

**Important**: If you do not use the default `MAGENTO_URL` you must use a hostname that contains a dot within it (e.g `foo.bar`), otherwise the [Magento admin panel login won't work](http://magento.stackexchange.com/a/7773).

## Magento sample data

Installation script for Magento sample data is also provided.

__Please note:__ Sample data must be installed __before__ Magento itself.

Use `/usr/local/bin/install-sampledata` to install sample data for Magento.

After Docker container started, use `docker ps` to find container id of image `alexcheng/magento`, then use `docker exec` to call `install-sampledata` script.

```bash
docker exec -it "$(docker-compose ps -q web)" install-sampledata

```

Magento 1.9 sample data is compressed version from [Vinai/compressed-magento-sample-data](https://github.com/Vinai/compressed-magento-sample-data). Magento 1.6 uses the [official sample data](http://devdocs.magento.com/guides/m1x/ce18-ee113/ht_magento-ce-sample.data.html).

For Magento 1.7 and 1.8, the sample data from 1.6 doesn't work properly as claimed in the offcial website and causes database errors, so the `install-sampledata` script is removed for 1.7 and 1.8.

## Magento installation script

After Docker container started, use `docker ps` to find container id of image `alexcheng/magento`, then use `docker exec` to call `install-magento` script.

```bash
docker exec -it "$(docker-compose ps -q web)" install-magento

```
## Copy plugin into magento

After magento installed, copy plugin files into magento.

```bash
docker cp plugin-v1/js "$(docker-compose ps -q web)":/var/www/html
docker cp plugin-v1/app "$(docker-compose ps -q web)":/var/www/html
docker cp plugin-v1/skin "$(docker-compose ps -q web)":/var/www/html

```

## For Clean Cache

After update plugin.

```bash
docker exec -it "$(docker-compose ps -q web)" bash
php -r 'require "app/Mage.php"; Mage::app()->getCacheInstance()->flush();'

```

Site oficial:
https://welight.live

Sugestões de melhoria? [Clique aqui e contribua](https://welight.live).
