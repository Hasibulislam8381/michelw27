 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
 <meta http-equiv="X-UA-Compatible" content="IE=edge">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <meta name="description"
     content="Riho admin is super flexible, powerful, clean &amp; modern responsive bootstrap 5 admin template with unlimited possibilities.">
 <meta name="keywords"
     content="admin template, Riho admin template, dashboard template, flat admin template, responsive admin template, web app">
 <meta name="author" content="pixelstrap">
 <link rel="icon" href="../assets/images/favicon.png" type="image/x-icon">
 <link rel="shortcut icon" href="../assets/images/favicon.png" type="image/x-icon">
 <title>Michel</title>
 <!-- Google font-->
 <link rel="preconnect" href="https://fonts.googleapis.com">
 <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
 <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@200;300;400;500;600;700;800&amp;display=swap"
     rel="stylesheet">
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css"
     integrity="sha512-5A8nwdMOWrSz20fDsjczgUidUBR8liPYU+WymTZP1lmY9G6Oc7HlZv156XqnsgNUzTyMefFTcsFH/tnJE/+xBg=="
     crossorigin="anonymous" referrerpolicy="no-referrer" />
 <!-- ico-font-->
 <link rel="stylesheet" type="text/css" href="{{ asset('backend/css/icofont.css') }}">
 <!-- Themify icon-->
 <link rel="stylesheet" type="text/css" href="{{ asset('backend/css/themify.css') }}">
 <!-- Flag icon-->
 <link rel="stylesheet" type="text/css" href="{{ asset('backend/css/flag-icon.css') }}">
 <!-- Feather icon-->
 <link rel="stylesheet" type="text/css" href="{{ asset('backend/css/feather-icon.css') }}">
 <!-- Plugins css start-->
 <link rel="stylesheet" href="{{ asset('backend/css/dropify.min.css') }}" />
 <link rel="stylesheet" type="text/css" href="{{ asset('backend/css/slick.css') }}">
 <link rel="stylesheet" type="text/css" href="{{ asset('backend/css/slick-theme.css') }}">
 <link rel="stylesheet" type="text/css" href="{{ asset('backend/css/scrollbar.css') }}">
 <link rel="stylesheet" type="text/css" href="{{ asset('backend/css/animate.css') }}">
 <link rel="stylesheet" type="text/css" href="{{ asset('backend/css/echart.css') }}">
 <link rel="stylesheet" type="text/css" href="{{ asset('backend/css/date-picker.css') }}">
 <!-- Plugins css Ends-->
 <!-- Bootstrap css-->
 <link rel="stylesheet" type="text/css" href="{{ asset('backend/css/bootstrap.css') }}">
 <!-- App css-->
 <link rel="stylesheet" type="text/css" href="{{ asset('backend/css/style.css') }}">
 <link id="color" rel="stylesheet" href="{{ asset('backend/css/color-1.css') }}" media="screen">
 <!-- Responsive css-->
 <link rel="stylesheet" type="text/css" href="{{ asset('backend/css/responsive.css') }}">

 <link rel="stylesheet" href="{{ asset('backend/css/datatables.min.css') }}" />

 <script src="{{ asset('backend/js/sweetalert2@11.js') }}"></script>

 {{-- dropify and ck-editor start --}}
 <style>
     .ck-editor__editable[role="textbox"] {
         min-height: 150px;
     }

     .dropify-wrapper .dropify-render {
         display: unset !important;
     }

     .admin_bg {
         background: #fffefe;
         padding: 10px 14px;
     }

     .form-check-input.status-toggle {
         width: 45px;
         height: 22px;
         cursor: pointer;
         transition: all 0.3s ease-in-out;
     }

     .page-link.active,
     .active>.page-link {
         background-color: #006666
     }

     .form-check-input.status-toggle:focus {
         box-shadow: none;
     }
 </style>
 {{-- dropify and ck-editor end --}}
