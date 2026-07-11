<!-- New Here — first-time visitor connect form -->
<div class="nb-modal" id="new-here-modal" hidden aria-hidden="true">
    <div class="nb-modal-backdrop" data-nh-close tabindex="-1"></div>
    <div
        class="nb-modal-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="nh-modal-title"
    >
        <header class="nb-modal-header">
            <h2 class="nb-modal-heading" id="nh-modal-title">So great to have you with us today!</h2>
            <button type="button" class="nb-modal-close" data-nh-close aria-label="Close form">&times;</button>
        </header>

        <div class="nb-modal-body">
            <div id="nh-form-success" class="nb-form-success" hidden>
                <p class="nb-form-success-title">Thank you!</p>
                <p class="nb-form-success-text">Your response has been received. Our team will be in touch with you soon.</p>
            </div>

            <form id="new-here-form" class="nb-form" action="#" method="post" novalidate>
                <div class="nb-field">
                    <label class="nb-label" for="nh-campus">
                        Where do we serve you? <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <?php $campus_select_id = 'nh-campus'; require __DIR__ . '/campus-select.php'; ?>
                </div>

                <p class="nb-form-intro">
                    We'd love to learn more about you and help you find your place in our church family because here,
                    you belong. Welcome home!
                </p>

                <label class="nb-check-row">
                    <input class="nb-check-input" type="checkbox" name="first_time" value="yes">
                    <span class="nb-check-label">Is this your first time here?</span>
                </label>

                <div class="nb-field">
                    <label class="nb-label" for="nh-name">
                        Name <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <input class="nb-input" type="text" id="nh-name" name="name" required autocomplete="name">
                </div>

                <div class="nb-field">
                    <label class="nb-label" for="nh-phone">
                        Phone <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <input class="nb-input" type="tel" id="nh-phone" name="phone" required autocomplete="tel">
                </div>

                <div class="nb-field">
                    <label class="nb-label" for="nh-email">
                        Email <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <input class="nb-input" type="email" id="nh-email" name="email" required autocomplete="email">
                </div>

                <div class="nb-field">
                    <label class="nb-label" for="nh-age">
                        Age <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <select class="nb-input nb-select" id="nh-age" name="age_range" required>
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

                <div class="nb-field">
                    <label class="nb-label" for="nh-marital">Marital Status (optional)</label>
                    <input class="nb-input" type="text" id="nh-marital" name="marital_status" autocomplete="off">
                </div>

                <div class="nb-field">
                    <label class="nb-label" for="nh-heard">
                        How did you hear about us? <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <select class="nb-input nb-select" id="nh-heard" name="heard_about" required>
                        <option value="" disabled selected>Select an option</option>
                        <option value="social_media">Social media</option>
                        <option value="youtube_livestream">YouTube or livestream</option>
                        <option value="outdoor_sign">Saw an outdoor sign or banner</option>
                        <option value="friend_family">Invited by a friend or family member</option>
                        <option value="church_member">Invited by a church member</option>
                        <option value="drove_walked_by">Drove or walked by the building</option>
                    </select>
                </div>

                <div class="nb-field">
                    <label class="nb-label" for="nh-experience">How was your experience with us?</label>
                    <textarea class="nb-input nb-textarea" id="nh-experience" name="experience" rows="3" placeholder="Share your experience (optional)"></textarea>
                </div>

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
