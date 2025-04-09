<?php
// footer.php
?>
        </div>
    </main>
    
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <a href="index.php"><?php echo SITE_NAME; ?></a>
                </div>
                
                <div class="footer-links">
                    <ul>
                        <li><a href="index.php">Главная</a></li>
                        <li><a href="categories.php">Категории</a></li>
                        <li><a href="terms.php">Правила</a></li>
                        <li><a href="privacy.php">Конфиденциальность</a></li>
                        <li><a href="contacts.php">Контакты</a></li>
                    </ul>
                </div>
                
                <div class="footer-copyright">
                    &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Все права защищены.
                </div>
            </div>
        </div>
    </footer>
</body>
</html>