<?php

declare(strict_types=1);

use Componenta\Filter\FileExtensionFilter;

it('rejects extensionless paths even when an empty extension is allowed', function (): void {
    expect((new FileExtensionFilter(['']))->accept('README'))->toBeFalse();
});

it('honors iterable binding in file extension filters', function (): void {
    $filter = new FileExtensionFilter(['php'], false, ['a.php', 'README']);

    expect($filter->toArray())->toBe(['a.php']);
});
