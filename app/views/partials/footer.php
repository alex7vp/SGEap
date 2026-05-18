            </div>
        </section>
    </main>
    <?php
    $appJsPath = BASE_PATH . '/public/assets/js/app.js';
    $appJsVersion = is_file($appJsPath) ? (string) filemtime($appJsPath) : '1';
    ?>
    <script src="<?= htmlspecialchars(asset('js/vendor/chart.umd.min.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="<?= htmlspecialchars(asset('js/app.js') . '?v=' . $appJsVersion, ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
