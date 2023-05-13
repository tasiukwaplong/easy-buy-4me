<?php

namespace App\Models\whatsapp\messages;

use App\Models\whatsapp\messages\partials\interactive\Interactive;

class InteractiveSendMessage extends SendMessage {

    public Interactive $interactive;

    public function __construct(string $to, string $type, Interactive $interactive)
    {
        parent::__construct($to, $type);
        $this->interactive = $interactive;
    }

}