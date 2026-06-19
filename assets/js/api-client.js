/**
 * Lume API Client
 * Centralized API logic for headless frontend
 */

const LumeAPI = {
    baseUrl: '/api/v1',
    settings: null,
    cssInjected: false,

    async fetchTheme() {
        if (this.settings) return this.settings;
        try {
            const res = await fetch(`${this.baseUrl}/theme`);
            const json = await res.json();
            if (json.status === 200) {
                this.settings = json.data.settings;
                
                // Inject custom CSS
                if (json.data.css && !this.cssInjected) {
                    const style = document.createElement('style');
                    style.innerHTML = json.data.css;
                    document.head.appendChild(style);
                    this.cssInjected = true;
                }

                // Apply CSS variables dynamically
                const s = this.settings;
                const root = document.documentElement;
                if (s.theme_color_bg) {
                    root.style.setProperty('--bg', s.theme_color_bg);
                    root.style.setProperty('--bg-card', s.theme_color_bg_card || s.theme_color_bg);
                }
                if (s.theme_color_cream) root.style.setProperty('--cream', s.theme_color_cream);
                if (s.theme_color_gold) root.style.setProperty('--gold', s.theme_color_gold);
                if (s.theme_color_accent) root.style.setProperty('--terracotta', s.theme_color_accent);
                if (s.theme_color_muted) root.style.setProperty('--muted', s.theme_color_muted);
                if (s.theme_font_heading) root.style.setProperty('--font-serif', `"${s.theme_font_heading}", serif`);
                if (s.theme_font_body) root.style.setProperty('--font-sans', `"${s.theme_font_body}", sans-serif`);

                // Update text elements marked with data-setting
                document.querySelectorAll('[data-setting]').forEach(el => {
                    const key = el.getAttribute('data-setting');
                    if (s[key]) {
                        if (el.tagName === 'IMG') {
                            el.src = '/' + s[key];
                        } else {
                            el.textContent = s[key];
                        }
                    }
                });
                
                return json.data;
            }
        } catch (e) {
            console.error('Failed to fetch theme:', e);
        }
        return null;
    },

    async getProducts() {
        try {
            const res = await fetch(`${this.baseUrl}/products`);
            const json = await res.json();
            return json.status === 200 ? json.data : [];
        } catch (e) {
            console.error(e);
            return [];
        }
    },

    async addToCart(productId, qty = 1) {
        try {
            const res = await fetch(`${this.baseUrl}/cart`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'add', product_id: productId, quantity: qty })
            });
            const json = await res.json();
            if (json.status === 200) {
                // Update cart badge
                const badge = document.getElementById('cart-count-badge');
                if (badge) {
                    badge.textContent = json.cart_count;
                    badge.style.display = json.cart_count > 0 ? 'inline-block' : 'none';
                }
                alert('Added to cart!');
            } else {
                alert(json.error || 'Failed to add to cart');
            }
        } catch (e) {
            alert('Network error');
        }
    }
};

// Initialize theme on load
document.addEventListener('DOMContentLoaded', () => {
    LumeAPI.fetchTheme();
});
