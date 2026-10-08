<!DOCTYPE html>
<html lang="en">

<head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Pratibha-Foundation</title>
        <!-- Bootstrap -->
        <link href="css/bootstrap.css" rel="stylesheet">
        <!--SLICK SLIDER CSS-->
        <link rel="stylesheet" type="text/css" href="css/slick.css"/>
        <!-- Font Awesome StyleSheet CSS -->
        <link href="css/font-awesome.min.css" rel="stylesheet">
        <!-- Font Awesome StyleSheet CSS -->
        <link href="css/svg.css" rel="stylesheet">
        <!-- Pretty Photo CSS -->
        <link href="css/prettyPhoto.css" rel="stylesheet">
        <!-- Shortcodes CSS -->
        <link href="css/shortcodes.css" rel="stylesheet">
        <!-- Widget CSS -->
        <link href="css/widget.css" rel="stylesheet">
        <!-- Typography CSS -->
        <link href="css/typography.css" rel="stylesheet">
        <!-- Custom Main StyleSheet CSS -->
        <link href="style.css" rel="stylesheet">
        <!-- Color CSS -->
        <link href="css/color.css" rel="stylesheet">
        <!-- Responsive CSS -->
        <link href="css/responsive.css" rel="stylesheet">

        <!-- ===== Custom fixes: responsive navbar + banner ===== -->
        <style>
            /* ---- Top strip ---- */
            .iq_top_strip_new{
                background:#f7f7f7;
                border-bottom:1px solid #eee;
                font-size:13px;
                padding:6px 0;
                color:#777;
            }
            .iq_top_strip_new a{ color:#777; margin-right:15px; text-decoration:none; }
            .iq_top_strip_new a:hover{ color:#f7a416; }
            .iq_top_strip_new a.iq_copy_trigger{ cursor:pointer; }
            .iq_top_strip_new a.iq_copy_trigger .iq_copy_value{ text-decoration:underline dotted; }
            .iq_top_strip_new a.iq_copy_trigger.iq_copied .iq_copy_value{ color:#28a745; text-decoration:none; }
            .iq_top_strip_new .iq_time_wrap{ float:none; }

            /* ---- Navbar ---- */
            .iq_main_navbar{
                background:#fff;
                border:none;
                margin-bottom:0;
                box-shadow:0 1px 4px rgba(0,0,0,.08);
                border-radius:0;
            }
            .iq_main_navbar .navbar-brand{ height:auto; padding:10px 15px; }
            .iq_main_navbar .navbar-brand img{ max-height:60px; width:auto; }
            .iq_main_navbar .navbar-nav > li > a{
                color:#333;
                font-size:14px;
                text-transform:capitalize;
                padding:22px 14px;
                transition:color .2s ease;
            }
            .iq_main_navbar .navbar-nav > li > a:hover,
            .iq_main_navbar .navbar-nav > li.active > a{
                color:#f7a416;
                background:transparent;
            }
            .iq_main_navbar .navbar-toggle{
                border-color:#f7a416;
                margin-top:20px;
            }
            .iq_main_navbar .navbar-toggle .icon-bar{ background:#f7a416; }
            .iq_main_navbar .navbar-toggle:hover,
            .iq_main_navbar .navbar-toggle:focus{ background:#fdf1dc; }
            .iq_search_btn{
                background:#f7a416;
                border:none;
                color:#fff;
                width:44px;
                height:44px;
                border-radius:2px;
                margin:15px 0;
            }
            .iq_search_btn:hover,
            .iq_search_btn:focus{ background:#e6960c; color:#fff; }

            /* Lower the collapse breakpoint from 768px to 992px so 11 links
               never wrap and overlap the banner on tablets/small desktops */
            @media (max-width: 991px){
                .iq_main_navbar .navbar-header{ float:none; }
                .iq_main_navbar .navbar-toggle{ display:block; }
                .iq_main_navbar .navbar-collapse.collapse{ display:none!important; }
                .iq_main_navbar .navbar-collapse.collapse.in{ display:block!important; }
                .iq_main_navbar .navbar-nav{ float:none!important; margin:0; }
                .iq_main_navbar .navbar-nav > li{ float:none; }
                .iq_main_navbar .navbar-nav > li > a{ padding:12px 15px; }
                .iq_search_btn{ margin:10px 15px 15px; }
                .iq_main_navbar .dropdown-menu{ position:static!important; float:none; box-shadow:none; border:none; padding-left:15px; }
            }

            /* ---- Registration dropdown ---- */
            .iq_main_navbar{ position:relative; z-index:1000; } /* sit above the banner/carousel below it */
            .iq_main_navbar .dropdown-menu{
                border:none;
                border-radius:0;
                box-shadow:0 4px 12px rgba(0,0,0,.15);
                padding:6px 0;
                margin-top:0;
                min-width:180px;
                z-index:1050; /* stays above the slick carousel and its arrows */
            }
            .iq_main_navbar .dropdown-menu > li > a{
                padding:9px 18px;
                font-size:14px;
                color:#333;
            }
            .iq_main_navbar .dropdown-menu > li > a i{
                width:18px;
                color:#f7a416;
                margin-right:6px;
            }
            .iq_main_navbar .dropdown-menu > li > a:hover,
            .iq_main_navbar .dropdown-menu > li > a:focus{
                background:#fdf1dc;
                color:#f7a416;
            }
            .iq_main_navbar .dropdown-toggle .caret{ margin-left:3px; }
            /* Optional: open on hover for desktop, tap for mobile (Bootstrap default) */
            @media (min-width: 992px){
                .iq_main_navbar .dropdown:hover > .dropdown-menu{ display:block; }
            }

            /* ---- Banner responsiveness ---- */
            .iq_banner{ position:relative; overflow:hidden; }
            .iq_banner .slider{ margin:0; padding:0; list-style:none; }
            .iq_banner ul.slider > li{ list-style:none; }
            .iq_banner img{ width:100%; height:auto; display:block; }
            .iq-caption-title{ font-size:38px; line-height:1.2; font-weight:700; }
            .iq-caption-text{ font-size:20px; margin:8px 0; }
            .iq-caption-contant{ font-size:15px; max-width:500px; }

            @media (max-width: 767px){
                .iq-caption-title{ font-size:24px; }
                .iq-caption-text{ font-size:15px; }
                .iq-caption-contant{ display:none; } /* keep small screens uncluttered */
                .iq_banner .iq_link_1{ padding:8px 18px; font-size:13px; }
            }
        </style>
</head>

<body>
    <!--iqoniq Wrapper Start-->
    <div class="iq_wrapper">

        <!--Top Strip Wrap Start-->
        <div class="iq_top_strip_new hidden-xs">
            <div class="container">
                <div class="row">
                    <div class="col-sm-8">
                        <a href="javascript:void(0);" class="iq_copy_trigger" data-copy="+91 94262 33488">
                            <i class="fa fa-phone"></i> Call us : <span class="iq_copy_value">+91 94262 33488</span>
                        </a>
                        <a href="javascript:void(0);" class="iq_copy_trigger" data-copy="foundation.pratibha@gmail.com">
                            <i class="fa fa-envelope"></i> Email : <span class="iq_copy_value">foundation.pratibha@gmail.com</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!--Top Strip Wrap End-->

        <script>
            document.addEventListener('click', function (e) {
                var trigger = e.target.closest ? e.target.closest('.iq_copy_trigger') : null;
                if (!trigger) return;

                var value = trigger.getAttribute('data-copy');
                if (!value) return;

                var doCopyFeedback = function () {
                    trigger.classList.add('iq_copied');
                    var valueEl = trigger.querySelector('.iq_copy_value');
                    var original = valueEl.textContent;
                    valueEl.textContent = 'Copied!';
                    setTimeout(function () {
                        valueEl.textContent = original;
                        trigger.classList.remove('iq_copied');
                    }, 1500);
                };

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(value).then(doCopyFeedback);
                } else {
                    /* Fallback for older browsers */
                    var tempInput = document.createElement('input');
                    tempInput.value = value;
                    document.body.appendChild(tempInput);
                    tempInput.select();
                    document.execCommand('copy');
                    document.body.removeChild(tempInput);
                    doCopyFeedback();
                }
            });
        </script>

        <!--Header / Navbar Wrap Start-->
        <nav class="navbar navbar-default iq_main_navbar" role="navigation">
            <div class="container">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#iq-main-nav">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="index.php"><img src="images/logo.jpg" alt="Logo"></a>
                </div>

                <div class="collapse navbar-collapse" id="iq-main-nav">
                    <ul class="nav navbar-nav">
                        <li class="active"><a href="index.php">Home</a></li>
                        <li><a href="about-us.php">About Us</a></li>
                        <li><a href="events.php">Upcoming Events</a></li>
                        <li><a href="news.php">News</a></li>
                        <li><a href="projects.php">Projects</a></li>
                        <li><a href="gallery.php">Gallery</a></li>
                        <li><a href="csr.php">CSR</a></li>
                        <li><a href="contact-us.php">Contact Us</a></li>
                    </ul>
                </div>
            </div>
        </nav>
        <!--Header / Navbar Wrap End-->