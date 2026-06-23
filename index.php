<?php
// Thomas Shaw
// Car detailing website


// Load .env file
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    die("<p>Missing .env file. See README.md for setup instructions.</p>");
}
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
    [$key, $value] = explode('=', $line, 2);
    $_ENV[trim($key)] = trim($value);
}

// DB Credentials from .env
$host   = $_ENV['DB_HOST'] ?? null;
$dbname = $_ENV['DB_NAME'] ?? null;
$username = $_ENV['DB_USER'] ?? null;
$password = $_ENV['DB_PASS'] ?? null;

if (!$host || !$dbname || !$username || !$password) {
    die("<p>One or more required environment variables are missing from .env. See README.md.</p>");
}

$pdo = null;
$message = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<p>Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>");
}

$page = isset($_GET['page']) ? $_GET['page'] : 'insert';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // INSERT HANDLERS

    if ($action === 'insert_customer') {
        try {
            $stmt = $pdo->prepare("INSERT INTO customer (FirstName, LastName, Email, PasswordHash, PhoneNumber, IsActive)
                                   VALUES (?, ?, ?, MD5(?), ?, 1)");
            $stmt->execute([
                $_POST['FirstName'], $_POST['LastName'],
                $_POST['Email'], $_POST['Password'],
                $_POST['PhoneNumber'] ?: null
            ]);
            $id = $pdo->lastInsertId();
            $row = $pdo->query("SELECT * FROM customer WHERE CustomerID = $id")->fetch(PDO::FETCH_ASSOC);
            $message = ["status" => "success", "text" => "Customer inserted successfully.", "record" => $row];
        } catch (PDOException $e) {
            $message = ["status" => "error", "text" => $e->getMessage()];
        }
    }

    elseif ($action === 'insert_employee') {
        try {
            $stmt = $pdo->prepare("INSERT INTO employee (FirstName, LastName, Email, PasswordHash, Role, IsActive)
                                   VALUES (?, ?, ?, MD5(?), ?, 1)");
            $stmt->execute([
                $_POST['FirstName'], $_POST['LastName'],
                $_POST['Email'], $_POST['Password'], $_POST['Role']
            ]);
            $id = $pdo->lastInsertId();
            $row = $pdo->query("SELECT * FROM employee WHERE EmployeeID = $id")->fetch(PDO::FETCH_ASSOC);
            $message = ["status" => "success", "text" => "Employee inserted successfully.", "record" => $row];
        } catch (PDOException $e) {
            $message = ["status" => "error", "text" => $e->getMessage()];
        }
    }

    elseif ($action === 'insert_product') {
        try {
            $stmt = $pdo->prepare("INSERT INTO product (ProductName, Description, Price, StockQuantity, Category, IsAvailable)
                                   VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([
                $_POST['ProductName'], $_POST['Description'],
                $_POST['Price'], $_POST['StockQuantity'], $_POST['Category']
            ]);
            $id = $pdo->lastInsertId();
            $row = $pdo->query("SELECT * FROM product WHERE ProductID = $id")->fetch(PDO::FETCH_ASSOC);
            $message = ["status" => "success", "text" => "Product inserted successfully.", "record" => $row];
        } catch (PDOException $e) {
            $message = ["status" => "error", "text" => $e->getMessage()];
        }
    }

    elseif ($action === 'insert_service') {
        try {
            $stmt = $pdo->prepare("INSERT INTO service (ServiceName, Description, Price, DurationMinutes, IsAvailable)
                                   VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([
                $_POST['ServiceName'], $_POST['Description'],
                $_POST['Price'], $_POST['DurationMinutes']
            ]);
            $id = $pdo->lastInsertId();
            $row = $pdo->query("SELECT * FROM service WHERE ServiceID = $id")->fetch(PDO::FETCH_ASSOC);
            $message = ["status" => "success", "text" => "Service inserted successfully.", "record" => $row];
        } catch (PDOException $e) {
            $message = ["status" => "error", "text" => $e->getMessage()];
        }
    }

    elseif ($action === 'insert_promo') {
        try {
            $stmt = $pdo->prepare("INSERT INTO promo_code (Code, DiscountType, DiscountValue, ExpirationDate, IsActive)
                                   VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([
                $_POST['Code'], $_POST['DiscountType'],
                $_POST['DiscountValue'], $_POST['ExpirationDate']
            ]);
            $id = $pdo->lastInsertId();
            $row = $pdo->query("SELECT * FROM promo_code WHERE PromoCodeID = $id")->fetch(PDO::FETCH_ASSOC);
            $message = ["status" => "success", "text" => "Promo code inserted successfully.", "record" => $row];
        } catch (PDOException $e) {
            $message = ["status" => "error", "text" => $e->getMessage()];
        }
    }

    elseif ($action === 'insert_vehicle') {
        try {
            $stmt = $pdo->prepare("INSERT INTO vehicle (CustomerID, Make, Model, Year, Color, LicensePlate)
                                   VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['CustomerID'], $_POST['Make'], $_POST['Model'],
                $_POST['Year'], $_POST['Color'],
                $_POST['LicensePlate'] ?: null
            ]);
            $id = $pdo->lastInsertId();
            $row = $pdo->query("SELECT * FROM vehicle WHERE VehicleID = $id")->fetch(PDO::FETCH_ASSOC);
            $message = ["status" => "success", "text" => "Vehicle inserted successfully.", "record" => $row];
        } catch (PDOException $e) {
            $message = ["status" => "error", "text" => $e->getMessage()];
        }
    }

    // UPDATE HANDLERS
    // Only fields that are not blank will be included in the SET clause.

    elseif ($action === 'update_customer') {
        try {
            $id = (int)$_POST['CustomerID'];
            $fields = [];
            $params = [];
            $map = [
                'FirstName'   => 'FirstName',
                'LastName'    => 'LastName',
                'Email'       => 'Email',
                'PhoneNumber' => 'PhoneNumber',
            ];
            foreach ($map as $post => $col) {
                $val = trim($_POST[$post] ?? '');
                if ($val !== '') {
                    $fields[] = "$col = ?";
                    $params[] = $val;
                }
            }
            if (empty($fields)) {
                $message = ["status" => "error", "text" => "No fields provided to update."];
            } else {
                $params[] = $id;
                $pdo->prepare("UPDATE customer SET " . implode(', ', $fields) . " WHERE CustomerID = ?")->execute($params);
                $row = $pdo->query("SELECT * FROM customer WHERE CustomerID = $id")->fetch(PDO::FETCH_ASSOC);
                $message = ["status" => "success", "text" => "Customer updated successfully.", "record" => $row];
            }
        } catch (PDOException $e) {
            $message = ["status" => "error", "text" => $e->getMessage()];
        }
    }

    elseif ($action === 'update_employee') {
        try {
            $id = (int)$_POST['EmployeeID'];
            $fields = [];
            $params = [];
            $map = [
                'FirstName' => 'FirstName',
                'LastName'  => 'LastName',
                'Email'     => 'Email',
            ];
            foreach ($map as $post => $col) {
                $val = trim($_POST[$post] ?? '');
                if ($val !== '') {
                    $fields[] = "$col = ?";
                    $params[] = $val;
                }
            }
            if (!empty($_POST['Role'])) {
                $fields[] = "Role = ?";
                $params[] = $_POST['Role'];
            }
            if (empty($fields)) {
                $message = ["status" => "error", "text" => "No fields provided to update."];
            } else {
                $params[] = $id;
                $pdo->prepare("UPDATE employee SET " . implode(', ', $fields) . " WHERE EmployeeID = ?")->execute($params);
                $row = $pdo->query("SELECT * FROM employee WHERE EmployeeID = $id")->fetch(PDO::FETCH_ASSOC);
                $message = ["status" => "success", "text" => "Employee updated successfully.", "record" => $row];
            }
        } catch (PDOException $e) {
            $message = ["status" => "error", "text" => $e->getMessage()];
        }
    }

    elseif ($action === 'update_product') {
        try {
            $id = (int)$_POST['ProductID'];
            $fields = [];
            $params = [];
            $map = [
                'ProductName'   => 'ProductName',
                'Price'         => 'Price',
                'StockQuantity' => 'StockQuantity',
                'Category'      => 'Category',
            ];
            foreach ($map as $post => $col) {
                $val = trim($_POST[$post] ?? '');
                if ($val !== '') {
                    $fields[] = "$col = ?";
                    $params[] = $val;
                }
            }
            if (isset($_POST['IsAvailable']) && $_POST['IsAvailable'] !== '') {
                $fields[] = "IsAvailable = ?";
                $params[] = $_POST['IsAvailable'];
            }
            if (empty($fields)) {
                $message = ["status" => "error", "text" => "No fields provided to update."];
            } else {
                $params[] = $id;
                $pdo->prepare("UPDATE product SET " . implode(', ', $fields) . " WHERE ProductID = ?")->execute($params);
                $row = $pdo->query("SELECT * FROM product WHERE ProductID = $id")->fetch(PDO::FETCH_ASSOC);
                $message = ["status" => "success", "text" => "Product updated successfully.", "record" => $row];
            }
        } catch (PDOException $e) {
            $message = ["status" => "error", "text" => $e->getMessage()];
        }
    }

    elseif ($action === 'update_service') {
        try {
            $id = (int)$_POST['ServiceID'];
            $fields = [];
            $params = [];
            $map = [
                'ServiceName'     => 'ServiceName',
                'Price'           => 'Price',
                'DurationMinutes' => 'DurationMinutes',
            ];
            foreach ($map as $post => $col) {
                $val = trim($_POST[$post] ?? '');
                if ($val !== '') {
                    $fields[] = "$col = ?";
                    $params[] = $val;
                }
            }
            if (isset($_POST['IsAvailable']) && $_POST['IsAvailable'] !== '') {
                $fields[] = "IsAvailable = ?";
                $params[] = $_POST['IsAvailable'];
            }
            if (empty($fields)) {
                $message = ["status" => "error", "text" => "No fields provided to update."];
            } else {
                $params[] = $id;
                $pdo->prepare("UPDATE service SET " . implode(', ', $fields) . " WHERE ServiceID = ?")->execute($params);
                $row = $pdo->query("SELECT * FROM service WHERE ServiceID = $id")->fetch(PDO::FETCH_ASSOC);
                $message = ["status" => "success", "text" => "Service updated successfully.", "record" => $row];
            }
        } catch (PDOException $e) {
            $message = ["status" => "error", "text" => $e->getMessage()];
        }
    }

    elseif ($action === 'update_appointment') {
        try {
            $id = (int)$_POST['AppointmentID'];
            $fields = [];
            $params = [];
            if (!empty($_POST['Status'])) {
                $fields[] = "Status = ?";
                $params[] = $_POST['Status'];
            }
            if (!empty($_POST['EmployeeID'])) {
                $fields[] = "EmployeeID = ?";
                $params[] = (int)$_POST['EmployeeID'];
            }
            $notes = trim($_POST['Notes'] ?? '');
            if ($notes !== '') {
                $fields[] = "Notes = ?";
                $params[] = $notes;
            }
            if (empty($fields)) {
                $message = ["status" => "error", "text" => "No fields provided to update."];
            } else {
                $params[] = $id;
                $pdo->prepare("UPDATE appointment SET " . implode(', ', $fields) . " WHERE AppointmentID = ?")->execute($params);
                $row = $pdo->query("SELECT * FROM appointment WHERE AppointmentID = $id")->fetch(PDO::FETCH_ASSOC);
                $message = ["status" => "success", "text" => "Appointment updated successfully.", "record" => $row];
            }
        } catch (PDOException $e) {
            $message = ["status" => "error", "text" => $e->getMessage()];
        }
    }

    // DELETE HANDLERS

    elseif ($action === 'delete_customer') {
        try {
            $row = $pdo->query("SELECT CONCAT(FirstName, ' ', LastName) AS Name FROM customer WHERE CustomerID = " . (int)$_POST['CustomerID'])->fetch(PDO::FETCH_ASSOC);
            $stmt = $pdo->prepare("DELETE FROM customer WHERE CustomerID=?");
            $stmt->execute([$_POST['CustomerID']]);
            $message = ["status" => "success", "text" => "Deleted customer: " . $row['Name']];
        } catch (PDOException $e) {
            $message = ["status" => "error", "text" => $e->getMessage()];
        }
    }

    elseif ($action === 'delete_employee') {
        try {
            $row = $pdo->query("SELECT CONCAT(FirstName, ' ', LastName) AS Name FROM employee WHERE EmployeeID = " . (int)$_POST['EmployeeID'])->fetch(PDO::FETCH_ASSOC);
            $stmt = $pdo->prepare("DELETE FROM employee WHERE EmployeeID=?");
            $stmt->execute([$_POST['EmployeeID']]);
            $message = ["status" => "success", "text" => "Deleted employee: " . $row['Name']];
        } catch (PDOException $e) {
            $message = ["status" => "error", "text" => $e->getMessage()];
        }
    }

    elseif ($action === 'delete_product') {
        try {
            $row = $pdo->query("SELECT ProductName AS Name FROM product WHERE ProductID = " . (int)$_POST['ProductID'])->fetch(PDO::FETCH_ASSOC);
            $stmt = $pdo->prepare("DELETE FROM product WHERE ProductID=?");
            $stmt->execute([$_POST['ProductID']]);
            $message = ["status" => "success", "text" => "Deleted product: " . $row['Name']];
        } catch (PDOException $e) {
            $message = ["status" => "error", "text" => $e->getMessage()];
        }
    }

    elseif ($action === 'delete_service') {
        try {
            $row = $pdo->query("SELECT ServiceName AS Name FROM service WHERE ServiceID = " . (int)$_POST['ServiceID'])->fetch(PDO::FETCH_ASSOC);
            $stmt = $pdo->prepare("DELETE FROM service WHERE ServiceID=?");
            $stmt->execute([$_POST['ServiceID']]);
            $message = ["status" => "success", "text" => "Deleted service: " . $row['Name']];
        } catch (PDOException $e) {
            $message = ["status" => "error", "text" => $e->getMessage()];
        }
    }

    elseif ($action === 'delete_vehicle') {
        try {
            $row = $pdo->query("SELECT CONCAT(Year, ' ', Make, ' ', Model) AS Name FROM vehicle WHERE VehicleID = " . (int)$_POST['VehicleID'])->fetch(PDO::FETCH_ASSOC);
            $stmt = $pdo->prepare("DELETE FROM vehicle WHERE VehicleID=?");
            $stmt->execute([$_POST['VehicleID']]);
            $message = ["status" => "success", "text" => "Deleted vehicle: " . $row['Name']];
        } catch (PDOException $e) {
            $message = ["status" => "error", "text" => $e->getMessage()];
        }
    }

    elseif ($action === 'delete_promo') {
        try {
            $row = $pdo->query("SELECT Code AS Name FROM promo_code WHERE PromoCodeID = " . (int)$_POST['PromoCodeID'])->fetch(PDO::FETCH_ASSOC);
            $stmt = $pdo->prepare("DELETE FROM promo_code WHERE PromoCodeID=?");
            $stmt->execute([$_POST['PromoCodeID']]);
            $message = ["status" => "success", "text" => "Deleted promo code: " . $row['Name']];
        } catch (PDOException $e) {
            $message = ["status" => "error", "text" => $e->getMessage()];
        }
    }
}

// HELPER: render message box
function renderMessage($message) {
    if (empty($message)) return;
    $color = $message['status'] === 'success' ? 'green' : 'red';
    echo "<p style='color:$color'><strong>Message:</strong> " . htmlspecialchars($message['text']) . "</p>";
    if (!empty($message['record'])) {
        echo "<table border='1' cellpadding='4'><tr>";
        foreach (array_keys($message['record']) as $col) {
            echo "<th>" . htmlspecialchars($col) . "</th>";
        }
        echo "</tr><tr>";
        foreach ($message['record'] as $val) {
            echo "<td>" . htmlspecialchars($val ?? 'NULL') . "</td>";
        }
        echo "</tr></table>";
    }
}

// HELPER: render a simple results table from a query
function renderTable($rows) {
    if (empty($rows)) { echo "<p>No records found.</p>"; return; }
    echo "<table border='1' cellpadding='4'><tr>";
    foreach (array_keys($rows[0]) as $col) echo "<th>" . htmlspecialchars($col) . "</th>";
    echo "</tr>";
    foreach ($rows as $row) {
        echo "<tr>";
        foreach ($row as $val) echo "<td>" . htmlspecialchars($val ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// LOAD DROPDOWN DATA
$customers    = $pdo->query("SELECT CustomerID, CONCAT(FirstName,' ',LastName) AS Name FROM customer ORDER BY LastName")->fetchAll(PDO::FETCH_ASSOC);
$employees    = $pdo->query("SELECT EmployeeID, CONCAT(FirstName,' ',LastName) AS Name FROM employee ORDER BY LastName")->fetchAll(PDO::FETCH_ASSOC);
$vehicles     = $pdo->query("SELECT VehicleID, CONCAT(Year,' ',Make,' ',Model) AS Name FROM vehicle ORDER BY Make")->fetchAll(PDO::FETCH_ASSOC);
$services     = $pdo->query("SELECT ServiceID, ServiceName AS Name FROM service ORDER BY ServiceName")->fetchAll(PDO::FETCH_ASSOC);
$products     = $pdo->query("SELECT ProductID, ProductName AS Name FROM product ORDER BY ProductName")->fetchAll(PDO::FETCH_ASSOC);
$promos       = $pdo->query("SELECT PromoCodeID, Code AS Name FROM promo_code ORDER BY Code")->fetchAll(PDO::FETCH_ASSOC);
$appointments = $pdo->query("SELECT AppointmentID, CONCAT('Appt #',AppointmentID,' : ',AppointmentDate) AS Name FROM appointment ORDER BY AppointmentDate DESC")->fetchAll(PDO::FETCH_ASSOC);

function dropdown($name, $options, $labelKey = 'Name', $valueKey = null) {
    $vk = $valueKey ?? array_key_first($options[0] ?? [null => null]);
    echo "<select name='$name'>";
    foreach ($options as $opt) {
        $val = $opt[$vk ?? array_key_first($opt)];
        echo "<option value='" . htmlspecialchars($val) . "'>" . htmlspecialchars($opt[$labelKey]) . "</option>";
    }
    echo "</select>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Car Detailing DB Demo</title>
</head>
<body>

<h1>Car Detailing Project : Operations Demo</h1>

<!-- NAVBAR -->
<nav>
    <a href="?page=insert"><button>Insert</button></a>
    <a href="?page=update"><button>Update</button></a>
    <a href="?page=delete"><button>Delete</button></a>
    <a href="?page=select"><button>Select</button></a>
    <a href="?page=views"><button>Views</button></a>
    <a href="?page=functions"><button>Functions</button></a>
    <a href="?page=procedures"><button>Stored Procedures</button></a>
    <a href="?page=triggers"><button>Triggers</button></a>
</nav>

<hr>

<?php renderMessage($message); ?>

<!-- INSERT PAGE -->
<?php if ($page === 'insert'): ?>

<h2>Insert : Customer</h2>
<form method="POST" action="?page=insert">
    <input type="hidden" name="action" value="insert_customer">
    First Name: <input type="text" name="FirstName" required><br>
    Last Name:  <input type="text" name="LastName"  required><br>
    Email:      <input type="email" name="Email"    required><br>
    Password:   <input type="text"  name="Password" required><br>
    Phone:      <input type="text"  name="PhoneNumber"><br>
    <button type="submit">Insert Customer</button>
</form>

<hr>

<h2>Insert : Employee</h2>
<form method="POST" action="?page=insert">
    <input type="hidden" name="action" value="insert_employee">
    First Name: <input type="text"  name="FirstName" required><br>
    Last Name:  <input type="text"  name="LastName"  required><br>
    Email:      <input type="email" name="Email"     required><br>
    Password:   <input type="text"  name="Password"  required><br>
    Role:
    <select name="Role">
        <option value="Staff">Staff</option>
        <option value="Administrator">Administrator</option>
    </select><br>
    <button type="submit">Insert Employee</button>
</form>

<hr>

<h2>Insert : Product</h2>
<form method="POST" action="?page=insert">
    <input type="hidden" name="action" value="insert_product">
    Product Name:   <input type="text"   name="ProductName"   required><br>
    Description:    <input type="text"   name="Description"><br>
    Price:          <input type="number" name="Price" step="0.01" required><br>
    Stock Quantity: <input type="number" name="StockQuantity"  required><br>
    Category:       <input type="text"   name="Category"><br>
    <button type="submit">Insert Product</button>
</form>

<hr>

<h2>Insert : Service</h2>
<form method="POST" action="?page=insert">
    <input type="hidden" name="action" value="insert_service">
    Service Name:     <input type="text"   name="ServiceName"     required><br>
    Description:      <input type="text"   name="Description"><br>
    Price:            <input type="number" name="Price" step="0.01" required><br>
    Duration (mins):  <input type="number" name="DurationMinutes"  required><br>
    <button type="submit">Insert Service</button>
</form>

<hr>

<h2>Insert : Promo Code</h2>
<form method="POST" action="?page=insert">
    <input type="hidden" name="action" value="insert_promo">
    Code:           <input type="text"   name="Code"          required><br>
    Discount Type:
    <select name="DiscountType">
        <option value="Percentage">Percentage</option>
        <option value="Fixed Amount">Fixed Amount</option>
    </select><br>
    Discount Value: <input type="number" name="DiscountValue" step="0.01" required><br>
    Expiration Date:<input type="date"   name="ExpirationDate" required><br>
    <button type="submit">Insert Promo Code</button>
</form>

<hr>

<h2>Insert : Vehicle</h2>
<form method="POST" action="?page=insert">
    <input type="hidden" name="action" value="insert_vehicle">
    Customer:
    <?php dropdown('CustomerID', $customers, 'Name', 'CustomerID'); ?><br>
    Make:  <input type="text"   name="Make"  required><br>
    Model: <input type="text"   name="Model" required><br>
    Year:  <input type="number" name="Year"  required><br>
    Color: <input type="text"   name="Color" required><br>
    License Plate: <input type="text" name="LicensePlate"><br>
    <button type="submit">Insert Vehicle</button>
</form>

<!-- UPDATE PAGE -->
<?php elseif ($page === 'update'): ?>

<p>All fields are optional. Only filled fields will be updated.</p>

<h2>Update : Customer</h2>
<form method="POST" action="?page=update">
    <input type="hidden" name="action" value="update_customer">
    Select Customer: <?php dropdown('CustomerID', $customers, 'Name', 'CustomerID'); ?><br>
    New First Name: <input type="text"  name="FirstName"><br>
    New Last Name:  <input type="text"  name="LastName"><br>
    New Email:      <input type="email" name="Email"><br>
    New Phone:      <input type="text"  name="PhoneNumber"><br>
    <button type="submit">Update Customer</button>
</form>

<hr>

<h2>Update : Employee</h2>
<form method="POST" action="?page=update">
    <input type="hidden" name="action" value="update_employee">
    Select Employee: <?php dropdown('EmployeeID', $employees, 'Name', 'EmployeeID'); ?><br>
    New First Name:  <input type="text"  name="FirstName"><br>
    New Last Name:   <input type="text"  name="LastName"><br>
    New Email:       <input type="email" name="Email"><br>
    New Role (leave as default to skip):
    <select name="Role">
        <option value="">-- No Change --</option>
        <option value="Staff">Staff</option>
        <option value="Administrator">Administrator</option>
    </select><br>
    <button type="submit">Update Employee</button>
</form>

<hr>

<h2>Update : Product</h2>
<form method="POST" action="?page=update">
    <input type="hidden" name="action" value="update_product">
    Select Product: <?php dropdown('ProductID', $products, 'Name', 'ProductID'); ?><br>
    New Name:          <input type="text"   name="ProductName"><br>
    New Price:         <input type="number" name="Price" step="0.01"><br>
    New Stock:         <input type="number" name="StockQuantity"><br>
    New Category:      <input type="text"   name="Category"><br>
    Availability (leave as default to skip):
    <select name="IsAvailable">
        <option value="">-- No Change --</option>
        <option value="1">Yes</option>
        <option value="0">No</option>
    </select><br>
    <button type="submit">Update Product</button>
</form>

<hr>

<h2>Update : Service</h2>
<form method="POST" action="?page=update">
    <input type="hidden" name="action" value="update_service">
    Select Service: <?php dropdown('ServiceID', $services, 'Name', 'ServiceID'); ?><br>
    New Name:            <input type="text"   name="ServiceName"><br>
    New Price:           <input type="number" name="Price" step="0.01"><br>
    New Duration (mins): <input type="number" name="DurationMinutes"><br>
    Availability (leave as default to skip):
    <select name="IsAvailable">
        <option value="">-- No Change --</option>
        <option value="1">Yes</option>
        <option value="0">No</option>
    </select><br>
    <button type="submit">Update Service</button>
</form>

<hr>

<h2>Update : Appointment</h2>
<form method="POST" action="?page=update">
    <input type="hidden" name="action" value="update_appointment">
    Select Appointment: <?php dropdown('AppointmentID', $appointments, 'Name', 'AppointmentID'); ?><br>
    New Status (leave as default to skip):
    <select name="Status">
        <option value="">-- No Change --</option>
        <option value="Scheduled">Scheduled</option>
        <option value="In Progress">In Progress</option>
        <option value="Completed">Completed</option>
        <option value="Cancelled">Cancelled</option>
    </select><br>
    Assign Employee (leave as default to skip):
    <select name="EmployeeID">
        <option value="">-- No Change --</option>
        <?php foreach ($employees as $e): ?>
            <option value="<?= htmlspecialchars($e['EmployeeID']) ?>"><?= htmlspecialchars($e['Name']) ?></option>
        <?php endforeach; ?>
    </select><br>
    Notes: <input type="text" name="Notes"><br>
    <button type="submit">Update Appointment</button>
</form>

<!-- DELETE PAGE -->
<?php elseif ($page === 'delete'): ?>

<h2>Delete : Customer</h2>
<form method="POST" action="?page=delete">
    <input type="hidden" name="action" value="delete_customer">
    Select Customer: <?php dropdown('CustomerID', $customers, 'Name', 'CustomerID'); ?><br>
    <button type="submit">Delete Customer</button>
</form>

<hr>

<h2>Delete : Employee</h2>
<form method="POST" action="?page=delete">
    <input type="hidden" name="action" value="delete_employee">
    Select Employee: <?php dropdown('EmployeeID', $employees, 'Name', 'EmployeeID'); ?><br>
    <button type="submit">Delete Employee</button>
</form>

<hr>

<h2>Delete : Product</h2>
<form method="POST" action="?page=delete">
    <input type="hidden" name="action" value="delete_product">
    Select Product: <?php dropdown('ProductID', $products, 'Name', 'ProductID'); ?><br>
    <button type="submit">Delete Product</button>
</form>

<hr>

<h2>Delete : Service</h2>
<form method="POST" action="?page=delete">
    <input type="hidden" name="action" value="delete_service">
    Select Service: <?php dropdown('ServiceID', $services, 'Name', 'ServiceID'); ?><br>
    <button type="submit">Delete Service</button>
</form>

<hr>

<h2>Delete : Vehicle</h2>
<form method="POST" action="?page=delete">
    <input type="hidden" name="action" value="delete_vehicle">
    Select Vehicle: <?php dropdown('VehicleID', $vehicles, 'Name', 'VehicleID'); ?><br>
    <button type="submit">Delete Vehicle</button>
</form>

<hr>

<h2>Delete : Promo Code</h2>
<form method="POST" action="?page=delete">
    <input type="hidden" name="action" value="delete_promo">
    Select Promo Code: <?php dropdown('PromoCodeID', $promos, 'Name', 'PromoCodeID'); ?><br>
    <button type="submit">Delete Promo Code</button>
</form>

<!-- SELECT PAGE -->
<?php elseif ($page === 'select'): ?>

<h2>Select : All Customers</h2>
<?php renderTable($pdo->query("SELECT * FROM customer")->fetchAll(PDO::FETCH_ASSOC)); ?>

<h2>Select : All Employees</h2>
<?php renderTable($pdo->query("SELECT * FROM employee")->fetchAll(PDO::FETCH_ASSOC)); ?>

<h2>Select : All Products</h2>
<?php renderTable($pdo->query("SELECT * FROM product")->fetchAll(PDO::FETCH_ASSOC)); ?>

<h2>Select : All Services</h2>
<?php renderTable($pdo->query("SELECT * FROM service")->fetchAll(PDO::FETCH_ASSOC)); ?>

<h2>Select : All Vehicles</h2>
<?php renderTable($pdo->query("SELECT * FROM vehicle")->fetchAll(PDO::FETCH_ASSOC)); ?>

<h2>Select : All Appointments</h2>
<?php renderTable($pdo->query("SELECT * FROM appointment")->fetchAll(PDO::FETCH_ASSOC)); ?>

<h2>Select : All Product Orders</h2>
<?php renderTable($pdo->query("SELECT * FROM product_order")->fetchAll(PDO::FETCH_ASSOC)); ?>

<h2>Select : All Promo Codes</h2>
<?php renderTable($pdo->query("SELECT * FROM promo_code")->fetchAll(PDO::FETCH_ASSOC)); ?>

<!-- VIEWS PAGE -->
<?php elseif ($page === 'views'): ?>

<h2>View : vw_appointment_summary</h2>
<p>Displays a complete overview of every appointment including customer name, vehicle, assigned employee, status, and calculated total cost.</p>
<?php renderTable($pdo->query("SELECT * FROM vw_appointment_summary")->fetchAll(PDO::FETCH_ASSOC)); ?>

<hr>

<h2>View : vw_product_order_summary</h2>
<p>Displays every product order expanded into its individual line items with customer name, promo code applied, and calculated order total.</p>
<?php renderTable($pdo->query("SELECT * FROM vw_product_order_summary")->fetchAll(PDO::FETCH_ASSOC)); ?>

<!-- FUNCTIONS PAGE -->
<?php elseif ($page === 'functions'): ?>

<h2>Function : GetAppointmentTotal(AppointmentID)</h2>
<p>Returns the sum of all service prices booked on a given appointment. Replaces the TotalCost column removed during 3NF normalization.</p>
<form method="GET" action="?page=functions">
    <input type="hidden" name="page" value="functions">
    Appointment ID: <input type="number" name="appt_id" value="<?= htmlspecialchars($_GET['appt_id'] ?? '') ?>">
    <button type="submit">Calculate</button>
</form>
<?php
if (!empty($_GET['appt_id'])) {
    $id = (int)$_GET['appt_id'];
    $result = $pdo->query("SELECT GetAppointmentTotal($id) AS AppointmentTotal")->fetch(PDO::FETCH_ASSOC);
    echo "<p>Appointment Total for ID $id: <strong>$" . htmlspecialchars($result['AppointmentTotal']) . "</strong></p>";
}
?>

<hr>

<h2>Function : GetOrderTotal(OrderID)</h2>
<p>Returns the final total of a product order by adding SubTotal and TaxAmount. Replaces the TotalAmount column removed during 3NF normalization.</p>
<form method="GET" action="?page=functions">
    <input type="hidden" name="page" value="functions">
    Order ID: <input type="number" name="order_id" value="<?= htmlspecialchars($_GET['order_id'] ?? '') ?>">
    <button type="submit">Calculate</button>
</form>
<?php
if (!empty($_GET['order_id'])) {
    $id = (int)$_GET['order_id'];
    $result = $pdo->query("SELECT GetOrderTotal($id) AS OrderTotal")->fetch(PDO::FETCH_ASSOC);
    echo "<p>Order Total for ID $id: <strong>$" . htmlspecialchars($result['OrderTotal']) . "</strong></p>";
}
?>

<!-- STORED PROCEDURES PAGE -->
<?php elseif ($page === 'procedures'): ?>

<h2>Stored Procedure : BookAppointment (with Transaction)</h2>
<p>Inserts a new appointment and links it to a service atomically. If anything fails, both inserts are rolled back preventing orphaned records.</p>
<form method="POST" action="?page=procedures">
    <input type="hidden" name="action" value="call_book_appointment">
    Customer:   <?php dropdown('CustomerID', $customers, 'Name', 'CustomerID'); ?><br>
    Vehicle:    <?php dropdown('VehicleID',  $vehicles,  'Name', 'VehicleID');  ?><br>
    Employee:   <?php dropdown('EmployeeID', $employees, 'Name', 'EmployeeID'); ?><br>
    Date:       <input type="date"   name="AppointmentDate" required><br>
    Time Slot:  <input type="text"   name="TimeSlot" placeholder="e.g. 9:00 AM - 11:00 AM" required><br>
    Service:    <?php dropdown('ServiceID', $services, 'Name', 'ServiceID'); ?><br>
    Price at Booking: <input type="number" name="PriceAtBooking" step="0.01" required><br>
    <button type="submit">Book Appointment</button>
</form>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'call_book_appointment') {
    try {
        $stmt = $pdo->prepare("CALL BookAppointment(?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['CustomerID'], $_POST['VehicleID'], $_POST['EmployeeID'],
            $_POST['AppointmentDate'], $_POST['TimeSlot'],
            $_POST['ServiceID'], $_POST['PriceAtBooking']
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color:green'><strong>Message:</strong> " . htmlspecialchars($result['Message'] ?? 'Procedure executed.') . "</p>";
        if (!empty($result['NewAppointmentID'])) {
            echo "<p>New Appointment ID: <strong>" . htmlspecialchars($result['NewAppointmentID']) . "</strong></p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>

<hr>

<h2>Stored Procedure : GetCustomerHistory</h2>
<p>Returns two result sets for a given customer: their full appointment history and their full product order history with line items.</p>
<form method="GET" action="?page=procedures">
    <input type="hidden" name="page" value="procedures">
    Select Customer: <?php dropdown('hist_customer', $customers, 'Name', 'CustomerID'); ?>
    <button type="submit">Get History</button>
</form>
<?php
if (!empty($_GET['hist_customer'])) {
    $cid = (int)$_GET['hist_customer'];
    try {
        $appts = $pdo->query("
            SELECT a.AppointmentID, a.AppointmentDate, a.TimeSlot, a.Status AS AppointmentStatus,
                   GetAppointmentTotal(a.AppointmentID) AS AppointmentTotal
            FROM appointment a WHERE a.CustomerID = $cid ORDER BY a.AppointmentDate DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        echo "<h3>Appointment History</h3>";
        renderTable($appts);

        $orders = $pdo->query("
            SELECT po.OrderID, po.OrderDate, po.Status AS OrderStatus,
                   p.ProductName, oi.Quantity, oi.PriceAtPurchase,
                   IFNULL(pc.Code, 'None') AS PromoCodeApplied,
                   GetOrderTotal(po.OrderID) AS OrderTotal
            FROM product_order po
            INNER JOIN order_item oi ON po.OrderID = oi.OrderID
            INNER JOIN product p     ON oi.ProductID = p.ProductID
            LEFT  JOIN promo_code pc ON po.PromoCodeID = pc.PromoCodeID
            WHERE po.CustomerID = $cid ORDER BY po.OrderDate DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        echo "<h3>Order History</h3>";
        renderTable($orders);

    } catch (PDOException $e) {
        echo "<p style='color:red'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>

<!-- TRIGGERS PAGE -->
<?php elseif ($page === 'triggers'): ?>

<h2>Trigger 1 : trg_reduce_stock_on_order</h2>
<p>Fires automatically after every insert into order_item and reduces the StockQuantity of the purchased product by the quantity ordered.</p>

<h3>Current Product Stock Levels</h3>
<?php renderTable($pdo->query("SELECT ProductID, ProductName, StockQuantity FROM product ORDER BY ProductID")->fetchAll(PDO::FETCH_ASSOC)); ?>

<h3>Insert Order Item (fires trigger)</h3>
<form method="POST" action="?page=triggers">
    <input type="hidden" name="action" value="trigger_stock">
    Order ID:   <?php dropdown('OrderID', array_map(fn($r) => ['OrderID' => $r['OrderID'], 'Name' => 'Order #'.$r['OrderID']], $pdo->query("SELECT OrderID FROM product_order")->fetchAll(PDO::FETCH_ASSOC)), 'Name', 'OrderID'); ?><br>
    Product:    <?php dropdown('ProductID', $products, 'Name', 'ProductID'); ?><br>
    Quantity:   <input type="number" name="Quantity" value="1" required><br>
    Price Paid: <input type="number" name="PriceAtPurchase" step="0.01" required><br>
    <button type="submit">Insert Order Item</button>
</form>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'trigger_stock') {
    try {
        $stmt = $pdo->prepare("INSERT INTO order_item (OrderID, ProductID, Quantity, PriceAtPurchase) VALUES (?,?,?,?)");
        $stmt->execute([$_POST['OrderID'], $_POST['ProductID'], $_POST['Quantity'], $_POST['PriceAtPurchase']]);
        echo "<p style='color:green'>Order item inserted. Trigger fired : stock updated.</p>";
        echo "<h3>Updated Product Stock Levels</h3>";
        renderTable($pdo->query("SELECT ProductID, ProductName, StockQuantity FROM product ORDER BY ProductID")->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        echo "<p style='color:red'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>

<hr>

<h2>Trigger 2 : trg_complete_appointment_on_record</h2>
<p>Fires automatically after every insert into service_record and updates the linked appointment's Status to Completed.</p>

<h3>Current Appointment Statuses</h3>
<?php renderTable($pdo->query("SELECT AppointmentID, AppointmentDate, Status FROM appointment ORDER BY AppointmentID")->fetchAll(PDO::FETCH_ASSOC)); ?>

<h3>Insert Service Record (fires trigger)</h3>
<form method="POST" action="?page=triggers">
    <input type="hidden" name="action" value="trigger_complete">
    Appointment: <?php dropdown('AppointmentID', $appointments, 'Name', 'AppointmentID'); ?><br>
    Employee:    <?php dropdown('EmployeeID', $employees, 'Name', 'EmployeeID'); ?><br>
    Products Used: <input type="text" name="ProductsUsed"><br>
    Labor Minutes: <input type="number" name="LaborMinutes"><br>
    Staff Notes:   <input type="text" name="StaffNotes"><br>
    <button type="submit">Insert Service Record</button>
</form>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'trigger_complete') {
    try {
        $stmt = $pdo->prepare("INSERT INTO service_record (AppointmentID, EmployeeID, ProductsUsed, LaborMinutes, StaffNotes, CompletedAt)
                               VALUES (?,?,?,?,?,NOW())");
        $stmt->execute([
            $_POST['AppointmentID'], $_POST['EmployeeID'],
            $_POST['ProductsUsed'] ?: null, $_POST['LaborMinutes'] ?: null,
            $_POST['StaffNotes'] ?: null
        ]);
        echo "<p style='color:green'>Service record inserted. Trigger fired : appointment marked Completed.</p>";
        echo "<h3>Updated Appointment Statuses</h3>";
        renderTable($pdo->query("SELECT AppointmentID, AppointmentDate, Status FROM appointment ORDER BY AppointmentID")->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        echo "<p style='color:red'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>

<?php endif; ?>

</body>
</html>
