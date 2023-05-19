<?php

namespace App\Models\whatsapp\messages;

use App\Models\whatsapp\messages\SendMessage;

class MediaSendMessage extends SendMessage {

    public array $image;

    public function __construct(string $to, string $type, $image)
    {
        parent::__construct($to, $type);
        $this->image = $image;
    }

}