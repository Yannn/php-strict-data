<?php
require '../vendor/autoload.php';

$a = new PhpStrictData\Examples\SampleDataStorage();

$a->integer = 1;            // success
// $a->integer = '7';       // error - not integer, enable StrictNumberTypeCheck in SampleDataStorage

$a->integers = [7, 1, 9];   // success
$a->integers = [3, 3, 3];   // success
// $a->integers = ['7'];    // error - exists not integer, enable StrictNumberTypeCheck in SampleDataStorage
// $a->integers = [];       // error - not exists integer
// $a->integers = [1,22];   // error - 22 not exists in SampleEnum

$a->enumFloat = '1.8';      // success
$a->enumFloat = (float)7;   // success
// $a->enumFloat = 7;       // error - not float
// $a->enumFloat = '5';     // error - not exists in enum

$a->enumArray = ['two', 'one']; // success

$a->callback = function () {
    return null;
}; // success
$a->mixed = null;           // success

echo '<pre>';
var_dump($a);
echo '</pre>';
