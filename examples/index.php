<?php
require '../vendor/autoload.php';

$a = new PhpStrictData\SampleDataStorage();

$a->integer = 1;
// $a->integer = '7'; // error - not integer, enable StrictNumberTypeCheck

$a->integers = [7, 1, 9];
$a->integers = [3, 3, 3];
// $a->integers = ['7']; // error - exists not integer, enable StrictNumberTypeCheck
// $a->integers = []; // error - not exists integer
// $a->integers = [1,22]; // error - 22 not exists in SampleEnum

$a->enumFloat = '1.8';
$a->enumFloat = (float)7;
// $a->enumFloat = 7; // error - not float
// $a->enumFloat = '5'; // error - not exists in enum

$a->enumArray = ['two', 'one'];

$a->callback = function () {
    return null;
};
$a->mixed = null;

echo '<pre>';
var_dump($a);
echo '</pre>';
