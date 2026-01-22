(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        const createStoryBtn = document.getElementById('create-story-btn');
        const createStoryForm = document.getElementById('create-story-form');
        const cancelStoryBtn = document.getElementById('cancel-story-btn');
        const storyCreationForm = document.getElementById('story-creation-form');
        
        if (createStoryBtn && createStoryForm) {
            createStoryBtn.addEventListener('click', function() {
                const isHidden = createStoryForm.style.display === 'none' || !createStoryForm.style.display;
                createStoryForm.style.display = isHidden ? 'block' : 'none';
                createStoryBtn.classList.toggle('active');
            });
        }
        
        if (cancelStoryBtn && createStoryForm && storyCreationForm) {
            cancelStoryBtn.addEventListener('click', function() {
                createStoryForm.style.display = 'none';
                createStoryBtn.classList.remove('active');
                storyCreationForm.reset();
            });
        }
        
        if (storyCreationForm) {
            storyCreationForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const titleInput = document.getElementById('story-title');
                const passwordInput = document.getElementById('story-password');
                const submitBtn = storyCreationForm.querySelector('button[type="submit"]');
                
                const title = titleInput ? titleInput.value.trim() : '';
                const password = passwordInput ? passwordInput.value : '';
                
                if (!title) {
                    alert('Please enter a story title.');
                    return;
                }
                
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Creating...';
                }
                
                const formData = new FormData();
                formData.append('action', 'their_story_create_story');
                formData.append('title', title);
                formData.append('password', password);
                formData.append('nonce', theirStoryAdmin.nonce);
                
                fetch(theirStoryAdmin.ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.data.message || 'Error creating story. Please try again.');
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Create Story';
                        }
                    }
                })
                .catch(function() {
                    alert('An error occurred. Please try again.');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Create Story';
                    }
                });
            });
        }
        
        const copyLinkBtns = document.querySelectorAll('.copy-link-btn');
        copyLinkBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const link = this.dataset.link;
                const fullUrl = window.location.origin + '/' + link;
                
                const tempInput = document.createElement('input');
                document.body.appendChild(tempInput);
                tempInput.value = fullUrl;
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                
                const originalText = this.textContent;
                this.textContent = 'Copied!';
                
                const self = this;
                setTimeout(function() {
                    self.textContent = originalText;
                }, 2000);
            });
        });
    });
    
})();

