/**
 * FAQ — скрипты для сайта (модуль)
 */
(function() {
    'use strict';

    function initFaq() {
        const module = document.querySelector('.faq-module');
        if (!module) return;

        const allowMultiple = module.dataset.allowMultiple === 'true';
        const items = module.querySelectorAll('.faq-item');

        items.forEach(function(item) {
            const question = item.querySelector('.faq-question');
            const answer = item.querySelector('.faq-answer');
            const icon = item.querySelector('.faq-icon');

            if (!question || !answer || !icon) return;

            function setAnswerHeight(el, open) {
                if (open) {
                    el.style.maxHeight = el.scrollHeight + 'px';
                    el.style.opacity = '1';
                } else {
                    el.style.maxHeight = '0';
                    el.style.opacity = '0';
                }
            }

            if (item.classList.contains('is-open')) {
                requestAnimationFrame(function() {
                    setAnswerHeight(answer, true);
                });
            }

            question.addEventListener('click', function() {
                const isOpen = item.classList.contains('is-open');

                if (!allowMultiple) {
                    items.forEach(function(other) {
                        if (other !== item && other.classList.contains('is-open')) {
                            other.classList.remove('is-open');
                            const otherAnswer = other.querySelector('.faq-answer');
                            const otherIcon = other.querySelector('.faq-icon');
                            if (otherAnswer) {
                                setAnswerHeight(otherAnswer, false);
                            }
                            if (otherIcon) {
                                otherIcon.classList.remove('rotate-45');
                            }
                        }
                    });
                }

                if (isOpen) {
                    item.classList.remove('is-open');
                    setAnswerHeight(answer, false);
                    icon.classList.remove('rotate-45');
                } else {
                    item.classList.add('is-open');
                    setAnswerHeight(answer, true);
                    icon.classList.add('rotate-45');
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFaq);
    } else {
        initFaq();
    }
})();