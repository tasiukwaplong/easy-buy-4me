<?php

namespace App\Models\whatsapp\messages;

use App\Models\whatsapp\messages\partials\BodyText;
use App\Models\whatsapp\messages\SendMessage;

class TextSendMessage extends SendMessage
{
    public BodyText $text;

    public function __construct(string $to, string $type, BodyText $text)
    {
        parent::__construct($to, $type);
        $this->text = $text;
    }
}
