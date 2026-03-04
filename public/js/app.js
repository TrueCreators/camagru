/**
 * Основной JavaScript приложения
 */

// Хелпер CSRF-токена
const App = {
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',

    // Получение CSRF-токена
    getCsrfToken() {
        return this.csrfToken;
    },

    // Выполнение fetch с CSRF-токеном
    async fetch(url, options = {}) {
        const defaultOptions = {
            headers: {
                'X-CSRF-Token': this.getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        if (options.body && !(options.body instanceof FormData)) {
            defaultOptions.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(options.body);
        }

        const mergedOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...options.headers
            }
        };

        const response = await fetch(url, mergedOptions);
        return response;
    },

    // Показ флеш-сообщения
    showMessage(message, type = 'success') {
        const container = document.createElement('div');
        container.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-opacity duration-300 ${
            type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500'
        } text-white`;
        container.textContent = message;

        document.body.appendChild(container);

        setTimeout(() => {
            container.classList.add('opacity-0');
            setTimeout(() => container.remove(), 300);
        }, 3000);
    },

    // Форматирование "сколько времени назад"
    timeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000);

        if (diff < 60) return 'just now';
        if (diff < 3600) return `${Math.floor(diff / 60)} minute${Math.floor(diff / 60) > 1 ? 's' : ''} ago`;
        if (diff < 86400) return `${Math.floor(diff / 3600)} hour${Math.floor(diff / 3600) > 1 ? 's' : ''} ago`;
        if (diff < 604800) return `${Math.floor(diff / 86400)} day${Math.floor(diff / 86400) > 1 ? 's' : ''} ago`;

        return date.toLocaleDateString();
    },

    // Экранирование HTML
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Переключение мобильного меню
document.addEventListener('DOMContentLoaded', () => {
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');

    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    }
});

// Сделать App глобально доступным
window.App = App;
