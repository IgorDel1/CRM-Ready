<?php

function OrdersSearch($params, $DB, $offset = 0, $limit = 5) {
    // Базовая часть запроса
    $query = "SELECT 
                orders.id,
                clients.name,
                orders.order_date,
                CAST(orders.total AS DECIMAL(10,2)) as total,
                orders.status,
                users.name as admin_name,
                GROUP_CONCAT(CONCAT(products.name,' ( ',order_items.quantity,'шт. : ',
                CAST(products.price AS DECIMAL(10,2)),')') SEPARATOR ', ') AS product_names,
                promotions.discount
              FROM orders
              JOIN clients ON orders.client_id = clients.id
              JOIN order_items ON orders.id = order_items.order_id
              JOIN products ON order_items.product_id = products.id
              LEFT JOIN users ON orders.admin = users.id
              LEFT JOIN promotions ON orders.promotion_id = promotions.id";
    
    // Добавление условий поиска
    $whereConditions = [];
    
    // Параметр поиска
    if (isset($params['search']) && !empty($params['search'])) {
        $search = '%' . strtolower($params['search']) . '%';
        $searchField = isset($params['search_name']) ? $params['search_name'] : 'clients.name';
        $whereConditions[] = "LOWER($searchField) LIKE " . $DB->quote($search);
    }
    
    // Параметр статуса из сессии
    if (isset($_SESSION["search_status"]) && $_SESSION["search_status"] != 'all') {
        $whereConditions[] = "orders.status = " . $DB->quote($_SESSION["search_status"]);
    }
    
    // Объединение условий WHERE
    if (!empty($whereConditions)) {
        $query .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    // Группировка только по необходимым полям
    $query .= " GROUP BY orders.id, clients.name, orders.order_date, orders.total, orders.status, users.name, promotions.discount";
    
    // Сортировка
    if (isset($params['sort']) && $params['sort'] != '0') {
        $sortField = isset($params['search_name']) ? $params['search_name'] : 'clients.name';
        $sortDirection = $params['sort'] == '1' ? 'ASC' : 'DESC';
        $query .= " ORDER BY $sortField $sortDirection";
    } else {
        // По умолчанию сортировка по ID
        $query .= " ORDER BY orders.id ASC";
    }
    
    // Добавление пагинации
    $query .= " LIMIT $limit OFFSET $offset";
    
    // Выполнение запроса
    $stmt = $DB->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
