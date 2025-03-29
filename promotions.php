<?php session_start();

if (isset($_GET['do']) && $_GET['do'] === 'logout') {
    require_once 'api/auth/LogoutUser.php';
    require_once 'api/DB.php';

    LogoutUser('login.php', $DB, $_SESSION['token']);

    exit;
}

require_once 'api/auth/AuthCheck.php';
require_once 'api/helpers/InputDefaultValue.php';

AuthCheck('', 'login.php');

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
    <link rel="stylesheet" href="styles/pages/promotions.css">
    <link rel="stylesheet" href="styles/tech.css">
    <title>CRM | Акции</title>
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
                    if ($userType === 'tech') {
                        echo '<li><a href="tech.php">Обращение пользователя</a></li>';
                    }
                ?>
            </ul>
            <a href="?do=logout" class="header__logout">Выйти</a>
        </div>
    </header>
    <main class="main">
        <div class="container">
            <h2 class="main__clients__title">Список акций</h2>
            <div class="main__clients__controls">
                <button class="main__clients__add" onclick="MicroModal.show('add-promotion-modal')"><i class="fa fa-plus-circle"></i></button>
        </div>
            
        <?php
            // Пагинация
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $maxPromotions = 6; // 6 карточек на страницу

        // Получаем общее количество акций
        $countPromotions = $DB->query("SELECT COUNT(*) as count FROM promotions")
            ->fetchAll()[0]['count'];

        $maxPage = ceil($countPromotions / $maxPromotions);
        $minPage = 1;

        // Сохраняем параметры поиска для URL пагинации
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

        $offset = ($currentPage - 1) * $maxPromotions;
        
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
        ?>
            
            <div class="promotions-container">
            <?php
                // Получаем акции с учетом пагинации
                $query = "SELECT * FROM promotions ORDER BY created_at DESC LIMIT $maxPromotions OFFSET $offset";
                $promotions = $DB->query($query)->fetchAll();
                
                foreach ($promotions as $promo) {
                    $imagePath = !empty($promo['path_to_image']) ? $promo['path_to_image'] : 'images/default-promo.jpg';
                    $usesInfo = $promo['uses'] . '/' . $promo['max_uses'];
                    $startDate = date('d.m.Y', strtotime($promo['created_at']));
                    $endDate = date('d.m.Y', strtotime($promo['cancel_at']));
                    $isActive = strtotime($promo['cancel_at']) > time() && $promo['uses'] < $promo['max_uses'];
                    $statusClass = $isActive ? 'promotion-active' : 'promotion-inactive';
                    $statusText = $isActive ? 'Активна' : 'Неактивна';

                    echo "
                    <div class='promotion-card $statusClass'>
                        <div class='promotion-status'>$statusText</div>
                        <div class='promotion-image'>
                            <img src='$imagePath' alt='{$promo['title']}'>
                        </div>
                        <div class='promotion-content'>
                            <h3 class='promotion-title'>{$promo['title']}</h3>
                            <div class='promotion-body'>{$promo['body']}</div>
                            <div class='promotion-info'>
                                <div class='promotion-promo'>
                                    <span>Промокод: </span>
                                    <span class='promo-code'>{$promo['code_promo']}</span>
                                    <button class='copy-btn' data-promo='{$promo['code_promo']}'>
                                        <i class='fa fa-copy'></i>
                                    </button>
                                </div>
                                <div class='promotion-discount'>Скидка: {$promo['discount']}%</div>
                                <div class='promotion-uses'>Использований: $usesInfo</div>
                                <div class='promotion-dates'>Период: $startDate - $endDate</div>
                        </div>
                            <div class='promotion-actions'>
                                <button class='edit-promotion-btn' 
                                        onclick='editPromotion({$promo['id']})'>
                                    <i class='fa fa-pencil'></i> Редактировать
                            </button>
                                <button class='delete-promotion-btn' 
                                        onclick='deletePromotion({$promo['id']})'>
                                    <i class='fa fa-trash'></i> Удалить
                            </button>
                            </div>
                        </div>
                        </div>";
                    }
                    
                if (count($promotions) === 0) {
                    echo "<div class='no-promotions'>Акции не найдены. Создайте новую акцию!</div>";
                }
            ?>
        </div>
        </div>
    </main>

    <!-- Модальное окно для добавления акции -->
    <div class="modal micromodal-slide" id="add-promotion-modal" aria-hidden="true">
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
            <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="modal-title">
                <header class="modal__header">
                    <h2 class="modal__title" id="modal-title">
                        Создать акцию
                    </h2>
                    <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
                </header>
                <main class="modal__content">
                    <form action="api/promotions/AddPromotion.php" method="POST" enctype="multipart/form-data" class="modal__form">
                        <div class="modal__form-group">
                            <label for="promo-title">Заголовок</label>
                            <input type="text" id="promo-title" name="title" required>
                        </div>
                        <div class="modal__form-group">
                            <label for="promo-body">Описание</label>
                            <textarea id="promo-body" name="body" rows="4" required></textarea>
                        </div>
                        <div class="modal__form-group">
                            <label for="promo-image">Изображение</label>
                            <input type="file" id="promo-image" name="image" accept="image/*">
                        </div>
                        <div class="modal__form-group">
                            <label for="promo-code">Промокод</label>
                            <div class="promo-code-container">
                                <input type="text" id="promo-code" name="code_promo" required>
                                <button type="button" id="generate-promo-btn">Сгенерировать</button>
                            </div>
                        </div>
                        <div class="modal__form-group">
                            <label for="promo-discount">Скидка (%)</label>
                            <input type="number" id="promo-discount" name="discount" min="1" max="100" required>
                        </div>
                        <div class="modal__form-group">
                            <label for="promo-max-uses">Максимальное количество использований</label>
                            <input type="number" id="promo-max-uses" name="max_uses" min="1" required>
                        </div>
                        <div class="modal__form-group">
                            <label for="promo-cancel-date">Дата окончания</label>
                            <input type="date" id="promo-cancel-date" name="cancel_at" required>
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

    <!-- Модальное окно для редактирования акции -->
    <div class="modal micromodal-slide" id="edit-promotion-modal" aria-hidden="true">
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
            <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="edit-modal-title">
            <header class="modal__header">
                    <h2 class="modal__title" id="edit-modal-title">
                        Редактировать акцию
              </h2>
              <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
            </header>
                <main class="modal__content">
                    <form action="api/promotions/EditPromotion.php" method="POST" enctype="multipart/form-data" class="modal__form">
                        <input type="hidden" id="edit-promo-id" name="id">
                        <div class="modal__form-group">
                            <label for="edit-promo-title">Заголовок</label>
                            <input type="text" id="edit-promo-title" name="title" required>
                        </div>
                        <div class="modal__form-group">
                            <label for="edit-promo-body">Описание</label>
                            <textarea id="edit-promo-body" name="body" rows="4" required></textarea>
                        </div>
                        <div class="modal__form-group">
                            <label for="edit-promo-image">Изображение</label>
                            <div class="current-image-container">
                                <img id="current-promo-image" src="" alt="Текущее изображение">
                            </div>
                            <input type="file" id="edit-promo-image" name="image" accept="image/*">
                            <input type="hidden" id="edit-current-image" name="current_image">
                        </div>
                        <div class="modal__form-group">
                            <label for="edit-promo-code">Промокод</label>
                            <div class="promo-code-container">
                                <input type="text" id="edit-promo-code" name="code_promo" required>
                                <button type="button" id="edit-generate-promo-btn">Сгенерировать</button>
                            </div>
                        </div>
                        <div class="modal__form-group">
                            <label for="edit-promo-discount">Скидка (%)</label>
                            <input type="number" id="edit-promo-discount" name="discount" min="1" max="100" required>
                        </div>
                        <div class="modal__form-group">
                            <label for="edit-promo-max-uses">Максимальное количество использований</label>
                            <input type="number" id="edit-promo-max-uses" name="max_uses" min="1" required>
                        </div>
                        <div class="modal__form-group">
                            <label for="edit-promo-cancel-date">Дата окончания</label>
                            <input type="date" id="edit-promo-cancel-date" name="cancel_at" required>
                        </div>
                        <div class="modal__form-actions">
                            <button type="submit" class="modal__btn modal__btn-primary">Сохранить</button>
                            <button type="button" class="modal__btn modal__btn-secondary" data-micromodal-close>Отменить</button>
                        </div>
                    </form>
            </main>
          </div>
        </div>
    </div>

    <!-- Модальное окно подтверждения удаления -->
    <div class="modal micromodal-slide" id="delete-promotion-modal" aria-hidden="true">
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
            <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title">
                <header class="modal__header">
                    <h2 class="modal__title" id="delete-modal-title">
                        Удалить акцию?
                    </h2>
                    <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
                </header>
                <main class="modal__content">
                    <p>Вы уверены, что хотите удалить эту акцию? Это действие нельзя отменить.</p>
                    <form action="api/promotions/DeletePromotion.php" method="POST">
                        <input type="hidden" id="delete-promo-id" name="id">
                        <div class="modal__form-actions">
                            <button type="submit" class="modal__btn modal__btn-danger">Удалить</button>
                            <button type="button" class="modal__btn modal__btn-secondary" data-micromodal-close>Отменить</button>
                    </div>
                    </form>
                </main>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/micromodal/dist/micromodal.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
            // Инициализация модальных окон
        MicroModal.init({
            disableScroll: true,
            awaitOpenAnimation: false,
                awaitCloseAnimation: false
            });
            
            // Функция для генерации случайного промокода
            function generatePromoCode() {
                const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                let promoCode = '';
                for (let i = 0; i < 8; i++) {
                    promoCode += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                return promoCode;
            }
            
            // Обработчик для кнопки генерации промокода (добавление)
            document.getElementById('generate-promo-btn').addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('promo-code').value = generatePromoCode();
            });
            
            // Обработчик для кнопки генерации промокода (редактирование)
            document.getElementById('edit-generate-promo-btn').addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('edit-promo-code').value = generatePromoCode();
            });
            
            // Устанавливаем минимальную дату окончания акции как сегодня
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('promo-cancel-date').min = today;
            document.getElementById('edit-promo-cancel-date').min = today;
            
            // Установка значения по умолчанию для даты окончания (сегодня + 30 дней)
            const defaultDate = new Date();
            defaultDate.setDate(defaultDate.getDate() + 30);
            const defaultDateString = defaultDate.toISOString().split('T')[0];
            document.getElementById('promo-cancel-date').value = defaultDateString;
            
            // Копирование промокода в буфер обмена
            document.querySelectorAll('.copy-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const promoCode = this.getAttribute('data-promo');
                    navigator.clipboard.writeText(promoCode).then(() => {
                        // Временно меняем иконку для подтверждения
                        const icon = this.querySelector('i');
                        icon.classList.remove('fa-copy');
                        icon.classList.add('fa-check');
                        setTimeout(() => {
                            icon.classList.remove('fa-check');
                            icon.classList.add('fa-copy');
                        }, 1500);
                    });
                });
            });
        });
        
        // Функция для редактирования акции
        function editPromotion(id) {
            fetch(`api/promotions/GetPromotion.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        // Показываем уведомление об ошибке
                        showNotification(data.error, 'error');
                        return;
                    }
                    
                    document.getElementById('edit-promo-id').value = data.id;
                    document.getElementById('edit-promo-title').value = data.title;
                    document.getElementById('edit-promo-body').value = data.body;
                    document.getElementById('edit-promo-code').value = data.code_promo;
                    document.getElementById('edit-promo-discount').value = data.discount;
                    document.getElementById('edit-promo-max-uses').value = data.max_uses;
                    
                    // Преобразовать дату в формат YYYY-MM-DD для input type="date"
                    let formattedDate;
                    try {
                        const cancelDate = new Date(data.cancel_at);
                        // Проверим, является ли дата действительной
                        if (isNaN(cancelDate.getTime())) {
                            // Если дата недействительна, используем текущую дату + 30 дней
                            const defaultDate = new Date();
                            defaultDate.setDate(defaultDate.getDate() + 30);
                            formattedDate = defaultDate.toISOString().split('T')[0];
                            console.warn('Неверный формат даты в базе данных, установлена дата по умолчанию');
                        } else {
                            formattedDate = cancelDate.toISOString().split('T')[0];
                        }
                    } catch (e) {
                        // В случае ошибки используем текущую дату + 30 дней
                        const defaultDate = new Date();
                        defaultDate.setDate(defaultDate.getDate() + 30);
                        formattedDate = defaultDate.toISOString().split('T')[0];
                        console.error('Ошибка при обработке даты:', e);
                    }
                    
            document.getElementById('edit-promo-cancel-date').value = formattedDate;
            
            // Отображаем текущее изображение
            if (data.path_to_image) {
                document.getElementById('current-promo-image').src = data.path_to_image;
                document.getElementById('current-promo-image').style.display = 'block';
                document.getElementById('edit-current-image').value = data.path_to_image;
                    } else {
                document.getElementById('current-promo-image').style.display = 'none';
                    }
            
            MicroModal.show('edit-promotion-modal');
                })
                .catch(error => {
            console.error('Ошибка при загрузке данных акции:', error);
            showNotification('Не удалось загрузить данные акции. Проверьте консоль для подробностей.', 'error');
        });
    }
        
        // Функция для удаления акции
        function deletePromotion(id) {
            document.getElementById('delete-promo-id').value = id;
            MicroModal.show('delete-promotion-modal');
        }
        
        // Обработка уведомлений
        document.addEventListener('DOMContentLoaded', function() {
            // Показываем уведомление об успешной операции
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success')) {
                const successType = urlParams.get('success');
                let message = '';
                
                switch(successType) {
                    case '1':
                        message = 'Акция успешно создана!';
                        break;
                    case '2':
                        message = 'Акция успешно обновлена!';
                        break;
                    case '3':
                        message = 'Акция успешно удалена!';
                        break;
                }
                
                if (message) {
                    showNotification(message, 'success');
                }
            }
            
            if (urlParams.has('error')) {
                const errorMsg = '<?php echo isset($_SESSION["promotion_error"]) ? $_SESSION["promotion_error"] : "Произошла ошибка"; ?>';
                showNotification(errorMsg, 'error');
                <?php unset($_SESSION["promotion_error"]); ?>
            }
        });
        
        // Функция для отображения уведомлений
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Показываем уведомление
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);
            
            // Скрываем и удаляем через 5 секунд
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 5000);
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

    <script>
    // Инициализация и работа компонента техподдержки
    document.addEventListener('DOMContentLoaded', function() {
        const supportBtn = document.querySelector('.support-btn');
        const supportPanel = document.querySelector('.support-create-ticket');
        const myTicketsBtn = document.querySelector('.my-tickets-btn');
        const myTicketsContainer = document.querySelector('.my-tickets-container');
        
        // Обработчик кнопки техподдержки
        if (supportBtn) {
            supportBtn.addEventListener('click', function() {
                supportPanel.classList.toggle('active');
                myTicketsContainer.classList.remove('active');
            });
        }
        
        // Обработчик кнопки "Мои обращения"
        if (myTicketsBtn) {
            myTicketsBtn.addEventListener('click', function() {
                supportPanel.classList.remove('active');
                myTicketsContainer.classList.toggle('active');
                loadMyTickets();
            });
        }
        
        // Функция загрузки списка тикетов пользователя
        function loadMyTickets() {
            const ticketsList = document.querySelector('.tickets-list');
            
            fetch('api/tickets/GetMyTickets.php')
                .then(response => response.json())
                .then(data => {
                    ticketsList.innerHTML = '';
                    
                    if (data.length === 0) {
                        ticketsList.innerHTML = '<p>У вас пока нет обращений</p>';
                        return;
                    }
                    
                    data.forEach(ticket => {
                        const statusClass = `status-${ticket.status}`;
                        let statusText = '';
                        
                        switch(ticket.status) {
                            case 'waiting':
                                statusText = 'Ожидает';
                                break;
                            case 'work':
                                statusText = 'В работе';
                                break;
                            case 'complete':
                                statusText = 'Выполнено';
                                break;
                        }
                        
                        ticketsList.innerHTML += `
                            <div class="ticket-item ${statusClass}">
                                <div class="ticket-header">
                                    <span class="ticket-id">#${ticket.id}</span>
                                    <span class="ticket-status">${statusText}</span>
                                </div>
                                <div class="ticket-type">${ticket.type === 'tech' ? 'Техническая неполадка' : 'Проблема с CRM'}</div>
                                <div class="ticket-message">${ticket.message}</div>
                                <div class="ticket-date">${new Date(ticket.created_at).toLocaleString()}</div>
                                <button class="chat-btn" onclick="openChat(${ticket.id})">
                                    <i class="fa fa-comments"></i> Чат
                                </button>
                            </div>
                        `;
                    });
                })
                .catch(error => {
                    console.error('Ошибка при загрузке тикетов:', error);
                    ticketsList.innerHTML = '<p>Ошибка при загрузке обращений</p>';
                });
        }
        
        // Глобальная функция для открытия чата
        window.openChat = function(ticketId) {
            // Сохраняем ID текущего тикета для отправки сообщений
            window.currentTicketId = ticketId;
            
            // Загружаем сообщения
            loadChatMessages(ticketId);
            
            // Открываем модальное окно чата
            MicroModal.show('chat-modal');
        };
        
        // Функция загрузки сообщений чата
        function loadChatMessages(ticketId) {
            const chatMessages = document.getElementById('chat-messages');
            
            fetch(`api/tickets/GetTicketMessages.php?ticket_id=${ticketId}`)
                .then(response => response.json())
                .then(data => {
                    chatMessages.innerHTML = '';
                    
                    data.forEach(message => {
                        const messageTime = new Date(message.created_at).toLocaleString();
                        const messageClass = message.sender_type === 'admin' ? 'admin' : 'user';
                        const senderLabel = message.sender_type === 'admin' ? 'Техподдержка' : 'Вы';
                        
                        chatMessages.innerHTML += `
                            <div class="chat-message ${messageClass}">
                                <div class="message-sender">${senderLabel}</div>
                                <div class="message-text">${message.message}</div>
                                <span class="message-time">${messageTime}</span>
                            </div>
                        `;
                    });
                    
                    // Прокручиваем чат вниз
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                })
                .catch(error => {
                    console.error('Ошибка при загрузке сообщений:', error);
                    chatMessages.innerHTML = '<p class="error-message">Ошибка при загрузке сообщений</p>';
                });
        }
        
        // Отправка сообщения в чат
        const sendMessageBtn = document.getElementById('send-message');
        if (sendMessageBtn) {
            sendMessageBtn.addEventListener('click', function() {
                const input = document.getElementById('chat-input');
                const message = input.value.trim();
                
                if (message && window.currentTicketId) {
                    const formData = new FormData();
                    formData.append('ticket_id', window.currentTicketId);
                    formData.append('message', message);
                    
                    fetch('api/tickets/SendMessage.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            input.value = '';
                            loadChatMessages(window.currentTicketId);
                        } else {
                            alert('Ошибка при отправке сообщения: ' + (data.message || 'Неизвестная ошибка'));
                        }
                    })
                    .catch(error => {
                        console.error('Ошибка:', error);
                        alert('Ошибка при отправке сообщения');
                    });
                }
            });
        }
        
        // Отправка по нажатию на Enter
        const chatInput = document.getElementById('chat-input');
        if (chatInput) {
            chatInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    document.getElementById('send-message').click();
                }
            });
        }
    });
    </script>
</body>
</html>