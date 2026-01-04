<?php
return [
  'db' => [
    'host' => 'localhost',
    'name' => 'tiendita',
    'user' => 'root',
    'pass' => 'CobyFiguer11-',
    'charset' => 'utf8mb4'
  ],
  'app' => [
    'env' => 'dev',    // 'prod' en produccion
    'base_url' => '/tiendita/', // ajusta si usas subcarpeta
    'base_url_full' => 'https://dcsolution.net/tiendita',
    'csrf_key' => '6c28f6ba4d096bb6e52ee2de4b3e19e01d04a58e87bdc28da5e09a0fa73a9035'
  ],
  'smtp' => [
    'host' => 'dcsolution-net.mail.protection.outlook.com',
    'port' => 25,
    'user' => 'info@dcsolution.net',
    'pass' => 'CobyFiguer11@2',
    'secure' => '',
    'from' => 'info@dcsolution.net'
  ]
];
