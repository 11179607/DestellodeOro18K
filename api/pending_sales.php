<?php
// api/pending_sales.php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';
require_once 'csrf.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}
$actorUser   = $_SESSION['username'] ?? 'admin';
$actorUserId = $_SESSION['user_id'] ?? null;

$method = $_SERVER['REQUEST_METHOD'];

ensureCsrfToken(); // preparar token/cookie

if ($method === 'GET') {
    // Listar pendientes - MODIFICADO: Mostrar todas las ventas con métodos de pago diferentes a efectivo
    // sin importar su estado (pending, completed, cancelled) para mantener el historial completo
    try {
        $month = $_GET['month'] ?? null;
        $year  = $_GET['year'] ?? null;

        $sql    = "SELECT * FROM sales WHERE payment_method != 'cash'";
        $params = [];

        if ($month !== null && $year !== null) {
            $month = intval($month); // JS 0-11 o -1 para todos
            if ($month === -1) {
                $sql  .= " AND YEAR(sale_date) = :year";
                $params[':year']  = $year;
            } else {
                $month = $month + 1; // 0-11 -> 1-12
                $sql  .= " AND MONTH(sale_date) = :month AND YEAR(sale_date) = :year";
                $params[':month'] = $month;
                $params[':year']  = $year;
            }
        }

        $sql .= " ORDER BY sale_date DESC";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $pending = $stmt->fetchAll();
        
        // Para cada venta, obtener breve info de productos para mostrar en tabla
        foreach ($pending as &$sale) {
            $itemStmt = $conn->prepare("SELECT product_name, quantity, sale_type, unit_price, subtotal FROM sale_items WHERE sale_id = :id");
            $itemStmt->execute([':id' => $sale['id']]);
            $items = $itemStmt->fetchAll();
            $sale['products'] = array_map(function($it) {
                return [
                    'productName' => $it['product_name'],
                    'quantity'    => (int)$it['quantity'],
                    'saleType'    => $it['sale_type'] ?? 'retail',
                    'unitPrice'   => (float)($it['unit_price'] ?? 0),
                    'subtotal'    => (float)($it['subtotal'] ?? 0),
                ];
            }, $items); 
            // Formato JS esperado: array de {productName: '...', ...}
            
            // Map database fields to JS expected fields
            $sale['date'] = $sale['sale_date'];
            $sale['customerInfo'] = [
                'name' => $sale['customer_name'],
                'id' => $sale['customer_id'],
                'phone' => $sale['customer_phone'],
                'email' => $sale['customer_email'],
                'address' => $sale['customer_address'],
                'city' => $sale['customer_city']
            ];
            $sale['paymentMethod'] = $sale['payment_method'];
            $sale['deliveryType'] = $sale['delivery_type'];
            $sale['deliveryCost'] = (float)($sale['delivery_cost'] ?? 0);
            $sale['warrantyIncrement'] = (float)($sale['warranty_increment'] ?? 0);
            $sale['user'] = $sale['username'];

            // Determinar tipo de venta
            $types = array_map(function($it) { return $it['saleType']; }, $sale['products']);
            $uniqueTypes = array_unique($types);
            $computedType = 'retail';
            if (count($uniqueTypes) > 1) {
                $computedType = 'mixed';
            } elseif (!empty($uniqueTypes)) {
                $computedType = reset($uniqueTypes);
            }
            $dbType = strtolower($sale['sale_type'] ?? '');
            $sale['saleType'] = in_array($dbType, ['retail','wholesale','other','mixed']) ? $dbType : $computedType;
        }
        
        echo json_encode($pending);
        exit;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }


} elseif ($method === 'POST') {
    requireCsrf();
    // Confirmar pago (POST con action='confirm') O registrar pendiente (si es normal)
    // El JS actual llama a `processSale` que guarda en localStorage.
    // Aquí implementaremos:
    // 1. Crear pendiente (similar a POST sales.php pero status='pending')
    // 2. Confirmar pendiente (UPDATE status='completed')
    // 3. Cancelar pendiente (DELETE o UPDATE status='cancelled')
    
    $data = json_decode(file_get_contents("php://input"));
    $action = $data->action ?? 'create';
    
    if ($action === 'create') {
        // Lógica CREATE PENDING (Similar a sales.php create, pero NO descuenta inventario aun? 
        // Normalmente se aparta la mercancía. Vamos a descontar inventario DE UNA VEZ para apartarlo.
        // Si se cancela, se devuelve.)
        
        try {
            $conn->beginTransaction();
            
             // 1. Crear cabecera
            $sql = "INSERT INTO sales (invoice_number, customer_name, customer_id, customer_phone, customer_email, customer_address, customer_city, total, discount, delivery_cost, warranty_increment, payment_method, delivery_type, sale_type, sale_date, user_id, username, status) 
            VALUES (:inv, :name, :cid, :phone, :email, :addr, :city, :total, :disc, :del, :war, :pay, :del_type, :sale_type, :sale_date, :uid, :uname, 'pending')";
    
            // Determinar tipo cabecera
            $itemTypes = [];
            foreach ($data->products as $it) {
                $raw = strtolower($it->saleType ?? 'retail');
                $itemTypes[] = in_array($raw, ['retail','wholesale','other']) ? $raw : 'retail';
            }
            $uniqueTypes = array_unique($itemTypes);
            $headerType = 'retail';
            if (count($uniqueTypes) > 1) {
                $headerType = 'mixed';
            } elseif (!empty($uniqueTypes)) {
                $headerType = reset($uniqueTypes);
            }

            $stmt = $conn->prepare($sql);
            $incomingDate = $data->date ?? null;
            $saleDate = $incomingDate ? ((strlen($incomingDate) === 10) ? ($incomingDate . ' ' . date('H:i:s')) : $incomingDate) : date('Y-m-d H:i:s');

            $stmt->execute([
                ':inv' => $data->id,
                ':name' => $data->customerInfo->name,
                ':cid' => $data->customerInfo->id,
                ':phone' => $data->customerInfo->phone,
                ':email' => $data->customerInfo->email ?? '',
                ':addr' => $data->customerInfo->address,
                ':city' => $data->customerInfo->city,
                ':total' => $data->total,
                ':disc' => $data->discount ?? 0,
                ':del' => $data->deliveryCost ?? 0,
                ':war' => $data->warrantyIncrement ?? 0,
                ':pay' => $data->paymentMethod,
                ':del_type' => $data->deliveryType,
                ':sale_type' => $headerType,
                ':sale_date' => $saleDate,
                ':uid' => $actorUserId,
                ':uname' => $actorUser
            ]);
            
            $saleId = $conn->lastInsertId();
            
            // 2. Items y Apartado de Inventario
            $itemSql = "INSERT INTO sale_items (sale_id, product_ref, product_name, quantity, unit_price, subtotal, sale_type) VALUES (:sid, :ref, :pname, :qty, :price, :sub, :type)";
            $stockSql = "UPDATE products SET quantity = quantity - :qty WHERE reference = :ref";
            
            $itemStmt = $conn->prepare($itemSql);
            $stockStmt = $conn->prepare($stockSql);
            
            foreach ($data->products as $item) {
                $qty = (int)($item->count ?? $item->quantity ?? 0);
                $unitPrice = isset($item->price) ? $item->price : (isset($item->unitPrice) ? $item->unitPrice : (isset($item->unit_price) ? $item->unit_price : 0));
                $unitPrice = (float)$unitPrice;
                $lineSubtotal = isset($item->total) ? (float)$item->total : ($unitPrice * $qty);
                
                $itemStmt->execute([
                    ':sid' => $saleId,
                    ':ref' => $item->id,
                    ':pname' => $item->name ?? $item->productName,
                    ':qty' => $qty,
                    ':price' => $unitPrice,
                    ':sub' => $lineSubtotal,
                    ':type' => $item->saleType ?? 'retail'
                ]);
                $stockStmt->execute([
                    ':qty' => $qty,
                    ':ref' => $item->id
                ]);
            }
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Venta pendiente registrada']);

        } catch (PDOException $e) {
            $conn->rollBack();
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        
    } elseif ($action === 'confirm') {
        // Confirmar pago (Solo admin)
        if ($_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso denegado']);
            exit;
        }
        
        $saleId = $data->sale_id; // Debe enviarse el ID interno (INT) o la invoice (VARCHAR). Usemos invoice desde JS (o mapear). 
        // JS usa IDs tipo "1001Pending". En DB el invoice almacena eso.
        
        try {
            $stmt = $conn->prepare("UPDATE sales SET status = 'completed' WHERE invoice_number = :inv OR id = :id");
            $stmt->execute([':inv' => $saleId, ':id' => $saleId]);
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Venta no encontrada o ya confirmada']);
                exit;
            }
            echo json_encode(['success' => true, 'message' => 'Venta confirmada']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }

    } elseif ($action === 'cancel') {
        // Cancelar y devolver inventario (Solo admin)
        if ($_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso denegado']);
            exit;
        }
        
        $saleId = $data->sale_id; // Invoice number probablemente
        
        try {
            $conn->beginTransaction();
            
            // Obtener venta
            $stmt = $conn->prepare("SELECT id FROM sales WHERE invoice_number = :inv OR id = :id");
            $stmt->execute([':inv' => $saleId, ':id' => $saleId]);
            $sale = $stmt->fetch();
            
            if ($sale) {
                $dbId = $sale['id'];
                
                // Devolver inventario
                $itemsStmt = $conn->prepare("SELECT product_ref, quantity FROM sale_items WHERE sale_id = :sid");
                $itemsStmt->execute([':sid' => $dbId]);
                $items = $itemsStmt->fetchAll();
                
                $stockStmt = $conn->prepare("UPDATE products SET quantity = quantity + :qty WHERE reference = :ref");
                
                foreach ($items as $item) {
                    $stockStmt->execute([':qty' => $item['quantity'], ':ref' => $item['product_ref']]);
                }
                
                // Marcar como cancelada (o borrar)
                $updateStmt = $conn->prepare("UPDATE sales SET status = 'cancelled' WHERE id = :id");
                $updateStmt->execute([':id' => $dbId]);
                
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Venta cancelada e inventario restaurado']);
            } else {
                throw new Exception("Venta no encontrada");
            }
            
        } catch (Exception $e) {
            $conn->rollBack();
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
?>
