/**
 * PartyMinder Mobile Menu
 * Handles responsive mobile menu toggle functionality
 * 
 * @package PartyMinder
 * @subpackage JavaScript
 * @since 1.0.0
 */

class PartyMinderMobileMenu {
    constructor() {
        this.mobileBreakpoint = 768;
        this.isMenuOpen = false;
        this.menuToggle = null;
        this.mobileMenu = null;
        this.menuOverlay = null;
        this.body = document.body;
        
        this.init();
    }

    init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }

    setup() {
        this.createMobileMenuStructure();
        this.bindEvents();
        this.handleResize();
    }

    createMobileMenuStructure() {
        // Find the main navigation bar
        const mainNav = document.querySelector('.pm-main-nav');
        if (!mainNav) {
            return;
        }

        // Find the existing mobile menu modal (server-rendered)
        this.mobileMenu = document.querySelector('.pm-mobile-menu-modal');
        if (!this.mobileMenu) {
            return;
        }

        this.menuOverlay = this.mobileMenu.querySelector('.pm-modal-overlay');

        // Create mobile menu toggle button in top nav
        if (!this.menuToggle) {
            this.menuToggle = document.createElement('button');
            this.menuToggle.className = 'pm-mobile-menu-toggle pm-main-nav-item';
            this.menuToggle.setAttribute('aria-label', 'Toggle navigation menu');
            this.menuToggle.setAttribute('aria-expanded', 'false');
            this.menuToggle.innerHTML = `
                <span class="pm-hamburger-icon">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
            `;
            
            // Append to main navigation
            mainNav.appendChild(this.menuToggle);
        }
    }

    bindEvents() {
        // Toggle button click
        if (this.menuToggle) {
            this.menuToggle.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleMenu();
            });
        }

        // Overlay click to close
        if (this.mobileMenu) {
            this.mobileMenu.addEventListener('click', (e) => {
                if (e.target.classList.contains('pm-modal-overlay') || e.target.classList.contains('pm-mobile-menu-close')) {
                    this.closeMenu();
                }
            });
        }

        // Escape key to close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isMenuOpen) {
                this.closeMenu();
            }
        });

        // Handle window resize
        window.addEventListener('resize', () => this.handleResize());

        // Close menu when clicking menu items
        if (this.mobileMenu) {
            this.mobileMenu.addEventListener('click', (e) => {
                if (e.target.classList.contains('pm-btn')) {
                    this.closeMenu();
                }
            });
        }
    }

    toggleMenu() {
        if (this.isMenuOpen) {
            this.closeMenu();
        } else {
            this.openMenu();
        }
    }

    openMenu() {
        if (!this.isMobileView()) return;
        
        this.isMenuOpen = true;
        
        if (this.mobileMenu) {
            this.mobileMenu.style.display = 'flex';
            this.mobileMenu.setAttribute('aria-hidden', 'false');
        }
        
        if (this.menuToggle) {
            this.menuToggle.setAttribute('aria-expanded', 'true');
            this.menuToggle.classList.add('pm-mobile-menu-toggle-active');
        }

        // Focus management
        if (this.mobileMenu) {
            const firstMenuItem = this.mobileMenu.querySelector('.pm-btn');
            if (firstMenuItem) {
                firstMenuItem.focus();
            }
        }
    }

    closeMenu() {
        this.isMenuOpen = false;
        
        if (this.mobileMenu) {
            this.mobileMenu.style.display = 'none';
            this.mobileMenu.setAttribute('aria-hidden', 'true');
        }
        
        if (this.menuToggle) {
            this.menuToggle.setAttribute('aria-expanded', 'false');
            this.menuToggle.classList.remove('pm-mobile-menu-toggle-active');
        }
    }

    handleResize() {
        // Close menu when switching to desktop view
        if (!this.isMobileView() && this.isMenuOpen) {
            this.closeMenu();
        }
    }

    isMobileView() {
        return window.innerWidth <= this.mobileBreakpoint;
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if we're on a PartyMinder page
    if (document.querySelector('.partyminder-content')) {
        new PartyMinderMobileMenu();
    }
});

// Also initialize immediately if DOM is already loaded
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    if (document.querySelector('.partyminder-content')) {
        new PartyMinderMobileMenu();
    }
}