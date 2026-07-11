<!-- Join — covenant membership application -->
<div class="nb-modal" id="join-modal" hidden aria-hidden="true">
    <div class="nb-modal-backdrop" data-join-close tabindex="-1"></div>
    <div
        class="nb-modal-dialog nb-modal-dialog--wide"
        role="dialog"
        aria-modal="true"
        aria-labelledby="join-modal-title"
    >
        <header class="nb-modal-header">
            <h2 class="nb-modal-heading" id="join-modal-title">Join Our Church Family</h2>
            <button type="button" class="nb-modal-close" data-join-close aria-label="Close form">&times;</button>
        </header>

        <div class="nb-modal-body">
            <div id="join-form-success" class="nb-form-success" hidden>
                <p class="nb-form-success-title">Thank you!</p>
                <p class="nb-form-success-text">
                    Your membership application has been received. A leader will contact you to walk you through the next steps.
                </p>
            </div>

            <form id="join-form" class="nb-form" action="#" method="post" novalidate>
                <p class="nb-form-intro">
                    Covenant membership is a commitment to Christ, His church, and His mission. Please complete every
                    section below so we can care for you and your household well.
                </p>

                <p class="nb-form-section-title">Church &amp; Contact</p>

                <div class="nb-field">
                    <label class="nb-label" for="join-campus">
                        Where do we serve you? <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <?php $campus_select_id = 'join-campus'; require __DIR__ . '/campus-select.php'; ?>
                </div>

                <div class="nb-field">
                    <label class="nb-label" for="join-name">
                        Full legal name <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <input class="nb-input" type="text" id="join-name" name="name" required autocomplete="name">
                </div>

                <div class="nb-field">
                    <label class="nb-label" for="join-phone">
                        Phone <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <input class="nb-input" type="tel" id="join-phone" name="phone" required autocomplete="tel">
                </div>

                <div class="nb-field">
                    <label class="nb-label" for="join-email">
                        Email <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <input class="nb-input" type="email" id="join-email" name="email" required autocomplete="email">
                </div>

                <div class="nb-field">
                    <label class="nb-label" for="join-dob">
                        Date of birth <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <input class="nb-input" type="date" id="join-dob" name="date_of_birth" required>
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
                    <label class="nb-label" for="join-marital">
                        Marital status <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <select class="nb-input nb-select" id="join-marital" name="marital_status" required>
                        <option value="" disabled selected>Select marital status</option>
                        <option value="single">Single</option>
                        <option value="married">Married</option>
                        <option value="divorced">Divorced</option>
                        <option value="widowed">Widowed</option>
                        <option value="separated">Separated</option>
                    </select>
                </div>

                <div class="nb-field">
                    <label class="nb-label" for="join-address">
                        Residential address <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <input class="nb-input" type="text" id="join-address" name="address" required autocomplete="street-address">
                </div>

                <div class="nb-field">
                    <label class="nb-label" for="join-attending">
                        How long have you been attending <?php echo htmlspecialchars($site_name); ?>?
                        <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <select class="nb-input nb-select" id="join-attending" name="attending_duration" required>
                        <option value="" disabled selected>Select duration</option>
                        <option value="first-time">This is my first time / I am new</option>
                        <option value="under-3-months">Less than 3 months</option>
                        <option value="3-6-months">3 – 6 months</option>
                        <option value="6-12-months">6 – 12 months</option>
                        <option value="over-1-year">Over 1 year</option>
                    </select>
                </div>

                <p class="nb-form-section-title">Family &amp; Household</p>

                <fieldset class="nb-fieldset">
                    <legend class="nb-label">
                        Do you have a spouse or partner? <span class="nb-required" aria-hidden="true">*</span>
                    </legend>
                    <div class="nb-radio-group">
                        <label class="nb-check-row">
                            <input class="nb-check-input" type="radio" name="has_spouse" value="yes" required data-join-toggle="spouse-fields">
                            <span class="nb-check-label">Yes</span>
                        </label>
                        <label class="nb-check-row">
                            <input class="nb-check-input" type="radio" name="has_spouse" value="no" data-join-toggle="spouse-fields">
                            <span class="nb-check-label">No</span>
                        </label>
                    </div>
                </fieldset>

                <div class="nb-conditional" id="join-spouse-fields" hidden>
                    <div class="nb-field">
                        <label class="nb-label" for="join-spouse-name">
                            Spouse / partner full name <span class="nb-required" aria-hidden="true">*</span>
                        </label>
                        <input class="nb-input" type="text" id="join-spouse-name" name="spouse_name" data-join-required-when="has_spouse:yes">
                    </div>
                    <div class="nb-field">
                        <label class="nb-label" for="join-spouse-phone">Spouse / partner phone</label>
                        <input class="nb-input" type="tel" id="join-spouse-phone" name="spouse_phone" autocomplete="tel">
                    </div>
                    <div class="nb-field">
                        <label class="nb-label" for="join-spouse-email">Spouse / partner email</label>
                        <input class="nb-input" type="email" id="join-spouse-email" name="spouse_email" autocomplete="email">
                    </div>
                    <label class="nb-check-row">
                        <input class="nb-check-input" type="checkbox" name="spouse_attends" value="yes">
                        <span class="nb-check-label">My spouse / partner also attends this church</span>
                    </label>
                </div>

                <fieldset class="nb-fieldset">
                    <legend class="nb-label">
                        Do you have children? <span class="nb-required" aria-hidden="true">*</span>
                    </legend>
                    <div class="nb-radio-group">
                        <label class="nb-check-row">
                            <input class="nb-check-input" type="radio" name="has_children" value="yes" required data-join-toggle="children-fields">
                            <span class="nb-check-label">Yes</span>
                        </label>
                        <label class="nb-check-row">
                            <input class="nb-check-input" type="radio" name="has_children" value="no" data-join-toggle="children-fields">
                            <span class="nb-check-label">No</span>
                        </label>
                    </div>
                </fieldset>

                <div class="nb-conditional" id="join-children-fields" hidden>
                    <div class="nb-field">
                        <label class="nb-label" for="join-children-details">
                            List all children (full names and ages) <span class="nb-required" aria-hidden="true">*</span>
                        </label>
                        <textarea
                            class="nb-input nb-textarea"
                            id="join-children-details"
                            name="children_details"
                            rows="3"
                            placeholder="e.g. Jane Doe, 8; John Doe, 12"
                            data-join-required-when="has_children:yes"
                        ></textarea>
                    </div>
                    <label class="nb-check-row">
                        <input class="nb-check-input" type="checkbox" name="children_attend" value="yes">
                        <span class="nb-check-label">My children also attend church programmes here (K-Kids, K-Teens, etc.)</span>
                    </label>
                </div>

                <fieldset class="nb-fieldset">
                    <legend class="nb-label">
                        Do you have other dependents living in your household?
                        <span class="nb-required" aria-hidden="true">*</span>
                    </legend>
                    <div class="nb-radio-group">
                        <label class="nb-check-row">
                            <input class="nb-check-input" type="radio" name="has_dependents" value="yes" required data-join-toggle="dependents-fields">
                            <span class="nb-check-label">Yes</span>
                        </label>
                        <label class="nb-check-row">
                            <input class="nb-check-input" type="radio" name="has_dependents" value="no" data-join-toggle="dependents-fields">
                            <span class="nb-check-label">No</span>
                        </label>
                    </div>
                </fieldset>

                <div class="nb-conditional" id="join-dependents-fields" hidden>
                    <div class="nb-field">
                        <label class="nb-label" for="join-dependents-details">
                            Who are they and how are they related to you?
                            <span class="nb-required" aria-hidden="true">*</span>
                        </label>
                        <textarea
                            class="nb-input nb-textarea"
                            id="join-dependents-details"
                            name="dependents_details"
                            rows="2"
                            data-join-required-when="has_dependents:yes"
                        ></textarea>
                    </div>
                </div>

                <div class="nb-field">
                    <label class="nb-label" for="join-household-size">
                        Total number of people in your household (including you)
                        <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <input class="nb-input" type="number" id="join-household-size" name="household_size" min="1" max="30" required>
                </div>

                <p class="nb-form-section-title">Faith Background</p>

                <fieldset class="nb-fieldset">
                    <legend class="nb-label">
                        Have you given your life to Jesus Christ? <span class="nb-required" aria-hidden="true">*</span>
                    </legend>
                    <div class="nb-radio-group">
                        <label class="nb-check-row">
                            <input class="nb-check-input" type="radio" name="born_again" value="yes" required>
                            <span class="nb-check-label">Yes</span>
                        </label>
                        <label class="nb-check-row">
                            <input class="nb-check-input" type="radio" name="born_again" value="no">
                            <span class="nb-check-label">Not yet</span>
                        </label>
                    </div>
                </fieldset>

                <fieldset class="nb-fieldset">
                    <legend class="nb-label">
                        Have you been water baptised? <span class="nb-required" aria-hidden="true">*</span>
                    </legend>
                    <div class="nb-radio-group">
                        <label class="nb-check-row">
                            <input class="nb-check-input" type="radio" name="water_baptised" value="yes" required>
                            <span class="nb-check-label">Yes</span>
                        </label>
                        <label class="nb-check-row">
                            <input class="nb-check-input" type="radio" name="water_baptised" value="no">
                            <span class="nb-check-label">No</span>
                        </label>
                        <label class="nb-check-row">
                            <input class="nb-check-input" type="radio" name="water_baptised" value="planning">
                            <span class="nb-check-label">I would like to be baptised</span>
                        </label>
                    </div>
                </fieldset>

                <fieldset class="nb-fieldset">
                    <legend class="nb-label">
                        Are you currently a member of another church?
                        <span class="nb-required" aria-hidden="true">*</span>
                    </legend>
                    <div class="nb-radio-group">
                        <label class="nb-check-row">
                            <input class="nb-check-input" type="radio" name="other_church_member" value="yes" required data-join-toggle="other-church-fields">
                            <span class="nb-check-label">Yes</span>
                        </label>
                        <label class="nb-check-row">
                            <input class="nb-check-input" type="radio" name="other_church_member" value="no" data-join-toggle="other-church-fields">
                            <span class="nb-check-label">No</span>
                        </label>
                    </div>
                </fieldset>

                <div class="nb-conditional" id="join-other-church-fields" hidden>
                    <div class="nb-field">
                        <label class="nb-label" for="join-other-church">
                            Name of church and reason for leaving / transferring
                            <span class="nb-required" aria-hidden="true">*</span>
                        </label>
                        <textarea
                            class="nb-input nb-textarea"
                            id="join-other-church"
                            name="other_church_details"
                            rows="2"
                            data-join-required-when="other_church_member:yes"
                        ></textarea>
                    </div>
                </div>

                <div class="nb-field">
                    <label class="nb-label" for="join-faith-story">Brief testimony or faith journey (optional)</label>
                    <textarea class="nb-input nb-textarea" id="join-faith-story" name="faith_story" rows="3"></textarea>
                </div>

                <p class="nb-form-section-title">Emergency Contact</p>

                <div class="nb-field">
                    <label class="nb-label" for="join-emergency-name">
                        Emergency contact name <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <input class="nb-input" type="text" id="join-emergency-name" name="emergency_name" required>
                </div>

                <div class="nb-field">
                    <label class="nb-label" for="join-emergency-phone">
                        Emergency contact phone <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <input class="nb-input" type="tel" id="join-emergency-phone" name="emergency_phone" required>
                </div>

                <div class="nb-field">
                    <label class="nb-label" for="join-emergency-relationship">
                        Relationship to you <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <input class="nb-input" type="text" id="join-emergency-relationship" name="emergency_relationship" required placeholder="e.g. Parent, sibling, friend">
                </div>

                <p class="nb-form-section-title">Serve &amp; Connect</p>

                <div class="nb-field">
                    <label class="nb-label" for="join-kingdom-group">
                        Would you like to join a Kingdom Group? <span class="nb-required" aria-hidden="true">*</span>
                    </label>
                    <select class="nb-input nb-select" id="join-kingdom-group" name="kingdom_group_interest" required>
                        <option value="" disabled selected>Select an option</option>
                        <option value="yes">Yes, please connect me</option>
                        <option value="already">I am already in a Kingdom Group</option>
                        <option value="not-yet">Not yet, but I am interested</option>
                        <option value="no">Not at this time</option>
                    </select>
                </div>

                <fieldset class="nb-fieldset">
                    <legend class="nb-label">Ministries you would like to serve in (select all that apply)</legend>
                    <div class="nb-check-list">
                        <?php foreach ($ministries_list as $ministry): ?>
                        <label class="nb-check-row">
                            <input class="nb-check-input" type="checkbox" name="ministry_serve[]" value="<?php echo htmlspecialchars($ministry['slug']); ?>">
                            <span class="nb-check-label"><?php echo htmlspecialchars($ministry['title']); ?></span>
                        </label>
                        <?php endforeach; ?>
                        <label class="nb-check-row">
                            <input class="nb-check-input" type="checkbox" name="ministry_serve[]" value="other">
                            <span class="nb-check-label">Other / not sure yet</span>
                        </label>
                    </div>
                </fieldset>

                <div class="nb-field">
                    <label class="nb-label" for="join-occupation">Occupation / workplace</label>
                    <input class="nb-input" type="text" id="join-occupation" name="occupation" autocomplete="organization">
                </div>

                <div class="nb-field">
                    <label class="nb-label" for="join-gifts">Skills, gifts, or areas you would like to contribute</label>
                    <textarea class="nb-input nb-textarea" id="join-gifts" name="gifts_skills" rows="2" placeholder="e.g. music, administration, hospitality, teaching"></textarea>
                </div>

                <p class="nb-form-section-title">Membership Commitment</p>

                <div class="nb-check-list nb-check-list--commitment">
                    <label class="nb-check-row">
                        <input class="nb-check-input" type="checkbox" name="commit_member" value="yes" required>
                        <span class="nb-check-label">
                            I desire to become a member of <?php echo htmlspecialchars($site_name); ?>.
                            <span class="nb-required" aria-hidden="true">*</span>
                        </span>
                    </label>
                </div>

                <p class="nb-disclaimer">
                    *By submitting this form and providing your contact details, you allow <?php echo htmlspecialchars($site_name); ?>
                    to contact you regarding membership, discipleship, and church life. Your information will be handled in
                    accordance with our Privacy Policy.
                </p>

                <div class="nb-form-actions">
                    <button type="submit" class="nb-submit-btn">Submit Application</button>
                </div>
            </form>
        </div>
    </div>
</div>
