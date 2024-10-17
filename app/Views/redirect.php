<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arrange Matrimony</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body,
        html {
            height: 100%;
            background-color: #f8f9fa;
            /* Light grey background */
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }

        .mobile-box {
            width: 300px;
            height: 250px;
            /* Set max width of the mobile box */
            border-radius: 30px;
            /* Rounded corners */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            background-color: #fff;
            padding: 20px;
            text-align: center;
            position: relative;
        }

        .mobile-box .gif {
            width: 100%;
            height: auto;
        }

        .mobile-box .success {
            font-size: 1.5rem;
            color: lightgreen;
            margin-bottom: 20px;
        }

        .mobile-box .fail {
            font-size: 1.5rem;
            color: red;
            margin-bottom: 20px;
        }

        .pending {
            font-size: 1.5rem;
            color: orangered;
            margin-bottom: 20px;
        }

        .p_gif {
            width: 120px !important;
            height: 120px !important;
        }
    </style>
</head>

<body>
    <div class="mobile-box">
        <?php if (isset($status) && $status == 'COMPLETED') { ?>
            <h1 class="success">Success!</h1>
            <img src="<?= base_url() ?>assets/p_success.gif" alt="Success" class="gif">
        <?php } else if (isset($status) && $status == 'FAILED') { ?>
            <h1 class="fail">Fail!</h1>
            <img src="<?= base_url() ?>assets/p_fail.gif" alt="fail" class="gif">
        <?php } else if (isset($status) && $status == 'PENDING') { ?>
            <h1 class="pending">Pending!</h1>
            <img src="<?= base_url() ?>assets/pending.gif" alt="pending" class="p_gif">
        <?php } else { ?>
            <h1 class="fail">Opps! Something went wrong</h1>
            <img src="<?= base_url() ?>assets/p_fail.gif" alt="fail" class="gif">
        <?php } ?>
    </div>
    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>