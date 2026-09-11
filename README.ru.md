# Componenta Filter

Компонуемые объекты-фильтры для iterable-данных и сценариев discovery/reflection.

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
| `componenta/class-finder` | Использует фильтры для отбора найденных классов, атрибутов и reflection-объектов. |
| `componenta/iterator` | Может использовать фильтрацию поверх переигрываемых итераторов. |

## Что предоставляет пакет

- `FilterInterface`: контракт iterable-фильтра с `accept()` и `toArray()`.
- `AbstractFilter`: базовая реализация фильтрации iterable-источника.
- `FilterableInterface` и `Filterable`: иммутабельная поддержка цепочки фильтров.
- Фильтры для scalar, array, string, class name, reflection, file, range, callback и composition-сценариев.

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

## Цепочки фильтров

Объекты с `Filterable` возвращают новые экземпляры при добавлении или удалении фильтров.

```php
$next = $filterable->withFilter($filter);
$sameWithout = $next->withoutFilter($filter);
```

`Filterable::accept()` использует AND-семантику: значение должны принять все зарегистрированные фильтры. `OneOfFilter::accept()` использует OR-семантику и возвращает `false`, если альтернативные фильтры отсутствуют.

## Фильтры, зависящие от коллекции

`PercentageFilter` зависит от размера всей коллекции. Его следует итерировать или вызывать `toArray()`; прямой вызов `accept()` выбрасывает `LogicException`, поскольку одного значения недостаточно для вычисления процента.

`MergingFilter` последовательно объединяет iterable-результаты вложенных фильтров. Его `accept()` использует OR-семантику: значение принимается, если его принимает хотя бы один вложенный фильтр. `withIterable()` применяет новый источник ко всем вложенным фильтрам и при необходимости делает одноразовый iterable переигрываемым.

## Проверка конфигурации

Некорректная конфигурация, при которой фильтр не может безопасно работать, отклоняется заранее через `InvalidArgumentException`: это касается, в частности, диапазонов, регулярных выражений, конфигурации `filter_var()`, вероятностей, процентов и типизированных списков строк/имён классов.

## Разработка

Установите dev-зависимости и запустите Pest:

```bash
composer install
composer test
```

CI проверяет Composer metadata, синтаксис всех PHP-файлов и запускает Pest на PHP 8.4 и 8.5. Для дополнительной проверки чувствительности тестов доступен ручной mutation-аудит.
