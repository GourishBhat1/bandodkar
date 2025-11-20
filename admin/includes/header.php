<!DOCTYPE html>
<html lang="en-US" dir="ltr">

  <head>
    <meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- ===============================================-->
<!--    Document Title-->
<!-- ===============================================-->
<title>Bandodkar Clinic | Patient Management System</title>

<!-- Meta description & keywords (optional but recommended) -->
<meta name="description" content="Bandodkar Clinic Patient Management System – Manage patients, prescriptions, and medical records efficiently.">
<meta name="keywords" content="Bandodkar Clinic, patient management, prescriptions, medical records, healthcare software">
<meta name="author" content="Bandodkar Clinic">
<!-- ===============================================-->
<!--    PWA Setup -->
<!-- ===============================================-->

<link rel="manifest" href="/admin/manifest.json">
<meta name="theme-color" content="#0066cc">

<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('service-worker.js')
    .then(() => console.log("Service Worker registered"));
}
</script>

<!-- ===============================================-->
<!--    Favicons -->
<!-- ===============================================-->

<!-- Standard browser icon -->
<link rel="icon" type="image/png" sizes="192x192" href="assets/img/favicons/icon-192.png">

<!-- Apple devices -->
<link rel="apple-touch-icon" href="assets/img/favicons/icon-192.png">

<!-- ===============================================-->
<!--    Vendor Scripts -->
<!-- ===============================================-->

<script src="assets/js/config.js"></script>
<script src="vendors/overlayscrollbars/OverlayScrollbars.min.js"></script>


    <!-- ===============================================-->
    <!--    Stylesheets-->
    <!-- ===============================================-->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700%7cPoppins:300,400,500,600,700,800,900&amp;display=swap" rel="stylesheet">
    <link href="vendors/overlayscrollbars/OverlayScrollbars.min.css" rel="stylesheet">
    <link href="assets/css/theme-rtl.min.css" rel="stylesheet" id="style-rtl">
    <link href="assets/css/theme.min.css" rel="stylesheet" id="style-default">
    <link href="assets/css/user-rtl.min.css" rel="stylesheet" id="user-style-rtl">
    <link href="assets/css/user.min.css" rel="stylesheet" id="user-style-default">
    <script>
      var isRTL = JSON.parse(localStorage.getItem('isRTL'));
      if (isRTL) {
        var linkDefault = document.getElementById('style-default');
        var userLinkDefault = document.getElementById('user-style-default');
        linkDefault.setAttribute('disabled', true);
        userLinkDefault.setAttribute('disabled', true);
        document.querySelector('html').setAttribute('dir', 'rtl');
      } else {
        var linkRTL = document.getElementById('style-rtl');
        var userLinkRTL = document.getElementById('user-style-rtl');
        linkRTL.setAttribute('disabled', true);
        userLinkRTL.setAttribute('disabled', true);
      }
    </script>

    <style>
/* Hide table layout on mobile */
@media (max-width: 768px) {
    table.dataTable thead {
        display: none;
    }
    table.dataTable tbody tr {
        display: block;
        margin-bottom: 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 12px;
        background: #fff;
    }
    table.dataTable tbody td {
        display: flex;
        justify-content: space-between;
        padding: 6px 10px;
        border: none !important;
    }
    table.dataTable tbody td:before {
        content: attr(data-label);
        font-weight: 600;
        color: #333;
    }
}
</style>
  </head>


  <body>