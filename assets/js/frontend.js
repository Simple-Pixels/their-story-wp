(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const imagesInput = document.getElementById('submission-images');
        const imagesPreview = document.getElementById('images-preview');
        const form = document.getElementById('their-story-submission-form');
        let selectedFiles = [];

        if (imagesInput) {
            imagesInput.addEventListener('change', function(e) {
                const files = Array.from(e.target.files);
                
                if (files.length > 5) {
                    alert('You can only upload up to 5 images. Please select 5 or fewer images.');
                    files.splice(5);
                    e.target.files = createFileList(files);
                }
                
                selectedFiles = Array.from(e.target.files);
                
                if (imagesPreview) {
                    imagesPreview.innerHTML = '';
                }
                
                selectedFiles.forEach(function(file, index) {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const previewItem = document.createElement('div');
                            previewItem.className = 'their-story-preview-item';
                            previewItem.innerHTML = `
                                <img src="${e.target.result}" alt="Preview ${index + 1}" />
                                <button type="button" class="their-story-btn-small their-story-remove-preview" data-index="${index}">Remove</button>
                            `;
                            if (imagesPreview) {
                                imagesPreview.appendChild(previewItem);
                            }
                        };
                        reader.readAsDataURL(file);
                    }
                });
            });
        }

        if (imagesPreview) {
            imagesPreview.addEventListener('click', function(e) {
                if (e.target.classList.contains('their-story-remove-preview')) {
                    const index = parseInt(e.target.dataset.index);
                    const previewItem = e.target.closest('.their-story-preview-item');
                    
                    selectedFiles.splice(index, 1);
                    
                    const dt = new DataTransfer();
                    selectedFiles.forEach(function(file) {
                        dt.items.add(file);
                    });
                    if (imagesInput) {
                        imagesInput.files = dt.files;
                    }
                    
                    if (previewItem) {
                        previewItem.remove();
                    }
                    
                    const remainingPreviews = imagesPreview.querySelectorAll('.their-story-preview-item');
                    remainingPreviews.forEach(function(item, newIndex) {
                        const removeBtn = item.querySelector('.their-story-remove-preview');
                        if (removeBtn) {
                            removeBtn.dataset.index = newIndex;
                        }
                    });
                }
            });
        }
        
        function createFileList(files) {
            const dt = new DataTransfer();
            files.forEach(function(file) {
                dt.items.add(file);
            });
            return dt.files;
        }

        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const submitBtn = form.querySelector('button[type="submit"]');
                const messageDiv = document.getElementById('submission-status-message');
                const nameInput = document.getElementById('submission-name');
                const messageInput = document.getElementById('submission-message');

                const name = nameInput ? nameInput.value.trim() : '';
                const message = messageInput ? messageInput.value.trim() : '';
                const originalText = submitBtn ? submitBtn.textContent : '';

                if (!name || !message) {
                    if (messageDiv) {
                        messageDiv.className = 'their-story-message error';
                        messageDiv.textContent = 'Please fill in all required fields.';
                        messageDiv.style.display = 'block';
                    }
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'their_story_submit_message');
                formData.append('story_id', theirStoryFrontend.storyId);
                formData.append('name', name);
                formData.append('message', message);
                formData.append('nonce', theirStoryFrontend.submitNonce);

                const imagesInput = document.getElementById('submission-images');
                if (imagesInput && imagesInput.files.length > 0) {
                    for (let i = 0; i < imagesInput.files.length; i++) {
                        formData.append('images[]', imagesInput.files[i]);
                    }
                }

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Submitting...';
                }
                if (messageDiv) messageDiv.style.display = 'none';

                fetch(theirStoryFrontend.ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) { return response.json(); })
                .then(function(response) {
                    if (response.success) {
                        if (messageDiv) {
                            messageDiv.className = 'their-story-message success';
                            messageDiv.textContent = response.data.message;
                            messageDiv.style.display = 'block';
                        }
                        form.reset();
                        if (imagesPreview) imagesPreview.innerHTML = '';
                        selectedFiles = [];

                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        if (messageDiv) {
                            messageDiv.className = 'their-story-message error';
                            messageDiv.textContent = response.data.message || 'Error submitting message.';
                            messageDiv.style.display = 'block';
                        }
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                        }
                    }
                })
                .catch(function() {
                    if (messageDiv) {
                        messageDiv.className = 'their-story-message error';
                        messageDiv.textContent = 'An error occurred. Please try again.';
                        messageDiv.style.display = 'block';
                    }
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }
                });
            });
        }

        if (theirStoryFrontend.canModerate) {
            const moderateBtns = document.querySelectorAll('.their-story-btn-approve, .their-story-btn-delete');
            moderateBtns.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const action = btn.dataset.action;
                    const submissionItem = btn.closest('.their-story-submission-item');
                    const submissionId = submissionItem
                        ? submissionItem.dataset.submissionId
                        : btn.dataset.submissionId;

                    if (!submissionId) return;

                    const confirmMessage = action === 'approve'
                        ? 'Are you sure you want to approve this submission?'
                        : 'Are you sure you want to delete this submission? This cannot be undone.';

                    if (!confirm(confirmMessage)) return;

                    const originalText = btn.textContent;
                    btn.disabled = true;
                    btn.textContent = 'Processing...';

                    const body = new URLSearchParams({
                        action: 'their_story_moderate_submission',
                        submission_id: submissionId,
                        moderate_action: action,
                        nonce: theirStoryFrontend.moderateNonce
                    });

                    fetch(theirStoryFrontend.ajaxUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: body.toString()
                    })
                    .then(function(response) { return response.json(); })
                    .then(function(response) {
                        if (response.success) {
                            const el = submissionItem || btn.closest('.their-story-submission-item');
                            if (el) {
                                el.style.opacity = '0';
                                el.style.transition = 'opacity 0.3s';
                                setTimeout(function() {
                                    el.remove();
                                    if (action === 'approve') location.reload();
                                }, 300);
                            }
                        } else {
                            alert(response.data.message || 'Error processing request.');
                            btn.disabled = false;
                            btn.textContent = originalText;
                        }
                    })
                    .catch(function() {
                        alert('An error occurred. Please try again.');
                        btn.disabled = false;
                        btn.textContent = originalText;
                    });
                });
            });
        }

        const copyBtns = document.querySelectorAll('.their-story-btn-copy');
        copyBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const targetId = btn.dataset.copyTarget;
                const codeEl = document.getElementById(targetId);
                if (!codeEl) return;

                const text = codeEl.textContent;
                const tempInput = document.createElement('input');
                document.body.appendChild(tempInput);
                tempInput.value = text;
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);

                const originalText = btn.textContent;
                btn.textContent = 'Copied!';
                setTimeout(function() {
                    btn.textContent = originalText;
                }, 2000);
            });
        });

        const closeStoryBtn = document.getElementById('close-story-btn');
        if (closeStoryBtn && theirStoryFrontend.canCloseStory) {
            closeStoryBtn.addEventListener('click', function() {
                if (!confirm('Are you sure you want to close this story? This will prevent any more messages from being added.')) {
                    return;
                }

                const originalText = closeStoryBtn.textContent;
                closeStoryBtn.disabled = true;
                closeStoryBtn.textContent = 'Closing...';

                const body = new URLSearchParams({
                    action: 'their_story_close_story',
                    story_id: theirStoryFrontend.storyId,
                    nonce: theirStoryFrontend.closeNonce
                });

                fetch(theirStoryFrontend.ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString()
                })
                .then(function(response) { return response.json(); })
                .then(function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || 'Error closing story. Please try again.');
                        closeStoryBtn.disabled = false;
                        closeStoryBtn.textContent = originalText;
                    }
                })
                .catch(function() {
                    alert('An error occurred. Please try again.');
                    closeStoryBtn.disabled = false;
                    closeStoryBtn.textContent = originalText;
                });
            });
        }
        
        const galleryImages = document.querySelectorAll('.their-story-gallery-image');
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
        
        function openLightbox(imageUrl) {
            const lb = createLightbox();
            const img = lb.querySelector('.their-story-lightbox-image');
            img.src = imageUrl;
            img.alt = 'Full size image';
            lb.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeLightbox() {
            if (lightbox) {
                lightbox.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
        
        galleryImages.forEach(function(img) {
            img.addEventListener('click', function(e) {
                e.preventDefault();
                const fullImageUrl = this.dataset.fullImage || this.src;
                if (fullImageUrl) {
                    openLightbox(fullImageUrl);
                }
            });
        });
    });
})();
