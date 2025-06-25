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

### 💾 Caching System
Can be set in the `config.ini` file, if not set will be false as default
```ini
[global]
cache_enable = true
```
- Cache path: `caches/tpl_{hash}.html`
- Metadata path: `caches/tpl_{hash}.html.meta`
- Automatically bypasses cache if any involved file (layout, partial, component) is modified.

---

### 📌 Public Methods

#### `set(string $key, mixed $value)`
Assign a single variable.

#### `set(array $data)`
Bulk assign associative array as variables.

#### `render(): string`
Returns the fully rendered HTML string. Auto-handles layout inheritance, components, includes, conditionals, etc.

---

### ⚙️ Internal Methods

#### `parse(string $content): string`
Core parsing method that calls all sub-parsers in order:
- remove comments
- parse components
- includes
- conditionals
- loops
- filters
- variables

#### `parseVariables(string $content)`
Replace all `{{@key}}` references with data values, with filter support.

#### `applyFilters(string $value, array $filters)`
Handles chaining of filters like `lower`, `upper`, `date`, `ucwords`, etc.

#### `parseHelpers()`
Legacy support; now merged into `parseVariables()` via chaining.

#### `parseIncludes(string $content)`
Handles `{{'file.html'}}` or dynamic paths using `~` concatenation.

#### `parseConditionals()`
Handles `{{if condition}} ... {{endif}}`

#### `parseLoops()`
Handles `{{foreach item in list}} ... {{endforeach}}`

#### `parseComponents()`
Handles components with context injection:
```php
{{@component:'path/to/component.html' with key="value"}}
```

#### `parseExtends()`
Detects `@extends:layout.html` in child views and stores parent file.

#### `parseSections()`
Parses `@section:name` and `@endsection` blocks from child views.

#### `injectYields(string $content)`
Replaces placeholders `@section:name` in layout with section values or fallback.

---

## 💾 Caching System

- Cache path: `cache/tpl_{hash}.html`
- Metadata path: `cache/tpl_{hash}.html.meta`
- Automatically bypasses cache if any involved file (layout, partial, component) is modified.

### CLI Clear Cache:
Use `clear-cache.php` to remove all cache files:
```bash
php clear-cache.php
```

---

## 📂 Recommended Structure
```
project/
├── Layout.php
├── index.php
├── layout.html
├── page.html
├── components/
│   └── alert.html
├── cache/
└── clear-cache.php
```

---

## ✅ Example Usage
Put it inside page handler
```php
$self->set('title', 'Dashboard');
$self->set('user', [
    'name' => 'John Doe',
    'created_at' => '2024-12-31 12:00:00'
]);
echo $self->render();
```

---

## 🧩 Supported Filters
| Filter     | Example                            | Description                  |
|------------|-------------------------------------|------------------------------|
| `lower`    | `{{@user.name|lower}}`             | Lowercase                   |
| `upper`    | `{{@title|upper}}`                 | Uppercase                   |
| `ucwords`  | `{{@user.name|lower|ucwords}}`     | Capitalize Words            |
| `date`     | `{{@user.created_at|date:"d M Y"}}` | Format date string          |

---

## 📌 To Do (Optional Enhancements)
- `@includeIf`, `@isset`, `@empty`
- `@push/@stack` for scripts/styles
- Custom user-registered filters
- Optional cache TTL (time-to-live)

