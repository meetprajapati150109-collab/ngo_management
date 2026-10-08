<?php
// ---------- Session (used to flash the result message across the redirect below) ----------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------- DB connection ----------
include('dbcon.php'); // defines $con

$formSuccess = "";
$formError   = "";
$activeForm  = ""; // 'donate' | 'volunteer' | 'intern' — tells JS which form to reopen after submit

// ---------- Pick up the result of a submission from the previous request ----------
// (see the header("Location: ...") redirects below — this is the "Get" half of
// the Post/Redirect/Get pattern, which is what makes the form fields come back
// empty on reload instead of showing the last-submitted values.)
if (isset($_SESSION['pf_form_flash'])) {
    $flash       = $_SESSION['pf_form_flash'];
    $formSuccess = $flash['success'] ?? '';
    $formError   = $flash['error'] ?? '';
    $activeForm  = $flash['form'] ?? '';
    unset($_SESSION['pf_form_flash']);
}

// ---------- Handle form submissions (Donate / Volunteer / Internship) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type'])) {

    $form_type = $_POST['form_type'];

    // ==========================================================
    // DONATE FORM  -> tbldonator
    // ==========================================================
    if ($form_type === 'donate') {
        $activeForm = 'donate';

        $full_name     = trim($_POST['donate_full_name'] ?? '');
        $email         = trim($_POST['donate_email'] ?? '');
        $phone         = trim($_POST['donate_phone'] ?? '');
        $address       = trim($_POST['donate_address'] ?? '');
        $total_donated = trim($_POST['donate_amount'] ?? '0');

        if ($full_name === '' || $email === '') {
            $formError = "Please fill in your name and email to continue.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $formError = "Please enter a valid email address.";
        } elseif ($total_donated !== '' && !is_numeric($total_donated)) {
            $formError = "Please enter a valid donation amount.";
        } else {
            $total_donated = ($total_donated === '') ? 0.00 : (float) $total_donated;

            // Donators don't log in to this site, so we generate a
            // random password internally just to satisfy the NOT NULL column.
            // (Only used the first time a given email donates — see ON DUPLICATE
            // KEY UPDATE below, which does not touch the password on repeat donations.)
            $randomPassword = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
            $status = 'active';

            // Email has a UNIQUE constraint on tbldonator, so a returning donor
            // donating again with the same email would normally fail. Instead of
            // blocking them, we add their new amount on top of their existing
            // total_donated and refresh their contact details.
            $stmt = $con->prepare(
                "INSERT INTO tbldonator
                    (full_name, email, phone, address, password, total_donated, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE
                    full_name     = VALUES(full_name),
                    phone         = IF(VALUES(phone) <> '', VALUES(phone), phone),
                    address       = IF(VALUES(address) <> '', VALUES(address), address),
                    total_donated = total_donated + VALUES(total_donated),
                    updated_at    = NOW()"
            );
            $stmt->bind_param(
                "sssssds",
                $full_name, $email, $phone, $address, $randomPassword, $total_donated, $status
            );

            if ($stmt->execute()) {
                // affected_rows is 1 for a brand-new donator, 2 for an existing
                // one that was just updated (MySQL's ON DUPLICATE KEY UPDATE convention).
                if ($stmt->affected_rows > 1) {
                    $formSuccess = "Thank you, $full_name! Your new donation has been added to your existing record.";
                } else {
                    $formSuccess = "Thank you, $full_name! Your donation interest has been recorded. Our team will contact you shortly.";
                }
            } else {
                $formError = "Something went wrong. Please try again.";
            }
            $stmt->close();
        }
    }

    // ==========================================================
    // VOLUNTEER FORM  -> tblvolunteer
    // ==========================================================
    elseif ($form_type === 'volunteer') {
        $activeForm = 'volunteer';

        $full_name    = trim($_POST['volunteer_full_name'] ?? '');
        $email        = trim($_POST['volunteer_email'] ?? '');
        $phone        = trim($_POST['volunteer_phone'] ?? '');
        $address      = trim($_POST['volunteer_address'] ?? '');
        $skills       = trim($_POST['volunteer_skills'] ?? '');
        $availability = trim($_POST['volunteer_availability'] ?? '');

        if ($full_name === '' || $email === '') {
            $formError = "Please fill in your name and email to continue.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $formError = "Please enter a valid email address.";
        } else {
            // Email has a UNIQUE constraint on tblvolunteer. Instead of blocking
            // a returning visitor who fills the form again (e.g. to update their
            // skills or availability), update their existing record.
            $randomPassword = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
            $status = 'active';
            $joined_date = date('Y-m-d');

            $stmt = $con->prepare(
                "INSERT INTO tblvolunteer
                    (full_name, email, phone, address, skills, availability, password, joined_date, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE
                    full_name    = VALUES(full_name),
                    phone        = IF(VALUES(phone) <> '', VALUES(phone), phone),
                    address      = IF(VALUES(address) <> '', VALUES(address), address),
                    skills       = IF(VALUES(skills) <> '', VALUES(skills), skills),
                    availability = IF(VALUES(availability) <> '', VALUES(availability), availability),
                    updated_at   = NOW()"
            );
            $stmt->bind_param(
                "sssssssss",
                $full_name, $email, $phone, $address, $skills, $availability, $randomPassword, $joined_date, $status
            );

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 1) {
                    $formSuccess = "Thank you, $full_name! Your volunteer details have been updated.";
                } else {
                    $formSuccess = "Thank you, $full_name! Your volunteer application has been received.";
                }
            } else {
                $formError = "Something went wrong. Please try again.";
            }
            $stmt->close();
        }
    }

    // ==========================================================
    // INTERNSHIP FORM  -> tblintern
    // ==========================================================
    elseif ($form_type === 'intern') {
        $activeForm = 'intern';

        $full_name       = trim($_POST['intern_full_name'] ?? '');
        $email           = trim($_POST['intern_email'] ?? '');
        $phone           = trim($_POST['intern_phone'] ?? '');
        $institution     = trim($_POST['intern_institution'] ?? '');
        $course          = trim($_POST['intern_course'] ?? '');
        $duration_months = trim($_POST['intern_duration'] ?? '');
        $start_date      = trim($_POST['intern_start_date'] ?? '');
        $end_date        = trim($_POST['intern_end_date'] ?? '');

        if ($full_name === '' || $email === '') {
            $formError = "Please fill in your name and email to continue.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $formError = "Please enter a valid email address.";
        } elseif ($duration_months !== '' && !ctype_digit($duration_months)) {
            $formError = "Duration (months) must be a whole number.";
        } elseif ($start_date !== '' && $end_date !== '' && strtotime($end_date) < strtotime($start_date)) {
            $formError = "End Date cannot be before Start Date.";
        } else {
            $duration_months = ($duration_months === '') ? null : (int) $duration_months;
            $start_date      = ($start_date === '') ? null : $start_date;
            $end_date        = ($end_date === '') ? null : $end_date;

            // Email has a UNIQUE constraint on tblintern. Instead of blocking a
            // returning applicant who fills the form again (e.g. to update their
            // course or dates), update their existing record. Nullable columns
            // use COALESCE so a blank resubmission doesn't wipe a previously
            // saved value.
            $randomPassword = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
            $status = 'active';

            $stmt = $con->prepare(
                "INSERT INTO tblintern
                    (full_name, email, phone, institution, course, duration_months, start_date, end_date, password, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE
                    full_name       = VALUES(full_name),
                    phone           = IF(VALUES(phone) <> '', VALUES(phone), phone),
                    institution     = IF(VALUES(institution) <> '', VALUES(institution), institution),
                    course          = IF(VALUES(course) <> '', VALUES(course), course),
                    duration_months = COALESCE(VALUES(duration_months), duration_months),
                    start_date      = COALESCE(VALUES(start_date), start_date),
                    end_date        = COALESCE(VALUES(end_date), end_date),
                    updated_at      = NOW()"
            );
            $stmt->bind_param(
                "sssssissss",
                $full_name, $email, $phone, $institution, $course, $duration_months, $start_date, $end_date, $randomPassword, $status
            );

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 1) {
                    $formSuccess = "Thank you, $full_name! Your internship details have been updated.";
                } else {
                    $formSuccess = "Thank you, $full_name! Your internship application has been received.";
                }
            } else {
                $formError = "Something went wrong. Please try again.";
            }
            $stmt->close();
        }
    }

    // ---------- Post/Redirect/Get: flash the result, then redirect to a plain
    // GET request. This is what makes the form come back empty on submit or
    // reload — there is no leftover $_POST for the fields to be repopulated from.
    $_SESSION['pf_form_flash'] = [
        'success' => $formSuccess,
        'error'   => $formError,
        'form'    => $activeForm,
    ];
    header("Location: index.php" . ($activeForm !== '' ? '#' . $activeForm . '-form' : ''));
    exit;
}

include('header.php');
?>

            <!--Banner Wrap Start-->
            <style>
                .iq_banner{ overflow:hidden; }
                .iq_banner .slider{ height:100%; }
                .iq_banner .slider li{
                    position:relative;
                    height:100%;
                    overflow:hidden;
                }
                .iq_banner .slider li a{ display:block; height:100%; }
                .iq_banner .slider li img{
                    width:100%;
                    height:100%;
                    object-fit:cover;
                    object-position:center;
                }
                /* Force the caption lines to stack in document order
                   (title -> welcome line -> lorem line -> button) instead
                   of the theme's absolute positioning, which was placing
                   the lorem line off to the side instead of underneath
                   the welcome line. */
                .iq-caption{
                    display:flex;
                    flex-direction:column;
                }
                .iq-caption-title,
                .iq-caption-text,
                .iq-caption-contant{
                    position:static !important;
                    display:block;
                }
                /* Keep .iq_link_1 relatively positioned so its ::before hover-swipe
                pseudo-element (position:absolute, width:150%) stays clipped to the
                button instead of escaping to fill the whole caption area. */
                .iq-caption .iq_link_1{
                    position:relative !important;
                    display:block;
                    overflow:hidden;
                }
                .iq-caption-text{ margin-top:10px; }
                .iq-caption-contant{ margin-top:10px; }
                .iq-caption .iq_link_1{ margin-top:20px; align-self:flex-start; }
                .iq-caption-wrapper.text-center .iq-caption{ align-items:center; }
                .iq-caption-wrapper.text-center .iq-caption .iq_link_1{ align-self:center; }
                .iq-caption-wrapper.text-right .iq-caption{ align-items:flex-end; }
                .iq-caption-wrapper.text-right .iq-caption .iq_link_1{ align-self:flex-end; }
                /* Belt-and-braces: hide Slick's arrow buttons even if JS re-adds them */
                .iq_banner .slick-arrow,
                .iq_banner .slick-prev,
                .iq_banner .slick-next{
                    display:none !important;
                }
            </style>
            <div class="iq_banner">
                <ul class="slider">
                    <li class="">
                        <!--Banner Thumb START-->
                        <a href="clothsellsema.php" data-rel="prettyphoto[]">
                            <img src="extra-images/carousel1.jpg" alt="Image here">
                            <div class="iq-caption-wrapper text-left">
                                <div class="iq_banner_caption container">
                                    <div class="iq-caption">
                                        <div class="iq-caption-title">Cloth Sell Sema</div>
                                        <div class="iq-caption-text">welcome to Pratibha Foundation</div>
                                        <div class="iq-caption-contant">Sunday March 15, 2026.</div>
                                        <div class="iq_link_1">Read More</div>
                                    </div>
                                </div>
                            </div>
                        </a>
                        <!--Banner Thumb End-->
                    </li>
                    <li class="">
                        <!--Banner Thumb START-->
                        <a href="#" data-rel="prettyphoto[]">
                            <img src="extra-images/carousel2.png" alt="Image here">
                            <div class="iq-caption-wrapper text-center">
                                <div class="iq_banner_caption container">
                                    <div class="iq-caption">
                                        <div class="iq-caption-title">Svarg - Keraoke Activity</div>
                                        <div class="iq-caption-text">welcome to Pratibha Foundation</div>
                                        <div class="iq-caption-contant">Satuday December 27,2025.</div>
                                        <div class="iq_link_1">Read More</div>
                                    </div>
                                </div>
                            </div>
                        </a>
                        <!--Banner Thumb End-->
                    </li>
                    <li class="">
                        <!--Banner Thumb START-->
                        <a href="" data-rel="prettyphoto[]">
                            <img src="extra-images/carousel3.jpg" alt="Image here">
                            <div class="iq-caption-wrapper text-right">
                                <div class="iq_banner_caption container">
                                    <div class="iq-caption">
                                        <div class="iq-caption-title">सफलता की दौड़</div>
                                        <div class="iq-caption-text">welcome to Pratibha Foundation</div>
                                        <div class="iq-caption-contant">March 6-9, 2025.</div>
                                        <div class="iq_link_1">Read More</div>
                                    </div>
                                </div>
                            </div>
                        </a>
                        <!--Banner Thumb End-->
                    </li>
                </ul>
            </div>
            <!--Banner Wrap End-->

            <!--Iqoniq Content Start-->
            <div class="iq_content_wrap">

                <!--We Are Professional Wrap Start-->
                <div class="we_professional_bg">
                    <div class="container">
                        <div class="row">
                            <div class="col-md-8">
                                <section>
                                <!--Heading Wrap Start-->
                                <div class="iq_heading_1 text-left">
                                    <pre><h4>Get<span>Involved</span></h4></pre>
                                    <p>There are many ways you can support Pratibha Foundation. Choose an option below
                                    <br>and fill out the form on the right — every contribution makes a difference.</p>
                                </div>
                                <!--Heading Wrap End-->
                                <style>
                                    .involve-row{ display:flex; flex-wrap:wrap; }
                                    .involve-row > [class*="col-"]{ display:flex; }
                                    .involve-card{
                                        display:flex;
                                        flex-direction:column;
                                        width:100%;
                                    }
                                    .involve-card p{ flex:1 0 auto; }
                                    .involve-card .iq_link_1{
                                        margin-top:auto;
                                        border:none;
                                        cursor:pointer;
                                        width:100%;
                                    }
                                    .iq_search_courses .iq-input input[type="text"],
                                    .iq_search_courses .iq-input input[type="email"],
                                    .iq_search_courses .iq-input input[type="tel"],
                                    .iq_search_courses .iq-input input[type="number"],
                                    .iq_search_courses .iq-input input[type="date"]{
                                        width:100%;
                                        box-sizing:border-box;
                                        display:block;
                                        text-transform:none !important;
                                    }
                                </style>
                                <div class="row involve-row">
                                    <!--Option Card: Donate -->
                                    <div class="col-md-4 col-sm-6">
                                        <div class="iq_professional_services involve-card">
                                            <span class="icon-medical-3">
                                                <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span><span class="path7"></span><span class="path8"></span><span class="path9"></span><span class="path10"></span><span class="path11"></span><span class="path12"></span><span class="path13"></span><span class="path14"></span><span class="path15"></span><span class="path16"></span><span class="path17"></span><span class="path18"></span><span class="path19"></span><span class="path20"></span><span class="path21"></span><span class="path22"></span><span class="path23"></span><span class="path24"></span>
                                            </span>
                                            <h5><a href="#donate-form" class="option-btn" data-form="donate">Donate</a></h5>
                                            <p>Support our cause with a financial contribution and help us reach more people in need.</p>
                                            <button type="button" class="iq_link_1 option-btn" data-form="donate">Donate</button>
                                        </div>
                                    </div>
                                    <!--Option Card: Volunteer -->
                                    <div class="col-md-4 col-sm-6">
                                        <div class="iq_professional_services involve-card">
                                            <span class="icon-lecture">
                                                <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span><span class="path7"></span><span class="path8"></span><span class="path9"></span><span class="path10"></span><span class="path11"></span><span class="path12"></span><span class="path13"></span><span class="path14"></span><span class="path15"></span><span class="path16"></span><span class="path17"></span><span class="path18"></span><span class="path19"></span><span class="path20"></span><span class="path21"></span><span class="path22"></span><span class="path23"></span><span class="path24"></span><span class="path25"></span><span class="path26"></span><span class="path27"></span><span class="path28"></span><span class="path29"></span><span class="path30"></span><span class="path31"></span><span class="path32"></span><span class="path33"></span><span class="path34"></span><span class="path35"></span><span class="path36"></span><span class="path37"></span><span class="path38"></span>
                                            </span>
                                            <h5><a href="#volunteer-form" class="option-btn" data-form="volunteer">Volunteer</a></h5>
                                            <p>Give your time and skills to help us run programs and events on the ground.</p>
                                            <button type="button" class="iq_link_1 option-btn" data-form="volunteer">Volunteer</button>
                                        </div>
                                    </div>
                                    <!--Option Card: Internship -->
                                    <div class="col-md-4 col-sm-6">
                                        <div class="iq_professional_services involve-card">
                                            <span class="icon-diploma-1">
                                                <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span><span class="path7"></span><span class="path8"></span><span class="path9"></span><span class="path10"></span><span class="path11"></span><span class="path12"></span><span class="path13"></span><span class="path14"></span><span class="path15"></span><span class="path16"></span><span class="path17"></span><span class="path18"></span><span class="path19"></span><span class="path20"></span><span class="path21"></span><span class="path22"></span><span class="path23"></span><span class="path24"></span><span class="path25"></span><span class="path26"></span><span class="path27"></span><span class="path28"></span>
                                            </span>
                                            <h5><a href="#intern-form" class="option-btn" data-form="intern">Internship</a></h5>
                                            <p>Gain real-world experience while contributing to meaningful social work.</p>
                                            <button type="button" class="iq_link_1 option-btn" data-form="intern">Internship</button>
                                        </div>
                                    </div>
                                </div>
                                <!--Professional Services Wrap End-->
                                </section>
                            </div>
                            <!--Search Wrap Start-->
                            <div class="col-md-4">
                                <div class="iq_search_courses">

                                    <!-- ---------- Placeholder shown before any option is picked ---------- -->
                                    <div id="option-placeholder" class="option-form" style="display:none;">
                                        <h4>Get Involved</h4>
                                        <p style="padding:0 20px 20px;">Select Donate, Volunteer, or Internship on the left to open the sign-up form here.</p>
                                    </div>

                                    <!-- ---------- Server-side response message ---------- -->
                                    <?php if ($formSuccess !== ''): ?>
                                        <div class="iq-input" style="padding:15px 20px;">
                                            <p style="color:#2e7d32; font-weight:600;"><?php echo htmlspecialchars($formSuccess); ?></p>
                                        </div>
                                    <?php elseif ($formError !== ''): ?>
                                        <div class="iq-input" style="padding:15px 20px;">
                                            <p style="color:#c62828; font-weight:600;"><?php echo htmlspecialchars($formError); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <!-- ---------- Donate Form ---------- -->
                                    <div id="donate-form" class="option-form" style="display:block;">
                                        <h4>Donate</h4>
                                        <form method="POST" action="index.php#donate-form">
                                            <input type="hidden" name="form_type" value="donate">
                                            <div class="iq-input">
                                                <input type="text" name="donate_full_name" placeholder="Full Name" required
                                                       value="<?php echo ($activeForm === 'donate' && isset($_POST['donate_full_name'])) ? htmlspecialchars($_POST['donate_full_name']) : ''; ?>">
                                            </div>
                                            <div class="iq-input">
                                                <input type="email" name="donate_email" placeholder="E-mail" required autocapitalize="off" autocorrect="off" spellcheck="false"
                                                       value="<?php echo ($activeForm === 'donate' && isset($_POST['donate_email'])) ? htmlspecialchars($_POST['donate_email']) : ''; ?>">
                                            </div>
                                            <div class="iq-input">
                                                <input type="tel" name="donate_phone" placeholder="Mobile No"
                                                       value="<?php echo ($activeForm === 'donate' && isset($_POST['donate_phone'])) ? htmlspecialchars($_POST['donate_phone']) : ''; ?>">
                                            </div>
                                            <div class="iq-input">
                                                <input type="text" name="donate_address" placeholder="Address"
                                                       value="<?php echo ($activeForm === 'donate' && isset($_POST['donate_address'])) ? htmlspecialchars($_POST['donate_address']) : ''; ?>">
                                            </div>
                                            <div class="iq-input">
                                                <input type="number" step="0.01" min="0" name="donate_amount" placeholder="Donation Amount (₹)"
                                                       value="<?php echo ($activeForm === 'donate' && isset($_POST['donate_amount'])) ? htmlspecialchars($_POST['donate_amount']) : ''; ?>">
                                            </div>
                                            <div class="iq-input">
                                                <input class="iq_link_1" type="submit" value="Donate">
                                            </div>
                                        </form>
                                    </div>

                                    <!-- ---------- Volunteer Form ---------- -->
                                    <div id="volunteer-form" class="option-form" style="display:none;">
                                        <h4>Volunteer</h4>
                                        <form method="POST" action="index.php#volunteer-form">
                                            <input type="hidden" name="form_type" value="volunteer">
                                            <div class="iq-input">
                                                <input type="text" name="volunteer_full_name" placeholder="Full Name" required
                                                       value="<?php echo ($activeForm === 'volunteer' && isset($_POST['volunteer_full_name'])) ? htmlspecialchars($_POST['volunteer_full_name']) : ''; ?>">
                                            </div>
                                            <div class="iq-input">
                                                <input type="email" name="volunteer_email" placeholder="E-mail" required autocapitalize="off" autocorrect="off" spellcheck="false"
                                                       value="<?php echo ($activeForm === 'volunteer' && isset($_POST['volunteer_email'])) ? htmlspecialchars($_POST['volunteer_email']) : ''; ?>">
                                            </div>
                                            <div class="iq-input">
                                                <input type="tel" name="volunteer_phone" placeholder="Mobile No"
                                                       value="<?php echo ($activeForm === 'volunteer' && isset($_POST['volunteer_phone'])) ? htmlspecialchars($_POST['volunteer_phone']) : ''; ?>">
                                            </div>
                                            <div class="iq-input">
                                                <input type="text" name="volunteer_address" placeholder="Address"
                                                       value="<?php echo ($activeForm === 'volunteer' && isset($_POST['volunteer_address'])) ? htmlspecialchars($_POST['volunteer_address']) : ''; ?>">
                                            </div>
                                            <div class="iq-input">
                                                <input type="text" name="volunteer_skills" placeholder="Skills (e.g. Teaching, First Aid)"
                                                       value="<?php echo ($activeForm === 'volunteer' && isset($_POST['volunteer_skills'])) ? htmlspecialchars($_POST['volunteer_skills']) : ''; ?>">
                                            </div>
                                            <div class="iq-input">
                                                <input type="text" name="volunteer_availability" placeholder="Availability (e.g. Weekends)"
                                                       value="<?php echo ($activeForm === 'volunteer' && isset($_POST['volunteer_availability'])) ? htmlspecialchars($_POST['volunteer_availability']) : ''; ?>">
                                            </div>
                                            <div class="iq-input">
                                                <input class="iq_link_1" type="submit" value="Volunteer">
                                            </div>
                                        </form>
                                    </div>

                                    <!-- ---------- Internship Form ---------- -->
                                    <div id="intern-form" class="option-form" style="display:none;">
                                        <h4>Internship</h4>
                                        <form method="POST" action="index.php#intern-form">
                                            <input type="hidden" name="form_type" value="intern">
                                            <div class="iq-input">
                                                <input type="text" name="intern_full_name" placeholder="Full Name" required
                                                       value="<?php echo ($activeForm === 'intern' && isset($_POST['intern_full_name'])) ? htmlspecialchars($_POST['intern_full_name']) : ''; ?>">
                                            </div>
                                            <div class="iq-input">
                                                <input type="email" name="intern_email" placeholder="E-mail" required autocapitalize="off" autocorrect="off" spellcheck="false"
                                                       value="<?php echo ($activeForm === 'intern' && isset($_POST['intern_email'])) ? htmlspecialchars($_POST['intern_email']) : ''; ?>">
                                            </div>
                                            <div class="iq-input">
                                                <input type="tel" name="intern_phone" placeholder="Mobile No"
                                                       value="<?php echo ($activeForm === 'intern' && isset($_POST['intern_phone'])) ? htmlspecialchars($_POST['intern_phone']) : ''; ?>">
                                            </div>
                                            <div class="iq-input">
                                                <input type="text" name="intern_institution" placeholder="Institution"
                                                       value="<?php echo ($activeForm === 'intern' && isset($_POST['intern_institution'])) ? htmlspecialchars($_POST['intern_institution']) : ''; ?>">
                                            </div>
                                            <div class="iq-input">
                                                <input type="text" name="intern_course" placeholder="Course"
                                                       value="<?php echo ($activeForm === 'intern' && isset($_POST['intern_course'])) ? htmlspecialchars($_POST['intern_course']) : ''; ?>">
                                            </div>
                                            <div class="iq-input">
                                                <input type="number" min="1" step="1" name="intern_duration" placeholder="Duration (Months)"
                                                       value="<?php echo ($activeForm === 'intern' && isset($_POST['intern_duration'])) ? htmlspecialchars($_POST['intern_duration']) : ''; ?>">
                                            </div>
                                            <div class="iq-input">
                                                <input type="date" name="intern_start_date" placeholder="Start Date"
                                                       value="<?php echo ($activeForm === 'intern' && isset($_POST['intern_start_date'])) ? htmlspecialchars($_POST['intern_start_date']) : ''; ?>">
                                            </div>
                                            <div class="iq-input">
                                                <input type="date" name="intern_end_date" placeholder="End Date"
                                                       value="<?php echo ($activeForm === 'intern' && isset($_POST['intern_end_date'])) ? htmlspecialchars($_POST['intern_end_date']) : ''; ?>">
                                            </div>
                                            <div class="iq-input">
                                                <input class="iq_link_1" type="submit" value="Apply">
                                            </div>
                                        </form>
                                    </div>

                                </div>
                            </div>
                            <!--Search Wrap End-->
                        </div>
                    </div>
                </div>
                <!--We Are Professional Wrap End-->

                <!--Find Us / Contact Wrap Start-->
                <style>
                    .iq_find_us_bg{ padding:70px 0; background:#f7f7f7; }
                    .iq_find_us_map{
                        width:100%;
                        height:400px;
                        border:0;
                        border-radius:4px;
                        box-shadow:0 1px 6px rgba(0,0,0,.1);
                    }
                    .iq_find_us_details{
                        background:#fff;
                        height:400px;
                        padding:35px 30px;
                        border-radius:4px;
                        box-shadow:0 1px 6px rgba(0,0,0,.1);
                        display:flex;
                        flex-direction:column;
                        justify-content:space-between;
                    }
                    .iq_find_us_details h4{ margin-top:0; }
                    .iq_find_us_details h4 span{ color:#f7a416; }
                    .iq_find_us_info li{
                        list-style:none;
                        display:flex;
                        align-items:flex-start;
                        margin-bottom:14px;
                        font-size:14px;
                        color:#555;
                    }
                    .iq_find_us_info li i{
                        color:#f7a416;
                        width:22px;
                        font-size:16px;
                        margin-top:2px;
                    }
                    .iq_find_us_social{ padding:0; margin:20px 0 0; }
                    .iq_find_us_social li{
                        list-style:none;
                        display:inline-block;
                        margin-right:10px;
                    }
                    .iq_find_us_social li a{
                        display:inline-flex;
                        align-items:center;
                        justify-content:center;
                        width:40px;
                        height:40px;
                        border-radius:50%;
                        background:#f7a416;
                        color:#fff;
                        font-size:16px;
                        transition:background .2s ease;
                        cursor:pointer;
                    }
                    .iq_find_us_social li a:hover{ background:#333; color:#fff; }
                    .iq_find_us_social li a.iq_copied{ background:#28a745; }
                </style>
                <section class="iq_find_us_bg">
                    <div class="container">
                        <div class="iq_heading_1 text-center">
                            <h4>Find <span>Us</span></h4>
                            <p>Reach out, visit us, or connect on social media — we would love to hear from you.</p>
                        </div>
                        <div class="row">
                            <div class="col-md-7 col-sm-12">
                                <iframe class="iq_find_us_map"
                                    src="https://www.google.com/maps?q=B-12+MBC+and+Das+Patel+Residency,+Near+Saint+Joseph+School,+Old+Chhani+Road,+Vadodara,+Gujarat+390002&output=embed"
                                    allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                                </iframe>
                            </div>
                            <div class="col-md-5 col-sm-12">
                                <div class="iq_find_us_details">
                                    <div>
                                        <h4>Contact <span>Info</span></h4>
                                        <ul class="iq_find_us_info">
                                            <li><i class="fa fa-map-marker"></i>&nbsp; B-12 MBC and Das Patel Residency, Near Saint Joseph School, Old Chhani Road, Vadodara, Gujarat 390002</li>
                                            <li><i class="fa fa-phone"></i>&nbsp; +91 94262 33488</li>
                                            <li><i class="fa fa-envelope"></i>&nbsp; foundation.pratibha@gmail.com</li>
                                            <li><i class="fa fa-clock-o"></i>&nbsp; Mon - Sat: 9:00 AM - 6:00 PM</li>
                                        </ul>
                                    </div>
                                    <ul class="iq_find_us_social">
                                        <li>
                                            <a href="#" target="_blank" title="Facebook"><i class="fa fa-facebook"></i></a>
                                        </li>
                                        <li>
                                            <a href="#" target="_blank" title="Instagram"><i class="fa fa-instagram"></i></a>
                                        </li>
                                        <li>
                                            <a href="#" target="_blank" title="Twitter"><i class="fa fa-twitter"></i></a>
                                        </li>
                                        <li>
                                            <a href="tel:+919426233488" title="Call +91 94262 33488"><i class="fa fa-phone"></i></a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <!--Find Us / Contact Wrap End-->

                <!--Number Counter Wrap Start-->
                <section class="iq_number_count_bg">
                    <div class="container">
                        <div class="row">
                            <div class="col-md-3 col-sm-3">
                                <div class="iq_counter">
                                    <i class="icon-lecture">
                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span><span class="path7"></span><span class="path8"></span><span class="path9"></span><span class="path10"></span><span class="path11"></span><span class="path12"></span><span class="path13"></span><span class="path14"></span><span class="path15"></span><span class="path16"></span><span class="path17"></span><span class="path18"></span><span class="path19"></span><span class="path20"></span><span class="path21"></span><span class="path22"></span><span class="path23"></span><span class="path24"></span><span class="path25"></span><span class="path26"></span><span class="path27"></span><span class="path28"></span><span class="path29"></span><span class="path30"></span><span class="path31"></span><span class="path32"></span><span class="path33"></span><span class="path34"></span><span class="path35"></span><span class="path36"></span><span class="path37"></span><span class="path38"></span>
                                    </i>
                                    <span class="counter">850+</span>
                                    <h6>Total Volunteers</h6>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-3">
                                <div class="iq_counter">
                                    <i class="icon-books">
                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span><span class="path7"></span><span class="path8"></span><span class="path9"></span><span class="path10"></span><span class="path11"></span><span class="path12"></span><span class="path13"></span><span class="path14"></span><span class="path15"></span><span class="path16"></span><span class="path17"></span>
                                    </i>
                                    <span class="counter">33500+</span>
                                    <h6>Total Children</h6>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-3">
                                <div class="iq_counter">
                                    <i class="icon-drawing">
                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span><span class="path7"></span><span class="path8"></span><span class="path9"></span><span class="path10"></span><span class="path11"></span><span class="path12"></span><span class="path13"></span><span class="path14"></span><span class="path15"></span><span class="path16"></span><span class="path17"></span><span class="path18"></span><span class="path19"></span><span class="path20"></span><span class="path21"></span><span class="path22"></span><span class="path23"></span><span class="path24"></span><span class="path25"></span><span class="path26"></span><span class="path27"></span><span class="path28"></span><span class="path29"></span><span class="path30"></span><span class="path31"></span><span class="path32"></span><span class="path33"></span><span class="path34"></span><span class="path35"></span>
                                    </i>
                                    <span class="counter">1591</span>
                                    <h6>Total Donors</h6>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-3">
                                <div class="iq_counter">
                                    <i class="icon-diploma-1">
                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span><span class="path7"></span><span class="path8"></span><span class="path9"></span><span class="path10"></span><span class="path11"></span><span class="path12"></span><span class="path13"></span><span class="path14"></span><span class="path15"></span><span class="path16"></span><span class="path17"></span><span class="path18"></span><span class="path19"></span><span class="path20"></span><span class="path21"></span><span class="path22"></span><span class="path23"></span><span class="path24"></span><span class="path25"></span><span class="path26"></span><span class="path27"></span><span class="path28"></span>
                                    </i>
                                    <span class="counter">9921</span>
                                    <h6>Total Interns</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <!--Number Counter Wrap End-->

            <!-- ---------- Get Involved: option-card <-> form toggle ---------- -->
            <script>
            (function () {
                'use strict';

                var buttons     = document.querySelectorAll('.option-btn');
                var forms       = document.querySelectorAll('.option-form');
                var placeholder = document.getElementById('option-placeholder');

                function showForm(formKey) {
                    forms.forEach(function (form) {
                        form.style.display = 'none';
                    });
                    if (placeholder) {
                        placeholder.style.display = 'none';
                    }
                    var target = document.getElementById(formKey + '-form');
                    if (target) {
                        target.style.display = 'block';
                    }
                }

                buttons.forEach(function (btn) {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        var formKey = btn.getAttribute('data-form');
                        showForm(formKey);
                    });
                });

                // If the server just processed a submission, reopen that form
                // automatically so the success/error message and form field
                // values (on validation failure) are visible where they belong.
                var activeForm = <?php echo json_encode($activeForm); ?> || 'donate';
                showForm(activeForm);
            })();
            </script>

<?php include('footer.php'); ?>

            <!-- ---------- Banner slider: automatic movement (arrow-click simulator) ----------
                 We stopped trying to reconfigure Slick's own autoplay option —
                 whatever script initializes it elsewhere in the theme keeps
                 winning that race regardless of load order. Instead, this just
                 finds Slick's own auto-generated "Next" button (hidden by our
                 CSS above) and clicks it on a timer, exactly like a visitor
                 would. This works no matter which script initialized the
                 slider or when, because we're not touching its config at all —
                 just simulating a click on the control it already provides. -->
            <script>
            (function () {
                'use strict';
                var INTERVAL_MS = 3000; // how often to auto-advance
                var timer = null;

                function getNextBtn() {
                    var banner = document.querySelector('.iq_banner');
                    return banner ? banner.querySelector('.slick-next') : null;
                }

                function startAutoplay() {
                    if (timer) return; // already running
                    timer = setInterval(function () {
                        var btn = getNextBtn();
                        if (btn) { btn.click(); }
                    }, INTERVAL_MS);
                }

                function stopAutoplay() {
                    if (timer) { clearInterval(timer); timer = null; }
                }

                // Keep checking until Slick has generated its Next button
                // (it only exists once Slick finishes initializing), then
                // start clicking it automatically.
                var attempts = 0;
                var waitForSlider = setInterval(function () {
                    attempts++;
                    if (getNextBtn()) {
                        clearInterval(waitForSlider);
                        startAutoplay();
                        var banner = document.querySelector('.iq_banner');
                        if (banner) {
                            banner.addEventListener('mouseenter', stopAutoplay);
                            banner.addEventListener('mouseleave', startAutoplay);
                        }
                    } else if (attempts > 100) { // ~10s safety cutoff
                        clearInterval(waitForSlider);
                    }
                }, 100);
            })();
            </script>