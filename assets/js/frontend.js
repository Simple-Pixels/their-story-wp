(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const imageInput = document.getElementById('submission-image');
        const previewImg = document.getElementById('preview-img');
        const imagePreview = document.getElementById('image-preview');
        const removeImageBtn = document.getElementById('remove-image');
        const form = document.getElementById('their-story-submission-form');

        if (imageInput) {
            imageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        if (previewImg) previewImg.src = e.target.result;
                        if (imagePreview) {
                            imagePreview.style.display = 'inline-block';
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        if (removeImageBtn) {
            removeImageBtn.addEventListener('click', function() {
                if (imageInput) imageInput.value = '';
                if (imagePreview) imagePreview.style.display = 'none';
                if (previewImg) previewImg.src = '';
            });
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

                const imageFile = imageInput && imageInput.files[0];
                if (imageFile) {
                    formData.append('image', imageFile);
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
                        if (imagePreview) imagePreview.style.display = 'none';

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
    });
})();
