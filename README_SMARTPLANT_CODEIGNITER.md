# SmartPlant CARE en CodeIgniter 4

Esta carpeta contiene SmartPlant CARE dentro de un proyecto CodeIgniter 4.7.3.

## Estructura

- `app/Config/Routes.php`: rutas simples de CodeIgniter con `get` y `post`.
- `app/Controllers/SmartPlant.php`: controlador principal que abre las pantallas de SmartPlant.
- `app/Controllers`: controladores y acciones del proyecto.
- `app/Views`: pantallas del proyecto.
- `app/Models/PlantaModel.php`: modelo CodeIgniter para la tabla `plantas`.
- `app/Libraries/Database.php`: conexion simple usada por el codigo existente.
- `public/assets`: CSS, imagenes y uploads publicos.
- `system`: framework CodeIgniter incluido.

## Ejecutar

Desde esta carpeta:

```bash
php spark serve
```

Luego abrir:

```text
http://localhost:8080/
```

Tambien quedan compatibles estas rutas:

```text
http://localhost:8080/
http://localhost:8080/login
http://localhost:8080/dashboard
http://localhost:8080/devices/new
http://localhost:8080/hardware/connect
http://localhost:8080/store
```

## Conexion de hardware ESP32

La pantalla `http://localhost:8080/hardware/connect` muestra el estado del ESP32, rele, sensores, bomba, fuente, microtubo y deposito. El ESP32 debe enviar un `POST` JSON a:

```text
http://localhost:8080/hardware/api?action=telemetry
```

Cada dispositivo usa su `codigo` y `api_key`, visibles en la pantalla de hardware. Al recibir lecturas recientes, la pagina marca los componentes como conectados y procesa humedad de suelo, DHT22, BH-1750, PH-4502C, nivel de tanque, fuente 5V y estado de riego.

## Base de datos

La conexion queda apuntando a:

```text
host: localhost
usuario: root
password: vacio
database: smartplant_care
driver: MySQLi
```

Si usas otros datos, cambialos en `.env` o en `app/Config/Database.php`.

Para crear la base local con datos de prueba, importa:

```powershell
& C:\xampp\mysql\bin\mysql.exe -u root --execute="SOURCE C:/xampp/htdocs/SmartPlant_Care-main_CodeIgniter/database/smartplant_care_schema_seed.sql"
```

El script recrea las tablas de `smartplant_care` y carga estas cuentas:

```text
admin@smartplant.test / 123456
demo@smartplant.test  / 123456
```

Si ya tenes datos cargados y no queres recrear las tablas, podes importar la actualizacion no destructiva:

```powershell
& C:\xampp\mysql\bin\mysql.exe -u root --execute="SOURCE C:/xampp/htdocs/SmartPlant_Care-main_CodeIgniter/database/smartplant_care_hito_compra_update.sql"
```

## Pagos

La tienda usa PayPal JS SDK y Mercado Pago Checkout Pro.

En `.env` configura:

```text
PAYPAL_CLIENT_ID = 'tu_client_id_publico'
PAYPAL_CURRENCY = 'USD'
PAYPAL_ARS_TO_USD_RATE = 1000
MERCADOPAGO_ACCESS_TOKEN = 'tu_access_token_privado'
MERCADOPAGO_USE_SANDBOX_INIT_POINT = true
```

Para probar Mercado Pago usa credenciales de prueba y usuarios de prueba. Para produccion, cambia el access token y pone `MERCADOPAGO_USE_SANDBOX_INIT_POINT = false`.

## Nota

Las rutas antiguas siguen funcionando por compatibilidad, pero el codigo ya vive en las carpetas normales de CodeIgniter.
