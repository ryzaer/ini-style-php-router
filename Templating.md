
## 📦 Templating Features

- `{{@var}}` – Basic variable replacement (supports nested keys)
- `{{'template/header.html'}}` – Static includes
- `{{'template/' ~ var ~ '.html'}}` – Dynamic includes
- `{{if ... }} ... {{elseif ...}} ...  {{else}} ... {{endif}}` – Conditionals with operator support `===, !==, ==, !=, >, <, >=, <=`
- `{{foreach item in list}} ... {{endforeach}}` – Looping
- `{{@component:'file.html' with key="value"}}` – Component rendering
- `@extends`, `@section`, `@endsection`, `@section:name` – Layout inheritance
- `{{@var|lower|ucwords}} | {{upper var}}` – Filter chaining (with optional parameters)
- `{{@var|date:"d M Y"}} | {{date var "d M Y"}}` – Filter date (with optional parameters)
- Caching system with auto-expiry on template modification

---

## 💾 Caching System

- Cache path: `caches/tpl_{hash}.html`
- Metadata path: `caches/tpl_{hash}.html.meta`
- Automatically bypasses cache if any involved file (layout, partial, component) is modified.

#### CLI Clear Cache:
Use `clear:caches` to remove all cache files:
```bash
php index.php clear:caches
```
---

## 🧩 Supported Filters
| Filter     | Example                             | Description                  |
|------------|-------------------------------------|------------------------------|
| `lower`    | `{{@user.name\|lower}}`             | Lowercase                    |
| `upper`    | `{{@title\|upper}}`                 | Uppercase                    |
| `ucwords`  | `{{@user.name\|lower\|ucwords}}`    | Capitalize Words             |
| `date`     | `{{@user.created_at\|date:"d M Y"}}`| Format date string           |

---

## 📌 To Do (Optional Enhancements)
- `@includeIf`, `@isset`, `@empty`
- `@push/@stack` for scripts/styles
- Custom user-registered filters
- Optional cache TTL (time-to-live)

---

## ✅ Example Usage
Put it inside `controllers/HomeController.php` file;
```php
public function method($self,$params) {
    //---> here....
    $self->set('title', 'Dashboard');
    $self->set('user', [
        'name' => 'JoHn dOe',
        'role' => 'Admin',
        'created_at' => '2024-12-31 12:00:00'
    ]);
    // or set array assoc data
    $self->set([
        // for dynamic includes
        'path_code' => 'other_openscript',
        'catalog' => [
            'VivoBook',
            'Galaxy A52',
            'Thinkpad E280',
            'WH-1000XM4'
        ]
    ]);        
    echo $self->render('templates/home.html');
}
```
Create folder `components` inside folder `templates`, then create html file `frame.html` inside it  `templates/components/frame.html`:
```html
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>{{@title}}</title>
</head>
<body>
 <header>
    {{@section:header}}
 </header>
 <main>
    {{@section:content}}
 </main>
 <footer>
    {{@section:footer}}
 </footer>
</body>
</html>
```
Next create file `templates/components/header.html`:
```html
<nav class="header">
    <h5>{{@info}}</h5>
</nav>
```
Also create file `templates/components/footer.html`:
```html
<div class="footer">
    <h5>{{@info}}</h5>
    <small>{{@tribute}}</small>
</div>
```
For example includes parse, create file `templates/components/other_openscript.html`:
```html
<code>
    <pre>
[
  {
    "id": 1,
    "name": "ASUS VivoBook Laptop",
    "category": "Electronics",
    "stock": 15,
    "price": 7500000
  },
  {
    "id": 2,
    "name": "Ergonomic Office Chair",
    "category": "Furniture",
    "stock": 20,
    "price": 1250000
  },
  {
    "id": 3,
    "name": "Samsung Galaxy A52 Smartphone",
    "category": "Electronics",
    "stock": 10,
    "price": 4200000
  }
]
    </pre>
</code>
```

And the last move, create file `home.html` inside `templates` folder:
```html
{{@extends:templates/components/frame.html}}

{{@section:header}}
    {{@component:'templates/components/header.html' with info="This is Header"}}
{{@endsection}}

{{@section:content}}
    <!--Example Basic comment!-->
    {{--Filter comment will not show!--}}
    <h4><i>Welcome, {{@user.name}}</i></h4>
    <h5>Example Filters:</h5>
    <p>Date 1 : {{@user.created_at|date:"d M Y"}}</p>
    <p>Date 2 : {{date user.created_at "d F Y"}}</p>
    <p>Lower case : {{lower user.name}} || {{@user.name|lower}}</p>
    <p>Upper case : {{upper user.name}} || {{@user.name|upper}}</p>
    <p>Ucwords case : {{@user.name|lower|ucwords}}</p>

    <h5>Example Basic Includes:</h5>
    {{'templates/components/other_openscript.html'}}

    <h5>Example Dynamic Includes:</h5>
    {{'templates/components/' ~ path_code ~ '.html'}}

    <h5>Example Conditional:</h5>
    <p>{{if user.role === 'Admin'}}
        <i>You are Admin!</i>
    {{elseif user.role === 'Editor'}}
        <i>You are Editor!</i>
    {{else}}
        <i>You are Quest!</i>
    {{endif}}</p>

    <h5>Example Loop:</h5>
    <ol>
    {{foreach item in catalog}}
        <li>{{@item}}</li>
    {{endforeach}}</ol>
{{@endsection}}

{{@section:footer}}
    {{@component:'templates/components/footer.html' with info="This is Footer" tribute="© 2025 App iniStyle"}}
{{@endsection}}
```
Now your structure folder must be like this:
```txt
/your-app
  ├── caches/
  ├── classes/
  │   └── __fn.php
  │   └── dbHandler.php
  │   └── mime.types
  │   └── Router.php
  ├── controllers/
  │   └── ErrorController.php
  │   └── HomeController.php
  │   └── ProfileController.php
  │   └── AuthController.php
  ├── templates/
  │   └── components/
  │   │   └── footer.html
  │   │   └── frame.html
  │   │   └── header.html
  │   │   └── other_openscript.html
  │   └── home.html
  ├── .htaccess
  ├── autoload.php
  ├── config.ini
  └── index.php
```


