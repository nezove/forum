// forum.js - общие функции JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Активация выпадающих меню
    initDropdowns();
    
    // Подсветка активного пункта в навигации
    highlightActiveNavItem();
    
    // Показать/скрыть мобильное меню
    initMobileMenu();
    
    // Прокрутка к выделенному сообщению
    scrollToHighlightedPost();
});

// Инициализация выпадающих меню
function initDropdowns() {
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            const dropdown = this.closest('.dropdown');
            dropdown.classList.toggle('show');
            
            // Закрываем другие открытые дропдауны
            document.querySelectorAll('.dropdown.show').forEach(openDropdown => {
                if (openDropdown !== dropdown) {
                    openDropdown.classList.remove('show');
                }
            });
        });
    });
    
    // Закрытие дропдаунов при клике вне них
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown.show').forEach(dropdown => {
                dropdown.classList.remove('show');
            });
        }
    });
}

// Подсветка активного пункта навигации
function highlightActiveNavItem() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.main-nav a');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (currentPath.endsWith(href)) {
            link.classList.add('active');
        }
    });
}

// Инициализация мобильного меню
function initMobileMenu() {
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');
    
    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', function() {
            mobileMenu.classList.toggle('show');
            this.classList.toggle('active');
            
            // Блокировка прокрутки страницы при открытом меню
            if (mobileMenu.classList.contains('show')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });
    }
}

// Прокрутка к выделенному сообщению
function scrollToHighlightedPost() {
    const hash = window.location.hash;
    const highlightedPost = document.querySelector('.highlight-post');
    
    if (hash || highlightedPost) {
        setTimeout(() => {
            const target = hash ? document.querySelector(hash) : highlightedPost;
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Добавляем анимацию подсветки
                target.classList.add('flash-highlight');
                setTimeout(() => {
                    target.classList.remove('flash-highlight');
                }, 2000);
            }
        }, 500);
    }
}

// Обработка уведомлений
function showNotification(message, type = 'info', duration = 3000) {
    const container = document.querySelector('.notifications-container') || createNotificationsContainer();
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <div class="notification-message">${message}</div>
            <button class="notification-close">&times;</button>
        </div>
    `;
    
    container.appendChild(notification);
    
    // Появление уведомления
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Автоматическое скрытие уведомления
    const hideTimeout = setTimeout(() => {
        hideNotification(notification);
    }, duration);
    
    // Обработчик закрытия уведомления
    const closeButton = notification.querySelector('.notification-close');
    closeButton.addEventListener('click', () => {
        clearTimeout(hideTimeout);
        hideNotification(notification);
    });
    
    return notification;
}

// Создание контейнера для уведомлений
function createNotificationsContainer() {
    const container = document.createElement('div');
    container.className = 'notifications-container';
    document.body.appendChild(container);
    return container;
}

// Скрытие уведомления
function hideNotification(notification) {
    notification.classList.remove('show');
    setTimeout(() => {
        notification.remove();
    }, 300);
}

// Утилиты для обработки форм
function serializeForm(form) {
    const formData = new FormData(form);
    const data = {};
    
    for (const [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    return data;
}

// Проверка формы на валидность
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// Обработка клика по ссылке "Наверх"
function initBackToTop() {
    const backToTopButton = document.querySelector('.back-to-top');
    
    if (backToTopButton) {
        // Показать/скрыть кнопку при скролле
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTopButton.classList.add('show');
            } else {
                backToTopButton.classList.remove('show');
            }
        });
        
        // Прокрутка наверх при клике
        backToTopButton.addEventListener('click', e => {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
}