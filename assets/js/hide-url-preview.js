/**
 * hide-url-preview.js
 * 1. Suppresses native browser bottom-left / bottom-right URL preview (status bubble) on hover
 *    without altering any button or navigation functionalities.
 * 2. Suppresses browser input autocomplete / previously typed input history suggestions
 *    across all input fields and forms.
 */
(function() {
    'use strict';

    var origSetAttr = Element.prototype.setAttribute;
    var origGetAttr = Element.prototype.getAttribute;
    var origHasAttr = Element.prototype.hasAttribute;
    var origRemoveAttr = Element.prototype.removeAttribute;

    var isRightClicking = false;

    // Helper to sanitize an anchor element (hide bottom-left URL preview)
    function sanitizeLink(el) {
        if (!el || el.tagName !== 'A') return;

        // If it currently has an href attribute
        if (origHasAttr.call(el, 'href')) {
            var rawHref = origGetAttr.call(el, 'href');
            if (rawHref) {
                var trimmed = rawHref.trim();
                if (trimmed !== '' && trimmed !== '#' && !trimmed.toLowerCase().startsWith('javascript:')) {
                    origSetAttr.call(el, 'data-href', rawHref);
                }
            }
            // Remove href so browser status bar preview is never shown
            origRemoveAttr.call(el, 'href');
        }

        // Ensure accessibility and cursor styling
        if (origHasAttr.call(el, 'data-href')) {
            if (!origHasAttr.call(el, 'role')) {
                origSetAttr.call(el, 'role', 'button');
            }
            if (!origHasAttr.call(el, 'tabindex')) {
                origSetAttr.call(el, 'tabindex', '0');
            }
            if (!el.style.cursor) {
                el.style.cursor = 'pointer';
            }
        }
    }

    // Process all anchor elements in a root node
    function cleanAllLinks(root) {
        if (!root) return;
        if (root.tagName === 'A') {
            sanitizeLink(root);
        }
        if (root.querySelectorAll) {
            var anchors = root.querySelectorAll('a[href]');
            for (var i = 0; i < anchors.length; i++) {
                sanitizeLink(anchors[i]);
            }
        }
    }

    // Helper to disable input history / autocomplete suggestions
    function sanitizeInputField(inp) {
        if (!inp) return;
        var tag = inp.tagName;
        if (tag === 'FORM') {
            origSetAttr.call(inp, 'autocomplete', 'off');
            return;
        }
        if (tag === 'INPUT' || tag === 'TEXTAREA') {
            var type = (origGetAttr.call(inp, 'type') || 'text').toLowerCase();
            // Don't modify checkboxes, radios, hidden tokens, file inputs, or action buttons
            if (['hidden', 'file', 'checkbox', 'radio', 'submit', 'button', 'reset'].indexOf(type) === -1) {
                origSetAttr.call(inp, 'autocomplete', 'off');
                origSetAttr.call(inp, 'autocorrect', 'off');
                origSetAttr.call(inp, 'autocapitalize', 'off');
                origSetAttr.call(inp, 'spellcheck', 'false');
            }
        }
    }

    // Process all forms and inputs in a root node to remove past input history suggestions
    function disableInputHistory(root) {
        if (!root) return;
        if (root.tagName === 'FORM' || root.tagName === 'INPUT' || root.tagName === 'TEXTAREA') {
            sanitizeInputField(root);
        }
        if (root.querySelectorAll) {
            var forms = root.querySelectorAll('form');
            for (var f = 0; f < forms.length; f++) {
                origSetAttr.call(forms[f], 'autocomplete', 'off');
            }

            var inputs = root.querySelectorAll('input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="button"]):not([type="file"]):not([type="reset"]), textarea');
            for (var i = 0; i < inputs.length; i++) {
                sanitizeInputField(inputs[i]);
            }
        }
    }

    // Enforce autocomplete="off" dynamically on focusin to prevent browser popup before typing
    document.addEventListener('focusin', function(e) {
        var el = e.target;
        if (el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA')) {
            sanitizeInputField(el);
            var parentForm = el.form || el.closest('form');
            if (parentForm) {
                origSetAttr.call(parentForm, 'autocomplete', 'off');
            }
        }
    }, true);

    // Hook Element.prototype.setAttribute so dynamically added href is safely redirected to data-href
    try {
        Element.prototype.setAttribute = function(name, val) {
            if (name && typeof name === 'string' && name.toLowerCase() === 'href' && this.tagName === 'A') {
                if (isRightClicking) {
                    return origSetAttr.call(this, name, val);
                }
                if (val && typeof val === 'string') {
                    var trimmed = val.trim();
                    if (trimmed !== '' && trimmed !== '#' && !trimmed.toLowerCase().startsWith('javascript:')) {
                        origSetAttr.call(this, 'data-href', val);
                        if (!origHasAttr.call(this, 'role')) origSetAttr.call(this, 'role', 'button');
                        if (!origHasAttr.call(this, 'tabindex')) origSetAttr.call(this, 'tabindex', '0');
                        if (!this.style.cursor) this.style.cursor = 'pointer';
                        return;
                    }
                }
                return;
            }
            return origSetAttr.apply(this, arguments);
        };
    } catch (e) {}

    // Hook HTMLAnchorElement.prototype.href property getter/setter
    try {
        var hrefDesc = Object.getOwnPropertyDescriptor(HTMLAnchorElement.prototype, 'href');
        if (hrefDesc && hrefDesc.set && hrefDesc.get) {
            Object.defineProperty(HTMLAnchorElement.prototype, 'href', {
                get: function() {
                    return this.getAttribute('data-href') || hrefDesc.get.call(this);
                },
                set: function(val) {
                    if (isRightClicking) {
                        return hrefDesc.set.call(this, val);
                    }
                    if (val && typeof val === 'string') {
                        var trimmed = val.trim();
                        if (trimmed !== '' && trimmed !== '#' && !trimmed.toLowerCase().startsWith('javascript:')) {
                            origSetAttr.call(this, 'data-href', val);
                            if (!origHasAttr.call(this, 'role')) origSetAttr.call(this, 'role', 'button');
                            if (!origHasAttr.call(this, 'tabindex')) origSetAttr.call(this, 'tabindex', '0');
                            if (!this.style.cursor) this.style.cursor = 'pointer';
                        }
                    }
                },
                configurable: true,
                enumerable: true
            });
        }
    } catch (e) {}

    // Hook Element.prototype.getAttribute so code reading 'href' receives 'data-href'
    try {
        Element.prototype.getAttribute = function(name) {
            if (name && typeof name === 'string' && name.toLowerCase() === 'href' && this.tagName === 'A') {
                if (origHasAttr.call(this, 'href')) {
                    return origGetAttr.call(this, 'href');
                }
                if (origHasAttr.call(this, 'data-href')) {
                    return origGetAttr.call(this, 'data-href');
                }
            }
            return origGetAttr.apply(this, arguments);
        };
    } catch (e) {}

    // Hook Element.prototype.hasAttribute so code checking 'href' gets true if 'data-href' exists
    try {
        Element.prototype.hasAttribute = function(name) {
            if (name && typeof name === 'string' && name.toLowerCase() === 'href' && this.tagName === 'A') {
                return origHasAttr.call(this, 'href') || origHasAttr.call(this, 'data-href');
            }
            return origHasAttr.apply(this, arguments);
        };
    } catch (e) {}

    // Navigation trigger
    function triggerNavigation(el, e) {
        if (!el) return;
        var url = el.getAttribute('data-href');
        if (!url) return;

        // Support download attribute
        if (el.hasAttribute('download')) {
            var temp = document.createElement('a');
            isRightClicking = true;
            temp.href = url;
            temp.download = el.getAttribute('download') || '';
            document.body.appendChild(temp);
            temp.click();
            document.body.removeChild(temp);
            isRightClicking = false;
            return;
        }

        var isNewTab = (e && (e.ctrlKey || e.metaKey || e.button === 1)) || el.getAttribute('target') === '_blank';
        if (isNewTab) {
            window.open(url, '_blank');
        } else {
            window.location.href = url;
        }
    }

    // Handle normal left click via delegation
    document.addEventListener('click', function(e) {
        var link = e.target.closest('a[data-href]');
        if (!link) return;

        // If the event was canceled by an inline or previous onclick handler
        if (e.defaultPrevented) return;

        e.preventDefault();
        triggerNavigation(link, e);
    }, false);

    // Handle middle click (auxclick with button 1)
    document.addEventListener('auxclick', function(e) {
        if (e.button === 1) { // Middle click
            var link = e.target.closest('a[data-href]');
            if (link) {
                e.preventDefault();
                triggerNavigation(link, e);
            }
        }
    }, false);

    // Handle Right Click (mousedown with button 2):
    // Temporarily restore href so native context menu still shows "Open in new tab", "Copy link", etc.
    document.addEventListener('mousedown', function(e) {
        if (e.button === 2) { // Right click
            var link = e.target.closest('a[data-href]');
            if (link && !origHasAttr.call(link, 'href')) {
                isRightClicking = true;
                var url = link.getAttribute('data-href');
                origSetAttr.call(link, 'href', url);
                setTimeout(function() {
                    isRightClicking = false;
                    if (origHasAttr.call(link, 'data-href')) {
                        origRemoveAttr.call(link, 'href');
                    }
                }, 600);
            }
        }
    }, true);

    // Keyboard navigation: Enter or Space on focused element
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            var active = document.activeElement ? document.activeElement.closest('a[data-href]') : null;
            if (active && active.tagName === 'A') {
                e.preventDefault();
                active.click();
            }
        }
    });

    // Continuous MutationObserver for dynamically added elements, href changes, or input elements
    function initObserver() {
        if (!window.MutationObserver) return;
        var obs = new MutationObserver(function(mutations) {
            for (var i = 0; i < mutations.length; i++) {
                var m = mutations[i];
                if (m.type === 'childList') {
                    for (var j = 0; j < m.addedNodes.length; j++) {
                        var node = m.addedNodes[j];
                        if (node.nodeType === 1) {
                            cleanAllLinks(node);
                            disableInputHistory(node);
                        }
                    }
                } else if (m.type === 'attributes') {
                    if (m.attributeName === 'href' && !isRightClicking && m.target && m.target.tagName === 'A' && origHasAttr.call(m.target, 'href')) {
                        sanitizeLink(m.target);
                    }
                }
            }
        });
        obs.observe(document.documentElement, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['href']
        });
    }

    // Run as early as possible
    cleanAllLinks(document.documentElement);
    disableInputHistory(document.documentElement);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            cleanAllLinks(document.body);
            disableInputHistory(document.body);
            initObserver();
        });
    } else {
        cleanAllLinks(document.body);
        disableInputHistory(document.body);
        initObserver();
    }
})();
