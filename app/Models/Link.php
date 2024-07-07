<?php

namespace App\Models;

class Link
{
    public $code;
    public $title;
    public $description;
    public $url;
    public $color;
    public $is_action;
    public $n_results;

    public function __construct($code, $title, $description, $url, $color, $is_action, $n_results)
    {
        $this->code         = $code;
        $this->title        = $title;
        $this->description  = $description;
        $this->url          = $url;
        $this->color        = $color;
        $this->is_action    = $is_action;
        $this->n_results    = $n_results;
    }

    public static function create($code, $title, $description, $url, $color, $is_action, $n_results)
    {
        return new self($code, $title, $description, $url, $color, $is_action, $n_results);
    }
}
