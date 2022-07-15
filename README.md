# crawler-full-node

### requirements
```
php-7.4
php-curl
php-mbstring
php-pdo
php-mysql
php-bcmath
```

#### database

https://github.com/kvazar-network/database

##### MySQL

https://github.com/kvazar-network/database/tree/master

##### SQLite

https://github.com/kvazar-network/database/tree/sqlite

#### kevacoind

https://github.com/kevacoin-project/kevacoin

#### kevacoin.conf

```
rpcuser=user
rpcpassword=password
rpcport=9992
server=1
addressindex=1
txindex=1
rpcallowip=127.0.0.1
whitelist=127.0.0.1
```

#### crontab
```
@reboot /path-to/kevacoind > /dev/null 2>&1
* * * * * /path-to/kevacoind > /dev/null 2>&1
* * * * * /path-to/php /path-to/crawler-full-node/crawler.php > /dev/null 2>&1
```
