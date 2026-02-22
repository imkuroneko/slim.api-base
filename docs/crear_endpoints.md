# 🚀 Crear Nuevos Endpoints

En este documento veremos paso a paso cómo crear un CRUD completo para `/productos`.

## 📋 Plan

Vamos a crear:
- Modelo `Product` (interactúa con BD)
- Controlador `ProductController` (lógica)
- Rutas en `public/index.php`
- Tabla `products` en base de datos

---

## Paso 1: Crear Tabla en BD

Agregar a `database/schema.sql`:

```sql
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stock INT DEFAULT 0,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_price (price)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Luego ejecutar:
```bash
mysql -u root -p api_db < database/schema.sql
```

---

## Paso 2: Crear Modelo `Product`

Crear archivo: `src/Models/Product.php`

```php
<?php

namespace App\Models;

use PDO;

class Product
{
    private PDO $db;
    private string $table = 'products';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtener todos los productos del usuario
     */
    public function getAll(int $userId): array
    {
        try {
            $query = "SELECT id, name, description, price, stock, created_at 
                     FROM {$this->table} 
                     WHERE user_id = ? 
                     ORDER BY created_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);
            return $stmt->fetchAll() ?: [];
        } catch (\PDOException $e) {
            throw new \Exception('Error en getAll: ' . $e->getMessage());
        }
    }

    /**
     * Obtener producto por ID
     */
    public function getById(int $id, int $userId): ?array
    {
        try {
            $query = "SELECT * FROM {$this->table} 
                     WHERE id = ? AND user_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id, $userId]);
            return $stmt->fetch() ?: null;
        } catch (\PDOException $e) {
            throw new \Exception('Error en getById: ' . $e->getMessage());
        }
    }

    /**
     * Crear producto
     */
    public function create(int $userId, array $data): array
    {
        try {
            $query = "INSERT INTO {$this->table} 
                     (name, description, price, stock, user_id, created_at) 
                     VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['name'],
                $data['description'] ?? null,
                $data['price'],
                $data['stock'] ?? 0,
                $userId
            ]);

            $id = $this->db->lastInsertId();
            return $this->getById((int) $id, $userId) ?? [];
        } catch (\PDOException $e) {
            throw new \Exception('Error en create: ' . $e->getMessage());
        }
    }

    /**
     * Actualizar producto
     */
    public function update(int $id, int $userId, array $data): array
    {
        try {
            $fields = [];
            $values = [];

            foreach ($data as $key => $value) {
                $fields[] = "{$key} = ?";
                $values[] = $value;
            }

            $values[] = $id;
            $values[] = $userId;

            $query = "UPDATE {$this->table} 
                     SET " . implode(', ', $fields) . " 
                     WHERE id = ? AND user_id = ?";

            $stmt = $this->db->prepare($query);
            $stmt->execute($values);

            return $this->getById($id, $userId) ?? [];
        } catch (\PDOException $e) {
            throw new \Exception('Error en update: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar producto
     */
    public function delete(int $id, int $userId): bool
    {
        try {
            $query = "DELETE FROM {$this->table} WHERE id = ? AND user_id = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$id, $userId]);
        } catch (\PDOException $e) {
            throw new \Exception('Error en delete: ' . $e->getMessage());
        }
    }
}
```

---

## Paso 3: Crear Controlador `ProductController`

Crear archivo: `src/Controllers/ProductController.php`

```php
<?php

namespace App\Controllers;

use App\Models\Product;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ProductController extends BaseController
{
    private Product $productModel;

    public function __construct(Product $productModel)
    {
        $this->productModel = $productModel;
    }

    /**
     * GET /productos - Obtener todos los productos del usuario
     */
    public function getAll(Request $request, Response $response): Response
    {
        try {
            $user = $this->getAuthUser($request);
            if (!$user) {
                return $this->errorResponse($response, 'Usuario no autenticado', 401);
            }

            $products = $this->productModel->getAll($user->userId);
            return $this->successResponse($response, $products, 200);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $response,
                'Error al obtener productos: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * GET /productos/{id} - Obtener producto por ID
     */
    public function getById(Request $request, Response $response, array $args): Response
    {
        try {
            $user = $this->getAuthUser($request);
            if (!$user) {
                return $this->errorResponse($response, 'Usuario no autenticado', 401);
            }

            $productId = $args['id'] ?? null;
            if (!$productId || !is_numeric($productId)) {
                return $this->errorResponse($response, 'ID inválido', 400);
            }

            $product = $this->productModel->getById((int) $productId, $user->userId);
            if (!$product) {
                return $this->errorResponse($response, 'Producto no encontrado', 404);
            }

            return $this->successResponse($response, $product, 200);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $response,
                'Error al obtener producto: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * POST /productos - Crear nuevo producto
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            $user = $this->getAuthUser($request);
            if (!$user) {
                return $this->errorResponse($response, 'Usuario no autenticado', 401);
            }

            $data = $this->getBodyData($request);

            // Validar campos requeridos
            $errors = $this->validateRequired($data, ['name', 'price']);
            if (!empty($errors)) {
                return $this->errorResponse($response, 'Datos inválidos', 422, $errors);
            }

            // Validar que price sea numérico
            if (!is_numeric($data['price']) || $data['price'] <= 0) {
                return $this->errorResponse(
                    $response,
                    'Precio inválido',
                    422,
                    ['price' => 'El precio debe ser un número mayor a 0']
                );
            }

            $product = $this->productModel->create($user->userId, [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'stock' => $data['stock'] ?? 0,
            ]);

            return $this->successResponse($response, $product, 201);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $response,
                'Error al crear producto: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * PUT /productos/{id} - Actualizar producto completo
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $user = $this->getAuthUser($request);
            if (!$user) {
                return $this->errorResponse($response, 'Usuario no autenticado', 401);
            }

            $productId = $args['id'] ?? null;
            if (!$productId || !is_numeric($productId)) {
                return $this->errorResponse($response, 'ID inválido', 400);
            }

            $product = $this->productModel->getById((int) $productId, $user->userId);
            if (!$product) {
                return $this->errorResponse($response, 'Producto no encontrado', 404);
            }

            $data = $this->getBodyData($request);
            $errors = $this->validateRequired($data, ['name', 'price']);
            if (!empty($errors)) {
                return $this->errorResponse($response, 'Datos inválidos', 422, $errors);
            }

            if (!is_numeric($data['price']) || $data['price'] <= 0) {
                return $this->errorResponse(
                    $response,
                    'Precio inválido',
                    422,
                    ['price' => 'El precio debe ser un número mayor a 0']
                );
            }

            $updatedProduct = $this->productModel->update((int) $productId, $user->userId, [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'stock' => $data['stock'] ?? 0,
            ]);

            return $this->successResponse($response, $updatedProduct, 200);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $response,
                'Error al actualizar producto: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * PATCH /productos/{id} - Actualizar parcialmente
     */
    public function patch(Request $request, Response $response, array $args): Response
    {
        try {
            $user = $this->getAuthUser($request);
            if (!$user) {
                return $this->errorResponse($response, 'Usuario no autenticado', 401);
            }

            $productId = $args['id'] ?? null;
            if (!$productId || !is_numeric($productId)) {
                return $this->errorResponse($response, 'ID inválido', 400);
            }

            $product = $this->productModel->getById((int) $productId, $user->userId);
            if (!$product) {
                return $this->errorResponse($response, 'Producto no encontrado', 404);
            }

            $data = $this->getBodyData($request);
            $updateData = [];

            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }
            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }
            if (isset($data['price'])) {
                if (!is_numeric($data['price']) || $data['price'] <= 0) {
                    return $this->errorResponse(
                        $response,
                        'Precio inválido',
                        422,
                        ['price' => 'El precio debe ser un número mayor a 0']
                    );
                }
                $updateData['price'] = $data['price'];
            }
            if (isset($data['stock'])) {
                $updateData['stock'] = $data['stock'];
            }

            if (empty($updateData)) {
                return $this->errorResponse($response, 'No hay datos para actualizar', 400);
            }

            $updatedProduct = $this->productModel->update((int) $productId, $user->userId, $updateData);
            return $this->successResponse($response, $updatedProduct, 200);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $response,
                'Error al actualizar producto: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * DELETE /productos/{id} - Eliminar producto
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $user = $this->getAuthUser($request);
            if (!$user) {
                return $this->errorResponse($response, 'Usuario no autenticado', 401);
            }

            $productId = $args['id'] ?? null;
            if (!$productId || !is_numeric($productId)) {
                return $this->errorResponse($response, 'ID inválido', 400);
            }

            $product = $this->productModel->getById((int) $productId, $user->userId);
            if (!$product) {
                return $this->errorResponse($response, 'Producto no encontrado', 404);
            }

            $this->productModel->delete((int) $productId, $user->userId);
            return $this->successResponse($response, null, 204);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $response,
                'Error al eliminar producto: ' . $e->getMessage(),
                500
            );
        }
    }
}
```

---

## Paso 4: Registrar Dependencias en `public/index.php`

Agregar en el contenedor (Container setup section):

```php
// Registrar modelos
$container->set(Product::class, function ($c) {
    return new Product($c->get('db'));
});

// Registrar controladores
$container->set(ProductController::class, function ($c) {
    return new ProductController($c->get(Product::class));
});
```

También necesitamos importar la clase al inicio:

```php
use App\Controllers\{UserController, AuthController, ProductController};
use App\Models\{User, Product};
```

---

## Paso 5: Registrar Rutas en `public/index.php`

Agregar en la sección RUTAS:

```php
// Rutas de productos (requieren autenticación)
$app->get('/productos', [ProductController::class, 'getAll']);
$app->get('/productos/{id}', [ProductController::class, 'getById']);
$app->post('/productos', [ProductController::class, 'create']);
$app->put('/productos/{id}', [ProductController::class, 'update']);
$app->patch('/productos/{id}', [ProductController::class, 'patch']);
$app->delete('/productos/{id}', [ProductController::class, 'delete']);
```

---

## Paso 6: Pruebar la API

### Con cURL

**Crear producto:**
```bash
TOKEN="tu_jwt_token"

curl -X POST http://localhost:8000/productos \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Laptop",
    "description": "Gaming laptop",
    "price": 1299.99,
    "stock": 5
  }'
```

**Obtener todos:**
```bash
curl -X GET http://localhost:8000/productos \
  -H "Authorization: Bearer $TOKEN"
```

**Obtener uno:**
```bash
curl -X GET http://localhost:8000/productos/1 \
  -H "Authorization: Bearer $TOKEN"
```

**Actualizar:**
```bash
curl -X PUT http://localhost:8000/productos/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Laptop Pro",
    "description": "Updated description",
    "price": 1499.99,
    "stock": 3
  }'
```

**Actualizar parcial:**
```bash
curl -X PATCH http://localhost:8000/productos/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "price": 1399.99
  }'
```

**Eliminar:**
```bash
curl -X DELETE http://localhost:8000/productos/1 \
  -H "Authorization: Bearer $TOKEN"
```

### Con Postman

1. Importar colección
2. Crear carpeta "Productos"
3. Crear requests para cada endpoint
4. Usar variable `{{token}}` en Authorization

---

## 📋 Checklist para Crear Nuevos Endpoints

- [ ] Crear tabla en `database/schema.sql`
- [ ] Crear Modelo en `src/Models/`
- [ ] Crear Controlador en `src/Controllers/` (extender `BaseController`)
- [ ] Registrar Modelo en container en `public/index.php`
- [ ] Registrar Controlador en container en `public/index.php`
- [ ] Agregar rutas en `public/index.php`
- [ ] Probar con cURL o Postman
- [ ] (Opcional) Agregar a colección Postman

---

## 💡 Buenas Prácticas Aplicadas

✅ **Separación de responsabilidades** - Modelo, Controlador, Rutas en archivos separados
✅ **Try/Catch** - Manejo de excepciones en cada método
✅ **Prepared Statements** - Protección contra SQL injection
✅ **Validación** - Datos requeridos, tipos correctos
✅ **Autenticación** - Solo usuarios autenticados pueden ver/editar sus productos
✅ **Autorización** - Un usuario solo ve sus propios productos
✅ **Respuestas JSON** - Formato consistente
✅ **HTTP Status Codes** - 200, 201, 400, 404, 500, etc.

---

## 🎯 Pasos Resumidos

```
1. BD:          Tabla + schema.sql
2. Modelo:      src/Models/Product.php
3. Controlador: src/Controllers/ProductController.php
4. Container:   Registrar en public/index.php
5. Rutas:       Agregar rutas en public/index.php
6. Test:        cURL o Postman
```
