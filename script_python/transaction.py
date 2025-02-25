import mysql.connector
from mysql.connector import Error
import sys
import uuid

def execute_transaction():
    # Bonus Mark Question: Why did we use "mysql-8.0.40-debian" as the servername in PHP and "localhost" as the servername in Python?
    servername = "localhost"
    username = "root"
    password = "5trathm0re"
    dbname = "classicmodels"
    port = 3307

    try:
        # Create connection
        conn = mysql.connector.connect(
            host=servername,
            user=username,
            password=password,
            database=dbname,
            port=port
        )

        # if conn.is_connected():
        #     print("Connected to the database server successfully")

        cursor = conn.cursor()
        try:
            # Start transaction
            conn.start_transaction()

            # Get new order number
            cursor.execute("SELECT MAX(orderNumber)+1 AS newOrderNumber FROM orders")
            row = cursor.fetchone()
            orderNumber = row[0]

            # Insert into orders
            cursor.execute("""
                INSERT INTO orders(orderNumber, orderDate, requiredDate, shippedDate, status, customerNumber)
                VALUES(%s, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'In Process', 145)
            """, (orderNumber,))

            # Savepoint before_product_1
            cursor.execute("SAVEPOINT before_product_1")

            # Insert into orderdetails for product 1
            cursor.execute("""
                INSERT INTO orderdetails(orderNumber, productCode, quantityOrdered, priceEach, orderLineNumber)
                VALUES(%s, 'S18_1749', 2, '136', 1)
            """, (orderNumber,))

            # Update product quantity for product 1
            cursor.execute("UPDATE products SET quantityInStock = quantityInStock - 2 WHERE productCode = 'S18_1749'")

            # Savepoint before_product_2
            cursor.execute("SAVEPOINT before_product_2")

            # Insert into orderdetails for product 2
            cursor.execute("""
                INSERT INTO orderdetails(orderNumber, productCode, quantityOrdered, priceEach, orderLineNumber)
                VALUES(%s, 'S18_2248', 2, '55.09', 2)
            """, (orderNumber,))

            # Update product quantity for product 2
            cursor.execute("UPDATE products SET quantityInStock = quantityInStock - 2 WHERE productCode = 'S18_2248'")

            # Rollback to savepoint before_product_2
            cursor.execute("ROLLBACK TO SAVEPOINT before_product_2")

            # Savepoint before_product_3
            cursor.execute("SAVEPOINT before_product_3")

            # Insert into orderdetails for product 3
            cursor.execute("""
                INSERT INTO orderdetails(orderNumber, productCode, quantityOrdered, priceEach, orderLineNumber)
                VALUES(%s, 'S12_1099', 2, '95.34', 3)
            """, (orderNumber,))

            # Update product quantity for product 3
            cursor.execute("UPDATE products SET quantityInStock = quantityInStock - 2 WHERE productCode = 'S12_1099'")


            # Generate unique check number with prefix CHQ_
            new_check_number = f"CHQ_{uuid.uuid4().hex[:8]}"

            # Insert into payments
            cursor.execute("""
                INSERT INTO payments(customerNumber, checkNumber, paymentDate, amount)
                VALUES(145, %s, CURDATE(), 1000.00)
            """, (new_check_number,))

            # Commit transaction
            conn.commit()

            # Display the order details
            cursor.execute("SELECT * FROM orderdetails WHERE orderNumber = %s ORDER BY orderLineNumber ASC", (orderNumber,))
            order_details = cursor.fetchall()
            for detail in order_details:
                print(detail)
            
        except Error as e:
            print(f"Error: {e}")
            conn.rollback()
        finally:
            cursor.close()

    except Error as e:
        print(f"Error: {e}")

    finally:
        if conn.is_connected():
            conn.close()
            # print("Connection closed")

if __name__ == "__main__":
    if len(sys.argv) != 2 or sys.argv[1] != 'POST':
        print("Usage: python transaction.py POST")
        sys.exit(1)
    execute_transaction()