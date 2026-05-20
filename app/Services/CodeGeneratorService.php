<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class CodeGeneratorService
{
    private const CHARACTERS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public static function generateUnique(Model $model, string $column, string $prefix): string
    {
        $dateSegment = now()->format('Ym');

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $randomSegment = self::randomSegment(6);
            $code = sprintf('%s-%s-%s', strtoupper($prefix), $dateSegment, $randomSegment);

            if (! $model->newQuery()->where($column, $code)->exists()) {
                return $code;
            }
        }

        throw new \RuntimeException('Unable to generate unique code.');
    }

    private static function randomSegment(int $length): string
    {
        $maxIndex = strlen(self::CHARACTERS) - 1;
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= self::CHARACTERS[random_int(0, $maxIndex)];
        }

        return $result;
    }
}
