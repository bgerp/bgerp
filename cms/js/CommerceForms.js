/** Password visibility control for the commerce login form. */
function initCommerceLogin(showLabel, hideLabel) {
    var input = document.querySelector('body.commerce-theme #login-form input[name="pass"]');
    if (!input || input.parentNode.classList.contains('commerce-password')) return;

    var holder = document.createElement('div');
    holder.className = 'commerce-password';
    input.parentNode.insertBefore(holder, input);
    holder.appendChild(input);

    var toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'commerce-password-toggle';
    toggle.setAttribute('aria-label', showLabel);
    toggle.setAttribute('aria-pressed', 'false');
    toggle.title = showLabel;
    toggle.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path class="commerce-eye-slash" d="m4 4 16 16"/></svg>';
    toggle.addEventListener('click', function () {
        var visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', visible ? 'true' : 'false');
        toggle.setAttribute('aria-label', visible ? hideLabel : showLabel);
        toggle.title = visible ? hideLabel : showLabel;
    });
    holder.appendChild(toggle);
}
