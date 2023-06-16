<?php

declare(strict_types=1);

namespace Inpsyde\WpStashTest;

class Test
{

    /**
     * @var string
     */
    private $test;

    /**
     * @var callable
     */
    private $assertion;

    /**
     * @var string
     */
    private $summary;

    public function __construct(
        string $test,
        callable $assertion,
        string $summary = ''
    ) {
        $this->test = $test;
        $this->assertion = $assertion;
        $this->summary = $summary;
    }

    public function execute()
    {
        $result = false;
        try {
            ob_start();
            ($this->assertion)();
            $result = true;
            $message = ob_get_clean();
            if (empty($message)) {
                $message = 'No details available';
            }
        } catch (\Throwable $exception) {
            ob_get_clean();
            $message = $exception->getMessage();
        }
        ?>

        <details>
            <summary class="<?php
            echo $result
                ? 'pass'
                : 'fail' ?>">
                <?php
                echo esc_html($this->test)
                ?>
            </span>
            </summary>
            <?php
            echo $message ?>
        </details>

        <?php
    }
}
