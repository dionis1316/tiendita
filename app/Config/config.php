<?php
return [
  'db' => [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'name' => getenv('DB_NAME') ?: 'tiendita',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASS') ?: 'CobyFiguer11-',
    'charset' => getenv('DB_CHARSET') ?: 'utf8mb4'
  ],
  'app' => [
    'env' => getenv('APP_ENV') ?: 'prod',    // 'prod' en produccion
    'base_url' => getenv('APP_BASE_URL') ?: '/tiendita/', // ajusta si usas subcarpeta
    'base_url_full' => getenv('APP_BASE_URL_FULL') ?: 'https://dcsolution.net/tiendita',
    'csrf_key' => getenv('APP_CSRF_KEY') ?: '6c28f6ba4d096bb6e52ee2de4b3e19e01d04a58e87bdc28da5e09a0fa73a9035'
  ],
  'smtp' => [
    'host' => getenv('SMTP_HOST') ?: 'dcsolution-net.mail.protection.outlook.com',
    'port' => (int)(getenv('SMTP_PORT') ?: 25),
    'user' => getenv('SMTP_USER') ?: 'noreply@dcsolution.net',
    'pass' => getenv('SMTP_PASS') ?: 'CobyFiguer11@2',
    'secure' => getenv('SMTP_SECURE') ?: '',
    'from' => getenv('SMTP_FROM') ?: 'noreply@dcsolution.net'
  ]
];
