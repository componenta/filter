# Componenta Filter

Компонуемые предикаты и iterable-фильтры коллекций для PHP 8.4+.

## Установка

```bash
composer require componenta/filter
```

## Требования

- PHP 8.4+
- `componenta/arrayable`
- `componenta/iterator`

## Контракты

Во второй major-версии разделены проверка отдельного значения и операции над коллекцией.

- `PredicateInterface` содержит только `accept()` и применяется там, где одно значение можно оценить без контекста коллекции.
- `CollectionFilterInterface` описывает обработку коллекции через `getIterator()`, `withIterable()` и `toArray()`.
- `FilterInterface` расширяет оба интерфейса и представляет predicate-backed фильтр коллекции.
- `AbstractCollectionFilter` реализует иммутабельную привязку iterable и `toArray()` для collection-only операторов.
- `AbstractFilter` расширяет `AbstractCollectionFilter` и добавляет predicate-backed итерацию.

## Predicate-backed фильтры

```php
use Componenta\Filter\StringFilter;

$filter = new StringFilter(['one', 2, 'three']);

$filter->accept('value'); // true
$filter->toArray();       // ['one', 'three']
```

Строковые предикаты принимают только `string` и `Stringable`. Числа, boolean и другие скаляры больше не приводятся к строке неявно.

## Чистые предикаты и композиция

Пользовательскому предикату больше не требуется iterable-контракт:

```php
use Componenta\Filter\ChainableFilter;
use Componenta\Filter\PredicateInterface;

$positiveInteger = new class implements PredicateInterface {
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return is_int($value) && $value > 0;
    }
};

$filter = new ChainableFilter($positiveInteger);
$filter->accept(10); // true
```

`Filterable`, `ChainableFilter`, `OneOfFilter`, `NotFilter` и `RecursiveFilter` зависят от `PredicateInterface`. `Filterable` и `ChainableFilter` используют AND-семантику; `OneOfFilter` — OR-семантику, а пустой `OneOfFilter` отклоняет любое значение.

## Collection-only операторы

Следующие операторы намеренно не реализуют `PredicateInterface`:

- `PercentageFilter`: результат зависит от размера коллекции и позиции элемента;
- `UniqueFilter`: уникальность зависит от значений, уже встреченных в текущем обходе;
- `MergingFilter`: объединяет результаты нескольких `CollectionFilterInterface`.

```php
use Componenta\Filter\IntFilter;
use Componenta\Filter\MergingFilter;
use Componenta\Filter\PercentageFilter;
use Componenta\Filter\UniqueFilter;

$filter = new MergingFilter(
    new IntFilter([1, 'two']),
    new PercentageFilter(50, ['a', 'b', 'c', 'd']),
    new UniqueFilter([1, 1, 2]),
);

$filter->toArray(); // [1, 'a', 'b', 1, 2]
```

`MergingFilter::withIterable()` использует `ReplayableIterator`: одноразовый generator не читается в момент привязки, а каждый вложенный фильтр получает независимый ленивый replay-cursor.

## Точные числовые предикаты

`NumericFilter`, `BetweenFilter`, `RangeFilter`, `GreaterThan*`, `LessThan*`, `MultipleOfFilter` и parity-фильтры не переводят numeric strings в `float` без необходимости.

Поэтому большие целые, длинные десятичные строки и scientific notation сравниваются без потери точности после `2^53`. `NAN` и бесконечности отклоняются value-oriented числовыми предикатами. Настоящие PHP `float` сохраняют естественную для них двоичную точность; `MultipleOfFilter` использует ограниченный tolerance только когда проверяемое значение само является `float`.

## Диапазоны дат

`DateRangeFilter` принимает `DateTimeInterface` или абсолютные ISO-подобные строки даты/времени. Для локальных строк timezone фиксируется при создании фильтра. Сравнение сохраняет микросекунды. Относительные выражения вроде `tomorrow` и невозможные даты, которые PHP обычно нормализует, не принимаются.

```php
$range = new DateRangeFilter(
    '2026-01-01 00:00:00',
    '2026-12-31 23:59:59',
    timezone: new DateTimeZone('Europe/Copenhagen'),
);
```

## Рекурсивная фильтрация

`RecursiveFilter` обнаруживает циклы iterable-объектов и дополнительно ограничивает глубину через `maxDepth` (по умолчанию `64`), поэтому самоссылочные массивы также не могут привести к бесконечной рекурсии.

```php
$filter = new RecursiveFilter($predicate, maxDepth: 32);
```

## Случайная фильтрация

`RandomFilter` использует изолированный `Random\Randomizer` и не изменяет глобальное состояние `mt_rand()`. Для воспроизводимой последовательности можно передать собственный `Randomizer` с детерминированным engine.

## Проверка конфигурации

Некорректная конфигурация отклоняется заранее через `InvalidArgumentException`, если безопасное выполнение иначе невозможно. Это относится, в частности, к числовым и временным диапазонам, regex, `filter_var()` ID/options, вероятностям, процентам и типизированным спискам строк, классов и ключей.

## Breaking changes относительно 1.x

- `FilterInterface` теперь является пересечением `PredicateInterface` и `CollectionFilterInterface`.
- `PercentageFilter`, `UniqueFilter` и `MergingFilter` являются collection-only и не имеют `accept()`.
- API композиции принимает `PredicateInterface`; пользовательскому предикату больше не нужны iterable-методы.
- `MergingFilter` принимает любые `CollectionFilterInterface` и лениво переигрывает одноразовые источники.
- Строковые предикаты больше не приводят произвольные скаляры к строке.
- Числовые границы принимают `int|float|string` и поддерживают точное decimal/scientific comparison.
- `DateRangeFilter` использует абсолютный детерминированный parsing, зафиксированный timezone и микросекундную точность.
- `RandomFilter` больше не потребляет глобальное состояние `mt_rand()`.
- `RecursiveFilter` ограничивает глубину и обнаруживает циклы.

## Разработка

```bash
composer install
composer test
```

CI проверяет Composer metadata, синтаксис всех PHP-файлов и запускает Pest на PHP 8.4 и 8.5. Для дополнительной проверки чувствительности тестов доступен ручной mutation-аудит.
