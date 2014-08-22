<?php
include('StrictDataStorage.php');
include('IEnumArrayable.php');
include('SampleEnum.php');
include('SampleDataStorage.php');

$a = new SampleDataStorage();

$a->integer = 1;
$a->callback = function () {
    return null;
};
$a->integers = [7, 1];
$a->enumFloat = 1.8;
$a->enumArray = ['two', 'one8'];
print_r($a);
