$(document).ready(function () {

    // Custom validator methods
    $.validator.addMethod("filesize", function (value, element, param) {
        return this.optional(element) || (element.files[0].size <= param);
    }, "File size must be less than {0}.");

    $.validator.addMethod("extension", function (value, element, param) {
        const allowedExtensions = param.split('|');
        const fileExtension = value.split('.').pop().toLowerCase();
        return this.optional(element) || allowedExtensions.includes(fileExtension);
    }, "Invalid file type. Only {0} are allowed.");

    $.validator.addMethod("regex", function (value, element, regexpr) {
        return regexpr.test(value);
    }, "Invalid format.");

    // Function to initialize validation for a specific form
    function initializeValidation(form) {
        $(form).validate({
            errorPlacement: function (error, element) {
                if (element.is(':checkbox') || element.is(':radio')) {
                    element.closest('.form-group').find('.error-msg').append(error);
                } else {
                    error.insertAfter(element);
                }
            },
            highlight: function (element) {
                $(element).addClass("is-invalid");
            },
            unhighlight: function (element) {
                $(element).removeClass("is-invalid");
            },
            ignore: ".no-validate",
            submitHandler: function (form) {
                if ($(form).valid()) {
                    alert("Form successfully submitted!");
                    form.submit(); // Submit only the valid form
                }
            }
        });

        const fields = [
            {
                type: 'input[type="text"]',
                rules: { required: true, minlength: 2 },
                messages: { required: "This field is required.", minlength: "Enter at least 2 characters." }
            },
            {
                type: 'input[type="email"]',
                rules: {
                    required: true,
                    email: true,
                    regex: /^[^\s@]+@[^\s@]+\.[a-z]{2,}$/i
                },
                messages: {
                    required: "This field is required.",
                    email: "Enter a valid email address.",
                    regex: "Email must contain '@' and end with a valid domain."
                }
            },
            {
                type: 'input[type="number"]',
                rules: { required: true, number: true, min: 1 },
                messages: { required: "This field is required.", number: "Enter a valid number.", min: "Value must be greater than 0." }
            },
            {
                type: 'select',
                rules: { required: true },
                messages: { required: "Please select an option." }
            },
            {
                type: 'textarea',
                rules: { required: true, minlength: 10 },
                messages: { required: "This field is required.", minlength: "Enter at least 10 characters." }
            },
            {
                type: 'input[type="checkbox"]',
                rules: { required: true },
                messages: { required: "You must agree to the terms." }
            },
            {
                type: 'input[type="radio"]',
                rules: { required: true },
                messages: { required: "Please select an option." }
            },
            {
                type: 'input[type="date"]',
                rules: { required: true, date: true },
                messages: { required: "Please select a date.", date: "Enter a valid date." }
            },
            {
                type: 'input[type="file"]',
                rules: {
                    required: true,
                    extension: "jpg|jpeg|png|webp",
                    filesize: 5242880
                },
                messages: {
                    required: "Please upload a file.",
                    extension: "Jpg,png & webp files are allowed.",
                    filesize: "File size must be less than 5MB."
                }
            },
            {
                type: 'input[type="password"]',
                rules: { required: true, minlength: 6 },
                messages: { required: "Password is required.", minlength: "Password must be at least 6 characters long." }
            }
        ];

        fields.forEach(field => {
            $(form).find(field.type).each(function () {
                if (!$(this).hasClass('no-validate')) {
                    $(this).rules("add", {
                        ...field.rules,
                        messages: field.messages
                    });
                }
            });
        });

        $(form).find('.confirm-password').each(function () {
            $(this).rules('add', {
                required: true,
                equalTo: $(form).find('input[type="password"]'),
                messages: {
                    required: "Please confirm your password.",
                    equalTo: "Passwords do not match."
                }
            });
        });
    }

    // Bind validation to each form independently
    $(".validateForm").each(function () {
        const form = $(this); // Get current form
        $(form).on("submit", function (event) {
            event.preventDefault(); // Prevent form submission initially
            initializeValidation(form); // Initialize validation only for the form being submitted

            if ($(form).valid()) {
                $(form).off('submit').submit(); // Submit the form if valid
            }
        });
    });

    // Input restrictions
    $(document).on('input', '.digit-only', function () {
        this.value = this.value.replace(/[^0-9.]/g, '');
    });

    $(document).on('input', '.char-only', function () {
        this.value = this.value.replace(/[^a-zA-Z ]/g, '');
    });

    $(document).on('input', '.char-with-sps', function () {
        this.value = this.value.replace(/[^a-zA-Z!@#\$%\^\&*\)\(+=._-\s]/g, '');
    });

    $('input[type="file"]').on('change', function () {
        $(this).valid();
    });
});
