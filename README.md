

# iniStyle PHP Router (micro framework) Class Documentation
This document describes the usage, structure, and configuration of the `Router` class used for routing in a PHP application with `.ini`-based configuration.

This class includes and integrated templating engine that supports Blade-like syntax, including @extends, @section, filters, includes, components, and caching.

Also a class extending PDO that provides ready-to-use CRUD functions like `insert`, `update`, `delete`, `select` and `create` table with automatic LONGBLOB file support.

## 📂 Class Overview
The `Router` class provides a flexible way to define HTTP routes using a configuration `.ini` file and dispatches requests to corresponding controller actions.

## 📁 Folder Structure Suggestion
```
/your-app
  ├── classes/
  │   └── __fn.php
  │   └── dbHandler.php
  │   └── Router.php
  ├── autoload.php
  └── config.ini
```
---
## 🔧 Configuration File Structure (`config.ini`)

### Full example:
```ini
[global]
error_handler = ErrorController@handle
auth_data = username|role|token
cache_enable = true
cache_path = caches
controller_path = controllers
template_path = templates

[router]
GET / = HomeController@index
GET|POST /profile/{id} = ProfileController@show [auth=true,cors=true]
POST /login = AuthController@login

[pwa]
name = My PHP App
short_name = PHPApp
start_url = /
theme_color = #3367D6
background_color = #ffffff
icon_192 = icons/icon-192x192.png
icon_512 = icons/icon-512x512.png
display = standalone
```
---
## 📌 Route Syntax

Each route entry in `[router]` must follow this format:
```ini
METHOD /path/{param} = Controller@method [optional=params]
```
or
```ini
METHOD /path/{param} [optional=params] = Controller@method
```
- Multiple methods can be joined with `|`, e.g., `GET|POST`
- Parameters are defined using `{}` brackets
- Supported options:
  - `auth=true`: Requires session variables defined in `global.auth_data`
  - `cors=true`: Sends `Access-Control-Allow-Origin: *` or custom domain `cors=www.domain.com`

full example
```ini
GET|POST|PUT /user/{id}/token/{hash} [cors=www.domain.com,auth=true] = userController@show
```

---
## 🔐 Authentication

If `auth=true` is set on a `config.ini`, the router checks for required session keys defined in `global` section:
```ini
[global]
auth_data = username|role|token
```
If any key is missing, the router will:
- Call `error_handler` if available
- Or return `403 Forbidden`

---

## ⚠️ Error Handling

This is an example of using error handler:
```ini
[global]
error_handler = ErrorController@handle
```
It passes parameters and error code (`403`, `404`, `405`, `500`) to the handler.

---

## 📤 Controller Interface

Each route must map to a controller file `controllers/NameController.php`, and the method should look like:
```php
public function method($self,$params,$http_code) {
    // $self is the Router instance
    // $params is an object url pattern from route {params}
    // $http_code is an numeric http code
}
```
---

## 🛠 Getting start with CLI Extension
Create a file `index.php`, then run the `Router` class
```
<?php
require_once 'autoload.php';
Router::dispatch('config.ini',isset($argv)?$argv:[]);
```
We are using a simple `autoload.php` custom file,but you can use composer instead, create and configure `composer.json`
```
{
    "autoload": {
        "psr-4": {
            "": "classes/"
        }
    },
    "config": {
        "platform": {
            "php": "7.4.3"
        }
    }
}
```
then run
```
composer dump-autoload
```
change the `autoload.php` to `vendor/autoload.php`
```
<?php
require_once 'vendor/autoload.php';
```
Now you can build structure with `index.php` script to:
```
php index.php [commands] [ini_file_name]
```
- Generate route handler stubs (`php index.php make:handlers config`) including `error_handler`
- Generate PWA setup (`php index.php make:pwa config`) based on `[pwa]` section
- Template Cache cleaner (`php index.php clear:caches`)

After `php index.php make:handlers config` executed, your structure folders will be like this
```
/your-app
  ├── caches/
  ├── classes/
  │   └── Router.php
  ├── controllers/
  │   └── ErrorController.php
  │   └── HomeController.php
  │   └── ProfileController.php
  │   └── AuthController.php
  ├── autoload.php
  ├── config.ini
  └── index.php
```
then make a folder `templates`, for templating like this
```
/your-app
  ├── caches/
  ├── classes/
  │   └── Router.php
  ├── controllers/
  │   └── ErrorController.php
  │   └── HomeController.php
  │   └── ProfileController.php
  │   └── AuthController.php
  ├── templates/
  │   └── components/
  ├── autoload.php
  ├── config.ini
  └── index.php
```
don't forget to create `.htaccess` file to protect your configuration `.ini` file or if nginx users can convert on [winginx](https://www.winginx.com/en/htaccess)
```
<IfModule mod_rewrite.c>
    Options +FollowSymLinks -MultiViews
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [QSA,L]  
    <FilesMatch "\.(ini|env|cfg|conf)$">
        Order allow,deny
        Deny from all
    </FilesMatch>
</IfModule>
```
so your folder structure now will be
```
/your-app
  ├── caches/
  ├── classes/
  │   └── Router.php
  ├── controllers/
  │   └── ErrorController.php
  │   └── HomeController.php
  │   └── ProfileController.php
  │   └── AuthController.php
  ├── templates/
  │   └── components/
  ├── .htaccess
  ├── autoload.php
  ├── config.ini
  └── index.php
```
## 📋 Templating
See templating documentation [here](./Templating.md)
## 🗄️ Database
See database documentation [here](./Database.md)