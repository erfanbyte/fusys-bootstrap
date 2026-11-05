// Document ready function
document.addEventListener('DOMContentLoaded', function() {
    // Confirm actions for delete buttons
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this?')) {
                e.preventDefault();
            }
        });
    });
    
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = 'red';
                } else {
                    field.style.borderColor = '';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });
    
    // Password confirmation check
    const passwordForms = document.querySelectorAll('form[action*="password"]');
    passwordForms.forEach(form => {
        const password = form.querySelector('#password');
        const confirmPassword = form.querySelector('#confirm_password');
        
        if (password && confirmPassword) {
            form.addEventListener('submit', function(e) {
                if (password.value !== confirmPassword.value) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                    confirmPassword.focus();
                }
            });
        }
    });
    
    // Preview profile picture before upload
    const profilePicInput = document.getElementById('profile_pic');
    if (profilePicInput) {
        profilePicInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const profilePic = document.querySelector('.profile-pic') || 
                                      document.querySelector('.profile-pic-placeholder');
                    if (profilePic) {
                        if (profilePic.classList.contains('profile-pic-placeholder')) {
                            profilePic.classList.remove('profile-pic-placeholder');
                            profilePic.classList.add('profile-pic');
                            profilePic.src = event.target.result;
                        } else {
                            profilePic.src = event.target.result;
                        }
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
});