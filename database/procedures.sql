DELIMITER $$

-- =========================
-- Login User
-- =========================
CREATE PROCEDURE SP_LoginUser (
    IN p_Email VARCHAR(150)
)
BEGIN
    SELECT 
        user_id,
        email,
        password,
        user_type
    FROM users
    WHERE email = p_Email
    LIMIT 1;
END$$

-- =========================
-- ADD BOOK
-- =========================
CREATE PROCEDURE SP_AddBook (
    IN p_Title VARCHAR(255),
    IN p_Author VARCHAR(255),
    IN p_ISBN VARCHAR(20),
    IN p_Publisher VARCHAR(150),
    IN p_Publication_Year YEAR,
    IN p_Category_ID INT,
    IN p_Quantity INT,
    IN p_Shelf_Location VARCHAR(50)
)
BEGIN
    INSERT INTO items (
        title, author, isbn, publisher, publication_year,
        category_id, quantity_available, shelf_location, item_status
    )
    VALUES (
        p_Title, p_Author, p_ISBN, p_Publisher, p_Publication_Year,
        p_Category_ID, p_Quantity, p_Shelf_Location,
        IF(p_Quantity > 0, 'Available', 'Borrowed')
    );

    SELECT LAST_INSERT_ID() AS new_item_id;
END$$


-- =========================
-- REMOVE BOOK
-- =========================
CREATE PROCEDURE SP_RemoveBook (
    IN p_Item_ID INT
)
BEGIN
    DECLARE v_count INT;

    SELECT COUNT(*) INTO v_count
    FROM history
    WHERE item_id = p_Item_ID AND borrow_status = 'Borrowed';

    IF v_count > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot remove item: currently borrowed.';
    ELSE
        DELETE FROM items WHERE item_id = p_Item_ID;
    END IF;
END$$


-- =========================
-- EDIT BOOK
-- =========================
CREATE PROCEDURE SP_EditBook (
    IN p_Item_ID INT,
    IN p_Title VARCHAR(255),
    IN p_Author VARCHAR(255),
    IN p_ISBN VARCHAR(20),
    IN p_Publisher VARCHAR(150),
    IN p_Publication_Year YEAR,
    IN p_Category_ID INT,
    IN p_Quantity INT,
    IN p_Shelf_Location VARCHAR(50),
    IN p_Item_Status VARCHAR(20)
)
BEGIN
    UPDATE items
    SET title = p_Title,
        author = p_Author,
        isbn = p_ISBN,
        publisher = p_Publisher,
        publication_year = p_Publication_Year,
        category_id = p_Category_ID,
        quantity_available = p_Quantity,
        shelf_location = p_Shelf_Location,
        item_status = p_Item_Status
    WHERE item_id = p_Item_ID;
END$$


-- =========================
-- SEARCH CATALOG
-- =========================
CREATE PROCEDURE SP_SearchCatalog (
    IN p_Keyword VARCHAR(255),
    IN p_Category_ID INT
)
BEGIN
    SELECT i.item_id, i.title, i.author, i.isbn,
           i.publisher, i.publication_year,
           c.category_name, i.quantity_available,
           i.shelf_location, i.item_status
    FROM items i
    JOIN categories c ON c.category_id = i.category_id
    WHERE (p_Category_ID IS NULL OR i.category_id = p_Category_ID)
      AND (
          i.title LIKE CONCAT('%', p_Keyword, '%')
          OR i.author LIKE CONCAT('%', p_Keyword, '%')
          OR i.isbn LIKE CONCAT('%', p_Keyword, '%')
          OR i.publisher LIKE CONCAT('%', p_Keyword, '%')
      )
    ORDER BY i.title;
END$$


-- =========================
-- REGISTER USER
-- =========================
CREATE PROCEDURE SP_RegisterUser (
    IN p_First_Name VARCHAR(50),
    IN p_Middle_Name VARCHAR(50),
    IN p_Last_Name VARCHAR(50),
    IN p_Email VARCHAR(150),
    IN p_Password VARCHAR(255),
    IN p_Contact_Number VARCHAR(20),
    IN p_Address TEXT,
    IN p_User_Type VARCHAR(20)
)
BEGIN
    DECLARE v_exists INT;

    SELECT COUNT(*) INTO v_exists
    FROM users
    WHERE email = p_Email;

    IF v_exists > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Email already registered.';
    ELSE
        INSERT INTO users (
            first_name, middle_name, last_name, email,
            password, contact_number, address, user_type, registration_date
        )
        VALUES (
            p_First_Name, p_Middle_Name, p_Last_Name, p_Email,
            p_Password, p_Contact_Number, p_Address, p_User_Type, CURRENT_DATE
        );

        SELECT LAST_INSERT_ID() AS new_user_id;
    END IF;
END$$


-- =========================
-- CANCEL ACCOUNT
-- =========================
CREATE PROCEDURE SP_CancelAccount (
    IN p_User_ID INT
)
BEGIN
    DECLARE v_borrowed INT;
    DECLARE v_unpaid INT;

    SELECT COUNT(*) INTO v_borrowed
    FROM history
    WHERE user_id = p_User_ID AND borrow_status = 'Borrowed';

    SELECT COUNT(*) INTO v_unpaid
    FROM penalties p
    JOIN history h ON h.history_id = p.history_id
    WHERE h.user_id = p_User_ID AND p.payment_status = 'Unpaid';

    IF v_borrowed > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot cancel account: unreturned items exist.';
    ELSEIF v_unpaid > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot cancel account: unpaid penalties exist.';
    ELSE
        UPDATE schedule
        SET status = 'Cancelled'
        WHERE user_id = p_User_ID;

        DELETE FROM users WHERE user_id = p_User_ID;
    END IF;
END$$


-- =========================
-- CHECKOUT BOOK
-- =========================
CREATE PROCEDURE SP_CheckOutBook (
    IN p_User_ID INT,
    IN p_Item_ID INT,
    IN p_Loan_Days INT
)
BEGIN
    DECLARE v_qty INT;
    DECLARE v_unpaid INT;

    SELECT quantity_available INTO v_qty
    FROM items
    WHERE item_id = p_Item_ID FOR UPDATE;

    SELECT COUNT(*) INTO v_unpaid
    FROM penalties p
    JOIN history h ON h.history_id = p.history_id
    WHERE h.user_id = p_User_ID AND p.payment_status = 'Unpaid';

    IF v_unpaid > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Unpaid penalties exist.';
    ELSEIF v_qty IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Item not found.';
    ELSEIF v_qty < 1 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'No copies available.';
    ELSE
        INSERT INTO history (
            user_id, item_id, borrowed_date, due_date, borrow_status
        )
        VALUES (
            p_User_ID, p_Item_ID, CURRENT_DATE,
            DATE_ADD(CURRENT_DATE, INTERVAL p_Loan_Days DAY),
            'Borrowed'
        );

        UPDATE items
        SET quantity_available = quantity_available - 1,
            item_status = IF(quantity_available - 1 = 0, 'Borrowed', 'Available')
        WHERE item_id = p_Item_ID;

        UPDATE schedule
        SET status = 'Cancelled'
        WHERE user_id = p_User_ID AND item_id = p_Item_ID;

        SELECT LAST_INSERT_ID() AS new_history_id;
    END IF;
END$$


-- =========================
-- RESERVE BOOK
-- =========================
CREATE PROCEDURE SP_ReserveBook (
    IN p_User_ID INT,
    IN p_Item_ID INT,
    IN p_Expiry_Days INT
)
BEGIN
    DECLARE v_existing INT;

    SELECT COUNT(*) INTO v_existing
    FROM schedule
    WHERE user_id = p_User_ID AND item_id = p_Item_ID
      AND status IN ('Pending','Approved');

    IF v_existing > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Already reserved.';
    ELSE
        INSERT INTO schedule (
            user_id, item_id, reserve_date, expiration_date, status
        )
        VALUES (
            p_User_ID, p_Item_ID, CURRENT_DATE,
            DATE_ADD(CURRENT_DATE, INTERVAL p_Expiry_Days DAY),
            'Pending'
        );

        SELECT LAST_INSERT_ID() AS new_schedule_id;
    END IF;
END$$


-- =========================
-- RENEW BOOK
-- =========================
CREATE PROCEDURE SP_RenewBook (
    IN p_History_ID INT,
    IN p_Extension_Days INT
)
BEGIN
    DECLARE v_status VARCHAR(20);
    DECLARE v_due DATE;

    SELECT borrow_status, due_date
    INTO v_status, v_due
    FROM history
    WHERE history_id = p_History_ID;

    IF v_status IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Record not found.';
    ELSEIF v_status <> 'Borrowed' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Only active borrows can be renewed.';
    ELSE
        UPDATE history
        SET due_date = DATE_ADD(due_date, INTERVAL p_Extension_Days DAY)
        WHERE history_id = p_History_ID;

        SELECT due_date AS new_due_date
        FROM history
        WHERE history_id = p_History_ID;
    END IF;
END$$


-- =========================
-- RETURN BOOK
-- =========================
CREATE PROCEDURE SP_ReturnBook (
    IN p_History_ID INT,
    IN p_Penalty_Per_Day DECIMAL(10,2)
)
BEGIN
    DECLARE v_item_id INT;
    DECLARE v_due DATE;
    DECLARE v_status VARCHAR(20);
    DECLARE v_days_late INT;
    DECLARE v_penalty DECIMAL(10,2);

    SELECT item_id, due_date, borrow_status
    INTO v_item_id, v_due, v_status
    FROM history
    WHERE history_id = p_History_ID;

    IF v_status IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Record not found.';
    ELSEIF v_status = 'Returned' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Already returned.';
    ELSE
        SET v_days_late = GREATEST(0, DATEDIFF(CURRENT_DATE, v_due));
        SET v_penalty = v_days_late * p_Penalty_Per_Day;

        UPDATE history
        SET return_date = CURRENT_DATE,
            borrow_status = IF(v_days_late > 0, 'Overdue', 'Returned')
        WHERE history_id = p_History_ID;

        UPDATE items
        SET quantity_available = quantity_available + 1,
            item_status = 'Available'
        WHERE item_id = v_item_id;

        IF v_penalty > 0 THEN
            INSERT INTO penalties (history_id, penalty_amount, payment_status)
            VALUES (p_History_ID, v_penalty, 'Unpaid');
        END IF;

        SELECT v_days_late AS days_late, v_penalty AS penalty_amount;
    END IF;
END$$

DELIMITER ;