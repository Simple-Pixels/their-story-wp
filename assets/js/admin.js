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
                
                const tempInput = document.createElement('input');
                document.body.appendChild(tempInput);
                tempInput.value = link;
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
        
        const passwordBtns = document.querySelectorAll('.their-story-password-btn');
        passwordBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const storyId = this.dataset.storyId;
                const storyTitle = this.dataset.storyTitle || 'this story';
                
                const newPassword = prompt('Enter new password for "' + storyTitle + '" (leave blank to remove password):');
                if (newPassword === null) {
                    return;
                }
                
                const self = this;
                const originalText = self.textContent;
                self.disabled = true;
                self.textContent = 'Updating...';
                
                const formData = new FormData();
                formData.append('action', 'their_story_update_password');
                formData.append('story_id', storyId);
                formData.append('password', newPassword);
                formData.append('nonce', theirStoryAdmin.passwordNonce);
                
                fetch(theirStoryAdmin.ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        alert(data.data.message || 'Password updated successfully.');
                        location.reload();
                    } else {
                        alert(data.data.message || 'Error updating password. Please try again.');
                        self.disabled = false;
                        self.textContent = originalText;
                    }
                })
                .catch(function() {
                    alert('An error occurred. Please try again.');
                    self.disabled = false;
                    self.textContent = originalText;
                });
            });
        });
        
        const deleteBtns = document.querySelectorAll('.their-story-delete-btn');
        deleteBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const storyId = this.dataset.storyId;
                const storyTitle = this.dataset.storyTitle || 'this story';
                
                if (!confirm('Are you sure you want to delete "' + storyTitle + '"? This action cannot be undone.')) {
                    return;
                }
                
                const self = this;
                const originalText = self.textContent;
                self.disabled = true;
                self.textContent = 'Deleting...';
                
                const formData = new FormData();
                formData.append('action', 'their_story_delete_story');
                formData.append('story_id', storyId);
                formData.append('nonce', theirStoryAdmin.deleteNonce);
                
                fetch(theirStoryAdmin.ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        const row = self.closest('tr');
                        if (row) {
                            row.style.opacity = '0.5';
                            setTimeout(function() {
                                row.remove();
                                
                                const tbody = document.querySelector('.their-story-table tbody');
                                if (tbody && tbody.children.length === 0) {
                                    location.reload();
                                }
                            }, 300);
                        } else {
                            location.reload();
                        }
                    } else {
                        alert(data.data.message || 'Error deleting story. Please try again.');
                        self.disabled = false;
                        self.textContent = originalText;
                    }
                })
                .catch(function() {
                    alert('An error occurred. Please try again.');
                    self.disabled = false;
                    self.textContent = originalText;
                });
            });
        });
        
        const approveSubmissionBtns = document.querySelectorAll('.their-story-approve-submission-btn');
        approveSubmissionBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const submissionId = this.dataset.submissionId;
                
                if (!submissionId) {
                    return;
                }
                
                const self = this;
                const originalText = self.textContent;
                self.disabled = true;
                self.textContent = 'Approving...';
                
                const formData = new FormData();
                formData.append('action', 'their_story_moderate_submission');
                formData.append('submission_id', submissionId);
                formData.append('moderate_action', 'approve');
                formData.append('nonce', theirStoryAdmin.moderateNonce);
                
                fetch(theirStoryAdmin.ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        const row = self.closest('tr');
                        if (row) {
                            const statusBadge = row.querySelector('.story-status');
                            if (statusBadge) {
                                statusBadge.className = 'story-status status-publish';
                                statusBadge.textContent = 'Approved';
                            }
                            
                            const approveBtn = row.querySelector('.their-story-approve-submission-btn');
                            if (approveBtn) {
                                const divider = approveBtn.nextElementSibling;
                                if (divider && divider.classList.contains('their-story-divider')) {
                                    divider.remove();
                                }
                                approveBtn.remove();
                            }
                            
                            row.className = row.className.replace('submission-status-pending', 'submission-status-publish');
                        }
                        alert(data.data.message || 'Submission approved.');
                    } else {
                        alert(data.data.message || 'Error approving submission. Please try again.');
                        self.disabled = false;
                        self.textContent = originalText;
                    }
                })
                .catch(function() {
                    alert('An error occurred. Please try again.');
                    self.disabled = false;
                    self.textContent = originalText;
                });
            });
        });
        
        const deleteSubmissionBtns = document.querySelectorAll('.their-story-delete-submission-btn');
        deleteSubmissionBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const submissionId = this.dataset.submissionId;
                
                if (!submissionId) {
                    return;
                }
                
                if (!confirm('Are you sure you want to delete this submission? This action cannot be undone.')) {
                    return;
                }
                
                const self = this;
                const originalText = self.textContent;
                self.disabled = true;
                self.textContent = 'Deleting...';
                
                const formData = new FormData();
                formData.append('action', 'their_story_moderate_submission');
                formData.append('submission_id', submissionId);
                formData.append('moderate_action', 'delete');
                formData.append('nonce', theirStoryAdmin.moderateNonce);
                
                fetch(theirStoryAdmin.ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        const row = self.closest('tr');
                        if (row) {
                            row.style.opacity = '0.5';
                            setTimeout(function() {
                                row.remove();
                                
                                const tbody = document.querySelector('.their-story-table tbody');
                                if (tbody && tbody.children.length === 0) {
                                    location.reload();
                                }
                            }, 300);
                        } else {
                            location.reload();
                        }
                    } else {
                        alert(data.data.message || 'Error deleting submission. Please try again.');
                        self.disabled = false;
                        self.textContent = originalText;
                    }
                })
                .catch(function() {
                    alert('An error occurred. Please try again.');
                    self.disabled = false;
                    self.textContent = originalText;
                });
            });
        });
        
        const reopenStoryBtns = document.querySelectorAll('.their-story-reopen-btn');
        reopenStoryBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const storyId = this.dataset.storyId;
                const storyTitle = this.dataset.storyTitle || 'this story';
                
                if (!confirm('Are you sure you want to re-open "' + storyTitle + '"? This will allow new messages to be added.')) {
                    return;
                }
                
                const self = this;
                const originalText = self.textContent;
                self.disabled = true;
                self.textContent = 'Re-opening...';
                
                const formData = new FormData();
                formData.append('action', 'their_story_reopen_story');
                formData.append('story_id', storyId);
                formData.append('nonce', theirStoryAdmin.reopenNonce);
                
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
                        alert(data.data.message || 'Error reopening story. Please try again.');
                        self.disabled = false;
                        self.textContent = originalText;
                    }
                })
                .catch(function() {
                    alert('An error occurred. Please try again.');
                    self.disabled = false;
                    self.textContent = originalText;
                });
            });
        });
        
        const lightboxTriggers = document.querySelectorAll('.their-story-lightbox-trigger');
        let lightbox = null;
        
        function createLightbox() {
            if (lightbox) return lightbox;
            
            lightbox = document.createElement('div');
            lightbox.className = 'their-story-lightbox';
            lightbox.innerHTML = `
                <div class="their-story-lightbox-overlay"></div>
                <button class="their-story-lightbox-close" aria-label="Close lightbox">&times;</button>
                <div class="their-story-lightbox-content">
                    <img class="their-story-lightbox-image" src="" alt="" />
                </div>
            `;
            document.body.appendChild(lightbox);
            
            lightbox.querySelector('.their-story-lightbox-overlay').addEventListener('click', closeLightbox);
            
            lightbox.querySelector('.their-story-lightbox-close').addEventListener('click', closeLightbox);
            
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && lightbox && lightbox.classList.contains('active')) {
                    closeLightbox();
                }
            });
            
            return lightbox;
        }
        
        function openLightbox(imageUrlOrArray) {
            const lb = createLightbox();
            const img = lb.querySelector('.their-story-lightbox-image');
            
            let imageUrls = [];
            if (Array.isArray(imageUrlOrArray)) {
                imageUrls = imageUrlOrArray;
            } else if (typeof imageUrlOrArray === 'string') {
                imageUrls = [imageUrlOrArray];
            }
            
            if (imageUrls.length === 0) return;
            
            let currentIndex = 0;
            img.src = imageUrls[0];
            img.alt = 'Full size image';
            lb.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            if (imageUrls.length > 1) {
                let prevBtn = lb.querySelector('.their-story-lightbox-prev');
                let nextBtn = lb.querySelector('.their-story-lightbox-next');
                
                if (!prevBtn) {
                    prevBtn = document.createElement('button');
                    prevBtn.className = 'their-story-lightbox-prev';
                    prevBtn.innerHTML = '&larr;';
                    prevBtn.setAttribute('aria-label', 'Previous image');
                    lb.querySelector('.their-story-lightbox-content').appendChild(prevBtn);
                }
                
                if (!nextBtn) {
                    nextBtn = document.createElement('button');
                    nextBtn.className = 'their-story-lightbox-next';
                    nextBtn.innerHTML = '&rarr;';
                    nextBtn.setAttribute('aria-label', 'Next image');
                    lb.querySelector('.their-story-lightbox-content').appendChild(nextBtn);
                }
                
                prevBtn.style.display = 'flex';
                nextBtn.style.display = 'flex';
                
                function showImage(index) {
                    if (index < 0) index = imageUrls.length - 1;
                    if (index >= imageUrls.length) index = 0;
                    currentIndex = index;
                    img.src = imageUrls[currentIndex];
                }
                
                prevBtn.onclick = function() {
                    showImage(currentIndex - 1);
                };
                
                nextBtn.onclick = function() {
                    showImage(currentIndex + 1);
                };
                
                document.addEventListener('keydown', function handleKey(e) {
                    if (!lb.classList.contains('active')) {
                        document.removeEventListener('keydown', handleKey);
                        return;
                    }
                    if (e.key === 'ArrowLeft') {
                        showImage(currentIndex - 1);
                    } else if (e.key === 'ArrowRight') {
                        showImage(currentIndex + 1);
                    }
                });
            } else {
                const prevBtn = lb.querySelector('.their-story-lightbox-prev');
                const nextBtn = lb.querySelector('.their-story-lightbox-next');
                if (prevBtn) prevBtn.style.display = 'none';
                if (nextBtn) nextBtn.style.display = 'none';
            }
        }
        
        function closeLightbox() {
            if (lightbox) {
                lightbox.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
        
        lightboxTriggers.forEach(function(trigger) {
            trigger.addEventListener('click', function(e) {
                e.preventDefault();
                const fullImagesJson = this.dataset.fullImages;
                if (fullImagesJson) {
                    try {
                        const imageUrls = JSON.parse(fullImagesJson);
                        openLightbox(imageUrls);
                    } catch (e) {
                        console.error('Error parsing image URLs:', e);
                    }
                } else {
                    const fullImageUrl = this.dataset.fullImage;
                    if (fullImageUrl) {
                        openLightbox(fullImageUrl);
                    }
                }
            });
        });
    });
    
})();

