<?php

namespace App\Models\whatsapp\messages\partials\interactive;

class Action
{
    public string $button;
    public array $sections;

    public function __construct(string $button, array $sections)
    {
        $this->button = $button;
        $this->sections = $sections;
    }
}
