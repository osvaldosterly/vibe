/**
 * WP Compare Engine JavaScript
 * Vanilla JS - No jQuery required
 */

(function() {
    'use strict';

    // Configuration from WordPress
    const config = window.wpCompareData || {};
    const ajaxUrl = config.ajaxUrl || '/wp-admin/admin-ajax.php';
    const nonce = config.nonce || '';
    const compareSlug = config.compareSlug || 'compare';
    const maxItems = config.maxItems || 3;
    const minItems = config.minItems || 2;
    const strings = config.strings || {};

    // State
    let compareList = [];
    let searchModal = null;
    let searchInput = null;
    let searchResults = null;
    let debounceTimer = null;

    /**
     * Initialize the compare engine
     */
    function init() {
        loadCompareList();
        renderCompareBar();
        bindEvents();
        checkComparePage();
    }

    /**
     * Load compare list from localStorage
     */
    function loadCompareList() {
        try {
            const stored = localStorage.getItem('wp_compare_list');
            if (stored) {
                compareList = JSON.parse(stored);
                if (!Array.isArray(compareList)) {
                    compareList = [];
                }
            }
        } catch (e) {
            compareList = [];
        }
    }

    /**
     * Save compare list to localStorage
     */
    function saveCompareList() {
        try {
            localStorage.setItem('wp_compare_list', JSON.stringify(compareList));
            // Also set cookie for PHP access
            document.cookie = 'wp_compare_list=' + encodeURIComponent(JSON.stringify(compareList)) + ';path=/;max-age=' + (30 * 24 * 60 * 60);
        } catch (e) {
            console.error('Failed to save compare list:', e);
        }
    }

    /**
     * Render the floating compare bar
     */
    function renderCompareBar() {
        // Remove existing bar
        const existingBar = document.querySelector('.wp-compare-bar');
        if (existingBar) {
            existingBar.remove();
        }

        if (compareList.length === 0) {
            return;
        }

        // Fetch post data for selected items
        fetchCompareItems().then(items => {
            if (items.length === 0) {
                return;
            }

            const bar = createCompareBar(items);
            document.body.appendChild(bar);

            // Show bar after short delay
            setTimeout(() => {
                bar.classList.add('show');
            }, 100);
        });
    }

    /**
     * Fetch compare items data
     */
    function fetchCompareItems() {
        return Promise.all(
            compareList.map(slug => {
                return new Promise((resolve) => {
                    // Try to find in DOM first (for performance)
                    const cached = document.querySelector('[data-compare-slug="' + slug + '"]');
                    if (cached) {
                        resolve({
                            slug: slug,
                            title: cached.getAttribute('data-title') || slug,
                            thumbnail: cached.getAttribute('data-thumbnail') || '',
                            permalink: cached.getAttribute('data-permalink') || '#'
                        });
                    } else {
                        // Fetch from server
                        fetch(ajaxUrl + '?action=wp_compare_get_item&slug=' + encodeURIComponent(slug) + '&nonce=' + nonce)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    resolve(data.data);
                                } else {
                                    resolve(null);
                                }
                            })
                            .catch(() => resolve(null));
                    }
                });
            })
        ).then(items => items.filter(item => item !== null));
    }

    /**
     * Create compare bar element
     */
    function createCompareBar(items) {
        const bar = document.createElement('div');
        bar.className = 'wp-compare-bar wp-compare-sticky';
        bar.setAttribute('data-min-items', minItems);
        bar.setAttribute('data-max-items', maxItems);

        const container = document.createElement('div');
        container.className = 'wp-compare-bar-container';

        // Items section
        const itemsDiv = document.createElement('div');
        itemsDiv.className = 'wp-compare-bar-items';

        items.forEach(item => {
            const itemDiv = document.createElement('div');
            itemDiv.className = 'wp-compare-bar-item';
            itemDiv.setAttribute('data-slug', item.slug);

            const thumbDiv = document.createElement('div');
            thumbDiv.className = 'wp-compare-bar-item-thumbnail';
            
            if (item.thumbnail) {
                thumbDiv.innerHTML = '<img src="' + item.thumbnail + '" alt="">';
            } else {
                thumbDiv.innerHTML = '<span class="dashicons dashicons-format-image"></span>';
            }

            const titleSpan = document.createElement('span');
            titleSpan.className = 'wp-compare-bar-item-title';
            titleSpan.textContent = item.title;

            const removeBtn = document.createElement('button');
            removeBtn.className = 'wp-compare-bar-remove';
            removeBtn.setAttribute('data-slug', item.slug);
            removeBtn.setAttribute('aria-label', (strings.remove || 'Remove') + ' ' + item.title);
            removeBtn.innerHTML = '<span class="dashicons dashicons-no-alt"></span>';

            itemDiv.appendChild(thumbDiv);
            itemDiv.appendChild(titleSpan);
            itemDiv.appendChild(removeBtn);
            itemsDiv.appendChild(itemDiv);
        });

        // Actions section
        const actionsDiv = document.createElement('div');
        actionsDiv.className = 'wp-compare-bar-actions';

        const countSpan = document.createElement('span');
        countSpan.className = 'wp-compare-count';
        countSpan.textContent = items.length + ' ' + (items.length === 1 ? (strings.item || 'item') : (strings.items || 'items')) + ' ' + (strings.selected || 'selected');

        const compareBtn = document.createElement('a');
        compareBtn.className = 'wp-compare-btn wp-compare-btn-primary';
        
        if (items.length >= minItems) {
            const slugs = compareList.join('-vs-');
            compareBtn.href = '/' + compareSlug + '/' + slugs;
            compareBtn.textContent = strings.compareNow || 'Compare Now';
        } else {
            compareBtn.style.display = 'none';
        }

        const clearBtn = document.createElement('button');
        clearBtn.className = 'wp-compare-btn wp-compare-btn-secondary wp-compare-clear-all';
        clearBtn.textContent = strings.clearAll || 'Clear All';

        actionsDiv.appendChild(countSpan);
        actionsDiv.appendChild(compareBtn);
        actionsDiv.appendChild(clearBtn);

        container.appendChild(itemsDiv);
        container.appendChild(actionsDiv);
        bar.appendChild(container);

        return bar;
    }

    /**
     * Bind event listeners
     */
    function bindEvents() {
        // Delegated events for compare bar
        document.addEventListener('click', function(e) {
            const target = e.target.closest('.wp-compare-bar-remove');
            if (target) {
                e.preventDefault();
                const slug = target.getAttribute('data-slug');
                removeFromCompare(slug);
            }

            const clearBtn = e.target.closest('.wp-compare-clear-all');
            if (clearBtn) {
                e.preventDefault();
                clearAll();
            }

            const compareCheckbox = e.target.closest('.wp-compare-checkbox');
            if (compareCheckbox) {
                e.preventDefault();
                toggleCompare(compareCheckbox);
            }

            const openSearchBtn = e.target.closest('.wp-compare-open-search');
            if (openSearchBtn) {
                e.preventDefault();
                openSearchModal();
            }
        });

        // Search modal events
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && searchModal) {
                closeSearchModal();
            }
        });
    }

    /**
     * Toggle compare status for an item
     */
    function toggleCompare(checkbox) {
        const slug = checkbox.getAttribute('data-slug');
        const isChecked = checkbox.classList.contains('checked');

        if (isChecked) {
            removeFromCompare(slug);
        } else {
            addToCompare(slug);
        }
    }

    /**
     * Add item to compare list
     */
    function addToCompare(slug) {
        if (compareList.length >= maxItems) {
            alert(strings.maxReached || 'Maximum ' + maxItems + ' items allowed');
            return;
        }

        if (compareList.includes(slug)) {
            return;
        }

        compareList.push(slug);
        saveCompareList();
        updateCompareButtons();
        renderCompareBar();

        // AJAX sync
        syncToServer('wp_compare_add', { slug: slug });
    }

    /**
     * Remove item from compare list
     */
    function removeFromCompare(slug) {
        compareList = compareList.filter(s => s !== slug);
        saveCompareList();
        updateCompareButtons();
        renderCompareBar();

        // AJAX sync
        syncToServer('wp_compare_remove', { slug: slug });
    }

    /**
     * Clear all items
     */
    function clearAll() {
        compareList = [];
        saveCompareList();
        updateCompareButtons();
        renderCompareBar();

        // AJAX sync
        syncToServer('wp_compare_clear', {});
    }

    /**
     * Update compare checkbox buttons on page
     */
    function updateCompareButtons() {
        document.querySelectorAll('.wp-compare-checkbox').forEach(btn => {
            const slug = btn.getAttribute('data-slug');
            const isChecked = compareList.includes(slug);
            
            btn.classList.toggle('checked', isChecked);
            
            const input = btn.querySelector('input[type="checkbox"]');
            if (input) {
                input.checked = isChecked;
            }

            const label = btn.querySelector('.wp-compare-checkbox-label');
            if (label) {
                label.textContent = isChecked ? (strings.compared || 'Compared') : (strings.compare || 'Compare');
            }
        });
    }

    /**
     * Sync to server (optional, for cookie fallback)
     */
    function syncToServer(action, data) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('nonce', nonce);
        
        Object.keys(data).forEach(key => {
            formData.append(key, data[key]);
        });

        fetch(ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        }).catch(console.error);
    }

    /**
     * Open search modal
     */
    function openSearchModal() {
        if (searchModal) {
            searchModal.setAttribute('aria-hidden', 'false');
            searchInput.focus();
            return;
        }

        // Create modal if it doesn't exist
        const modalHtml = document.getElementById('wp-compare-search-modal');
        if (modalHtml) {
            searchModal = modalHtml;
            searchInput = searchModal.querySelector('.wp-compare-search-input');
            searchResults = searchModal.querySelector('.wp-compare-search-results');
            
            searchModal.setAttribute('aria-hidden', 'false');
            
            bindSearchEvents();
            searchInput.focus();
        }
    }

    /**
     * Close search modal
     */
    function closeSearchModal() {
        if (searchModal) {
            searchModal.setAttribute('aria-hidden', 'true');
        }
    }

    /**
     * Bind search modal events
     */
    function bindSearchEvents() {
        if (!searchInput || !searchResults) return;

        // Close button
        const closeBtn = searchModal.querySelector('.wp-compare-modal-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', closeSearchModal);
        }

        // Overlay click
        const overlay = searchModal.querySelector('.wp-compare-modal-overlay');
        if (overlay) {
            overlay.addEventListener('click', closeSearchModal);
        }

        // Search input
        searchInput.addEventListener('input', function(e) {
            clearTimeout(debounceTimer);
            const query = e.target.value.trim();

            if (query.length < 2) {
                searchResults.innerHTML = '<p class="wp-compare-search-hint">' + (strings.typeToSearch || 'Type at least 2 characters...') + '</p>';
                return;
            }

            debounceTimer = setTimeout(() => {
                performSearch(query);
            }, 300);
        });

        // Keyboard navigation
        searchResults.addEventListener('keydown', function(e) {
            const selected = searchResults.querySelector('.wp-compare-search-result-item.selected');
            const items = searchResults.querySelectorAll('.wp-compare-search-result-item');

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (selected) {
                    const next = selected.nextElementSibling;
                    if (next) {
                        selected.classList.remove('selected');
                        next.classList.add('selected');
                        next.focus();
                    }
                } else if (items.length > 0) {
                    items[0].classList.add('selected');
                    items[0].focus();
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (selected && selected.previousElementSibling) {
                    selected.classList.remove('selected');
                    selected.previousElementSibling.classList.add('selected');
                    selected.previousElementSibling.focus();
                }
            } else if (e.key === 'Enter' && selected) {
                e.preventDefault();
                selected.click();
            }
        });
    }

    /**
     * Perform AJAX search
     */
    function performSearch(query) {
        if (!searchResults) return;

        searchResults.innerHTML = '<span class="wp-compare-search-spinner dashicons dashicons-admin-spin"></span>';

        const formData = new FormData();
        formData.append('action', 'wp_compare_search');
        formData.append('nonce', nonce);
        formData.append('s', query);

        fetch(ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                renderSearchResults(data.data);
            } else {
                searchResults.innerHTML = '<p class="wp-compare-search-hint">' + (strings.noResults || 'No results found') + '</p>';
            }
        })
        .catch(() => {
            searchResults.innerHTML = '<p class="wp-compare-search-hint">' + (strings.searchError || 'Search error') + '</p>';
        });
    }

    /**
     * Render search results
     */
    function renderSearchResults(results) {
        if (!searchResults) return;

        let html = '';
        
        results.forEach(item => {
            const isSelected = compareList.includes(item.slug);
            const isDisabled = !isSelected && compareList.length >= maxItems;

            html += '<div class="wp-compare-search-result-item" ' +
                    'role="option" ' +
                    'tabindex="0" ' +
                    'data-slug="' + item.slug + '" ' +
                    'data-title="' + item.title + '" ' +
                    'data-permalink="' + item.permalink + '" ' +
                    'data-thumbnail="' + (item.thumbnail || '') + '">';
            
            if (item.thumbnail) {
                html += '<img src="' + item.thumbnail + '" alt="">';
            } else {
                html += '<span class="dashicons dashicons-format-image"></span>';
            }

            html += '<div class="wp-compare-search-result-info">';
            html += '<div class="wp-compare-search-result-title">' + escapeHtml(item.title) + '</div>';
            html += '<div class="wp-compare-search-result-type">' + escapeHtml(item.post_type || '') + '</div>';
            html += '</div>';

            if (isSelected) {
                html += '<span class="wp-compare-status compare-yes">✔ ' + (strings.compared || 'Compared') + '</span>';
            } else if (isDisabled) {
                html += '<span class="wp-compare-status">' + (strings.maxReached || 'Max reached') + '</span>';
            } else {
                html += '<span class="wp-compare-status">+ ' + (strings.add || 'Add') + '</span>';
            }

            html += '</div>';
        });

        searchResults.innerHTML = html;

        // Bind click events
        searchResults.querySelectorAll('.wp-compare-search-result-item').forEach(item => {
            item.addEventListener('click', function() {
                const slug = this.getAttribute('data-slug');
                
                if (compareList.includes(slug)) {
                    removeFromCompare(slug);
                } else if (compareList.length < maxItems) {
                    addToCompare(slug);
                    closeSearchModal();
                }
            });
        });
    }

    /**
     * Check if we're on a compare page
     */
    function checkComparePage() {
        const path = window.location.pathname;
        const comparePattern = new RegExp('/' + compareSlug + '/(.+)');
        const match = path.match(comparePattern);

        if (match && match[1]) {
            // We're on a compare page
            const slugs = match[1].split('-vs-');
            compareList = slugs;
            saveCompareList();
        }
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Register compare checkboxes on archive pages
     */
    function registerArchiveCheckboxes() {
        // This should be called by themes to add compare checkboxes to post cards
        window.wpCompareRegisterItem = function(slug, title, thumbnail, permalink) {
            const existing = document.querySelector('[data-compare-slug="' + slug + '"]');
            if (!existing) {
                const wrapper = document.createElement('span');
                wrapper.setAttribute('data-compare-slug', slug);
                wrapper.setAttribute('data-title', title);
                wrapper.setAttribute('data-thumbnail', thumbnail || '');
                wrapper.setAttribute('data-permalink', permalink || '#');
                wrapper.style.display = 'none'; // Hidden cache element
                document.body.appendChild(wrapper);
            }
        };
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            registerArchiveCheckboxes();
            init();
        });
    } else {
        registerArchiveCheckboxes();
        init();
    }

    // Expose API for external use
    window.wpCompare = {
        add: addToCompare,
        remove: removeFromCompare,
        clear: clearAll,
        getList: function() { return [...compareList]; },
        openSearch: openSearchModal,
        refresh: renderCompareBar
    };

})();
