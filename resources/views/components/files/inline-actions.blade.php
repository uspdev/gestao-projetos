@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (window.fileActionsInitialized) return;

      window.fileActionsInitialized = true;

      document.querySelectorAll('[data-file-upload-form]').forEach(function (form) {
        var input = form.querySelector('[data-file-upload-input]');
        var feedback = form.querySelector('[data-file-upload-feedback]');

        if (!input) return;

        input.addEventListener('change', function () {
          var count = input.files ? input.files.length : 0;
          if (!count) return;
          if (feedback) feedback.textContent = count === 1 ? 'Enviando 1 Arquivo.' : 'Enviando ' + count + ' Arquivos.';
          form.submit();
        });
      });

      document.querySelectorAll('[data-file-browser]').forEach(function (browser) {
        var tabs = Array.prototype.slice.call(browser.querySelectorAll('[data-file-tab]'));
        var panels = Array.prototype.slice.call(browser.querySelectorAll('[data-file-tab-panel]'));
        var placeholder = browser.querySelector('[data-file-details-placeholder]');
        var scrollRegion = browser.querySelector('[data-file-browser-scroll]');

        function updateScrollContainment() {
          if (!scrollRegion) return;

          scrollRegion.classList.toggle(
            'has-vertical-overflow',
            scrollRegion.scrollHeight > scrollRegion.clientHeight
          );
        }

        function clearDetails() {
          browser.querySelectorAll('[data-file-details]').forEach(function (details) {
            details.hidden = true;
          });
          browser.querySelectorAll('[data-file-card]').forEach(function (card) {
            card.classList.remove('is-selected');
          });

          if (placeholder) placeholder.hidden = false;
        }

        function selectTab(tabName, focusTab) {
          tabs.forEach(function (tab) {
            var isSelected = tab.getAttribute('data-file-tab') === tabName;
            tab.classList.toggle('active', isSelected);
            tab.setAttribute('aria-selected', String(isSelected));
            tab.setAttribute('tabindex', isSelected ? '0' : '-1');

            if (isSelected && focusTab) tab.focus();
          });

          panels.forEach(function (panel) {
            panel.classList.toggle('d-none', panel.getAttribute('data-file-tab-panel') !== tabName);
          });

          clearDetails();
          window.requestAnimationFrame(updateScrollContainment);
        }

        function showDetails(detailsId, focusDetails) {
          var details = detailsId ? document.getElementById(detailsId) : null;

          if (!details || !browser.contains(details)) return;

          browser.querySelectorAll('[data-file-details]').forEach(function (item) {
            item.hidden = item !== details;
          });
          browser.querySelectorAll('[data-file-card]').forEach(function (card) {
            var selector = card.querySelector('[data-file-select]');
            card.classList.toggle(
              'is-selected',
              selector && selector.getAttribute('data-file-details-id') === detailsId
            );
          });

          if (placeholder) placeholder.hidden = true;
          if (focusDetails) details.focus();
        }

        if (scrollRegion) {
          window.requestAnimationFrame(updateScrollContainment);

          if (window.jQuery) {
            window.jQuery(browser)
              .on('show.bs.dropdown', '[data-file-action]', function () {
                scrollRegion.classList.add('has-open-file-actions');
              })
              .on('hidden.bs.dropdown', '[data-file-action]', function () {
                scrollRegion.classList.remove('has-open-file-actions');
                window.requestAnimationFrame(updateScrollContainment);
              });
          }

          if ('ResizeObserver' in window) {
            var scrollObserver = new window.ResizeObserver(updateScrollContainment);

            scrollObserver.observe(scrollRegion);
            panels.forEach(function (panel) {
              scrollObserver.observe(panel);
            });
          } else {
            window.addEventListener('resize', updateScrollContainment);
          }
        }

        tabs.forEach(function (tab, index) {
          tab.addEventListener('click', function () {
            selectTab(tab.getAttribute('data-file-tab'), false);
          });

          tab.addEventListener('keydown', function (event) {
            if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;

            event.preventDefault();
            var direction = event.key === 'ArrowRight' ? 1 : -1;
            var nextIndex = (index + direction + tabs.length) % tabs.length;
            selectTab(tabs[nextIndex].getAttribute('data-file-tab'), true);
          });
        });

        browser.querySelectorAll('[data-file-select]').forEach(function (item) {
          item.addEventListener('click', function () {
            showDetails(item.getAttribute('data-file-details-id'), false);
          });
        });

        browser.querySelectorAll('[data-file-details-toggle], [data-file-preview-toggle]').forEach(function (toggle) {
          toggle.addEventListener('click', function () {
            showDetails(
              toggle.getAttribute('data-file-details-id'),
              toggle.hasAttribute('data-file-preview-toggle')
            );
          });
        });

        browser.querySelectorAll('[data-file-rename-toggle]').forEach(function (toggle) {
          var form = document.getElementById(toggle.getAttribute('aria-controls'));
          var card = toggle.closest('[data-file-card]');
          var region = card && card.querySelector('[data-file-edit-region]');

          if (!form || !region) return;

          var input = form.querySelector('[data-file-rename-input]');
          var cancel = form.querySelector('[data-file-rename-cancel]');

          function setRenameVisibility(isVisible) {
            if (isVisible) {
              browser.querySelectorAll('[data-file-rename-form], [data-link-edit-form]').forEach(function (otherForm) {
                if (otherForm !== form) otherForm.hidden = true;
              });
              browser.querySelectorAll('[data-file-rename-toggle]').forEach(function (otherToggle) {
                if (otherToggle !== toggle) otherToggle.setAttribute('aria-expanded', 'false');
              });
              browser.querySelectorAll('[data-link-edit-form]').forEach(function (otherForm) {
                otherForm.hidden = true;
              });
              browser.querySelectorAll('[data-link-edit-toggle]').forEach(function (otherToggle) {
                otherToggle.setAttribute('aria-expanded', 'false');
              });

              browser.querySelectorAll('[data-file-edit-region]').forEach(function (otherRegion) {
                otherRegion.hidden = otherRegion !== region;
              });

              region.appendChild(form);
              region.hidden = false;
            }

            form.hidden = !isVisible;
            toggle.setAttribute('aria-expanded', String(isVisible));

            if (isVisible && input) {
              input.focus();
              input.select();
            }

            if (!isVisible) {
              if (input) input.value = input.defaultValue;
              region.hidden = true;
            }
          }

          toggle.addEventListener('click', function () {
            setRenameVisibility(true);
          });

          form.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;

            event.preventDefault();
            setRenameVisibility(false);
            toggle.focus();
          });

          if (cancel) {
            cancel.addEventListener('click', function () {
              setRenameVisibility(false);
              toggle.focus();
            });
          }
        });
      });

      document.querySelectorAll('[data-link-edit-toggle]').forEach(function (toggle) {
        var form = document.getElementById(toggle.getAttribute('aria-controls'));
        if (!form) return;

        var browser = toggle.closest('[data-file-browser]');
        var card = toggle.closest('[data-link-card]');
        var region = card && card.querySelector('[data-file-edit-region]');
        if (!region) return;

        var cancel = form.querySelector('[data-link-edit-cancel]');

        function setLinkEditVisibility(isVisible) {
          if (isVisible) {
            browser.querySelectorAll('[data-file-rename-form], [data-link-edit-form]').forEach(function (other) {
              if (other !== form) other.hidden = true;
            });
            browser.querySelectorAll('[data-link-edit-toggle]').forEach(function (otherToggle) {
              if (otherToggle !== toggle) otherToggle.setAttribute('aria-expanded', 'false');
            });
            browser.querySelectorAll('[data-file-rename-form]').forEach(function (otherForm) {
              otherForm.hidden = true;
            });
            browser.querySelectorAll('[data-file-rename-toggle]').forEach(function (otherToggle) {
              otherToggle.setAttribute('aria-expanded', 'false');
            });

            browser.querySelectorAll('[data-file-edit-region]').forEach(function (otherRegion) {
              otherRegion.hidden = otherRegion !== region;
            });

            region.appendChild(form);
            region.hidden = false;
          }

          form.hidden = !isVisible;
          toggle.setAttribute('aria-expanded', String(isVisible));

          if (isVisible) {
            var input = form.querySelector('input[name=name]');
            if (input) {
              input.focus();
              input.select();
            }
          }

          if (!isVisible) {
            form.querySelectorAll('input').forEach(function (input) {
              input.value = input.defaultValue;
            });
            region.hidden = true;
          }
        }

        toggle.addEventListener('click', function () {
          setLinkEditVisibility(true);
        });

        form.addEventListener('keydown', function (event) {
          if (event.key !== 'Escape') return;

          event.preventDefault();
          setLinkEditVisibility(false);
          toggle.focus();
        });

        if (cancel) {
          cancel.addEventListener('click', function () {
            setLinkEditVisibility(false);
            toggle.focus();
          });
        }
      });
    });
  </script>
@endpush
