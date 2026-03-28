# UCCD3243-CoCurricular-System
make a cocurricular system 
# Student Co-curricular Management System 🎓

> **Course:** UCCD3243 Server-Side Web Applications Development (Session 202602)
> **Institution:** Universiti Tunku Abdul Rahman (UTAR)
> **Architecture:** 3-Tier Architecture (PHP & MySQL)

## 📖 Project Overview
The **Student Co-curricular Management System** is a centralized web-based application designed to help university students record and manage their co-curricular activities. Developed using Vanilla PHP and MySQL, the system features a robust relational database (7-table schema with bridge entities) and secure session-based authentication. 

It allows students to seamlessly track their event participation, club memberships, merit contribution hours, and personal achievements all in one place.

## 👥 Team Members & Module Distribution
This project is collaboratively developed by a team of 4 members, with each member taking full responsibility for the full-stack development (CRUD operations) of their respective modules:

| Name | Assigned Module | Module Description |
| :--- | :--- | :--- |
| **Chew Sai Hou**|  **Achievement Tracker** | Manages records of awards, certificates, and recognition. |
| [Chia Tze Wei] | **Event Tracker** | Manages participation in university events, workshops, or talks. |
| [Chuah Jia Jian] |**Club Tracker** | Manages club/society affiliations and leadership roles. |
| [Beh Jin Yong] | **Merit Tracker** | Records contribution hours through volunteering or services. |

*Note: The Centralized User Authentication (Login/Register) and the core Database Architecture (ERD) were developed collaboratively as a group effort.*

## 🛠️ System Requirements
* **Local Server:** XAMPP (Apache)
* **Database:** MySQL / MariaDB
* **Language:** PHP 7.4 or higher
* **Browser:** Chrome, Edge, or Firefox (Cookie and Session enabled)

## 🚀 How to Run the Project Locally (XAMPP)

Please follow these instructions to deploy the web application on your local machine:

### Step 1: Clone the Repository
1. Open your XAMPP installation folder and navigate to the `htdocs` directory (e.g., `C:\xampp\htdocs\`).
2. Clone this repository or extract the project ZIP file into a new folder named `co-curricular-system`.

### Step 2: Start the Server
1. Launch the **XAMPP Control Panel**.
2. Click **Start** for both **Apache** and **MySQL** modules.

### Step 3: Database Setup
1. Open your web browser and go to `http://localhost/phpmyadmin/`.
2. Click on **New** in the left sidebar to create a new database. Name it `co_curricular_db` (or the name specified in the config file) and select `utf8mb4_general_ci` as the collation.
3. Select the newly created database, go to the **Import** tab at the top.
4. Click **Choose File** and select the `.sql` file located in the `database/` folder of this project.
5. Click **Import** at the bottom to generate all 7 relational tables.

### Step 4: Access the Application
1. Open a new tab in your web browser.
2. Navigate to: `http://localhost/co-curricular-system/`
3. You will be redirected to the Login page. You can register a new student account to test the 4 modules!

---
*Developed with ❤️ for UCCD3243 Group Assignment.*
