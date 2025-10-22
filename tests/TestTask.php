<?php declare(strict_types=1);

namespace Valery\Tests;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SplFixedArray;

final class TestTask extends TestCase
{
    #[DataProvider('packProvider')]
    public function testCalculate(array $expected, array $offer, int $N): void
    {
        self::assertEquals($expected, $this->calculate($offer, $N));
    }

    public static function packProvider(): Generator
    {
        yield [
            [
                ['id' => 111, 'qty' => 1],
                ['id' => 222, 'qty' => 20],
                ['id' => 333, 'qty' => 50],
                ['id' => 444, 'qty' => 5],
            ],
            [
                ['id' => 111, 'count' => 42, 'price' => 13, 'pack' => 1],
                ['id' => 222, 'count' => 77, 'price' => 11, 'pack' => 10],
                ['id' => 333, 'count' => 103, 'price' => 10, 'pack' => 50],
                ['id' => 444, 'count' => 65, 'price' => 12, 'pack' => 5],
            ],
            76
        ];
        yield [
            [
                ['id' => 111, 'qty' => 26],
                ['id' => 333, 'qty' => 50],
            ],
            [
                ['id' => 111, 'count' => 42, 'price' => 9, 'pack' => 1],
                ['id' => 222, 'count' => 77, 'price' => 11, 'pack' => 10],
                ['id' => 333, 'count' => 103, 'price' => 10, 'pack' => 50],
                ['id' => 444, 'count' => 65, 'price' => 12, 'pack' => 5],
            ],
            76
        ];
        yield [
            [
                ['id' => 111, 'qty' => 6],
                ['id' => 222, 'qty' => 20],
                ['id' => 333, 'qty' => 50],
            ],
            [
                ['id' => 111, 'count' => 100, 'price' => 30, 'pack' => 1],
                ['id' => 222, 'count' => 60, 'price' => 11, 'pack' => 10],
                ['id' => 333, 'count' => 100, 'price' => 13, 'pack' => 50],
            ],
            76
        ];
    }

    private function calculate(array $offers, int $N): array
    {
        // Инициализация массивов для хранения стоимости и путей
        $dp = new SplFixedArray($N + 1); // dp - dynamic programming
        $parent = new SplFixedArray($N + 1);

        // Заполняем массивы максимально возможным значением
        for ($i = 0; $i <= $N; $i++) {
            $dp[$i] = PHP_FLOAT_MAX;
            $parent[$i] = null;
        }
        $dp[0] = 0; // базовая стоимость — 0

        $this->handleOffers($offers, $N, $dp, $parent);

        // Если достигнуть N невозможно, вернуть пустой массив
        if ($dp[$N] === PHP_FLOAT_MAX) {
            return [];
        }

        // Восстановим решение, идя по массиву parent
        $res = [];
        for ($cur = $N; $cur > 0; ) {
            [$prev, $id, $batch] = $parent[$cur];
            $res[$id] = ($res[$id] ?? 0) + $batch;
            $cur = $prev;
        }

        // Сортируем по id для итогового вывода
        ksort($res);

        // Формируем финальный массив
        $out = [];
        foreach ($res as $id => $qty) {
            $out[] = ['id' => $id, 'qty' => $qty];
        }

        return $out;
    }

    private function handleOffers(array $offers, int $N, SplFixedArray $dp, SplFixedArray $parent): void
    {
        foreach ($offers as $offer) {
            $id = $offer['id'];
            $count = $offer['count'];
            $price = $offer['price'];
            $pack = $offer['pack'];

            if ($count < $pack) {
                continue; // пропускаем, если товаров меньше, чем упаковок
            }

            $maxUnits = intdiv($count, $pack) * $pack; // максимально возможное количество полностью упакованных товаров
            $parts = [];
            $left = intdiv($maxUnits, $pack);
            $qty = 1;

            // Разбиваем на части с помощью "размножения", чтобы учесть все комбинации
            while ($qty <= $left) {
                $parts[] = $qty * $pack;
                $left -= $qty;
                $qty <<= 1; // удваиваем
            }

            if ($left > 0) {
                $parts[] = $left * $pack; // остаток
            }

            // Обновляем dp для каждой части
            foreach ($parts as $batch) {
                $cost = $batch * $price;

                for ($cur = $N; $cur >= $batch; $cur--) {
                    $prevCost = $dp[$cur - $batch] + $cost;

                    if ($prevCost < $dp[$cur]) {
                        $dp[$cur] = $prevCost;
                        $parent[$cur] = [$cur - $batch, $id, $batch];
                    }
                }
            }
        }
    }
}