<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/api/transactions?type=expense', 'GET');
var_dump($request->has('type'));
var_dump($request->type);
var_dump($request->query('type'));
