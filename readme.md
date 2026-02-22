# 🚀 API Base - Slim Framework

Proyecto profesional de API REST con todas las buenas prácticas de seguridad y arquitectura.

> **Base armada con Claude Haiku 4.5**; se irán ajustando detalles según necesidad

## ✨ Características

- **Slim Framework 4** - Micro framework PHP moderno
- **JWT** - Autenticación con tokens seguros
- **CORS** - Control de orígenes configurable
- **Rate Limiting** - Protección contra abuso
- **ODBC** - Soporte MySQL y PostgreSQL
- **Sanitización** - Validación y escape de datos
- **Try/Catch** - Manejo robusto de errores
- **REST API** - GET, POST, PUT, PATCH, DELETE

## ⚡ Quick Start

Para iniciar en 5 minutos, ver **[inicio_rapido.md](inicio_rapido.md)**

```bash
composer install
cp .env.example .env
composer start  # http://localhost:8000
```

## 📚 Documentación

| Documento | Contenido |
|-----------|----------|
| **[docs/inicio_rapido.md](docs/inicio_rapido.md)** | Setup en 5 minutos |
| **[docs/estructura_proyecto.md](docs/estructura_proyecto.md)** | Estructura y arquitectura del proyecto |
| **[docs/crear_endpoints.md](docs/crear_endpoints.md)** | Cómo crear nuevos endpoints (ejemplo: /productos) |

### Endpoints Disponibles

```
POST   /auth/register       # Registrarse
POST   /auth/login          # Login → token JWT
GET    /health              # Health check

GET    /users               # Listar (requiere JWT)
GET    /users/{id}          # Obtener uno
POST   /users               # Crear
PUT    /users/{id}          # Actualizar
PATCH  /users/{id}          # Actualizar parcial
DELETE /users/{id}          # Eliminar
```

## 🔐 Seguridad

✅ **JWT** - Tokens con expiración configurable
✅ **CORS** - Orígenes configurables
✅ **Rate Limiting** - 100 req/hora por IP
✅ **Sanitización** - Escape HTML, validación de datos
✅ **Try/Catch** - Manejo robusto de errores
✅ **SQL Injection** - Prepared statements en todas las queries
✅ **Password Hashing** - Bcrypt con cost 12

## � Estructura

Ver detalles completos en **[estructura_proyecto.md](docs/estructura_proyecto.md)**

```
slim.core/
├── public/index.php          # Punto de entrada
├── src/
│   ├── Controllers/          # AuthController, UserController
│   ├── Models/               # User
│   ├── Middleware/           # JWT, CORS, RateLimit, Sanitización
│   ├── Database/             # Connection (ODBC)
│   └── Utils/                # Validator, helpers
├── config/                   # config.php, database.php
├── database/schema.sql       # Esquema SQL
├── .env                      # Variables locales (no commitear)
└── .env.example              # Plantilla
```

## 🔄 Autenticación

1. `POST /auth/register` o `POST /auth/login` → recibe **JWT token**
2. Envía token en header: `Authorization: Bearer <token>`
3. Middleware valida antes de acceder a rutas protegidas
4. Datos del usuario disponibles en controller vía `$request->getAttribute('user')`

## ⚙️ Configuración

Ver variables en `.env.example`. Las principales:

```env
DB_DRIVER=mysql              # mysql o postgresql
DB_HOST=localhost
DB_PASSWORD=tu_contraseña

JWT_SECRET=clave-secreta
CORS_ORIGIN=http://localhost:3000
RATE_LIMIT_REQUESTS=100
```

## 🧪 Testing

- **Postman**: Importar `postman/Slim-API-Base.postman_collection.json`
- **cURL**: Ver ejemplos en [inicio_rapido.md](docs/inicio_rapido.md)
- **Script PHP**: Usar `GuzzleHttp` o `curl`

## 🚀 Deployment

```bash
APP_ENV=production
APP_DEBUG=false
JWT_SECRET=clave-fuerte-aleatorio
DEV_PASSWORD=contraseña-fuerte
CORS_ORIGIN=https://app.example.com
```

Ver detalles en [inicio_rapido.md](docs/inicio_rapido.md)

## � Recursos

- [Slim Framework Docs](https://www.slimframework.com/)
- [JWT.io](https://jwt.io/)
- [OWASP Security](https://owasp.org/)

