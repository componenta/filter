<?php

declare(strict_types=1);

namespace Componenta\Filter;

/** @internal */
final class ConcreteClassName
{
    private function __construct()
    {
    }

    public static function equals(string $actual, string $candidate): bool
    {
        if (strcasecmp($actual, $candidate) === 0) {
            return true;
        }

        try {
            return strcasecmp(
                $actual,
                (new \ReflectionClass($candidate))->getName(),
            ) === 0;
        } catch (\ReflectionException) {
            return false;
        }
    }

    public static function contains(array $candidates, string $actual): bool
    {
        foreach ($candidates as $candidate) {
            if (self::equals($actual, $candidate)) {
                return true;
            }
        }

        return false;
    }
}
