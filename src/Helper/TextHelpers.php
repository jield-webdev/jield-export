<?php

namespace Jield\Export\Helper;

final class TextHelpers
{
    public static function beautifyTextValue(?string $value): string
    {
        if (null === $value) {
            return '';
        }

        return ucwords(string: trim(string: $value));
    }
}
