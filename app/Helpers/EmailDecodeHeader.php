<?php

if (!function_exists('decodeMimeHeader')) {
    function decodeMimeHeader($string) {
        $elements = imap_mime_header_decode($string);
        $decoded = '';
        foreach ($elements as $element) {
            $decoded .= $element->text;
        }
        return $decoded;
    }
}
