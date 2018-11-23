<?php declare(strict_types=1); // -*- coding: utf-8 -*-

namespace Inpsyde\WpStash\Admin;

class MenuItem
{

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $href;

    public function __construct(
        string $id,
        string $title,
        string $href
    ) {

        $this->id = $id;
        $this->title = $title;
        $this->href = $href;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function href(): string
    {
        return $this->href;
    }
}
