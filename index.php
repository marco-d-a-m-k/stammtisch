<?php
status_header(404);
nocache_headers();
header("X-Robots-Tag: noindex, nofollow", true);
?>
<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="refresh" content="1;url=<?php echo home_url(); ?>">
    <script>
        setTimeout(function() {
            window.location.href = "<?php echo home_url(); ?>";
        }, 1000);
    </script>
</head>

<body>
    <h1>Page Not Found</h1>
    <p>This page doesn't exist. Redirecting to the homepage...</p>
</body>

</html>