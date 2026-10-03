# AuraTerra — Backend API REST

API RESTful desarrollada en PHP para el sistema de monitoreo agrometeorológico **AuraTerra**, encargada de procesar métricas climáticas en tiempo real, calcular índices térmicos y proveer servicios de datos para los clientes estáticos Web y Móvil.

---

## Tecnologías utilizadas

El sistema está desarrollado utilizando una arquitectura de API REST y las siguientes tecnologías principales:

* **PHP 8.2+** como lenguaje de programación del backend.
* **MySQL** como sistema gestor de base de datos relacional.
* **Eloquent ORM** mediante `illuminate/database` para el acceso y manejo de datos.
* **Composer** para la gestión de dependencias PHP.
* **vlucas/phpdotenv** para la gestión segura de variables de entorno (`.env`).
* **OpenWeather API** como proveedor externo de telemetría meteorológica.
* **Apache / XAMPP** como entorno de desarrollo local.

---

## Requerimientos de software

Para ejecutar el backend se requiere disponer de los siguientes componentes:

* **PHP 8.2** o superior.
* **Composer** instalado globalmente.
* **MySQL 8.0** o superior (incluido en XAMPP/WAMP).
* **XAMPP / WAMP** para disponer del servidor Apache y MySQL.
* **Git** para clonar el repositorio.

> **Nota:** El proyecto utiliza Eloquent ORM de forma desacoplada como ORM para el acceso a datos. **No requiere la instalación del framework Laravel**.

---

## Pasos para ejecutar el backend

### [1. Clonar el repositorio](#1-clonar-el-repositorio)
Clonar el proyecto mediante Git:
```bash
git clone [https://github.com/AuraTerra/auraterra-backend-api.git](https://github.com/AuraTerra/auraterra-backend-api.git)

## 2. Ingresar a la carpeta del proyecto
cd auraterra-backend-api

### 3. Instalar las dependencias de PHP
Ejecutar el gestor de dependencias:
composer install

#### 4. Configurar la base de datos
1. Abrir phpMyAdmin o tu cliente MySQL (mediante XAMPP / WAMP).
2. Crear una nueva base de datos llamada:
⁠auraterra_db⁠
3. Importar el archivo SQL con la estructura inicial ubicado dentro de la carpeta ⁠database/⁠:
 Seleccionar la base de datos ⁠auraterra_db⁠ en phpMyAdmin.
 Ir a la pestaña Importar y seleccionar el archivo ⁠database/auraterra_db.sql⁠ presente en este repositorio.

##### 5. Configurar las variables de entorno (.env)
Crear o renombrar un archivo denominado .env en la raíz del proyecto basándose en el ejemplo:
# Conexión a Base de Datos
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=auraterra_db
DB_USERNAME=root
DB_PASSWORD=

# Credenciales de API Meteorológica
OPENWEATHER_API_KEY=tu_api_key_de_openweather

# Configuración del Sistema
APP_ENV=development
APP_DEBUG=true

6. Ejecutar el backend
Puedes iniciar el servidor Backend mediante cualquiera de las dos modalidades locales:

Opción A: Servidor integrado de PHP (Recomendado)
Desde la terminal en la raíz del proyecto, ejecutar:
php -S localhost:8000 -t public
La API quedará escuchando en:
👉 http://localhost:8000

Opción B: Entorno local con XAMPP
1. Iniciar los servicios Apache y MySQL desde el Panel de Control de XAMPP.
2. Asegurarse de que el repositorio se encuentre dentro de la ruta C:\xampp\htdocs\Workspace_AuraTerra\auraterra-backend-api (o directamente en C:\xampp\htdocs\auraterra-backend-api).
3. Verificar que la base de datos auraterra_db esté importada.
4. Acceder desde el navegador o cliente API a:
👉 http://localhost/Workspace_AuraTerra/auraterra-backend-api/public/

Endpoints Principales de la API

Metodo GET. Endpoint: clima/actual?ciudad={nombre}. Descripción: Devuelve las condiciones meteorológicas en tiempo real de una localidad.
Metodo GET. Endpoint: clima/actual?lat={lat}&lon={lon}. Descripción: Devuelve el clima actual basándose en coordenadas GPS. 
Metodo GET. Endpoint clima/pronostico?ciudad={nombre}. Descripción: Devuelve el pronóstico extendido telemétrico de las próximas horas.
