<?php
function convertDate($date) {
    return date('d.m.Y', strtotime($date));
}

// добавить отображение имени администратора

function OutputOrders($orders) {
    foreach ($orders as $key => $order) {
        $status = isset($order['status']) ? $order['status'] : 'Хз';
        $fullname = $order['name'] ?? 'Неизвестно';
        $order_date = $order['order_date'] ? date('Y-m-d H:i:s', strtotime($order['order_date'])) : 'Неизвестно';
        $total_price = $order['total'] ?? '0';
        $order_items = $order['product_names'] ?? 'Нет данных';
        $id = $order['id'];
        $admin_name = $order['admin_name'] ?? 'Не назначен';
        
        // Корректное отображение цены с учетом скидки
        // Если есть скидка и она больше 0
        if (isset($order['discount']) && $order['discount'] > 0) {
            // total_price - это НАЧАЛЬНАЯ цена (до скидки)
            // Рассчитываем цену после скидки
            $discountedPrice = $total_price * (1 - $order['discount'] / 100);
            
            // Форматируем для отображения
            $displayOriginal = number_format($total_price, 2, '.', ' ');
            $displayDiscounted = number_format($discountedPrice, 2, '.', ' ');
            
            // Используем div с классами для большего контроля над отображением
            $priceDisplay = "
                <div class='price-container'>
                    <div class='original-price'>{$displayOriginal}</div>
                    <div class='discounted-price'>{$displayDiscounted}</div>
                </div>";
        } else {
            // Без скидки просто показываем цену
            $priceDisplay = number_format($total_price, 2, '.', ' ');
        }

        echo "<tr>";
        echo "<td>{$order['id']}</td>";
        echo "<td>{$admin_name}</td>";
        echo "<td>{$fullname}</td>";
        echo "<td>{$order_date}</td>";
        echo "<td>{$priceDisplay}</td>";
        echo "<td>{$order_items}</td>";
        echo "<td>{$status}</td>";
        echo "<td> <a href='api/orders/generateCheack.php?id=$id'><i class='fa fa-qrcode'></i></a></td>";
        echo "<td>
                <button class='edit-order-btn' 
                        data-id='{$order['id']}' 
                        data-status='{$order['status']}'>
                    <i class='fa fa-pencil'></i>
                </button>
              </td>";
        echo "<td><a href='api/orders/OrdersDelete.php?id={$order['id']}'><i class='fa fa-trash'></i></a></td>";
        echo "</tr>";
    }
}
?>