// Function to handle errors and redirects
function handleResponse(response) {
    if (response.status === 401) {
        window.location.href = 'login.html';
        throw new Error('Please log in to continue');
    }
    return response.json();
}

// Function to update navigation profile picture
function updateNavProfilePic(profilePicPath) {
    const profileContainers = document.querySelectorAll('.profile-icon-container a[href="profile.html"]');
    
    profileContainers.forEach(container => {
        // Remove loading and loaded classes
        container.classList.remove('loading', 'loaded');
        
        // Clear existing content
        container.innerHTML = '';
        
        // Create default icon
        const icon = document.createElement('i');
        icon.className = 'fas fa-user profile-icon';
        container.appendChild(icon);
        
        if (profilePicPath) {
            // Create and add profile image
            const img = document.createElement('img');
            img.src = profilePicPath;
            img.alt = 'Profile';
            img.classList.add('nav-profile-pic');
            
            // Add loading state
            container.classList.add('loading');
            
            img.onload = function() {
                container.classList.remove('loading');
                container.classList.add('loaded');
                icon.style.opacity = '0';
            };
            
            img.onerror = function() {
                console.error('Failed to load profile picture:', profilePicPath);
                container.classList.remove('loading');
                icon.style.opacity = '1';
                img.remove();
            };
            
            container.appendChild(img);
        }
    });

    // Update profile page image if it exists
    const profilePageImage = document.getElementById('profile-image');
    if (profilePageImage && profilePicPath) {
        profilePageImage.src = profilePicPath;
    }
}

// Function to load user profile picture
function loadNavProfilePic() {
    fetch('check_session.php', {
        method: 'GET',
        credentials: 'include'
    })
    .then(handleResponse)
    .then(data => {
        if (data.success && data.data && data.data.profile_picture) {
            updateNavProfilePic(data.data.profile_picture);
        } else {
            console.warn('No profile picture available in user data');
            updateNavProfilePic(null);
        }
    })
    .catch(error => {
        console.error('Error loading profile picture:', error);
        updateNavProfilePic(null);
    });
}

// Load profile picture when DOM is ready
document.addEventListener('DOMContentLoaded', loadNavProfilePic);

// Reload profile picture every 5 minutes to keep it updated
setInterval(loadNavProfilePic, 300000); 