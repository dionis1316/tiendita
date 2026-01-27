# Cambios implementados

## Resumen funcional
- Validacion de pagos en checkout: Yappy/Transferencia/Efectivo requieren adjunto; se guarda comprobante/foto.
- Yappy muestra numero `6910-0451`.
- Logo agregado en header y en login cliente/admin.
- Auditoria: log de logins exitosos y carritos agregados; admin puede ver actividad con filtros.
- Busqueda por texto en tienda, admin productos y admin clientes.
- Busqueda automatica (sin recargar) en tienda, admin productos y admin clientes.
- Mensaje adicional en confirmacion de compra.
- Endurecimiento rapido de seguridad (errores, uploads, .htaccess, env vars).
- Remitente de correos actualizado a `noreply@dcsolution.net`.

## Cambios por archivo
- `app/Views/store/checkout.php`
  - Opciones de pago: Yappy con numero, Transferencia y Efectivo.
  - Campo de adjunto obligatorio segun metodo, con etiquetas dinamicas.
- `app/Controllers/CheckoutController.php`
  - Acepta metodo `transfer`.
  - Requiere comprobante/foto para yappy/transfer/cash.
  - Marca logs de carrito como completados al finalizar pedido.
  - Validacion de MIME y verificacion de upload para comprobantes.
- `app/Controllers/CartController.php`
  - Log de `cart_add` al agregar al carrito.
- `app/Controllers/AuthController.php`
  - Log de `login_success` al iniciar sesion correctamente.
- `app/Core/ActivityLogger.php`
  - Nueva clase para registrar actividad y marcar carritos completados.
- `app/Controllers/Admin/AdminActivityController.php`
  - Nueva vista de actividad con filtros (tipo, fechas, busqueda, cliente, producto).
- `app/Views/admin/activity/index.php`
  - UI de actividad con filtros y tabla de resultados.
- `app/Views/admin/_layout.php`
  - Enlace de menu a `Actividad`.
- `public/index.php`
  - Ruta `GET /admin/activity`.
  - Errores solo visibles en `APP_ENV=dev`.
- `app/Views/partials/header.php`
  - Logo en header + estilos responsivos.
- `app/Views/auth/login.php`
  - Logo en login de cliente.
- `app/Views/admin/login.php`
  - Logo en login de admin.
- `app/Controllers/StoreController.php`
  - Busqueda por texto en listado de tienda.
- `app/Views/store/home.php`
  - Formulario de busqueda en tienda.
  - Filtro automatico en vivo por texto (JS).
- `app/Controllers/Admin/AdminProductsController.php`
  - Busqueda por texto en admin productos.
- `app/Views/admin/products/index.php`
  - Formulario de busqueda en admin productos.
  - Filtro automatico en vivo por texto (JS).
- `app/Controllers/Admin/AdminCustomersController.php`
  - Busqueda por texto en admin clientes.
- `app/Views/admin/customers/index.php`
  - Formulario de busqueda en admin clientes.
  - Filtro automatico en vivo por texto (JS).
- `app/Views/store/checkout_success.php`
  - Agrega mensaje: "Gracias por tu compra y agradecemos tu honestidad. Recuerda que Dios te ve."
- `app/Config/config.php`
  - Soporte de variables de entorno para DB/APP/SMTP.
  - Remitente SMTP actualizado a `noreply@dcsolution.net`.
- `public/.htaccess`
  - Bloquea acceso a `.git` y `.env`, desactiva indexado.
- `public/uploads/.htaccess`
  - Bloquea ejecucion de scripts en uploads.

## Archivos estaticos
- `public/assets/logo-evy.jpeg` (logo agregado)

## Cambios en base de datos
- Tabla `activity_logs`:
  - Campos: `user_id`, `type`, `product_id`, `product_name`, `quantity`, `ip`, `user_agent`, `created_at`, `completed_at`.
  - Indices: `user_id`, `type`, `created_at`, `completed_at`.
