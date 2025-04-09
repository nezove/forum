// ajax.js - обработка AJAX запросов
document.addEventListener('DOMContentLoaded', function() {
    // Обработка лайков
    initLikeButtons();
    
    // Обработка ответов в темах
    initReplyForm();
    
    // Обработка закладок
    initBookmarkButtons();
});

// Инициализация кнопок лайков
function initLikeButtons() {
    const likeButtons = document.querySelectorAll('.like-button');
    
    likeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const postId = this.dataset.postId;
            const likeCountElement = document.querySelector(`.like-count[data-post-id="${postId}"]`);
            
            fetch('api/like.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ post_id: postId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    likeCountElement.textContent = data.likes;
                    
                    if (data.action === 'liked') {
                        this.classList.add('liked');
                        this.setAttribute('title', 'Убрать лайк');
                        this.querySelector('svg').innerHTML = '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" fill="currentColor"></path>';
                    } else {
                        this.classList.remove('liked');
                        this.setAttribute('title', 'Поставить лайк');
                        this.querySelector('svg').innerHTML = '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>';
                    }
                } else {
                    // Если пользователь не авторизован, перенаправляем на страницу входа
                    if (data.message === 'Необходимо авторизоваться') {
                        window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                    } else {
                        showAlert(data.message, 'error');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Произошла ошибка при обработке запроса', 'error');
            });
        });
    });
}

// Инициализация формы ответа
function initReplyForm() {
    const replyForm = document.getElementById('reply-form');
    
    if (replyForm) {
        replyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const topicId = this.dataset.topicId;
            const content = document.getElementById('reply-content').value;
            const submitButton = this.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.textContent;
            
            if (content.trim() === '') {
                showAlert('Сообщение не может быть пустым', 'error');
                return;
            }
            
            // Блокируем кнопку и показываем индикатор загрузки
            submitButton.disabled = true;
            submitButton.textContent = 'Отправка...';
            
            fetch('api/reply.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ topic_id: topicId, content: content })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Очищаем поле ввода
                    document.getElementById('reply-content').value = '';
                    
                    // Создаем и добавляем новый пост в DOM
                    appendNewPost(data.post);
                    
                    // Показываем уведомление об успехе
                    showAlert('Ответ успешно добавлен', 'success');
                    
                    // Прокручиваем к новому посту
                    window.location.hash = 'post-' + data.post.id;
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Произошла ошибка при отправке ответа', 'error');
            })
            .finally(() => {
                // Разблокируем кнопку и восстанавливаем текст
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
            });
        });
    }
}

// Добавление нового поста в DOM
function appendNewPost(post) {
    const postsContainer = document.querySelector('.posts-list');
    
    // Создаем элемент поста
    const postElement = document.createElement('div');
    postElement.className = 'post-card';
    postElement.id = 'post-' + post.id;
    
    // Формируем HTML для нового поста
    postElement.innerHTML = `
        <div class="post-author">
            <div class="post-avatar">
                <img src="assets/uploads/avatars/${post.avatar}" alt="${post.username}">
            </div>
            <div class="post-username">${post.username}</div>
            <div class="post-userstats">
                Сообщений: ${post.post_count}
            </div>
            <div class="post-joined">
                На форуме с ${post.joined}
            </div>
        </div>
        <div class="post-content">
            <div class="post-body">
                ${post.content}
            </div>
            <div class="post-footer">
                <div class="post-date">${post.created_at}</div>
                <div class="post-actions">
                    <button class="btn btn-sm like-button" data-post-id="${post.id}" title="Поставить лайк">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                        <span class="like-count" data-post-id="${post.id}">0</span>
                    </button>
                    <button class="btn btn-sm quote-button" data-post-id="${post.id}" data-username="${post.username}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Добавляем пост в контейнер
    postsContainer.appendChild(postElement);
    
    // Инициализируем кнопки лайков и цитирования для нового поста
    initLikeButtons();
    initQuoteButtons();
}

// Инициализация кнопок цитирования
function initQuoteButtons() {
    const quoteButtons = document.querySelectorAll('.quote-button');
    
    quoteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const username = this.dataset.username;
            const postId = this.dataset.postId;
            const replyTextarea = document.getElementById('reply-content');
            
            // Получаем текст поста для цитирования
            const postElement = document.getElementById('post-' + postId);
            const postContent = postElement.querySelector('.post-body').innerText.trim();
            
            // Формируем цитату
            const quoteText = `> **${username}** написал(а):\n> ${postContent.replace(/\n/g, '\n> ')}\n\n`;
            
            // Вставляем цитату в textarea
            replyTextarea.value += quoteText;
            
            // Фокусируемся на textarea
            replyTextarea.focus();
            
            // Прокручиваем к форме ответа
            replyTextarea.scrollIntoView({ behavior: 'smooth' });
        });
    });
}

// Инициализация кнопок закладок
function initBookmarkButtons() {
    const bookmarkButtons = document.querySelectorAll('.bookmark-button');
    
    bookmarkButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const topicId = this.dataset.topicId;
            
            fetch('api/bookmark.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ topic_id: topicId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.action === 'added') {
                        this.classList.add('bookmarked');
                        this.setAttribute('title', 'Удалить из закладок');
                        this.querySelector('svg').innerHTML = '<path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" fill="currentColor"></path>';
                        showAlert('Тема добавлена в закладки', 'success');
                    } else {
                        this.classList.remove('bookmarked');
                        this.setAttribute('title', 'Добавить в закладки');
                        this.querySelector('svg').innerHTML = '<path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>';
                        showAlert('Тема удалена из закладок', 'success');
                    }
                } else {
                    // Если пользователь не авторизован, перенаправляем на страницу входа
                    if (data.message === 'Необходимо авторизоваться') {
                        window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                    } else {
                        showAlert(data.message, 'error');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Произошла ошибка при обработке запроса', 'error');
            });
        });
    });
}

// Отображение уведомлений
function showAlert(message, type = 'info') {
    // Создаем элемент уведомления
    const alertElement = document.createElement('div');
    alertElement.className = `alert alert-${type}`;
    alertElement.textContent = message;
    
    // Добавляем уведомление в контейнер
    const alertsContainer = document.getElementById('alerts-container');
    if (!alertsContainer) {
        // Если контейнер не существует, создаем его
        const newContainer = document.createElement('div');
        newContainer.id = 'alerts-container';
        document.body.appendChild(newContainer);
        newContainer.appendChild(alertElement);
    } else {
        alertsContainer.appendChild(alertElement);
    }
    
    // Удаляем уведомление через 3 секунды
    setTimeout(() => {
        alertElement.classList.add('fade-out');
        
        // Полностью удаляем элемент после завершения анимации
        setTimeout(() => {
            alertElement.remove();
        }, 500);
    }, 3000);
}