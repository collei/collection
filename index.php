<?php
/**
 * Loads the Composer-generated autoloader
 */
require __DIR__ . '/vendor/autoload.php';

/**
 * Loads the models
 **/
$models = ['Client','Invoice','InvoiceItem','Product'];

foreach ($models as $model) {
    require __DIR__ . '/Models/' . $model . '.php';
}

use Rovi\Metadata\Prospector;

$entites = [];

foreach ($models as $model) {
    $class = 'Models\\'.$model;

    $entities[$class] = new Prospector($class);
}


echo '<pre>';
print_r(compact('entities'));
echo '</pre>';

