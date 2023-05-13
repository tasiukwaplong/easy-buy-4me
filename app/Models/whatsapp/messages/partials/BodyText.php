<?php

namespace App\Models\whatsapp\messages\partials;

class BodyText { 

    public bool $preview_url;
    public string $body;

    public function __construct(string $body, bool $preview_url)
    {
        $this->body = $body;
        $this->preview_url = $preview_url;
    }
}