-- Schema for CarDBMS
DROP DATABASE IF EXISTS USEDCARS;
CREATE DATABASE USEDCARS;
USE USEDCARS;

-- role table
CREATE TABLE ROLE(
roleID int NOT NULL,
name varchar(30) NOT NULL,
CONSTRAINT pk_role 
PRIMARY KEY (roleID, name)
);
 
INSERT INTO ROLE (roleID, name) VALUES(1, 'admin');

INSERT INTO ROLE (roleID, name) VALUES(2, 'employee');

INSERT INTO ROLE (roleID, name) VALUES(3, 'customer');

-- fuel type table
CREATE TABLE FUELTYPE(
fueltypeID int NOT NULL,
name varchar(30) NOT NULL,	
CONSTRAINT pk_fueltype PRIMARY KEY (fueltypeID, name)
);
 
INSERT INTO FUELTYPE (fueltypeID, name) VALUES(1, 'gas');

INSERT INTO FUELTYPE (fueltypeID, name) VALUES(2, 'EV');

INSERT INTO FUELTYPE (fueltypeID, name) VALUES(3, 'PHEV');

INSERT INTO FUELTYPE (fueltypeID, name) VALUES(4, 'hybrid');

INSERT INTO FUELTYPE (fueltypeID, name) VALUES(5, 'diesel');

INSERT INTO FUELTYPE (fueltypeID, name) VALUES(6, 'hydrogen');

-- drive train table
CREATE TABLE DRIVETRAIN(
drivetrainID int NOT NULL,
name varchar(30) NOT NULL,
CONSTRAINT pk_drivetrain 
PRIMARY KEY (drivetrainID, name)
);
 
INSERT INTO DRIVETRAIN (drivetrainID, name) VALUES(1, 'CVT');

INSERT INTO DRIVETRAIN (drivetrainID, name) VALUES(2, 'auto');

INSERT INTO DRIVETRAIN (drivetrainID, name) VALUES(3, 'manual');

-- transmission table
CREATE TABLE TRANSMISSION(
transmissionID int NOT NULL,
name varchar(30) NOT NULL,
CONSTRAINT pk_transmission PRIMARY KEY (transmissionID, name)
);
 
INSERT INTO TRANSMISSION(transmissionID, name) VALUES(1, '4WD');

INSERT INTO TRANSMISSION(transmissionID, name) VALUES(2, 'AWD');

INSERT INTO TRANSMISSION(transmissionID, name) VALUES(3, 'FWD');

INSERT INTO TRANSMISSION(transmissionID, name) VALUES(4, 'RWD');

-- condition table
CREATE TABLE CONDITIONS(
conditionID int NOT NULL,
name varchar(30) NOT NULL,
CONSTRAINT pk_condition PRIMARY KEY (conditionID)
);
 
INSERT INTO CONDITIONS (conditionID, name) VALUES (1, 'oneOwner');

INSERT INTO CONDITIONS (conditionID, name) VALUES (2, 'cleanTitle');

INSERT INTO CONDITIONS (conditionID, name) VALUES (3, 'noAccident');

INSERT INTO CONDITIONS (conditionID, name) VALUES (4, 'certifiedPreowned');


-- body style table
CREATE TABLE BODYSTYLE(
bodystyleID int NOT NULL,
name varchar(30) NOT NULL,
CONSTRAINT pk_bodystyle 
PRIMARY KEY (bodystyleID, name)
);
 
INSERT INTO BODYSTYLE (bodystyleID, name) VALUES(1, 'sedan');

INSERT INTO BODYSTYLE (bodystyleID, name) VALUES(2, 'SUV');

INSERT INTO BODYSTYLE (bodystyleID, name) VALUES(3, 'truck');

INSERT INTO BODYSTYLE (bodystyleID, name) VALUES(4, 'van');

INSERT INTO BODYSTYLE (bodystyleID, name) VALUES(5, 'coupe');

INSERT INTO BODYSTYLE (bodystyleID, name) VALUES(6, 'hatchback');

-- feature table
DROP TABLE IF EXISTS FEATURES;
CREATE TABLE FEATURES(
featureID int NOT NULL,
name varchar(30) NOT NULL,
CONSTRAINT pk_feature 
PRIMARY KEY (featureID, name)
);
 
INSERT INTO FEATURES (featureID,name) VALUES(1, 'towHitch');

INSERT INTO FEATURES (featureID,name) VALUES(2, 'backupCamera');

INSERT INTO FEATURES (featureID,name) VALUES(3, 'thirdRowSeating');

INSERT INTO FEATURES (featureID,name) VALUES(4, 'Navigation');

INSERT INTO FEATURES (featureID,name) VALUES(5, 'moonRoof');


-- store table
DROP TABLE IF EXISTS STORE;
CREATE TABLE STORE(
storeID int NOT NULL,
name varchar(50) NOT NULL,
address varchar(150) NOT NULL,
ZIP int(5) NOT NULL,
CONSTRAINT pk_store 
PRIMARY KEY (storeID)
);

-- vehicle table (use license plate as vehicleID)
CREATE TABLE VEHICLE(
vehicleID varchar(8) NOT NULL,
brand varchar(50) NOT NULL,
model varchar(50) NOT NULL,
year year NOT NULL,
mileage int(8) NOT NULL,
exColor varchar(20) NOT NULL,
intColor varchar(20) NOT NULL,
seatCapacity int(2) NOT NULL,
fuelTypeID int NOT NULL,
drivetrainID int NOT NULL,
transmissionID int NOT NULL,
bodystyleID int NOT NULL,
storeID int NOT NULL,
CONSTRAINT pk_vehicle PRIMARY KEY (vehicleID),
CONSTRAINT fk_fuelTypeID FOREIGN KEY (fuelTypeID) REFERENCES FUELTYPE(fuelTypeID),
CONSTRAINT fk_drivetrainID FOREIGN KEY (drivetrainID) REFERENCES DRIVETRAIN(drivetrainID),
CONSTRAINT fk_transmissionID FOREIGN KEY (transmissionID) REFERENCES TRANSMISSION(transmissionID),
CONSTRAINT fk_bodystyleID FOREIGN KEY (bodystyleID) REFERENCES BODYSTYLE(bodystyleID),
CONSTRAINT fk_storeID FOREIGN KEY (storeID) REFERENCES STORE(storeID)
);

-- user table
CREATE TABLE USER(
userID int NOT NULL,
name varchar(60) NOT NULL,
email varchar(60) NOT NULL,
phone int(10) NOT NULL,
CONSTRAINT pk_user PRIMARY KEY (userID)
);

-- password table
CREATE TABLE RAINBOW(
userID int NOT NULL,
hashbrown varchar(50) NOT NULL,
CONSTRAINT pk_rainbow PRIMARY KEY (userID,hashbrown),
CONSTRAINT fk_userID FOREIGN KEY (userID) REFERENCES USER(userID)
);

-- has conditions table
CREATE TABLE HAS_CONDITION(
vehicleID varchar(8) NOT NULL,
conditionID int NOT NULL,
CONSTRAINT pk_has_condition PRIMARY KEY (vehicleID, conditionID),
CONSTRAINT fk_vehicleID FOREIGN KEY (vehicleID) REFERENCES VEHICLE(vehicleID),
CONSTRAINT fk_conditionID FOREIGN KEY (conditionID) REFERENCES CONDITIONS(conditionID)
);

-- has features table
CREATE TABLE HAS_FEATURE(
vehicleID varchar(8) NOT NULL,
featureID int NOT NULL,
CONSTRAINT pk_has_feature PRIMARY KEY (vehicleID, featureID),
CONSTRAINT fk_has_feature_vehicleID FOREIGN KEY (vehicleID) REFERENCES VEHICLE(vehicleID)
);

-- employee table
CREATE TABLE EMPLOYEE(
userID int NOT NULL,
storeID int NOT NULL,
CONSTRAINT pk_employee PRIMARY KEY (userID, storeID),
CONSTRAINT fk_employee_userID FOREIGN KEY (userID) REFERENCES USER(userID),
CONSTRAINT fk_employee_storeID FOREIGN KEY (storeID) REFERENCES STORE(storeID)
);


-- store manager table
CREATE TABLE MANAGER(
userID int NOT NULL,
storeID int NOT NULL,
CONSTRAINT pk_manager PRIMARY KEY (userID, storeID),
CONSTRAINT fk_manager_userID FOREIGN KEY (userID) REFERENCES USER(userID),
CONSTRAINT fk_manager_storeID FOREIGN KEY (storeID) REFERENCES STORE(storeID)
);

-- purchases table
CREATE TABLE PURCHASE(
vehicleID varchar(8) NOT NULL,
userID int NOT NULL,
date DATE,
price int NOT NULL,
CONSTRAINT pk_purchase PRIMARY KEY (vehicleID, userID),
CONSTRAINT fk_purchases_userID FOREIGN KEY (userID) REFERENCES USER(userID),
CONSTRAINT fk_purchases_vehicleID FOREIGN KEY (vehicleID) REFERENCES VEHICLE(vehicleID)
);
