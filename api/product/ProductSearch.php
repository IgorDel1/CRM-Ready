<?php

function ProductSearch($params, $DB) {
    $search = isset($params['search']) ? $params['search'] : '';
    $search_name = isset($params['search_name']) ? $params['search_name'] : '';
    $sort = isset($params['sort']) ? $params['sort'] : '';
    
    // Получаем номер текущей страницы, по умолчанию 1
    $currentPage = isset($params['page']) ? (int)$params['page'] : 1;
    $itemsPerPage = 5; // Количество товаров на странице
    $offset = ($currentPage - 1) * $itemsPerPage;

    $sql = "SELECT * FROM products";
    $params_array = array();

    // Добавляем условие WHERE только если есть поисковый запрос
    if (!empty($search)) {
        $sql .= " WHERE ";
        if ($search_name === 'name') {
            $sql .= "name LIKE :search";
        } else if ($search_name === 'price') {
            $sql .= "price LIKE :search";
        } else if ($search_name === 'stock') {
            $sql .= "stock LIKE :search";
        }
        $params_array[':search'] = "%$search%";
    }

    if ($sort === '1') {
        if ($search_name === 'name') {
            $sql .= " ORDER BY name ASC";
        } else if ($search_name === 'price') {
            $sql .= " ORDER BY price ASC";
        } else if ($search_name === 'stock') {
            $sql .= " ORDER BY stock ASC";
        }
    } else if ($sort === '2') {
        if ($search_name === 'name') {
            $sql .= " ORDER BY name DESC";
        } else if ($search_name === 'price') {
            $sql .= " ORDER BY price DESC";
        } else if ($search_name === 'stock') {
            $sql .= " ORDER BY stock DESC";
        }
    }

    // Добавляем LIMIT и OFFSET для пагинации
    $sql .= " LIMIT :limit OFFSET :offset";
    $params_array[':limit'] = $itemsPerPage;
    $params_array[':offset'] = $offset;

    $stmt = $DB->prepare($sql);

    // Привязываем все параметры
    foreach($params_array as $key => &$val) {
        if($key === ':limit' || $key === ':offset') {
            $stmt->bindValue($key, $val, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $val);
        }
    }

    $stmt->execute();
    return $stmt->fetchAll();
}

?>
