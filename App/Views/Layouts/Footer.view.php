    </div>
    <script src="<?= asset('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= asset('assets/js/sweetalert2.all.min.js') ?>"></script>
    <script>
        // Responsive: envuelve toda tabla que no esté ya en un contenedor con
        // scroll horizontal, para que en móvil se puedan ver todas las columnas
        // deslizando en vez de quedar recortadas.
        (function () {
            function wrapTables(scope) {
                (scope || document).querySelectorAll('table').forEach(function (t) {
                    if (!t.closest('.table-responsive')) {
                        var w = document.createElement('div');
                        w.className = 'table-responsive';
                        t.parentNode.insertBefore(w, t);
                        w.appendChild(t);
                    }
                });
            }
            wrapTables(document);
            // Tablas que se cargan por AJAX después (reportes, etc.)
            document.addEventListener('DOMContentLoaded', function () { wrapTables(document); });
            window.wrapResponsiveTables = wrapTables;
        })();
    </script>
</body>
</html>