<?php # -*- coding: utf-8 -*-

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


        $this->id    = $id;
        $this->title = $title;
        $this->href  = $href;
    }

    public function get_id(): string
    {

        return $this->id;

    }

    public function get_title(): string
    {

        return $this->title;
    }

    public function get_href(): string
    {

        return $this->href;

    }
}
