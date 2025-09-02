# iniStyle PHP Router (micro framework) Class Documentation
This document provides a complete guide to the usage, structure, and configuration of the Router class, which is used for routing in PHP applications with .ini-based configuration.

The Router class comes with an integrated templating engine that supports Blade-like syntax, including @extends, @section, filters, includes, components, and caching.

Additionally, this package includes a PDO-based class that offers ready-to-use CRUD operations such as `insert`, `update`, `delete`, `select`, and `create` Table, with automatic support for LONGBLOB file handling.

## 📂 Class Overview
The `Router` class provides a flexible way to define HTTP routes using a configuration `.ini` file and dispatches requests to corresponding controller actions.

## 📁 Folder Structure Suggestion
```
/your-app
  ├── classes/
  │   └── __fn.php
  │   └── dbHandler.php
  │   └── mime.types
  │   └── Router.php
  └── autoload.php
```
---
## 🔧 Configuration (`config.ini`)

### ⚙️ Section ini variables:
```ini
[global]
;error_handler = ErrorController@handle
; Values separated by "|" .exp (username|password|....)
auth_data = 
;cache_enable = true
cache_path = caches
controller_path = controllers
template_path = templates
; The allow_extension option is optional for managing file extensions in the database.
; Values separated by "|" .exp (mp4|mp3|jpg|.....)
allow_extension = 

[router]
GET / = HomeController@method

[pwa]
name = PHP App iniStyle support
short_name = I-App
; description option is optional, and app stores may not use this
description = PHP application with .ini-based configuration
start_url = /
theme_color = #3367D6
background_color = #ffffff
; An icon with a size of 192×192 is required for PWA
icon_192 = icons/icon-192x192.png
icon_512 = icons/icon-512x512.png
; Screenshots are optional. Recommended narrow size: ≤ 640px
; Values separated by "|" .exp (sc_wide=image960.jpg|image2k.jpg)
sc_narrow = 
sc_wide = 
orientation = any
display = standalone
; version is optional
version =
```

### 📌 Route Syntax

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

#### full example:
```ini
GET|POST|PUT /user/{id}/token/{hash} [cors=www.domain.com,auth=true] = userController@show
```
### 🔐 Authentication

If `auth=true` is set on a `config.ini`, the router checks for required session keys defined in `global` section:
```ini
[global]
auth_data = username|role|token
```
If any key is missing, the router will:
- Call `error_handler` if available
- Or return `403 Forbidden`

### ⚠️ Error Handling

This is an example of using error handler:
```ini
[global]
error_handler = ErrorController@handle
```
It passes parameters and error code (`403`, `404`, `405`, `500`) to the handler.

### 💾 Caching System
If not set in `config.ini` caching template will be off as default
```ini
[global]
cache_enable = true
```
- Cache path: `caches/tpl_{hash}.html`
- Metadata path: `caches/tpl_{hash}.html.meta`
- Automatically bypasses cache if any involved file (layout, partial, component) is modified.

### 📄 Other default values
```ini
[global]
cache_path = caches           ← Default caches folder
controller_path = controllers ← Default controllers folder
template_path = templates     ← Default templates folder
allow_extension = pdf|jpg|mp4 ← Default allow extension for Database handler
```
  
---

## 📤 Controller Interface

Each route must map to a controller file `controllers/NameController.php` which is implemented from the router section `NameController@method`, and the method should look like:
```php
public function method($params) {
    // The $params object contains http_code and parameters from the URL router.
}
```
---

## 🛠 Getting start with CLI Extension
Create a file `index.php`, then run the `Router` class
```php
<?php
require_once 'autoload.php';
Router::dispatch('config.ini');
```
We are using a simple custom `autoload.php` file, but you can use Composer instead by creating and configuring a `composer.json` file.
```json
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
```bash
composer dump-autoload
```
change the `autoload.php` to `vendor/autoload.php`
```php
require_once 'vendor/autoload.php';
```
Now you can build structure with `index.php` script to:
- Generate route handler stubs (`make:handlers`) including `error_handler`
- Generate standard ini file configuration (`make:ini`)
- Generate PWA setup (`make:pwa`) based on `[pwa]` section
- Template Cache cleaner (`clear:caches`)

CLI patterns:
```shell
php index.php [make:command] [ini_name_file]   ← Sintax to generate handlers, ini file standard and pwa component
php index.php [clear:command]                  ← Sintax to clear template caches
```

First step run command in terminal :
```
php index.php make:ini config
```
Then run :
```
php index.php make:handlers config
```
Don't forget to create an `.htaccess` file to protect your .ini configuration files. If you are using Nginx, you can convert the rule using [Winginx](https://www.winginx.com/en/htaccess)
```xml
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
Here’s how your folder structure should look:
```
/your-app
  ├── caches/
  ├── classes/
  │   └── __fn.php
  │   └── dbHandler.php
  │   └── mime.types
  │   └── Router.php
  ├── controllers/
  │   └── HomeController.php
  ├── templates/
  ├── .htaccess
  ├── autoload.php
  ├── config.ini
  └── index.php
```
You’ve reached the point; you may now proceed to the next documentation.

##### 📦 Templating [here](./README.Templating.md)
##### 🗄️ Database [here](./README.Database.md)
---
## 🧩 Public Methods

#### `fn->custom(...$params)`
Assign a single customization function, it will be created in `classes/__functions/custom.php`.

#### `set(string $key, mixed $value):void`
Assign a single variable.

#### `set(array $data):void`
Bulk assign associative array as variables.

#### `get(string $key):?string`
Assign a single to get variable sections in `config.ini` or which is set by `set()` function on handlers, exp.`get("global")` or `get("global.auth_data")`.

#### `getAuthData():array`
Return `auth_data` keys on [global] section variables.

#### `getMimeFile(string $filepath, bool true):string`
Return mime_type based on file (default:true).

#### `getMimeFile(string $filebin, bool false):string`
Return mime_type based on binary data.

#### `getExtension(string $mimeType):string`
Return extension based on mime_type of file.

#### `dbConnect(string $user,string $passwd,string $dbname,string $host,string $port,string $type):void`
Bulk assign connection to manage `insert`, `update`, `delete`, `select` and `create` table in managing [databases](./README.Database.md)

#### `render(string $htmlFileLocation):string`
Returns the fully rendered HTML string. Auto-handles layout inheritance, components, includes, conditionals, etc.

#### `dispatch(string $configFile, array $cliParams):void`
Bulk assign to run the Router

#### `apiResponse(int $httpCode,array $Data,array $optionalData,bool $beautifyJSON):string`
Simple JSON output