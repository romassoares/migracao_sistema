<?php

function RemoveStrangeCharacter($string)
{
    if ($string === '' || $string === null) {
        return $string;
    }

    // Remove BOM
    if (strncmp($string, "\xEF\xBB\xBF", 3) === 0) {
        $string = substr($string, 3);
    }

    $string = convertToUtf8($string);

    // Map of known mis-encoded sequences → correct chars
    static $replacePairs = [
        '‡Ã' => 'O',
        'Ã' => 'Í',
        'Ã“' => 'Ó',
        'Ã‡' => 'Ç',
        'Ã”' => 'Ô',
        'Ã‰' => 'É',
        'Ãƒ' => 'Ã',
        'ÃŠ' => 'Ê',
        'Ã€' => 'À',
        'Ã•' => 'Õ',
        'Ãš' => 'Ú',
        'Ã›' => 'Û',
        'Ãœ' => 'Ü',
        'Ã„' => 'Ä',
        'Ã‹' => 'Ë',
        'ÃŒ' => 'Ì',
        'ÃŽ' => 'Î',
        'Ã¯' => 'Ï',
        'Ã³' => 'ó',
        'Ã§' => 'ç',
        'Ã¢' => 'â',
        'Ãª' => 'ê',
        'Ã¡' => 'á',
        'Ã©' => 'é',
        'Ã´' => 'ô',
        'Ã£' => 'ã',
        'Ãº' => 'ú',
        'Ã¹' => 'ù',
        'Ã»' => 'û',
        'Ã¼' => 'ü',
        'Ã¤' => 'ä',
        'Ã«' => 'ë',
        'Ã®' => 'î',
        'Ã¬' => 'ì',
        'Â²' => '²',
        'Ã ' => 'à',
        'â€“' => '–',
        'â€”' => '—',
        'â€˜' => '‘',
        'â€™' => '’',
        'â€œ' => '“',
        'â€' => '”',
        'â€¢' => '•',
        'â€¦' => '…',
        'â€' => '†',
        'Â©' => '©',
        'Â®' => '®',
        'Â±' => '±',
        'Âµ' => 'µ',
        'Â¥' => '¥',
        'Â§' => '§',
        'Â«' => '«',
        'Â»' => '»',
        'Â°' => '°',
        'Â¶' => '¶',
        'Ã½' => 'ý',
        'Ã¿' => 'ÿ',
        'Ã–' => 'Ö',
        'ÃŸ' => 'ß',
        'Ã†' => 'Æ',
        'Ã˜' => 'Ø',
        'Ã…' => 'Å',
        'Ã²' => 'ò',
        'Ã­' => 'í',
        'Â¾' => '¾',
        'Â½' => '½',
        'Â¼' => '¼',
        'Â¢' => '¢',
        'Â£' => '£',
        'Â¤' => '¤',
        'Â¬' => '¬',
        'â‚¬' => '€',
        'â„¢' => '™'
    ];

    $string = preg_replace_callback(
        '/' . implode('|', array_map('preg_quote', array_keys($replacePairs))) . '/u',
        fn($m) => $replacePairs[$m[0]],
        $string
    );

    // Remove carcateres invisiveis
    $string = preg_replace('/[\x{2060}\x{0303}\x{FE0F}\xCC\x83]/u', '', $string);

    $string = removeEmojis($string);

    // Normalize UTF-8 (fix combining accents)
    if (class_exists('Normalizer')) {
        $string = Normalizer::normalize($string, Normalizer::FORM_C);
    }

    return trim($string);
}

function removeEmojis($text)
{
    // Removes ALL emojis + pictographs in one pass
    $text = preg_replace('/\p{Emoji}+/u', '', $text);

    // Removes odd symbols, dingbats, control chars
    return preg_replace('/[\p{So}\p{Cn}\p{Mn}]+/u', '', $text);
}


function convertToUtf8($string)
{
    $enc = mb_detect_encoding(
        $string,
        ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'],
        true
    );

    if ($enc === 'UTF-8') {
        return $string;
    }

    // desconhecido assume Latin-1 
    if ($enc === false) {
        return mb_convert_encoding($string, 'UTF-8', 'ISO-8859-1');
    }

    return mb_convert_encoding($string, 'UTF-8', $enc);
}
