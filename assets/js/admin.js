(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {

        // -----------------------------------------------------------------------
        // Create Story Wizard
        // -----------------------------------------------------------------------

        const modal       = document.getElementById('ts-wizard-modal');
        const backdrop    = modal ? modal.querySelector('.ts-wizard-backdrop') : null;
        const closeBtn    = modal ? modal.querySelector('.ts-wizard-close') : null;
        const openBtn     = document.getElementById('create-story-btn');

        // Step panels
        const step1 = document.getElementById('ts-step-1');
        const step2 = document.getElementById('ts-step-2');
        const step3 = document.getElementById('ts-step-3');

        // Step indicators
        const stepDots = modal ? modal.querySelectorAll('.ts-step') : [];

        // Controls
        const termsCheckbox = document.getElementById('ts-terms-accept');
        const next1Btn      = document.getElementById('ts-next-1');
        const back2Btn      = document.getElementById('ts-back-2');
        const next2Btn      = document.getElementById('ts-next-2');
        const back3Btn      = document.getElementById('ts-back-3');
        const purchaseBtn   = document.getElementById('ts-purchase-btn');
        const productsWrap  = document.getElementById('ts-products-container');

        let currentStep      = 1;
        let productsLoaded   = false;
        let selectedProduct  = null;
        let selectedVariation = null;

        function setStep(n) {
            currentStep = n;
            [step1, step2, step3].forEach(function(el, i) {
                if (!el) return;
                if (i + 1 === n) {
                    el.removeAttribute('hidden');
                } else {
                    el.setAttribute('hidden', '');
                }
            });
            stepDots.forEach(function(dot) {
                const dotStep = parseInt(dot.getAttribute('data-step'), 10);
                dot.classList.toggle('ts-step--active', dotStep === n);
                dot.classList.toggle('ts-step--done', dotStep < n);
            });
        }

        function openModal() {
            if (!modal) return;
            setStep(1);
            modal.removeAttribute('hidden');
            document.body.classList.add('ts-wizard-open');
            if (closeBtn) closeBtn.focus();
        }

        function closeModal() {
            if (!modal) return;
            modal.setAttribute('hidden', '');
            document.body.classList.remove('ts-wizard-open');
            if (openBtn) openBtn.focus();
        }

        if (openBtn) {
            openBtn.addEventListener('click', openModal);
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }

        if (backdrop) {
            backdrop.addEventListener('click', closeModal);
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal && !modal.hasAttribute('hidden')) {
                closeModal();
            }
        });

        // Step 1: enable Next when checkbox is checked
        if (termsCheckbox && next1Btn) {
            termsCheckbox.addEventListener('change', function() {
                next1Btn.disabled = !this.checked;
            });
            next1Btn.addEventListener('click', function() {
                setStep(2);
                const titleInput = document.getElementById('ts-story-title');
                if (titleInput) titleInput.focus();
            });
        }

        // Step 2: Back / Next
        if (back2Btn) {
            back2Btn.addEventListener('click', function() {
                setStep(1);
            });
        }

        if (next2Btn) {
            next2Btn.addEventListener('click', function() {
                const title = (document.getElementById('ts-story-title') || {}).value || '';
                if (!title.trim()) {
                    const input = document.getElementById('ts-story-title');
                    if (input) { input.focus(); input.reportValidity && input.reportValidity(); }
                    alert('Please enter a story title.');
                    return;
                }
                setStep(3);
                if (!productsLoaded) {
                    loadProducts();
                }
            });
        }

        // Step 3: Back
        if (back3Btn) {
            back3Btn.addEventListener('click', function() {
                setStep(2);
            });
        }

        // -----------------------------------------------------------------------
        // Load products for step 3
        // -----------------------------------------------------------------------

        // Products data store (set after load)
        var loadedProducts = [];

        function loadProducts() {
            if (!productsWrap) return;

            var fd = new FormData();
            fd.append('action', 'their_story_get_products');
            fd.append('nonce', theirStoryAdmin.getProductsNonce);

            fetch(theirStoryAdmin.ajaxUrl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    productsLoaded = true;
                    if (!data.success || !data.data || !data.data.length) {
                        productsWrap.innerHTML = '<p class="ts-products-error">No products available. Please contact the administrator.</p>';
                        return;
                    }
                    loadedProducts = data.data;
                    renderProducts(loadedProducts);
                })
                .catch(function() {
                    productsWrap.innerHTML = '<p class="ts-products-error">Failed to load products. Please refresh and try again.</p>';
                });
        }

        function renderProducts(products) {
            if (!productsWrap) return;

            if (products.length === 1) {
                // Single product — go straight to attribute pickers
                productsWrap.innerHTML = '';
                renderAttributePickers(products[0], productsWrap);
            } else {
                // Multiple products — show selector, then pickers below
                var html = '<div class="ts-product-selector">';
                products.forEach(function(p) {
                    html += '<button type="button" class="ts-product-pick-btn" data-product-index="' + products.indexOf(p) + '">';
                    if (p.image) html += '<img src="' + escAttr(p.image) + '" alt="" />';
                    html += '<span>' + escHtml(p.name) + '</span></button>';
                });
                html += '</div><div id="ts-attr-pickers"></div>';
                productsWrap.innerHTML = html;

                productsWrap.querySelectorAll('.ts-product-pick-btn').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        productsWrap.querySelectorAll('.ts-product-pick-btn').forEach(function(b) {
                            b.classList.remove('ts-product-pick-btn--selected');
                        });
                        this.classList.add('ts-product-pick-btn--selected');
                        var idx = parseInt(this.getAttribute('data-product-index'), 10);
                        var pickerWrap = document.getElementById('ts-attr-pickers');
                        if (pickerWrap) {
                            pickerWrap.innerHTML = '';
                            renderAttributePickers(products[idx], pickerWrap);
                        }
                    });
                });
            }
        }

        function renderAttributePickers(product, container) {
            var groups    = product.attribute_groups || [];
            var selections = {};

            var html = '';

            if (product.description) {
                html += '<p class="ts-product-desc-top">' + escHtml(product.description) + '</p>';
            }

            groups.forEach(function(group) {
                html += '<div class="ts-attr-group">';
                html += '<p class="ts-attr-label">' + escHtml(group.label) + '</p>';
                html += '<div class="ts-attr-options" data-attr-key="' + escAttr(group.key) + '">';
                group.options.forEach(function(opt) {
                    html += '<button type="button" class="ts-attr-btn"'
                          + ' data-attr-key="' + escAttr(group.key) + '"'
                          + ' data-attr-value="' + escAttr(opt.slug) + '">'
                          + escHtml(opt.label) + '</button>';
                });
                html += '</div></div>';
            });

            html += '<div class="ts-price-summary" id="ts-price-summary" hidden>'
                  + '<span class="ts-price-label">Total:</span>'
                  + '<span class="ts-price-value" id="ts-price-value"></span>'
                  + '</div>';

            container.innerHTML = html;

            container.querySelectorAll('.ts-attr-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var attrKey = this.getAttribute('data-attr-key');
                    selections[attrKey] = this.getAttribute('data-attr-value');

                    // Highlight within this group
                    container.querySelectorAll('.ts-attr-btn[data-attr-key="' + attrKey + '"]').forEach(function(b) {
                        b.classList.remove('ts-attr-btn--selected');
                    });
                    this.classList.add('ts-attr-btn--selected');

                    tryMatchVariation(product, selections, container);
                });
            });
        }

        function tryMatchVariation(product, selections, container) {
            var totalGroups   = (product.attribute_groups || []).length;
            var selectedCount = Object.keys(selections).length;

            var summary  = document.getElementById('ts-price-summary');
            var priceEl  = document.getElementById('ts-price-value');

            if (selectedCount < totalGroups) {
                selectedVariation = null;
                if (purchaseBtn) purchaseBtn.disabled = true;
                if (summary) summary.setAttribute('hidden', '');
                return;
            }

            // Find the variation that matches all selected attributes
            var match = null;
            product.variations.forEach(function(v) {
                if (match) return;
                var isMatch = true;
                for (var attrKey in selections) {
                    var varAttrVal = v.attributes[attrKey];
                    // Empty string means "any" in WC
                    if (varAttrVal !== '' && varAttrVal !== selections[attrKey]) {
                        isMatch = false;
                        break;
                    }
                }
                if (isMatch) match = v;
            });

            if (match) {
                selectedProduct   = product.id;
                selectedVariation = match.id;
                if (purchaseBtn) purchaseBtn.disabled = false;
                if (summary) {
                    summary.removeAttribute('hidden');
                    if (priceEl) priceEl.innerHTML = match.price_html;
                }
            } else {
                selectedVariation = null;
                if (purchaseBtn) purchaseBtn.disabled = true;
                if (summary) summary.setAttribute('hidden', '');
            }
        }

        // -----------------------------------------------------------------------
        // Proceed to Checkout
        // -----------------------------------------------------------------------

        if (purchaseBtn) {
            purchaseBtn.addEventListener('click', function() {
                if (!selectedProduct || !selectedVariation) {
                    alert('Please select a book size to continue.');
                    return;
                }

                var title         = (document.getElementById('ts-story-title') || {}).value || '';
                var password      = (document.getElementById('ts-story-password') || {}).value || '';
                var subjectName   = (document.getElementById('ts-subject-name') || {}).value || '';
                var relationLabel = (document.getElementById('ts-relation-label') || {}).value || '';

                purchaseBtn.disabled = true;
                purchaseBtn.textContent = 'Redirecting to checkout…';

                var fd = new FormData();
                fd.append('action', 'their_story_prepare_checkout');
                fd.append('nonce', theirStoryAdmin.prepareCheckoutNonce);
                fd.append('story_title', title);
                fd.append('story_password', password);
                fd.append('contribution_subject_name', subjectName);
                fd.append('contribution_relation_label', relationLabel);
                fd.append('product_id', selectedProduct);
                fd.append('variation_id', selectedVariation);

                fetch(theirStoryAdmin.ajaxUrl, { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success && data.data && data.data.redirect_url) {
                            window.location.href = data.data.redirect_url;
                        } else {
                            var msg = (data.data && data.data.message) ? data.data.message : 'An error occurred. Please try again.';
                            alert(msg);
                            purchaseBtn.disabled = false;
                            purchaseBtn.textContent = 'Proceed to Checkout';
                        }
                    })
                    .catch(function() {
                        alert('An error occurred. Please try again.');
                        purchaseBtn.disabled = false;
                        purchaseBtn.textContent = 'Proceed to Checkout';
                    });
            });
        }

        // -----------------------------------------------------------------------
        // Copy story link
        // -----------------------------------------------------------------------

        document.querySelectorAll('.copy-link-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var link = this.dataset.link;
                var tempInput = document.createElement('input');
                document.body.appendChild(tempInput);
                tempInput.value = link;
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);

                var self = this;
                var orig = this.textContent;
                this.textContent = 'Copied!';
                setTimeout(function() { self.textContent = orig; }, 2000);
            });
        });

        // -----------------------------------------------------------------------
        // Change password
        // -----------------------------------------------------------------------

        document.querySelectorAll('.their-story-password-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var storyId    = this.dataset.storyId;
                var storyTitle = this.dataset.storyTitle || 'this story';

                var newPassword = prompt('Enter new password for "' + storyTitle + '" (leave blank to remove password):');
                if (newPassword === null) return;

                var self = this;
                self.disabled = true;

                var fd = new FormData();
                fd.append('action', 'their_story_update_password');
                fd.append('story_id', storyId);
                fd.append('password', newPassword);
                fd.append('nonce', theirStoryAdmin.passwordNonce);

                fetch(theirStoryAdmin.ajaxUrl, { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            alert(data.data.message || 'Password updated successfully.');
                            location.reload();
                        } else {
                            alert(data.data.message || 'Error updating password. Please try again.');
                            self.disabled = false;
                        }
                    })
                    .catch(function() {
                        alert('An error occurred. Please try again.');
                        self.disabled = false;
                    });
            });
        });

        // -----------------------------------------------------------------------
        // Delete story
        // -----------------------------------------------------------------------

        document.querySelectorAll('.their-story-delete-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var storyId    = this.dataset.storyId;
                var storyTitle = this.dataset.storyTitle || 'this story';

                if (!confirm('Are you sure you want to delete "' + storyTitle + '"? This action cannot be undone.')) return;

                var self = this;
                self.disabled = true;

                var fd = new FormData();
                fd.append('action', 'their_story_delete_story');
                fd.append('story_id', storyId);
                fd.append('nonce', theirStoryAdmin.deleteNonce);

                fetch(theirStoryAdmin.ajaxUrl, { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            var row = self.closest('tr');
                            if (row) {
                                row.style.opacity = '0.5';
                                setTimeout(function() {
                                    row.remove();
                                    var tbody = document.querySelector('.their-story-table tbody');
                                    if (tbody && tbody.children.length === 0) location.reload();
                                }, 300);
                            } else {
                                location.reload();
                            }
                        } else {
                            alert(data.data.message || 'Error deleting story. Please try again.');
                            self.disabled = false;
                        }
                    })
                    .catch(function() {
                        alert('An error occurred. Please try again.');
                        self.disabled = false;
                    });
            });
        });

        // -----------------------------------------------------------------------
        // Approve submission
        // -----------------------------------------------------------------------

        document.querySelectorAll('.their-story-approve-submission-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var submissionId = this.dataset.submissionId;
                if (!submissionId) return;

                var self = this;
                var orig = self.textContent;
                self.disabled = true;
                self.textContent = 'Approving...';

                var fd = new FormData();
                fd.append('action', 'their_story_moderate_submission');
                fd.append('submission_id', submissionId);
                fd.append('moderate_action', 'approve');
                fd.append('nonce', theirStoryAdmin.moderateNonce);

                fetch(theirStoryAdmin.ajaxUrl, { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            var row = document.querySelector('tr[data-submission-id="' + submissionId + '"]');
                            if (row) {
                                var badge = row.querySelector('.story-status');
                                if (badge) { badge.className = 'story-status status-publish'; badge.textContent = 'Approved'; }
                                var approveBtn = row.querySelector('.their-story-approve-submission-btn');
                                if (approveBtn) {
                                    var divider = approveBtn.nextElementSibling;
                                    if (divider && divider.classList.contains('their-story-divider')) divider.remove();
                                    approveBtn.remove();
                                }
                                row.className = row.className.replace('submission-status-pending', 'submission-status-publish');
                            }
                            var subModal = document.getElementById('their-story-admin-submission-modal');
                            if (subModal && !subModal.hidden) {
                                subModal.setAttribute('hidden', '');
                                subModal.setAttribute('aria-hidden', 'true');
                                var mb = document.getElementById('their-story-admin-submission-modal-body');
                                if (mb) mb.innerHTML = '';
                                document.body.style.overflow = '';
                            }
                            alert(data.data.message || 'Submission approved.');
                        } else {
                            alert(data.data.message || 'Error approving submission. Please try again.');
                            self.disabled = false;
                            self.textContent = orig;
                        }
                    })
                    .catch(function() {
                        alert('An error occurred. Please try again.');
                        self.disabled = false;
                        self.textContent = orig;
                    });
            });
        });

        // -----------------------------------------------------------------------
        // Delete submission
        // -----------------------------------------------------------------------

        document.querySelectorAll('.their-story-delete-submission-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var submissionId = this.dataset.submissionId;
                if (!submissionId) return;
                if (!confirm('Are you sure you want to delete this submission? This action cannot be undone.')) return;

                var self = this;
                var orig = self.textContent;
                self.disabled = true;
                self.textContent = 'Deleting...';

                var fd = new FormData();
                fd.append('action', 'their_story_moderate_submission');
                fd.append('submission_id', submissionId);
                fd.append('moderate_action', 'delete');
                fd.append('nonce', theirStoryAdmin.moderateNonce);

                fetch(theirStoryAdmin.ajaxUrl, { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            var row = document.querySelector('tr[data-submission-id="' + submissionId + '"]');
                            var subModal = document.getElementById('their-story-admin-submission-modal');
                            if (subModal && !subModal.hidden) {
                                subModal.setAttribute('hidden', '');
                                subModal.setAttribute('aria-hidden', 'true');
                                var mb = document.getElementById('their-story-admin-submission-modal-body');
                                if (mb) mb.innerHTML = '';
                                document.body.style.overflow = '';
                            }
                            if (row) {
                                row.style.opacity = '0.5';
                                setTimeout(function() {
                                    row.remove();
                                    var tbody = document.querySelector('.their-story-table tbody');
                                    if (tbody && tbody.children.length === 0) location.reload();
                                }, 300);
                            } else {
                                location.reload();
                            }
                        } else {
                            alert(data.data.message || 'Error deleting submission. Please try again.');
                            self.disabled = false;
                            self.textContent = orig;
                        }
                    })
                    .catch(function() {
                        alert('An error occurred. Please try again.');
                        self.disabled = false;
                        self.textContent = orig;
                    });
            });
        });

        // -----------------------------------------------------------------------
        // Reopen story
        // -----------------------------------------------------------------------

        document.querySelectorAll('.their-story-reopen-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var storyId    = this.dataset.storyId;
                var storyTitle = this.dataset.storyTitle || 'this story';

                if (!confirm('Are you sure you want to re-open "' + storyTitle + '"? This will allow new messages to be added.')) return;

                var self = this;
                var orig = self.textContent;
                self.disabled = true;
                self.textContent = 'Re-opening...';

                var fd = new FormData();
                fd.append('action', 'their_story_reopen_story');
                fd.append('story_id', storyId);
                fd.append('nonce', theirStoryAdmin.reopenNonce);

                fetch(theirStoryAdmin.ajaxUrl, { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert(data.data.message || 'Error reopening story. Please try again.');
                            self.disabled = false;
                            self.textContent = orig;
                        }
                    })
                    .catch(function() {
                        alert('An error occurred. Please try again.');
                        self.disabled = false;
                        self.textContent = orig;
                    });
            });
        });

        // -----------------------------------------------------------------------
        // Lightbox
        // -----------------------------------------------------------------------

        var lightbox = null;

        function createLightbox() {
            if (lightbox) return lightbox;
            lightbox = document.createElement('div');
            lightbox.className = 'their-story-lightbox';
            lightbox.innerHTML =
                '<div class="their-story-lightbox-overlay"></div>' +
                '<button class="their-story-lightbox-close" aria-label="Close lightbox">&times;</button>' +
                '<div class="their-story-lightbox-content"><img class="their-story-lightbox-image" src="" alt="" /></div>';
            document.body.appendChild(lightbox);
            lightbox.querySelector('.their-story-lightbox-overlay').addEventListener('click', closeLightbox);
            lightbox.querySelector('.their-story-lightbox-close').addEventListener('click', closeLightbox);
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && lightbox && lightbox.classList.contains('active')) closeLightbox();
            });
            return lightbox;
        }

        function openLightbox(imageUrlOrArray) {
            var lb = createLightbox();
            var img = lb.querySelector('.their-story-lightbox-image');
            var imageUrls = Array.isArray(imageUrlOrArray) ? imageUrlOrArray : (typeof imageUrlOrArray === 'string' ? [imageUrlOrArray] : []);
            if (!imageUrls.length) return;
            var currentIndex = 0;
            img.src = imageUrls[0];
            img.alt = 'Full size image';
            lb.classList.add('active');
            document.body.style.overflow = 'hidden';

            if (imageUrls.length > 1) {
                var prevBtn = lb.querySelector('.their-story-lightbox-prev');
                var nextBtn = lb.querySelector('.their-story-lightbox-next');
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
                prevBtn.onclick = function() { showImage(currentIndex - 1); };
                nextBtn.onclick = function() { showImage(currentIndex + 1); };
                document.addEventListener('keydown', function handleKey(e) {
                    if (!lb.classList.contains('active')) { document.removeEventListener('keydown', handleKey); return; }
                    if (e.key === 'ArrowLeft') showImage(currentIndex - 1);
                    else if (e.key === 'ArrowRight') showImage(currentIndex + 1);
                });
            } else {
                var p = lb.querySelector('.their-story-lightbox-prev');
                var n = lb.querySelector('.their-story-lightbox-next');
                if (p) p.style.display = 'none';
                if (n) n.style.display = 'none';
            }
        }

        function closeLightbox() {
            if (lightbox) { lightbox.classList.remove('active'); document.body.style.overflow = ''; }
        }

        document.body.addEventListener('click', function(e) {
            var trigger = e.target.closest('.their-story-lightbox-trigger');
            if (!trigger) return;
            e.preventDefault();
            var fullImagesJson = trigger.dataset.fullImages;
            if (fullImagesJson) {
                try { openLightbox(JSON.parse(fullImagesJson)); } catch (err) { console.error(err); }
            } else {
                var url = trigger.dataset.fullImage;
                if (url) openLightbox(url);
            }
        });

        // -----------------------------------------------------------------------
        // Submission detail modal
        // -----------------------------------------------------------------------

        var submissionModal = document.getElementById('their-story-admin-submission-modal');
        if (submissionModal) {
            var submissionModalBody  = document.getElementById('their-story-admin-submission-modal-body');
            var submissionModalTitle = document.getElementById('their-story-admin-submission-modal-title');

            function closeSubmissionModal() {
                submissionModal.setAttribute('hidden', '');
                submissionModal.hidden = true;
                submissionModal.setAttribute('aria-hidden', 'true');
                if (submissionModalBody) submissionModalBody.innerHTML = '';
                document.body.style.overflow = '';
            }

            function openSubmissionModal(templateId, titleText) {
                var tpl = templateId ? document.getElementById(templateId) : null;
                if (!tpl || !submissionModalBody) return;
                submissionModalBody.innerHTML = '';
                submissionModalBody.appendChild(tpl.content.cloneNode(true));
                if (submissionModalTitle) submissionModalTitle.textContent = titleText || '';
                submissionModal.removeAttribute('hidden');
                submissionModal.hidden = false;
                submissionModal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }

            document.querySelectorAll('.their-story-view-submission-detail').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    openSubmissionModal(this.getAttribute('data-detail-template'), this.getAttribute('data-modal-title') || '');
                });
            });

            submissionModal.querySelector('.their-story-admin-submission-modal-backdrop').addEventListener('click', closeSubmissionModal);
            var xBtn = submissionModal.querySelector('.their-story-admin-submission-modal-x');
            if (xBtn) xBtn.addEventListener('click', closeSubmissionModal);
            var closeSubmBtn = submissionModal.querySelector('.their-story-admin-submission-modal-close-btn');
            if (closeSubmBtn) closeSubmBtn.addEventListener('click', closeSubmissionModal);

            document.addEventListener('keydown', function(ev) {
                if (ev.key !== 'Escape') return;
                if (!submissionModal.hasAttribute('hidden')) closeSubmissionModal();
            });
        }

        // -----------------------------------------------------------------------
        // Reorder modal
        // -----------------------------------------------------------------------

        var reorderModal     = document.getElementById('ts-reorder-modal');
        var reorderLede      = document.getElementById('ts-reorder-lede');
        var reorderDetails   = document.getElementById('ts-reorder-details');
        var reorderQtyInput  = document.getElementById('ts-reorder-qty');
        var reorderSubmitBtn = document.getElementById('ts-reorder-submit-btn');
        var reorderError     = document.getElementById('ts-reorder-error');
        var activeReorderStoryId = null;

        function openReorderModal(storyId, storyTitle) {
            activeReorderStoryId = storyId;
            reorderLede.textContent = 'Reordering copies of “' + storyTitle + '”.';
            reorderDetails.innerHTML = '<p style="color:#888;font-size:0.875rem;">Same book format as your original order.</p>';
            reorderQtyInput.value = 1;
            if (reorderError) { reorderError.hidden = true; reorderError.textContent = ''; }
            reorderSubmitBtn.disabled = false;
            reorderModal.removeAttribute('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeReorderModal() {
            reorderModal.setAttribute('hidden', '');
            document.body.style.overflow = '';
            activeReorderStoryId = null;
        }

        if (reorderModal) {
            document.querySelectorAll('.their-story-reorder-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    openReorderModal(this.dataset.storyId, this.dataset.storyTitle || 'your story');
                });
            });

            reorderModal.querySelector('.ts-reorder-close').addEventListener('click', closeReorderModal);
            reorderModal.querySelector('.ts-wizard-backdrop').addEventListener('click', closeReorderModal);

            document.addEventListener('keydown', function(ev) {
                if (ev.key === 'Escape' && !reorderModal.hasAttribute('hidden')) closeReorderModal();
            });

            document.getElementById('ts-qty-minus').addEventListener('click', function() {
                var v = parseInt(reorderQtyInput.value, 10);
                if (v > 1) reorderQtyInput.value = v - 1;
            });

            document.getElementById('ts-qty-plus').addEventListener('click', function() {
                var v = parseInt(reorderQtyInput.value, 10);
                if (v < 99) reorderQtyInput.value = v + 1;
            });

            reorderSubmitBtn.addEventListener('click', function() {
                if (!activeReorderStoryId) return;
                reorderSubmitBtn.disabled = true;
                if (reorderError) { reorderError.hidden = true; reorderError.textContent = ''; }

                var fd = new FormData();
                fd.append('action', 'their_story_prepare_reorder');
                fd.append('nonce', theirStoryAdmin.prepareReorderNonce);
                fd.append('story_id', activeReorderStoryId);
                fd.append('qty', reorderQtyInput.value);

                fetch(theirStoryAdmin.ajaxUrl, { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            window.location.href = data.data.redirect_url;
                        } else {
                            if (reorderError) {
                                reorderError.textContent = data.data.message || 'An error occurred. Please try again.';
                                reorderError.hidden = false;
                            }
                            reorderSubmitBtn.disabled = false;
                        }
                    })
                    .catch(function() {
                        if (reorderError) {
                            reorderError.textContent = 'An error occurred. Please try again.';
                            reorderError.hidden = false;
                        }
                        reorderSubmitBtn.disabled = false;
                    });
            });
        }

        // -----------------------------------------------------------------------
        // Help modal
        // -----------------------------------------------------------------------

        var helpBtn    = document.getElementById('ts-help-btn');
        var helpModal  = document.getElementById('ts-help-modal');
        var helpClose  = helpModal ? helpModal.querySelector('.ts-help-close') : null;
        var helpBackdrop = helpModal ? helpModal.querySelector('.ts-help-backdrop') : null;
        var helpSubmit = document.getElementById('ts-help-submit');
        var helpError  = document.getElementById('ts-help-error');
        var helpSuccess = document.getElementById('ts-help-success');

        function openHelpModal() {
            if (!helpModal) return;
            helpModal.hidden = false;
            helpModal.setAttribute('aria-hidden', 'false');
            if (helpError) { helpError.hidden = true; helpError.textContent = ''; }
            if (helpSuccess) helpSuccess.hidden = true;
            if (helpSubmit) helpSubmit.disabled = false;
        }

        function closeHelpModal() {
            if (!helpModal) return;
            helpModal.hidden = true;
            helpModal.setAttribute('aria-hidden', 'true');
        }

        if (helpBtn) helpBtn.addEventListener('click', openHelpModal);
        if (helpClose) helpClose.addEventListener('click', closeHelpModal);
        if (helpBackdrop) helpBackdrop.addEventListener('click', closeHelpModal);

        if (helpSubmit) {
            helpSubmit.addEventListener('click', function() {
                var name    = document.getElementById('ts-help-name') ? document.getElementById('ts-help-name').value.trim() : '';
                var email   = document.getElementById('ts-help-email') ? document.getElementById('ts-help-email').value.trim() : '';
                var message = document.getElementById('ts-help-message') ? document.getElementById('ts-help-message').value.trim() : '';

                if (!name || !email || !message) {
                    if (helpError) { helpError.textContent = 'Please fill in all fields.'; helpError.hidden = false; }
                    return;
                }

                helpSubmit.disabled = true;
                if (helpError) { helpError.hidden = true; helpError.textContent = ''; }

                var fd = new FormData();
                fd.append('action', 'their_story_help_request');
                fd.append('nonce', theirStoryAdmin.helpNonce);
                fd.append('name', name);
                fd.append('email', email);
                fd.append('message', message);

                fetch(theirStoryAdmin.ajaxUrl, { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            if (helpSuccess) helpSuccess.hidden = false;
                            helpSubmit.textContent = 'Sent!';
                        } else {
                            if (helpError) {
                                helpError.textContent = (data.data && data.data.message) || 'An error occurred. Please try again.';
                                helpError.hidden = false;
                            }
                            helpSubmit.disabled = false;
                        }
                    })
                    .catch(function() {
                        if (helpError) { helpError.textContent = 'An error occurred. Please try again.'; helpError.hidden = false; }
                        helpSubmit.disabled = false;
                    });
            });
        }

        // -----------------------------------------------------------------------
        // Helpers
        // -----------------------------------------------------------------------

        function escHtml(str) {
            var d = document.createElement('div');
            d.appendChild(document.createTextNode(String(str)));
            return d.innerHTML;
        }

        function escAttr(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

    });

})();
