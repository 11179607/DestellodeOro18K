<?php
session_start();
header('Content-Type: application/json');

require_once '../../includes/functions.php';

authenticateUser();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
        $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
        
        $startDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        
        // Obtener estadísticas mensuales
        $stats = getMonthlyStats($db, $month, $year);
        
        // Obtener todas las transacciones del mes
        $transactions = [];
        
        // Ventas
        $query = "SELECT s.id, s.sale_date as date, 'venta' as type, 
                         CONCAT('Venta ', s.id, ' - ', COALESCE(c.name, 'Cliente de mostrador')) as description,
                         s.user_id, s.total as amount, u.name as user_name
                  FROM sales s
                  LEFT JOIN customers c ON s.customer_id = c.id
                  LEFT JOIN users u ON s.user_id = u.id
                  WHERE s.sale_date BETWEEN :start_date AND :end_date 
                  AND s.status = 'completed'
                  
                  UNION ALL
                  
                  -- Gastos
                  SELECT e.id, e.expense_date as date, 'gasto' as type, 
                         e.description, e.user_id, e.amount, u.name as user_name
                  FROM expenses e
                  LEFT JOIN users u ON e.user_id = u.id
                  WHERE e.expense_date BETWEEN :start_date AND :end_date
                  
                  UNION ALL
                  
                  -- Surtidos
                  SELECT r.id, r.restock_date as date, 'surtido' as type,
                         CONCAT('Surtido: ', r.product_name, ' (', r.quantity, ' unidades)') as description,
                         r.user_id, r.total_value as amount, u.name as user_name
                  FROM restocks r
                  LEFT JOIN users u ON r.user_id = u.id
                  WHERE r.restock_date BETWEEN :start_date AND :end_date
                  
                  UNION ALL
                  
                  -- Garantías
                  SELECT w.id, w.warranty_date as date, 'garantía' as type,
                         CONCAT('Garantía: ', w.customer_name, ' - ', p.name) as description,
                         w.user_id, (w.shipping_cost + w.price_difference) as amount, u.name as user_name
                  FROM warranties w
                  LEFT JOIN products p ON w.original_product_id = p.id
                  LEFT JOIN users u ON w.user_id = u.id
                  WHERE w.warranty_date BETWEEN :start_date AND :end_date
                  
                  ORDER BY date DESC";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        $transactions = $stmt->fetchAll();
        
        // Formatear transacciones
        foreach ($transactions as &$transaction) {
            $transaction['date_formatted'] = date('d/m/Y H:i', strtotime($transaction['date']));
            $transaction['amount_formatted'] = formatCurrency($transaction['amount']);
            
            // Asignar colores según tipo
            $typeColors = [
                'venta' => 'badge-success',
                'gasto' => 'badge-danger',
                'surtido' => 'badge-warning',
                'garantía' => 'badge-info'
            ];
            $transaction['type_class'] = $typeColors[$transaction['type']] ?? 'badge-secondary';
        }
        
        sendResponse('success', [
            'stats' => $stats,
            'transactions' => $transactions,
            'month' => $month,
            'year' => $year,
            'month_name' => getMonthName($month)
        ]);
        
    } catch (PDOException $e) {
        error_log("Error al obtener historial: " . $e->getMessage());
        sendResponse('error', [], 'Error al obtener historial');
    }
}

function getMonthName($month) {
    $months = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    return $months[$month] ?? 'Desconocido';
}
?>