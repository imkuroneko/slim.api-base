# ⚡ Inicio Rápido - 5 Minutos

## 1️⃣ Instalar

```bash
composer install
cp .env.example .env
```

## 2️⃣ Base de Datos (MySQL)

```bash
# Crear BD
mysql -u root -p -e "CREATE DATABASE api_db;"

# Importar esquema
mysql -u root -p api_db < database/schema.sql

# Editar .env
DB_USER=root
DB_PASSWORD=tu_contraseña
DB_NAME=api_db
```

O **PostgreSQL**:
```bash
psql -U postgres -c "CREATE DATABASE api_db;"
psql -U postgres -d api_db -f database/schema.sql

# Editar .env
DB_DRIVER=postgresql
DB_USER=postgres
DB_PASSWORD=tu_contraseña
```

## 3️⃣ JWT Secret

```bash
php -r 'echo bin2hex(random_bytes(32));'
```

Copiar en `.env`: `JWT_SECRET=resultado_del_comando`

## 4️⃣ Iniciar

```bash
composer start
# ✅ http://localhost:8000
```

## 5️⃣ Test

**Registrar:**
```bash
curl -X POST http://localhost:8000/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Juan","email":"juan@example.com","password":"password123"}'
```

**Con token:**
```bash
curl -X GET http://localhost:8000/users \
  -H "Authorization: Bearer TOKEN_AQUI"
```

## 📮 Postman

Importar: `postman/Slim-API-Base.postman_collection.json`

## 📚 Más Info

- [README.md](../README.md) - Características
- [estructura_proyecto.md](estructura_proyecto.md) - Estructura
- [crear_endpoints.md](crear_endpoints.md) - Crear endpoints
