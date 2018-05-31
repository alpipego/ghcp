<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 31.05.18
 * Time: 13:12
 */
declare(strict_types = 1);

namespace Alpipego\GhCp;

class MetaDataParser
{
    private $metaInv = [
        [
            'key'      => 'title',
            'regex'    => '',
            'meta_key' => '',
        ],
        [
            'key'      => 'description',
            'regex'    => '',
            'meta_key' => '',
        ],
        [
            'key'      => 'robots',
            'regex'    => '',
            'meta_key' => '',
        ],
    ];

    public function __construct()
    {
        $this->metaInv = apply_filters('ghcp/meta/data', $this->metaInv);
    }


    public function parse(string $rawBody) : array
    {
        if ( ! preg_match('/^\s*<!--\s*(.+?)\s*-->/s', $rawBody, $metaArr)) {
            return [];
        }

        $metaArr = preg_split('/\r\n|\r|\n/', $metaArr[1]);
        foreach ($metaArr as $key => $meta) {
            $meta = array_map('trim', explode(':', $meta, 2));
            unset($metaArr[$key]);
            if ( ! isset($meta[1])) {
                continue;
            }

            $metaKey = array_search(strtolower($meta[0]), array_column($this->metaInv, 'key'));
            if ($metaKey === false) {
                continue;
            }

            if ( ! empty($this->metaInv[$metaKey]['regex']) && ! preg_match($this->metaInv[$metaKey]['regex'], $meta[1])) {
                continue;
            }

            $metaKey = ! empty($this->metaInv[$metaKey]['meta_key']) ? $this->metaInv[$metaKey]['meta_key'] : $this->metaInv[$metaKey]['key'];

            $metaArr[$metaKey] = $meta[1];
        }

        return $metaArr;
    }

    public function getInventory()
    {
        return $this->metaInv;
    }
}
