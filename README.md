# Ride: Varnish CLI

This module adds various Varnish commands to the Ride CLI.

## Commands

### varnish

This command gets an overview of the Varnish servers in the pool with their status.

**Syntax**: ```varnish```

**Alias**: ```v```

### varnish add

This command adds a Varnish server to the pool.

**Syntax**: ```varnish add <host> [<port> [<secret>]]```
- ```<host>```: Hostname or IP address of the server
- ```<port>```: Port the varnishadm listens to (6082)
- ```<secret>```: Secret to authenticate with the server

**Alias**: ```va```

### varnish remove

This command removes a Varnish server from the pool.

**Syntax**: ```varnish remove <host> [<port>]```
- ```<host>```: Hostname or IP address of the server
- ```<port>```: Port the varnishadm listens to (6082)

**Alias**: ```vr```

### varnish ban

This command bans an expression on all the Varnish servers of the pool.

**Syntax**: ```varnish ban [--server] [--force] <expression>```
- ```--server```: Limit to a single server, provide the server and port
- ```--force```: Ignore failures and execute the command on the remaining servers
- ```<expression>``` Expression to ban

**Alias**: ```vb```

### varnish ban url

This command bans an URL on all the Varnish servers of the pool.

**Syntax**: ```varnish ban url [--recursive] [--server] [--force] <url>```
- ```--recursive```: Clear everything starting with the provided URL
- ```--server```: Limit to a single server, provide the server and port
- ```--force```: Ignore failures and execute the command on the remaining servers
- ```<url>```: URL to ban

**Alias**: ```vbu```

### varnish generate redirect

This command generates varnish configuration to redirect a set of URL's.

**Syntax**: ```varnish generate redirect [--baseUrl] [--ignoreHeader] [--statusCode] <file>```
- ```--baseUrl```: Base URL for the old URL or destination, used for relative URL's
- ```--ignoreHeader```: Add this flag to ignore the first row
- ```--statusCode```: Default status code, used when 3rd column is empty
- ```<file>```: Path to a CSV file with the old URL as first column, the destination as second column, an optional HTTP status code (301 or 302) as third column, an optional flag (yes or no) to see if everything starting with the old URL should be matched as fourth column and an optional flag (yes or no) to see if the original path should be appended to the destination as fifth column.

**Alias**: ```vgr```

## Related Modules 

- [ride/app](https://github.com/all-ride/ride-app)
- [ride/app-varnish](https://github.com/all-ride/ride-app-varnish)
- [ride/cli](https://github.com/all-ride/ride-cli)
- [ride/lib-cli](https://github.com/all-ride/ride-lib-cli)
- [ride/lib-varnish](https://github.com/all-ride/ride-lib-varnish)
- [ride/wba-cms-varnish](https://github.com/all-ride/ride-wba-cms-varnish)
- [ride/wba-varnish](https://github.com/all-ride/ride-wba-varnish)
- [ride/web-cms-varnish](https://github.com/all-ride/ride-web-cms-varnish)

## Installation

You can use [Composer](http://getcomposer.org) to install this application.

```
composer require ride/cli-varnish
```
