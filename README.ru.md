# Componenta Filter

Компонуемые filter objects для iterable data и reflection/class discovery.

## Установка

```bash
composer require componenta/filter
```

## Требования

- PHP 8.4+
- `componenta/arrayable`

## Связанные пакеты

| Пакет | Зачем нужен здесь |
|---|---|
| `componenta/arrayable` | Фильтры могут раскрывать результат через `toArray()`. |
| `componenta/class-finder` | Использует фильтры для отбора найденных классов, атрибутов и рефлексии. |
| `componenta/iterator` | Может использовать фильтрацию поверх переигрываемых итераторов. |

## Что предоставляет пакет

- `FilterInterface`: контракт iterable filter с `accept()` и `toArray()`.
- `AbstractFilter`: базовая реализация фильтрации iterable-источника.
- `FilterableInterface` и `Filterable`: иммутабельная поддержка filter chain.
- Конкретные фильтры для scalars, arrays, strings, class names, reflection objects, files, ranges, callbacks и composition.

## Базовое использование

```php
use Componenta\Filter\StringFilter;

$filter = new StringFilter(['one', 2, 'three']);

$filter->toArray(); // ['one', 'three']
```

Ключи по умолчанию не сохраняются:

```php
$filter->toArray(preserveKeys: true);
```

## Пользовательский критерий

```php
use Componenta\Filter\CallbackFilter;

$filter = new CallbackFilter(
    static fn(mixed $value, string|int|null $key): bool => is_int($value) && $value > 10,
    [5, 15, 20],
);

$filter->toArray(); // [15, 20]
```

## Filter Chains

Объекты с `Filterable` должны возвращать новые инстансы при добавлении или удалении фильтров.

```php
$next = $filterable->withFilter($filter);
$sameWithout = $next->withoutFilter($filter);
```

`accept()` использует AND-семантику: значение должны принять все зарегистрированные фильтры.
