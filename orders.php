<?php session_start();

if (isset($_GET['do']) && $_GET['do'] === 'logout') {
    require_once 'api/auth/LogoutUser.php';
    require_once 'api/DB.php';

    LogoutUser('login.php', $DB, $_SESSION['token']);

    exit;
}

require_once 'api/auth/AuthCheck.php';

AuthCheck('', 'login.php');

require_once 'api/helpers/InputDefaultValue.php';

// Обработка состояния статуса заказов
if (isset($_GET["search_status"])) {
    $_SESSION["search_status"] = $_GET["search_status"];
} else if (!isset($_SESSION["search_status"])) {
    $_SESSION["search_status"] = "all"; // По умолчанию показываем все заказы
}

// Обработка кнопки сброса
if (isset($_GET['reset'])) {
    $_SESSION["search_status"] = "all"; // По умолчанию показываем все заказы
    header("Location: orders.php");
    exit;
}

// Добавляем параметры в URL пагинации
$searchParams = '';
if (isset($_GET['search_name'])) {
    $searchParams .= '&search_name=' . urlencode($_GET['search_name']);
}
if (isset($_GET['search'])) {
    $searchParams .= '&search=' . urlencode($_GET['search']);
}
if (isset($_GET['sort'])) {
    $searchParams .= '&sort=' . urlencode($_GET['sort']);
}
if (isset($_SESSION['search_status'])) {
    $searchParams .= '&search_status=' . urlencode($_SESSION['search_status']);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/settings.css">
    <link rel="stylesheet" href="styles/pages/clients.css">
    <link rel="stylesheet" href="styles/modules/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="styles/modules/micromodal.css">
    <title>CRM | Заказы</title>
</head>
<body>
    <header class="header">
        <div class="container">
            <p class="header__admin">
                <?php 
                    require 'api/DB.php';
                    require_once 'api/clients/AdminName.php';
                    require_once 'api/helpers/getUserType.php';

                    echo AdminName($_SESSION['token'], $DB);
                    $userType = getUserType($DB);
                    echo " <span style='color: green'>($userType)</span>";
                ?>
            </p>
            <ul class="header__links">
                <li><a href="clients.php">Клиенты</a></li>
                <li><a href="product.php">Товары</a></li>
                <li><a href="orders.php">Заказы</a></li>
                <li><a href="promotions.php">Акции</a></li>
                <?php
                    require_once 'api/helpers/getUserType.php';
                    $userType = getUserType($DB);
                    if ($userType === 'tech') {
                        echo '<li><a href="tech.php">Обращение пользователя</a></li>';
                    }
                ?>
            </ul>
            <a href="?do=logout" class="header__logout">Выйти</a>
        </div>
    </header>
    <main class="main">
        <section class="main__filters">
            <div class="container">
                <form action="" class="main__form">
                    <label class="main__label" for="search">Поиск по заказу</label>
                    <input <?php InputDefaultValue('search', ''); ?> class="main__input" type="text" id="search" name="search" placeholder="Поиск...">
                    <select class="main__select" name="search_name" id="search_name">
                        <option value="client.name" <?php echo ($_GET['search_name'] ?? '') === 'client.name' ? 'selected' : ''; ?>>По клиенту</option>
                        <option value="orders.id" <?php echo ($_GET['search_name'] ?? '') === 'orders.id' ? 'selected' : ''; ?>>По ID</option>
                        <option value="orders.order_date" <?php echo ($_GET['search_name'] ?? '') === 'orders.order_date' ? 'selected' : ''; ?>>По дате</option>
                        <option value="orders.total" <?php echo ($_GET['search_name'] ?? '') === 'orders.total' ? 'selected' : ''; ?>>По сумме</option>
                        <option value="orders.status" <?php echo ($_GET['search_name'] ?? '') === 'orders.status' ? 'selected' : ''; ?>>По статусу</option>
                    </select>
                    <select class="main__select" name="sort" id="sort">
                        <option value="0" <?php echo ($_GET['sort'] ?? '') === '0' ? 'selected' : ''; ?>>По умолчанию</option>
                        <option value="1" <?php echo ($_GET['sort'] ?? '') === '1' ? 'selected' : ''; ?>>По возрастанию</option>
                        <option value="2" <?php echo ($_GET['sort'] ?? '') === '2' ? 'selected' : ''; ?>>По убыванию</option>
                    </select>
                    <div class="filter-controls">
                        <label>
                            <select class="main__select" name="search_status" id="search_status">
                                <option value="all" <?php echo ($_SESSION["search_status"] === "all" ? "selected" : ""); ?>>Все заказы</option>
                                <option value="1" <?php echo ($_SESSION["search_status"] === "1" ? "selected" : ""); ?>>Активные заказы</option>
                                <option value="0" <?php echo ($_SESSION["search_status"] === "0" ? "selected" : ""); ?>>Неактивные заказы</option>
                            </select>
                        </label>
                    </div>
                    <button type="submit">Поиск</button>
                    <a href="?" class="main__reset" onclick="' . session_unset() . '">Сбросить</a>
                </form>
            </div>
        </section>
        <section class="main__clients">
            <div class="container">
                <h2 class="main__clients__title">Список заказов</h2>
                <div class="main__clients__controls">
                    <button class="main__clients__add" onclick="MicroModal.show('add-modal')"><i class="fa fa-plus-circle"></i></button>
                </div>
                <table>
                    <thead>
                        <th>ИД</th>
                        <th>Менеджер</th>
                        <th>ФИО</th>
                        <th>Дата заказа</th>
                        <th>Общая сумма</th>
                        <th>Состав заказа</th>
                        <th>Статус</th>
                        <th>Чек</th>
                        <th>Редактировать</th>
                        <th>Удалить</th>
                    </thead>
                    <tbody>
                        <?php
                            require 'api/DB.php';
                            require_once 'api/orders/OutputOrders.php';
                            require_once 'api/orders/OrdersSearch.php';

                            // Пагинация
                            $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                            $maxOrders = 5; // Количество заказов на странице

                            // Подсчет общего количества заказов с учетом фильтров
                            $search = isset($_GET['search']) ? strtolower($_GET['search']) : '';
                            $whereClause = "";
                            if (!empty($search)) {
                                $searchField = isset($_GET['search_name']) ? $_GET['search_name'] : 'client.name';
                                $whereClause = "WHERE (LOWER($searchField) LIKE '%$search%')";
                            }

                            // Добавляем условие статуса
                            if (isset($_SESSION["search_status"]) && $_SESSION["search_status"] != 'all') {
                                $whereClause = $whereClause ? $whereClause . " AND orders.status = '{$_SESSION["search_status"]}'" : "WHERE orders.status = '{$_SESSION["search_status"]}'";
                            }

                            $countQuery = "SELECT COUNT(DISTINCT orders.id) as count 
                                           FROM orders 
                                           JOIN clients ON orders.client_id = clients.id 
                                           JOIN order_items ON orders.id = order_items.order_id 
                                           JOIN products ON order_items.product_id = products.id 
                                           $whereClause";

                            $countOrders = $DB->query($countQuery)->fetchAll()[0]['count'];

                            $maxPage = ceil($countOrders / $maxOrders);
                            $minPage = 1;

                            // Prepare search parameters for pagination URLs
                            $searchParams = '';
                            if (isset($_GET['search_name'])) {
                                $searchParams .= '&search_name=' . urlencode($_GET['search_name']);
                            }
                            if (isset($_GET['search'])) {
                                $searchParams .= '&search=' . urlencode($_GET['search']);
                            }
                            if (isset($_GET['sort'])) {
                                $searchParams .= '&sort=' . urlencode($_GET['sort']);
                            }
                            if (isset($_SESSION['search_status'])) {
                                $searchParams .= '&search_status=' . urlencode($_SESSION['search_status']);
                            }

                            // Нормализация currentPage
                            if ($currentPage < $minPage || !is_numeric($currentPage)) {
                                $currentPage = $minPage;
                                header("Location: ?page=$currentPage" . $searchParams);
                                exit;
                            }
                            if ($currentPage > $maxPage && $maxPage > 0) {
                                $currentPage = $maxPage;
                                header("Location: ?page=$currentPage" . $searchParams);
                                exit;
                            }

                            $offset = ($currentPage - 1) * $maxOrders;

                            // Отображение пагинации
                            if ($maxPage > 1) {
                                echo '<div class="pagination-container">';
                                
                                // Кнопка "Предыдущая"
                                $prevDisabled = ($currentPage <= $minPage) ? " disabled" : "";
                                $Prev = $currentPage - 1;
                                echo "<a href='?page=$Prev" . $searchParams . "'$prevDisabled><i class='fa fa-arrow-left' aria-hidden='true'></i></a>";
                                
                                // Нумерованные кнопки
                                echo "<div class='pagination'>";
                                for ($i = 1; $i <= $maxPage; $i++) {
                                    $activeClass = ($i === $currentPage) ? " class='active'" : "";
                                    echo "<a href='?page=$i" . $searchParams . "'$activeClass>$i</a>";
                                }
                                echo "</div>";
                                
                                // Кнопка "Следующая"
                                $nextDisabled = ($currentPage >= $maxPage) ? " disabled" : "";
                                $Next = $currentPage + 1;
                                echo "<a href='?page=$Next" . $searchParams . "'$nextDisabled><i class='fa fa-arrow-right' aria-hidden='true'></i></a>";
                                
                                echo '</div>';
                            }

                            // Получение заказов с пагинацией
                            $orders = OrdersSearch($_GET, $DB, $offset, $maxOrders);
                            OutputOrders($orders);
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <div class="modal micromodal-slide" id="add-modal" aria-hidden="true">
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
          <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="modal-1-title">
            <header class="modal__header">
              <h2 class="modal__title" id="modal-1-title">
                Создание заказа
              </h2>
              <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
            </header>
            <main class="modal__content" id="modal-1-content">
                <form action="api/orders/AddOrders.php" method="POST" class="modal__form">
                    <div class="modal__form-group">
                        <label for="client">Клиент</label>
                        <select class="main__select" name="client" id="client">
                        <option value="new">Новый пользователь</option>
                            <?php
                                $users = $DB->query("SELECT id, name FROM clients")->fetchAll();
                                foreach ($users as $key => $user) {
                                    $id = $user['id'];
                                    $name = $user['name'];
                                    echo "<option value='$id'>$name</option>";
                                }
                            ?>
                        </select>
                    </div>
                    <div class="modal__form-group group-email" id="email-field">
                        <label for="email">Почта</label>
                        <input type="email" id="email" name="email" placeholder="Введите почту....">
                    </div>
                    <div class="modal__form-group">
                        <label for="products">Товар</label>
                        <select class="main__select" name="products[]" id="products" multiple>
                        <?php
                                $products = $DB->query("SELECT id, name, price, stock FROM products WHERE stock > 0")->fetchAll();
                                foreach ($products as $key => $product) {
                                    $id = $product['id'];
                                    $name = $product['name'];
                                    $price = $product['price'];
                                    $stock = $product['stock'];
                                    echo "<option value='$id'>$name - {$price}₽ - ({$stock} шт.)</option>";
                                }
                            ?>
                        </select>
                    </div>
                    <div class="modal__form-group">
                        <label for="promo">Промокод</label>
                        <input type="text" id="promo" name="promo" placeholder="Введите промокод...">
                    </div>
                    <div class="modal__form-actions">
                        <button type="submit" class="modal__btn modal__btn-primary">Создать</button>
                        <button type="button" class="modal__btn modal__btn-secondary" data-micromodal-close>Отменить</button>
                    </div>
                </form>
            </main>
          </div>
        </div>
      </div>
      <div class="modal micromodal-slide" id="delete-modal" aria-hidden="true">
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
            <div class="modal__container" role="dialog" aria-modal="true">
                <header class="modal__header">
                    <h2 class="modal__title">Удалить заказ?</h2>
                    <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
                </header>
                <main class="modal__content">
                    <p>Вы уверены, что хотите удалить заказ?</p>
                    <button class="modal__btn modal__btn-danger">Удалить</button>
                    <button class="modal__btn" data-micromodal-close>Отменить</button>
                </main>
            </div>
        </div>
    </div>
    <div class="modal micromodal-slide" id="edit-modal" aria-hidden="true">
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
            <div class="modal__container" role="dialog" aria-modal="true">
            <header class="modal__header">
                    <h2 class="modal__title">Редактировать заказ</h2>
              <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
            </header>
                <main class="modal__content">
                    <form action="api/orders/EditOrder.php" method="POST" class="modal__form">
                        <input type="hidden" name="id" id="edit-id">
                    <div class="modal__form-group">
                            <label for="status">Статус</label>
                            <select name="status" id="edit-status">
                                <option value="1">Активный</option>
                                <option value="0">Неактивный</option>
                            </select>
                    </div>
                    <div class="modal__form-actions">
                            <button type="submit" class="modal__btn modal__btn-primary">Сохранить</button>
                        <button type="button" class="modal__btn" data-micromodal-close>Отменить</button>
                    </div>
                </form>
            </main>
          </div>
        </div>
      </div>
      <div class="modal micromodal-slide" id="history-modal" aria-hidden="true">
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
            <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="modal-1-title">
                <header class="modal__header">
                    <h2 class="modal__title" id="modal-1-title">
                        История покупок
                    </h2>
                    <small>Фамилия Имя Отчество</small>
                    <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
                </header>
                <main class="modal__content" id="modal-1-content">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>ID заказа</th>
                                <th>Товар</th>
                                <th>Количество</th>
                                <th>Цена</th>
                                <th>Дата</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Товар 1</td>
                                <td>2</td>
                                <td>1000₽</td>
                                <td>12.01.2024</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Товар 2</td>
                                <td>1</td>
                                <td>500₽</td>
                                <td>15.01.2024</td>
                            </tr>
                        </tbody>
                    </table>
                </main>
            </div>
        </div>
    </div>
    <div class="modal micromodal-slide" id="details-modal" aria-hidden="true">
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
            <div class="modal__container" role="dialog" aria-modal="true">
                <header class="modal__header">
                    <h2 class="modal__title">Информация о заказе #<span id="order-id"></span></h2>
                    <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
                </header>
                <main class="modal__content">
                    <div class="order-details">
                        <p><strong>Клиент:</strong> <span id="client-name"></span></p>
                        <p><strong>Дата заказа:</strong> <span id="order-date"></span></p>
                        <p><strong>Общая сумма:</strong> <span id="order-total"></span>₽</p>
                        
                        <h3>Состав заказа:</h3>
                        <table class="details-table">
                            <thead>
                                <tr>
                                    <th>Товар</th>
                                    <th>Количество</th>
                                    <th>Цена</th>
                                    <th>Сумма</th>
                                </tr>
                            </thead>
                            <tbody id="order-items">
                            </tbody>
                        </table>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <div class="modal micromodal-slide
        <?php
        if (isset($_SESSION['orders_error']) && 
        !empty($_SESSION['orders_error'])) {
            echo 'is-open';
        }
        ?>
    " id="error-modal" aria-hidden="true">
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
            <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="modal-1-title">
                <header class="modal__header">
                    <h2 class="modal__title" id="modal-1-title">
                        Ошибка!
                    </h2>   
                    <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
                </header>
                <main class="modal__content" id="modal-1-content">
                <?php
                if (isset($_SESSION['orders_error'])
                && !empty($_SESSION['orders_error'])) {
                    echo $_SESSION['orders_error'];
                    unset($_SESSION['orders_error']);
                }
                ?>
                </main>
            </div>
        </div>
    </div>
    <script src="https://unpkg.com/micromodal/dist/micromodal.min.js"></script>
    <script src="scripts/initClientsModal.js"></script>
    <script src="scripts/orders.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            MicroModal.init({
                disableScroll: true,
                awaitOpenAnimation: false,
                awaitCloseAnimation: false
            });
            
            function editOrder(id, status) {
                document.getElementById('edit-id').value = id;
                document.getElementById('edit-status').value = status;
                MicroModal.show('edit-modal');
            }
            
            // Инициализация модального окна с ошибкой
            if (document.querySelector('#error-modal.is-open')) {
                MicroModal.show('error-modal');
            }

            function showOrderDetails(orderId, clientName, orderDate, total, items, discount) {
                document.getElementById('order-id').textContent = orderId;
                document.getElementById('client-name').textContent = clientName;
                document.getElementById('order-date').textContent = orderDate;
                
                // Корректное отображение цены с учетом скидки
                const totalElement = document.getElementById('order-total');
                if (discount && discount > 0) {
                    const originalPrice = total * (100 / (100 - discount));
                    totalElement.innerHTML = `${total} <span class="original-price">${originalPrice.toFixed(2)}</span>`;
                } else {
                    totalElement.textContent = total;
                }
                
                // Заполняем таблицу товаров
                // ...
                
                MicroModal.show('details-modal');
            }
        });
    </script>

    <!-- техподдержка -->
    <button class="support-btn">
        <i class="fa fa-question"></i>
    </button>

    <div class="support-create-ticket">
        <form action="api/tickets/CreateTicket.php" method="POST" enctype="multipart/form-data">
            <label for="type">Тип обращения</label>
            <select name="type" id="type" class="support-select">
                <option value="tech">Техническая неполадка</option>
                <option value="crm">Проблема с crm</option>
            </select>
            <label for="message">Текст обращения</label>
            <textarea name="message" id="message"></textarea>
            <input type="file" name="ticket_file" id="ticket_file">
            <button type="submit" class="support-submit">Создать тикет</button>
        </form>
        <button class="my-tickets-btn">Мои обращения</button>
    </div>

    <div class="my-tickets-container">
        <h3>Мои обращения</h3>
        <div class="tickets-list"></div>
    </div>

    <!-- Модальное окно чата -->
    <div class="modal micromodal-slide" id="chat-modal" aria-hidden="true">
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
            <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="chat-modal-title">
                <header class="modal__header">
                    <h2 class="modal__title" id="chat-modal-title">
                        Чат
                    </h2>
                    <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
                </header>
                <main class="modal__content" id="chat-modal-content">
                    <div id="chat-messages" class="chat-messages"></div>
                    <div class="chat-input-container">
                        <input type="text" id="chat-input" placeholder="Введите сообщение...">
                        <button id="send-message" class="send-btn">
                            <i class="fa fa-paper-plane"></i> Отправить
                        </button>
                    </div>
                </main>
            </div>
        </div>
    </div>
</body>
</html>