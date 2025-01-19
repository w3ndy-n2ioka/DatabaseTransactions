<?php
$servername = "mysql-8.0.40-debian";
$username = "root";
$password = "5trathm0re";
$dbname = "classicmodels";
$port = 3306;

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    // echo "Connected to the database server successfully";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $conn->begin_transaction();

        // Get new order number
        $result = $conn->query("SELECT MAX(orderNumber)+1 AS newOrderNumber FROM orders");
        $row = $result->fetch_assoc();
        $orderNumber = $row['newOrderNumber'];

        // Insert into orders
        $conn->query("INSERT INTO orders(orderNumber, orderDate, requiredDate, shippedDate, status, customerNumber)
                      VALUES($orderNumber, DATE(NOW()), DATE(DATE_ADD(NOW(), INTERVAL 3 DAY)), DATE(DATE_ADD(NOW(), INTERVAL 2 DAY)), 'In Process', 145)");

        // Savepoint before_product_1
        $conn->query("SAVEPOINT before_product_1");

        // Insert into orderdetails for product 1
        $conn->query("INSERT INTO orderdetails(orderNumber, productCode, quantityOrdered, priceEach, orderLineNumber)
                      VALUES($orderNumber, 'S18_1749', 2, '136', 1)");

        // Update product quantity for product 1
        $conn->query("UPDATE products SET quantityInStock = quantityInStock - 2 WHERE productCode = 'S18_1749'");

        // Savepoint before_product_2
        $conn->query("SAVEPOINT before_product_2");

        // Insert into orderdetails for product 2
        $conn->query("INSERT INTO orderdetails(orderNumber, productCode, quantityOrdered, priceEach, orderLineNumber)
                      VALUES($orderNumber, 'S18_2248', 2, '55.09', 2)");

        // Update product quantity for product 2
        $conn->query("UPDATE products SET quantityInStock = quantityInStock - 2 WHERE productCode = 'S18_2248'");

        // Rollback to savepoint before_product_2
        $conn->query("ROLLBACK TO SAVEPOINT before_product_2");

        // Savepoint before_product_3
        $conn->query("SAVEPOINT before_product_3");

        // Insert into orderdetails for product 3
        $conn->query("INSERT INTO orderdetails(orderNumber, productCode, quantityOrdered, priceEach, orderLineNumber)
                      VALUES($orderNumber, 'S12_1099', 2, '95.34', 3)");

        // Update product quantity for product 3
        $conn->query("UPDATE products SET quantityInStock = quantityInStock - 2 WHERE productCode = 'S12_1099'");

        // Insert into payments
        // Generate a unique check number
        $checkNumber = uniqid('CHK_');

        // Insert into payments
        $conn->query("INSERT INTO payments (customerNumber, checkNumber, paymentDate, amount)
                  VALUES (145, '$checkNumber', DATE(NOW()), 12000)");

        // Commit transaction
        $conn->commit();

        // Select and display order details
        $result = $conn->query("SELECT * FROM orderdetails WHERE orderNumber = $orderNumber");
        echo "**Customer's Receipt**";
        echo "<br>";
        while ($row = $result->fetch_assoc()) {
            echo "OrderNumber: " . $row["orderNumber"]. " - ProductCode: " . $row["productCode"]. " - QuantityOrdered: " . $row["quantityOrdered"]. " - PriceEach: " . $row["priceEach"]. " - OrderLineNumber: " . $row["orderLineNumber"]. "<br>";
        }
    } catch (Exception $e) {
        // Rollback transaction if any error occurs
        $conn->rollback();
        echo "Failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Transaction</title>
</head>
<body>
    <form method="post">
        <button type="submit">Begin Transaction</button>
    </form>
</body>
</html>

<?php
// Close connection
$conn->close();
?>