(function() {
    'use strict';

    /** Theme-safe hide: native [hidden] is often overridden on buttons. */
    var CF_HIDE = 'their-story-cf-is-hidden';

    function readCfConfig() {
        var el = document.getElementById('their-story-cf-config');
        if (!el || !el.textContent) return {};
        try {
            return JSON.parse(el.textContent);
        } catch (e) {
            return {};
        }
    }

    function show(el) {
        if (!el) return;
        el.classList.remove(CF_HIDE);
        el.removeAttribute('hidden');
    }

    function hide(el) {
        if (!el) return;
        el.classList.add(CF_HIDE);
        el.removeAttribute('hidden');
    }

    function val(id) {
        var n = document.getElementById(id);
        return n ? String(n.value || '').trim() : '';
    }

    function createFileList(files) {
        var dt = new DataTransfer();
        files.forEach(function(f) {
            dt.items.add(f);
        });
        return dt.files;
    }

    function formatStepProgress(cfg, current, total) {
        var tpl = (cfg.i18n && cfg.i18n.stepProgress) || 'Step %1$d of %2$d';
        return tpl.replace('%1$d', String(current)).replace('%2$d', String(total));
    }

    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('their-story-contribution-form');
        if (!form || typeof theirStoryFrontend === 'undefined') return;

        if (theirStoryFrontend.isStoryClosed) {
            var closedSection = form.closest('.their-story-contribution-section');
            if (closedSection) {
                while (closedSection.firstChild) {
                    closedSection.removeChild(closedSection.firstChild);
                }
                var notice = document.createElement('div');
                notice.className = 'their-story-notice-closed';
                var closedP = document.createElement('p');
                closedP.textContent = theirStoryFrontend.storyClosedMessage || 'This story has been closed.';
                notice.appendChild(closedP);
                closedSection.appendChild(notice);
            }
            return;
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();
        });

        var cfg = readCfConfig();
        var joinIntent = null;
        var currentScreen = 'q1';

        var wizard = form.querySelector('.their-story-cf-wizard');
        var nav = document.getElementById('their-story-cf-wizard-nav');
        var backBtn = document.getElementById('their-story-cf-wizard-back');
        var nextBtn = document.getElementById('their-story-cf-wizard-next');
        var progressEl = document.getElementById('their-story-cf-step-progress');
        var nextLabelNext = (cfg.i18n && cfg.i18n.next) || 'Next';
        var nextLabelSubmit = (cfg.i18n && cfg.i18n.submit) || 'Submit';
        var statusEl = document.getElementById('submission-status-message');

        var funnyChoice = null;
        var extra1Choice = null;
        var extra2Choice = null;
        var thoughtsChoice = null;

        var imagesInput = document.getElementById('cf-submission-images');
        var imagesPreview = document.getElementById('cf-images-preview');
        var selectedFiles = [];

        function getYesScreens() {
            var screens = ['q1', 'details', 'how_met', 'one_word', 'particular', 'funny'];
            if (funnyChoice === 'yes') {
                screens.push('funny_text');
            }
            screens.push('extra1');
            if (extra1Choice === 'yes') {
                screens.push('extra1_text');
                screens.push('extra2');
                if (extra2Choice === 'yes') {
                    screens.push('extra2_text');
                }
            }
            screens.push('thoughts');
            if (thoughtsChoice === 'yes') {
                screens.push('thoughts_text');
            }
            screens.push('images');
            return screens;
        }

        function setStatus(type, text) {
            if (!statusEl) return;
            statusEl.className = 'their-story-message ' + (type || '');
            statusEl.textContent = text || '';
            statusEl.style.display = text ? 'block' : 'none';
        }

        function syncJoinAria() {
            form.querySelectorAll('[data-cf-join]').forEach(function(b) {
                var v = b.getAttribute('data-cf-join');
                b.setAttribute('aria-pressed', joinIntent === v ? 'true' : 'false');
            });
        }

        function playStepEnter(activeEl) {
            if (!activeEl) return;
            activeEl.classList.remove('their-story-cf-step--enter');
            activeEl.offsetHeight;
            activeEl.classList.add('their-story-cf-step--enter');
            var cleaned = false;
            function cleanup() {
                if (cleaned) return;
                cleaned = true;
                activeEl.removeEventListener('animationend', cleanup);
                activeEl.classList.remove('their-story-cf-step--enter');
            }
            activeEl.addEventListener('animationend', cleanup);
            window.setTimeout(cleanup, 600);
        }

        function goTo(screen, opts) {
            opts = opts || {};
            var forward = !!opts.forward;
            currentScreen = screen;
            if (!wizard) return;
            wizard.querySelectorAll('[data-cf-screen]').forEach(function(el) {
                var isActive = el.getAttribute('data-cf-screen') === screen;
                if (isActive) show(el);
                else hide(el);
                el.classList.toggle('their-story-cf-step-active', isActive);
                el.setAttribute('aria-hidden', isActive ? 'false' : 'true');
            });
            syncNav();
            updateProgress();
            var active = wizard.querySelector('.their-story-cf-step-active');
            if (forward && active) {
                playStepEnter(active);
            }
            if (active) {
                var focusTarget = active.querySelector('input:not([type="file"]), textarea, select, button:not(.' + CF_HIDE + ')');
                if (!focusTarget) {
                    focusTarget = active.querySelector('button[type="button"]:not(.' + CF_HIDE + ')');
                }
                if (focusTarget && typeof focusTarget.focus === 'function') {
                    try {
                        focusTarget.focus();
                    } catch (e) { /* ignore */ }
                }
            }
        }

        function updateProgress() {
            if (!progressEl) return;
            var flow = getYesScreens();
            var idx = flow.indexOf(currentScreen);
            if (idx >= 0) {
                show(progressEl);
                progressEl.textContent = formatStepProgress(cfg, idx + 1, flow.length);
            } else {
                hide(progressEl);
                progressEl.textContent = '';
            }
        }

        function syncNav() {
            if (!nav || !backBtn || !nextBtn) return;
            if (currentScreen === 'no_thanks') {
                hide(nav);
                return;
            }

            var hideFullNavScreens = ['q1', 'funny', 'extra1', 'extra2', 'thoughts'];
            if (hideFullNavScreens.indexOf(currentScreen) !== -1) {
                hide(nav);
                return;
            }

            show(nav);

            var onImages = currentScreen === 'images';
            var onNoBranch = currentScreen === 'no_confirm';
            var onUncertain = currentScreen === 'uncertain';
            var textStepsWithNext = ['details', 'how_met', 'one_word', 'particular', 'funny_text', 'extra1_text', 'extra2_text', 'thoughts_text'];
            var onTextStep = textStepsWithNext.indexOf(currentScreen) !== -1;

            show(backBtn);

            if (onImages) {
                show(nextBtn);
                nextBtn.textContent = nextLabelSubmit;
            } else if (onNoBranch || onUncertain) {
                hide(nextBtn);
                nextBtn.textContent = nextLabelNext;
            } else if (onTextStep) {
                show(nextBtn);
                nextBtn.textContent = nextLabelNext;
            } else {
                hide(nextBtn);
                nextBtn.textContent = nextLabelNext;
            }
        }

        function validateCurrentScreen() {
            switch (currentScreen) {
                case 'q1':
                    if (!joinIntent) {
                        return 'Please choose Yes, No, or Uncertain before continuing.';
                    }
                    return '';
                case 'details':
                    if (!val('cf-first-name') || !val('cf-surname') || !val('cf-email') || !val('cf-live') || !val('cf-known')) {
                        return 'Please complete your name, email, and where you live.';
                    }
                    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val('cf-email'))) {
                        return 'Please enter a valid email address.';
                    }
                    return '';
                case 'how_met':
                    if (!val('cf-how-met')) {
                        return 'Please share how you met.';
                    }
                    return '';
                case 'one_word':
                    if (!val('cf-one-word')) {
                        return 'Please enter one word.';
                    }
                    return '';
                case 'particular':
                    if (!val('cf-particular')) {
                        return 'Please share a particular story.';
                    }
                    return '';
                case 'funny':
                    if (!funnyChoice) {
                        return 'Please answer whether you have a funny story.';
                    }
                    return '';
                case 'funny_text':
                    if (funnyChoice === 'yes' && !val('cf-funny-text')) {
                        return 'Please enter your funny story, or go back and choose No.';
                    }
                    return '';
                case 'extra1':
                    if (extra1Choice !== 'yes' && extra1Choice !== 'no') {
                        return 'Please answer whether you have another story to share.';
                    }
                    return '';
                case 'extra1_text':
                    if (extra1Choice === 'yes' && !val('cf-extra1')) {
                        return 'Please enter your story, or go back and choose No.';
                    }
                    return '';
                case 'extra2':
                    if (extra2Choice !== 'yes' && extra2Choice !== 'no') {
                        return 'Please answer whether you have another story to share.';
                    }
                    return '';
                case 'extra2_text':
                    if (extra2Choice === 'yes' && !val('cf-extra2')) {
                        return 'Please enter your story, or go back and choose No.';
                    }
                    return '';
                case 'thoughts':
                    if (thoughtsChoice !== 'yes' && thoughtsChoice !== 'no') {
                        return 'Please answer whether you would like to share other thoughts.';
                    }
                    return '';
                case 'thoughts_text':
                    if (thoughtsChoice === 'yes' && !val('cf-thoughts')) {
                        return 'Please enter your thoughts, or go back and choose No.';
                    }
                    return '';
                case 'images':
                    return '';
                default:
                    return '';
            }
        }

        function nextScreenFrom(s) {
            if (s === 'q1') {
                if (joinIntent === 'yes') return 'details';
                if (joinIntent === 'no') return 'no_confirm';
                if (joinIntent === 'uncertain') return 'uncertain';
                return null;
            }
            var flow = getYesScreens();
            var i = flow.indexOf(s);
            if (i >= 0 && i < flow.length - 1) {
                return flow[i + 1];
            }
            return null;
        }

        function prevScreenFrom(s) {
            if (s === 'no_confirm' || s === 'uncertain') return 'q1';
            var flow = getYesScreens();
            var i = flow.indexOf(s);
            if (i > 0) return flow[i - 1];
            return null;
        }

        function handleNext() {
            var err = validateCurrentScreen();
            if (err) {
                setStatus('error', err);
                return;
            }
            setStatus('', '');
            var next = nextScreenFrom(currentScreen);
            if (next) goTo(next, { forward: true });
        }

        function handleBack() {
            setStatus('', '');
            var prev = prevScreenFrom(currentScreen);
            if (prev) goTo(prev, { forward: false });
        }

        if (backBtn) backBtn.addEventListener('click', handleBack);

        form.querySelectorAll('.their-story-cf-inline-back').forEach(function(btn) {
            btn.addEventListener('click', handleBack);
        });

        form.querySelectorAll('[data-cf-join]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                joinIntent = btn.getAttribute('data-cf-join');
                syncJoinAria();
                setStatus('', '');
                var next = nextScreenFrom('q1');
                if (next) goTo(next, { forward: true });
            });
        });

        var noFinal = document.getElementById('their-story-cf-no-final');
        var noMore = document.getElementById('their-story-cf-no-more-info');
        if (noFinal) {
            noFinal.addEventListener('click', function() {
                goTo('no_thanks', { forward: false });
            });
        }
        if (noMore) {
            noMore.addEventListener('click', function() {
                var u = cfg.moreInfoUrl || cfg.faqUrl || '#';
                if (u && u !== '#') window.open(u, '_blank', 'noopener,noreferrer');
            });
        }

        form.querySelectorAll('[data-cf-funny]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                funnyChoice = btn.getAttribute('data-cf-funny');
                form.querySelectorAll('[data-cf-funny]').forEach(function(b) {
                    b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
                });
                if (funnyChoice === 'yes') {
                    setStatus('', '');
                    goTo('funny_text', { forward: true });
                } else {
                    var ft = document.getElementById('cf-funny-text');
                    if (ft) ft.value = '';
                    extra1Choice = null;
                    extra2Choice = null;
                    thoughtsChoice = null;
                    var x1 = document.getElementById('cf-extra1');
                    var x2 = document.getElementById('cf-extra2');
                    var th = document.getElementById('cf-thoughts');
                    if (x1) x1.value = '';
                    if (x2) x2.value = '';
                    if (th) th.value = '';
                    form.querySelectorAll('[data-cf-extra1], [data-cf-extra2], [data-cf-thoughts]').forEach(function(b) {
                        b.setAttribute('aria-pressed', 'false');
                    });
                    setStatus('', '');
                    goTo('extra1', { forward: true });
                }
            });
        });

        form.querySelectorAll('[data-cf-extra1]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                extra1Choice = btn.getAttribute('data-cf-extra1');
                form.querySelectorAll('[data-cf-extra1]').forEach(function(b) {
                    b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
                });
                if (extra1Choice === 'yes') {
                    setStatus('', '');
                    goTo('extra1_text', { forward: true });
                } else {
                    var x = document.getElementById('cf-extra1');
                    if (x) x.value = '';
                    extra2Choice = null;
                    thoughtsChoice = null;
                    var x2 = document.getElementById('cf-extra2');
                    var th = document.getElementById('cf-thoughts');
                    if (x2) x2.value = '';
                    if (th) th.value = '';
                    form.querySelectorAll('[data-cf-extra2], [data-cf-thoughts]').forEach(function(b) {
                        b.setAttribute('aria-pressed', 'false');
                    });
                    setStatus('', '');
                    goTo('thoughts', { forward: true });
                }
            });
        });

        form.querySelectorAll('[data-cf-extra2]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                extra2Choice = btn.getAttribute('data-cf-extra2');
                form.querySelectorAll('[data-cf-extra2]').forEach(function(b) {
                    b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
                });
                if (extra2Choice === 'yes') {
                    setStatus('', '');
                    goTo('extra2_text', { forward: true });
                } else {
                    var x = document.getElementById('cf-extra2');
                    if (x) x.value = '';
                    thoughtsChoice = null;
                    var th = document.getElementById('cf-thoughts');
                    if (th) th.value = '';
                    form.querySelectorAll('[data-cf-thoughts]').forEach(function(b) {
                        b.setAttribute('aria-pressed', 'false');
                    });
                    setStatus('', '');
                    goTo('thoughts', { forward: true });
                }
            });
        });

        form.querySelectorAll('[data-cf-thoughts]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                thoughtsChoice = btn.getAttribute('data-cf-thoughts');
                form.querySelectorAll('[data-cf-thoughts]').forEach(function(b) {
                    b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
                });
                if (thoughtsChoice === 'yes') {
                    setStatus('', '');
                    goTo('thoughts_text', { forward: true });
                } else {
                    var x = document.getElementById('cf-thoughts');
                    if (x) x.value = '';
                    setStatus('', '');
                    goTo('images', { forward: true });
                }
            });
        });

        if (imagesInput && imagesPreview) {
            imagesInput.addEventListener('change', function() {
                var files = Array.from(imagesInput.files);
                if (files.length > 5) {
                    alert('You can only upload up to 5 images.');
                    files = files.slice(0, 5);
                    imagesInput.files = createFileList(files);
                }
                selectedFiles = Array.from(imagesInput.files);
                imagesPreview.innerHTML = '';
                selectedFiles.forEach(function(file, index) {
                    if (!file.type.startsWith('image/')) return;
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        var div = document.createElement('div');
                        div.className = 'their-story-preview-item';
                        div.innerHTML = '<img src="' + e.target.result + '" alt="" /><button type="button" class="their-story-btn-small their-story-remove-preview" data-index="' + index + '">Remove</button>';
                        imagesPreview.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                });
            });
            imagesPreview.addEventListener('click', function(e) {
                if (!e.target.classList.contains('their-story-remove-preview')) return;
                var idx = parseInt(e.target.getAttribute('data-index'), 10);
                var item = e.target.closest('.their-story-preview-item');
                selectedFiles.splice(idx, 1);
                var dt = new DataTransfer();
                selectedFiles.forEach(function(f) {
                    dt.items.add(f);
                });
                imagesInput.files = dt.files;
                if (item) item.remove();
                imagesPreview.querySelectorAll('.their-story-preview-item').forEach(function(row, i) {
                    var b = row.querySelector('.their-story-remove-preview');
                    if (b) b.setAttribute('data-index', String(i));
                });
            });
        }

        function gatherContribution() {
            var payload = {
                join_intent: joinIntent === 'yes' ? 'yes' : (joinIntent || ''),
                first_name: val('cf-first-name'),
                surname: val('cf-surname'),
                email: val('cf-email'),
                live_where: val('cf-live'),
                known_duration: val('cf-known'),
                how_met_story: val('cf-how-met'),
                describe_one_word: val('cf-one-word'),
                particular_story: val('cf-particular'),
                funny_story_choice: funnyChoice,
                funny_story_text: val('cf-funny-text'),
                extra_story1_choice: extra1Choice,
                extra_story1_text: val('cf-extra1'),
                extra_story2_choice: extra1Choice === 'no' ? null : extra2Choice,
                extra_story2_text: extra1Choice === 'no' ? '' : val('cf-extra2'),
                other_thoughts_choice: thoughtsChoice,
                other_thoughts_text: val('cf-thoughts')
            };
            return payload;
        }

        function validateBeforeConfirm() {
            if (joinIntent !== 'yes') {
                return 'Please choose Yes to continue with a submission.';
            }
            var d = gatherContribution();
            if (!d.first_name || !d.surname || !d.email || !d.live_where || !d.known_duration) {
                return 'Please complete your name, email, and where you live.';
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(d.email)) {
                return 'Please enter a valid email address.';
            }
            if (!d.how_met_story || !d.describe_one_word || !d.particular_story) {
                return 'Please complete the story questions marked as required.';
            }
            if (!funnyChoice) return 'Please answer whether you have a funny story.';
            if (funnyChoice === 'yes' && !d.funny_story_text) {
                return 'Please enter your funny story, or choose No.';
            }
            if (extra1Choice !== 'yes' && extra1Choice !== 'no') {
                return 'Please answer the first “any other stories” question.';
            }
            if (extra1Choice === 'yes' && !d.extra_story1_text) {
                return 'Please enter your other story, or choose No.';
            }
            if (extra1Choice === 'yes') {
                if (extra2Choice !== 'yes' && extra2Choice !== 'no') {
                    return 'Please answer the second “any other stories” question.';
                }
                if (extra2Choice === 'yes' && !d.extra_story2_text) {
                    return 'Please enter your other story, or choose No.';
                }
            }
            if (thoughtsChoice !== 'yes' && thoughtsChoice !== 'no') {
                return 'Please answer whether you would like to share other thoughts.';
            }
            if (thoughtsChoice === 'yes' && !d.other_thoughts_text) {
                return 'Please enter your thoughts, or choose No.';
            }
            return '';
        }

        function resetWizardAfterSuccess() {
            joinIntent = null;
            funnyChoice = null;
            extra1Choice = null;
            extra2Choice = null;
            thoughtsChoice = null;
            syncJoinAria();
            form.querySelectorAll('[data-cf-funny], [data-cf-extra1], [data-cf-extra2], [data-cf-thoughts]').forEach(function(b) {
                b.setAttribute('aria-pressed', 'false');
            });
            selectedFiles = [];
            if (imagesPreview) imagesPreview.innerHTML = '';
            goTo('q1', { forward: false });
        }

        var confirmModal = document.getElementById('their-story-submit-confirm-modal');
        var finalSubmitBtn = document.getElementById('their-story-cf-final-submit');

        function openConfirmModal() {
            var err = validateBeforeConfirm();
            if (err) {
                setStatus('error', err);
                return;
            }
            setStatus('', '');
            if (!confirmModal) return;
            confirmModal.removeAttribute('hidden');
            confirmModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeConfirmModal() {
            if (!confirmModal) return;
            confirmModal.setAttribute('hidden', '');
            confirmModal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                if (currentScreen === 'images') {
                    openConfirmModal();
                } else {
                    handleNext();
                }
            });
        }

        if (confirmModal) {
            confirmModal.querySelectorAll('[data-their-story-close-submit-confirm]').forEach(function(el) {
                el.addEventListener('click', closeConfirmModal);
            });
            document.addEventListener('keydown', function(ev) {
                if (ev.key === 'Escape' && confirmModal && !confirmModal.hasAttribute('hidden')) {
                    closeConfirmModal();
                }
            });
        }

        if (finalSubmitBtn) {
            finalSubmitBtn.addEventListener('click', function() {
                var err = validateBeforeConfirm();
                if (err) {
                    setStatus('error', err);
                    closeConfirmModal();
                    return;
                }
                var payload = gatherContribution();
                var formData = new FormData();
                formData.append('action', 'their_story_submit_message');
                formData.append('story_id', theirStoryFrontend.storyId);
                formData.append('nonce', theirStoryFrontend.submitNonce);
                formData.append('contribution_json', JSON.stringify(payload));

                if (imagesInput && imagesInput.files.length > 0) {
                    for (var i = 0; i < imagesInput.files.length; i++) {
                        formData.append('images[]', imagesInput.files[i]);
                    }
                }

                finalSubmitBtn.disabled = true;
                var orig = finalSubmitBtn.textContent;
                finalSubmitBtn.textContent = 'Submitting...';

                fetch(theirStoryFrontend.ajaxUrl, { method: 'POST', body: formData })
                    .then(function(r) { return r.json(); })
                    .then(function(response) {
                        if (response.success) {
                            setStatus('success', response.data.message || 'Submitted.');
                            closeConfirmModal();
                            form.reset();
                            resetWizardAfterSuccess();
                            setTimeout(function() { location.reload(); }, 2000);
                        } else {
                            setStatus('error', (response.data && response.data.message) || 'Error submitting.');
                            finalSubmitBtn.disabled = false;
                            finalSubmitBtn.textContent = orig;
                        }
                    })
                    .catch(function() {
                        setStatus('error', 'An error occurred. Please try again.');
                        finalSubmitBtn.disabled = false;
                        finalSubmitBtn.textContent = orig;
                    });
            });
        }

        syncJoinAria();
        goTo('q1', { forward: false });
    });
})();
