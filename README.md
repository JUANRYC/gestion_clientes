# Prueba Técnica - Dts Gestión Clientes (Laravel 8 Full-Stack)

Esta es la solución a la Prueba Técnica implementando un API RESTful pura en Laravel 8 y un Frontend reactivo (Single Page Application type) usando vistas Blade, JavaScript Vanilla (Fetch API) y Bootstrap 5.

## Requisitos Previos

- **PHP**: ^8.0 (Laravel 8 requiere PHP >= 7.3.0, pero se recomienda 8.x)
- **Composer**: Instalado globalmente.
- **Servidor Web**: Apache, Nginx o la utilidad embebida de PHP.
- **MySQL / MariaDB**: Con un servidor de base de datos en ejecución.
- **Git**: Para clonar el repositorio.

## Base de Datos

El sistema asume que la base de datos se llama `dts_gestion_clentes_db`.
Si no la has creado, por favor ejecute lo siguiente en su gestor de MySQL:

```sql
CREATE DATABASE dts_gestion_clentes_db;
```

Asegúrate de que en el archivo `.env` estén las credenciales correctas:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dts_gestion_clentes_db
DB_USERNAME=root
DB_PASSWORD=
```

## Configuración y Ejecución

Sigue estos pasos para levantar el proyecto localmente.

1. **Clonar e instalar dependencias:**

```bash
git clone <url-del-repositorio>
cd dts-test
composer install
```

1. **Configuración del Entorno:**
Si el archivo `.env` no existe, cópielo desde el archivo de ejemplo y genere la clave de la aplicación:

```bash
cp .env.example .env
php artisan key:generate
```

Verifica que las credenciales de base de datos sean correctas dentro del `.env` (referirse a la sección *Base de Datos* arriba).

1. **Ejecutar Migraciones:**
Esto creará las tablas necesarias (`customers`, `orders`) y aplicará las relaciones foráneas para garantizar la integridad referencial.

```bash
php artisan migrate
```

1. **Levantar el Servidor Backend / Frontend:**
Al ser una aplicación monolítica estructurada, el mismo comando levantará la aplicación consumible desde el navegador.

```bash
php artisan serve
```

La aplicación estará disponible por defecto en: [http://localhost:8000](http://localhost:8000)

## Uso de la Aplicación

### Frontend

- Navega a `http://localhost:8000`. Eres redirigido a la gestión de **Clientes**.
- **Customers**: Listado reactivo con búsqueda por nombre y correo. Opciones para Crear, Editar y Eliminar de forma dinámica mediante ventanas Modales y Fetch API de JS.
- **Orders**: Listado reactivo con búsqueda por número de orden. Opciones para Crear, Editar y Eliminar. Al crear o editar, el formulario carga automáticamente todos los Clientes activos en un selector desplegable consumiendo el API interno.

### API (Backend)

Las rutas del API base son consumidas internamente pero pueden ser verificadas externamente a través de aplicaciones como Postman.

- `GET /api/customers` : Lista clientes. Acepta query `?search=valor`
- `POST /api/customers` : Crea cliente.
- `PUT /api/customers/{id}` : Actualiza cliente.
- `DELETE /api/customers/{id}` : Elimina cliente (Falla con 500 o manejado internamente si tiene órdenes hijas).
- `GET /api/orders` : Lista órdenes (con Customer incluído). Acepta query `?search=valor`
- `POST /api/orders` : Crea orden.
- `PUT /api/orders/{id}` : Actualiza orden.
- `DELETE /api/orders/{id}` : Elimina orden.

## Entregables adicionales (Imágenes/PDF)

Según las instrucciones, si necesita las capturas:

1. Levante la aplicación `php artisan serve`.
2. Diríjase a las vistas en el navegador (ej `localhost:8000`).
3. Tome capturas del listado, los formularios modales (crear/editar) y los Toast o Alerts de éxito al eliminar.
4. Tome una captura a su tabla de la base de datos `dts_gestion_clentes_db` en phpMyAdmin, DBeaver u otra herramienta.
5. Adjunte esas imágenes al PDF y envíe.
