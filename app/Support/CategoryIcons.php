<?php

namespace App\Support;

class CategoryIcons
{
    /**
     * Catalog of selectable category icons.
     * Each entry: label, keywords (for name suggestions), color (icon), bg (circle).
     *
     * @return array<string, array{label: string, keywords: list<string>, color: string, bg: string}>
     */
    public static function catalog(): array
    {
        return [
            'phone' => [
                'label' => 'Phone',
                'keywords' => ['phone', 'smartphone', 'mobile', 'iphone', 'android', 'cell'],
                'color' => '#2563eb',
                'bg' => '#dbeafe',
            ],
            'laptop' => [
                'label' => 'Laptop',
                'keywords' => ['laptop', 'notebook', 'macbook', 'computer', 'pc'],
                'color' => '#7c3aed',
                'bg' => '#ede9fe',
            ],
            'tablet' => [
                'label' => 'Tablet',
                'keywords' => ['tablet', 'ipad', 'tab'],
                'color' => '#0891b2',
                'bg' => '#cffafe',
            ],
            'headphones' => [
                'label' => 'Headphones',
                'keywords' => ['headphone', 'earphone', 'earbuds', 'airpod', 'headset', 'audio'],
                'color' => '#db2777',
                'bg' => '#fce7f3',
            ],
            'watch' => [
                'label' => 'Watch',
                'keywords' => ['watch', 'smartwatch', 'wearable', 'band'],
                'color' => '#ea580c',
                'bg' => '#ffedd5',
            ],
            'camera' => [
                'label' => 'Camera',
                'keywords' => ['camera', 'dslr', 'photo', 'lens', 'gopro'],
                'color' => '#4f46e5',
                'bg' => '#e0e7ff',
            ],
            'battery' => [
                'label' => 'Battery',
                'keywords' => ['battery', 'batteries', 'powerbank', 'power bank', 'cell'],
                'color' => '#16a34a',
                'bg' => '#dcfce7',
            ],
            'charger' => [
                'label' => 'Charger',
                'keywords' => ['charger', 'charging', 'adapter', 'adaptor', 'plug'],
                'color' => '#ca8a04',
                'bg' => '#fef9c3',
            ],
            'cable' => [
                'label' => 'Cable',
                'keywords' => ['cable', 'wire', 'usb', 'type-c', 'typec', 'lightning', 'hdmi'],
                'color' => '#0d9488',
                'bg' => '#ccfbf1',
            ],
            'speaker' => [
                'label' => 'Speaker',
                'keywords' => ['speaker', 'soundbar', 'bluetooth speaker', 'boombox'],
                'color' => '#e11d48',
                'bg' => '#ffe4e6',
            ],
            'game' => [
                'label' => 'Gaming',
                'keywords' => ['game', 'gaming', 'console', 'playstation', 'xbox', 'nintendo', 'controller'],
                'color' => '#9333ea',
                'bg' => '#f3e8ff',
            ],
            'mouse' => [
                'label' => 'Mouse',
                'keywords' => ['mouse', 'mice'],
                'color' => '#0284c7',
                'bg' => '#e0f2fe',
            ],
            'keyboard' => [
                'label' => 'Keyboard',
                'keywords' => ['keyboard', 'keypad'],
                'color' => '#4338ca',
                'bg' => '#e0e7ff',
            ],
            'monitor' => [
                'label' => 'Monitor',
                'keywords' => ['monitor', 'display', 'screen', 'desktop'],
                'color' => '#0369a1',
                'bg' => '#e0f2fe',
            ],
            'tv' => [
                'label' => 'TV',
                'keywords' => ['tv', 'television', 'smart tv', 'oled'],
                'color' => '#1d4ed8',
                'bg' => '#dbeafe',
            ],
            'drone' => [
                'label' => 'Drone',
                'keywords' => ['drone', 'quadcopter', 'uav'],
                'color' => '#0f766e',
                'bg' => '#ccfbf1',
            ],
            'router' => [
                'label' => 'Router',
                'keywords' => ['router', 'wifi', 'wi-fi', 'modem', 'network', 'internet'],
                'color' => '#c026d3',
                'bg' => '#fae8ff',
            ],
            'memory' => [
                'label' => 'Storage',
                'keywords' => ['memory', 'ssd', 'hdd', 'storage', 'flash', 'pendrive', 'usb drive', 'sd card'],
                'color' => '#b45309',
                'bg' => '#fef3c7',
            ],
            'chip' => [
                'label' => 'Chip',
                'keywords' => ['chip', 'processor', 'cpu', 'gpu', 'ram', 'electronics'],
                'color' => '#be123c',
                'bg' => '#ffe4e6',
            ],
            'accessory' => [
                'label' => 'Accessory',
                'keywords' => ['accessory', 'accessories', 'case', 'cover', 'stand', 'holder', 'gadget'],
                'color' => '#64748b',
                'bg' => '#f1f5f9',
            ],
            'box' => [
                'label' => 'General',
                'keywords' => ['box', 'general', 'other', 'misc', 'product'],
                'color' => '#475569',
                'bg' => '#e2e8f0',
            ],
        ];
    }

    public static function keys(): array
    {
        return array_keys(self::catalog());
    }

    public static function default(): string
    {
        return 'box';
    }

    public static function resolve(?string $icon): string
    {
        $icon = strtolower(trim((string) $icon));

        return isset(self::catalog()[$icon]) ? $icon : self::default();
    }

    public static function meta(?string $icon): array
    {
        $key = self::resolve($icon);

        return array_merge(['key' => $key], self::catalog()[$key]);
    }

    /**
     * Suggest the best icon key from a category name.
     */
    public static function suggest(?string $name): string
    {
        $haystack = strtolower(trim((string) $name));
        if ($haystack === '') {
            return self::default();
        }

        $best = null;
        $bestScore = 0;

        foreach (self::catalog() as $key => $meta) {
            foreach ($meta['keywords'] as $keyword) {
                $keyword = strtolower($keyword);
                if ($haystack === $keyword) {
                    return $key;
                }
                if (str_contains($haystack, $keyword)) {
                    $score = strlen($keyword);
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $best = $key;
                    }
                }
            }
        }

        return $best ?? self::default();
    }

    /**
     * Ranked suggestions for UI highlighting (best first).
     *
     * @return list<string>
     */
    public static function suggestions(?string $name, int $limit = 6): array
    {
        $haystack = strtolower(trim((string) $name));
        $scores = [];

        foreach (self::catalog() as $key => $meta) {
            $score = 0;
            foreach ($meta['keywords'] as $keyword) {
                $keyword = strtolower($keyword);
                if ($haystack === $keyword) {
                    $score = max($score, 100 + strlen($keyword));
                } elseif ($haystack !== '' && str_contains($haystack, $keyword)) {
                    $score = max($score, 50 + strlen($keyword));
                } elseif ($haystack !== '' && str_contains($keyword, $haystack)) {
                    $score = max($score, 20 + strlen($haystack));
                }
            }
            if ($score > 0) {
                $scores[$key] = $score;
            }
        }

        arsort($scores);
        $keys = array_keys($scores);

        if ($keys === []) {
            return array_slice(self::keys(), 0, $limit);
        }

        return array_slice($keys, 0, $limit);
    }

    /**
     * Compact catalog for Alpine / JSON.
     */
    public static function forPicker(): array
    {
        $out = [];
        foreach (self::catalog() as $key => $meta) {
            $out[] = [
                'key' => $key,
                'label' => $meta['label'],
                'keywords' => $meta['keywords'],
                'color' => $meta['color'],
                'bg' => $meta['bg'],
            ];
        }

        return $out;
    }
}
