// Function to check session status
function checkSession() {
    fetch('check_session.php')
        .then(response => response.json())
        .then(data => {
            if (!data.logged_in) {
                // If user was deleted or session expired
                if (data.user_deleted) {
                    alert('Your account has been removed. Please register again.');
                }
                window.location.href = 'index.html';
                return;
            }
        })
        .catch(error => {
            console.error('Session check error:', error);
            window.location.href = 'index.html';
        });
}

// Function to restrict menu access
function restrictMenuAccess() {
    const restrictedPages = ['learn.html', 'profile.html', 'blogs.html', 'faqs.html'];
    const currentPage = window.location.pathname.split('/').pop();

    // If we're on a restricted page, check session
    if (restrictedPages.includes(currentPage)) {
        checkSession();
    }

    // Modify menu items based on session
    fetch('check_session.php')
        .then(response => response.json())
        .then(data => {
            const navLinks = document.querySelector('.nav-links');
            const profileIcon = document.querySelector('.profile-icon-container');

            if (!data.logged_in) {
                // If not logged in, only show Home and About
                if (navLinks) {
                    navLinks.innerHTML = `
                        <li><a href="home.html">Home</a></li>
                        <li><a href="about.html">About Us</a></li>
                    `;
                }
                if (profileIcon) {
                    profileIcon.innerHTML = `
                        <a href="login.html" class="login-btn">
                            <i class="fas fa-sign-in-alt"></i>
                            Login
                        </a>
                    `;
                }
            }
        })
        .catch(error => console.error('Menu access error:', error));
}

// Check session and restrict menu access when page loads
document.addEventListener('DOMContentLoaded', () => {
    restrictMenuAccess();
});

// Check session every 30 seconds
setInterval(checkSession, 30000); 