<?php
require_once __DIR__ . '/Parsedown.php';

function markdown($text)
{
    $parsedown = new Parsedown();
    $parsedown->setSafeMode(false);
    return $parsedown->text($text);
}
