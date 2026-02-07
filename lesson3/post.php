<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forms</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</head>

<body class="container mt-5">
    <h1>Forms</h1>
    <p>Here is the result of the form on the previous page.</p>
    <p>
    <?php
    if (isset($_POST['name']) && isset($_POST['email'])) {
        echo "<b>Name:</b> " . $_POST['name'] . "<br>";
        echo "<b>Email:</b> " . $_POST['email'] . "<br>";
    } else {
        echo "Please fill in the form.";
    }
    ?>
    </p>
    <p>This just demonstrates how to use the POST method to submit form data. The form data is sent to the server and can be accessed using the $_POST superglobal array.</p>
    <p><b>We will learn more about this in the PHP unit.</b></p>

    <a onclick="history.back()" class="btn btn-success">Go Back</a>
</body>

</html>