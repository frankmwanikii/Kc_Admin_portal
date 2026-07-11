<!-- Kingdom Groups — "Want to plug in?" connect form -->
<div class="nb-modal" id="kingdom-groups-modal" hidden aria-hidden="true">
    <div class="nb-modal-backdrop" data-kg-close tabindex="-1"></div>
    <div
        class="nb-modal-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="kg-modal-title"
    >
        <header class="nb-modal-header">
            <h2 class="nb-modal-heading" id="kg-modal-title">Want to plug in?</h2>
            <button type="button" class="nb-modal-close" data-kg-close aria-label="Close form">&times;</button>
        </header>

        <div class="nb-modal-body">
            <div id="kg-form-success" class="nb-form-success" hidden>
                <p class="nb-form-success-title">Thank you!</p>
                <p class="nb-form-success-text">Your response has been received. Our team will be in touch with you soon.</p>
            </div>

            <form id="kingdom-groups-form" class="nb-form" action="#" method="post" novalidate>
                <div class="nb-field">
                    <label class="nb-label" for="kg-campus">
                        Where do we serve you? <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <?php $campus_select_id = 'kg-campus'; require __DIR__ . '/campus-select.php'; ?>
                </div>

                <div class="nb-field">
                    <label class="nb-label" for="kg-ministry">
                        Ministry Interest <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <select class="nb-input nb-select" id="kg-ministry" name="ministry_interest" required>
                        <option value="" disabled selected>Select your ministry interest</option>
                        <option value="kingdom_group">Kingdom Group</option>
                        <?php foreach ($ministries_list as $ministry): ?>
                        <option value="<?php echo htmlspecialchars($ministry['slug']); ?>">
                            <?php echo htmlspecialchars($ministry['title']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <p class="nb-form-section-title">Sign Up to Join a Kingdom Group:</p>

                <div class="nb-field">
                    <label class="nb-label" for="kg-name">
                        Name <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <input class="nb-input" type="text" id="kg-name" name="name" required autocomplete="name">
                </div>

                <div class="nb-field">
                    <label class="nb-label" for="kg-phone">
                        Phone <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <input class="nb-input" type="tel" id="kg-phone" name="phone" required autocomplete="tel">
                </div>

                <div class="nb-field">
                    <label class="nb-label" for="kg-email">
                        Email <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <input class="nb-input" type="email" id="kg-email" name="email" required autocomplete="email">
                </div>

                <div class="nb-field">
                    <label class="nb-label" for="kg-age">
                        Age <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <select class="nb-input nb-select" id="kg-age" name="age_range" required>
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

                <label class="nb-check-row">
                    <input class="nb-check-input" type="checkbox" name="speak_to_pastor" value="yes">
                    <span class="nb-check-label">Want to speak to a pastor?</span>
                </label>

                <div class="nb-field">
                    <label class="nb-label" for="kg-address">Address</label>
                    <input class="nb-input" type="text" id="kg-address" name="address" autocomplete="street-address">
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
