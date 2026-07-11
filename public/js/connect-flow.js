(function () {
    'use strict';

    const AGE_OPTIONS = [
        { value: 'under-18', label: 'Under 18' },
        { value: '18-24', label: '18 – 24' },
        { value: '25-34', label: '25 – 34' },
        { value: '35-44', label: '35 – 44' },
        { value: '45-54', label: '45 – 54' },
        { value: '55-64', label: '55 – 64' },
        { value: '65-plus', label: '65+' },
    ];

    const HEARD_OPTIONS = [
        { value: 'social_media', label: 'Social media' },
        { value: 'youtube_livestream', label: 'YouTube or livestream' },
        { value: 'outdoor_sign', label: 'Saw an outdoor sign or banner' },
        { value: 'friend_family', label: 'Invited by a friend or family member' },
        { value: 'church_member', label: 'Invited by a church member' },
        { value: 'drove_walked_by', label: 'Drove or walked by the building' },
    ];

    const FORM_META = {
        'new-beginning': { title: 'New Beginning', icon: 'cross', success: 'Your response has been received. Our team will be in touch with you soon.' },
        'new-here': { title: 'New Here', icon: 'hand', success: 'Welcome! Our team will connect with you shortly.' },
        'kingdom-groups': { title: 'Kingdom Groups', icon: 'handshake', success: 'Thank you! A leader will reach out to help you find community.' },
        join: { title: 'Join Our Church', icon: 'church', success: 'Your membership application has been received. A leader will contact you soon.' },
    };

    function connectHubFactory(config) {
        return {
            config,
            activeForm: null,
            qi: 0,
            form: {},
            stepError: '',
            submitError: '',
            submitted: false,
            submitting: false,
            successMessage: '',
            yesNoValue: null,
            hasSpouse: null,
            hasChildren: null,
            hasDependents: null,
            otherChurch: null,

            get formMeta() {
                return FORM_META[this.activeForm] || {};
            },

            get queue() {
                if (!this.activeForm) return [];
                return this.buildQueue(this.activeForm);
            },

            get current() {
                return this.queue[this.qi] || null;
            },

            get progress() {
                if (!this.queue.length) return 0;
                return Math.round(((this.qi + 1) / this.queue.length) * 100);
            },

            stepIconName() {
                return window.ConnectStepIcons?.resolve(this.current?.icon) || 'sparkles';
            },

            init() {
                this.$watch('qi', () => {
                    this.stepError = '';
                    this.syncYesNo();
                    this.$nextTick(() => {
                        this.$refs.input?.focus();
                        window.ConnectStepIcons?.refresh(this.$el);
                    });
                });
                this.$watch('submitted', () => {
                    this.$nextTick(() => window.ConnectStepIcons?.refresh(this.$el));
                });
            },

            campusOptions() {
                return (this.config.campuses || []).map((c) => ({
                    value: c.id,
                    label: c.name,
                }));
            },

            ministryOptions(includeKingdomGroup) {
                const opts = [];
                if (includeKingdomGroup) {
                    opts.push({ value: 'kingdom_group', label: 'Kingdom Group' });
                }
                (this.config.ministries || []).forEach((m) => {
                    opts.push({ value: m.slug, label: m.title });
                });
                return opts;
            },

            ministryServeOptions() {
                const opts = (this.config.ministries || []).map((m) => ({
                    value: m.slug,
                    label: m.title,
                }));
                opts.push({ value: 'other', label: 'Other / not sure yet' });
                return opts;
            },

            openForm(type) {
                this.activeForm = type;
                this.qi = 0;
                this.form = this.defaultForm(type);
                this.stepError = '';
                this.submitError = '';
                this.submitted = false;
                this.submitting = false;
                this.yesNoValue = null;
                this.hasSpouse = null;
                this.hasChildren = null;
                this.hasDependents = null;
                this.otherChurch = null;
                document.documentElement.classList.add('connect-form-open');
                document.body.classList.add('connect-form-open');
                this.$nextTick(() => {
                    this.$refs.input?.focus();
                    window.ConnectStepIcons?.refresh(this.$el);
                });
            },

            closeForm() {
                this.activeForm = null;
                document.documentElement.classList.remove('connect-form-open');
                document.body.classList.remove('connect-form-open');
            },

            defaultForm(type) {
                const base = {
                    campus: (this.config.campuses[0] && this.config.campuses[0].id) || 'nanyuki',
                    name: '',
                    phone: '',
                    email: '',
                    age_range: '',
                    gender: '',
                };
                if (type === 'new-beginning') {
                    return Object.assign(base, {
                        first_time: '',
                        decision: '',
                        water_baptised: '',
                        signup: [],
                    });
                }
                if (type === 'new-here') {
                    return Object.assign(base, {
                        first_time: '',
                        marital_status: '',
                        heard_about: '',
                        experience: '',
                    });
                }
                if (type === 'kingdom-groups') {
                    return Object.assign(base, {
                        ministry_interest: '',
                        speak_to_pastor: '',
                        address: '',
                    });
                }
                if (type === 'join') {
                    return Object.assign(base, {
                        date_of_birth: '',
                        marital_status: '',
                        address: '',
                        attending_duration: '',
                        spouse_name: '',
                        spouse_phone: '',
                        spouse_email: '',
                        spouse_attends: '',
                        children_details: '',
                        children_attend: '',
                        dependents_details: '',
                        household_size: '',
                        born_again: '',
                        water_baptised: '',
                        other_church_details: '',
                        faith_story: '',
                        emergency_name: '',
                        emergency_phone: '',
                        emergency_relationship: '',
                        kingdom_group_interest: '',
                        ministry_serve: [],
                        occupation: '',
                        gifts_skills: '',
                        commit_member: '',
                        commit_attendance: '',
                        commit_leadership: '',
                    });
                }
                return base;
            },

            buildQueue(type) {
                const q = [];
                const add = (item) => q.push(item);
                const campus = {
                    id: 'campus',
                    section: 'Campus',
                    icon: 'map-pin',
                    question: 'Where do we serve you?',
                    type: 'select',
                    field: 'campus',
                    options: this.campusOptions(),
                    required: true,
                };

                if (type === 'new-beginning') {
                    add(campus);
                    add({ id: 'first_time', section: 'Welcome', icon: 'hand', question: 'Is this your first time here?', type: 'yesno', field: '_first_time' });
                    add({ id: 'name', section: 'About you', icon: 'user', question: "What's your full name?", type: 'text', field: 'name', required: true });
                    add({ id: 'phone', section: 'Contact', icon: 'phone', question: "What's your phone number?", type: 'tel', field: 'phone', required: true, placeholder: '+254 7XX XXX XXX' });
                    add({ id: 'email', section: 'Contact', icon: 'mail', question: "What's your email?", type: 'email', field: 'email', required: true });
                    add({ id: 'age', section: 'About you', icon: 'cake', question: 'How old are you?', type: 'select', field: 'age_range', options: AGE_OPTIONS, required: true });
                    add({ id: 'gender', section: 'About you', icon: 'users', question: 'Gender', type: 'choices', field: 'gender', options: [{ value: 'male', label: 'Male' }, { value: 'female', label: 'Female' }], required: true });
                    add({ id: 'decision', section: 'Your decision', icon: 'cross', question: 'Today I made the decision to:', type: 'choices', field: 'decision', required: true, options: [
                        { value: 'first_time', label: 'Give my life to Christ for the first time' },
                        { value: 'rededicate', label: 'Rededicate my life to Christ' },
                        { value: 'child', label: 'My child gave their life to Christ' },
                    ]});
                    add({ id: 'water_baptised', section: 'Baptism', icon: 'droplets', question: 'Have you been water baptised before?', type: 'yesno', field: '_water_baptised' });
                    add({ id: 'signup', section: 'Next steps', icon: 'sprout', question: 'Sign me up for (select all that apply):', hint: 'Optional — tap Continue when done', type: 'multichoice', field: 'signup', options: [
                        { value: 'water_baptism', label: 'Water Baptism' },
                        { value: 'kingdom_group', label: 'Kingdom Group' },
                        { value: 'new_believers_class', label: 'New Believers Class' },
                    ]});
                    add({ id: 'review', section: 'Review', icon: 'review', question: 'Ready to submit?', hint: 'Review your details below', type: 'review' });
                }

                if (type === 'new-here') {
                    add(campus);
                    add({ id: 'first_time', section: 'Welcome', icon: 'hand', question: 'Is this your first time here?', hint: "So great to have you with us today! Welcome home.", type: 'yesno', field: '_first_time' });
                    add({ id: 'name', section: 'About you', icon: 'user', question: "What's your full name?", type: 'text', field: 'name', required: true });
                    add({ id: 'phone', section: 'Contact', icon: 'phone', question: "What's your phone number?", type: 'tel', field: 'phone', required: true });
                    add({ id: 'email', section: 'Contact', icon: 'mail', question: "What's your email?", type: 'email', field: 'email', required: true });
                    add({ id: 'age', section: 'About you', icon: 'cake', question: 'How old are you?', type: 'select', field: 'age_range', options: AGE_OPTIONS, required: true });
                    add({ id: 'gender', section: 'About you', icon: 'users', question: 'Gender', type: 'choices', field: 'gender', options: [{ value: 'male', label: 'Male' }, { value: 'female', label: 'Female' }], required: true });
                    add({ id: 'marital', section: 'About you', icon: 'heart', question: 'Marital status (optional)', hint: 'Press Enter to skip', type: 'text', field: 'marital_status' });
                    add({ id: 'heard', section: 'Your visit', icon: 'lightbulb', question: 'How did you hear about us?', type: 'select', field: 'heard_about', options: HEARD_OPTIONS, required: true });
                    add({ id: 'experience', section: 'Your visit', icon: 'message', question: 'How was your experience with us?', hint: 'Optional', type: 'textarea', field: 'experience', placeholder: 'Share your experience...' });
                    add({ id: 'review', section: 'Review', icon: 'review', question: 'Ready to submit?', type: 'review' });
                }

                if (type === 'kingdom-groups') {
                    add(campus);
                    add({ id: 'ministry', section: 'Interest', icon: 'target', question: 'What is your ministry interest?', type: 'select', field: 'ministry_interest', options: this.ministryOptions(true), required: true });
                    add({ id: 'name', section: 'About you', icon: 'user', question: "What's your full name?", type: 'text', field: 'name', required: true });
                    add({ id: 'phone', section: 'Contact', icon: 'phone', question: "What's your phone number?", type: 'tel', field: 'phone', required: true });
                    add({ id: 'email', section: 'Contact', icon: 'mail', question: "What's your email?", type: 'email', field: 'email', required: true });
                    add({ id: 'age', section: 'About you', icon: 'cake', question: 'How old are you?', type: 'select', field: 'age_range', options: AGE_OPTIONS, required: true });
                    add({ id: 'pastor', section: 'Pastoral care', icon: 'pray', question: 'Would you like to speak to a pastor?', type: 'yesno', field: '_speak_to_pastor' });
                    add({ id: 'address', section: 'Location', icon: 'home', question: 'Your address (optional)', hint: 'Press Enter to skip', type: 'text', field: 'address' });
                    add({ id: 'review', section: 'Review', icon: 'handshake', question: 'Ready to submit?', type: 'review' });
                }

                if (type === 'join') {
                    add(campus);
                    add({ id: 'name', section: 'Contact', icon: 'user', question: 'Your full legal name', type: 'text', field: 'name', required: true });
                    add({ id: 'phone', section: 'Contact', icon: 'phone', question: 'Phone number', type: 'tel', field: 'phone', required: true });
                    add({ id: 'email', section: 'Contact', icon: 'mail', question: 'Email address', type: 'email', field: 'email', required: true });
                    add({ id: 'dob', section: 'About you', icon: 'cake', question: 'Date of birth', type: 'date', field: 'date_of_birth', required: true });
                    add({ id: 'gender', section: 'About you', icon: 'users', question: 'Gender', type: 'choices', field: 'gender', options: [{ value: 'male', label: 'Male' }, { value: 'female', label: 'Female' }], required: true });
                    add({ id: 'marital', section: 'About you', icon: 'heart', question: 'Marital status', type: 'select', field: 'marital_status', required: true, options: [
                        { value: 'single', label: 'Single' }, { value: 'married', label: 'Married' },
                        { value: 'divorced', label: 'Divorced' }, { value: 'widowed', label: 'Widowed' },
                        { value: 'separated', label: 'Separated' },
                    ]});
                    add({ id: 'address', section: 'About you', icon: 'home', question: 'Residential address', type: 'text', field: 'address', required: true });
                    add({ id: 'attending', section: 'Church life', icon: 'church', question: 'How long have you been attending ' + (this.config.churchName || 'our church') + '?', type: 'select', field: 'attending_duration', required: true, options: [
                        { value: 'first-time', label: 'This is my first time / I am new' },
                        { value: 'under-3-months', label: 'Less than 3 months' },
                        { value: '3-6-months', label: '3 – 6 months' },
                        { value: '6-12-months', label: '6 – 12 months' },
                        { value: 'over-1-year', label: 'Over 1 year' },
                    ]});
                    add({ id: 'has_spouse', section: 'Family', icon: 'couple', question: 'Do you have a spouse or partner?', type: 'yesno', field: '_has_spouse', required: true });
                    if (this.hasSpouse === true) {
                        add({ id: 'spouse_name', section: 'Family', icon: 'couple', question: "Spouse / partner's full name", type: 'text', field: 'spouse_name', required: true });
                        add({ id: 'spouse_phone', section: 'Family', icon: 'phone', question: "Spouse / partner's phone (optional)", type: 'tel', field: 'spouse_phone' });
                        add({ id: 'spouse_email', section: 'Family', icon: 'mail', question: "Spouse / partner's email (optional)", type: 'email', field: 'spouse_email' });
                        add({ id: 'spouse_attends', section: 'Family', icon: 'church', question: 'Does your spouse also attend this church?', type: 'yesno', field: '_spouse_attends' });
                    }
                    add({ id: 'has_children', section: 'Family', icon: 'baby', question: 'Do you have children?', type: 'yesno', field: '_has_children', required: true });
                    if (this.hasChildren === true) {
                        add({ id: 'children_details', section: 'Family', icon: 'family', question: 'List all children (names and ages)', hint: 'e.g. Jane Doe, 8; John Doe, 12', type: 'textarea', field: 'children_details', required: true });
                        add({ id: 'children_attend', section: 'Family', icon: 'backpack', question: 'Do your children attend church programmes here?', type: 'yesno', field: '_children_attend' });
                    }
                    add({ id: 'has_dependents', section: 'Family', icon: 'household', question: 'Do you have other dependents in your household?', type: 'yesno', field: '_has_dependents', required: true });
                    if (this.hasDependents === true) {
                        add({ id: 'dependents_details', section: 'Family', icon: 'family', question: 'Who are they and how are they related?', type: 'textarea', field: 'dependents_details', required: true });
                    }
                    add({ id: 'household_size', section: 'Family', icon: 'hash', question: 'Total people in your household (including you)', type: 'number', field: 'household_size', required: true, placeholder: 'e.g. 4' });
                    add({ id: 'born_again', section: 'Faith', icon: 'cross', question: 'Have you given your life to Jesus Christ?', type: 'yesno', field: '_born_again', required: true });
                    add({ id: 'water_baptised', section: 'Faith', icon: 'droplets', question: 'Have you been water baptised?', type: 'choices', field: 'water_baptised', required: true, options: [
                        { value: 'yes', label: 'Yes' }, { value: 'no', label: 'No' }, { value: 'planning', label: 'I would like to be baptised' },
                    ]});
                    add({ id: 'other_church', section: 'Faith', icon: 'church', question: 'Are you currently a member of another church?', type: 'yesno', field: '_other_church', required: true });
                    if (this.otherChurch === true) {
                        add({ id: 'other_church_details', section: 'Faith', icon: 'file-text', question: 'Name of church and reason for leaving / transferring', type: 'textarea', field: 'other_church_details', required: true });
                    }
                    add({ id: 'faith_story', section: 'Faith', icon: 'book', question: 'Brief testimony or faith journey (optional)', type: 'textarea', field: 'faith_story' });
                    add({ id: 'emergency_name', section: 'Emergency', icon: 'emergency', question: 'Emergency contact name', type: 'text', field: 'emergency_name', required: true });
                    add({ id: 'emergency_phone', section: 'Emergency', icon: 'phone', question: 'Emergency contact phone', type: 'tel', field: 'emergency_phone', required: true });
                    add({ id: 'emergency_rel', section: 'Emergency', icon: 'user-circle', question: 'Relationship to you', type: 'text', field: 'emergency_relationship', required: true, placeholder: 'e.g. Parent, sibling' });
                    add({ id: 'kg_interest', section: 'Connect', icon: 'handshake', question: 'Would you like to join a Kingdom Group?', type: 'select', field: 'kingdom_group_interest', required: true, options: [
                        { value: 'yes', label: 'Yes, please connect me' },
                        { value: 'already', label: 'I am already in a Kingdom Group' },
                        { value: 'not-yet', label: 'Not yet, but I am interested' },
                        { value: 'no', label: 'Not at this time' },
                    ]});
                    add({ id: 'ministry_serve', section: 'Serve', icon: 'serve', question: 'Ministries you would like to serve in (select all that apply)', hint: 'Optional', type: 'multichoice', field: 'ministry_serve', options: this.ministryServeOptions() });
                    add({ id: 'occupation', section: 'Serve', icon: 'briefcase', question: 'Occupation / workplace (optional)', type: 'text', field: 'occupation' });
                    add({ id: 'gifts', section: 'Serve', icon: 'gift', question: 'Skills or gifts you would like to contribute (optional)', type: 'textarea', field: 'gifts_skills' });
                    add({ id: 'commit_member', section: 'Commitment', icon: 'scroll', question: 'I desire to become a member of this church', type: 'yesno', field: '_commit_member', required: true });
                    add({ id: 'review', section: 'Review', icon: 'review', question: 'Ready to submit your application?', type: 'review' });
                }

                return q;
            },

            get reviewSections() {
                const row = (label, value) => {
                    const v = value === null || value === undefined ? '' : String(value).trim();
                    if (!v || v === 'false') return null;
                    return { label, value: v };
                };

                const labelMap = {
                    campus: () => {
                        const c = (this.config.campuses || []).find((x) => x.id === this.form.campus);
                        return c ? c.name : this.form.campus;
                    },
                    gender: () => this.form.gender === 'male' ? 'Male' : this.form.gender === 'female' ? 'Female' : this.form.gender,
                    age_range: () => (AGE_OPTIONS.find((o) => o.value === this.form.age_range) || {}).label || this.form.age_range,
                    heard_about: () => (HEARD_OPTIONS.find((o) => o.value === this.form.heard_about) || {}).label || this.form.heard_about,
                    decision: () => ({
                        first_time: 'Gave life to Christ (first time)',
                        rededicate: 'Rededicated life to Christ',
                        child: 'Child gave life to Christ',
                    }[this.form.decision] || this.form.decision),
                };

                const val = (field) => {
                    if (labelMap[field]) return labelMap[field]();
                    if (Array.isArray(this.form[field])) return this.form[field].length ? this.form[field].join(', ') : null;
                    return this.form[field];
                };

                const sections = [];
                const personal = [
                    row('Name', val('name')),
                    row('Campus', labelMap.campus()),
                    row('Age', val('age_range')),
                    row('Gender', val('gender')),
                ].filter(Boolean);
                if (personal.length) sections.push({ title: 'About you', items: personal });

                const contact = [
                    row('Phone', val('phone')),
                    row('Email', val('email')),
                    row('Address', val('address')),
                ].filter(Boolean);
                if (contact.length) sections.push({ title: 'Contact', items: contact });

                return sections.length ? sections : [{ title: 'Summary', items: [row('Name', val('name'))].filter(Boolean) }];
            },

            syncYesNo() {
                const c = this.current;
                if (!c || c.type !== 'yesno') return;
                const map = {
                    _first_time: this.form.first_time === 'yes' ? true : this.form.first_time === 'no' ? false : null,
                    _water_baptised: this.form.water_baptised === 'yes' ? true : this.form.water_baptised === 'no' ? false : null,
                    _speak_to_pastor: this.form.speak_to_pastor === 'yes' ? true : this.form.speak_to_pastor === 'no' ? false : null,
                    _has_spouse: this.hasSpouse,
                    _has_children: this.hasChildren,
                    _has_dependents: this.hasDependents,
                    _spouse_attends: this.form.spouse_attends === 'yes' ? true : this.form.spouse_attends === 'no' ? false : null,
                    _children_attend: this.form.children_attend === 'yes' ? true : this.form.children_attend === 'no' ? false : null,
                    _born_again: this.form.born_again === 'yes' ? true : this.form.born_again === 'no' ? false : null,
                    _other_church: this.otherChurch,
                    _commit_member: this.form.commit_member === 'yes' ? true : this.form.commit_member === 'no' ? false : null,
                    _commit_attendance: this.form.commit_attendance === 'yes' ? true : this.form.commit_attendance === 'no' ? false : null,
                    _commit_leadership: this.form.commit_leadership === 'yes' ? true : this.form.commit_leadership === 'no' ? false : null,
                };
                this.yesNoValue = map[c.field] !== undefined ? map[c.field] : null;
            },

            pickYesNo(val) {
                this.yesNoValue = val;
                const c = this.current;
                const yesNoFields = {
                    _first_time: 'first_time',
                    _water_baptised: 'water_baptised',
                    _speak_to_pastor: 'speak_to_pastor',
                    _spouse_attends: 'spouse_attends',
                    _children_attend: 'children_attend',
                    _born_again: 'born_again',
                    _commit_member: 'commit_member',
                    _commit_attendance: 'commit_attendance',
                    _commit_leadership: 'commit_leadership',
                };
                if (yesNoFields[c.field]) {
                    this.form[yesNoFields[c.field]] = val ? 'yes' : 'no';
                }
                if (c.field === '_has_spouse') {
                    this.hasSpouse = val;
                    if (!val) {
                        this.form.spouse_name = '';
                        this.form.spouse_phone = '';
                        this.form.spouse_email = '';
                        this.form.spouse_attends = '';
                    }
                }
                if (c.field === '_has_children') {
                    this.hasChildren = val;
                    if (!val) {
                        this.form.children_details = '';
                        this.form.children_attend = '';
                    }
                }
                if (c.field === '_has_dependents') {
                    this.hasDependents = val;
                    if (!val) this.form.dependents_details = '';
                }
                if (c.field === '_other_church') {
                    this.otherChurch = val;
                    if (!val) this.form.other_church_details = '';
                }
                if (c.field.startsWith('_commit_') && !val) {
                    this.stepError = 'This commitment is required for membership.';
                    return;
                }
                setTimeout(() => this.forward(), 280);
            },

            pickChoice(value) {
                if (this.current?.field) {
                    this.form[this.current.field] = value;
                }
                setTimeout(() => this.forward(), 280);
            },

            isMultiSelected(field, value) {
                return Array.isArray(this.form[field]) && this.form[field].includes(value);
            },

            toggleMulti(field, value, checked) {
                if (!Array.isArray(this.form[field])) this.form[field] = [];
                if (checked && !this.form[field].includes(value)) {
                    this.form[field].push(value);
                } else if (!checked) {
                    this.form[field] = this.form[field].filter((v) => v !== value);
                }
            },

            validate() {
                const c = this.current;
                if (!c || c.type === 'review') return '';
                if (c.field === '_skip') return '';
                if (c.type === 'yesno' && c.required && this.yesNoValue !== true) {
                    return c.field.startsWith('_commit_') ? 'Please select Yes to continue.' : 'Please choose Yes or No.';
                }
                if (c.type === 'choices' && c.required && !this.form[c.field]) return 'Please choose an option.';
                if (c.type === 'multichoice') return '';
                if (!c.required) return '';
                if (!String(this.form[c.field] || '').trim()) return 'This field is required.';
                if (c.field === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) {
                    return 'Please enter a valid email.';
                }
                return '';
            },

            forward() {
                const err = this.validate();
                if (err) {
                    this.stepError = err;
                    return;
                }
                this.stepError = '';
                if (this.qi < this.queue.length - 1) {
                    this.qi++;
                    this.syncYesNo();
                    this.$nextTick(() => this.$refs.input?.focus());
                }
            },

            back() {
                this.stepError = '';
                if (this.qi > 0) {
                    this.qi--;
                    this.syncYesNo();
                    this.$nextTick(() => this.$refs.input?.focus());
                }
            },

            handleKey(e) {
                if (!this.activeForm || this.submitted) return;
                if (e.key === 'Enter' && !e.shiftKey) {
                    if (['textarea', 'review', 'yesno', 'choices', 'multichoice'].includes(this.current?.type)) return;
                    e.preventDefault();
                    this.forward();
                }
            },

            payloadData() {
                const data = Object.assign({}, this.form);
                delete data._skip;
                if (Array.isArray(data.signup) && data.signup.length) {
                    data['signup[]'] = data.signup;
                }
                if (Array.isArray(data.ministry_serve) && data.ministry_serve.length) {
                    data['ministry_serve[]'] = data.ministry_serve;
                }
                return data;
            },

            submit() {
                this.submitting = true;
                this.submitError = '';
                fetch('/api/send-form', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                    body: JSON.stringify({ form: this.activeForm, data: this.payloadData() }),
                })
                    .then((res) => res.json().then((body) => ({ ok: res.ok, body })))
                    .then((result) => {
                        this.submitting = false;
                        if (result.body && result.body.ok) {
                            this.submitted = true;
                            this.successMessage = result.body.message || this.formMeta.success;
                            return;
                        }
                        this.submitError = (result.body && result.body.message) || 'Unable to send. Please try again.';
                    })
                    .catch(() => {
                        this.submitting = false;
                        this.submitError = 'Network error. Please try again.';
                    });
            },
        };
    }

    document.addEventListener('alpine:init', () => {
        Alpine.data('connectHub', connectHubFactory);
    });
})();
