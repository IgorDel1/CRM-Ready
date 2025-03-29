<?php session_start();
error_log('Session data in tech.php: ' . print_r($_SESSION, true));

// Добавим получение user_id из token
if (isset($_SESSION['token']) && !isset($_SESSION['user_id'])) {
    require_once 'api/DB.php';
    $stmt = $DB->prepare("SELECT id FROM users WHERE token = ?");
    $stmt->execute([$_SESSION['token']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
    }
}

if (isset($_GET['do']) && $_GET['do'] === 'logout') {
    require_once 'api/auth/LogoutUser.php';
    require_once 'api/DB.php';

    LogoutUser('login.php', $DB, $_SESSION['token']);

    exit;
}

require_once 'api/auth/AuthCheck.php';
require_once 'api/helpers/InputDefaultValue.php';
require_once 'api/clients/ClientsSearch.php';

AuthCheck('', 'login.php');

require_once 'api/helpers/getUserType.php';

$userType = getUserType($DB);

if ($userType !== 'tech') {
    header('Location: clients.php');
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
    <title>CRM | Тех</title>
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
        <h2 class="main__clients__title">Список обращений пользователей</h2>
        <div class="filter-buttons">
            <button class="filter-btn active" data-status="all">Все</button>
            <button class="filter-btn" data-status="waiting">Ожидает</button>
            <button class="filter-btn" data-status="work">В работе</button>
            <button class="filter-btn" data-status="complete">Выполнено</button>
        </div>
        <!-- Пагинация -->
        <?php
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $maxClients = 10; // 10 карточек на странице (5x2)

        // Получаем общее количество тикетов
        $countTickets = $DB->query("SELECT COUNT(*) as count FROM tickets")
            ->fetchAll()[0]['count'];

        $maxPage = ceil($countTickets / $maxClients);
        $minPage = 1;

        // Нормализация currentPage
        if ($currentPage < $minPage || !is_numeric($currentPage)) {
            $currentPage = $minPage;
            header("Location: ?page=$currentPage");
            exit;
        }
        if ($currentPage > $maxPage) {
            $currentPage = $maxPage;
            header("Location: ?page=$currentPage");
            exit;
        }

        $offset = ($currentPage - 1) * $maxClients;
        if ($maxPage > 1) {
            echo '<div class="pagination-container">';
            
            // Кнопка "Предыдущая"
            $prevDisabled = ($currentPage <= $minPage) ? " disabled" : "";
            $Prev = $currentPage - 1;
            echo "<a href='?page=$Prev'$prevDisabled><i class='fa fa-arrow-left' aria-hidden='true'></i></a>";

            // Нумерованные кнопки
            echo "<div class='pagination'>";
            for ($i = 1; $i <= $maxPage; $i++) {
                $activeClass = ($i === $currentPage) ? " class='active'" : "";
                echo "<a href='?page=$i'$activeClass>$i</a>";
            }
            echo "</div>";

            // Кнопка "Следующая"
            $nextDisabled = ($currentPage >= $maxPage) ? " disabled" : "";
            $Next = $currentPage + 1;
            echo "<a href='?page=$Next'$nextDisabled><i class='fa fa-arrow-right' aria-hidden='true'></i></a>";
            
            echo '</div>';
        }
        ?>
        <div class="tickets-container">
            <?php
                // Получаем тикеты с учетом пагинации
                $query = "SELECT t.*, c.name as client_name, u.name as admin_name 
                         FROM tickets t 
                         LEFT JOIN clients c ON t.clients = c.id 
                         LEFT JOIN users u ON t.admin = u.id 
                         ORDER BY t.created_at DESC
                         LIMIT $maxClients OFFSET $offset";
                $stmt = $DB->query($query);
                
                while ($ticket = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $statusClass = '';
                    $statusText = '';
                    
                    switch($ticket['status']) {
                        case 'waiting':
                            $statusClass = 'status-waiting';
                            $statusText = 'Ожидает';
                            break;
                        case 'work':
                            $statusClass = 'status-work';
                            $statusText = 'В работе';
                            break;
                        case 'complete':
                            $statusClass = 'status-complete';
                            $statusText = 'Выполнено';
                            break;
                    }

                    // Определяем тип обращения
                    $ticketType = $ticket['type'] === 'tech' ? 'Техническая неполадка' : 'Проблема с CRM';

                    echo "
                    <div class='ticket-card' data-ticket-id='" . $ticket['id'] . "' data-status='" . $ticket['status'] . "'>
                        <div class='ticket-header'>
                            <span class='ticket-id'>#" . htmlspecialchars($ticket['id']) . "</span>
                            <select class='status-select " . $statusClass . "'>
                                <option value='waiting' " . ($ticket['status'] === 'waiting' ? 'selected' : '') . ">Ожидает</option>
                                <option value='work' " . ($ticket['status'] === 'work' ? 'selected' : '') . ">В работе</option>
                                <option value='complete' " . ($ticket['status'] === 'complete' ? 'selected' : '') . ">Выполнено</option>
                            </select>
                        </div>
                        <div class='ticket-type'>" . $ticketType . "</div>
                        <div class='ticket-message'><strong>Текст обращения:</strong> " . htmlspecialchars($ticket['message']) . "</div>
                        <div class='ticket-client'><i class='fa fa-user'></i> " . ($ticket['client_name'] ? htmlspecialchars($ticket['client_name']) : 'Пользователь не назначен') . "</div>
                        <div class='ticket-admin'><i class='fa fa-user-circle'></i> " . ($ticket['admin_name'] ? htmlspecialchars($ticket['admin_name']) : 'Админ не назначен') . "</div>
                        <div class='ticket-date'><i class='fa fa-calendar'></i> " . date('d.m.Y H:i', strtotime($ticket['created_at'])) . "</div>";
                    
                    // Добавляем кнопку чата перед кнопкой просмотра файла
                    echo "<div class='ticket-chat'>
                            <button onclick='openChat(" . $ticket['id'] . ")' class='chat-btn'>
                                <i class='fa fa-comments'></i> Чат
                            </button>
                        </div>";
                    
                    // Добавляем кнопку просмотра файла, если он есть
                    if ($ticket['file_path']) {
                        echo "<div class='ticket-file'>
                            <button onclick='showFile(\"" . htmlspecialchars($ticket['file_path']) . "\")' class='file-view-btn'>
                                <i class='fa fa-file-o'></i> Просмотреть файл
                            </button>
                        </div>";
                    }
                    
                    echo "</div>";
                }
            ?>
        </div>
        </div>
    </main>

    <div class="modal micromodal-slide" id="file-modal" aria-hidden="true">
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
          <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="modal-1-title">
            <header class="modal__header">
              <h2 class="modal__title" id="modal-1-title">
                Просмотр файла
              </h2>
              <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
            </header>
            <main class="modal__content" id="modal-1-content">
                <div id="file-content"></div>
            </main>
          </div>
        </div>
    </div>

    <!-- Модальное окно для чата -->
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

    <script src="https://unpkg.com/micromodal/dist/micromodal.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Функция для остановки всех медиа элементов
        function stopAllMedia() {
            const mediaElements = document.querySelectorAll('video, audio');
            mediaElements.forEach(media => {
                media.pause();
                media.currentTime = 0;
            });
        }

        // Инициализация MicroModal
        MicroModal.init({
            openTrigger: 'data-micromodal-trigger',
            closeTrigger: 'data-micromodal-close',
            disableScroll: true,
            awaitOpenAnimation: false,
            awaitCloseAnimation: false,
            onClose: modal => {
                stopAllMedia();
            }
        });

        // Добавляем обработчики для всех способов закрытия
        const modal = document.getElementById('file-modal');
        const closeButton = modal.querySelector('.modal__close');
        const overlay = modal.querySelector('.modal__overlay');

        // Обработчик для кнопки закрытия (крестик)
        closeButton.addEventListener('click', stopAllMedia);

        // Обработчик для клика по оверлею (свободное место)
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                stopAllMedia();
            }
        });

        // Обработчик для клавиши Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.hasAttribute('aria-hidden')) {
                stopAllMedia();
            }
        });

        let currentTicketId = null;

        window.openChat = function(ticketId) {
            currentTicketId = ticketId;
            loadChatMessages(ticketId);
            MicroModal.show('chat-modal');
        }

        function loadChatMessages(ticketId) {
            fetch(`api/tickets/GetTicketMessages.php?ticket_id=${ticketId}`)
                .then(response => response.json())
                .then(data => {
                    const chatMessages = document.getElementById('chat-messages');
                    chatMessages.innerHTML = '';
                    data.forEach(message => {
                        const messageTime = new Date(message.created_at).toLocaleString();
                        const messageClass = message.sender_type === 'admin' ? 'admin' : 'tech';
                        const senderLabel = message.sender_type === 'admin' ? 'Администратор' : 'Техподдержка';
                        
                        chatMessages.innerHTML += `
                            <div class="chat-message ${messageClass}">
                                <div class="message-sender">${senderLabel}</div>
                                ${message.message}
                                <span class="message-time">${messageTime}</span>
                            </div>`;
                    });
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                });
        }

        document.getElementById('send-message').addEventListener('click', function() {
            const input = document.getElementById('chat-input');
            const message = input.value.trim();
            
            if (message && currentTicketId) {
                const formData = new FormData();
                formData.append('ticket_id', currentTicketId);
                formData.append('message', message);

                fetch('api/tickets/SendMessage.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        input.value = '';
                        loadChatMessages(currentTicketId);
                    } else {
                        console.error('Server response:', data);
                        alert('Ошибка при отправке сообщения: ' + (data.message || 'Неизвестная ошибка'));
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Ошибка при отправке сообщения: ' + error.message);
                });
            }
        });

        // Отправка по Enter
        document.getElementById('chat-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('send-message').click();
            }
        });
    });

    function showFile(filePath) {
        const fileContent = document.getElementById('file-content');
        const extension = filePath.split('.').pop().toLowerCase();
        
        fileContent.innerHTML = '';
        
        if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
            fileContent.innerHTML = `<img src="${filePath}" style="max-width: 100%; height: auto;">`;
        } else if (['pdf'].includes(extension)) {
            fileContent.innerHTML = `<embed src="${filePath}" type="application/pdf" style="width: 100%; height: 600px;">`;
        } else if (extension === 'mp4') {
            fileContent.innerHTML = `
                <video controls style="width: 100%; max-height: 80vh; object-fit: contain;">
                    <source src="${filePath}" type="video/mp4">
                    Ваш браузер не поддерживает видео тег.
                </video>`;
        } else if (extension === 'mp3') {
            fileContent.innerHTML = `
                <audio controls style="width: 100%;">
                    <source src="${filePath}" type="audio/mpeg">
                    Ваш браузер не поддерживает аудио тег.
                </audio>`;
        } else {
            fileContent.innerHTML = `<a href="${filePath}" target="_blank" class="modal__btn">Скачать файл</a>`;
        }
        
        MicroModal.show('file-modal');
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', function() {
                const ticketCard = this.closest('.ticket-card');
                const ticketId = ticketCard.dataset.ticketId;
                const newStatus = this.value;
                const select = this;

                const formData = new FormData();
                formData.append('ticketId', ticketId);
                formData.append('status', newStatus);

                fetch('api/tickets/UpdateTicketStatus.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        ticketCard.dataset.status = newStatus;
                        select.classList.remove('status-waiting', 'status-work', 'status-complete');
                        select.classList.add('status-' + newStatus);
                    } else {
                        alert('Ошибка при обновлении статуса: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ошибка при выполнении запроса');
                });
            });
        });

        const filterButtons = document.querySelectorAll('.filter-btn');
        const ticketCards = document.querySelectorAll('.ticket-card');
        
        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                filterButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                
                const status = button.dataset.status;
                
                ticketCards.forEach(card => {
                    const cardStatus = card.querySelector('.status-select').value;
                    if (status === 'all' || cardStatus === status) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    });

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
        
        // Функция для открытия чата по ID тикета
        window.openUserChat = function(ticketId) {
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
                        const messageClass = message.sender_type === 'tech' ? 'tech' : 'user';
                        const senderLabel = message.sender_type === 'tech' ? 'Техподдержка' : 'Вы';
                        
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