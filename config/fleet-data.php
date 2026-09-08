<?php
require_once __DIR__ . '/database.php';

function getFleetCars(PDO $pdo, string $assetPrefix = ''): array
{
    $sql = "SELECT
                c.id,
                c.brand,
                c.model,
                c.category,
                c.seats,
                c.fuel_type,
                c.image,
                v.id AS variant_id,
                v.transmission,
                v.daily_rate
            FROM cars c
            INNER JOIN car_variants v ON v.car_id = c.id
            WHERE c.status = 'active'
              AND v.status = 'available'
              AND v.quantity > 0
            ORDER BY c.brand, c.model, v.daily_rate, v.transmission";

    $rows = $pdo->query($sql)->fetchAll();
    $cars = [];

    foreach ($rows as $row) {
        $carId = (int)$row['id'];

        if (!isset($cars[$carId])) {
            $cars[$carId] = [
                'id' => $carId,
                'name' => trim($row['brand'] . ' ' . $row['model']),
                'brand' => $row['brand'],
                'model' => $row['model'],
                'cat' => strtolower($row['category']),
                'label' => $row['category'],
                'seats' => (int)$row['seats'],
                'fuel' => $row['fuel_type'],
                'image' => !empty($row['image']) && !preg_match('~^(?:https?:)?//~i', $row['image']) ? $assetPrefix . ltrim($row['image'], '/') : ($row['image'] ?: ''),
                'variants' => [],
            ];
        }

        $cars[$carId]['variants'][] = [
            'id' => (int)$row['variant_id'],
            'transmission' => strtolower($row['transmission']),
            'transmissionLabel' => ucfirst(strtolower($row['transmission'])),
            'dailyRate' => (float)$row['daily_rate'],
        ];
    }

    return array_values($cars);
}
