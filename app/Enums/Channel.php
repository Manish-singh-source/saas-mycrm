<?php

namespace App\Enums;

enum Channel: string
{
    case Email = 'email';
    case Sms = 'sms';
    case WhatsApp = 'whatsapp';
    case Push = 'push';
    case InApp = 'in_app';
    case Call = 'call';
}