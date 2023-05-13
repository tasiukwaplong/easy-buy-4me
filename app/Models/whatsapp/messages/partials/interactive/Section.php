<?php

namespace App\Models\whatsapp\messages\partials\interactive;

class Section
{
    public string $title;
    public array $rows;

    public function __construct(string $title, array $rows)
    {
        $this->title = $title;
        $this->rows = $rows;
    }
}
