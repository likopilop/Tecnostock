# TecnoStock — Sistema de Inventario

Proyecto individual del Caso 1 de Sistemas de Información. La aplicación permite gestionar productos, categorías y movimientos de existencias mediante PHP 8, PDO, MySQL/MariaDB, HTML5, Bootstrap y JavaScript.

## Funcionalidades

- Inicio y cierre de sesión.
- Registro del primer usuario administrador con contraseña protegida mediante `password_hash()`.
- Gestión de categorías: listar, buscar, crear, editar, desactivar y reactivar.
- Gestión de productos: registrar, listar, buscar por código/nombre/categoría, editar, desactivar y reactivar.
- Control de código de producto único.
- Validaciones para impedir valores negativos.
- Registro de entradas y salidas de inventario.
- Actualización automática del stock después de cada movimiento válido.
- Bloqueo de salidas que dejarían el stock bajo cero.
- Historial de movimientos con fecha, producto, tipo, cantidad y usuario responsable.
- Alertas visuales de stock bajo.
- Dashboard con indicadores y movimientos recientes.
- PDO con consultas preparadas, sesiones, CSRF y escape de salida HTML.

## Requisitos

- XAMPP con Apache, PHP 8 y MySQL/MariaDB.
- Navegador web moderno.
- Visual Studio Code u otro editor, solo si se desea modificar el código.

## Instalación desde cero

1. Copiar la carpeta `TecnoStock` a `C:\xampp\htdocs\TecnoStock`.
2. Iniciar Apache y MySQL desde XAMPP.
3. Abrir `http://localhost/phpmyadmin`.
4. Importar `database/tecnostock.sql`.
5. Revisar `config/database.php`. Por defecto usa:
   - host: `localhost`
   - puerto: `3306`
   - base: `tecnostock`
   - usuario: `root`
   - contraseña: vacía
6. Abrir `http://localhost/TecnoStock/crear_admin.php` y crear el primer usuario.
7. Iniciar sesión en `http://localhost/TecnoStock/`.

> ADVERTENCIA: `database/tecnostock.sql` comienza con `DROP DATABASE IF EXISTS tecnostock;`. Se usa para reconstruir una instalación limpia. No se debe reimportar sobre una base con datos que se quieran conservar.

## Estructura principal

```text
TecnoStock/
├── assets/
│   ├── css/
│   └── js/
├── categorias/
├── config/
├── database/
├── DOCUMENTACION/
├── evidencias/
├── includes/
├── movimientos/
├── productos/
├── crear_admin.php
├── dashboard.php
├── index.php
├── login.php
└── logout.php
```

## Seguridad y calidad

- PDO y consultas preparadas con `PDO::ATTR_EMULATE_PREPARES => false`.
- Contraseñas almacenadas con `password_hash()` y verificadas con `password_verify()`.
- Regeneración del ID de sesión después del inicio de sesión.
- Token CSRF en formularios que modifican información.
- Escape de contenido mostrado con `htmlspecialchars()`.
- Validaciones en cliente y servidor.
- Transacciones para registrar movimientos y actualizar el stock de forma atómica.
- `SELECT ... FOR UPDATE` para proteger el stock durante un movimiento.
- Desactivación lógica de productos en lugar de eliminación física.

