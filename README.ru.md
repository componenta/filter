# Componenta Filter

Компонуемые предикаты и iterable-фильтры коллекций для PHP 8.4+.

## Установка

```bash
composer require componenta/filter
```

## Требования

- PHP 8.4+
- `componenta/arrayable`

## Контракты

Во второй major-версии разделены проверка отдельного значения и операции над коллекцией.

- `PredicateInterface` содержит только `accept()` и используется там, где значение можно проверить независимо от коллекции.
- `CollectionFilterInterface` описывает работу с iterable через `getIterator()`, `withIterable()` и `toArray()`.
- `FilterInterface` расширяет оба интерфейса и представляет обычный predicate-backed фильтр коллекции.
- `AbstractCollectionFilter` реализует общую иммутабельную привязку iterable и `toArray()` для collection-only операторов.
- `AbstractFilter` расширяет `AbstractCollectionFilter` и добавляет фильтрацию на основе предиката.

Благодаря этому операторы, зависящие от всей коллекции, больше не обязаны иметь искусственный `accept()`.

## Обычная фильтрация

```php
use Componenta\Filter\StringFilter;

$filter = new StringFilter(['one', 2, 'three']);

$filter->accept('value'); // true
$filter->toArray();       // ['one', 'three']
```

Ключи по умолчанию не сохраняются:

```php
$filter->toArray(preserveKeys: true);
```

## Чистые предикаты

Для композиции больше не требуется реализовывать iterable-контракт:

```php
use Componenta\Filter\ChainableFilter;
use Componenta\Filter\PredicateInterface;

$positiveInteger = new class implements PredicateInterface {
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return is_int($value) && $value > 0;
    }
};

$filter = ChainableFilter::create($positiveInteger);
$filter->accept(10); // true
```

`Filterable`, `ChainableFilter`, `OneOfFilter`, `NotFilter` и `RecursiveFilter` теперь зависят от `PredicateInterface`, а не от `FilterInterface`.

## Collection-only операторы

`PercentageFilter` реализует только `CollectionFilterInterface`, поскольку его результат зависит от размера и позиции элементов всей коллекции:

```php
use Componenta\Filter\PercentageFilter;

$filter = new PercentageFilter(50, ['a', 'b', 'c', 'd']);
$filter->toArray(); // ['a', 'b']
```

Метода `accept()` у него больше нет.

`MergingFilter` также является collection-only оператором. Он объединяет результаты любых реализаций `CollectionFilterInterface`, включая другие collection-only операторы:

```php
use Componenta\Filter\IntFilter;
use Componenta\Filter\MergingFilter;
use Componenta\Filter\PercentageFilter;

$filter = new MergingFilter(
    new IntFilter([1, 'two']),
    new PercentageFilter(50, ['a', 'b', 'c', 'd']),
);

$filter->toArray(); // [1, 'a', 'b']
```

`withIterable()` заменяет источник у всех объединённых collection filters и при необходимости делает одноразовый iterable переигрываемым.

## Композиция предикатов

Объекты с `Filterable` возвращают новые экземпляры при добавлении или удалении предикатов:

```php
$next = $filterable->withFilter($predicate);
$sameWithout = $next->withoutFilter($predicate);
```

`Filterable::accept()` использует AND-семантику. `OneOfFilter::accept()` использует OR-семантику и возвращает `false`, если предикаты не заданы.

## Проверка конфигурации

Некорректная конфигурация, при которой фильтр не может безопасно работать, отклоняется заранее через `InvalidArgumentException`: это касается диапазонов, регулярных выражений, конфигурации `filter_var()`, вероятностей, процентов и типизированных списков строк/имён классов.

## Breaking changes относительно 1.x

- `FilterInterface` теперь является пересечением `PredicateInterface` и `CollectionFilterInterface`.
- `PercentageFilter` больше не наследуется от `AbstractFilter` и не имеет `accept()`.
- `MergingFilter` больше не реализует `FilterInterface` и не имеет `accept()`.
- `MergingFilter` принимает любые `CollectionFilterInterface`, а не только predicate-backed фильтры.
- API композиции принимает `PredicateInterface`, поэтому пользовательскому предикату больше не нужны iterable-методы.
- `AbstractFilter` теперь наследуется от `AbstractCollectionFilter`.

## Разработка

Установите dev-зависимости и запустите Pest:

```bash
composer install
composer test
```

CI проверяет Composer metadata, синтаксис всех PHP-файлов и запускает Pest на PHP 8.4 и 8.5. Для дополнительной проверки чувствительности тестов доступен ручной mutation-аудит.
