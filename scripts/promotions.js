document.addEventListener('DOMContentLoaded', function() {
    // Получаем элементы DOM
    const modal = document.getElementById('promotionModal');
    const addBtn = document.getElementById('addPromotionBtn');
    const closeBtn = document.querySelector('.close');
    const cancelBtn = document.getElementById('cancelBtn');
    const saveBtn = document.getElementById('savePromotionBtn');
    const form = document.getElementById('promotionForm');
    const modalTitle = document.getElementById('modalTitle');
    
    // Открываем модальное окно для добавления акции
    addBtn.addEventListener('click', function() {
        modalTitle.textContent = 'Добавить акцию';
        form.reset();
        document.getElementById('promotionId').value = '';
        
        // Устанавливаем текущую дату для начальной даты
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('startDate').value = today;
        
        // Устанавливаем дату через месяц для конечной даты
        const oneMonthLater = new Date();
        oneMonthLater.setMonth(oneMonthLater.getMonth() + 1);
        document.getElementById('endDate').value = oneMonthLater.toISOString().split('T')[0];
        
        modal.style.display = 'block';
    });
    
    // Закрываем модальное окно при клике на крестик или отмену
    closeBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    
    cancelBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    
    // Закрываем модальное окно при клике вне его
    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });
    
    // Обработка клика по кнопкам редактирования акции
    document.addEventListener('click', function(event) {
        if (event.target.closest('.edit-promotion-btn')) {
            const btn = event.target.closest('.edit-promotion-btn');
            const promotionId = btn.getAttribute('data-id');
            editPromotion(promotionId);
        }
        
        if (event.target.closest('.delete-promotion-btn')) {
            const btn = event.target.closest('.delete-promotion-btn');
            const promotionId = btn.getAttribute('data-id');
            deletePromotion(promotionId);
        }
    });
    
    // Отправка формы акции
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        
        const promotionId = document.getElementById('promotionId').value;
        const formData = new FormData(form);
        
        // Добавляем значение чекбокса
        formData.append('is_active', document.getElementById('isActive').checked ? '1' : '0');
        
        // API endpoint в зависимости от операции (создание/обновление)
        const url = promotionId ? 'api/promotions/EditPromotion.php' : 'api/promotions/CreatePromotion.php';
        
        // Отправляем данные на сервер
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(promotionId ? 'Акция успешно обновлена' : 'Акция успешно создана');
                window.location.reload();
            } else {
                alert('Ошибка: ' + (data.message || 'Неизвестная ошибка'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Произошла ошибка. Пожалуйста, попробуйте еще раз.');
        });
    });
    
    // Функция для редактирования акции
    function editPromotion(id) {
        fetch(`api/promotions/GetPromotion.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const promotion = data.promotion;
                
                modalTitle.textContent = 'Редактировать акцию';
                document.getElementById('promotionId').value = promotion.id;
                document.getElementById('title').value = promotion.title;
                document.getElementById('description').value = promotion.description;
                document.getElementById('discount').value = promotion.discount;
                
                // Форматируем даты для input[type="date"]
                const startDate = new Date(promotion.start_date);
                const endDate = new Date(promotion.end_date);
                
                // Обрабатываем потенциально неверные даты
                if (!isNaN(startDate.getTime())) {
                    document.getElementById('startDate').value = startDate.toISOString().split('T')[0];
                }
                
                if (!isNaN(endDate.getTime())) {
                    document.getElementById('endDate').value = endDate.toISOString().split('T')[0];
                }
                
                document.getElementById('isActive').checked = promotion.is_active == '1';
                
                modal.style.display = 'block';
            } else {
                alert('Ошибка при получении данных акции.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Произошла ошибка. Пожалуйста, попробуйте еще раз.');
        });
    }
    
    // Функция для удаления акции
    function deletePromotion(id) {
        if (confirm('Вы уверены, что хотите удалить эту акцию?')) {
            fetch(`api/promotions/DeletePromotion.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Акция успешно удалена.');
                    window.location.reload();
                } else {
                    alert('Ошибка при удалении акции.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка. Пожалуйста, попробуйте еще раз.');
            });
        }
    }
    
    // Функция для поиска акций
    window.searchPromotions = function() {
        const searchTerm = document.getElementById('searchInput').value.trim();
        if (searchTerm) {
            window.location.href = `promotions.php?search=${encodeURIComponent(searchTerm)}`;
        } else {
            window.location.href = 'promotions.php';
        }
    };
    
    // Обработка нажатия Enter в поле поиска
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            window.searchPromotions();
        }
    });
    
    // Заполнение поля поиска из URL
    const urlParams = new URLSearchParams(window.location.search);
    const searchParam = urlParams.get('search');
    if (searchParam) {
        document.getElementById('searchInput').value = searchParam;
    }
}); 