<?php

declare(strict_types=1);

use Inpsyde\WpStash\WpStash;
use Stash\Driver\Composite;

?>
<html>
<head><?php

    wp_head(); ?>
<style>
    .pass{
        background: #5cab5f;
    }
    .fail{
        background: #c2865c;
    }
</style>
</head>
<body>
<?php
wp_body_open();
wp_cache_flush();
?>
<h1>WP Stash</h1>
<section>
    <h2>Environment</h2>
    <?php

    $stash = WpStash::instance();
    (new \Inpsyde\WpStashTest\Test(
        'Using object cache',
        function () {
            if (!wp_using_ext_object_cache()) {
                throw new Exception("WordPress does not have object caching configured");
            }
        }
    ))->execute();
    (new \Inpsyde\WpStashTest\Test(
        'Using WP-Stash',
        function () {
            $stash = WpStash::instance();
            if (!get_class($stash->driver()) === Composite::class) {
                throw new Exception("WpStash does not use the expected driver");
            }
        }
    ))->execute();
    ?>
</section>
<section>
    <h2>Single cache entries</h2>
    <?php

    (new \Inpsyde\WpStashTest\Test(
        'Get unset key',
        function () {
            $key = 'wp-stash.single';
            $value = wp_cache_get($key);
            if (!empty($value)) {
                throw new Exception("Expected cache to be empty");
            }
        }
    ))->execute();

    (new \Inpsyde\WpStashTest\Test(
        'Set single',
        function () {
            $key = 'wp-stash.single';
            $expectedValue = uniqid();
            wp_cache_set($key, $expectedValue);
            $value = wp_cache_get($key);
            if (!$expectedValue === $value) {
                throw new Exception("Cache does not return the same value");
            }
        }
    ))->execute();

    (new \Inpsyde\WpStashTest\Test(
        'Delete single',
        function () {
            $key = 'wp-stash.single';
            wp_cache_delete($key);
            $value = wp_cache_get($key);
            if (!empty($value)) {
                throw new Exception("Cache not empty after deleting");
            }
        }
    ))->execute();
    ?>
</section>
<section>
    <h2>Cache groups (unimplemented)</h2>
    <?php
    $key = 'wp-stash.group';
    $single = wp_cache_get($key);

    (new \Inpsyde\WpStashTest\Test(
        'Get unset group',
        function () {
            $results = wp_cache_get_multiple(['foo', 'bar', 'baz']);
            echo 'Expecting all these values to be false:'.PHP_EOL;
            var_dump($results);
            if (!empty(array_filter($results))) {
                throw new Exception("There should be no truthy values here");
            }
        }
    ))->execute();

    (new \Inpsyde\WpStashTest\Test(
        'Set multiple',
        function () {
            $values = [
                'foo' => 1,
                'bar' => 2,
                'baz' => 3,
            ];
            wp_cache_set_multiple($values);
            $results = wp_cache_get_multiple(['foo', 'bar', 'baz']);
            echo 'Expecting the cache to contain this exact array:'.PHP_EOL;
            var_dump($results);
            if (!empty(array_diff_key($values, $results))) {
                throw new Exception("There should be different keys");
            }
            if (!empty(array_diff($values, $results))) {
                throw new Exception("There should be different values");
            }
        }
    ))->execute();

    (new \Inpsyde\WpStashTest\Test(
        'Delete multiple',
        function () {
            $values = [
                'foo' => 1,
                'bar' => 2,
                'baz' => 3,
            ];
            wp_cache_set_multiple($values);
            wp_cache_delete_multiple(['foo', 'bar', 'baz']);
            $results = wp_cache_get_multiple(['foo', 'bar', 'baz']);
            echo 'Expecting all these values to be false:'.PHP_EOL;
            var_dump($results);
            if (!empty(array_filter($results))) {
                throw new Exception("There should be no truthy values here");
            }
        }
    ))->execute();
    ?>
</section>
<?php
wp_footer(); ?>
</body>
</html>
