(function() {
    'use strict';

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
        if (el) el.hidden = false;
    }

    function hide(el) {
        if (el) el.hidden = true;
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
        var openConfirmBtn = document.getElementById('their-story-cf-open-confirm');
        var progressEl = document.getElementById('their-story-cf-step-progress');
        var statusEl = document.getElementById('submission-status-message');

        var funnyTextWrap = document.getElementById('their-story-cf-funny-text-wrap');
        var extra1Wrap = document.getElementById('their-story-cf-extra1-text-wrap');
        var extra2Wrap = document.getElementById('their-story-cf-extra2-text-wrap');
        var thoughtsWrap = document.getElementById('their-story-cf-thoughts-text-wrap');

        var funnyChoice = null;
        var extra1Choice = null;
        var extra2Choice = null;
        var thoughtsChoice = null;

        var imagesInput = document.getElementById('cf-submission-images');
        var imagesPreview = document.getElementById('cf-images-preview');
        var selectedFiles = [];

        function getYesScreens() {
            var screens = ['q1', 'details', 'how_met', 'one_word', 'particular', 'funny', 'extra1'];
            if (extra1Choice === 'yes' && val('cf-extra1').length > 0) {
                screens.push('extra2');
            }
            screens.push('thoughts', 'images');
            return screens;
        }

        function tryAdvanceFromTextarea(fieldId, mustBeScreen, nextScreen) {
            if (currentScreen !== mustBeScreen) return;
            if (fieldId === 'cf-funny-text' && funnyChoice !== 'yes') return;
            if (fieldId === 'cf-extra1' && extra1Choice !== 'yes') return;
            if (fieldId === 'cf-extra2' && extra2Choice !== 'yes') return;
            if (fieldId === 'cf-thoughts' && thoughtsChoice !== 'yes') return;
            var el = document.getElementById(fieldId);
            if (!el) return;
            var t = String(el.value || '').trim();
            if (t.length === 0) return;
            setStatus('', '');
            goTo(nextScreen, { forward: true });
        }

        var textDebounceTimer = null;
        function scheduleTextareaAdvance(fieldId, mustBeScreen, nextScreen, delay) {
            if (currentScreen !== mustBeScreen) return;
            if (fieldId === 'cf-funny-text' && funnyChoice !== 'yes') return;
            if (fieldId === 'cf-extra1' && extra1Choice !== 'yes') return;
            if (fieldId === 'cf-extra2' && extra2Choice !== 'yes') return;
            if (fieldId === 'cf-thoughts' && thoughtsChoice !== 'yes') return;
            if (textDebounceTimer) window.clearTimeout(textDebounceTimer);
            textDebounceTimer = window.setTimeout(function() {
                textDebounceTimer = null;
                tryAdvanceFromTextarea(fieldId, mustBeScreen, nextScreen);
            }, delay || 420);
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
                el.hidden = !isActive;
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
                var focusTarget = active.querySelector('input:not([type="file"]), textarea, select, button:not([hidden])');
                if (!focusTarget) {
                    focusTarget = active.querySelector('button[type="button"]:not([hidden])');
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
                progressEl.hidden = false;
                progressEl.textContent = formatStepProgress(cfg, idx + 1, flow.length);
            } else {
                progressEl.hidden = true;
                progressEl.textContent = '';
            }
        }

        function syncNav() {
            if (!nav || !backBtn || !nextBtn || !openConfirmBtn) return;
            if (currentScreen === 'no_thanks' || currentScreen === 'q1') {
                nav.hidden = true;
                return;
            }
            nav.hidden = false;

            backBtn.hidden = false;

            var onImages = currentScreen === 'images';
            var onNoBranch = currentScreen === 'no_confirm';
            var onUncertain = currentScreen === 'uncertain';

            var textStepsWithNext = ['details', 'how_met', 'one_word', 'particular'];
            var showNext = textStepsWithNext.indexOf(currentScreen) !== -1;

            nextBtn.hidden = onImages || onNoBranch || onUncertain || !showNext;
            openConfirmBtn.hidden = !onImages;
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
                    if (funnyChoice === 'yes' && !val('cf-funny-text')) {
                        return 'Please enter your funny story, or choose No.';
                    }
                    return '';
                case 'extra1':
                    if (extra1Choice !== 'yes' && extra1Choice !== 'no') {
                        return 'Please answer whether you have another story to share.';
                    }
                    if (extra1Choice === 'yes' && !val('cf-extra1')) {
                        return 'Please enter your story, or choose No.';
                    }
                    return '';
                case 'extra2':
                    if (extra2Choice !== 'yes' && extra2Choice !== 'no') {
                        return 'Please answer whether you have another story to share.';
                    }
                    if (extra2Choice === 'yes' && !val('cf-extra2')) {
                        return 'Please enter your story, or choose No.';
                    }
                    return '';
                case 'thoughts':
                    if (thoughtsChoice !== 'yes' && thoughtsChoice !== 'no') {
                        return 'Please answer whether you would like to share other thoughts.';
                    }
                    if (thoughtsChoice === 'yes' && !val('cf-thoughts')) {
                        return 'Please enter your thoughts, or choose No.';
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

        if (nextBtn) nextBtn.addEventListener('click', handleNext);
        if (backBtn) backBtn.addEventListener('click', handleBack);

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
                    show(funnyTextWrap);
                    setStatus('', '');
                    syncNav();
                } else {
                    hide(funnyTextWrap);
                    var ft = document.getElementById('cf-funny-text');
                    if (ft) ft.value = '';
                    extra1Choice = null;
                    extra2Choice = null;
                    thoughtsChoice = null;
                    hide(extra1Wrap);
                    hide(extra2Wrap);
                    hide(thoughtsWrap);
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
                    show(extra1Wrap);
                    setStatus('', '');
                    syncNav();
                } else {
                    hide(extra1Wrap);
                    var x = document.getElementById('cf-extra1');
                    if (x) x.value = '';
                    extra2Choice = null;
                    thoughtsChoice = null;
                    hide(extra2Wrap);
                    hide(thoughtsWrap);
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
                    show(extra2Wrap);
                    setStatus('', '');
                    syncNav();
                } else {
                    hide(extra2Wrap);
                    var x = document.getElementById('cf-extra2');
                    if (x) x.value = '';
                    thoughtsChoice = null;
                    hide(thoughtsWrap);
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
                    show(thoughtsWrap);
                    setStatus('', '');
                    syncNav();
                } else {
                    hide(thoughtsWrap);
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
            hide(funnyTextWrap);
            hide(extra1Wrap);
            hide(extra2Wrap);
            hide(thoughtsWrap);
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

        if (openConfirmBtn) openConfirmBtn.addEventListener('click', openConfirmModal);
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

        var funnyTa = document.getElementById('cf-funny-text');
        if (funnyTa) {
            funnyTa.addEventListener('blur', function() {
                tryAdvanceFromTextarea('cf-funny-text', 'funny', 'extra1');
            });
            funnyTa.addEventListener('input', function() {
                scheduleTextareaAdvance('cf-funny-text', 'funny', 'extra1', 420);
            });
        }
        var ex1Ta = document.getElementById('cf-extra1');
        if (ex1Ta) {
            ex1Ta.addEventListener('blur', function() {
                if (extra1Choice !== 'yes') return;
                tryAdvanceFromTextarea('cf-extra1', 'extra1', 'extra2');
            });
            ex1Ta.addEventListener('input', function() {
                if (extra1Choice !== 'yes') return;
                scheduleTextareaAdvance('cf-extra1', 'extra1', 'extra2', 420);
            });
        }
        var ex2Ta = document.getElementById('cf-extra2');
        if (ex2Ta) {
            ex2Ta.addEventListener('blur', function() {
                if (extra2Choice !== 'yes') return;
                tryAdvanceFromTextarea('cf-extra2', 'extra2', 'thoughts');
            });
            ex2Ta.addEventListener('input', function() {
                if (extra2Choice !== 'yes') return;
                scheduleTextareaAdvance('cf-extra2', 'extra2', 'thoughts', 420);
            });
        }
        var thoughtsTa = document.getElementById('cf-thoughts');
        if (thoughtsTa) {
            thoughtsTa.addEventListener('blur', function() {
                if (thoughtsChoice !== 'yes') return;
                tryAdvanceFromTextarea('cf-thoughts', 'thoughts', 'images');
            });
            thoughtsTa.addEventListener('input', function() {
                if (thoughtsChoice !== 'yes') return;
                scheduleTextareaAdvance('cf-thoughts', 'thoughts', 'images', 420);
            });
        }

        syncJoinAria();
        syncNav();
        updateProgress();
    });
})();
