<!-- New Beginning — "Given your life to Christ?" connect form -->
<div class="nb-modal" id="new-beginning-modal" hidden aria-hidden="true">
    <div class="nb-modal-backdrop" data-nb-close tabindex="-1"></div>
    <div
        class="nb-modal-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="nb-modal-title"
    >
        <header class="nb-modal-header">
            <h2 class="nb-modal-heading" id="nb-modal-title">Given your life to Christ?</h2>
            <button type="button" class="nb-modal-close" data-nb-close aria-label="Close form">&times;</button>
        </header>

        <div class="nb-modal-body">
            <div id="nb-form-success" class="nb-form-success" hidden>
                <p class="nb-form-success-title">Thank you!</p>
                <p class="nb-form-success-text">Your response has been received. Our team will be in touch with you soon.</p>
            </div>

            <form id="new-beginning-form" class="nb-form" action="#" method="post" novalidate>
                <div class="nb-field">
                    <label class="nb-label" for="nb-campus">
                        Where do we serve you? <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <?php $campus_select_id = 'nb-campus'; require __DIR__ . '/campus-select.php'; ?>
                </div>

                <label class="nb-check-row">
                    <input class="nb-check-input" type="checkbox" name="first_time" value="yes">
                    <span class="nb-check-label">Is this your first time here?</span>
                </label>

                <div class="nb-field">
                    <label class="nb-label" for="nb-name">
                        Name <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <input class="nb-input" type="text" id="nb-name" name="name" required autocomplete="name">
                </div>

                <div class="nb-field">
                    <label class="nb-label" for="nb-phone">
                        Phone <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <input class="nb-input" type="tel" id="nb-phone" name="phone" required autocomplete="tel">
                </div>

                <div class="nb-field">
                    <label class="nb-label" for="nb-email">
                        Email <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <input class="nb-input" type="email" id="nb-email" name="email" required autocomplete="email">
                </div>

                <div class="nb-field">
                    <label class="nb-label" for="nb-age">
                        Age <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <select class="nb-input nb-select" id="nb-age" name="age_range" required>
                        <option value="" disabled selected>Select your age range</option>
                        <option value="under-18">Under 18</option>
                        <option value="18-24">18 – 24</option>
                        <option value="25-34">25 – 34</option>
                        <option value="35-44">35 – 44</option>
                        <option value="45-54">45 – 54</option>
                        <option value="55-64">55 – 64</option>
                        <option value="65-plus">65+</option>
                    </select>
                </div>

                <fieldset class="nb-fieldset">
                    <legend class="nb-label">
                        Gender <span class="nb-required" aria-hidden="true">*</span>
                    </legend>
                    <div class="nb-radio-group">
                        <label class="nb-check-row">
                            <input class="nb-check-input" type="radio" name="gender" value="male" required>
                            <span class="nb-check-label">Male</span>
                        </label>
                        <label class="nb-check-row">
                            <input class="nb-check-input" type="radio" name="gender" value="female">
                            <span class="nb-check-label">Female</span>
                        </label>
                    </div>
                </fieldset>

                <fieldset class="nb-fieldset">
                    <legend class="nb-label">Today I made the decision to:</legend>
                    <div class="nb-check-list">
                        <label class="nb-check-row">
                            <input class="nb-check-input nb-radio-square" type="radio" name="decision" value="first_time" required>
                            <span class="nb-check-label">Give my life to Christ for the first time</span>
                        </label>
                        <label class="nb-check-row">
                            <input class="nb-check-input nb-radio-square" type="radio" name="decision" value="rededicate">
                            <span class="nb-check-label">Rededicate my life to Christ</span>
                        </label>
                        <label class="nb-check-row">
                            <input class="nb-check-input nb-radio-square" type="radio" name="decision" value="child">
                            <span class="nb-check-label">My child gave their life to Christ</span>
                        </label>
                    </div>
                </fieldset>

                <label class="nb-check-row">
                    <input class="nb-check-input" type="checkbox" name="water_baptised" value="yes">
                    <span class="nb-check-label">Have you been water baptised before?</span>
                </label>

                <fieldset class="nb-fieldset">
                    <legend class="nb-label">Sign me up for:</legend>
                    <div class="nb-check-list">
                        <label class="nb-check-row">
                            <input class="nb-check-input" type="checkbox" name="signup[]" value="water_baptism">
                            <span class="nb-check-label">Water Baptism</span>
                        </label>
                        <label class="nb-check-row">
                            <input class="nb-check-input" type="checkbox" name="signup[]" value="kingdom_group">
                            <span class="nb-check-label">Kingdom Group</span>
                        </label>
                        <label class="nb-check-row">
                            <input class="nb-check-input" type="checkbox" name="signup[]" value="new_believers_class">
                            <span class="nb-check-label">New Believers Class</span>
                        </label>
                    </div>
                </fieldset>

                <p class="nb-disclaimer">
                    *By providing your email and phone number, you allow <?php echo htmlspecialchars($site_name); ?>
                    to contact you with more information about events and opportunities happening at our church.
                </p>

                <div class="nb-form-actions">
                    <button type="submit" class="nb-submit-btn">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
