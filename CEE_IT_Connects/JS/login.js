document.addEventListener('DOMContentLoaded', () => {
    
    // --- LOADING SCREEN LOGIC ---
    const loader = document.getElementById('loading-screen');
    
    if(loader) {
        // Simulate loading time (e.g., 2 seconds)
        setTimeout(() => {
            loader.style.opacity = '0';
            setTimeout(() => {
                loader.style.display = 'none';
            }, 500); // Wait for fade out
        }, 1000);
    }

    // --- PASSWORD VISIBILITY TOGGLE ---
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');
    const passwordInput = document.getElementById('password');

    if(togglePasswordIcon && passwordInput) {
        togglePasswordIcon.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            } else {
                passwordInput.type = 'password';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            }
        });
    }
});