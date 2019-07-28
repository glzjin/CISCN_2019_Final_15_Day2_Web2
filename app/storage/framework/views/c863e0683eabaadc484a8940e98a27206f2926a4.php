<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <title>File Manager</title>
        <link rel="stylesheet" href="/css/all.css">
        <link rel="stylesheet" href="/css/app.css">
<!--        <script src="<?php echo e(asset('vendor/file-manager/js/file-manager.js')); ?>"></script>-->
    </head>
    <body>
    <?php if(Request::ip() === '127.0.0.1'): ?>
        <div class="container">
            <div style="height: 800px;">
                <div id="fm"></div>
            </div>
        </div>
        <script src="/js/app.js"></script>

    <?php else: ?>
        Only localhost can access it!
    <?php endif; ?>
    </body>
</html>
<?php /**PATH /Users/sx/projects/ciscn/app/resources/views/welcome.blade.php ENDPATH**/ ?>