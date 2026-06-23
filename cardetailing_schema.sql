-- ============================================================
--  Car Detailing Schema
-- ============================================================

CREATE SCHEMA IF NOT EXISTS `cardetailing`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_0900_ai_ci;

USE `cardetailing`;

-- ============================================================
--  TABLES
-- ============================================================

CREATE TABLE `customer` (
  `CustomerID`   int          NOT NULL AUTO_INCREMENT,
  `FirstName`    varchar(50)  NOT NULL,
  `LastName`     varchar(50)  NOT NULL,
  `Email`        varchar(100) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `PhoneNumber`  varchar(20)  DEFAULT NULL,
  `CreatedAt`    datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `IsActive`     tinyint(1)   NOT NULL DEFAULT '1',
  PRIMARY KEY (`CustomerID`),
  UNIQUE KEY `Email` (`Email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `employee` (
  `EmployeeID`   int          NOT NULL AUTO_INCREMENT,
  `FirstName`    varchar(50)  NOT NULL,
  `LastName`     varchar(50)  NOT NULL,
  `Email`        varchar(100) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `Role`         enum('Staff','Administrator') NOT NULL,
  `IsActive`     tinyint(1)   NOT NULL DEFAULT '1',
  PRIMARY KEY (`EmployeeID`),
  UNIQUE KEY `Email` (`Email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `vehicle` (
  `VehicleID`    int         NOT NULL AUTO_INCREMENT,
  `CustomerID`   int         NOT NULL,
  `Make`         varchar(50) NOT NULL,
  `Model`        varchar(50) NOT NULL,
  `Year`         year        NOT NULL,
  `Color`        varchar(30) NOT NULL,
  `LicensePlate` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`VehicleID`),
  KEY `fk_vehicle_customer` (`CustomerID`),
  CONSTRAINT `fk_vehicle_customer` FOREIGN KEY (`CustomerID`) REFERENCES `customer` (`CustomerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `service` (
  `ServiceID`       int          NOT NULL AUTO_INCREMENT,
  `ServiceName`     varchar(100) NOT NULL,
  `Description`     text,
  `Price`           decimal(10,2) NOT NULL,
  `DurationMinutes` int          NOT NULL,
  `IsAvailable`     tinyint(1)   NOT NULL DEFAULT '1',
  PRIMARY KEY (`ServiceID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `product` (
  `ProductID`     int          NOT NULL AUTO_INCREMENT,
  `ProductName`   varchar(100) NOT NULL,
  `Description`   text,
  `Price`         decimal(10,2) NOT NULL,
  `StockQuantity` int          NOT NULL DEFAULT '0',
  `Category`      varchar(50)  DEFAULT NULL,
  `IsAvailable`   tinyint(1)   NOT NULL DEFAULT '1',
  PRIMARY KEY (`ProductID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `promo_code` (
  `PromoCodeID`    int          NOT NULL AUTO_INCREMENT,
  `Code`           varchar(50)  NOT NULL,
  `DiscountType`   enum('Percentage','Fixed Amount') NOT NULL,
  `DiscountValue`  decimal(10,2) NOT NULL,
  `ExpirationDate` date         NOT NULL,
  `IsActive`       tinyint(1)   NOT NULL DEFAULT '1',
  PRIMARY KEY (`PromoCodeID`),
  UNIQUE KEY `Code` (`Code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `appointment` (
  `AppointmentID`   int         NOT NULL AUTO_INCREMENT,
  `CustomerID`      int         NOT NULL,
  `VehicleID`       int         NOT NULL,
  `EmployeeID`      int         DEFAULT NULL,
  `AppointmentDate` date        NOT NULL,
  `TimeSlot`        varchar(50) NOT NULL,
  `Status`          enum('Scheduled','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Scheduled',
  `Notes`           text,
  PRIMARY KEY (`AppointmentID`),
  KEY `fk_appt_customer`  (`CustomerID`),
  KEY `fk_appt_vehicle`   (`VehicleID`),
  KEY `fk_appt_employee`  (`EmployeeID`),
  CONSTRAINT `fk_appt_customer`  FOREIGN KEY (`CustomerID`) REFERENCES `customer`  (`CustomerID`),
  CONSTRAINT `fk_appt_vehicle`   FOREIGN KEY (`VehicleID`)  REFERENCES `vehicle`   (`VehicleID`),
  CONSTRAINT `fk_appt_employee`  FOREIGN KEY (`EmployeeID`) REFERENCES `employee`  (`EmployeeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `appointment_service` (
  `AppointmentID`  int           NOT NULL,
  `ServiceID`      int           NOT NULL,
  `PriceAtBooking` decimal(10,2) NOT NULL,
  PRIMARY KEY (`AppointmentID`, `ServiceID`),
  KEY `fk_apptservice_service` (`ServiceID`),
  CONSTRAINT `fk_apptservice_appt`    FOREIGN KEY (`AppointmentID`) REFERENCES `appointment` (`AppointmentID`),
  CONSTRAINT `fk_apptservice_service` FOREIGN KEY (`ServiceID`)     REFERENCES `service`     (`ServiceID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `service_record` (
  `RecordID`      int      NOT NULL AUTO_INCREMENT,
  `AppointmentID` int      NOT NULL,
  `EmployeeID`    int      NOT NULL,
  `ProductsUsed`  text,
  `LaborMinutes`  int      DEFAULT NULL,
  `StaffNotes`    text,
  `CompletedAt`   datetime NOT NULL,
  PRIMARY KEY (`RecordID`),
  UNIQUE KEY `AppointmentID` (`AppointmentID`),
  KEY `fk_record_employee` (`EmployeeID`),
  CONSTRAINT `fk_record_appt`     FOREIGN KEY (`AppointmentID`) REFERENCES `appointment` (`AppointmentID`),
  CONSTRAINT `fk_record_employee` FOREIGN KEY (`EmployeeID`)    REFERENCES `employee`    (`EmployeeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `product_order` (
  `OrderID`     int           NOT NULL AUTO_INCREMENT,
  `CustomerID`  int           NOT NULL,
  `PromoCodeID` int           DEFAULT NULL,
  `OrderDate`   datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `SubTotal`    decimal(10,2) NOT NULL,
  `TaxAmount`   decimal(10,2) NOT NULL,
  `Status`      enum('Processing','Shipped','Delivered','Cancelled') NOT NULL DEFAULT 'Processing',
  PRIMARY KEY (`OrderID`),
  KEY `fk_order_customer` (`CustomerID`),
  KEY `fk_order_promo`    (`PromoCodeID`),
  CONSTRAINT `fk_order_customer` FOREIGN KEY (`CustomerID`)  REFERENCES `customer`   (`CustomerID`),
  CONSTRAINT `fk_order_promo`    FOREIGN KEY (`PromoCodeID`) REFERENCES `promo_code` (`PromoCodeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `order_item` (
  `OrderID`         int           NOT NULL,
  `ProductID`       int           NOT NULL,
  `Quantity`        int           NOT NULL DEFAULT '1',
  `PriceAtPurchase` decimal(10,2) NOT NULL,
  PRIMARY KEY (`OrderID`, `ProductID`),
  KEY `fk_orderitem_product` (`ProductID`),
  CONSTRAINT `fk_orderitem_order`   FOREIGN KEY (`OrderID`)   REFERENCES `product_order` (`OrderID`),
  CONSTRAINT `fk_orderitem_product` FOREIGN KEY (`ProductID`) REFERENCES `product`       (`ProductID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `product_review` (
  `ReviewID`   int      NOT NULL AUTO_INCREMENT,
  `CustomerID` int      NOT NULL,
  `ProductID`  int      NOT NULL,
  `Rating`     tinyint  NOT NULL,
  `ReviewText` text,
  `ReviewDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ReviewID`),
  KEY `fk_prodreview_customer` (`CustomerID`),
  KEY `fk_prodreview_product`  (`ProductID`),
  CONSTRAINT `fk_prodreview_customer` FOREIGN KEY (`CustomerID`) REFERENCES `customer` (`CustomerID`),
  CONSTRAINT `fk_prodreview_product`  FOREIGN KEY (`ProductID`)  REFERENCES `product`  (`ProductID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `service_review` (
  `ReviewID`   int      NOT NULL AUTO_INCREMENT,
  `CustomerID` int      NOT NULL,
  `ServiceID`  int      NOT NULL,
  `Rating`     tinyint  NOT NULL,
  `ReviewText` text,
  `ReviewDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ReviewID`),
  KEY `fk_svcreview_customer` (`CustomerID`),
  KEY `fk_svcreview_service`  (`ServiceID`),
  CONSTRAINT `fk_svcreview_customer` FOREIGN KEY (`CustomerID`) REFERENCES `customer` (`CustomerID`),
  CONSTRAINT `fk_svcreview_service`  FOREIGN KEY (`ServiceID`)  REFERENCES `service`  (`ServiceID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================
--  FUNCTIONS
-- ============================================================

DELIMITER $$

CREATE DEFINER=`cardetailing`@`%` FUNCTION `GetAppointmentTotal`(p_AppointmentID INT)
RETURNS decimal(10,2)
    READS SQL DATA
    DETERMINISTIC
BEGIN
    DECLARE v_Total DECIMAL(10,2);

    SELECT SUM(PriceAtBooking)
    INTO v_Total
    FROM appointment_service
    WHERE AppointmentID = p_AppointmentID;

    RETURN IFNULL(v_Total, 0.00);
END$$

CREATE DEFINER=`cardetailing`@`%` FUNCTION `GetOrderTotal`(p_OrderID INT)
RETURNS decimal(10,2)
    READS SQL DATA
    DETERMINISTIC
BEGIN
    DECLARE v_Total DECIMAL(10,2);

    SELECT (SubTotal + TaxAmount)
    INTO v_Total
    FROM product_order
    WHERE OrderID = p_OrderID;

    RETURN IFNULL(v_Total, 0.00);
END$$

DELIMITER ;

-- ============================================================
--  STORED PROCEDURES
-- ============================================================

DELIMITER $$

CREATE DEFINER=`cardetailing`@`%` PROCEDURE `BookAppointment`(
    IN p_CustomerID     INT,
    IN p_VehicleID      INT,
    IN p_EmployeeID     INT,
    IN p_Date           DATE,
    IN p_TimeSlot       VARCHAR(50),
    IN p_ServiceID      INT,
    IN p_PriceAtBooking DECIMAL(10,2)
)
BEGIN
    DECLARE v_AppointmentID INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SELECT 'Transaction failed. Appointment was not booked.' AS Message;
    END;

    START TRANSACTION;

        INSERT INTO appointment (CustomerID, VehicleID, EmployeeID, AppointmentDate, TimeSlot, Status, Notes)
        VALUES (p_CustomerID, p_VehicleID, p_EmployeeID, p_Date, p_TimeSlot, 'Scheduled', NULL);

        SET v_AppointmentID = LAST_INSERT_ID();

        INSERT INTO appointment_service (AppointmentID, ServiceID, PriceAtBooking)
        VALUES (v_AppointmentID, p_ServiceID, p_PriceAtBooking);

    COMMIT;

    SELECT v_AppointmentID AS NewAppointmentID, 'Appointment booked successfully.' AS Message;
END$$

CREATE DEFINER=`cardetailing`@`%` PROCEDURE `GetCustomerHistory`(
    IN p_CustomerID INT
)
BEGIN
    SELECT
        a.AppointmentID,
        a.AppointmentDate,
        a.TimeSlot,
        a.Status                             AS AppointmentStatus,
        GetAppointmentTotal(a.AppointmentID) AS AppointmentTotal
    FROM appointment a
    WHERE a.CustomerID = p_CustomerID
    ORDER BY a.AppointmentDate DESC;

    SELECT
        po.OrderID,
        po.OrderDate,
        po.Status                            AS OrderStatus,
        p.ProductName,
        oi.Quantity,
        oi.PriceAtPurchase,
        IFNULL(pc.Code, 'None')              AS PromoCodeApplied,
        GetOrderTotal(po.OrderID)            AS OrderTotal
    FROM product_order po
    INNER JOIN order_item  oi ON po.OrderID     = oi.OrderID
    INNER JOIN product     p  ON oi.ProductID   = p.ProductID
    LEFT  JOIN promo_code  pc ON po.PromoCodeID = pc.PromoCodeID
    WHERE po.CustomerID = p_CustomerID
    ORDER BY po.OrderDate DESC;
END$$

DELIMITER ;

-- ============================================================
--  TRIGGERS
-- ============================================================

CREATE DEFINER=`cardetailing`@`%` TRIGGER `trg_reduce_stock_on_order`
AFTER INSERT ON `order_item`
FOR EACH ROW
BEGIN
    UPDATE product
    SET StockQuantity = StockQuantity - NEW.Quantity
    WHERE ProductID = NEW.ProductID;
END;

CREATE DEFINER=`cardetailing`@`%` TRIGGER `trg_complete_appointment_on_record`
AFTER INSERT ON `service_record`
FOR EACH ROW
BEGIN
    UPDATE appointment
    SET Status = 'Completed'
    WHERE AppointmentID = NEW.AppointmentID;
END;

-- ============================================================
--  VIEWS
-- ============================================================

CREATE ALGORITHM=UNDEFINED
    DEFINER=`cardetailing`@`%`
    SQL SECURITY DEFINER
VIEW `vw_appointment_summary` AS
    SELECT
        a.AppointmentID,
        CONCAT(c.FirstName, ' ', c.LastName)         AS CustomerName,
        CONCAT(v.Year, ' ', v.Make, ' ', v.Model)    AS Vehicle,
        a.AppointmentDate,
        a.TimeSlot,
        a.Status,
        CONCAT(e.FirstName, ' ', e.LastName)         AS AssignedEmployee,
        GetAppointmentTotal(a.AppointmentID)         AS TotalCost
    FROM appointment a
    JOIN customer c  ON a.CustomerID = c.CustomerID
    JOIN vehicle  v  ON a.VehicleID  = v.VehicleID
    LEFT JOIN employee e ON a.EmployeeID = e.EmployeeID;

CREATE ALGORITHM=UNDEFINED
    DEFINER=`cardetailing`@`%`
    SQL SECURITY DEFINER
VIEW `vw_product_order_summary` AS
    SELECT
        po.OrderID,
        CONCAT(c.FirstName, ' ', c.LastName) AS CustomerName,
        po.OrderDate,
        po.Status,
        p.ProductName,
        oi.Quantity,
        oi.PriceAtPurchase,
        pc.Code                              AS PromoCodeApplied,
        GetOrderTotal(po.OrderID)            AS OrderTotal
    FROM product_order po
    JOIN customer   c  ON po.CustomerID  = c.CustomerID
    JOIN order_item oi ON po.OrderID     = oi.OrderID
    JOIN product    p  ON oi.ProductID   = p.ProductID
    LEFT JOIN promo_code pc ON po.PromoCodeID = pc.PromoCodeID;
