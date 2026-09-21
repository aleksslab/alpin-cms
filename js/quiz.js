/**
 * Квиз — скрипты для сайта
 */
(function() {
    'use strict';

    function initQuiz() {
        var container = document.querySelector('.quiz-container');
        if (!container) return;

        var questions = container.querySelectorAll('.quiz-question');
        var total = questions.length;
        var prevBtn = container.querySelector('.quiz-prev');
        var nextBtn = container.querySelector('.quiz-next');
        var progressBar = container.querySelector('.quiz-progress-bar');
        var progressText = container.querySelector('.quiz-progress-text');
        var currentSpan = container.querySelector('.quiz-current');
        var totalSpan = container.querySelector('.quiz-total');
        var resultsContainer = container.querySelector('.quiz-results');
        var csrfToken = container.dataset.csrf || '';
        var successMessage = container.dataset.successMessage || '';

        var currentStep = 0;

        function updateProgress() {
            var percent = 0;
            if (currentStep > 0) {
                percent = (currentStep / total) * 100;
            }
            if (currentStep === total) {
                percent = 100;
            }
            
            if (progressBar) progressBar.style.width = percent + '%';
            if (progressText) progressText.textContent = Math.round(percent) + '%';
            if (currentSpan) currentSpan.textContent = Math.min(currentStep + 1, total);
            if (totalSpan) totalSpan.textContent = total;

            questions.forEach(function(q, index) {
                if (index === currentStep) {
                    q.classList.add('active');
                    q.classList.remove('hidden');
                } else {
                    q.classList.remove('active');
                    q.classList.add('hidden');
                }
            });

            if (prevBtn) {
                prevBtn.disabled = currentStep === 0;
            }
            if (nextBtn) {
                if (currentStep === total - 1) {
                    nextBtn.textContent = 'Результат';
                } else {
                    nextBtn.textContent = 'Далее';
                }
            }
        }

        function getCurrentQuestion() {
            return questions[currentStep];
        }

        function isQuestionAnswered() {
            var q = getCurrentQuestion();
            if (!q) return true;

            var required = q.dataset.required === 'true';
            if (!required) return true;

            var radios = q.querySelectorAll('.quiz-radio:checked');
            var checkboxes = q.querySelectorAll('.quiz-checkbox:checked');
            var textarea = q.querySelector('textarea');

            if (radios.length > 0) return true;
            if (checkboxes.length > 0) return true;
            if (textarea && textarea.value.trim() !== '') return true;

            return false;
        }

        function showError() {
            var q = getCurrentQuestion();
            if (!q) return;

            var radios = q.querySelectorAll('.quiz-radio');
            var checkboxes = q.querySelectorAll('.quiz-checkbox');
            var textarea = q.querySelector('textarea');

            var hasEmpty = false;

            if (radios.length > 0) {
                var checked = q.querySelectorAll('.quiz-radio:checked');
                if (checked.length === 0) {
                    hasEmpty = true;
                    q.querySelectorAll('.border-slate-200').forEach(function(el) {
                        el.style.borderColor = '#f43f5e';
                    });
                }
            }

            if (checkboxes.length > 0) {
                var checked = q.querySelectorAll('.quiz-checkbox:checked');
                if (checked.length === 0) {
                    hasEmpty = true;
                }
            }

            if (textarea && textarea.value.trim() === '') {
                hasEmpty = true;
                textarea.style.borderColor = '#f43f5e';
            }

            if (hasEmpty) {
                var existing = q.querySelector('.quiz-error');
                if (!existing) {
                    var err = document.createElement('p');
                    err.className = 'quiz-error text-rose-500 text-sm mt-3';
                    err.textContent = 'Пожалуйста, ответьте на вопрос';
                    q.appendChild(err);
                    setTimeout(function() {
                        if (err.parentNode) err.remove();
                    }, 3000);
                }
            }
        }

        function goToStep(step) {
            if (step < 0 || step >= total) return;
            currentStep = step;
            updateProgress();
            document.querySelectorAll('.border-rose-500').forEach(function(el) {
                el.classList.remove('border-rose-500');
            });
            document.querySelectorAll('.quiz-error').forEach(function(el) {
                el.remove();
            });
        }

        function nextStep() {
            if (!isQuestionAnswered()) {
                showError();
                return;
            }

            if (currentStep === total - 1) {
                var allAnswered = true;
                questions.forEach(function(q, index) {
                    var required = q.dataset.required === 'true';
                    if (required) {
                        var radios = q.querySelectorAll('.quiz-radio:checked');
                        var checkboxes = q.querySelectorAll('.quiz-checkbox:checked');
                        var textarea = q.querySelector('textarea');
                        
                        if (radios.length === 0 && checkboxes.length === 0 && (!textarea || textarea.value.trim() === '')) {
                            allAnswered = false;
                            q.querySelectorAll('.border-slate-200').forEach(function(el) {
                                el.style.borderColor = '#f43f5e';
                            });
                        }
                    }
                });
                
                if (!allAnswered) {
                    return;
                }
                
                showResults();
                return;
            }

            goToStep(currentStep + 1);
        }

        function showResults() {
            var answers = {};
            questions.forEach(function(q, index) {
                var radios = q.querySelectorAll('.quiz-radio:checked');
                var checkboxes = q.querySelectorAll('.quiz-checkbox:checked');
                var textarea = q.querySelector('textarea');

                if (radios.length > 0) {
                    answers['q' + (index + 1)] = radios[0].value;
                } else if (checkboxes.length > 0) {
                    var vals = [];
                    checkboxes.forEach(function(cb) {
                        vals.push(cb.value);
                    });
                    answers['q' + (index + 1)] = vals;
                } else if (textarea) {
                    answers['q' + (index + 1)] = textarea.value;
                }
            });

            container.dataset.answers = JSON.stringify(answers);

            questions.forEach(function(q) {
                q.classList.add('hidden');
                q.classList.remove('active');
            });
            if (prevBtn) prevBtn.style.display = 'none';
            if (nextBtn) nextBtn.style.display = 'none';
            if (resultsContainer) resultsContainer.classList.remove('hidden');

            if (progressBar) progressBar.style.width = '100%';
            if (progressText) progressText.textContent = '100%';
        }

        // ===== КНОПКИ НАВИГАЦИИ =====
        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                goToStep(currentStep - 1);
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', nextStep);
        }

        container.querySelectorAll('textarea').forEach(function(textarea) {
            textarea.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    nextStep();
                }
            });
        });

        // ===== ОТПРАВКА ФОРМЫ =====
        var form = container.querySelector('#quiz-form');
        // Сохраняем оригинальный HTML формы для восстановления
        var formHtml = form ? form.outerHTML : '';

        // Функция для привязки обработчика к форме
        function attachFormHandler(formElement) {
            if (!formElement) return;
            
            // Убираем старые обработчики
            var newForm = formElement.cloneNode(true);
            formElement.parentNode.replaceChild(newForm, formElement);
            
            newForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // Проверяем чекбокс
                var privacyCheckbox = this.querySelector('input[name="privacy_policy"]');
                if (privacyCheckbox && !privacyCheckbox.checked) {
                    privacyCheckbox.setCustomValidity('Подтвердите согласие с политикой конфиденциальности');
                    privacyCheckbox.reportValidity();
                    return;
                }

                // Получаем данные формы
                var formData = new FormData(this);
                var fields = this.querySelectorAll('input:not([type="checkbox"])');
                var data = {};
                
                // Добавляем CSRF токен и модуль
                formData.append('csrf_token', csrfToken);
                formData.append('module', 'quiz');
                formData.append('title', container.dataset.title || 'Заявка с квиза');
                
                // Добавляем success_message из data-атрибута
                if (successMessage) {
                    formData.append('success_message', successMessage);
                }

                try {
                    var answers = JSON.parse(container.dataset.answers || '{}');
                    data.answers = answers;
                    formData.append('answers', JSON.stringify(answers));
                } catch(e) {
                    data.answers = {};
                }

                var submitBtn = this.querySelector('.quiz-submit');
                var fieldsContainer = this;
                var resultsContainerEl = container.querySelector('.quiz-results .bg-white');
                
                // Сохраняем форму для восстановления
                var savedFormHtml = fieldsContainer.outerHTML;
                
                // Скрываем форму
                fieldsContainer.style.display = 'none';
                if (submitBtn) submitBtn.style.display = 'none';
                
                // Показываем лоадер
                var loader = document.createElement('div');
                loader.id = 'quiz-loading';
                loader.className = 'w-full py-12 flex flex-col items-center justify-center text-center space-y-4';
                loader.innerHTML = `
                    <span class="loader"></span>
                    <p class="text-slate-500 font-medium text-sm tracking-wide uppercase">Отправка...</p>
                `;
                if (resultsContainerEl) {
                    resultsContainerEl.appendChild(loader);
                }
                
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Отправка...';
                }

                fetch('/includes/mailer.php', {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(result) {
                    var loaderEl = document.getElementById('quiz-loading');
                    if (loaderEl) loaderEl.remove();
                    
                    var oldMessages = container.querySelectorAll('.form-messages');
                    oldMessages.forEach(function(el) {
                        el.remove();
                    });
                    
                    var isSuccess = result.status === 'success';
                    var borderClass = isSuccess ? 'border-emerald-100' : 'border-slate-200';
                    var icon = isSuccess ? 'icon-shield-check text-emerald-500' : 'icon-x text-slate-400';
                    var textClass = isSuccess ? 'text-[var(--primary-dark)]' : 'text-slate-800';

                    var messageText = successMessage || result.message;

                    var msg = document.createElement('div');
                    msg.className = 'form-messages w-full bg-white border ' + borderClass + ' p-8 rounded-2xl flex flex-col items-center justify-center text-center space-y-4 shadow-sm animate-fade-in';
                    msg.innerHTML = `
                        <div class="w-14 h-14 rounded-full bg-slate-50 flex items-center justify-center shadow-inner">
                            <span class="${icon} text-2xl"></span>
                        </div>
                        <h4 class="text-xl font-bold ${textClass}">${result.head}</h4>
                        <p class="text-slate-600 text-sm font-medium leading-relaxed max-w-sm">${messageText}</p>
                    `;
                    
                    if (resultsContainerEl) {
                        resultsContainerEl.innerHTML = '';
                        resultsContainerEl.appendChild(msg);
                    }
                    
                    if (isSuccess) {
                        this.querySelectorAll('input').forEach(function(input) {
                            input.value = '';
                        });
                        
                        setTimeout(function() {
                            var msgEl = container.querySelector('.form-messages');
                            if (msgEl) msgEl.remove();
                            
                            if (resultsContainer) resultsContainer.classList.add('hidden');
                            
                            currentStep = 0;
                            
                            questions.forEach(function(q, index) {
                                q.classList.add('hidden');
                                q.classList.remove('active');
                                if (index === 0) {
                                    q.classList.remove('hidden');
                                    q.classList.add('active');
                                }
                            });
                            
                            container.querySelectorAll('input[type="radio"], input[type="checkbox"]').forEach(function(input) {
                                input.checked = false;
                            });
                            
                            container.querySelectorAll('textarea').forEach(function(textarea) {
                                textarea.value = '';
                            });
                            
                            if (prevBtn) {
                                prevBtn.style.display = '';
                                prevBtn.disabled = true;
                            }
                            if (nextBtn) {
                                nextBtn.style.display = '';
                                nextBtn.textContent = 'Далее';
                                nextBtn.disabled = false;
                            }
                            
                            if (progressBar) progressBar.style.width = '0%';
                            if (progressText) progressText.textContent = '0%';
                            if (currentSpan) currentSpan.textContent = '1';
                            
                            // Восстанавливаем форму из сохраненного HTML и привязываем обработчик
                            if (resultsContainerEl) {
                                resultsContainerEl.innerHTML = savedFormHtml;
                                var newForm = resultsContainerEl.querySelector('#quiz-form');
                                if (newForm) {
                                    attachFormHandler(newForm);
                                }
                            }
                        }, 10000);
                    } else {
                        // Показываем форму обратно
                        fieldsContainer.style.display = '';
                        if (submitBtn) {
                            submitBtn.style.display = '';
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Отправить';
                        }
                    }
                }.bind(this))
                .catch(function() {
                    var loaderEl = document.getElementById('quiz-loading');
                    if (loaderEl) loaderEl.remove();
                    
                    var oldMessages = container.querySelectorAll('.form-messages');
                    oldMessages.forEach(function(el) {
                        el.remove();
                    });
                    
                    var msg = document.createElement('div');
                    msg.className = 'form-messages w-full bg-white border border-slate-200 p-8 rounded-2xl flex flex-col items-center justify-center text-center space-y-4 shadow-sm';
                    msg.innerHTML = `
                        <div class="w-14 h-14 rounded-full bg-slate-50 flex items-center justify-center shadow-inner">
                            <span class="icon-x text-rose-500 text-2xl"></span>
                        </div>
                        <h4 class="text-xl font-bold text-slate-800">Ошибка соединения</h4>
                        <p class="text-slate-600 text-sm font-medium leading-relaxed max-w-sm">Не удалось отправить обращение. Проверьте интернет или попробуйте позже.</p>
                    `;
                    
                    var resultsContainerEl = container.querySelector('.quiz-results .bg-white');
                    if (resultsContainerEl) {
                        resultsContainerEl.innerHTML = '';
                        resultsContainerEl.appendChild(msg);
                    }
                    
                    fieldsContainer.style.display = '';
                    if (submitBtn) {
                        submitBtn.style.display = '';
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Отправить';
                    }
                }.bind(this));
            });
        }

        // Привязываем обработчик к форме
        if (form) {
            attachFormHandler(form);
        }

        // Инициализация
        currentStep = 0;
        updateProgress();
        questions.forEach(function(q, index) {
            if (index !== 0) {
                q.classList.add('hidden');
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initQuiz);
    } else {
        initQuiz();
    }
})();