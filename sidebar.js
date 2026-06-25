document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.nav-group-title').forEach((button) => {
        button.addEventListener('click', () => {
            const group = button.closest('.nav-group');
            if (!group) {
                return;
            }
            group.classList.toggle('open');
        });
    });

    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });
    }
});
