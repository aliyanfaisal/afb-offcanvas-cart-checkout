<?php
if (!defined('ABSPATH')) { exit; }

// Inject a DOM observer in the footer to handle ajax add-to-cart buttons
add_action('wp_footer', function () {
    // Avoid printing in admin or if no body
    if (is_admin()) { return; }

    $text = __('Out of stock', 'afb-offcanvas');
    ?>
    <script>
    (function() {
        var OUT_OF_STOCK_TEXT = <?php echo wp_json_encode($text); ?>;
        var SELECTOR = '.ajax_add_to_cart.wpr-atc-not-clickable';

        function process(el) {
            if (!el || !el.classList) return;
            // Remove the non-clickable class
            el.classList.remove('wpr-atc-not-clickable');
            // Update inner span text
            var span = el.querySelector('span');
            if (span) { span.textContent = OUT_OF_STOCK_TEXT; }
        }

        function initialScan() {
            var nodes = document.querySelectorAll(SELECTOR);
            nodes.forEach(process);
        }

        // Observe additions and attribute updates that affect class list
        var observer = new MutationObserver(function(mutations) {
            for (var i = 0; i < mutations.length; i++) {
                var m = mutations[i];
                if (m.type === 'childList') {
                    m.addedNodes.forEach(function(node) {
                        if (!node || node.nodeType !== 1) return; // ELEMENT_NODE
                        if (node.matches && node.matches(SELECTOR)) {
                            process(node);
                        } else if (node.querySelectorAll) {
                            node.querySelectorAll(SELECTOR).forEach(process);
                        }
                    });
                } else if (m.type === 'attributes' && m.target && m.attributeName === 'class') {
                    var el = m.target;
                    if (el.matches && el.matches(SELECTOR)) {
                        process(el);
                    }
                }
            }
        });

        // Run once on load
        initialScan();
        // Start observing the document
        if (document.body) {
            observer.observe(document.body, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['class']
            });
        }
    })();
    </script>
    <?php
});