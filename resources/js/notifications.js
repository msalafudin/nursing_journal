/**
 * Notification System
 * Handles displaying toast notifications to users
 */

export class NotificationManager {
    constructor() {
        this.container = document.getElementById('notification-container');
    }

    /**
     * Show a notification
     * @param {string} message - The notification message
     * @param {string} type - The notification type (success, error, warning, info)
     * @param {number} duration - Duration in milliseconds (0 = no auto-dismiss)
     */
    show(message, type = 'info', duration = 3000) {
        const notification = document.createElement('div');
        notification.className = `notification ${type} rounded-lg shadow-lg p-4 max-w-sm animate-fade-in`;
        notification.setAttribute('role', 'alert');

        // Determine icon based on type
        let icon = '';
        switch (type) {
            case 'success':
                icon = `<svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>`;
                break;
            case 'error':
                icon = `<svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>`;
                break;
            case 'warning':
                icon = `<svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>`;
                break;
            default:
                icon = `<svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zm-11-1a1 1 0 11-2 0 1 1 0 012 0zm3 0a1 1 0 11-2 0 1 1 0 012 0zm3 0a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd" />
                </svg>`;
        }

        notification.innerHTML = `
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    ${icon}
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium">
                        ${message}
                    </p>
                </div>
                <button type="button" class="ml-3 inline-flex text-gray-400 hover:text-gray-500 focus:outline-none" onclick="this.closest('[role=alert]').remove()">
                    <span class="sr-only">Close</span>
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        `;

        // Apply styling based on type
        if (type === 'success') {
            notification.classList.add('bg-green-50', 'border', 'border-green-200');
        } else if (type === 'error') {
            notification.classList.add('bg-red-50', 'border', 'border-red-200');
        } else if (type === 'warning') {
            notification.classList.add('bg-yellow-50', 'border', 'border-yellow-200');
        } else {
            notification.classList.add('bg-blue-50', 'border', 'border-blue-200');
        }

        this.container.appendChild(notification);

        // Auto-dismiss if duration is set
        if (duration > 0) {
            setTimeout(() => {
                notification.remove();
            }, duration);
        }

        return notification;
    }

    success(message, duration = 3000) {
        return this.show(message, 'success', duration);
    }

    error(message, duration = 3000) {
        return this.show(message, 'error', duration);
    }

    warning(message, duration = 3000) {
        return this.show(message, 'warning', duration);
    }

    info(message, duration = 3000) {
        return this.show(message, 'info', duration);
    }
}

// Initialize notification manager globally
window.notificationManager = new NotificationManager();

// Display notifications from session flash data
document.addEventListener('DOMContentLoaded', function() {
    // Check for flash messages in the page
    const successMessages = document.querySelectorAll('[data-notification-success]');
    const errorMessages = document.querySelectorAll('[data-notification-error]');
    const warningMessages = document.querySelectorAll('[data-notification-warning]');
    const infoMessages = document.querySelectorAll('[data-notification-info]');

    successMessages.forEach(el => {
        window.notificationManager.success(el.getAttribute('data-notification-success'));
    });

    errorMessages.forEach(el => {
        window.notificationManager.error(el.getAttribute('data-notification-error'));
    });

    warningMessages.forEach(el => {
        window.notificationManager.warning(el.getAttribute('data-notification-warning'));
    });

    infoMessages.forEach(el => {
        window.notificationManager.info(el.getAttribute('data-notification-info'));
    });
});
