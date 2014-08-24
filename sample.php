<?php
include('StrictDataStorage.php');
include('EnumArrayableInterface.php');
include('SampleEnum.php');
include('SampleDataStorage.php');

$a = new SampleDataStorage();

$a->integer = 1;
$a->callback = function () {
    return null;
};
$a->mixed = null;

$a->integers = [7, 1, 9];
$a->enumFloat = '1.8';
$a->enumArray = ['two', 'one'];

var_dump($a);
