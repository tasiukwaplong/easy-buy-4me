<?php

namespace App\Models\whatsapp\messages\partials\interactive;

class Interactive {
    
    public string $type;
    public $header;
    public $body;
    public $footer;
    public $action;

    
    public function __construct(string $type, $header, $body, $footer, $action)
    {
        $this->type = $type;
        $this->header = $header;
        $this->body = $body;
        $this->footer = $footer;
        $this->action = $action;

    }
}