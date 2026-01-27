# Tiendita - Documentacion Tecnica

## Resumen
Aplicacion PHP sin framework con MVC simple, router propio y panel admin. Incluye tienda, carrito, checkout con inventario, panel admin (productos, clientes, estado de cuenta, pagos), recuperacion de contrasena por email, reportes y exportacion CSV/PDF.

## Estructura del proyecto
- `public/index.php`
  - Punto de entrada. Autoload, rutas, guard de autenticacion.
- `app/Controllers/`
  - Controladores de tienda, auth, checkout, estado de cuenta del cliente.
- `app/Controllers/Admin/`
  - Panel admin: productos, clientes, dashboard.
- `app/Core/`
  - Utilidades: Router, Auth, Csrf, Mailer, helpers.
- `app/Views/`
  - Vistas de tienda y admin.
- `app/Config/`
  - Configuracion de DB, app y SMTP.
- `public/uploads/`
  - Archivos subidos (productos y comprobantes).

## Configuracion
Archivo: `app/Config/config.php`

- DB:
  - host, name, user, pass, charset
- App:
  - `base_url`: ruta relativa (ej: `/tiendita/`)
  - `base_url_full`: URL publica completa (ej: `https://dcsolution.net/tiendita`)
- SMTP:
  - host, port, user, pass, from, secure

## Rutas principales
### Tienda
- `GET /` Inicio
- `GET /product/{id}` Detalle producto
- `GET /cart` Carrito
- `POST /cart/add` Agregar
- `POST /cart/update` Actualizar
- `POST /cart/remove` Quitar
- `GET /checkout` Formulario
- `POST /checkout/submit` Confirmar
- `GET /checkout/success` Confirmacion

### Auth
- `GET /login`
- `POST /login`
- `GET /register`
- `POST /register`
- `POST /logout`

### Recuperacion de contrasena
- `GET /forgot` Solicitud
- `POST /forgot` Envio de email
- `GET /reset/{token}` Formulario
- `POST /reset/{token}` Guardar

### Cuenta cliente
- `GET /account/statement` Estado de cuenta (filtros y grafico)

### Admin
- `GET /admin` Dashboard
- `GET /admin/products` Lista
- `GET /admin/products/create` Crear
- `POST /admin/products/create` Guardar
- `GET /admin/products/{id}/edit` Editar
- `POST /admin/products/{id}/edit` Actualizar
- `POST /admin/products/{id}/activate` Activar
- `POST /admin/products/{id}/deactivate` Desactivar
- `GET /admin/customers` Lista
- `GET /admin/customers/{id}` Estado de cuenta
- `POST /admin/customers/{id}/payments` Registrar pago
- `POST /admin/customers/{id}/send` Enviar estado por email
- `GET /admin/customers/{id}/export/csv`
- `GET /admin/customers/{id}/export/pdf`

## Inventario
- Se descuenta en `CheckoutController::submitOrder()`.
- Se valida stock con `FOR UPDATE`.
- Si el stock no alcanza, se cancela el pedido.

## Subidas de archivos
- Productos: `public/uploads/products/`
- Comprobantes Yappi: `public/uploads/receipts/`
- Permisos: `www-data` con 755.

## Panel admin
- Dashboard con metricas y graficas (Chart.js).
- CRUD de productos con subida de imagen.
- Estado de cuenta por usuario (clientes y admins).
- Pagos manuales y movimientos de credito.
- Envio de estado por email.

## Recuperacion de contrasena
Tabla: `password_resets`
- Token SHA256
- Expira en 1 hora
- Se marca `used_at` al usar

## SMTP
Config actual (relay):
- Host: `dcsolution-net.mail.protection.outlook.com`
- Puerto: `25`
- From: `info@dcsolution.net`

## Helpers
Archivo: `app/Core/helpers.php`
- `asset_url($path)` resuelve URLs relativas

## Tabla de datos clave
- `users`
- `products`
- `orders`, `order_items`
- `credit_transactions`, `credit_debts`, `credit_accounts`
- `password_resets`

## Operaciones frecuentes
### Crear producto
1. Admin > Productos > Nuevo.
2. Sube imagen o URL.
3. Guardar.

### Enviar estado por correo
1. Admin > Clientes > Estado de cuenta.
2. Boton "Enviar por correo".

### Reset de contrasena
1. Login > Olvide mi contrasena.
2. Recibir email.
3. Abrir link y reset.

## Problemas comunes
- Imagen no se ve:
  - Verificar permisos de `public/uploads`.
  - Verificar `image_url` y `asset_url()`.
- Email no llega:
  - Verificar relay SMTP.
  - Verificar logs.
- Comprobante no visible:
  - Verificar `receipt_path` en `orders`.


## Diagramas
Ver docs/tiendita-docs.md y docs/diagrams/*.png

Diagramas SVG en docs/diagrams/*.svg
