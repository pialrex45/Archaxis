<?php
$pageTitle = 'Page Not Found';
$currentPage = '404';
?>
<?php include_once __DIR__ . '/layouts/header.php'; ?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12 text-center">
            <h1 class="display-1">404</h1>
            <h2>Page Not Found</h2>
            <p class="lead">The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.</p>
            <a href="<?php echo url('/'); ?>" class="btn btn-primary">Go Home</a>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/layouts/footer.php'; ?>