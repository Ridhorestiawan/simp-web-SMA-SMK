const SIMP = {

    init() {
        this.initSidebar();
        this.initFileInputs();
        this.initAutoDismiss();
        this.initCurrentDate();
        this.initSmoothScroll();
    },

    /* Sidebar */
    initSidebar() {
        window.toggleMobileSidebar = function () {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (!sidebar) return;
            sidebar.classList.toggle('hidden');
            sidebar.classList.toggle('flex');
            if (overlay) overlay.classList.toggle('hidden');
            document.body.classList.toggle('overflow-hidden');
        };
    },

    /* Modal */
    openModal(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.remove('hidden');
        el.classList.add('animate-scale-in');
        document.body.classList.add('overflow-hidden');
    },

    closeModal(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.add('hidden');
        el.classList.remove('animate-scale-in');
        document.body.classList.remove('overflow-hidden');
    },

    /* File Input Label */
    initFileInputs() {
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function () {
                const target = this.dataset.labelTarget || this.closest('label')?.querySelector('span');
                if (target) {
                    target.textContent = this.files[0]?.name || target.dataset.placeholder || 'Pilih file...';
                }
            });
        });
    },

    /* Auto-dismiss notifications */
    initAutoDismiss() {
        document.querySelectorAll('[data-auto-dismiss]').forEach(el => {
            const ms = parseInt(el.dataset.autoDismiss) || 5000;
            setTimeout(() => {
                el.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                el.style.opacity = '0';
                el.style.transform = 'translateY(-8px)';
                setTimeout(() => el.remove(), 300);
            }, ms);
        });
    },

    /* Current Date */
    initCurrentDate() {
        const el = document.getElementById('current-date');
        if (!el) return;
        const today = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        el.innerText = today.toLocaleDateString('id-ID', options);
    },

    /* Smooth scroll */
    initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href === '#') return;
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    },

    /* Format date */
    formatDate(dateStr, format = 'long') {
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return dateStr;
        if (format === 'short') {
            return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        }
        return date.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    },

    /* Format time remaining */
    timeRemaining(dateStr) {
        const now = new Date().getTime();
        const target = new Date(dateStr).getTime();
        const diff = target - now;

        if (diff <= 0) return 'Terlewat';

        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));

        if (days > 0) return `${days} hari ${hours} jam`;
        if (hours > 0) return `${hours} jam`;
        return 'Kurang dari 1 jam';
    },

    /* Confirm dialog wrapper */
    confirm(message = 'Apakah Anda yakin?') {
        return window.confirm(message);
    },

    /* Fetch wrapper */
    async fetch(url, options = {}) {
        try {
            const res = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', ...options.headers },
                ...options
            });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return await res.json();
        } catch (err) {
            console.error('SIMP.fetch error:', err);
            throw err;
        }
    },

    /* Toast notification */
    toast(message, type = 'success', duration = 4000) {
        const container = document.getElementById('toast-container');
        if (!container) {
            const c = document.createElement('div');
            c.id = 'toast-container';
            c.style.cssText = 'position:fixed;top:80px;right:16px;z-index:9999;display:flex;flex-direction:column;gap:8px;max-width:400px;';
            document.body.appendChild(c);
        }

        const toast = document.createElement('div');
        const icons = { success: 'check_circle', error: 'error', warning: 'warning', info: 'info' };
        const colors = {
            success: 'bg-green-50 border-green-300 text-green-800',
            error: 'bg-red-50 border-red-300 text-red-800',
            warning: 'bg-amber-50 border-amber-300 text-amber-800',
            info: 'bg-blue-50 border-blue-300 text-blue-800'
        };

        toast.className = `flex items-center gap-2 px-4 py-3 rounded-lg border animate-fade-in shadow-lg ${colors[type] || colors.info}`;
        toast.innerHTML = `<span class="material-symbols-outlined text-lg">${icons[type] || icons.info}</span><p class="font-body-md">${message}</p>`;

        const containerEl = document.getElementById('toast-container');
        containerEl.appendChild(toast);

        setTimeout(() => {
            toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(16px)';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
};

document.addEventListener('DOMContentLoaded', () => SIMP.init());
