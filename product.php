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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/settings.css">
    <link rel="stylesheet" href="styles/pages/products.css">
    <link rel="stylesheet" href="styles/pages/clients.css">
    <link rel="stylesheet" href="styles/modules/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="styles/modules/micromodal.css">
    <title>CRM | Товары</title>
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
            <form action="" method="GET" class="main__form">
                    <label class="main__label" for="search">Поиск по названию</label>
                    <input <?php InputDefaultValue('search', ''); ?> class="main__input" type="text" id="search" name="search" placeholder="Что-то...">
                    <select class="main__select" name="search_name" id="sort1">
                        <option value="name" <?php echo ($_GET['search_name'] ?? '') === 'name' ? 'selected' : ''; ?>>Название</option>
                        <option value="price" <?php echo ($_GET['search_name'] ?? '') === 'price' ? 'selected' : ''; ?>>Цена</option>
                        <option value="stock" <?php echo ($_GET['search_name'] ?? '') === 'stock' ? 'selected' : ''; ?>>Количество</option>
                    </select>
                    <select class="main__select" name="sort" id="sort">
                        <option value="0" <?php echo ($_GET['sort'] ?? '') === '0' ? 'selected' : ''; ?>>По умолчанию</option>
                        <option value="1" <?php echo ($_GET['sort'] ?? '') === '1' ? 'selected' : ''; ?>>По возрастанию</option>
                        <option value="2" <?php echo ($_GET['sort'] ?? '') === '2' ? 'selected' : ''; ?>>По убыванию</option>
                    </select>
                    <button type="submit">Поиск</button>
                    <a href="?" class="main__reset">Сбросить</a>
                </form>
            </div>
        </section>
        <section class="main__products">
            <div class="container">
                <h2 class="main__products__title">Список товаров</h2>
                <button class="main__products__add" onclick="MicroModal.show('add-modal')"><i class="fa fa-plus-circle"></i></button>
                <table>
                    <thead>
                        <th>ИД</th>
                        <th>Название</th>
                        <th>Описание</th>
                        <th>Цена</th>
                        <th>Количество</th>
                        <th>QR код</th>
                        <th>Редактировать</th>
                        <th>Удалить</th>
                    </thead>
                    <tbody>
                    <?php
                        require 'api/DB.php';
                        require_once('api/product/ProductSearch.php');
                        require_once('api/product/OutputProduct.php');

                        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                        $maxProducts = 5;

                        $countProducts = $DB->query("SELECT COUNT(*) as count FROM products")->fetchAll()[0]['count'];

                        // Build search parameters string
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

                        $maxPage = ceil($countProducts / $maxProducts);
                        $minPage = 1;

                        // Normalize currentPage
                        if ($currentPage < $minPage || !is_numeric($currentPage)) {
                            $currentPage = $minPage;
                            header("Location: ?page=$currentPage" . $searchParams);
                            exit;
                        }
                        if ($currentPage > $maxPage) {
                            $currentPage = $maxPage;
                            header("Location: ?page=$currentPage" . $searchParams);
                            exit;
                        }

                        // Wrap pagination in container
                        echo "<div class='pagination-container'>";
                        
                        // Prev button with link
                        $prevPage = $currentPage - 1;
                        $prevDisabled = ($currentPage <= $minPage) ? " disabled" : "";
                        echo "<a href='?page=$prevPage" . $searchParams . "'$prevDisabled><i class='fa fa-arrow-left'></i></a>";

                        // Numbered pagination
                        echo "<div class='pagination'>";
                        for ($i = 1; $i <= $maxPage; $i++) {
                            if ($i == $currentPage) {
                                echo "<span class='active'>$i</span>";
                            } else {
                                echo "<a href='?page=$i" . $searchParams . "'>$i</a>";
                            }
                        }
                        echo "</div>";

                        // Next button with link
                        $nextPage = $currentPage + 1;
                        $nextDisabled = ($currentPage >= $maxPage) ? " disabled" : "";
                        echo "<a href='?page=$nextPage" . $searchParams . "'$nextDisabled><i class='fa fa-arrow-right'></i></a>";

                        echo "</div>";

                        $products = ProductSearch($_GET, $DB);
                        OutputProducts($products);
                        ?>
                        <!-- <tr>
                            <td>1</td>
                            <td>Товар 1</td>
                            <td>Описание товара 1</td>
                            <td>1000₽</td>
                            <td>10</td>
                            <td onclick="MicroModal.show('qr-modal')"><i class="fa fa-qrcode"></i></td>
                            <td onclick="MicroModal.show('edit-modal')"><i class="fa fa-pencil"></i></td>
                            <td onclick="MicroModal.show('delete-modal')"><i class="fa fa-trash"></i></td>
                        </tr> -->
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Модальное окно добавления товара -->
    <div class="modal micromodal-slide" id="add-modal" aria-hidden="true">
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
            <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="modal-1-title">
                <header class="modal__header">
                    <h2 class="modal__title" id="modal-1-title">
                        Добавить товар
                    </h2>
                    <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
                </header>
                <main class="modal__content" id="modal-1-content">
                    <form action="api/product/AddProduct.php" method="POST" class="modal__form">
                        <div class="modal__form-group">
                            <label for="name">Название</label>
                            <input type="text" id="name" name="name">
                        </div>
                        <div class="modal__form-group">
                            <label for="description">Описание</label>
                            <textarea id="description" name="description"></textarea>
                        </div>
                        <div class="modal__form-group">
                            <label for="price">Цена</label>
                            <input type="number" id="price" name="price">
                        </div>
                        <div class="modal__form-group">
                            <label for="quantity">Количество</label>
                            <input type="number" id="quantity" name="quantity">
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

    <!-- Модальное окно редактирования товара -->
    <div class="modal micromodal-slide" id="edit-modal" aria-hidden="true">
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
            <div class="modal__container" role="dialog" aria-modal="true">
                <header class="modal__header">
                    <h2 class="modal__title">Редактировать товар</h2>
                    <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
                </header>
                <main class="modal__content">
                    <form action="api/product/EditProduct.php" method="POST" class="modal__form">
                        <input type="hidden" name="id" id="edit-id">
                        <div class="modal__form-group">
                            <label for="edit-name">Название</label>
                            <input type="text" id="edit-name" name="name" required>
                        </div>
                        <div class="modal__form-group">
                            <label for="edit-description">Описание</label>
                            <textarea id="edit-description" name="description" required></textarea>
                        </div>
                        <div class="modal__form-group">
                            <label for="edit-price">Цена</label>
                            <input type="number" id="edit-price" name="price" required>
                        </div>
                        <div class="modal__form-group">
                            <label for="edit-quantity">Количество</label>
                            <input type="number" id="edit-quantity" name="quantity" required>
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

    <!-- Модальное окно удаления товара -->
    <div class="modal micromodal-slide" id="delete-modal" aria-hidden="true">
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
            <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="modal-1-title">
                <header class="modal__header">
                    <h2 class="modal__title" id="modal-1-title">
                        Вы уверены, что хотите удалить товар?
                    </h2>
                    <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
                </header>
                <main class="modal__content" id="modal-1-content">
                    <button class="modal__btn danger">Удалить</button>
                    <button class="modal__btn" data-micromodal-close>Отменить</button>
                </main>
            </div>
        </div>
    </div>

    <!-- Модальное окно QR кода -->
    <div class="modal micromodal-slide" id="qr-modal" aria-hidden="true">
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
            <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="modal-1-title">
                <header class="modal__header">
                    <h2 class="modal__title" id="modal-1-title">
                        QR код товара
                    </h2>
                    <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
                </header>
                <main class="modal__content" id="modal-1-content">
                    <div class="qr-code-container">
                        <!-- Здесь будет QR код -->
                        <img src="path/to/qr-code.png" alt="QR код товара">
                    </div>
                    <button class="modal__btn modal__btn-primary">Скачать</button>
                </main>
            </div>
        </div>
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

    <!-- Скрипты должны быть в правильном порядке -->
    <script src="https://unpkg.com/micromodal/dist/micromodal.min.js"></script>
    <script src="scripts/initClientsModal.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            MicroModal.init({
                disableScroll: true,
                awaitOpenAnimation: false,
                awaitCloseAnimation: false
            });
        });
    </script>

    <script>
    function editProduct(id, name, description, price, quantity) {
        document.getElementById('edit-id').value = id;
        document.getElementById('edit-name').value = name;
        document.getElementById('edit-description').value = description;
        document.getElementById('edit-price').value = price;
        document.getElementById('edit-quantity').value = quantity;
        MicroModal.show('edit-modal');
    }
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

</body>
</html> 