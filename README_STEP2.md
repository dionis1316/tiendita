# Tiendita de Evy — Paso 2: Skeleton PHP

## Estructura
- public/
  - index.php (router frontal)
  - .htaccess (rewrite a index.php)
- app/Config/
  - config.php (ajusta credenciales)
  - db.php (PDO)
- app/Core/
  - Router.php (router mínimo con parámetros)
- app/Controllers/
  - StoreController.php (home)
- app/Views/
  - partials/header.php, footer.php
  - store/home.php

## Instalar
1) Copia todo el contenido del zip a tu carpeta web (ej. /var/www/tiendita).
   - El DocumentRoot de Apache debe apuntar a: /var/www/tiendita/public
2) Edita `app/Config/config.php` con tu usuario/clave/BD.
3) Navega a `/` y verifica que ves la home simple.

## Próximo paso
- Conectar el catálogo real (SELECT products WHERE is_active=1 AND stock>0).
- Agregar rutas de carrito y checkout.
- Luego añadimos crédito y admin paso a paso.
