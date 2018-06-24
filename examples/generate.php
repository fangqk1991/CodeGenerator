<?php

use FC\Generator\Lib\Compiler;

require_once __DIR__ . '/../vendor/autoload.php';

$arr = [
    'one',
    'two',
    'three',
];

$map = [
    'No.1' => ['name' => 'ONE'],
    'No.2' => ['name' => 'TWO'],
    'No.3' => ['name' => 'THREE'],
];

$data = array(
    'first' => 'Hello World!',
    'arr' => $arr,
    'map' => $map,
);

$compiler = new Compiler(__DIR__ . '/example.tpl', $data);
$output = $compiler->compile();

echo $output;