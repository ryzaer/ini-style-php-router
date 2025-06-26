
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

- Cache path: `cache/tpl_{hash}.html`
- Metadata path: `cache/tpl_{hash}.html.meta`
- Automatically bypasses cache if any involved file (layout, partial, component) is modified.

### CLI Clear Cache:
Use `clear:cache` to remove all cache files:
```bash
php index.php clear:cache
```
---

## ✅ Example Usage
Put it inside page controller
```php
$self->set('title', 'Dashboard');
$self->set('user', [
    'name' => 'John Doe',
    'created_at' => '2024-12-31 12:00:00'
]);
echo $self->render('templates/path.html');
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

