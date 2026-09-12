<?php

declare(strict_types=1);

use Componenta\Filter\SubclassFilter;

class SubclassIterableParentFixture {}
class SubclassIterableChildFixture extends SubclassIterableParentFixture {}

it('honors constructor iterable binding in subclass filters', function (): void {
    $filter = new SubclassFilter(
        SubclassIterableParentFixture::class,
        [SubclassIterableChildFixture::class, SubclassIterableParentFixture::class],
    );

    expect($filter->toArray())->toBe([SubclassIterableChildFixture::class]);
});
