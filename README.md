

# iniStyle PHP Router Class Documentation
This document describes the usage, structure, and configuration of the `Router` class used for routing in a PHP application with `.ini`-based configuration.

Templating engine integrated, supporting Blade-like syntax including `@extends`, `@section`, filters, includes, components, and caching.

## 📂 Class Overview
The `Router` class provides a flexible way to define HTTP routes using a configuration `.ini` file and dispatches requests to corresponding controller actions.

## 📁 Folder Structure Suggestion
```
/your-app
  ├── cli.php
  ├── config.ini
  └── Router.php
```
---
## 🔧 Configuration File Structure (`config.ini`)

### Example:
```ini
[global]
error_handler = ErrorController@handle
auth_data = username|role|token

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
- Multiple methods can be joined with `|`, e.g., `GET|POST`
- Parameters are defined using `{}` brackets
- Supported options:
  - `auth=true`: Requires session variables defined in `global.auth_data`
  - `cors=true`: Sends `Access-Control-Allow-Origin: *`

---
## 🔐 Authentication

If `auth=true` is set on a route, the router checks for required session keys defined in:
```ini
[global]
auth_data = username|role|token
```
If any key is missing, the router will:
- Call `error_handler` if available
- Or return `403 Forbidden`

---

## ⚠️ Error Handling

If a route fails to match or controller is not found, the router uses:
```ini
[global]
error_handler = ErrorController@handle
```
It passes parameters and error code (`403`, `404`, `405`, `500`) to the handler.

---

## 📤 Controller Interface

Each route must map to a controller file `controllers/NameController.php`, and the method should look like:
```php
public function method($self,$params) {
    // $self is the Router instance
    // $params is an object with route parameters
}
```
---
## 📦 Templating Features

- `{{@variable}}` – Basic variable replacement (supports nested keys)
- `{{@var|lower|ucwords}}` – Filter chaining (with optional parameters)
- `{{'template/header.html'}}` – Static includes
- `{{'template/' ~ name ~ '.html'}}` – Dynamic includes
- `{{if ...}} ... {{endif}}` – Conditionals
- `{{foreach item in list}} ... {{endforeach}}` – Looping
- `{{@component:'file.html' with key="value"}}` – Component rendering
- `@extends`, `@section`, `@endsection`, `@section:name` – Layout inheritance
- Caching system with auto-expiry on template modification

---

## 💾 Caching System

- Cache path: `caches/tpl_{hash}.html`
- Metadata path: `caches/tpl_{hash}.html.meta`
- Automatically bypasses cache if any involved file (layout, partial, component) is modified.

---

## 🛠 Getting start with CLI Extension

You can build CLI scripts (`cli.php`) to:
- Generate handler stubs (`php cli.php config make:handlers`)
- Generate PWA setup (`php cli.php config make:pwa`) based on `[pwa]` section
- Template Cache cleaner (`php cli.php clear:caches`)

After `php cli.php config make:handlers` executed, your structure folders will be like this
```
/your-app
  ├── caches/
  ├── controllers/
  │   └── ErrorController.php
  │   └── HomeController.php
  │   └── ProfileController.php
  │   └── AuthController.php
  ├── cli.php
  ├── config.ini
  └── Router.php
```
then make folder `templates`, for templating like this
```
/your-app
  ├── caches/
  ├── controllers/
  │   └── ErrorController.php
  │   └── HomeController.php
  │   └── ProfileController.php
  │   └── AuthController.php
  ├── templates/
  │   └── components/
  ├── cli.php
  ├── config.ini
  └── Router.php
```
create file `index.php`, then run the Router class
```
<?php
require_once 'Router.php';
Router::dispatch('config.ini');
```
so your folder structure now will be
```
/your-app
  ├── caches/
  ├── controllers/
  │   └── ErrorController.php
  │   └── HomeController.php
  │   └── ProfileController.php
  │   └── AuthController.php
  ├── templates/
  │   └── components/
  ├── cli.php
  ├── config.ini
  ├── index.php
  └── Router.php
```