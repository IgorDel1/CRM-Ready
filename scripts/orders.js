const clientInput = document.querySelector('#client');

const emailField = document.querySelector('#email-field');

const toggleVisibleEmailField = () => {
    const value = clientInput.value;
    if (value === 'new') {
        emailField.style.display = 'block';
    } else {
        emailField.style.display = 'none';
    }
}
toggleVisibleEmailField();
clientInput.addEventListener('input', toggleVisibleEmailField);

document.addEventListener('DOMContentLoaded', function() {
    // Обработчик изменения статуса заказов
    document.querySelector('select[name="search_status"]').addEventListener('change', function(e) {
        const currentUrl = new URL(window.location.href);
        const searchParams = currentUrl.searchParams;
        
        // Обновляем или добавляем параметр search_status
        searchParams.set('search_status', e.target.value);
        
        // Сбрасываем страницу на первую при изменении фильтра
        if(searchParams.has('page')) {
            searchParams.set('page', '1');
        }
        
        // Обновляем URL и перезагружаем страницу
        window.location.href = currentUrl.toString();
    });

    // Находим все кнопки редактирования
    const editButtons = document.querySelectorAll('.edit-order-btn');
    
    // Добавляем обработчик для каждой кнопки
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-id');
            const orderStatus = this.getAttribute('data-status');
            editOrder(orderId, orderStatus);
        });
    });
});

// Функция для открытия модального окна редактирования
function editOrder(id, status) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-status').value = status;
    MicroModal.show('edit-modal');
}
