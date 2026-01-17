<?php
session_start();
header('Content-Type: application/json');

require_once '../../includes/functions.php';

authenticateUser();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $month = isset($_GET['month']) ? (int)$_GET['month'] : null;
        $year = isset($_GET['year']) ? (int)$_GET['year'] : null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        
        $query = "SELECT e.*, u.name as user_name 
                  FROM expenses e
                  LEFT JOIN users u ON e.user_id = u.id
                  WHERE 1=1";
        
        $params = [];
        
        if ($month && $year) {
            $startDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
            $endDate = date('Y-m-t', strtotime($startDate));
            
            $query .= " AND e.expense_date BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $startDate;
            $params[':end_date'] = $endDate;
        }
        
        $query .= " ORDER BY e.expense_date DESC, e.created_at DESC LIMIT :limit";
        
        $stmt = $db->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        
        $stmt->execute();
        $expenses = $stmt->fetchAll();
        
        // Formatear campos
        foreach ($expenses as &$expense) {
            $expense['amount_formatted'] = formatCurrency($expense['amount']);
            $expense['expense_date_formatted'] = date('d/m/Y', strtotime($expense['expense_date']));
            
            // Categorías con colores
            $categoryColors = [
                'general' => 'badge-success',
                'shipping' => 'badge-info',
                'warranty' => 'badge-warning',
                'supplies' => 'badge-primary',
                'transport' => 'badge-secondary',
                'other' => 'badge-dark'
            ];
            
            $expense['category_class'] = $categoryColors[$expense['category']] ?? 'badge-secondary';
        }
        
        sendResponse('success', $expenses);
        
    } catch (PDOException $e) {
        error_log("Error al obtener gastos: " . $e->getMessage());
        sendResponse('error', [], 'Error al obtener gastos');
    }
} else {
    sendResponse('error', [], 'Método no permitido');
}
?>