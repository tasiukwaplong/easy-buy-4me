<?php

namespace App\Models\whatsapp\messages\partials\interactive;

class Header
{
    public string $type;
    public string $text;

    public function __construct(string $type, string $text)
    {
        $this->type = $type;
        $this->text = $text;
    }
}
