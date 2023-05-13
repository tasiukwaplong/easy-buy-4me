<?php

namespace App\Models\whatsapp\messages\partials\interactive;

class Interactive {
    
    public string $type;
    public Header $header;
    public $body;
    public $footer;
    public Action $action;

    
    public function __construct(string $type, Header $header, $body, $footer, Action $action)
    {
        $this->type = $type;
        $this->header = $header;
        $this->body = $body;
        $this->footer = $footer;
        $this->action = $action;

    }
}