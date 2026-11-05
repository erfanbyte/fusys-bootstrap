        </main>
    <footer>
        <p>&copy; <?php echo date('Y'); ?> FUSYS - Fuel Usage System</p>
    </footer>
    <script src="/fusys/assets/js/script.js"></script>
    <?php if (isset($_SESSION['user_id'])): ?>
   <script src="/fusys/assets/js/timeoutwarning.js"></script>
   <?php endif; ?>
</body>
</html> 