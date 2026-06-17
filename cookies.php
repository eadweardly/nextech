<?php
$hasConsent = isset($_COOKIE['cookie_consent']);
$dismissed  = isset($_COOKIE['cookie_consent_dismissed']);

if (!$hasConsent && !$dismissed):
?>
<div class="cookie-consent" id="cookie-consent" role="dialog" aria-live="polite" aria-label="Cookie settings">
    <button type="button" class="cookie-close" id="cookie-close" aria-label="Close">
        <i class="bi bi-x-lg"></i>
    </button>

    <div class="cookie-default-view" id="cookie-default-view">
        <h2 class="cookie-title">Cookies Settings</h2>
        <p class="cookie-text">
            We use cookies and similar technologies to help personalize content, tailor and measure ads, and provide a better experience. By clicking accept, you agree to this, as outlined in our
            <a href="cookiepolicy.php" class="cookie-link">Cookie Policy</a>.
        </p>
        <div class="cookie-actions">
            <button type="button" class="cookie-btn cookie-btn-accept" id="cookie-accept">Accept</button>
            <button type="button" class="cookie-btn cookie-btn-preferences" id="cookie-open-preferences">Preferences</button>
        </div>
    </div>

    <div class="cookie-preferences-view" id="cookie-preferences-view" hidden>
        <h2 class="cookie-title">Manage Preferences</h2>

        <div class="cookie-option">
            <div class="cookie-option-text">
                <span class="cookie-option-title">Necessary</span>
                <span class="cookie-option-desc">Required for the site to function. Always on.</span>
            </div>
            <label class="cookie-switch cookie-switch-disabled">
                <input type="checkbox" checked disabled>
                <span class="cookie-slider"></span>
            </label>
        </div>

        <div class="cookie-option">
            <div class="cookie-option-text">
                <span class="cookie-option-title">Analytics</span>
                <span class="cookie-option-desc">Helps us understand how visitors use the site.</span>
            </div>
            <label class="cookie-switch">
                <input type="checkbox" id="cookie-analytics">
                <span class="cookie-slider"></span>
            </label>
        </div>

        <div class="cookie-option">
            <div class="cookie-option-text">
                <span class="cookie-option-title">Marketing</span>
                <span class="cookie-option-desc">Used to personalize ads and measure performance.</span>
            </div>
            <label class="cookie-switch">
                <input type="checkbox" id="cookie-marketing">
                <span class="cookie-slider"></span>
            </label>
        </div>

        <div class="cookie-actions">
            <button type="button" class="cookie-btn cookie-btn-accept" id="cookie-save-preferences">Save Preferences</button>
            <button type="button" class="cookie-btn cookie-btn-preferences" id="cookie-back">Back</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const banner = document.getElementById('cookie-consent');
    if (!banner) return;

    const defaultView    = document.getElementById('cookie-default-view');
    const prefsView      = document.getElementById('cookie-preferences-view');
    const closeBtn        = document.getElementById('cookie-close');
    const acceptBtn       = document.getElementById('cookie-accept');
    const openPrefsBtn    = document.getElementById('cookie-open-preferences');
    const backBtn         = document.getElementById('cookie-back');
    const savePrefsBtn    = document.getElementById('cookie-save-preferences');
    const analyticsInput  = document.getElementById('cookie-analytics');
    const marketingInput  = document.getElementById('cookie-marketing');

    function hideBanner() {
        banner.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
        banner.style.opacity = '0';
        banner.style.transform = 'translateY(12px)';
        setTimeout(function () { banner.remove(); }, 250);
    }

    function saveConsent(payload) {
        fetch('consent.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).catch(function (err) {
            console.error('Could not save cookie preference:', err);
        });
    }

    if (openPrefsBtn) {
        openPrefsBtn.addEventListener('click', function () {
            defaultView.hidden = true;
            prefsView.hidden = false;
        });
    }

    if (backBtn) {
        backBtn.addEventListener('click', function () {
            prefsView.hidden = true;
            defaultView.hidden = false;
        });
    }

    if (acceptBtn) {
        acceptBtn.addEventListener('click', function () {
            saveConsent({ analytics: true, marketing: true });
            hideBanner();
        });
    }

    if (savePrefsBtn) {
        savePrefsBtn.addEventListener('click', function () {
            saveConsent({
                analytics: !!(analyticsInput && analyticsInput.checked),
                marketing: !!(marketingInput && marketingInput.checked)
            });
            hideBanner();
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            saveConsent({ dismissOnly: true });
            hideBanner();
        });
    }
});
</script>
<?php endif; ?>