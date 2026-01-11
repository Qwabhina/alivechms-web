/**
 * AliveChMS UI Components
 * Reusable components using Grid.js, Choices.js, Dropzone, etc.
 * @version 2.0.0
 */

const Components = {
    /**
     * Create a Grid.js table
     * @param {string} selector - Container selector
     * @param {Object} options - Grid.js options
     * @returns {Grid} Grid instance
     */
    createGrid(selector, options = {}) {
        const defaultOptions = {
            className: {
                table: 'table table-hover',
                thead: 'table-light',
                th: 'gridjs-th',
                td: 'gridjs-td'
            },
            pagination: {
                limit: 25,
                summary: true
            },
            search: true,
            sort: true,
            resizable: true,
            language: {
                search: { placeholder: 'Search...' },
                pagination: {
                    previous: '←',
                    next: '→',
                    showing: 'Showing',
                    results: () => 'records'
                }
            }
        };

        const grid = new gridjs.Grid({
            ...defaultOptions,
            ...options
        });

        grid.render(document.querySelector(selector));
        return grid;
    },

    /**
     * Create a Choices.js select
     * @param {string|Element} selector - Select element or selector
     * @param {Object} options - Choices.js options
     * @returns {Choices} Choices instance
     */
    createSelect(selector, options = {}) {
        const element = typeof selector === 'string' ? document.querySelector(selector) : selector;
        if (!element) return null;

        const defaultOptions = {
            removeItemButton: true,
            searchEnabled: true,
            searchPlaceholderValue: 'Search...',
            noResultsText: 'No results found',
            noChoicesText: 'No choices available',
            itemSelectText: '',
            classNames: {
                containerOuter: 'choices',
                containerInner: 'choices__inner form-control'
            }
        };

        return new Choices(element, { ...defaultOptions, ...options });
    },

    /**
     * Create a Dropzone file uploader
     * @param {string} selector - Container selector
     * @param {Object} options - Dropzone options
     * @returns {Dropzone} Dropzone instance
     */
    createDropzone(selector, options = {}) {
        const defaultOptions = {
            maxFilesize: 5, // MB
            acceptedFiles: 'image/*',
            addRemoveLinks: true,
            dictDefaultMessage: '<i class="bi bi-cloud-upload fs-1"></i><br>Drop files here or click to upload',
            dictRemoveFile: 'Remove',
            dictCancelUpload: 'Cancel',
            dictFileTooBig: 'File is too big ({{filesize}}MB). Max: {{maxFilesize}}MB',
            dictInvalidFileType: 'Invalid file type',
            headers: {
                'Authorization': `Bearer ${Auth.getToken()}`
            }
        };

        return new Dropzone(selector, { ...defaultOptions, ...options });
    },

    /**
     * Create a date picker
     * @param {string|Element} selector - Input element or selector
     * @param {Object} options - Flatpickr options
     * @returns {flatpickr} Flatpickr instance
     */
    createDatePicker(selector, options = {}) {
        const defaultOptions = {
            dateFormat: 'Y-m-d',
            allowInput: true,
            disableMobile: false
        };

        return flatpickr(selector, { ...defaultOptions, ...options });
    },

    /**
     * Create a FullCalendar instance
     * @param {string} selector - Container selector
     * @param {Object} options - FullCalendar options
     * @returns {Calendar} Calendar instance
     */
    createCalendar(selector, options = {}) {
        const element = document.querySelector(selector);
        if (!element) return null;

        const defaultOptions = {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listWeek'
            },
            editable: true,
            selectable: true,
            selectMirror: true,
            dayMaxEvents: true,
            weekends: true,
            themeSystem: 'bootstrap5'
        };

        const calendar = new FullCalendar.Calendar(element, { ...defaultOptions, ...options });
        calendar.render();
        return calendar;
    },

    /**
     * Create a Chart.js chart
     * @param {string} selector - Canvas selector
     * @param {string} type - Chart type
     * @param {Object} data - Chart data
     * @param {Object} options - Chart options
     * @returns {Chart} Chart instance
     */
    createChart(selector, type, data, options = {}) {
        const ctx = document.querySelector(selector);
        if (!ctx) return null;

        const defaultOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' }
            }
        };

        return new Chart(ctx, {
            type,
            data,
            options: { ...defaultOptions, ...options }
        });
    },

    /**
     * Create a stepper/wizard component
     * @param {string} selector - Container selector
     * @param {Object} options - Stepper options
     * @returns {Object} Stepper controller
     */
    createStepper(selector, options = {}) {
        const container = document.querySelector(selector);
        if (!container) return null;

        const steps = container.querySelectorAll('.stepper-step');
        const contents = container.querySelectorAll('.stepper-content');
        let currentStep = 0;

        const updateUI = () => {
            steps.forEach((step, index) => {
                step.classList.remove('active', 'completed');
                if (index < currentStep) step.classList.add('completed');
                if (index === currentStep) step.classList.add('active');
            });

            contents.forEach((content, index) => {
                content.classList.toggle('d-none', index !== currentStep);
            });

            // Update buttons
            const prevBtn = container.querySelector('[data-stepper="prev"]');
            const nextBtn = container.querySelector('[data-stepper="next"]');
            const submitBtn = container.querySelector('[data-stepper="submit"]');

            if (prevBtn) prevBtn.disabled = currentStep === 0;
            if (nextBtn) nextBtn.classList.toggle('d-none', currentStep === steps.length - 1);
            if (submitBtn) submitBtn.classList.toggle('d-none', currentStep !== steps.length - 1);

            options.onStepChange?.(currentStep);
        };

        const validateStep = async () => {
            if (options.validateStep) {
                return await options.validateStep(currentStep);
            }
            return true;
        };

        const next = async () => {
            if (await validateStep() && currentStep < steps.length - 1) {
                currentStep++;
                updateUI();
            }
        };

        const prev = () => {
            if (currentStep > 0) {
                currentStep--;
                updateUI();
            }
        };

        const goTo = async (step) => {
            if (step >= 0 && step < steps.length) {
                currentStep = step;
                updateUI();
            }
        };

        // Bind events
        container.querySelector('[data-stepper="prev"]')?.addEventListener('click', prev);
        container.querySelector('[data-stepper="next"]')?.addEventListener('click', next);

        // Allow clicking on completed steps
        steps.forEach((step, index) => {
            step.addEventListener('click', () => {
                if (index < currentStep) goTo(index);
            });
        });

        updateUI();

        return { next, prev, goTo, getCurrentStep: () => currentStep };
    }
};

// Disable Dropzone auto-discover
if (typeof Dropzone !== 'undefined') {
    Dropzone.autoDiscover = false;
}
