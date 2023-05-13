<?php

namespace App\Models\whatsapp\messages\partials\interactive;

class Row
{
    public string $id;
    public string $title;
    public string $description;

    public function __construct(string $id, string $title, string $description)
    {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
    }
}
