        </div>
    </main>
    
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            sidebar.classList.toggle('open');
        }
        
        // 모바일에서 사이드바 외부 클릭 시 닫기
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('adminSidebar');
            const toggleBtn = document.querySelector('.mobile-menu-toggle');
            
            if (window.innerWidth <= 768 && sidebar.classList.contains('open')) {
                if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
    </script>
</body>
</html>








