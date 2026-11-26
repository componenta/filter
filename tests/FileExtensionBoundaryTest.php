<?php

declare(strict_types=1);

use Componenta\Filter\FileExtensionFilter;

it('normalizes file extensions case-insensitively on both sides', function (): void {
    expect((new FileExtensionFilter(['php']))->accept('example.PHP'))->toBeTrue()
        ->and((new FileExtensionFilter(['PHP']))->accept('example.php'))->toBeTrue()
        ->and((new FileExtensionFilter(['php']))->accept('README'))->toBeFalse();
});

it('rejects extensionless paths even when an empty extension is allowed', function (): void {
    expect((new FileExtensionFilter(['']))->accept('README'))->toBeFalse();
});

it('honors iterable binding in file extension filters', function (): void {
    $filter = new FileExtensionFilter(['php'], false, ['a.php', 'README']);

    expect($filter->toArray())->toBe(['a.php']);
});

it('rejects null bytes anywhere in a path before inspecting its extension', function (): void {
    $filter = new FileExtensionFilter(['jpg', 'php', "jpg\0"]);
    $invalid = [
        "report.php\0.jpg",
        "directory\0/report.jpg",
        "report.jpg\0",
        "\0report.php",
    ];

    foreach ($invalid as $path) {
        $stringable = new class($path) implements Stringable {
            public function __construct(private readonly string $path)
            {
            }

            public function __toString(): string
            {
                return $this->path;
            }
        };

        expect($filter->accept($path))->toBeFalse()
            ->and($filter->accept($stringable))->toBeFalse()
            ->and($filter->withCaseSensitive(true)->accept($path))->toBeFalse();
    }

    expect($filter->accept('report.JPG'))->toBeTrue()
        ->and($filter->accept('directory/report.jpg'))->toBeTrue()
        ->and($filter->withIterable([...$invalid, 'report.jpg'])->toArray())->toBe(['report.jpg']);
});
