<?php
/** @var list<array{slug?: string, title?: string}> $ministries */
/** @var list<array{id?: string, name?: string, short_label?: string}> $campuses */
$ministries = $ministries ?? [];
$campuses = $campuses ?? [
    ['id' => 'nanyuki', 'short_label' => 'Nanyuki', 'name' => 'Kingdomcity Church Nanyuki'],
    ['id' => 'nairobi', 'short_label' => 'Nairobi', 'name' => 'Kingdomcity Church Nairobi'],
];
$ageOptions = [
    'under-18' => 'Under 18',
    '18-24' => '18 – 24',
    '25-34' => '25 – 34',
    '35-44' => '35 – 44',
    '45-54' => '45 – 54',
    '55-64' => '55 – 64',
    '65-plus' => '65+',
];
?>
<div class="finance-field finance-field--full">
    <label class="finance-label" for="member-form-type">Connect form <span class="finance-req">*</span></label>
    <select id="member-form-type" name="form_type" class="finance-input" required x-model="formType">
        <option value="join">Join Our Church Family</option>
        <option value="new-here">New Here (Visiting Us)</option>
        <option value="new-beginning">New Beginning</option>
        <option value="kingdom-groups">Kingdom Groups</option>
    </select>
    <p class="finance-field-hint">Matches the Connect With Us forms on the church website. Fields below switch with the form type.</p>
</div>

<div class="finance-field finance-field--full">
    <label class="finance-label" for="member-campus">Where do we serve you? <span class="finance-req">*</span></label>
    <select id="member-campus" name="campus" class="finance-input" required>
        <?php foreach ($campuses as $campus): ?>
            <option value="<?= htmlspecialchars((string) ($campus['id'] ?? '')) ?>">
                <?= htmlspecialchars((string) ($campus['name'] ?? $campus['short_label'] ?? $campus['id'] ?? '')) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="finance-field finance-field--full">
    <label class="finance-label" for="member-name">Full name <span class="finance-req">*</span></label>
    <input type="text" id="member-name" name="name" required class="finance-input" autocomplete="name" placeholder="e.g. Jane Wanjiku">
</div>
<div class="finance-field">
    <label class="finance-label" for="member-phone">Phone <span class="finance-req">*</span></label>
    <input type="tel" id="member-phone" name="phone" required class="finance-input" autocomplete="tel" placeholder="+254…">
</div>
<div class="finance-field">
    <label class="finance-label" for="member-email">Email <span class="finance-req">*</span></label>
    <input type="email" id="member-email" name="email" required class="finance-input" autocomplete="email" placeholder="name@example.com">
</div>

<!-- ═══════════ Join ═══════════ -->
<fieldset class="member-form-block" x-show="formType === 'join'" :disabled="formType !== 'join'">
    <legend class="member-form-legend">Join Our Church Family — all fields</legend>

    <p class="finance-section-title">Church &amp; Contact</p>
    <div class="finance-field">
        <label class="finance-label" for="join-dob">Date of birth <span class="finance-req">*</span></label>
        <input type="date" id="join-dob" name="date_of_birth" class="finance-input" :required="formType === 'join'">
    </div>
    <div class="finance-field finance-field--full">
        <span class="finance-label">Gender <span class="finance-req">*</span></span>
        <div class="finance-choice-row">
            <label class="finance-choice"><input type="radio" name="gender" value="male" :required="formType === 'join'"> Male</label>
            <label class="finance-choice"><input type="radio" name="gender" value="female"> Female</label>
        </div>
    </div>
    <div class="finance-field">
        <label class="finance-label" for="join-marital">Marital status <span class="finance-req">*</span></label>
        <select id="join-marital" name="marital_status" class="finance-input" :required="formType === 'join'">
            <option value="">Select…</option>
            <option value="single">Single</option>
            <option value="married">Married</option>
            <option value="divorced">Divorced</option>
            <option value="widowed">Widowed</option>
            <option value="separated">Separated</option>
        </select>
    </div>
    <div class="finance-field finance-field--full">
        <label class="finance-label" for="join-address">Residential address <span class="finance-req">*</span></label>
        <input type="text" id="join-address" name="address" class="finance-input" autocomplete="street-address" :required="formType === 'join'">
    </div>
    <div class="finance-field finance-field--full">
        <label class="finance-label" for="join-attending">How long have you been attending? <span class="finance-req">*</span></label>
        <select id="join-attending" name="attending_duration" class="finance-input" :required="formType === 'join'">
            <option value="">Select…</option>
            <option value="first-time">This is my first time</option>
            <option value="under-3-months">Less than 3 months</option>
            <option value="3-6-months">3 – 6 months</option>
            <option value="6-12-months">6 – 12 months</option>
            <option value="over-1-year">Over 1 year</option>
        </select>
    </div>

    <p class="finance-section-title">Family &amp; Household</p>
    <div class="finance-field finance-field--full">
        <span class="finance-label">Do you have a spouse or partner? <span class="finance-req">*</span></span>
        <div class="finance-choice-row">
            <label class="finance-choice"><input type="radio" name="has_spouse" value="yes" x-model="hasSpouse" :required="formType === 'join'"> Yes</label>
            <label class="finance-choice"><input type="radio" name="has_spouse" value="no" x-model="hasSpouse"> No</label>
        </div>
    </div>
    <div class="finance-field finance-field--full" x-show="hasSpouse === 'yes'">
        <label class="finance-label" for="join-spouse-name">Spouse / partner full name <span class="finance-req">*</span></label>
        <input type="text" id="join-spouse-name" name="spouse_name" class="finance-input" :required="formType === 'join' && hasSpouse === 'yes'">
    </div>
    <div class="finance-field" x-show="hasSpouse === 'yes'">
        <label class="finance-label" for="join-spouse-phone">Spouse / partner phone</label>
        <input type="tel" id="join-spouse-phone" name="spouse_phone" class="finance-input">
    </div>
    <div class="finance-field" x-show="hasSpouse === 'yes'">
        <label class="finance-label" for="join-spouse-email">Spouse / partner email</label>
        <input type="email" id="join-spouse-email" name="spouse_email" class="finance-input">
    </div>
    <div class="finance-field finance-field--full" x-show="hasSpouse === 'yes'">
        <label class="finance-choice"><input type="checkbox" name="spouse_attends" value="yes"> My spouse / partner also attends this church</label>
    </div>

    <div class="finance-field finance-field--full">
        <span class="finance-label">Do you have children? <span class="finance-req">*</span></span>
        <div class="finance-choice-row">
            <label class="finance-choice"><input type="radio" name="has_children" value="yes" x-model="hasChildren" :required="formType === 'join'"> Yes</label>
            <label class="finance-choice"><input type="radio" name="has_children" value="no" x-model="hasChildren"> No</label>
        </div>
    </div>
    <div class="finance-field finance-field--full" x-show="hasChildren === 'yes'">
        <label class="finance-label" for="join-children">List all children (full names and ages) <span class="finance-req">*</span></label>
        <textarea id="join-children" name="children_details" rows="3" class="finance-input finance-textarea" placeholder="e.g. Jane Doe, 8; John Doe, 12" :required="formType === 'join' && hasChildren === 'yes'"></textarea>
    </div>
    <div class="finance-field finance-field--full" x-show="hasChildren === 'yes'">
        <label class="finance-choice"><input type="checkbox" name="children_attend" value="yes"> My children also attend church programmes here (K-Kids, K-Teens, etc.)</label>
    </div>

    <div class="finance-field finance-field--full">
        <span class="finance-label">Do you have other dependents living in your household? <span class="finance-req">*</span></span>
        <div class="finance-choice-row">
            <label class="finance-choice"><input type="radio" name="has_dependents" value="yes" x-model="hasDependents" :required="formType === 'join'"> Yes</label>
            <label class="finance-choice"><input type="radio" name="has_dependents" value="no" x-model="hasDependents"> No</label>
        </div>
    </div>
    <div class="finance-field finance-field--full" x-show="hasDependents === 'yes'">
        <label class="finance-label" for="join-dependents">Who are they and how are they related? <span class="finance-req">*</span></label>
        <textarea id="join-dependents" name="dependents_details" rows="2" class="finance-input finance-textarea" :required="formType === 'join' && hasDependents === 'yes'"></textarea>
    </div>
    <div class="finance-field">
        <label class="finance-label" for="join-household">Total household size (including you) <span class="finance-req">*</span></label>
        <input type="number" id="join-household" name="household_size" min="1" max="30" class="finance-input" :required="formType === 'join'">
    </div>

    <p class="finance-section-title">Faith Background</p>
    <div class="finance-field finance-field--full">
        <span class="finance-label">Have you given your life to Jesus Christ? <span class="finance-req">*</span></span>
        <div class="finance-choice-row">
            <label class="finance-choice"><input type="radio" name="born_again" value="yes" :required="formType === 'join'"> Yes</label>
            <label class="finance-choice"><input type="radio" name="born_again" value="no"> Not yet</label>
        </div>
    </div>
    <div class="finance-field finance-field--full">
        <span class="finance-label">Have you been water baptised? <span class="finance-req">*</span></span>
        <div class="finance-choice-row">
            <label class="finance-choice"><input type="radio" name="water_baptised" value="yes" :required="formType === 'join'"> Yes</label>
            <label class="finance-choice"><input type="radio" name="water_baptised" value="no"> No</label>
            <label class="finance-choice"><input type="radio" name="water_baptised" value="planning"> I would like to be baptised</label>
        </div>
    </div>
    <div class="finance-field finance-field--full">
        <span class="finance-label">Are you currently a member of another church? <span class="finance-req">*</span></span>
        <div class="finance-choice-row">
            <label class="finance-choice"><input type="radio" name="other_church_member" value="yes" x-model="otherChurch" :required="formType === 'join'"> Yes</label>
            <label class="finance-choice"><input type="radio" name="other_church_member" value="no" x-model="otherChurch"> No</label>
        </div>
    </div>
    <div class="finance-field finance-field--full" x-show="otherChurch === 'yes'">
        <label class="finance-label" for="join-other-church">Name of church and reason for leaving / transferring <span class="finance-req">*</span></label>
        <textarea id="join-other-church" name="other_church_details" rows="2" class="finance-input finance-textarea" :required="formType === 'join' && otherChurch === 'yes'"></textarea>
    </div>
    <div class="finance-field finance-field--full">
        <label class="finance-label" for="join-faith-story">Brief testimony or faith journey</label>
        <textarea id="join-faith-story" name="faith_story" rows="3" class="finance-input finance-textarea"></textarea>
    </div>

    <p class="finance-section-title">Emergency Contact</p>
    <div class="finance-field">
        <label class="finance-label" for="join-emergency-name">Emergency contact name <span class="finance-req">*</span></label>
        <input type="text" id="join-emergency-name" name="emergency_name" class="finance-input" :required="formType === 'join'">
    </div>
    <div class="finance-field">
        <label class="finance-label" for="join-emergency-phone">Emergency contact phone <span class="finance-req">*</span></label>
        <input type="tel" id="join-emergency-phone" name="emergency_phone" class="finance-input" :required="formType === 'join'">
    </div>
    <div class="finance-field finance-field--full">
        <label class="finance-label" for="join-emergency-rel">Relationship to you <span class="finance-req">*</span></label>
        <input type="text" id="join-emergency-rel" name="emergency_relationship" class="finance-input" :required="formType === 'join'">
    </div>

    <p class="finance-section-title">Serve &amp; Connect</p>
    <div class="finance-field finance-field--full">
        <label class="finance-label" for="join-kg">Would you like to join a Kingdom Group? <span class="finance-req">*</span></label>
        <select id="join-kg" name="kingdom_group_interest" class="finance-input" :required="formType === 'join'">
            <option value="">Select…</option>
            <option value="yes">Yes</option>
            <option value="already">I already belong to one</option>
            <option value="not-yet">Not yet</option>
            <option value="no">No</option>
        </select>
    </div>
    <div class="finance-field finance-field--full">
        <span class="finance-label">Ministries you would like to serve in</span>
        <div class="finance-choice-list">
            <?php foreach ($ministries as $ministry): ?>
                <label class="finance-choice">
                    <input type="checkbox" name="ministry_serve[]" value="<?= htmlspecialchars((string) ($ministry['slug'] ?? '')) ?>">
                    <?= htmlspecialchars((string) ($ministry['title'] ?? $ministry['slug'] ?? '')) ?>
                </label>
            <?php endforeach; ?>
            <label class="finance-choice"><input type="checkbox" name="ministry_serve[]" value="other"> Other</label>
        </div>
    </div>
    <div class="finance-field">
        <label class="finance-label" for="join-occupation">Occupation / workplace</label>
        <input type="text" id="join-occupation" name="occupation" class="finance-input">
    </div>
    <div class="finance-field finance-field--full">
        <label class="finance-label" for="join-gifts">Skills, gifts, or areas you would like to contribute</label>
        <textarea id="join-gifts" name="gifts_skills" rows="2" class="finance-input finance-textarea"></textarea>
    </div>

    <p class="finance-section-title">Membership Commitment</p>
    <div class="finance-field finance-field--full">
        <label class="finance-choice">
            <input type="checkbox" name="commit_member" value="yes" :required="formType === 'join'">
            I desire to become a member of this church <span class="finance-req">*</span>
        </label>
    </div>
</fieldset>

<!-- ═══════════ New Here ═══════════ -->
<fieldset class="member-form-block" x-show="formType === 'new-here'" :disabled="formType !== 'new-here'">
    <legend class="member-form-legend">New Here — all fields</legend>

    <div class="finance-field finance-field--full">
        <label class="finance-choice"><input type="checkbox" name="first_time" value="yes"> Is this your first time here?</label>
    </div>
    <div class="finance-field">
        <label class="finance-label" for="nh-age">Age <span class="finance-req">*</span></label>
        <select id="nh-age" name="age_range" class="finance-input" :required="formType === 'new-here'">
            <option value="">Select age range…</option>
            <?php foreach ($ageOptions as $value => $label): ?>
                <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="finance-field finance-field--full">
        <span class="finance-label">Gender <span class="finance-req">*</span></span>
        <div class="finance-choice-row">
            <label class="finance-choice"><input type="radio" name="gender" value="male" :required="formType === 'new-here'"> Male</label>
            <label class="finance-choice"><input type="radio" name="gender" value="female"> Female</label>
        </div>
    </div>
    <div class="finance-field">
        <label class="finance-label" for="nh-marital">Marital status (optional)</label>
        <input type="text" id="nh-marital" name="marital_status" class="finance-input" autocomplete="off">
    </div>
    <div class="finance-field finance-field--full">
        <label class="finance-label" for="nh-heard">How did you hear about us? <span class="finance-req">*</span></label>
        <select id="nh-heard" name="heard_about" class="finance-input" :required="formType === 'new-here'">
            <option value="">Select…</option>
            <option value="social_media">Social media</option>
            <option value="youtube_livestream">YouTube or livestream</option>
            <option value="outdoor_sign">Saw an outdoor sign or banner</option>
            <option value="friend_family">Invited by a friend or family member</option>
            <option value="church_member">Invited by a church member</option>
            <option value="drove_walked_by">Drove or walked by the building</option>
        </select>
    </div>
    <div class="finance-field finance-field--full">
        <label class="finance-label" for="nh-experience">How was your experience with us?</label>
        <textarea id="nh-experience" name="experience" rows="3" class="finance-input finance-textarea" placeholder="Share your experience (optional)"></textarea>
    </div>
</fieldset>

<!-- ═══════════ New Beginning ═══════════ -->
<fieldset class="member-form-block" x-show="formType === 'new-beginning'" :disabled="formType !== 'new-beginning'">
    <legend class="member-form-legend">New Beginning — all fields</legend>

    <div class="finance-field finance-field--full">
        <label class="finance-choice"><input type="checkbox" name="first_time" value="yes"> Is this your first time here?</label>
    </div>
    <div class="finance-field">
        <label class="finance-label" for="nb-age">Age <span class="finance-req">*</span></label>
        <select id="nb-age" name="age_range" class="finance-input" :required="formType === 'new-beginning'">
            <option value="">Select age range…</option>
            <?php foreach ($ageOptions as $value => $label): ?>
                <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="finance-field finance-field--full">
        <span class="finance-label">Gender <span class="finance-req">*</span></span>
        <div class="finance-choice-row">
            <label class="finance-choice"><input type="radio" name="gender" value="male" :required="formType === 'new-beginning'"> Male</label>
            <label class="finance-choice"><input type="radio" name="gender" value="female"> Female</label>
        </div>
    </div>
    <div class="finance-field finance-field--full">
        <span class="finance-label">Today I made the decision to: <span class="finance-req">*</span></span>
        <div class="finance-choice-list">
            <label class="finance-choice"><input type="radio" name="decision" value="first_time" :required="formType === 'new-beginning'"> Give my life to Christ for the first time</label>
            <label class="finance-choice"><input type="radio" name="decision" value="rededicate"> Rededicate my life to Christ</label>
            <label class="finance-choice"><input type="radio" name="decision" value="child"> My child gave their life to Christ</label>
        </div>
    </div>
    <div class="finance-field finance-field--full">
        <label class="finance-choice"><input type="checkbox" name="water_baptised" value="yes"> Have you been water baptised before?</label>
    </div>
    <div class="finance-field finance-field--full">
        <span class="finance-label">Sign me up for:</span>
        <div class="finance-choice-list">
            <label class="finance-choice"><input type="checkbox" name="signup[]" value="water_baptism"> Water Baptism</label>
            <label class="finance-choice"><input type="checkbox" name="signup[]" value="kingdom_group"> Kingdom Group</label>
            <label class="finance-choice"><input type="checkbox" name="signup[]" value="new_believers_class"> New Believers Class</label>
        </div>
    </div>
</fieldset>

<!-- ═══════════ Kingdom Groups ═══════════ -->
<fieldset class="member-form-block" x-show="formType === 'kingdom-groups'" :disabled="formType !== 'kingdom-groups'">
    <legend class="member-form-legend">Kingdom Groups — all fields</legend>

    <div class="finance-field finance-field--full">
        <label class="finance-label" for="kg-ministry">Ministry interest <span class="finance-req">*</span></label>
        <select id="kg-ministry" name="ministry_interest" class="finance-input" :required="formType === 'kingdom-groups'">
            <option value="">Select…</option>
            <option value="kingdom_group">Kingdom Group</option>
            <?php foreach ($ministries as $ministry): ?>
                <option value="<?= htmlspecialchars((string) ($ministry['slug'] ?? '')) ?>">
                    <?= htmlspecialchars((string) ($ministry['title'] ?? $ministry['slug'] ?? '')) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="finance-field">
        <label class="finance-label" for="kg-age">Age <span class="finance-req">*</span></label>
        <select id="kg-age" name="age_range" class="finance-input" :required="formType === 'kingdom-groups'">
            <option value="">Select age range…</option>
            <?php foreach ($ageOptions as $value => $label): ?>
                <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="finance-field finance-field--full">
        <label class="finance-choice"><input type="checkbox" name="speak_to_pastor" value="yes"> Want to speak to a pastor?</label>
    </div>
    <div class="finance-field finance-field--full">
        <label class="finance-label" for="kg-address">Address</label>
        <input type="text" id="kg-address" name="address" class="finance-input" autocomplete="street-address">
    </div>
</fieldset>

<div class="finance-field finance-field--full">
    <label class="finance-label" for="member-notes">Admin notes</label>
    <textarea id="member-notes" name="notes" rows="2" class="finance-input finance-textarea" placeholder="Internal notes (optional)…"></textarea>
</div>
