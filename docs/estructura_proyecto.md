# 📂 Estructura del Proyecto

Esta es la documentación completa de la arquitectura y estructura de directorios.

```
slim.core/
│
├── public/
│   └── index.php                    # 🎯 Punto de entrada de la API
│
├── src/
│   ├── Controllers/
│   │   ├── BaseController.php       # 📋 Controlador base con respuestas JSON
│   │   ├── AuthController.php       # 🔐 Login, Register, JWT generación
│   │   └── UserController.php       # 👤 CRUD de usuarios (GET/POST/PUT/PATCH/DELETE)
│   │
│   ├── Models/
│   │   └── User.php                 # 💾 Modelo de usuario con queries SQL
│   │
│   ├── Middleware/
│   │   ├── JWTMiddleware.php        # 🔑 Validación de tokens JWT
│   │   ├── CORSMiddleware.php       # 🌐 Control de CORS
│   │   ├── RateLimitMiddleware.php  # ⏱️ Limitador de solicitudes
│   │   └── InputSanitizationMiddleware.php  # 🛡️ Sanitización de inputs
│   │
│   ├── Database/
│   │   └── Connection.php           # 🔌 Conexión ODBC MySQL/PostgreSQL
│   │
│   ├── Utils/
│   │   └── Validator.php            # ✅ Validación de datos
│   │
│   └── helpers.php                  # 🔧 Funciones auxiliares
│
├── config/
│   ├── config.php                   # ⚙️ Configuración general
│   └── database.php                 # 🗄️ Configuración de BD
│
├── database/
│   └── schema.sql                   # 📊 Esquema SQL inicial
│
├── postman/
│   └── Slim-API-Base.postman_collection.json  # 📮 Colección de tests
│
├── composer.json                    # 📦 Dependencias del proyecto
├── .env                             # 🔒 Variables de entorno (local)
├── .env.example                     # 📋 Plantilla de variables
├── .gitignore                       # ❌ Archivos ignorados en Git
├── .htaccess                        # 🔄 Rewrite para Apache
├── docker-compose.yml               # 🐳 Configuración Docker
├── nginx.conf                       # 🌐 Configuración Nginx
├── README.md                        # 📚 Documentación
├── docs/
│   ├── inicio_rapido.md            # ⚡ Guía de inicio rápido
│   ├── estructura_proyecto.md      # 📂 Este archivo
│   └── crear_endpoints.md          # 🚀 Cómo crear endpoints
└── postman/                         # 📮 Colecciones Postman
```

## 📋 Descripción de Archivos Principales

### Configuración
- **composer.json**: Define dependencias (Slim, JWT, Dotenv, etc.)
- **.env**: Variables de entorno (credenciales, secretos, etc.)
- **config/**: Archivos de configuración centralizados

### Punto de Entrada
- **public/index.php**: Instancia Slim, registra middlewares, define rutas

### Lógica de Negocio
- **Controllers/**: Manejan requests y responden
- **Models/**: Interactúan con la base de datos
- **Middleware/**: Interceptan y procesan requests

### Seguridad
- **JWTMiddleware.php**: Valida tokens antes de acceder a rutas protegidas
- **CORSMiddleware.php**: Controla acceso desde diferentes orígenes
- **RateLimitMiddleware.php**: Limita solicitudes por IP
- **InputSanitizationMiddleware.php**: Limpia datos de entrada

### Base de Datos
- **Database/Connection.php**: PDO con soporte MySQL y PostgreSQL
- **database/schema.sql**: DDL para crear tablas

## 🔐 Flujo de Seguridad

```
Request → CORS → RateLimit → InputSanitization → JWT → Controller → Response
```

### 1. CORS
- Valida origen permitido
- Agrega headers de CORS

### 2. Rate Limiting  
- Verifica solicitudes por IP
- Retorna 429 si se excede límite

### 3. Input Sanitization
- Escapa caracteres HTML
- Remueve null bytes
- Limpia caracteres de control

### 4. JWT Validation
- Lee token del header Authorization
- Valida firma y expiración
- Agrega datos del usuario al request

### 5. Controller
- Valida datos específicos del endpoint
- Ejecuta lógica de negocio en try/catch
- Retorna respuesta JSON apropiada

## 🚀 Buenas Prácticas Implementadas

✅ **JWT**: Autenticación stateless
✅ **CORS**: Control de origen configurable
✅ **ODBC**: Soporte MySQL/PostgreSQL con PDO
✅ **Try/Catch**: Exception handling en todos los controllers
✅ **Sanitización**: Escapado de HTML y validación
✅ **Rate Limiting**: Protección contra ataques
✅ **REST**: Métodos HTTP semánticos
✅ **Validación**: Campos requeridos y tipado
✅ **Logging**: Sistema de logs (helpers)
✅ **Modular**: Separación de responsabilidades

## 📦 Dependencias Principales

```json
{
  "slim/slim": "^4.0",           // Framework
  "slim/psr7": "^1.6",           // Implementación PSR-7
  "firebase/php-jwt": "^6.0",    // JWT
  "symfony/dotenv": "^6.0",      // Variables de entorno
  "php-di/php-di": "^7.0"        // Inyección de dependencias
}
```

## 🌐 Endpoints Disponibles

```
POST   /auth/register              # Registrar usuario
POST   /auth/login                 # Iniciar sesión → recibe JWT
GET    /health                     # Health check

GET    /users                      # Obtener todos (requiere JWT)
GET    /users/{id}                 # Obtener por ID
POST   /users                      # Crear nuevo
PUT    /users/{id}                 # Actualizar completo
PATCH  /users/{id}                 # Actualizar parcial
DELETE /users/{id}                 # Eliminar
```

## 🔄 Ciclo de Vida de una Request

```
1. Cliente envía request con header Authorization: Bearer <token>
2. Nginx recibe y redirige a PHP-FPM
3. public/index.php se ejecuta
4. Middlewares se aplican en orden:
   a. CORS valida origen
   b. RateLimit verifica cuota
   c. InputSanitization limpia datos
   d. JWT valida token
5. Router encuentra controller correspondiente
6. Controller ejecuta try/catch:
   a. Obtiene datos del request
   b. Valida requeridos
   c. Ejecuta modelo/BD
   d. Retorna respuesta JSON
7. Response se envía al cliente
```

## 🧪 Probando la API

### Con curl
```bash
curl -X GET http://localhost:8000/users \
  -H "Authorization: Bearer eyJ0eXAi..."
```

### Con Postman
1. Importar `postman/Slim-API-Base.postman_collection.json`
2. Ejecutar `/auth/login` primero
3. Copiar token en variable `{{token}}`
4. Usar en otros endpoints

### Con PHP
```php
$client = new \GuzzleHttp\Client();
$response = $client->get('http://localhost:8000/users', [
    'headers' => [
        'Authorization' => 'Bearer ' . $token
    ]
]);
```

## 📝 Notas Importantes

- El JWT expira cada hora (configurable en `.env`)
- Rate limiting usa archivos en `/tmp` (cambiar en producción)
- Las contraseñas se hashean con bcrypt (cost 12)
- Emails se validan con FILTER_VALIDATE_EMAIL
- Prepared statements protegen contra SQL injection
