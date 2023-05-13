<?php

namespace App\Models\whatsapp\messages;

class SendMessage {

    public string $messaging_product = "whatsapp";
    public string $recipient_type = "individual";
    public string $to;
    public string $type;

    public function __construct(string $to, string $type)
    {
        $this->to = $to;
        $this->type = $type;
    }
}