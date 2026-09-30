<?php

/**
 * Plugin Name: WP Stash Test Plugin
 * Plugin URI: https://github.com/inpsyde/WP-Stash
 * Description: Exposes the object cache via REST so the E2E suite can verify it across requests. Never use in production.
 * Version: 2.0
 * Author: Moritz Meißelbach
 * Author URI:
 * License: MIT
 */

declare(strict_types=1);

namespace Inpsyde\WpStashTest;

use Inpsyde\WpStash\WpStash;
use Stash\Driver\Composite;

const REST_NAMESPACE = 'wp-stash-test/v1';

/**
 * Each request starts with a fresh in-memory layer, so everything read here in a later request
 * than it was written has gone through the configured persistent Stash driver.
 */
add_action('rest_api_init', static function (): void {
    register_rest_route(REST_NAMESPACE, '/info', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => static function (): array {
            global $wp_object_cache;

            $driver = WpStash::instance()->driver();
            $drivers = [];
            if ($driver instanceof Composite) {
                $property = new \ReflectionProperty(Composite::class, 'drivers');
                $property->setAccessible(true);
                $drivers = array_map('get_class', $property->getValue($driver));
            }

            return [
                'usingExtObjectCache' => wp_using_ext_object_cache(),
                'objectCache' => is_object($wp_object_cache) ? get_class($wp_object_cache) : null,
                'driver' => get_class($driver),
                'drivers' => $drivers,
            ];
        },
    ]);

    $args = [
        'group' => ['type' => 'string', 'default' => 'default'],
        // Groups have to be registered as non-persistent on every request, like core does it
        'nonPersistent' => ['type' => 'boolean', 'default' => false],
        // "single" uses wp_cache_{get,set,delete}(), "multiple" the *_multiple() variants
        'mode' => ['type' => 'string', 'enum' => ['single', 'multiple'], 'default' => 'single'],
    ];

    register_rest_route(REST_NAMESPACE, '/cache', [
        [
            'methods' => 'GET',
            'permission_callback' => '__return_true',
            'args' => $args + [
                'keys' => ['type' => 'array', 'items' => ['type' => 'string'], 'required' => true],
            ],
            'callback' => static function (\WP_REST_Request $request): array {
                $group = prepareGroup($request);
                $keys = $request['keys'];

                if ($request['mode'] === 'multiple') {
                    $values = wp_cache_get_multiple($keys, $group);

                    return array_map(static function ($value): array {
                        return ['found' => $value !== false, 'value' => $value];
                    }, $values);
                }

                $result = [];
                foreach ($keys as $key) {
                    $found = false;
                    $value = wp_cache_get($key, $group, false, $found);
                    $result[$key] = ['found' => $found, 'value' => $value];
                }

                return $result;
            },
        ],
        [
            'methods' => 'POST',
            'permission_callback' => '__return_true',
            'args' => $args + [
                'items' => ['type' => 'object', 'required' => true],
                'expire' => ['type' => 'integer', 'default' => 0],
                'op' => ['type' => 'string', 'enum' => ['set', 'add', 'replace'], 'default' => 'set'],
            ],
            'callback' => static function (\WP_REST_Request $request) {
                $group = prepareGroup($request);
                $items = (array) $request['items'];
                $expire = (int) $request['expire'];
                $op = $request['op'];

                if ($request['mode'] === 'multiple') {
                    if ($op === 'replace') {
                        return new \WP_Error('unsupported', 'There is no wp_cache_replace_multiple()', ['status' => 400]);
                    }

                    return $op === 'add'
                        ? wp_cache_add_multiple($items, $group, $expire)
                        : wp_cache_set_multiple($items, $group, $expire);
                }

                $function = 'wp_cache_' . $op;
                $result = [];
                foreach ($items as $key => $value) {
                    $result[$key] = $function($key, $value, $group, $expire);
                }

                return $result;
            },
        ],
        [
            'methods' => 'DELETE',
            'permission_callback' => '__return_true',
            'args' => $args + [
                'keys' => ['type' => 'array', 'items' => ['type' => 'string'], 'required' => true],
            ],
            'callback' => static function (\WP_REST_Request $request): array {
                $group = prepareGroup($request);
                $keys = $request['keys'];

                if ($request['mode'] === 'multiple') {
                    return wp_cache_delete_multiple($keys, $group);
                }

                $result = [];
                foreach ($keys as $key) {
                    $result[$key] = wp_cache_delete($key, $group);
                }

                return $result;
            },
        ],
    ]);

    register_rest_route(REST_NAMESPACE, '/cache/(?P<op>incr|decr)', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'args' => $args + [
            'key' => ['type' => 'string', 'required' => true],
            'offset' => ['type' => 'integer', 'default' => 1],
        ],
        'callback' => static function (\WP_REST_Request $request): array {
            $group = prepareGroup($request);
            $function = 'wp_cache_' . $request['op'];

            return ['value' => $function($request['key'], (int) $request['offset'], $group)];
        },
    ]);
});

function prepareGroup(\WP_REST_Request $request): string
{
    $group = (string) $request['group'];
    if ($request['nonPersistent']) {
        wp_cache_add_non_persistent_groups([$group]);
    }

    return $group;
}
