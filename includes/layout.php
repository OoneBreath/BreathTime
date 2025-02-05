<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'BreathTime'; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: transparent;
            color: white;
            font-family: Arial, sans-serif;
        }

        .page-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        .content-box {
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 200, 0, 0.1);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .content-box h1, .content-box h2 {
            color: white;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: rgba(255, 200, 0, 0.5);
            box-shadow: 0 0 10px rgba(255, 200, 0, 0.2);
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(45deg, rgba(255, 200, 0, 0.8), rgba(255, 180, 0, 0.8));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 20px rgba(255, 200, 0, 0.3);
        }

        .btn-block {
            display: block;
            width: 100%;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: #98ff98;
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #ffb3b3;
        }

        .form-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .form-footer a {
            color: rgba(255, 200, 0, 0.8);
            text-decoration: none;
        }

        .form-footer a:hover {
            color: rgba(255, 200, 0, 1);
        }

        @media (max-width: 768px) {
            .page-container {
                padding: 10px;
                margin: 20px auto;
            }

            .content-box {
                padding: 1rem;
            }
        }
    </style>
    <?php if (isset($additional_styles)) echo $additional_styles; ?>
</head>
<body>
    <?php require_once 'header.php'; ?>
    
    <div class="page-container">
        <?php echo $content; ?>
    </div>

    <?php if (isset($additional_scripts)) echo $additional_scripts; ?>
</body>
</html>
